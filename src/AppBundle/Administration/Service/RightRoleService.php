<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 27/04/2016
 * Time: 18:28
 */

namespace AppBundle\Administration\Service;

use AppBundle\Administration\Entity\Action;
use AppBundle\Security\Entity\Rights;
use AppBundle\Security\Entity\Role;
use Doctrine\ORM\EntityManager;

/**
 * Class RightRoleService
 */
class RightRoleService
{

    private $em;

    /**
     * RightRoleService constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param $data
     */
    public function setRightsForRoles($data)
    {
        foreach ($data as $role) {
            /**
             * @var Role $role ['role']
             * @var Action $right
             */
            foreach ($role['role']->getActions() as $right) {
                $role['role']->removeAction($right);
                $right->removeRole($role['role']);
            }

            foreach ($role['right'] as $right) {
                $role['role']->addAction($right);
                $right->addRole($role['role']);
                $this->em->persist($right);
            }
            $this->em->persist($role['role']);
        }
        $this->em->flush();
    }
}
