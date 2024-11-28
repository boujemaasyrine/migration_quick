<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 09/02/2016
 * Time: 17:20
 */

namespace AppBundle\Merchandise\Controller;

use AppBundle\Merchandise\Entity\Delivery;
use AppBundle\Merchandise\Entity\DeliveryTmp;
use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Form\DeliveryType;
use AppBundle\Security\RightAnnotation;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DeliveryController
 *
 * @package           AppBundle\Merchandise\Controller
 * @Route("delivery")
 */
class DeliveryController extends Controller
{
    /**
     * @param Request $request
     * @param $tmp
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/new/{tmp}",name="delivery_entry",options={"expose"=true})
     * @RightAnnotation("delivery_entry")
     */
    public function deliveryEntryAction(Request $request, DeliveryTmp $tmp = null)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        if ($request->getMethod() == 'POST') {
            $delivery = new Delivery();
            $form = $this->createForm(
                DeliveryType::class,
                $delivery,
                array(
                    'restaurant' => $currentRestaurant,
                )
            );
            $form->handleRequest($request);
            if ($form->isValid()) {
                $delivery->setEmployee($this->getUser());
                $delivery->setDeliveryBordereau($form->get('prefix-num')->getData().$delivery->getDeliveryBordereau());
                $delivery->setOriginRestaurant($currentRestaurant);

                if ($request->query->has('download')) {
                    $file = $this->get('delivery.service')->generateBonOrder($delivery);
                    $response = Utilities::createFileResponse(
                        $file,
                        'delivery_'.$delivery->getDeliveryBordereau().'.pdf'
                    );

                    return $response;
                }

                $r = $this->get('delivery.service')->createDelivery($delivery,$currentRestaurant);
                if ($r) {
                    $this->get('session')->getFlashBag()->add('success', 'delivery_create_success');
                    $this->get('delivery.service')->UpdateMFCforDelivery($delivery);
                } else {
                    $this->get('session')->getFlashBag()->add('error', 'delivery_create_fail');
                }

                if ($tmp && ($tmp->getOrder() == $delivery->getOrder())) {
                    $this->getDoctrine()->getManager()->remove($tmp);
                    $this->getDoctrine()->getManager()->flush();
                }

                return $this->get('workflow.service')->nextStep($this->redirectToRoute("delivered_list"));
            }
        } else //GET
        {
            //Getting xml files for ftp
            $this->get('delivery.integration.service')->createTmpDeliveries($currentRestaurant);

            if ($tmp) {
                $delivery = $this->get('delivery.integration.service')->convertDeliveryTmpToDelivery($tmp);
                $delivery->setDate(new \DateTime('NOW'));
                $delivery->setOriginRestaurant($currentRestaurant);
                $num = $tmp->getDeliveryBordereau();
                $delivery->setDeliveryBordereau(substr($num, 9));
                $form = $this->createForm(
                    DeliveryType::class,
                    $delivery,
                    array(
                        'restaurant' => $currentRestaurant,
                    )
                );
                $form->get('prefix-num')->setData(substr($num, 0, 9));
                foreach ($form->get('lines') as $l) {
                    $l->get('product_id')->setData($l->getData()->getProduct()->getExternalId());
                }
            } else {
                $delivery = new Delivery();
                $delivery->setDate(new \DateTime('NOW'));
                $order = $this->getDoctrine()->getRepository("Merchandise:Order")->getOrderWithTheSoonerDelivery(
                    $currentRestaurant
                );
                if (!$order) {
                    $this->get('session')->getFlashBag()->add('warning', 'no_order_found');
                }
                $form = $this->createForm(
                    DeliveryType::class,
                    $delivery,
                    array(
                        'restaurant' => $currentRestaurant,
                    )
                );
                $form->get('prefix-num')->setData(date('y-m-d')."-");
            }
        }

        return $this->render(
            "@Merchandise/Delivery/new.html.twig",
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/delivered_list",name="delivered_list")
     * @RightAnnotation("delivered_list")
     */
    public function deliveredListAction()
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $lastMonth = date('d/m/Y', mktime(0, 0, 0, date('m'), intval(date('d')) - 30, date('Y')));
        $suppliers = $this->getDoctrine()->getRepository("Merchandise:Supplier")->getSuppliersByRestaurant(
            $currentRestaurant,
            true
        );

        return $this->render(
            "@Merchandise/Delivery/delivered_list.html.twig",
            array(
                'lastMonth' => $lastMonth,
                'suppliers' => $suppliers,
            )
        );
    }

    /**
     * @param Request  $request
     * @param Delivery $delivery
     * @return JsonResponse
     * @Route("/json/delivery_detail/{delivery}",name="delivery_details",options={"expose"=true})
     */
    public function deliveryDetailJsonAction(Request $request, Delivery $delivery)
    {

        return new JsonResponse(
            array(
                'data' => $this->renderView(
                    "@Merchandise/Delivery/modals/details_delivery.html.twig",
                    array(
                        'delivery' => $delivery,
                    )
                ),
            )
        );
    }

    /**
     * @param Delivery $delivery
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     * @Route("/print/{delivery}",name="print_delivery",options={"expose"=true})
     */
    public function printBlAction(Delivery $delivery)
    {
        $filename = "delivery_".date('Y_m_d_H_i_s').".pdf";

        $filepath = $this->get("delivery.service")->generateBonOrder($delivery);

        $response = Utilities::createFileResponse($filepath, $filename);

        return $response;
    }

