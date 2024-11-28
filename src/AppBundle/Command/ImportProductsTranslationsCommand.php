<?php

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use Doctrine\Common\Collections\ArrayCollection;
use Entity\Category;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportProductsTranslationsCommand extends ContainerAwareCommand
{
    /*
     * @var EntityManager
     */
    private $em;
    private $syncService;
    private $dataDir;
    private $items;
    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:import:products:translations')
            ->addArgument('file', InputArgument::OPTIONAL, 'File Name in case of init from file.')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'The format of the import file (json/csv).', 'csv')
            ->setDescription('Command to import products translations.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->syncService = $this->getContainer()->get('sync.create.entry.service');
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/saas/";
        $this->logger = $this->getContainer()->get('monolog.logger.import_commands');
        $this->items = array();

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

                            $this->items[] = array(
                                "id" => $data[0],
                                "origin_restaurant_code" => trim($data[1]),
                                "name" => trim($data[2]),
                                "name_fr" => trim($data[3]),
                                "name_nl" => trim($data[4])
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
                            $itemsData = json_decode($fileData, true);
                        } catch (\Exception $e) {
                            $output->writeln($e->getMessage());

                            return;
                        }

                        foreach ($itemsData as $data) {

                            $this->items[] = array(
                                "id" => $data['id'],
                                "name" => trim($data['name']),
                                "origin_restaurant_code" => trim($data['origin_restaurant_code']),
                                "name_fr" => trim($data['name_fr']),
                                "name_nl" => trim($data['name_nl'])
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

        $output->writeln("Start importing products translations...");

        $updatedItem=0;
        $notFoundCount=0;
        $invalidProductName=0;
        $batchSize = 200;

        foreach ($this->items as $i) {
            $isUpdate = false;
            $product= $this->em->getRepository(Product::class)->find($i['id']);


            if (!$product) {
                $notFoundCount++;
                $output->writeln("Product not found => ".$i['name']);
                continue;
            } else {
                $isUpdate = true;
            }
            /*if(trim($product->getRawName()) != trim($i['name']) ){
                $invalidProductName++;
                $output->writeln("Invalid Product name => ".$product->getName()." < > ".$i['name']);
                continue;
            }*/
            if ($i['name_fr'] !== "") {
                $product->addNameTranslation('fr', $i['name_fr']);
            }
            if ($i['name_nl'] !== "") {
                $product->addNameTranslation('nl', $i['name_nl']);
            }

            $this->em->persist($product);

            if($isUpdate){
                $updatedItem++;
                $output->writeln("Product translation updated => ".$i['name']);
            }

            if (($updatedItem % $batchSize) === 0) {
                $this->em->flush();
                $this->em->clear();
            }

        }
        $this->em->flush();
        $this->em->clear();

        //$output->writeln("----> ".$invalidProductName." invalid products names.");
        $output->writeln("----> ".$notFoundCount." products not founds.");
        $output->writeln("----> ".$updatedItem." products translations updated.");
        $output->writeln("==> Product translations import finished <==");


    }


}
