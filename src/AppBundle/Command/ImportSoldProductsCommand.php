<?php

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\RecipeLine;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\Supervision\Entity\RecipeLineSupervision;
use AppBundle\Supervision\Entity\RecipeSupervision;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportSoldProductsCommand extends ContainerAwareCommand
{

    private $em;
    private $syncService;
    private $dataDir;
    private $logger;
    private $items;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:import:sold:products')
            ->addArgument('file', InputArgument::OPTIONAL, 'File Name in case of init from file.')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'The format of the import file (json/csv).', 'csv')
            ->setDescription('Command to import sold items ( products sold ) for the platform.');
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

                            $recipes = substr(
                                $data[16],
                                1,
                                -1
                            ); // delete [ in the begining and ] in the end of the string
                            $recipes = explode(",", $recipes);

                            $recipes[3] = substr(
                                $recipes[3],
                                2,
                                -2
                            );// delete {{ in the begining and }} in the end of the string
                            $recipes[3] = explode("::", $recipes[3]);
                            //prepare solding canal associative array
                            $recipes[3] = array(
                                'label' => $recipes[3][0],
                                'type' => $recipes[3][1],
                                'wyndMppingColumn' => $recipes[3][2],
                                'default' => boolval($recipes[3][3]),
                            );

                            $recipes[4] = substr(
                                $recipes[4],
                                2,
                                -2
                            );// delete {{ in the begining and }} in the end of the string
                            $recipes[4] = explode("|", $recipes[4]);

                            //prepare recipe lines associative array
                            foreach ($recipes[4] as &$line) {
                                $line = explode("::", $line);
                                $line = array(
                                    'qty' => $line[0],
                                    'supplierCode' => $line[1],
                                    'productPurchasedName' => $line[2],
                                    'productPurchasedExternalId' => $line[3],
                                );
                            }

                            //prepare recipe associative array
                            $recipesArray = array();
                            $recipesArray['globalId'] = $recipes[0];
                            $recipesArray['active'] = boolval($recipes[1]);
                            $recipesArray['revenuePrice'] = $recipes[2];
                            $recipesArray['soldingCanal'] = $recipes[3];
                            $recipesArray['recipeLines'] = $recipes[4];

                            $this->items[] = array(
                                "name" => trim($data[0]),
                                "reference" => $data[1],
                                "active" => $data[2],
                                "global_product_id" => $data[3],
                                "last_date_synchro" => $data[4],
                                "date_synchro" => $data[5],
                                "id" => $data[6],
                                "created_at_in_central" => $data[7],
                                "updated_at_in_central" => $data[8],
                                "type" => $data[9],
                                "code_plu" => $data[10],
                                "external_id" => trim($data[11]),
                                "product_discr" => $data[12],
                                "name_translation" => $data[13],
                                "product_purchased_name" => $data[14],
                                "product_purchased_external_id" => $data[15],
                                "recipes" => $recipesArray,
                                "restaurants" => explode(',', $data[17]),
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
                                "name" => trim($data['name']),
                                "reference" => $data['reference'],
                                "active" => $data['active'],
                                "global_product_id" => $data['globalProductID'],
                                "last_date_synchro" => $data['lastDateSynchro'],
                                "date_synchro" => $data['dateSynchro'],
                                "created_at_in_central" => $data['createdAtInCentral'],
                                "updated_at_in_central" => $data['updatedAtInCentral'],
                                "type" => $data['type'],
                                "id" => $data['id'],
                                "code_plu" => $data['codePlu'],
                                "external_id" => trim($data['externalId']),
                                "product_discr" => $data['product_discr'],
                                "name_translation" => $data['name_translation'],
                                "product_purchased_name" => trim($data['productPurchasedName']),
                                "product_purchased_external_id" => trim($data['productPurchasedExternalId']),
                                "recipes" => !empty($data['recipes']) ? $data['recipes'][0] : null,
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

        $output->writeln("Start importing sold items ( products sold )...");
        $count = 0;
        $updatedItem = 0;
        $productsForSync = array();
        /*$this->em->getClassMetadata(RecipeSupervision::class)->setLifecycleCallbacks(
            array()
        );*/// disbale all event because data already calculated
        foreach ($this->items as $i) {
            $isUpdate = false;
            $productSold = $this->em->getRepository(ProductSoldSupervision::class)->findOneBy(
                array(
                    'codePlu' => trim($i['code_plu']),
                    'name' => trim($i['name'])
                )
            );

            if (!$productSold) {
                $productSold = new ProductSoldSupervision();
            } else {
                $isUpdate = true;
            }

            // check existing restaurant
            foreach ($i['restaurants'] as $code) {
                $restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($code);
                if ($restaurant && !$productSold->getRestaurants()->contains($restaurant)) {
                    $productSold->addRestaurant($restaurant);
                }
            }

            //prepare the product Sold type and get the product purchased in case of non_transformed_product
            $productPurchased = null;
            if (trim($i['type']) === "transformed_product") {
                $type = ProductSoldSupervision::TRANSFORMED_PRODUCT;
            } elseif (trim($i['type']) === "non_transformed_product") {
                $type = ProductSoldSupervision::NON_TRANSFORMED_PRODUCT;

                $productPurchased = $this->em->getRepository(
                    ProductPurchasedSupervision::class
                )->findOneBy(
                    array(
                        "externalId" => $i['product_purchased_external_id'],
                        "name" => $i['product_purchased_name'],
                    )
                );
            } else {

                if (trim($i['recipes']['globalId']) != '' && strtoupper(
                        trim($i['recipes']['globalId'])
                    ) != 'NULL') {
                    $type = ProductSoldSupervision::TRANSFORMED_PRODUCT;

                } elseif ($i['product_purchased_external_id'] != '' && strtoupper(
                        $i['product_purchased_external_id']
                    ) != 'NULL') {
                    $type = ProductSoldSupervision::NON_TRANSFORMED_PRODUCT;
                    $productPurchased = $this->em->getRepository(ProductPurchasedSupervision::class)->findOneBy(
                        array(
                            "externalId" => $i['product_purchased_external_id'],
                            "name" => $i['product_purchased_name'],
                        )
                    );

                } else {
                    $this->logger->info(
                        'Unnhandled product sold type ',
                        array("productPLU" => $i['code_plu'], "name" => $i['name'])
                    );
                    continue;
                }
            }

            //set the product sold basic data
            $productSold
                ->setType($type)
                ->setCodePlu($i['code_plu'])
                ->setName($i['name'])
                ->setActive(boolval($i['active']))
                ->setReference($i['reference']);
            if ($type === ProductSoldSupervision::NON_TRANSFORMED_PRODUCT && !$productPurchased) {
                $this->logger->info(
                    'Product Sold Skipped because of invalid data : no product purchased is assigned for this NON_TRANSFORMED_PRODUCT',
                    array("Product Id" => $i['id'], "productName" => $i['name'])
                );
                continue;
            }
            if ($productPurchased) {
                $productSold->setProductPurchased($productPurchased);
            }

            if ($i['name_translation'] !== "") {
                $productSold->addNameTranslation('nl', $i['name_translation']);
            }

            if($isUpdate){
                foreach ($productSold->getRecipes() as $r){
                    $productSold->removeRecipe($r);
                }
            }
            //add recipes in case of transformed product
            if ($type == ProductSoldSupervision::TRANSFORMED_PRODUCT && !empty($i['recipes'])) {
                $recipeData = $i['recipes'];

                $recipe = $this->em->getRepository(RecipeSupervision::class)->findOneByGlobalId(
                    $recipeData['globalId']
                );
                if (!$recipe) {
                    $recipe = new RecipeSupervision();
                    $recipe
                        ->setGlobalId($recipeData['globalId'])
                        ->setRevenuePrice($recipeData['revenuePrice'])
                        ->setActive(boolval($recipeData['active']));
                    //set recipe solding canal or create new solding canal if not exist
                    $soldingCanal = $this->em->getRepository(SoldingCanal::class)->findOneByLabel(
                        $recipeData['soldingCanal']['label']
                    );
                    if (!$soldingCanal) {
                        $soldingCanal = new SoldingCanal();
                        $soldingCanal->setType($recipeData['soldingCanal']['type']);
                        $soldingCanal->setLabel($recipeData['soldingCanal']['label']);
                        $soldingCanal->setWyndMppingColumn($recipeData['soldingCanal']['wyndMppingColumn']);
                        $soldingCanal->setDefault(boolval($recipeData['soldingCanal']['default']));
                        $this->em->persist($soldingCanal);
                    }
                    $recipe->setSoldingCanal($soldingCanal);

                    //prepare and add recipe lines to recipe
                    foreach ($recipeData['recipeLines'] as $line) {
                        $recipeLine = new RecipeLineSupervision();
                        $recipeLine->setQty($line['qty']);
                        $recipeLine->setSupplierCode($line['supplierCode']);
                        $productPurchased = $this->em->getRepository(ProductPurchasedSupervision::class)->findOneBy(
                            array(
                                "externalId" => trim($line['productPurchasedExternalId']),
                                "name" => trim($line['productPurchasedName']),
                            )
                        );
                        if ($productPurchased) {
                            $recipeLine->setProductPurchased($productPurchased);
                        } else {
                            $this->logger->info(
                                'Recipe Line skipped because product purchased not found :',
                                array(
                                    "productPLU" => $i['code_plu'],
                                    "productPurchasedName" => $line['productPurchasedName'],
                                )
                            );
                            continue;
                        }
                        if (!$recipe->getRecipeLines()->contains($recipeLine)) {
                            $recipe->addRecipeLine($recipeLine);
                            $this->em->flush();
                        }
                    }

                } else {
                    $this->logger->info(
                        'Recipe already found, updating and using it :',
                        array("RecipeGlobalId" => $recipeData['globalId'])
                    );
                    $recipe
                        ->setRevenuePrice($recipeData['revenuePrice'])
                        ->setActive(boolval($recipeData['active']));
                    //set recipe solding canal or create new solding canal if not exist
                    $soldingCanal = $this->em->getRepository(SoldingCanal::class)->findOneByLabel(
                        $recipeData['soldingCanal']['label']
                    );
                    if (!$soldingCanal) {
                        $soldingCanal = new SoldingCanal();
                        $soldingCanal->setType($recipeData['soldingCanal']['type']);
                        $soldingCanal->setLabel($recipeData['soldingCanal']['label']);
                        $soldingCanal->setWyndMppingColumn($recipeData['soldingCanal']['wyndMppingColumn']);
                        $soldingCanal->setDefault(boolval($recipeData['soldingCanal']['default']));
                        $this->em->persist($soldingCanal);
                    }
                    $recipe->setSoldingCanal($soldingCanal);
                    //update the recipeLine if recipe already exist
                    foreach ($recipeData['recipeLines'] as $line){
                        $productPurchased = $this->em->getRepository(ProductPurchasedSupervision::class)->findOneBy(
                            array(
                                "externalId" => trim($line['productPurchasedExternalId']),
                                "name" => trim($line['productPurchasedName']),
                            )
                        );
                        if(!$productPurchased){
                            $this->logger->info(
                                'Recipe Line update skipped because product purchased not found :',
                                array(
                                    "productPLU" => $i['code_plu'],
                                    "productPurchasedName" => $line['productPurchasedName'],
                                )
                            );
                            continue;
                        }
                        $recipe_line=$this->em->getRepository(RecipeLineSupervision::class)->findOneBy(
                            array(
                                "recipe"=>$recipe,
                                "productPurchased"=>$productPurchased,
                                "qty"=>$line['qty']
                            )
                        );
                        if(!$recipe_line){
                            $recipe_line = new RecipeLineSupervision();
                            $recipe_line->setQty($line['qty']);
                            $recipe_line->setSupplierCode($line['supplierCode']);
                            $recipe_line->setProductPurchased($productPurchased);
                            $recipe->addRecipeLine($recipe_line);
                            $this->em->flush();
                        }
                    }
                }

                if ($recipe->getProductSold()) {
                    $newRecipe = clone $recipe;
                    $newRecipe->setProductSold($productSold);
                    $this->em->persist($newRecipe);
                } else {
                    $recipe->setProductSold($productSold);
                }

                $this->em->persist($recipe);
                $productSold->addRecipe($recipe);

            }

            $this->em->persist($productSold);
            if ($i["global_product_id"] != "") {
                $productSold->setGlobalProductID($i["global_product_id"]);
            }

            $productSold->setDateSynchro(new \DateTime('NOW'));

            if ($isUpdate) {
                $updatedItem++;
                $output->writeln("Product sold updated => ".$i['name']);
            } else {
                $count++;
                $output->writeln("Product sold imported => ".$i['name']);
            }

            $productsForSync[] = $productSold;

            $this->em->flush();
        }


        if (count($productsForSync) > 0) {
            $output->writeln("Creating sync command for the added items...");
            $progress = new ProgressBar($output, count($productsForSync));
            $progress->start();
            foreach ($productsForSync as $product) {
                //create sync command to download product to all eligible restaurants
                $this->syncService->createProductSoldEntry($product, true, true);
                $progress->advance();
            }

            $progress->finish();
            $output->writeln("");// just to return to new line
        }


        $output->writeln("----> ".$count." products sold imported.");
        $output->writeln("----> ".$updatedItem." products sold updated.");
        $output->writeln("==> Products sold import finished <==");


    }


}
