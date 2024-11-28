<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 26/05/2016
 * Time: 14:31
 */

namespace AppBundle\Supervision\Filter;

use AppBundle\Security\Entity\Role;
use AppBundle\Security\Entity\User;
use AppBundle\Administration\Entity\Action;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use AppBundle\Supervision\Annotation\RightAnnotation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class WsSecurityCheckerFilter
{

    /**
     * @var Router
     */
    private $router;

    private $em;

    private $biToken;

    private $user;

    public function __construct(Router $router, EntityManager $em, $biToken, TokenStorage $tokenStorage)
    {
        $this->router = $router;
        $this->em = $em;
        $this->biToken = $biToken;

        if ($tokenStorage !== null && $tokenStorage->getToken() !== null && $tokenStorage->getToken()->getUser(
        ) !== null) {
            $this->user = $tokenStorage->getToken()->getUser();
        } else {
            $this->user = null;
        }
    }

    public function onKernelController(FilterControllerEvent $event)
    {

        $pathInfo = $event->getRequest()->getPathInfo();
        $postRequest = $event->getRequest()->request;
        $response = null;


        if (preg_match("#^/ws_bo_api#", $pathInfo) > 0) { //WS BO
            if (!$postRequest->has('restaurant')) {
                $response = new JsonResponse(
                    array(
                        'error' => 'Restaurant ID NOT FOUND',
                    )
                );
            }

            if (!$postRequest->has('token')) {
                $response = new JsonResponse(
                    array(
                        'error' => 'Token NOT FOUND',
                    )
                );
            }

            //Verify existing Restaurant
            $restaurant = $this->em->getRepository("AppBundle:Restaurant")
                ->findOneBy(array('code' => $postRequest->get('restaurant')));
            if (!$restaurant) {
                $response = new JsonResponse(
                    array(
                        'error' => "RESTAURANT WITH ID  ".$postRequest->get('restaurant')." NOT FOUND",
                    )
                );
            } else {
                //Verify token is for the TOKEN

                $tokenPost = $postRequest->get('token');
                $hashToken = $restaurant->getSecretKey();
                if (md5($tokenPost) != $hashToken) {
                    $response = new JsonResponse(
                        array(
                            'error' => "INVALID TOKEN",
                        )
                    );
                } else {
                    //Set last ping date IF OK
                    $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
                    $restaurant->setLastPingTime(new \DateTime('now'));
                    $this->em->flush();
                }
            }
        } elseif (preg_match("#^/ws_bi_api#", $pathInfo) > 0) {
            if (!$postRequest->has('token')) {
                $response = new JsonResponse(
                    array(
                        'error' => 'Token NOT FOUND',
                    )
                );
            } else {
                $tokenPost = $postRequest->get('token');
                if (md5($tokenPost) != $this->biToken) {
                    $response = new JsonResponse(
                        array(
                            'error' => "INVALID TOKEN",
                        )
                    );
                }
            }
        }

        if ($response) {
            //There's error
            throw new NotAuthorizedException($response);
        }

        $user = null;

        //Checking user rights for access the controller except the role ADMIN
        $roleAdmin = $this->em->getRepository('AppBundle:Security\Role')->findOneBy(
            [
                'label' => Role::ROLE_ADMIN,
            ]
        );

        if ($this->user === null) {
            //Permission denied , redirect to
        }

        if ($this->user instanceof User) {
            $user = $this->user;
        }
        if ($user && $roleAdmin && !$user->hasRole($roleAdmin)) {
            //Checking user rights for access the controller
            $controller = $event->getController();
            $controller_class = get_class($controller[0]);
            $method_name = $controller[1];
            $actionMethod = new \ReflectionMethod($controller_class, $method_name);

            $reader = new AnnotationReader();
            $annotation = $reader->getMethodAnnotation($actionMethod, RightAnnotation::class);

            if ($annotation !== null && $annotation instanceof RightAnnotation) {
                $accessDenied = true;
                $rights = $annotation->getRights();
                if (count($rights) > 0) {
                    $request = $event->getRequest();

                    //test on user rights
                    /**
                     * @var Role $role
                     * @var Action $right
                     */
                    foreach ($user->getRolesAsObject() as $role) {
                        foreach ($role->getActions() as $right) {
                            if ($right->getRoute() == $rights and $right->getType() == Action::CENTRAL_ACTION_TYPE) {
                                $accessDenied = false;
                                break;
                            }
                        }
                    }
                }
                if ($accessDenied) {
                    throw new AccessDeniedException();
                }
            }
        }
    }
}
