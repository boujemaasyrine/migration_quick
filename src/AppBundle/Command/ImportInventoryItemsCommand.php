<?php

namespace AppBundle\Command;

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

class ImportInventoryItemsCommand extends ContainerAwareCommand
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
            ->setName('saas:import:inventory:items')
            ->addArgument('file', InputArgument::OPTIONAL, 'File Name in case of init from file.')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'The format of the import file (json/csv).', 'csv')
            ->setDescription('Command to import Inventory items for the platform.');
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
                                "name" => $data[1],
                                "name_translation" => $data[2],
                                "global_product_id" => $data[3],
                                "reference" => $data[4],
                                "active" => boolval($data[5]),
                                "external_id" => $data[6],
                                "status" => $data[7],
                                "type" => $data[8],
                                "storage_condition" => $data[9],
                                "buying_cost" => $data[10],
                                "label_unit_exped" => $data[11],
                                "label_unit_inventory" => $data[12],
                                "label_unit_usage" => $data[13],
                                "inventory_qty" => $data[14],
                                "usage_qty" => $data[15],
                                "id_item_inv" => $data[16],
                                "dlc" => empty(trim($data[17])) ? null : trim($data[17]),
                                "category_name" => $data[18],
                                "suppliers" => explode(',', $data[19]),
                                "restaurants" => explode(',', $data[20]),
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
                                "name_translation" => $data['name_translation'],
                                "global_product_id" => $data['global_product_id'],
                                "reference" => $data['reference'],
                                "active" => $data['active'],
                                "external_id" => trim($data['external_id']),
                                "status" => $data['status'],
                                "type" => $data['type'],
                                "storage_condition" => $data['storage_condition'],
                                "buying_cost" => $data['buying_cost'],
                                "label_unit_exped" => $data['label_unit_exped'],
                                "label_unit_inventory" => $data['label_unit_inventory'],
                                "label_unit_usage" => $data['label_unit_usage'],
                                "inventory_qty" => $data['inventory_qty'],
                                "usage_qty" => $data['usage_qty'],
                                "id_item_inv" => $data['id_item_inv'],
                                "dlc" => $data['dlc'],
                                "category_name" => $data['category_name'],
                                "suppliers" => $data['suppliers'],
                                "restaurants" => $data['restaurants'],
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

        $output->writeln("Start importing inventory items ( products purchased )...");
        $count = 0;
        $updatedItem=0;
        $missingCategory = array();
        $missingSuppliers = array();
        $productsForSync = array();

        foreach ($this->items as $i) {
            $isUpdate = false;
            /*$item = $this->em->getRepository(ProductPurchasedSupervision::class)->findOneBy(
                array(
                    'externalId' => $i['external_id'],
                    'name' => $i['name']
                )
            );*/
            $item = $this->em->getRepository(ProductPurchasedSupervision::class)->createQueryBuilder('p')
                ->join("p.suppliers",'s')
                ->where("p.externalId = :external_id")
                ->andWhere("s.name = :supplierName")
                ->setParameter("external_id",$i['external_id'])
                ->setParameter("supplierName",$i['suppliers'][0])
                ->getQuery()->getOneOrNullResult();

            if (!$item) {
                $item = new ProductPurchasedSupervision();
            } else {
                $isUpdate = true;
            }
            // check existing category
            $category = $this->em->getRepository(ProductCategories::class)->findOneBy(
                array('name' => $i['category_name'])
            );
            if (is_null($category)) {
                $missingCategory[] = $i['category_name'];
                $this->logger->info(
                    'Product Purchased Skipped because category doesn\'t exist : ',
                    array("Product Id" => $i['id'], "Category" => $i['category_name'])
                );
                continue;
            }
            $item->setProductCategory($category);

            // check existing supplier
            $supplier = $this->em->getRepository(Supplier::class)->findOneBy(['name' => $i['suppliers'][0]]);
            if (is_null($supplier)) {
                $missingSuppliers[] = $i['suppliers'][0];
                $this->logger->info(
                    'Product Purchased Skipped because supplier doesn\'t exist : ',
                    array("Product Id" => $i['id'], "Supplier" => $i['suppliers'][0])
                );
                continue;
            }

            //remove related restaurants and suppliers iin case of update
            if($isUpdate){
                foreach ($item->getRestaurants() as $r){
                    $item->removeRestaurant($r);
                }
                foreach ($item->getSuppliers() as $s){
                    $item->removeSupplier($s);
                }
            }
            $item->addSupplier($supplier);
            // check existing restaurant
            foreach ($i['restaurants'] as $code) {
                $restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($code);
                if ($restaurant && !$item->getRestaurants()->contains($restaurant)) {
                    $item->addRestaurant($restaurant);
                }
            }

            $item
                ->setStatus($i['status'])
                ->setType($i['type'])
                ->setStorageCondition($i['storage_condition'])
                ->setBuyingCost($i['buying_cost'])
                ->setLabelUnitExped($i['label_unit_exped'])
                ->setLabelUnitInventory($i['label_unit_inventory'])
                ->setLabelUnitUsage($i['label_unit_usage'])
                ->setInventoryQty($i['inventory_qty'])
                ->setUsageQty($i['usage_qty'])
                ->setIdItemInv($i['id_item_inv'])
                ->setDlc($i['dlc'])
                ->setActive($i['active'])
                ->setName($i['name'])
                ->setReference($i['reference']);
            $item->setExternalId($i['external_id']);

            if ($i['name_translation'] !== "") {
                $item->addNameTranslation('nl', $i['name_translation']);
            }

            $this->em->persist($item);
            if ($i["global_product_id"] != "") {
                $item->setGlobalProductID($i["global_product_id"]);
            }

            $item->setDateSynchro(new \DateTime('NOW'));

            if($isUpdate){
                $updatedItem++;
                $output->writeln("Inventory item updated => ".$i['name']);
            }else{
                $count++;
                $output->writeln("Inventory item imported => ".$i['name']);
            }
            $productsForSync[] = $item;


            $this->em->flush();

        }


        if (count($productsForSync) > 0) {
            $output->writeln("Creating sync command for the added items...");
            $progress = new ProgressBar($output, count($productsForSync));
            $progress->start();

            foreach ($productsForSync as $product) {
                //create sync command to download product to all eligible restaurants
                $this->syncService->createProductPurchasedEntry($product, true, null, true);
                $progress->advance();
            }
            $progress->finish();
            $output->writeln("");// just to return to new line
        }


        if (!empty($missingSuppliers)) {
            $output->writeln(
                "-> ".count($missingSuppliers)." inventory items import failed because of unknown suppliers!"
            );
            foreach ($missingSuppliers as $ms) {
                $output->writeln("- ".$ms." : unknown supplier code.");
            }
        }
        if (!empty($missingCategory)) {
            $output->writeln(
                "-> ".count($missingCategory)." inventory items import failed because of unknown categories!"
            );
            foreach ($missingCategory as $mc) {
                $output->writeln("- ".$mc." : unknown categories.");
            }
        }

        $output->writeln("----> ".$count." inventory items imported.");
        $output->writeln("----> ".$updatedItem." inventory items updated.");
        $output->writeln("==> Inventory items import finished <==");


    }


}
