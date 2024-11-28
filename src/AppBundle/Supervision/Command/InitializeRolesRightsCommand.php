<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 12:18
 */

namespace AppBundle\Supervision\Command;

use AppBundle\Security\Entity\Role;
use AppBundle\Staff\Entity\Employee as User;
use AppBundle\Staff\Entity\Employee;
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

        $adminUser = $this->em->getRepository(Employee::class)->findOneByUsername('admin');
        if (!$adminUser) {
            $adminUser = new User();
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
        if (count($adminUser->getRolesAsObject()) > 0) {
            foreach ($adminUser->getRolesAsObject() as $role) {
                $adminUser->removeRole($role);
                $role->removeUser($adminUser);
            }
        }

        $adminRole = $this->em->getRepository('AppBundle:Security\Role')->findOneBy(
            array(
                'label' => Role::ROLE_ADMIN,
            )
        );
        $adminUser->addRole($adminRole);
        $adminRole->addUser($adminUser);

        $rights = $this->em->getRepository('AppBundle:Staff\Action')->findAll();
        foreach ($rights as $right) {
            if (!$adminRole->getActions()->contains($right)) {
                $adminRole->addAction($right);
                $right->addRole($adminRole);

                $this->em->persist($right);
                echo "Right ".$right->getName()." added to role ".$adminRole->getTextLabel()." with success \n";
            }
        }

        $this->em->flush();

        echo " => Finish initializing  Data \n";
    }
}
