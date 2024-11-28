<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 11:56
 */

namespace AppBundle\General\Command;

use AppBundle\Security\Entity\Role;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportRolesCommand extends ContainerAwareCommand
{

    private $roles = [
        ['label' => 'ROLE_USER', 'textLabel' => 'Rôle User'],
        ['label' => 'ROLE_MANAGER', 'textLabel' => 'Rôle Manager'],
        ['label' => 'ROLE_FIRST_ASSISTANT', 'textLabel' => 'Rôle First Assistant '],
        ['label' => 'ROLE_ASSISTANT', 'textLabel' => 'Rôle Assistant'],
        ['label' => 'ROLE_INTERN', 'textLabel' => 'Rôle Intern'],
        ['label' => 'ROLE_SHIFT_LEADER', 'textLabel' => 'Rôle Shift Leader'],
        ['label' => 'ROLE_HOSTESS', 'textLabel' => 'Rôle Hostess'],
        ['label' => 'ROLE_CREW_LEADER', 'textLabel' => 'Rôle Crew Leader'],
        ['label' => 'ROLE_CREW', 'textLabel' => 'Rôle Crew'],
        ['label' => 'ROLE_DISTRICT_MANAGER', 'textLabel' => 'Rôle District Manager'],
        ['label' => 'ROLE_EMPLOYEE', 'textLabel' => 'Rôle Employé'],
        ['label' => 'ROLE_ADMIN', 'textLabel' => 'Rôle Admin'],
        ['label' => 'ROLE_SUPERVISION', 'textLabel' => 'Rôle Supervision'],
    ];

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:roles:import')->setDefinition(
            []
        )->setDescription('Import All Roles.');
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
        $dbName = $this->em->getConnection()->getDatabase();
        echo $dbName;
        echo "Import Roles => \n";

        foreach ($this->roles as $r) {
            echo "Import Role => ".$r['textLabel']."\n";
            $role = $this->em->getRepository("Security:Role")->findOneBy(
                array(
                    'label' => $r['label'],
                )
            );

            if (!$role) {
                $role = new Role();
                $role->setLabel($r['label']);
            }

            $role->setTextLabel($r['textLabel']);
            $this->em->persist($role);
            $this->em->flush();
        }
        echo " => Finish Importing Roles <= \n";
    }
}
