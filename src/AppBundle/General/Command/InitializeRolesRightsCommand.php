<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 12:18
 */

namespace AppBundle\General\Command;

use AppBundle\Security\Entity\Role;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeRolesRightsCommand extends ContainerAwareCommand
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
        $this->setName('quick:roles:right:initialize')->setDefinition(
            []
        )->setDescription('Initialize Rights for Roles.');
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
        echo "Initialize  Rights for Roles \n";

        $admin = $this->em->getRepository("Staff:Employee")->findOneBy(
            array(
                'username' => 'admin',
            )
        );

        $employeeRole = $this->em->getRepository("Security:Role")->findOneBy(
            array(
                'label' => Role::ROLE_EMPLOYEE,
            )
        );

        $managerRole = $this->em->getRepository("Security:Role")->findOneBy(
            array(
                'label' => Role::ROLE_MANAGER,
            )
        );


        if (!$admin->hasEmployeeRole($employeeRole)) {
            $admin->addEmployeeRole($employeeRole);
            $employeeRole->addUser($admin);
        }
        if (!$admin->hasEmployeeRole($managerRole)) {
            $admin->addEmployeeRole($managerRole);
            $managerRole->addUser($admin);
        }


        $rights = $this->em->getRepository("Administration:Action")->findAll();
        foreach ($rights as $right) {
            if (!$managerRole->getActions()->contains($right)) {
                $managerRole->addAction($right);
                $right->addRole($managerRole);

                $this->em->persist($right);
                echo "Right ".$right->getName()." added to role ".$managerRole->getTextLabel()." with success \n";
            }
        }

        $this->em->persist($admin);
        $this->em->persist($employeeRole);
        $this->em->persist($managerRole);
        $this->em->flush();

        echo " => Finish initializing  Data \n";
    }
}
