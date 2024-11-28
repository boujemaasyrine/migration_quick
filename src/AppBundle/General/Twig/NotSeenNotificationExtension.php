<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 17/05/2016
 * Time: 09:16
 */

namespace AppBundle\General\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

class NotSeenNotificationExtension extends \Twig_Extension
{

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('not_seen_notification', array($this, 'notSeenNotification')),
        );
    }

    public function notSeenNotification($onlyCount = false)
    {

        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $currentRestaurant = $this->container->get('restaurant.service')->getCurrentRestaurant();
        $notifications = array();
        if ($user) {
            $notifications = $this->container->get('doctrine.orm.entity_manager')->getRepository(
                'General:NotificationInstance'
            )
                ->getNotSeenNotification($user, $currentRestaurant);
        }

        if ($onlyCount) {
            return count($notifications);
        }

        return $notifications;
    }

    public function getName()
    {
        return 'not_seen_notification_extension';
    }
}
