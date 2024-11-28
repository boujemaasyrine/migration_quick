<?php

namespace AppBundle\Command;

use AppBundle\Administration\Entity\Action;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Security\Entity\Role;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/*
 *
 */
class InitAdminUserCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:init:admin:user')
            ->setDescription('Command to create default super admin user.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Initilising the admin user account...");

        $adminUser = $this->em->getRepository(Employee::class)->findOneByUsername('admin');

        if (!$adminUser) {
            $adminUser = new Employee();
            $adminUser
                ->setUsername('admin')
                ->setPassword('admin')
                ->setActive(true)
                ->setDeleted(false)
                ->setEmail('admin@admin.com')
                ->setFirstName('Admin')
                ->setLastName('Admin');
            $this->em->persist($adminUser);
        }
        if (count($adminUser->getEmployeeRoles()) > 0) {
            foreach ($adminUser->getEmployeeRoles() as $role) {
                $adminUser->removeEmployeeRole($role);
                $role->removeUser($adminUser);
            }
        }

        $adminRole = $this->em->getRepository(Role::class)->findOneBy(
            array(
                'label' => Role::ROLE_ADMIN,
            )
        );

        $adminUser->addEmployeeRole($adminRole);
        $adminUser->setRoles([Role::ROLE_SUPERVISION]);
        $adminRole->addUser($adminUser);

        $rights = $this->em->getRepository(Action::class)->findAll();
        foreach ($rights as $right) {
            if (!$adminRole->getActions()->contains($right)) {
                $adminRole->addAction($right);
                $right->addRole($adminRole);

                $this->em->persist($right);
                $output->writeln("-> Right ".$right->getName()." added to role ".$adminRole->getTextLabel()." with success.");
            }
        }

        // add all restaurants to admin user
        $restaurants = $this->em->getRepository(Restaurant::class)->findAll();
        foreach ($restaurants as $restaurant){
            if(!$adminUser->getEligibleRestaurants()->contains($restaurant)){
                $adminUser->addEligibleRestaurant($restaurant);
            }
        }

        $this->em->flush();

        $output->writeln("==> Admin user created successfully. <==");
        $output->writeln("----->Login    : ".$adminUser->getUsername());
        $output->writeln("----->Password : ".$adminUser->getPassword());


    }
}
