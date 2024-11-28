<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 30/05/2016
 * Time: 18:54
 */

namespace AppBundle\Supervision\Service\UsersManagement;

use AppBundle\Security\Entity\Role;
use AppBundle\Security\Entity\User;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;

class UsersService
{
    private $em;
    private $translator;

    public function __construct(EntityManager $em, Translator $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    /**
     * @param Employee $user
     * @param Role $role
     */
    public function saveUser($user, $role)
    {
        /* using the getEmployeeRoles method instead of getRolesAsObject method */
        if ($user->getEmployeeRoles() != null) {
            foreach ($user->getEmployeeRoles() as $r) {


                /**
                 * @var Role $r
                 */
                if($r->getLabel()!=Role::ROLE_EMPLOYEE && $r->getLabel()!=Role::ROLE_SUPERVISION){
                    $user->removeRole($r);
                    $r->removeUser($user);
                }

            }
        }
        if (!$user->getId()) {
            /* if this is a newly created user set active to true*/
            $user->setActive(true);
            $user->setFirstConnection(true);
            /* add SUPERVISION_ROLE role to the newly created supervision user*/
            $supervisionRole = $this->em->getRepository(Role::class)->findOneBy(
                array("label" => Role::ROLE_SUPERVISION)
            );
            if ($supervisionRole != null) {
                $user->addEmployeeRole($supervisionRole);
                $supervisionRole->addUser($user);
            }
            $this->em->persist($user);
        }
        /* comment this line */
        //$user->setGlobalId($user->getId());

        //using addEmployeeRole method instead of addRole
        $user->addEmployeeRole($role);
        $role->addUser($user);

        $this->em->flush();
    }

    /**
     * @param Role $role
     */
    public function saveRole($role)
    {
        if (!$role->getId()) {
            $this->em->persist($role);
            $label = strtolower($role->getType()).'_'.$role->getId();
            $role->setLabel($label);
        }
        /* this section to be verified as the synchronize mechanism won't be used anymore*/

        /*$uow = $this->em->getUnitOfWork();
        $uow->computeChangeSets();
        $changes = $uow->getEntityChangeSet($role);
        if (count($changes) > 0 ) {
            $this->syncCmdCreateEntry->createRoleEntry();
        }*/

        $this->em->flush();
    }

    /**
     * @param Role $role
     * @return bool
     */
    public function deleteRole($role)
    {
        if (count($role->getUsers()) == 0 && count($role->getActions()) == 0) {
            $this->em->remove($role);
            $this->em->flush();

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Employee $user
     * @return bool
     */
    public function deleteUser($user)
    {
        $user->setDeleted(true);
        $this->em->flush();

        return true;
    }

    public function getRoles($criteria, $order, $limit, $offset)
    {
        $roles = $this->em->getRepository("AppBundle:Security\Role")->getRolesOrdered(
            $criteria,
            $order,
            $offset,
            $limit
        );

        return $this->serializeRoles($roles);
    }

    public function serializeRoles($roles)
    {
        $result = [];
        foreach ($roles as $r) {
            /**
             * @var Role $r
             */
            $result[] = array(
                'label' => $r->getTextLabel(),
                'type' => $r->getType() == Role::CENTRAL_ROLE_TYPE ? $this->translator->trans('parameters.central')
                    : $this->translator->trans('parameters.restaurant'),
            );
        }

        return $result;
    }

    public function getUsers($criteria, $order, $limit, $offset)
    {
        $users = $this->em->getRepository(Employee::class)->getUsersOrdered(
            $criteria,
            $order,
            $offset,
            $limit
        );

        return $this->serializeUsers($users);
    }

    public function serializeUsers($users)
    {
        $result = [];
        foreach ($users as $u) {
            /**
             * @var User $u
             */
            $result[] = array(
                'id' => $u->getId(),
                'lastName' => $u->getLastName(),
                'firstName' => $u->getFirstName(),
                'login' => $u->getUsername(),
                'email' => $u->getEmail(),
                'function' => $u->getEmployeeRoles()['0']->getTextLabel(),
            );
        }

        return $result;
    }

    public function isFromSupervision(Employee $user)
    {
        $roles=$user->getEmployeeRoles();
        $supervisionRole = $this->em->getRepository(Role::class)->findOneBy(
        array("label" => Role::ROLE_SUPERVISION)
    );

        if($supervisionRole!=null){
            return $roles->contains($supervisionRole);
        }

        else {
            return false;
        }

    }

    /**
     * retourne seulement le rôle contrôle de gestion
     * @param User $user
     * @return mixed|null
     */
    public function getRoleManagementControl(User $user ){
        $userRoles = $user->getEmployeeRoles();
        foreach ($userRoles as $r){
            $label=$r->getLabel();
            if($label!=Role::ROLE_ADMIN && $label!=Role::ROLE_SUPERVISION && $r->getType()==  Role::CENTRAL_ROLE_TYPE ){
               return $r ;
            }
        }
        return null;
    }
}
