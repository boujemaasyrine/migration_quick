<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 06/10/2017
 * Time: 15:20
 */

namespace AppBundle\Security\Service;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener
{
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        if ($user) {
            $restaurants = $user->getEligibleRestaurants();
            if ($restaurants->count() > 0) {
                $currentRestaurant = $restaurants->first()->getId();
                $this->session->set('currentRestaurant', $currentRestaurant);
            }
        }
    }
}
