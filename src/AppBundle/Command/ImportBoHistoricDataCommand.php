<?php

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductPurchasedHistoric;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\ProductSoldHistoric;
use AppBundle\Merchandise\Entity\RecipeHistoric;
use AppBundle\Merchandise\Entity\RecipeLineHistoric;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Merchandise\Entity\Supplier;
use Entity\Category;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ImportBoHistoricDataCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    private $dataDir;

    private $logger;

    private $restaurant;
    private $restaurantCode;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:import:bo:historic:data')
            ->addArgument('restaurantCode', InputArgument::OPTIONAL)
            ->setDescription('Import restaurant stock historic data form json file exported by a BO instance.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/saas/";
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->logger = $this->getContainer()->get('monolog.logger.import_commands');

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ($input->getArgument('restaurantCode')) {
            $this->restaurantCode = trim($input->getArgument('restaurantCode'));
        } else {
            $helper = $this->getHelper('question');
            $question = new Question(
                'Please enter restaurant code (found at the end of json file name : historicData_restaurant_xxxx.json ) :'
            );
            $question->setValidator(
                function ($answer) {
                    if (!is_string($answer) || strlen($answer) < 1) {
                        throw new \RuntimeException(
                            'Please enter the restaurnat code!'
                        );
                    }
                    return trim($answer);
                }
            );
            $this->restaurantCode = $helper->ask($input, $output, $question);
        }
        $filename = "historicData_restaurant_".$this->restaurantCode.".json";
        $filePath = $this->dataDir.$filename;

        if (!file_exists($filePath)) {
            $output->writeln("No import file with the '".$this->restaurantCode."' restaurant code found !");
            return;
        }
        try {
            $fileData = file_get_contents($filePath);
            $historicData = json_decode($fileData, true);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return;
        }



        /************ Start the import process *****************/


        try {
            $this->restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($historicData['restaurant_code']);

            if (!$this->restaurant) {
                $output->writeln("No restaurant with the '".$historicData['restaurant_code']."' exist! Command failed... ");
                $output->writeln("->Please add this restaurant first.");
                return;
            }
            $output->writeln("Restaurant ".$this->restaurant->getName()." historic data import started...");
            $batchCounter = 0;


            //import product Purchased Historic
            $output->writeln("Importing Product Purchased Historic...");
            $productPurchasedHistorics=$historicData['productPurchasedHistoric'];

            $progress = new ProgressBar($output, count($productPurchasedHistorics));
            $progress->start();
            $addedProductPurchasedHistoric=0;
            $updatedProductPurchasedHistoric=0;
            $skippedProductPurchasedHistoric=0;
            foreach ($productPurchasedHistorics as $productPurchasedHistoric) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($productPurchasedHistoric) || !array_key_exists('id', $productPurchasedHistoric)) {
                    continue;
                }
                $startDate = $productPurchasedHistoric['startDate']['date'] ? new \DateTime($productPurchasedHistoric['startDate']['date']) : null;
                $dlc = $productPurchasedHistoric['dlc'] ? new \DateTime($productPurchasedHistoric['dlc']['date']) : null;
                $deactivationDate = $productPurchasedHistoric['deactivationDate'] ? new \DateTime($productPurchasedHistoric['deactivationDate']['date']) : null;
                $createdAt = $productPurchasedHistoric['createdAt'] ? new \DateTime($productPurchasedHistoric['createdAt']['date']) : null;

                $productPurchasedHistoricEntity = $this->em->getRepository(ProductPurchasedHistoric::class)->findOneBy(
                    array(
                        "externalId" => $productPurchasedHistoric['externalId'],
                        "originRestaurant" => $this->restaurant,
                        "createdAt" => $createdAt,
                        "startDate" => $startDate
                    )
                );
                if (!$productPurchasedHistoricEntity) {
                    $productPurchasedHistoricEntity = new ProductPurchasedHistoric();
                } else {
                    $isUpdate = true;
                }

                $productPurchasedHistoricEntity
                    ->setStartDate($startDate)
                    ->setCreatedAt($createdAt)
                    ->setName($productPurchasedHistoric['name'])
                    ->setReference($productPurchasedHistoric['reference'])
                    ->setStockCurrentQty(floatval($productPurchasedHistoric['stockCurrentQty']))
                    ->setActive(boolval($productPurchasedHistoric['active']))
                    ->setType($productPurchasedHistoric['type'])
                    ->setExternalId($productPurchasedHistoric['externalId'])
                    ->setStorageCondition($productPurchasedHistoric['storageCondition'])
                    ->setBuyingCost(floatval($productPurchasedHistoric['buyingCost']))
                    ->setStatus($productPurchasedHistoric['status'])
                    ->setDeactivationDate($deactivationDate)
                    ->setDlc($dlc)
                    ->setLabelUnitExped($productPurchasedHistoric['labelUnitExped'])
                    ->setLabelUnitInventory($productPurchasedHistoric['labelUnitInventory'])
                    ->setLabelUnitUsage($productPurchasedHistoric['labelUnitUsage'])
                    ->setInventoryQty(floatval($productPurchasedHistoric['inventoryQty']))
                    ->setUsageQty(floatval($productPurchasedHistoric['usageQty']))
                    ->setIdItemInv($productPurchasedHistoric['idItemInv']);

                $supplier = $this->em->getRepository(Supplier::class)->findOneByCode($productPurchasedHistoric['supplierCode']);
                if ($supplier) {
                    if(!$productPurchasedHistoricEntity->getSupplier()->contains($supplier)){
                        $productPurchasedHistoricEntity->addSupplier($supplier);
                    }
                } else {
                    $skippedProductPurchasedHistoric++;
                    $this->logger->info('Product Purchased Historic Skipped because Supplier not found : ', array("SupplierCode" => $productPurchasedHistoric['supplierCode'], "externalId" => $productPurchasedHistoric['externalId'], "name" => $productPurchasedHistoric['name'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }

                $category = $this->em->getRepository(ProductCategories::class)->findOneByName($productPurchasedHistoric['categoryName']);
                if ($category) {
                    $productPurchasedHistoricEntity->setProductCategory($category);
                } else {
                    $skippedProductPurchasedHistoric++;
                    $this->logger->info('Product Purchased Historic Skipped because Category not found : ', array("CategoryName" => $productPurchasedHistoric['categoryName'], "externalId" => $productPurchasedHistoric['externalId'], "name" => $productPurchasedHistoric['name'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }


                $originalProduct = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                    array(
                        "externalId" => $productPurchasedHistoric['externalId'],
                        "name" => $productPurchasedHistoric['name'],
                        "originRestaurant" => $this->restaurant
                    )
                );
                if ($originalProduct) {
                    $productPurchasedHistoricEntity->setOriginalID($originalProduct->getId());
                }

                if ($productPurchasedHistoric['primaryItem']) {
                    $primaryItem = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                        array(
                            "externalId" => $productPurchasedHistoric['primaryItem']['external_id'],
                            "name" => $productPurchasedHistoric['primaryItem']['name'],
                            "originRestaurant" => $this->restaurant
                        )
                    );
                    if ($primaryItem) {
                        $productPurchasedHistoricEntity->setPrimaryItem($primaryItem);
                    } else {
                        $skippedProductPurchasedHistoric++;
                        $this->logger->info('Product Purchased Historic Skipped because Primary Item not found : ', array("primaryItemExternalId" => $productPurchasedHistoric['primaryItem']['external_id'], "externalId" => $productPurchasedHistoric['externalId'], "name" => $productPurchasedHistoric['name'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }
                if ($productPurchasedHistoric['secondaryItem']) {
                    $secondaryItem = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                        array(
                            "externalId" => $productPurchasedHistoric['secondaryItem']['external_id'],
                            "name" => $productPurchasedHistoric['secondaryItem']['name'],
                            "originRestaurant" => $this->restaurant
                        )
                    );
                    if ($secondaryItem) {
                        $productPurchasedHistoricEntity->setSecondaryItem($secondaryItem);
                    } else {
                        $skippedProductPurchasedHistoric++;
                        $this->logger->info('Product Purchased Historic Skipped because Secondary Item not found : ', array("secondaryItemExternalId" => $productPurchasedHistoric['secondaryItem']['external_id'], "externalId" => $productPurchasedHistoric['externalId'], "name" => $productPurchasedHistoric['name'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }

                $productPurchasedHistoricEntity->setOriginRestaurant($this->restaurant);
                $this->em->persist($productPurchasedHistoricEntity);
                $isUpdate ? $updatedProductPurchasedHistoric++ :  $addedProductPurchasedHistoric++;
                $this->flush($batchCounter);

            }

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedProductPurchasedHistoric." Product Purchased Historics were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedProductPurchasedHistoric." Product Purchased Historics were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedProductPurchasedHistoric." Product Purchased Historics were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            $this->em->flush();

            /////////////////////////////////////////////
            //import Product Sold Historic

            $output->writeln("Importing Product Sold Historic...");
            $productSoldHistorics=$historicData['productSoldHistoric'];

            $progress = new ProgressBar($output, count($productSoldHistorics));
            $progress->start();
            $addedProductSoldHistorics=0;
            $updatedProductSoldHistorics=0;
            $skippedProductSoldHistorics=0;
            foreach ($productSoldHistorics as $productSoldHistoric) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($productSoldHistoric) || !array_key_exists('id', $productSoldHistoric)) {
                    continue;
                }

                $createdAt = new \DateTime($productSoldHistoric['createdAt']['date']);
                $startDate = $productSoldHistoric['startDate']['date'] ? new \DateTime($productSoldHistoric['startDate']['date']) : null;

                $productSoldHistoricEntity = $this->em->getRepository(ProductSoldHistoric::class)->findOneBy(
                    array(
                        "codePlu" => $productSoldHistoric['codePlu'],
                        "originRestaurant" => $this->restaurant,
                        "createdAt" => $createdAt,
                        "startDate" => $startDate
                    )
                );

                if (!$productSoldHistoricEntity) {
                    $productSoldHistoricEntity = new ProductSoldHistoric();
                } else {
                    $isUpdate = true;
                }

                $productSoldHistoricEntity
                    ->setName($productSoldHistoric['name'])
                    ->setReference($productSoldHistoric['reference'])
                    ->setActive(boolval($productSoldHistoric['active']))
                    ->setCodePlu($productSoldHistoric['codePlu'])
                    ->setType($productSoldHistoric['type'])
                    ->setGlobalId($productSoldHistoric['globalId'])
                    ->setCreatedAt($createdAt)
                    ->setStartDate($startDate);
                //remove all old recipe historic if is an update
                if($isUpdate){
                    foreach ($productSoldHistoricEntity->getRecipes() as $recipe){
                        $this->em->remove($recipe);
                    }
                    $this->em->flush();
                }

                if ($productSoldHistoric['type'] === ProductSold::TRANSFORMED_PRODUCT) {

                    foreach ($productSoldHistoric['recipes'] as $recipe) {
                        $recipeCp = new RecipeHistoric();

                        $soldingCanal = $this->em->getRepository(SoldingCanal::class)->findOneBy(
                            array(
                                "label" => $recipe['soldingCanal']['label'],
                                "wyndMppingColumn" => $recipe['soldingCanal']['wyndMppingColumn'],
                                "type" => $recipe['soldingCanal']['type']
                            )
                        );
                        if (!$soldingCanal) {
                            $this->logger->info('Product Sold Historic Skipped because Solding Canal not found : ', array("SoldingCanal" => $recipe['soldingCanal']['label'], "PLU" => $productSoldHistoric['codePlu'], "name" => $productSoldHistoric['name'], "Restaurant" => $this->restaurant->getName()));
                            $skippedProductSoldHistorics++;
                            continue 2;
                        }

                        $recipeCp
                            ->setSoldingCanal($soldingCanal)
                            ->setExternalId($recipe['externalId'])
                            ->setGlobalId($recipe['globalId'])
                            ->setActive(boolval($recipe['active']));

                        foreach ($recipe['recipeLines'] as $recipeLine) {
                            $recipeLineCp = new RecipeLineHistoric();

                            if ($recipeLine['productPurchasedExternalId']) {
                                $productPurchased = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                                    array(
                                        "externalId" => $recipeLine['productPurchasedExternalId'],
                                        "name" => $recipeLine['productPurchasedName'],
                                        "originRestaurant" => $this->restaurant
                                    )
                                );
                                $recipeLineCp->setProductPurchased($productPurchased);
                            }
                            if ($recipeLine['productPurchasedHistoric']) {
                                $date = new \DateTime($recipeLine['productPurchasedHistoric']['createdAt']);
                                $productPurchasedHistoric = $this->em->getRepository(
                                    ProductPurchasedHistoric::class
                                )->findOneBy(
                                    array(
                                        "externalId" => $recipeLine['productPurchasedHistoric']['externalId'],
                                        "createdAt" => $date,
                                        "originRestaurant" => $this->restaurant
                                    )
                                );
                                if ($productPurchasedHistoric) {
                                    $recipeLineCp->setProductPurchasedHistoric($productPurchasedHistoric);
                                }
                            }

                            $recipeLineCp
                                ->setQty($recipeLine['qty']);

                            $recipeCp->addRecipeLine($recipeLineCp);
                        }
                        $productSoldHistoricEntity->addRecipe($recipeCp);
                    }

                } else {
                    $productPurchased = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                        array(
                            "externalId" => $productSoldHistoric['productPurchased']['external_id'],
                            "name" => $productSoldHistoric['productPurchased']['name'],
                            "originRestaurant" => $this->restaurant
                        )
                    );
                    if ($productPurchased) {
                        $productSoldHistoricEntity->setProductPurchased($productPurchased);
                    }

                    if ($productSoldHistoric['productPurchasedHistoric']) {
                        $date = new \DateTime($productSoldHistoric['productPurchasedHistoric']['createdAt']);
                        $productPurchasedHistoric = $this->em->getRepository(
                            ProductPurchasedHistoric::class
                        )->findOneBy(
                            array(
                                "externalId" => $productSoldHistoric['productPurchasedHistoric']['external_id'],
                                "name" => $productSoldHistoric['productPurchasedHistoric']['name'],
                                "createdAt" => $date,
                                "originRestaurant" => $this->restaurant
                            )
                        );
                        if ($productPurchasedHistoric) {
                            $productSoldHistoricEntity->setProductPurchasedHistoric($productPurchasedHistoric);
                        }
                    }
                }
                $productSoldHistoricEntity->setOriginRestaurant($this->restaurant);
                $this->em->persist($productSoldHistoricEntity);
                $isUpdate ? $updatedProductSoldHistorics++ : $addedProductSoldHistorics++;
                $this->flush($batchCounter);

            }

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedProductSoldHistorics." Product Sold Historics were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedProductSoldHistorics." Product Sold Historics were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedProductSoldHistorics." Product Sold Historics were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            $this->em->flush();
            $this->em->clear();


        }catch (\Exception $e){
            $output->writeln("");
            $output->writeln("Command failed ! ");
            $output->writeln($e->getMessage());
            return;
        }

        $progress->finish();
        $output->writeln("\n====> Restaurant [".$this->restaurant->getName()."] historic stock data imported successfully.");

    }

    //doctrine Batch Processing
    public function flush($i)
    {
        if ($i % 100 === 0) {
            $this->em->flush();
            $this->em->clear();
            gc_collect_cycles();
            $this->restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($this->restaurantCode);
        }
    }

}
