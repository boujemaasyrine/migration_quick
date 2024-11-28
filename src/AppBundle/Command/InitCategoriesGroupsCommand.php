<?php

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\CategoryGroup;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCategoriesGroupsCommand extends ContainerAwareCommand
{
    //Row format :   name ;  active  ;  global_id ;  name_translation  ;

    private $em;
    private $dataDir;
    private $groups=array(
        array(
            'name'=>"FOODCOST",
            'active'=>true,
            'global_id' =>1,
            'name_translation' => "FOODCOST"
        ),
        array(
            'name'=> "NON FOODCOST",
            'active'=>true,
            'global_id' => 2,
            'name_translation' =>"NON FOODCOST"
        ),
        array(
            'name'=>"PAPERCOST",
            'active'=>true,
            'global_id' =>3,
            'name_translation' =>"PAPERCOST"
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:init:categories:groups')
            ->addArgument('file', InputArgument::OPTIONAL, 'File Name in case of init from csv file.')
            ->setDescription('Command to initialise default categories groups for the platform.');
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
                $this->groups = array();
                if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier, à adapter
                    $output->writeln("---->Import mode: CSV file.");
                    while (($data = fgetcsv(
                            $handle,
                            1000,
                            ";"
                        )) !== false) { // Eléments séparés par un point-virgule, à modifier si necessaire

                        $this->groups[] = array(
                            "name" => $data[0],
                            "active" => boolval($data[1]),
                            "global_id" => $data[2],
                            "name_translation" => $data[3],
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

        } else {
            $output->writeln("---->Import mode: Default.");
        }

        $output->writeln("Start importing groups...");
        $count = 0;

        foreach ($this->groups as $g) {

            $group = $this->em->getRepository(CategoryGroup::class)->findOneBy(['name' => $g['name']]);
            if (is_null($group)) {
                $output->writeln("Import Category group => ".$g['name']);
                $group = new CategoryGroup();
                $group->setName($g['name'])
                    ->setActive(true);
                if ($g['name_translation'] != "") {
                    $group->addNameTranslation('nl', $g['name_translation']);
                } else {
                    $group->addNameTranslation('nl', $g['name']);
                }
                $this->em->persist($group);
                $count++;
                $group->setGlobalId($group->getId());
            }else{
                $output->writeln("-> Category group [".$g['name']."] already exist! Skipping it...");
            }

        }

        $this->em->flush();

        $output->writeln("----> ".$count." groups imported.");
        $output->writeln("==> Groups initialised successfully <==");

    }
}
