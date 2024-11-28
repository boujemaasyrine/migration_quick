<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 06/06/2016
 * Time: 14:01
 */

namespace AppBundle\Supervision\Service\UsersManagement;

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

    public function check($actionName)
    {
        if (!$this->token->getToken()) {
            return false;
        }
        $user = $this->token->getToken()->getUser();

        if (!$user) {
            return false;
        }

        $roleAdmin = $this->em->getRepository('AppBundle:Security\Role')->findOneBy(
            [
                'label' => Role::ROLE_ADMIN,
            ]
        );

        if ($user && $roleAdmin && $user->hasRole($roleAdmin)) {
            return true;
        }

        $hasAccess = false;
        foreach ($user->getRolesAsObject() as $role) {
            /**
             * @var Role $role
             */
            foreach ($role->getActions() as $right) {
                if ($right->getName() == $actionName) {
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

    public function checkOrThrowAccedDenied($actionName)
    {
        if (!$this->check($actionName)) {
            throw new AccessDeniedException();
        }
    }

    public function disableBtn($actionName)
    {
        if (!$this->check($actionName)) {
            return 'disabled=disabled';
        }

        return '';
    }

    public function hideLink($actionName)
    {
        if (!$this->check($actionName)) {
            return 'hidden';
        }

        return '';
    }
}
