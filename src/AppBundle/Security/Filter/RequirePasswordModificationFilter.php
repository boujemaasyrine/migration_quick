<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 09/02/2016
 * Time: 10:49
 */

namespace AppBundle\Security\Filter;

use AppBundle\Security\Entity\User;
use AppBundle\Security\RightAnnotation;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class RequirePasswordModificationFilter
{

    private $user;
    private $router;

    public function __construct(TokenStorage $tokenStorage, Router $router)
    {

        if ($tokenStorage !== null && $tokenStorage->getToken() !== null && $tokenStorage->getToken()->getUser(
        ) !== null) {
            $this->user = $tokenStorage->getToken()->getUser();
        } else {
            $this->user = null;
        }
        $this->router = $router;
    }


    public function onKernelResponse(FilterResponseEvent $event)
    {

        $user = null;

        //Checking first connection
        if ($this->user === null) {
            //Permission denied , redirect to
        }

        if ($this->user instanceof User) {
            $user = $this->user;
            $passwordRoute = $this->router->generate('force_password_modification');
            $loginRoute = $this->router->generate('login');

            if (!$user->getFirstConnection() && !in_array($event->getRequest()->getRequestUri(), [$passwordRoute, $loginRoute]) ) {
                $request = $event->getRequest();

                if (!$request->isXmlHttpRequest()) {
                    $newResponse = new RedirectResponse($passwordRoute);
                    $event->setResponse($newResponse);
                }
            }
        }

        return;
    }
}
