<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 03/12/2015
 * Time: 12:04
 */

namespace AppBundle\Supervision\Controller;

use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Repository\AdministrativeClosingRepository;
use AppBundle\General\Entity\Notification;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\General\Entity\SyncCmdQueue;
use AppBundle\Security\Entity\User;
use AppBundle\Supervision\Form\Restaurant\RestaurantFilterType;
use AppBundle\Supervision\Utils\DateUtilities;
use AppBundle\Supervision\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SupervisionController
 *
 * @package    AppBundle\Controller
 * @Route("/")
 */
class SupervisionController extends Controller
{
    /**
     * @RightAnnotation("restaurant_list_super")
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/restaurant_list_super",name="restaurant_list_super",options={"expose"=true})
     * @RightAnnotation("restaurant_list_super")
     */
    public function indexAction(Request $request)
    {
        $em=$this->getDoctrine()->getManager();
        $user = $this->getUser();
        if ($user->isSuperAdmin()){
            $quicks = $this->getDoctrine()->getRepository(Restaurant::class)->findByActive(true);
        } else {
            $quicks = $user->getEligibleRestaurants();
        }
        $form = $this->createForm(RestaurantFilterType::class);

        if($request->getMethod() == "POST"){
            $form->handleRequest($request);
            if($form->isValid()){
             $data=$form->getData();
             $dateFilter=$data['date'] ->format('Y-m-d');
             $quicksFilter=array();
              foreach ($quicks as $quick){
                  $lastClosed=$this->getDoctrine()->getRepository(AdministrativeClosing::class)->getLastClosingDate($quick);
                 if($lastClosed->format('Y-m-d') == $dateFilter){
                     $quicksFilter[]=$quick;
                 }
              }
            return $this->render(
            '@Supervision/supervision/index.html.twig',
            array(
                'quicks' => $quicksFilter,
                'form' =>$form->createView(),
                'display' => true,
            )
        );

            }
        }

        return $this->render(
            '@Supervision/supervision/index.html.twig',
            array(
                'quicks' => $quicks,
                'form' =>$form->createView(),
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/supervision/details/{quick}",name="supervision_details")
     * @RightAnnotation("supervision_details")
     */
    public function detailsAction(Restaurant $quick)
    {

        $date = DateUtilities::getDateFromDate(new \DateTime('today'), -14);
        /*$detailsUpload = $this->getDoctrine()->getRepository("General:RemoteHistoric")
            ->getEntriesForRestaurantBeforeDate($quick,$date);*/


        $downloadTypes = SyncCmdQueue::getDownloadConstant();

        //$uploadTypes = RemoteHistoric::getUploadConstant();

        return $this->render(
            "@Supervision/supervision/details.html.twig",
            array(
                'quick' => $quick,
                // 'detailsUpload' => $detailsUpload,
                'downloadTypes' => $downloadTypes,
                //'uploadTypes' => $uploadTypes
            )
        );
    }

    /**
     * @param Request    $request
     * @param Restaurant $restaurant
     * @return JsonResponse
     * @Route("/remote_historic_list_json/{restaurant}",name="remote_historic_list_json", options={"expose"=true})
     */
    public function remoteHistoricListJsonAction(Request $request, Restaurant $restaurant)
    {

        $orders = array('date', 'status', 'type');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);

        $items = $this->getDoctrine()->getRepository("AppBundle:RemoteHistoric")->getList(
            $dataTableHeaders['criteria'],
            $restaurant,
            $dataTableHeaders['search'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit']
        );

        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $items['filtred'];
        $return['recordsTotal'] = $items['total'];
        $return['data'] = $this->_serializeRemoteHist($items['list']);

        return new JsonResponse($return);
    }

    /**
     * @param Request    $request
     * @param Restaurant $restaurant
     * @return JsonResponse
     * @Route("/download_sync_list_json/{restaurant}",name="download_sync_list_json", options={"expose"=true})
     */
    public function downloadSyncListJsonAction(Request $request, Restaurant $restaurant)
    {

        $orders = array('date', 'status', 'type');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);
        $currentUser = $this->getUser();
        $restaurants = $currentUser->getEligibleRestaurants()->toArray();
        $items = $this->getDoctrine()->getRepository(SyncCmdQueue::class)->getList(
            $restaurants,
            $dataTableHeaders['criteria'],
            $restaurant,
            $dataTableHeaders['search'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit']
        );

        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $items['filtred'];
        $return['recordsTotal'] = $items['total'];
        $return['data'] = $this->_serializeSyncCmd($items['list']);

        return new JsonResponse($return);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @param $request
     * @Route("/historic_broadcast_all_restaurants",name="historic_broadcast_all_restaurants",options={"expose"=true})
     * @RightAnnotation("historic_broadcast_all_restaurants")
     */
    public function supervisionRestaurantDetailedListAction(Request $request)
    {

        $currentUser = $this->getUser();
        $restaurants = $currentUser->getEligibleRestaurants()->toArray();
        usort(
            $restaurants,
            function (Restaurant $r1, Restaurant $r2) {
                if ($r1->getName() < $r2->getName()) {
                    return -1;
                }

                return 1;
            }
        );

        if ($request->isXmlHttpRequest()) {
            $orders = array('restaurant', 'date', 'status', 'type');
            $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);

            $restaurantsCriteria = [];
            if (isset($dataTableHeaders['criteria']['restaurant'])
                && is_array($dataTableHeaders['criteria']['restaurant'])
                && count($dataTableHeaders['criteria']['restaurant']) > 0
            ) {
                $restaurantsCriteria = [];
                foreach ($dataTableHeaders['criteria']['restaurant'] as $r) {
                    if (trim($r) != '') {
                        $res = $restaurant = $this->getDoctrine()->getRepository(Restaurant::class)
                            ->find($r);
                        if ($res) {
                            $restaurantsCriteria[] = $res;
                        }
                    }
                }
            }

            $items = $this->getDoctrine()->getRepository(SyncCmdQueue::class)->getList(
                $restaurants,
                $dataTableHeaders['criteria'],
                $restaurantsCriteria,
                $dataTableHeaders['search'],
                $dataTableHeaders['orderBy'],
                $dataTableHeaders['offset'],
                $dataTableHeaders['limit']
            );

            $return['draw'] = $dataTableHeaders['draw'];
            $return['recordsFiltered'] = $items['filtred'];
            $return['recordsTotal'] = $items['total'];
            $return['data'] = $this->_serializeSyncCmd($items['list']);

            return new JsonResponse($return);
        }

        $downloadTypes = SyncCmdQueue::getDownloadConstant();


        return $this->render(
            "@Supervision/supervision/detailed_restaurant_list.html.twig",
            array(
                'downloadTypes' => $downloadTypes,
                'restaurants' => $restaurants,
            )
        );
    }

    /**
     * @param Restaurant $restaurant
     * @Route("/redirect_to_restaurant/{restaurant}", name="redirect_to_restaurant")
     * @return Response
     */
    public function redirectToBOAction(Restaurant $restaurant)
    {
        /**
         * @var User $currentUser
         */
        $currentUser = $this->getUser();
        if(!$currentUser->isSuperAdmin())
        {
            if (!$currentUser->getEligibleRestaurants()->contains($restaurant))
            {
                $this->addFlash("warning", $this->get("translator")->trans("restaurant_access_denied"));
                return $this->redirectToRoute("restaurant_list_super");
            }
        }


        $this->get("session")->set('currentRestaurant', $restaurant);

        return $this->redirectToRoute('index');
    }

    /***
     *
     * * *** PRIVATES FUNCTIONS *****
     */

    /**
     * @param SyncCmdQueue[] $items
     * @return array
     */
    private function _serializeSyncCmd($items)
    {
        $data = [];
        foreach ($items as $i) {
            $data[] = [
                'date' => $i->getUpdatedAt()->format('d/m/Y H:i:s'),
                'status' => $i->getStatus(),
                'status_translated' => $this->get('translator')->trans($i->getStatus(), [], 'synchro_msg'),
                'type' => $this->get('translator')->trans($i->getCmd(), [], 'synchro_msg'),
                'details' => $i->getProduct()->getName(),
                'quick_name' => $i->getOriginRestaurant()->getName(),
                'quick_code' => $i->getOriginRestaurant()->getCode(),
            ];
        };

        return $data;
    }

    /**
     * @param RemoteHistoric[] $items
     * @return array
     */
    private function _serializeRemoteHist($items)
    {
        $data = [];
        foreach ($items as $i) {
            $data[] = [
                'date' => $i->getCreatedAtInCentral()->format('d/m/Y H:i:s'),
                'status' => $i->getStatus(),
                'status_translated' => $this->get('translator')->trans($i->getStatus(), [], 'synchro_msg'),
                'type' => $this->get('translator')->trans($i->getType(), [], 'synchro_msg'),
            ];
        }

        return $data;
    }


    /**
     * @param Restaurant $restaurant
     * @return JsonResponse
     * @Route("/revive_order/{restaurant}",name="resend_order_rejected", options={"expose"=true})
     */
    public function resendOrderAction( Restaurant $restaurant)
    {
        $logger=$this->get('monolog.logger.generate_report');
        $em = $this->get('doctrine.orm.entity_manager');
        $orderService = $this->get('order.service');

        //Recupérer toutes les commandes non envoyées
       /** $orders = $em->getRepository("Merchandise:Order")->findBy(
            array(
                'originRestaurant' => $restaurant->getId(),
                'status' => Order::REJECTED,
            )
        ); */
        
        $orders = $em->getRepository("Merchandise:Order")->getRejectedOrder($restaurant->getId());

        // s'il n'existe pas des ordres
        if(empty($orders)){
            $this->addFlash("warning", $this->get("translator")->trans("pas de commande rejectée"));
            return new JsonResponse(array('res' =>0));
        }

        foreach ($orders as $order){
            $order->setStatus(Order::SENDING);
            $em->persist($order);
            $em->flush();
//            $orderService->sendOrder($order);

        }

        return new JsonResponse(array('res' =>1));
    }
}
