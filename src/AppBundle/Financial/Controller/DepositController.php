<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 26/04/2016
 * Time: 17:42
 */

namespace AppBundle\Financial\Controller;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\Deposit;
use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Form\Deposit\DepositTicketType;
use AppBundle\Financial\Form\Deposit\DepositType;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Security\RightAnnotation;

/**
 * Class ConsultationsController
 *
 * @Route("deposit")
 */
class DepositController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/cash",name="deposit_cash", options={"expose"=true})
     *
     * @RightAnnotation("deposit_cash")
     *
     * @throws \Exception
     */
    public function depositCashAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();

        $envelopes = $em->getRepository('Financial:Envelope')->findBy(
            array(
                'status' => Envelope::NOT_VERSED,
                'type' => Envelope::TYPE_CASH,
                'originRestaurant' => $currentRestaurant,
            ),
            ['createdAt' => 'asc']
        );

        $deposit = new Deposit();
        $deposit->setTotalAmount($this->get('envelope.service')->getTotalEnvelopeNotVersed(Envelope::TYPE_CASH));

        $form = $this->createForm(DepositType::class, $deposit);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $response = new JsonResponse();
            if ($form->isValid()) {
                $deposit->setOwner($this->getUser());
                $deposit->setType(Deposit::TYPE_CASH);
                $deposit->setSousType(Deposit::TYPE_CASH);
                $this->get('deposit.service')->saveDepositEnvelope($deposit, $envelopes);
                $envelopes = $this->get('envelope.service')->getEnvelopesDeposit($deposit);
                $period = $this->get('deposit.service')->getPeriod($deposit->getId());

                $typeLabel = $this->get('paremeter.service')->getCashLabel('cash_payment');
                $data = [
                    "data" => [
                        $this->renderView(
                            '@Financial/Deposit/Report/exports/body.html.twig',
                            [
                                'deposit' => $deposit,
                                'envelopes' => $envelopes,
                                'period' => $period,
                                'typeLabel' => $typeLabel,
                            ]
                        ),
                        "footer" => $this->renderView(
                            '@Financial/Deposit/Cash/parts/modal_footer.html.twig',
                            [
                                'deposit' => $deposit,
                            ]
                        ),
                        "header" => $this->renderView(
                            '@Financial/Deposit/Cash/parts/modal_header.html.twig',
                            [
                                'deposit' => $deposit,
                                'period' => $period,
                            ]
                        ),
                    ],
                ];
            } else {
                $data = [
                    "data" => [
                        $this->render(
                            '@Financial/Deposit/Cash/parts/form_container.html.twig',
                            array(
                                'form' => $form->createView(),
                            )
                        ),
                    ],
                    "errors" => [],
                ];
            }
            $response->setData($data);

            return $this->get('workflow.service')->nextStep($response);
        }

        return $this->render(
            '@Financial/Deposit/Cash/index.html.twig',
            array(
                'form' => $form->createView(),
                'envelopes' => $envelopes,
                'deposit' => $deposit,
            )
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/ticket",name="deposit_ticket", options={"expose"=true})
     *
     * @RightAnnotation("deposit_ticket")
     *
     * @throws \Exception
     */
    public function depositTicketAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $deposit = new Deposit();
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();

        if ($request->isXmlHttpRequest()) {
            $form = $this->createForm(DepositTicketType::class, $deposit);
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $envelopes = $em->getRepository('Financial:Envelope')->getEnvelopesCriteria(
                        Envelope::TYPE_TICKET,
                        Envelope::NOT_VERSED,
                        $deposit->getSousType(),
                        $currentRestaurant
                    );
                    $deposit->setTotalAmount(
                        $this->get('envelope.service')->getTotalEnvelopeNotVersed(
                            Envelope::TYPE_TICKET,
                            $deposit->getSousType()
                        )
                    );
                    $deposit->setType(Envelope::TYPE_TICKET);
                    $deposit->setOwner($this->getUser());

                    $this->get('deposit.service')->saveDepositEnvelope($deposit, $envelopes);
                    $envelopes = $this->get('envelope.service')->getEnvelopesDeposit($deposit);
                    $period = $this->get('deposit.service')->getPeriod($deposit->getId());
                    $data = [
                        "data" => [
                            $this->renderView(
                                '@Financial/Deposit/Report/exports/body.html.twig',
                                [
                                    'deposit' => $deposit,
                                    'envelopes' => $envelopes,
                                    'period' => $period,
                                    'typeLabel' => $this->get('paremeter.service')->getTicketRestaurantLabel(
                                        $deposit->getSousType()
                                    ),
                                ]
                            ),
                            "footer" => $this->renderView(
                                '@Financial/Deposit/Ticket/parts/modal_footer.html.twig',
                                [
                                    'deposit' => $deposit,
                                ]
                            ),
                            "header" => $this->renderView(
                                '@Financial/Deposit/Ticket/parts/modal_header.html.twig',
                                [
                                    'deposit' => $deposit,
                                    'period' => $period,
                                ]
                            ),
                        ],
                    ];
                } else {
                    $data = [
                        "data" => [
                            $this->renderView(
                                '@Financial/Deposit/Ticket/parts/form_container.html.twig',
                                array(
                                    'deposit' => $deposit,
                                    'form' => $form->createView(),
                                )
                            ),
                        ],
                        "errors" => [],
                    ];
                }
            } else {
                $type = $request->get('deposit_ticket')['sousType'];
                $total = $this->get('envelope.service')->getTotalEnvelopeNotVersed(Envelope::TYPE_TICKET, $type);
                $affiliateCode = $this->get('paremeter.service')->getTicketAffiliateCode($type);
                $envelopes = $this->get('envelope.service')->getEnvelopesCriteria(
                    Envelope::TYPE_TICKET,
                    Envelope::NOT_VERSED,
                    $type
                );
                $data = [
                    "data" => ["total" => $total, "envelopes" => $envelopes, "affiliateCode" => $affiliateCode],
                ];
            }

            return new JsonResponse($data);
        }

        $form = $this->createForm(DepositTicketType::class, $deposit);

        $envelopes = $this->get('envelope.service')->getEnvelopesCriteria(Envelope::TYPE_TICKET, Envelope::NOT_VERSED);

        return $this->render(
            '@Financial/Deposit/Ticket/index.html.twig',
            array(
                'form' => $form->createView(),
                'envelopes' => $envelopes,
                'deposit' => $deposit,
            )
        );
    }


    /**
     * @param Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/report/{id}",name="deposit_report")
     *
     * @throws \Exception
     */
    public function depositCashReportAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $deposit = $em->getRepository('Financial:Deposit')->find($id);
        if (null == $deposit) {
            throw new NotFoundHttpException(sprintf('No deposit found with this id: %s.', $id));
        }
        $period = $this->get('deposit.service')->getPeriod($id);

        $envelopes = $this->get('envelope.service')->getEnvelopesDeposit($deposit);
        if ($deposit->getType() === Deposit::TYPE_CASH) {
            $typeLabel = null;
            $cashLabels = $this->get('paremeter.service')->getCashLabels();
            foreach ($cashLabels as $cash) {
                /**
                 * @var Parameter $cash
                 */
                if ($cash->getValue() === 'cash_payment') {
                    $typeLabel = $cash->getLabel();
                }
            }
        } else {
            $typeLabel = $this->get('paremeter.service')->getTicketRestaurantLabel($deposit->getSousType());
        }

        if (!is_null($request->get('download', null))) {
            $start = new \DateTime($period['startDate']);
            $end = new \DateTime($period['endDate']);

            $filename = $this->get('translator')->trans('deposit.report.title').'__'.$start->format('d_m_Y').'__'.$end->format('d_m_Y').".pdf";
            $filePath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                $filename,
                '@Financial/Deposit/Report/exports/export.html.twig',
                [
                    'deposit' => $deposit,
                    'period' => $period,
                    'envelopes' => $envelopes,
                    'typeLabel' => $typeLabel,
                    "download" => true,
                ],
                [
                    'orientation' => 'Portrait',
                ],
                true
            );

            return Utilities::createFileResponse($filePath, $filename);
        }

        return $this->render(
            '@Financial/Deposit/Report/report.html.twig',
            array(
                'deposit' => $deposit,
                'envelopes' => $envelopes,
                'period' => $period,
                'typeLabel' => $typeLabel,
            )
        );
    }


    /**
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/redirect/{source}",name="deposit_redirect")
     */
    public function depositRedirectAction()
    {

        return $this->get('workflow.service')->nextStep($this->redirect($this->generateUrl('expenses_list')));
    }

    /**
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     *
     * @Route("/electronic/{id}",name="deposit_electronic", options={"expose"=true})
     */
    public function depositBankElectronicAction($id)
    {

        if ($this->get('workflow.service')->inAdministrativeClosing()) {
            $this->get('administrative.closing.service')->resetInChestCount();
            $this->get('workflow.service')->setSubStep(4);

            $em = $this->getDoctrine()->getManager();
            $smallChest = $em->getRepository('Financial:ChestSmallChest')->find($id);
            if (null == $smallChest) {
                throw new NotFoundHttpException('Small chest not found.');
            }

            $result = array();

            if (!$smallChest->isElectronicDeposed() && $smallChest->getChestCount()->isClosure()) {
                try {
                    $em->beginTransaction();

                    $result['card'] = $this->get('deposit.service')->depositElectronic(
                        $smallChest,
                        Deposit::TYPE_BANK_CARD
                    );
                    $result['ticket'] = $this->get('deposit.service')->depositElectronic(
                        $smallChest,
                        Deposit::TYPE_E_TICKET
                    );

                    $smallChest->setElectronicDeposed(true);
                    $em->flush();

                    $em->commit();
                } catch (\Exception $e) {
                    $em->rollback();
                    throw new \Exception($e);
                }
            } elseif ($smallChest->isElectronicDeposed() && $smallChest->getChestCount()->isClosure()) {
                $result['card'] = $this->get('deposit.service')->getDepositElectronic(
                    $smallChest,
                    Deposit::TYPE_BANK_CARD
                );
                $result['ticket'] = $this->get('deposit.service')->getDepositElectronic(
                    $smallChest,
                    Deposit::TYPE_E_TICKET
                );
            }

            return $this->render(
                '@Financial/Deposit/Electronic/index.html.twig',
                array(
                    'smallChest' => $smallChest,
                    'result' => $result,
                )
            );
        } else {
            throw new NotFoundHttpException('Page not found.');
        }
    }

}
