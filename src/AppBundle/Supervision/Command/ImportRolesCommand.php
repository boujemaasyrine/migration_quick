<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 11:56
 */

namespace AppBundle\Supervision\Command;

use AppBundle\Security\Entity\Role;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportRolesCommand extends ContainerAwareCommand
{

    private $roles = [
        //        ['label' => 'ROLE_USER', 'textLabel' => 'Rôle User', 'type' => Role::RESTAURANT_ROLE_TYPE],
        ['label' => 'ROLE_MANAGER', 'textLabel' => 'Manager (accès distant)', 'type' => Role::CENTRAL_ROLE_TYPE],
        ['label' => 'ROLE_FIRST_ASSISTANT', 'textLabel' => 'First Assistant', 'type' => Role::RESTAURANT_ROLE_TYPE],
        ['label' => 'ROLE_ASSISTANT', 'textLabel' => 'Assistant', 'type' => Role::RESTAURANT_ROLE_TYPE],
        //        ['label' => 'ROLE_INTERN', 'textLabel' => 'Rôle Intern', 'type' => Role::RESTAURANT_ROLE_TYPE],
        ['label' => 'ROLE_SHIFT_LEADER', 'textLabel' => 'Shift Leader', 'type' => Role::RESTAURANT_ROLE_TYPE],
        //        ['label' => 'ROLE_HOSTESS', 'textLabel' => 'Rôle Hostess', 'type' => Role::RESTAURANT_ROLE_TYPE],
        //        ['label' => 'ROLE_CREW_LEADER', 'textLabel' => 'Rôle Crew Leader', 'type' => Role::RESTAURANT_ROLE_TYPE],
        //        ['label' => 'ROLE_CREW', 'textLabel' => 'Rôle Crew', 'type' => Role::RESTAURANT_ROLE_TYPE],
        ['label' => 'ROLE_DISTRICT_MANAGER', 'textLabel' => 'District Manager', 'type' => Role::CENTRAL_ROLE_TYPE],
        ['label' => 'ROLE_EMPLOYEE', 'textLabel' => 'Rôle Employé', 'type' => Role::RESTAURANT_ROLE_TYPE],
        ['label' => 'ROLE_COORDINATION', 'textLabel' => 'Coordination', 'type' => Role::CENTRAL_ROLE_TYPE],
        ['label' => 'ROLE_IT', 'textLabel' => 'IT', 'type' => Role::CENTRAL_ROLE_TYPE],
        ['label' => 'ROLE_AUDIT', 'textLabel' => 'Audit', 'type' => Role::CENTRAL_ROLE_TYPE],
        ['label' => 'ROLE_ADMIN', 'textLabel' => 'Rôle Admin', 'type' => Role::CENTRAL_ROLE_TYPE],
        ['label' => 'ROLE_FRANCHISE', 'textLabel' => 'Franchise (accès distant)', 'type' => Role::CENTRAL_ROLE_TYPE],
        ['label' => 'ROLE_MANAGER_REST', 'textLabel' => 'Manager', 'type' => Role::RESTAURANT_ROLE_TYPE],
        ['label' => 'ROLE_FRANCHISE_REST', 'textLabel' => 'Franchise', 'type' => Role::RESTAURANT_ROLE_TYPE],
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
        echo "Import Roles => \n";

        foreach ($this->roles as $r) {
            echo "Import Role => ".$r['textLabel']."\n";
            $role = $this->em->getRepository('AppBundle:Security\Role')->findOneBy(
                array(
                    'label' => $r['label'],
                )
            );

            if (!$role) {
                $role = new Role();
                $role->setLabel($r['label']);
            }

            $role->setTextLabel($r['textLabel']);
            $role->setType($r['type']);
            $this->em->persist($role);
            if ($role->getGlobalId() == null) {
                $role->setGlobalId($role->getId());
            }
            $this->em->flush();
        }
        echo " => Finish Importing Roles <= \n";
    }
}
