<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 28/03/2016
 * Time: 11:13
 */

namespace AppBundle\Financial\Controller;

use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\Financial\Service\WithdrawalSynchronizationService;
use AppBundle\Security\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Financial\Form\Withdrawal\EnvelopeType;
use AppBundle\Financial\Form\Withdrawal\WithdrawalSearchType;
use AppBundle\Financial\Form\Withdrawal\WithdrawalType;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use AppBundle\ToolBox\Utils\Utilities;

/**
 * Class FundManagementController
 *
 * @Route("fund_management")
 */
class FundManagementController extends Controller
{

    /**
     * @param Request $request
     * @param String $validate
     * @param Withdrawal $withdrawal
     *
     * @RightAnnotation ("withdrawal_entry")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/withdrawalEntry",name="withdrawal_entry",options={"expose"=true})
     *
     * @throws \Exception
     */
    public function entryWithdrawalAction()
    {
        $responsible = $this->getUser();
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        /**
         * @var WithdrawalSynchronizationService $wss
         */
        $wss = $this->get('withdrawal.synchronization.service');
        $lud = $wss->getLatestUpdateDateFromApi($currentRestaurant);
        $df = $this->get('administrative.closing.service')->getLastWorkingEndDate();
        $lud = is_bool($lud) ? $df : $lud;
        $startDate = new \DateTime($df->format('j-m-Y'));
        $endDate = new \DateTime($df->format('j-m-Y'));
        $wss->synchApiWithdrawalTmp($currentRestaurant, $startDate, $endDate);
        $iw = $wss->getInvalidWithdrawalsTmp(null, $currentRestaurant);

        $criteria = ['restaurant' => $currentRestaurant,
            'withdrawal_search[startDate' => $df->format('j/m/Y'),
            'withdrawal_search[endDate' => $df->format('j/m/Y')];
        $previousAmount = $this->getDoctrine()->getRepository("Financial:Withdrawal")
            ->getWithdrawalsFiltredOrdered($criteria, null, null, null, true);


        return $this->render(
            "@Financial/FundManagement/Withdrawal/entry.html.twig",
            [
                'invalidWithdrawalsTmp' => $iw,
                'previousAmount' => $previousAmount,
                'date' => $lud,
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route("/withdrawalsJsonList/{download}/{pdf}",name="withdrawals_json_list", options={"expose"=true})
     *
     * @throws \Exception
     */
    public function withdrawalsJsonListAction(Request $request, $download = 0, $pdf = 0)
    {
        $orders = array('responsible', 'member', 'date', 'amount', 'status');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);
        $dataTableHeaders['criteria']['restaurant'] = $this->get('restaurant.service')->getCurrentRestaurant();

        $download = intval($download);
        $pdf = intval($pdf);

        if (1 === $download) {
            $fileName = 'Prelevement' . date('dmY_His');
            $response = $this->get('toolbox.document.generator')
                ->generateXlsFile(
                    'withdrawal.service',
                    'getWithdrawals',
                    array(
                        'criteria' => $dataTableHeaders['criteria'],
                        'order' => $dataTableHeaders['orderBy'],
                        'onlyList' => true,

                    ),
                    $this->get('translator')->trans('fund_management.withdrawal.list.title'),
                    [
                        $this->get('translator')->trans('label.manager'),
                        $this->get('translator')->trans('label.member'),
                        $this->get('translator')->trans('keyword.date'),
                        $this->get('translator')->trans('keyword.amount'),
                        $this->get('translator')->trans('label.status'),
                    ],
                    function ($line) {
                        return [
                            $line['responsible'],
                            $line['member'],
                            $line['date'],
                            number_format($line['amount'], 2, '.', ''),
                            $line['status'],
                        ];
                    },
                    $fileName
                );

            //            $response = Utilities::createFileResponse($filepath, 'Prélèvement' . date('dmY_His') . ".csv");
            return $response;
        }

        $withdrawals = $this->getDoctrine()->getRepository("Financial:Withdrawal")->getWithdrawalsFiltredOrdered(
            $dataTableHeaders['criteria'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit']
        );
        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $withdrawals['filtred'];
        $return['recordsTotal'] = $withdrawals['total'];
        $return['data'] = $this->get('withdrawal.service')->serializeWithdrawals($withdrawals['list']);
        $return['criteria'] = $this->get('withdrawal.service')->getCriteria($dataTableHeaders['criteria']);
        if (1 === $pdf) {
            $title = $this->get('translator')->trans('fund_management.withdrawal.list.report_title') . ' ' . date(
                    'Y_m_d_H_i_s'
                ) . ".pdf";
            $filePath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                'withdrawal.list' . date('Y_m_d_H_i_s') . ".pdf",
                '@Financial/FundManagement/Withdrawal/exports/export.html.twig',
                [
                    'return' => $return,
                ],
                [
                    'orientation' => 'Portrait',
                ],
                true
            );

            return Utilities::createFileResponse($filePath, $title);
        }

        return new JsonResponse($return);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/envelopeEntry/{withdrawal}/{envelope}",name="envelope_entry",options={"expose"=true})
     */
    public function entryEnvelopeAction(Request $request, $withdrawal, Envelope $envelope = null)
    {

        if (null == $envelope) {
            $envelope = new Envelope();
        }

        $form = $this->createForm(EnvelopeType::Class, $envelope);
        $response = new JsonResponse();

        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            if ("POST" === $request->getMethod()) {
                try {
                    $form->handleRequest($request);
                    if ($form->isValid()) {
                        $this->get('withdrawal.service')->saveEnvelope($envelope, $withdrawal);
                        $message = $this->get('translator')->trans('fund_management.withdrawal.entry.add_success');
                        $this->get('session')->getFlashBag()->add('success', $message);

                        return $response;
                    }

                    $response->setData(
                        [
                            "formError" => [
                                $this->renderView(
                                    '@Financial/FundManagement/Withdrawal/envelope.html.twig',
                                    array(
                                        'form' => $form->createView(),
                                    )
                                ),
                            ],
                        ]
                    );

                } catch (\Exception $e) {
                    $response->setData(
                        [
                            "errors" => [$this->get('translator')->trans('Error.general.internal'), $e->getMessage()],
                        ]
                    );
                }

                return $response;
            }
        }

        $response->setData(
            [
                "data" => [
                    $this->renderView(
                        '@Financial/FundManagement/Withdrawal/envelope.html.twig',
                        array(
                            'form' => $form->createView(),
                        )
                    ),
                    $this->renderView(
                        '@Financial/FundManagement/Withdrawal/btn.html.twig',
                        array(
                            'type' => 'create',
                            'withdrawal' => $withdrawal,
                        )
                    ),
                ],
            ]
        );

        return ($response);
    }

    /**
     * @RightAnnotation ("withdrawal_list")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/withdrawalList",name="withdrawal_list",options={"expose"=true})
     *
     * @throws \Exception
     */
    public function withdrawalListAction(Request $request)
    {

        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $form = $this->createForm(WithdrawalSearchType::class, null, array('restaurant' => $currentRestaurant));

        return $this->render(
            "@Financial/FundManagement/Withdrawal/list.html.twig",
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     *
     * @Route("/cancelEnvelope",name="cancel_envelope",options={"expose"=true})
     */
    public function cancelEnvelopeAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $message = $this->get('translator')->trans('fund_management.withdrawal.entry.add_success_without_envelope');
            $this->get('session')->getFlashBag()->add('success', $message);

            return $response;
        }

        throw new AccessDeniedHttpException("This method accept only ajax calls.");
    }

    /**
     * @param Request $request
     * @param Withdrawal $withdrawal
     *
     * @return JsonResponse
     *
     * @Route("/json/detailsWithdrawal/{withdrawal}",name="withdrawal_detail",options={"expose"=true})
     */
    public function detailsWithdrawalJsonAction(Request $request, Withdrawal $withdrawal)
    {

        $envelope = $this->getDoctrine()->getRepository("Financial:Envelope")->find($withdrawal->getEnvelopeId());

        return new JsonResponse(
            array(

                'data' => $this->renderView(
                    "@Financial/FundManagement/Withdrawal/modals/details.html.twig",
                    array(
                        'envelope' => $envelope,
                    )
                ),
            )
        );
    }

    /**
     * @param Request $request
     * @param Withdrawal $withdrawal
     *
     * @return JsonResponse
     *
     * @Route("/json/previousWithdrawals/{member}/{withdrawal}",name="previous_withdrawals",options={"expose"=true})
     *
     * @throws \Exception
     */
    public function previousWithdrawalsJsonAction(Request $request, User $member, $withdrawal = null)
    {
        $date = $this->get('administrative.closing.service')->getLastWorkingEndDate();
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();

        $previousWithdrawals = $this->getDoctrine()->getRepository("Financial:Withdrawal")->findTotalPreviousAmount(
            $member,
            $withdrawal,
            $date,
            $currentRestaurant
        );
        $previousWithdrawalsSerialised = $this->get('withdrawal.service')->serializeWithdrawals($previousWithdrawals);

        return new JsonResponse(
            array(
                'data' => $previousWithdrawalsSerialised,
            )
        );
    }
}
