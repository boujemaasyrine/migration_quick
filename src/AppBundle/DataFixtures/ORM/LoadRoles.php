<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 21/12/2015
 * Time: 17:47
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Security\Entity\Role;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LoadRoles
 */
class LoadRoles extends AbstractFixture
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $roleLabels = [
            'ROLE_USER',
            'ROLE_MANAGER',
            'ROLE_FIRST_ASSISTANT',
            'ROLE_ASSISTANT',
            'ROLE_INTERN',
            'ROLE_SHIFT_LEADER',
            'ROLE_HOSTESS',
            'ROLE_CREW_LEADER',
            'ROLE_CREW',
            'ROLE_DISTRICT_MANAGER',
            'ROLE_EMPLOYEE',
        ];

        foreach ($roleLabels as $roleLabel) {
            $role = new Role();
            $role->setLabel($roleLabel);
            $manager->persist($role);
        }

        $manager->flush();
    }
}
