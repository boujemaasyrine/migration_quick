<?php

namespace AppBundle\Command;

use AppBundle\Administration\Entity\Action;
use AppBundle\Security\Entity\Role;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportRolesActionsCommand extends ContainerAwareCommand
{
    private $em;
    private $dataDir;
    private $data;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:import:roles:actions')
            ->addArgument('file', InputArgument::OPTIONAL, 'File Name to import data from.')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'The format of the import file (json/csv).', 'csv')
            ->setDescription('Command to Link roles to actions for the platform.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/saas/";
        $this->data = array();

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $argument = $input->getArgument('file');
        $option = strtolower(trim($input->getOption('format')));
        if ($option !== "csv" && $option !== "json") {
            $output->writeln("Invalid format option ! Only json or csv values are accepted. Command exit...");

            return;
        }

        if (isset($argument)) {
            $filename = $argument.".".$option;
            $filePath = $this->dataDir.$filename;

            if (!file_exists($filePath)) {
                $output->writeln("No ".$option." import file with the '".$argument."' name !");

                return;
            }

            try {
                if ($option === "csv") {
                    // Import du fichier CSV
                    if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier, à adapter

                        $output->writeln("---->Import mode: CSV file.");
                        while (($data = fgetcsv(
                                $handle,
                                0,
                                ";"
                            )) !== false) { // Eléments séparés par un point-virgule, à modifier si necessaire

                            if(!empty($data[3])){
                                $actions = explode(",", $data[3]);
                                $actionsArray = array();
                                foreach ($actions as $action) {
                                    $tmp = explode("::", $action);
                                    $params = array();
                                    parse_str($tmp[2], $params);
                                    $actionsArray[] = array(
                                        "name" => $tmp[0],
                                        "route" => $tmp[1],
                                        "params" => $params,
                                        "hasExit" => boolval($tmp[3]),
                                        "isPage" => boolval($tmp[4]),
                                    );
                                }
                            }

                            $this->data[] = array(
                                'label' => $data[0],
                                'textLabel' => $data[1],
                                'type' => $data[2],
                                'actions' => $actionsArray,
                            );

                        }
                        fclose($handle);
                    } else {
                        $output->writeln("Cannot open the csv file! Exit command...");

                        return;
                    }

                } else {// import du fichier json

                    if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier, à adapter

                        $output->writeln("---->Import mode: JSON file.");
                        try {
                            $fileData = file_get_contents($filePath);
                            $rolesData = json_decode($fileData, true);
                        } catch (\Exception $e) {
                            $output->writeln($e->getMessage());

                            return;
                        }

                        foreach ($rolesData as $data) {

                            $this->data[] = array(
                                'label' => $data['label'],
                                'textLabel' => $data['textLabel'],
                                'type' => $data['type'],
                                'actions' => $data['actions'],
                            );
                        }

                        fclose($handle);

                    } else {
                        $output->writeln("Cannot open the json file! Exit command...");

                        return;
                    }

                }

            } catch (\Exception $e) {
                $output->writeln($e->getMessage());

                return;
            }

        } else {
            $output->writeln("Please provide a valid import file name. ");

            return;
        }

        $output->writeln("Start importing Roles/Actions links ...");
        $count = 0;
        $actionsAdded = 0;
        $roleAdded = 0;


        foreach ($this->data as $r) {

            $role = $this->em->getRepository(Role::class)->findOneBy(
                array(
                    'label' => $r['label'],
                    'type' => isset($r['type']) ? $r['type'] : '',
                )
            );

            if (!$role) {
                $output->writeln("Role => ".$r['textLabel']." not exist ! Creating it...");
                $roleAdded++;
                $role = new Role();
                $role->setLabel($r['label']);
                $role->setTextLabel($r['textLabel']);
                $role->setType($r['type']);
                if ($role->getGlobalId() == null) {
                    $role->setGlobalId($role->getId());
                }
            }

            foreach ($r['actions'] as $a) {
                $action = $this->em->getRepository(Action::class)->findOneBy(
                    array(
                        'name' => $a['name'],
                        'type' => isset($a['type']) ? $a['type'] : '',
                    )
                );

                if (!$action) {
                    $output->writeln("Action => ".$a['name']." not exist ! Creating it...");
                    $actionsAdded++;
                    $action = new Action();
                    $action->setName($a['name']);
                    if (isset($a['type'])) {
                        $action->setType($a['type']);
                    }
                    $action->setGlobalId($action->getId());
                    $action->setRoute($a['route'])
                        ->setParams($a['params']);
                    if(isset($a['hasExitBtn'])){
                        $action->setHasExit($a['hasExitBtn']);
                    }else{
                        $action->setHasExit(false);
                    }
                    if (!isset($a['isPage']) || ($a['isPage'] && isset($a['isPage']))) {
                        $action->setIsPage(true);
                    } else {
                        $action->setIsPage(false);
                    }
                }

                if (!$role->getActions()->contains($action)) {
                    $role->addAction($action);
                    $output->writeln("-> Action [".$a['name']."] added to [".$role->getTextLabel()."] Role.");
                }
                if (!$action->getRoles()->contains($role)) {
                    $action->addRole($role);
                }
                $this->em->persist($action);
            }

            $this->em->persist($role);
            $this->em->flush();
            $count++;

        }

        $this->em->flush();

        if ($actionsAdded > 0) {
            $output->writeln("--> ".$actionsAdded." new Action added.");
        }
        if ($roleAdded > 0) {
            $output->writeln("--> ".$roleAdded." new Role added.");
        }

        $output->writeln("----> ".$count." Role/Action link imported.");
        $output->writeln("==> Role/Action link import finished <==");


    }

}
