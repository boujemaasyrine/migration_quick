<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 31/05/2016
 * Time: 14:12
 */

namespace AppBundle\Supervision\Service\UsersManagement;

use AppBundle\Administration\Entity\Action;
use AppBundle\Security\Entity\Role;
use AppBundle\Service\SyncCmdCreateEntryService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;

class RightsRolesService
{
    private $em;
    private $translator;

    public function __construct(EntityManager $em, Translator $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    public function setRightsForRoles($data, $type)
    {
        foreach ($data as $role) {
            /**
             * @var Role $role ['role']
             * @var Action $right
             */
            foreach ($role['role']->getActions() as $right) {
                if ($right->getType() == $type) {
                    $role['role']->removeAction($right);
                    $right->removeRole($role['role']);
                }
            }

            foreach ($role['right'] as $right) {
                $role['role']->addAction($right);
                $right->addRole($role['role']);
                $this->em->persist($right);
            }
            //$this->syncCmdCreateEntry->createRoleEntry();
        }
        $this->em->flush();
    }

    public function getRightsForAllRoles()
    {

        $rights = array();
        $roles = $this->em->getRepository(Role::class)->findAll();
        foreach ($roles as $role) {
            $rights[$role->getId()] = array();
            $i = 0;
            foreach ($role->getActions() as $action) {
                $rights[$role->getId()][$i]['idRight'] = $action->getId();
                $rights[$role->getId()][$i]['labelRight'] = $action->getRoute();
                $i++;
            }
        }

        return $rights;
    }
}
