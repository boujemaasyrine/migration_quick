<?php

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\SoldingCanal;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class InitSoldingCanalCommand
 * @package AppBundle\Command
 */
class InitSoldingCanalCommand extends ContainerAwareCommand
{
    //Row format :   label ;  type  ;  default ;  wyndMappingColumn ;

    private $em;
    private $dataDir;
    private $soldingCanals = array(
        array(
            "label" => "pos",
            "type" => SoldingCanal::ORIGIN,
            'default' => false,
            'wyndMappingColumn' => 'POS'
        ),
        array(
            "label" => "pos_drive",
            "type" => SoldingCanal::ORIGIN,
            'default' => false,
            'wyndMappingColumn' => 'DriveThru'
        ),
        array(
            "label" => "allcanals",
            "type" => SoldingCanal::DESTINATION,
            'default' => true,
            'wyndMappingColumn' => 'allcanals'
        ),
        array(
            "label" => "onsite",
            "type" => SoldingCanal::DESTINATION,
            'default' => false,
            'wyndMappingColumn' => 'EatIn'
        ),
        array(
            "label" => "takeaway",
            "type" => SoldingCanal::DESTINATION,
            'default' => false,
            'wyndMappingColumn' => 'TakeOut'
        ),
        array(
            "label" => "drive",
            "type" => SoldingCanal::DESTINATION,
            'default' => false,
            'wyndMappingColumn' => 'DriveThru'
        ),
        array(
            "label" => "kiosk",
            "type" => SoldingCanal::ORIGIN,
            'default' => false,
            'wyndMappingColumn' => 'KIOSK'
        )
        ,
        array(
            "label" => "e_ordering",
            "type" => SoldingCanal::ORIGIN,
            'default' => false,
            'wyndMappingColumn' => 'MyQuick'
        )
    ,
        array(
            "label" => "e_ordering_in",
            "type" => SoldingCanal::DESTINATION,
            'default' => false,
            'wyndMappingColumn' => 'MyQuickEatIn'
        )
    ,
        array(
            "label" => "e_ordering_out",
            "type" => SoldingCanal::DESTINATION,
            'default' => false,
            'wyndMappingColumn' => 'MyQuickTakeout'
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:init:solding:canal')
            ->addArgument('file', InputArgument::OPTIONAL, 'File Name in case of init from csv file.')
            ->setDescription('Command to initialise default categories solding Canals for the platform.');
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
                $this->soldingCanals = array();
                if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier, à adapter
                    $output->writeln("---->Import mode: CSV file.");
                    while (($data = fgetcsv(
                            $handle,
                            1000,
                            ";"
                        )) !== false) { // Eléments séparés par un point-virgule, à modifier si necessaire

                        $this->soldingCanals[] = array(
                            "label" => $data[0],
                            "type" => $data[1],
                            'default' => boolval($data[2]),
                            'wyndMappingColumn' => $data[3]
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

        $output->writeln("Start importing solding Canals...");
        $count = 0;
        foreach ($this->soldingCanals as $s) {

            $soldingCanal = $this->em->getRepository(SoldingCanal::class)->findOneByLabel($s['label']);
            if (is_null($soldingCanal)) {
                $output->writeln("Import Solding Canal => ".$s['label']);
                $soldingCanal = new SoldingCanal();
                $soldingCanal
                    ->setLabel($s['label'])
                    ->setWyndMppingColumn($s['wyndMappingColumn'])
                    ->setType($s['type'])
                    ->setDefault($s['default']);

                $this->em->persist( $soldingCanal);
                $soldingCanal->setGlobalId( $soldingCanal->getId());
                $count++;
            }else{
                $output->writeln("-> Solding canal [".$s['label']."] already exist ! Skipping it...");
            }

        }

        $this->em->flush();

        $output->writeln("----> ".$count." Solding Canals imported.");
        $output->writeln("==> Solding Canals successfully <==");

    }

}
