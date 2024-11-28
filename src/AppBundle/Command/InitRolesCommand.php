<?php

namespace AppBundle\Command;

use AppBundle\Security\Entity\Role;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InitRolesCommand
 * @package AppBundle\Command
 */
class InitRolesCommand extends ContainerAwareCommand
{

    // row format :[ label ; textLabel ; type ]

    /**
     * @var array
     */
    private $roles = [
        ['label' => 'ROLE_MANAGER', 'textLabel' => 'Manager (accès distant)', 'type' => Role::CENTRAL_ROLE_TYPE],
        ['label' => 'ROLE_FIRST_ASSISTANT', 'textLabel' => 'First Assistant', 'type' => Role::RESTAURANT_ROLE_TYPE],
        ['label' => 'ROLE_ASSISTANT', 'textLabel' => 'Assistant', 'type' => Role::RESTAURANT_ROLE_TYPE],
        ['label' => 'ROLE_SHIFT_LEADER', 'textLabel' => 'Shift Leader', 'type' => Role::RESTAURANT_ROLE_TYPE],
        ['label' => 'ROLE_DISTRICT_MANAGER', 'textLabel' => 'District Manager', 'type' => Role::CENTRAL_ROLE_TYPE],
        ['label' => 'ROLE_COORDINATION', 'textLabel' => 'Coordination', 'type' => Role::CENTRAL_ROLE_TYPE],
        ['label' => 'ROLE_IT', 'textLabel' => 'IT', 'type' => Role::CENTRAL_ROLE_TYPE],
        ['label' => 'ROLE_AUDIT', 'textLabel' => 'Audit', 'type' => Role::CENTRAL_ROLE_TYPE],
        ['label' => 'ROLE_FRANCHISE', 'textLabel' => 'Franchise (accès distant)', 'type' => Role::CENTRAL_ROLE_TYPE],
        ['label' => 'ROLE_MANAGER_REST', 'textLabel' => 'Manager', 'type' => Role::RESTAURANT_ROLE_TYPE],
        ['label' => 'ROLE_FRANCHISE_REST', 'textLabel' => 'Franchise', 'type' => Role::RESTAURANT_ROLE_TYPE],
        ['label' => 'ROLE_EMPLOYEE', 'textLabel' => 'Rôle Employé', 'type' => Role::RESTAURANT_ROLE_TYPE],
        ['label' => 'ROLE_ADMIN', 'textLabel' => 'Rôle Admin', 'type' => Role::CENTRAL_ROLE_TYPE],
        ['label' => 'ROLE_SUPERVISION', 'textLabel' => 'Rôle Supervision', 'type' => Role::CENTRAL_ROLE_TYPE]
    ];

    private $em;
    private $dataDir;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:init:roles')
            ->addArgument('file', InputArgument::OPTIONAL, 'File Name in case of init from csv file.')
            ->setDescription('Command to initialise default Roles (fonctions) for the platform.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/saas/";

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $argument = $input->getArgument('file');
        if (isset($argument)) {
            $filename = $argument.".csv";
            $filePath = $this->dataDir.$filename;

            if (!file_exists($filePath)) {
                $output->writeln("No csv import file with the '".$argument."' name !");

                return;
            }
            try {
                // Import du fichier CSV
                $this->roles = array();
                if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier, à adapter
                    $output->writeln("---->Import mode: CSV file.");
                    while (($data = fgetcsv(
                            $handle,
                            1000,
                            ";"
                        )) !== false) { // Eléments séparés par un point-virgule, à modifier si necessaire

                        $this->roles[] = array(
                            "label" => $data[0],
                            "textLabel" => $data[1],
                            "type" => $data[2],
                        );
                    }
                    fclose($handle);
                } else {
                    $output->writeln("Cannot open the csv file! Exit command...");

                    return;
                }

            } catch (\Exception $e) {
                $output->writeln($e->getMessage());

                return;
            }

        }else{
            $output->writeln("---->Import mode: Default.");
        }

        $output->writeln("Start importing roles...");
        $count = 0;
        foreach ($this->roles as $r) {

            $role = $this->em->getRepository(Role::class)->findOneBy(
                array(
                    'label' => $r['label'],
                )
            );

            if (!$role) {
                $output->writeln("Import Role => ".$r['textLabel']);
                $role = new Role();
                $role->setLabel($r['label']);
            }else{
                $output->writeln("-> Role ".$r['textLabel']." already exist! Updating it...");
            }
            $role->setTextLabel($r['textLabel']);
            $role->setType($r['type']);

            $this->em->persist($role);
            if ($role->getGlobalId() == null) {
                $role->setGlobalId($role->getId());
            }
            $count++;

        }
        $this->em->flush();

        $output->writeln("----> ".$count." roles imported.");
        $output->writeln("==> Roles initialised successfully <==");

    }
}
