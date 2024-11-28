<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 06/06/2016
 * Time: 14:01
 */

namespace AppBundle\Security\Service;

use AppBundle\Security\Entity\Role;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class VerifyingRoleService
{

    private $token;

    private $em;

    /**
     * @param TokenStorage $tokenStorage
     */
    public function __construct(TokenStorage $tokenStorage, EntityManager $em)
    {
        $this->token = $tokenStorage;
        $this->em = $em;
    }

    public function check($routeName)
    {
        if (!$this->token->getToken()) {
            return false;
        }

        $user = $this->token->getToken()->getUser();

        $roleAdmin = $this->em->getRepository('Security:Role')->findOneBy(
            [
                'label' => Role::ROLE_ADMIN,
            ]
        );

        if ($user && $roleAdmin && $user->hasRole($roleAdmin)) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $hasAccess = false;
        foreach ($user->getEmployeeRoles() as $role) {
            foreach ($role->getActions() as $right) {
                if ($right->getName() == $routeName) {
                    $hasAccess = true;
                    break;
                }
            }
        }

        if (!$hasAccess) {
            return false;
        }

        return true;
    }

    public function checkOrThrowAccedDenied($routeName)
    {
        if (!$this->check($routeName)) {
            throw new AccessDeniedException();
        }
    }

    public function disableBtn($routeName)
    {
        if (!$this->check($routeName)) {
            return 'disabled=disabled';
        }

        return '';
    }

    public function hideLink($routeName)
    {
        if (!$this->check($routeName)) {
            return 'hidden';
        }

        return '';
    }
}
