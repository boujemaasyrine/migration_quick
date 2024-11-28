<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 07/06/2016
 * Time: 15:18
 */

namespace AppBundle\General\Command;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Security\Entity\Role;
use AppBundle\Security\Entity\User;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeUserCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:user:initialize')->setDefinition(
            []
        )->setDescription('Initialize user role.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "Initialize  User \n";

        $adminUser = $this->em->getRepository('Security:User')->findOneByUsername('admin');
        if (!$adminUser) {
            $restaurant = $this->em->getRepository(Restaurant::class)->findOneByName('Mersch');
            $adminUser = new Employee();
            $adminUser
                ->setUsername('admin')
                ->setPassword('admin')
                ->setActive(true)
                ->setFirstConnection(false)
                ->setFirstName('Admin')
                ->setLastName('Admin')
                ->setEmail('admin@admin.com')
                ->addEligibleRestaurant($restaurant)
                ->setDeleted(false);
            $this->em->persist($adminUser);
            $adminRole = $this->em->getRepository('Security:Role')->findOneBy(
                array(
                    'label' => Role::ROLE_ADMIN,
                )
            );
            $employeeRole = $this->em->getRepository('Security:Role')->findOneBy(
                array(
                    'label' => Role::ROLE_EMPLOYEE,
                )
            );
            $adminUser->addEmployeeRole($adminRole);
            $adminUser->addEmployeeRole($employeeRole);

            $adminRole->addUser($adminUser);
            $employeeRole->addUser($adminUser);
        }

        $this->em->flush();
        echo "User created: admin admin\n";

        echo "Initialize  User \n";

        $superAdminUser = $this->em->getRepository('Security:User')->findOneByUsername('superadmin');
        if (!$superAdminUser) {
            $superAdminUser = new Employee();
            $superAdminUser
                ->setUsername('superadmin')
                ->setPassword('superadmin')
                ->setActive(true)
                ->setFirstConnection(false)
                ->setFirstName('Super Admin')
                ->setLastName('Super Admin')
                ->setEmail('superadmin@admin.com')
                ->setDeleted(false)
                ->setFirstConnection(true);
            $superAdminRole = $this->em->getRepository('Security:Role')->findOneBy(
                array(
                    'label' => Role::ROLE_SUPERVISION,
                )
            );

            $adminRole = $this->em->getRepository('Security:Role')->findOneBy(
                array(
                    'label' => Role::ROLE_ADMIN,
                )
            );

            $superAdminUser->addEmployeeRole($adminRole);
            $adminRole->addUser($superAdminUser);
            $superAdminUser->addEmployeeRole($superAdminRole);
            $superAdminRole->addUser($superAdminUser);

            $restaurants = $this->em->getRepository(Restaurant::class)->findAll();
            foreach ($restaurants as $restaurant) {
                $superAdminUser->addEligibleRestaurant($restaurant);
                $restaurant->addEligibleUser($superAdminUser);
            }
            $this->em->persist($superAdminUser);
        }

        $this->em->flush();
        echo "User created: super admin\n";

        echo " => Initialize  User \n";
    }
}
