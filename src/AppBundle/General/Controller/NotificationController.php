<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 16/05/2016
 * Time: 11:00
 */

namespace AppBundle\General\Controller;

use AppBundle\General\Entity\NotificationInstance;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class NotificationController
 *
 * @package               AppBundle\General\Controller
 * @Route("notification")
 */
class NotificationController extends Controller
{

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @param NotificationInstance $instance
     * @Route("/seeNotification/{instance}", name="see_notification",options={"expose"=true})
     */
    public function seeNotificationAction(NotificationInstance $instance, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $instance->setSeen(true);
            $em = $this->getDoctrine()->getManager();
            $em->persist($instance);
            $em->flush();

            return new JsonResponse(array('viewed' => true));
        }

        $parameters = $this->get('notification.service')->accessNotification($instance);
        $route = $instance->getNotification()->getRoute();
        $response = $this->redirectToRoute($route, $parameters);

        if (array_key_exists('modalId', $instance->getNotification()->getData())) {
            $response->setTargetUrl($response->getTargetUrl().'#'.$instance->getNotification()->getData()['modalId']);
        }

        return $response;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @param NotificationInstance $instance
     * @Route("/missingPlu/{instance}", name="missing_plu",options={"expose"=true})
     */
    public function missingPluSAction(NotificationInstance $instance)
    {

        return $this->render(
            '@General/Notification/missing_pluS.html.twig',
            [
                'instance' => $instance,
            ]
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @param NotificationInstance $instance
     * @Route("/delete/{instance}", name="delete_notification",options={"expose"=true})
     */
    public function deleteAction(NotificationInstance $instance, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($instance);
        $em->flush();

        $this->addFlash('success', 'notification_deleted_with_success');

        return $this->redirect($this->generateUrl('notification_list'));
    }

    /**
     * @param Request $request
     * @param NotificationInstance $instance
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/notificationList/{instance}",name="notification_list",options={"expose"=true})
     */
    public function notificationListAction(Request $request, NotificationInstance $instance = null)
    {
        if ($instance && $instance->iSeen() == false) {
            $instance->setSeen(true);
            $em = $this->getDoctrine()->getManager();
            $em->persist($instance);
            $em->flush();
        }

        return $this->render(
            "@General/Notification/list.html.twig",
            [
                'instance' => $instance,
            ]
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/notificationsJsonList",name="notifications_json_list", options={"expose"=true})
     */
    public function notificationJsonListAction(Request $request)
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        if ($request->isXmlHttpRequest()) {
            $orders = array('type', 'message');
            $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $dataTableHeaders['criteria']['user'] = $user;
            $dataTableHeaders['criteria']['restaurant'] = $currentRestaurant;
            $instances = $this->getDoctrine()->getRepository("General:NotificationInstance")->getNotificationsFiltred(
                $dataTableHeaders['criteria'],
                $dataTableHeaders['orderBy'],
                $dataTableHeaders['offset'],
                $dataTableHeaders['limit']
            );
            $return['draw'] = $dataTableHeaders['draw'];
            $return['recordsFiltered'] = $instances['filtred'];
            $return['recordsTotal'] = $instances['total'];
            $return['data'] = $this->get('notification.service')->serializeNotifications($instances['list']);

            return new JsonResponse($return);
        } else {
            throw new Exception('Not allowed for http requests');
        }
    }
}
