<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/02/2016
 * Time: 10:10
 */

namespace AppBundle\Merchandise\Controller;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Transfer;
use AppBundle\Merchandise\Form\TransferInType;
use AppBundle\Merchandise\Form\TransferOutType;
use AppBundle\Merchandise\Service\TransferService;
use AppBundle\Security\RightAnnotation;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TransferController
 *
 * @package            AppBundle\Merchandise\Controller
 * @Route("/transfer")
 */
class TransferController extends Controller
{

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/new_transfer_in",name="new_transfer_in")
     * @RightAnnotation("new_transfer_in")
     */
    public function newTransferInAction(Request $request)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $transfer = new Transfer();
        $transfer->setType(Transfer::TRANSFER_IN)
            ->setDateTransfer(new \DateTime('NOW'));
        $form = $this->createForm(
            TransferInType::class,
            $transfer,
            array(
                "current_restaurant" => $currentRestaurant,
            )
        );
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $transfer->setEmployee($this->getUser());
                $transfer->setOriginRestaurant($currentRestaurant);
                $created = $this->get('transfer.service')->createTransferIn($transfer, $currentRestaurant);

                if ($created) {
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $this->get('translator')->trans(
                            'transfer_created_with_success',
                            ['%id_transfer%' => $transfer->getNumTransfer()]
                        )
                    );
                    /**
                     * @var $ts TransferService
                     */
                    $ts = $this->get('transfer.service');
                    $sended = $ts->notifyRestaurant($transfer, $currentRestaurant);
                    $this->get('transfer.service')->UpdateMFCforTransfer($transfer);
                    if (!$sended) {
                        $this->get('session')->getFlashBag()->add('error', "send_mail_fail");
                    }
                    $newTransfer = new Transfer();
                    $newTransfer->setType(Transfer::TRANSFER_IN);
                    $form = $this->createForm(
                        TransferInType::class,
                        $newTransfer,
                        array(
                            "current_restaurant" => $currentRestaurant,
                        )
                    );
                    return $this->render(
                        "@Merchandise/Transfer/new_transfer.html.twig",
                        array(
                            'form' => $form->createView(),
                            'transfer' => $transfer,
                        )
                    );
                } else {
                    $this->get('session')->getFlashBag()->add('success', 'transfer_created_with_fail');
                }
                return $this->get('workflow.service')->nextStep($this->redirectToRoute('list_transfer'));
            }
        }

        return $this->render(
            "@Merchandise/Transfer/new_transfer.html.twig",
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/new_transfer_out",name="new_transfer_out")
     * @RightAnnotation("new_transfer_out")
     */
    public function newTransferOutAction(Request $request)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $transfer = new Transfer();
        $transfer->setType(Transfer::TRANSFER_OUT)
            ->setDateTransfer(new \DateTime('NOW'));
        $form = $this->createForm(
            TransferOutType::class,
            $transfer,
            array(
                "current_restaurant" => $currentRestaurant,
            )
        );
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $transfer->setEmployee($this->getUser());
                $transfer->setOriginRestaurant($currentRestaurant);
                $created = $this->get('transfer.service')->createTransferOut($transfer, $currentRestaurant);

                if ($created) {
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $this->get('translator')->trans(
                            'transfer_created_with_success',
                            ['%id_transfer%' => $transfer->getNumTransfer()]
                        )
                    );

                    /**
                     * @var $ts TransferService
                     */
                    $ts = $this->get('transfer.service');
                    $sended = $ts->notifyRestaurant($transfer, $currentRestaurant);
                    $this->get('transfer.service')->UpdateMFCforTransfer($transfer);
                    if (!$sended) {
                        $this->get('session')->getFlashBag()->add('error', "send_mail_fail");
                    }
                    $form = $this->createForm(
                        TransferOutType::class,
                        new Transfer(),
                        array(
                            "current_restaurant" => $currentRestaurant,
                        )
                    );
                    return $this->render(
                        "@Merchandise/Transfer/new_transfer.html.twig",
                        array(
                            'form' => $form->createView(),
                            'transfer' => $transfer,
                        )
                    );
                } else {
                    $this->get('session')->getFlashBag()->add('success', 'transfer_created_with_fail');
                }
                return $this->get('workflow.service')->nextStep($this->redirectToRoute('list_transfer'));
            }
        }

        return $this->render(
            "@Merchandise/Transfer/new_transfer.html.twig",
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/list",name="list_transfer",options={"expose"=true})
     * @RightAnnotation("list_transfer")
     */
    public function showListAction()
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $transfers = $this->getDoctrine()->getRepository(Transfer::class)->findBy(
            array(
                "originRestaurant" => $currentRestaurant,
            )
        );
        $restaurants = $this->getDoctrine()->getRepository(Restaurant::class)
            ->createQueryBuilder('r')
            ->where('r != :restaurant')
            ->setParameter('restaurant', $currentRestaurant)
            ->getQuery()
            ->getResult();


        return $this->render(
            "@Merchandise/Transfer/list.html.twig",
            array(
                'transfers' => $transfers,
                'restaurants' => $restaurants,
            )
        );
    }

    /**
     * @param Transfer $transfer
     * @return JsonResponse
     * @Route("/details/{transfer}",name="transfer_details",options={"expose"=true})
     */
    public function detailsAction(Transfer $transfer)
    {

        return new JsonResponse(
            array(
                'data' => $this->renderView(
                    "@Merchandise/Transfer/modal/details.html.twig",
                    array(
                        'transfer' => $transfer,
                    )
                ),
                'footer' => $this->renderView(
                    "@Merchandise/Transfer/modal/footer.html.twig",
                    array(
                        'transfer' => $transfer,
                    )
                ),
            )
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/json/transfer_list",name="transfer_list",options={"expose"=true})
     */
    public function getJsonListAction(Request $request)
    {
        $dataTable = Utilities::getDataTableHeader($request, ['num', 'type', 'restaurant', 'date', 'val']);
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $list = $this->getDoctrine()->getRepository(Transfer::class)
            ->getList(
                $currentRestaurant,
                $dataTable['criteria'],
                $dataTable['orderBy'],
                $dataTable['offset'],
                $dataTable['limit']
            );

        return new JsonResponse(
            array(
                'data' => $this->get('transfer.service')->serializeTransferList($list['list']),
                'draw' => $dataTable['draw'],
                'recordsTotal' => $list['total'],
                'recordsFiltered' => $list['filtred'],
            )
        );
    }

    /**
     * @Route("/download/{type}",name="download_transfers_file",options={"expose"=true})
     */
    public function DownloadFileAction(Request $request, $type = 1)
    {

        $dataTableHeaders = Utilities::getDataTableHeader(
            $request,
            ['num', 'restaurant', 'date', 'responsible', 'type']
        );
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        if ($type === 1) {
            $translator = $this->get('translator');
            $filepath = $this->get('toolbox.document.generator')
                ->generateCsvFile(
                    'transfer.service',
                    'getList',
                    array(
                        'currentRestaurant' => $currentRestaurant,
                        'criteria' => $dataTableHeaders['criteria'],
                        'order' => $dataTableHeaders['orderBy'],
                    ),
                    ["NUM Transfert", "DATE", "RESTAURANT", "TYPE", "RESPONSABLE", "VALORISATION (EURO)"],
                    function ($line) use ($translator) {
                        return array(
                            $line['num'],
                            $line['date'],
                            $line['restaurant'],
                            $translator->trans($line['type']),
                            $line['responsible'],
                            $line['val'],
                        );
                    }
                );

            $response = Utilities::createFileResponse($filepath, 'transferts_' . date('dmY_His') . ".csv");

            return $response;
        }
        $logoPath = $this->get('kernel')->getRootDir() . '/../web/src/images/logo.png';
        $response = $this->get('transfer.service')->genreateExcelFile(
            $currentRestaurant,
            $dataTableHeaders['criteria'],
            $dataTableHeaders['orderBy'],
            $logoPath
        );

        return $response;
    }

    /**
     * @param Transfer $transfer
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     * @Route("/print/{transfer}",name="print_transfer",options={"expose"=true})
     */
    public function printBonAction(Transfer $transfer)
    {
        $filename = "transfer_" . date('Y_m_d_H_i_s') . ".pdf";

        $filepath = $this->get("transfer.service")->generateBon($transfer);

        $response = Utilities::createFileResponse($filepath, $filename);

        return $response;
    }

    /**
     * @param Transfer $transfer
     * @return JsonResponse
     * @Route("/delete/{transfer}",name="delete_transfer",options={"expose"=true})
     */
    public function deleteAction(Transfer $transfer)
    {
        $result = $this->get('transfer.service')->deleteTransfer($transfer);

        if ($result['deleted'] == true) {
            $message = $this->get('translator')->trans('transfers.delete.success');
            if ($transfer->getMailSent() == true) {
                $this->get('transfer.service')->notifyDeleteTransferRestaurant($transfer);
            }
            $this->get('session')->getFlashBag()->add('success', $message);
        }

        return new JsonResponse(
            array(
                'data' => $result,
            )
        );
    }
}
