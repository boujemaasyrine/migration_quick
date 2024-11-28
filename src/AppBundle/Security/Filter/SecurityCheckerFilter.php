<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 09/02/2016
 * Time: 10:49
 */

namespace AppBundle\Security\Filter;

use AppBundle\Administration\Entity\Action;
use AppBundle\Security\Entity\Rights;
use AppBundle\Security\Entity\Role;
use AppBundle\Security\Entity\User;
use AppBundle\Security\RightAnnotation;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\ORM\EntityManager;

class SecurityCheckerFilter
{

    private $user;
    private $router;
    private $em;

    public function __construct(TokenStorage $tokenStorage, Router $router, EntityManager $em)
    {

        if ($tokenStorage !== null && $tokenStorage->getToken() !== null && $tokenStorage->getToken()->getUser(
        ) !== null) {
            $this->user = $tokenStorage->getToken()->getUser();
        } else {
            $this->user = null;
        }
        $this->router = $router;
        $this->em = $em;
    }


    public function onKernelController(FilterControllerEvent $event)
    {

        $user = null;

        if ($this->user === null) {
            //Permission denied , redirect to
        }

        if ($this->user instanceof User) {
            $user = $this->user;
        }

        //Checking user rights for access the controller except the role ADMIN
        $roleAdmin = $this->em->getRepository('Security:Role')->findOneBy(
            [
                'label' => Role::ROLE_ADMIN,
            ]
        );

        if ($user && $roleAdmin && !$user->hasRole($roleAdmin)) {
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
                    foreach ($user->getEmployeeRoles() as $role) {
                        foreach ($role->getActions() as $right) {
                            if ($right->getRoute() == $rights) {
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
        } else {
        }
    }
}
