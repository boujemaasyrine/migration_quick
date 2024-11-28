<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 21/12/2015
 * Time: 17:47
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Staff\Entity\Employee;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class LoadEmployee
 * @package AppBundle\DataFixtures\ORM
 */
class LoadEmployee extends AbstractFixture
{

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {

        $admin = $manager->getRepository("Staff:Employee")->findOneBy(
            array(
                'username' => 'admin',
            )
        );
        if (null == $admin) {
            $userAdmin = new Employee();
            $userAdmin
                ->setFirstName("Admin")
                ->setLastName("Admin")
                ->setUsername('admin')
                ->setPassword('admin')
                ->setEmail('admin@admin.com')
                ->setCreatedAt(new \DateTime('NOW'))
                ->addRole('ROLE_ADMIN');

            $manager->persist($userAdmin);
            $manager->flush();
        }
    }
}