    /**
     *
     * * ** Integration BL ****
     */

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/new_create_test_xml",name="new_create_test_xml",options={"expose"=true})
     */
    public function deliveryEntryCreateTestXMLAction(Request $request)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $this->get('session')->getFlashBag()->add(
            'info',
            $this->get('translator')->trans('delivery.test.warning')
        );
        $xml = null;
        if ($request->getMethod() == 'POST') {
            $delivery = new Delivery();
            $delivery->setOriginRestaurant($currentRestaurant);
            $form = $this->createForm(
                DeliveryType::class,
                $delivery,
                array(
                    'restaurant' => $currentRestaurant,
                )
            );
            $form->handleRequest($request);
            if ($form->isValid()) {
                $delivery->setEmployee($this->getUser());
                $delivery->setDeliveryBordereau($form->get('prefix-num')->getData().$delivery->getDeliveryBordereau());

                $xml = $this->renderView(
                    "@Merchandise/Delivery/integration/delivery_test.xml.test.html.twig",
                    array(
                        'delivery' => $delivery,
                        'xml' => $xml,
                    )
                );

                $deposed = $this->deposeXmlOnFtp($xml, $delivery->getOrder());
                if ($deposed) {
                    $this->get('session')->getFlashBag()->add('success', 'Déposé avec succès');
                } else {
                    $this->get('session')->getFlashBag()->add('error', 'Echec lors de la déposition');
                }

                //                $dls = $this->get('delivery.integration.service');
                //                $deliveryTmp = $dls->createDeliveryTmpFromXml($delivery->getOrder(),$xml);
                //                $deliveryTmp->setDate($delivery->getDate())
                //                    ->setDeliveryBordereau($delivery->getDeliveryBordereau())
                //                    ->setValorization($delivery->getValorization());
                //                $this->getDoctrine()->getManager()->persist($deliveryTmp);
                //                $this->getDoctrine()->getManager()->persist($deliveryTmp);
                //                $this->getDoctrine()->getManager()->flush();
            }
        } else //GET
        {
            $delivery = new Delivery();
            $delivery->setDate(new \DateTime('NOW'));
            $delivery->setOriginRestaurant($currentRestaurant);
            $order = $this->getDoctrine()->getRepository("Merchandise:Order")->getOrderWithTheSoonerDelivery(
                $currentRestaurant
            );
            if (!$order) {
                $this->get('session')->getFlashBag()->add('warning', 'no_order_found');
            }
            $form = $this->createForm(
                DeliveryType::class,
                $delivery,
                array(
                    'restaurant' => $currentRestaurant,
                )
            );
            $form->get('prefix-num')->setData(date('y-m-d')."-");
        }

        return $this->render(
            "@Merchandise/Delivery/integration/new_test.html.twig",
            [
                'form' => $form->createView(),
                'xml' => $xml,
            ]
        );
    }

    /**
     * @return Response
     * @Route("/pending_list",name="pending_delievries_list")
     */
    public function pendingDeliveriesAction()
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $this->get('delivery.integration.service')->createTmpDeliveries($currentRestaurant);
        $deliveries = $this->getDoctrine()->getRepository("Merchandise:DeliveryTmp")->findAll();

        return $this->render(
            "@Merchandise/Delivery/integration/pending_delivered_list.html.twig",
            array(
                'deliveries' => $deliveries,
            )
        );
    }

    /**
     * @param Order $order
     * @return JsonResponse
     * @Route("check_delivery_tmp/{order}",name="check_delivery_tmp",options={"expose"=true})
     */
    public function checkDeliveryTmp(Order $order)
    {
        $deliv = $this->get('delivery.integration.service')->checkDeliveryTmpExistenceForOrder($order);
        if ($deliv) {
            return new JsonResponse(
                array(
                    'data' => array(
                        'exist' => true,
                        'tmp_id' => $deliv->getId(),
                    ),
                )
            );
        } else {
            return new JsonResponse(
                array(
                    'data' => array(
                        'exist' => false,
                    ),
                )
            );
        }
    }

    /**
     * @Route("/test_moving_file/{order}")
     */
    public function test(Order $order)
    {

        $dls = $this->get('delivery.integration.service');

        $ftpHost = $this->container->getParameter('ftp_host');
        $ftpUser = $this->container->getParameter('ftp_user');
        $ftpPw = $this->container->getParameter('ftp_pw');
        $ftpPort = $this->container->getParameter('ftp_port');

        $found = $dls->checkDeliveryTicketAvailabiltiyForAnOrder($order);

        if ($found != false) {
            $path = $this->container->getParameter('tmp_directory')."/".basename($found);
            $moved = Utilities::moveFileFromFtpToPath($found, $path, $ftpHost, $ftpPort, $ftpUser, $ftpPw);
            $x = $dls->createDeliveryTmpFromXml($order, file_get_contents($path));
        } else {
        }

        die;

        return new Response('');
    }

    private function deposeXmlOnFtp($xml, $order)
    {
        $ftpHost = $this->container->getParameter('ftp_host');
        $ftpUser = $this->container->getParameter('ftp_user');
        $ftpPw = $this->container->getParameter('ftp_pw');
        $ftpPort = $this->container->getParameter('ftp_port');
        $filename = $this->container->getParameter('tmp_directory')."/D_".$order->getNumOrder().".xml";

        file_put_contents($filename, $xml);

        return Utilities::sendFileToFtp($filename, $ftpHost, $ftpPort, $ftpUser, $ftpPw);
    }
}
