<?php

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\InventoryLine;
use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductPurchasedHistoric;
use AppBundle\Merchandise\Entity\ProductPurchasedMvmt;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\RecipeHistoric;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Entity\SheetModelLine;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Staff\Entity\Employee;
use AppBundle\Supervision\Entity\ProductSupervision;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\Constraints\DateTime;

class ImportBoStockDataCommand extends ContainerAwareCommand
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
            ->setName('saas:import:bo:stock:data')
            ->addArgument('restaurantCode', InputArgument::OPTIONAL)
            ->setDescription('Import restaurant stock data form json file exported by a BO instance.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir') . "/../data/import/saas/";
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
                'Please enter restaurant code (found at the end of json file name : stockData_restaurant_xxxx.json ) :'
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
        $filename = "stockData_restaurant_" . $this->restaurantCode . ".json";
        $filePath = $this->dataDir . $filename;

        if (!file_exists($filePath)) {
            $output->writeln("No import file with the '" . $this->restaurantCode . "' restaurant code found !");
            return;
        }
        try {
            $fileData = file_get_contents($filePath);
            $stockData = json_decode($fileData, true);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return;
        }


        /************ Start the import process *****************/


        try {
            $this->restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($stockData['restaurant_code']);

            if (!$this->restaurant) {
                $output->writeln("No restaurant with the '" . $stockData['restaurant_code'] . "' exist! Command failed... ");
                $output->writeln("->Please add this restaurant first.");
                return;
            }
            $restaurantId = $this->restaurant->getId();
            $output->writeln("Restaurant " . $this->restaurant->getName() . " stock data import started...");
            $batchCounter = 0;


            //import sheet models
            $output->writeln("Importing Sheet Models...");
            $sheetModels = $stockData['sheetModel'];

            $progress = new ProgressBar($output, count($sheetModels));
            $progress->start();
            $addedSheetModels = 0;
            $updatedSheetModels = 0;
            $skippedSheetModels = 0;
            foreach ($sheetModels as $sheetModel) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($sheetModel) || !array_key_exists('id', $sheetModel)) {
                    continue;
                }

                $sheetModelEntity = $this->em->getRepository(SheetModel::class)->findOneBy(
                    array(
                        "importId" => $sheetModel['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );

                if (!$sheetModelEntity) {
                    $sheetModelEntity = new SheetModel();
                } else {
                    $isUpdate = true;
                }


                if(empty($sheetModel['employee']['wyndId'])){
                    $userName=$sheetModel['employee']['username'];
                }else{
                    $userName=$this->restaurantCode."_".$sheetModel['employee']['username'];
                }
                $employee = $this->em->getRepository(Employee::class)->findOneBy(
                    array(
                        "username" => $userName
                    )
                );
                if ($employee) {
                    $sheetModelEntity->setEmployee($employee);
                } else {
                    $skippedSheetModels++;
                    $this->logger->info('Sheet Model Skipped because employee doesn\'t exist : ', array("sheetModelLabel" => $sheetModel['label'], "username" => $sheetModel['employee']['username'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }

                $sheetModelEntity->setOriginRestaurant($this->em->getReference(Restaurant::class, $restaurantId));
                $sheetModelEntity->setImportId($sheetModel['id'] . "_" . $this->restaurantCode);
                $sheetModelEntity->setDeleted(boolval($sheetModel['deleted']));
                $sheetModelEntity
                    ->setLinesType($sheetModel['linesType'])
                    ->setType($sheetModel['type'])
                    ->setLabel($sheetModel['label']);

                foreach ($sheetModel['lines'] as $line) {
                    $sheetModelLine = $this->em->getRepository(SheetModelLine::class)->findOneBy(array('importId' => $line['id'] . "_" . $this->restaurantCode));
                    if (!$sheetModelLine) {
                        $sheetModelLine = new SheetModelLine();
                        $sheetModelEntity->addLine($sheetModelLine);
                    }
                    $product = $this->em->getRepository(Product::class)->findOneBy(array('globalProductID' => $line['productGlobalId'], "originRestaurant" => $this->restaurant));
                    if ($product) {
                        $sheetModelLine->setProduct($product);
                    } else {
                        $this->logger->info('Product not found for sheetModelLine, skipping it: ', array("sheetModelId" => $sheetModel['id'], "sheetModelLineId" => $line['id'], "productGlobalId" => $line['productGlobalId'], "Restaurant" => $this->restaurant->getName()));
                        $sheetModelEntity->removeLine($sheetModelLine);
                        continue;
                    }
                    $sheetModelLine
                        ->setCnt(floatval($line['cnt']))
                        ->setOrderInSheet($line['orderInSheet']);
                    $sheetModelLine->setImportId($line['id'] . "_" . $this->restaurantCode);
                    $this->em->persist($sheetModelLine);
                }

                $this->em->persist($sheetModelEntity);
                $isUpdate ? $updatedSheetModels++ : $addedSheetModels++;
                $this->flush($batchCounter);

            }

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> " . $addedSheetModels . " sheet models were added successfully for [" . $this->restaurant->getName() . "] restaurant.");
            $output->writeln("--> " . $updatedSheetModels . " sheet models were updated successfully for [" . $this->restaurant->getName() . "] restaurant.");
            $output->writeln("--> " . $skippedSheetModels . " sheet models were skipped because of missing data or they already exist in [" . $this->restaurant->getName() . "] restaurant.");

            $this->em->flush();


            /////////////////////////////////////////////
            //import inventory sheets
            $output->writeln("Importing Inventory Sheet...");
            $inventorySheets = $stockData['inventorySheet'];

            $progress = new ProgressBar($output, count($inventorySheets));
            $progress->start();
            $addedInventorySheets = 0;
            $updatedInventorySheets = 0;
            $skippedInventorySheets = 0;
            foreach ($inventorySheets as $inventorySheet) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($inventorySheet) || !array_key_exists('id', $inventorySheet)) {
                    continue;
                }

                unset($userName);
                if(empty($inventorySheet['employee']['wyndId'])){
                    $userName=$inventorySheet['employee']['username'];
                }else{
                    $userName=$this->restaurantCode."_".$inventorySheet['employee']['username'];
                }
                $employee = $this->em->getRepository(Employee::class)->findOneBy(
                    array(
                        "username" => $userName
                    )
                );
                if (!$employee) {
                    $skippedInventorySheets++;
                    $this->logger->info('Inventory Sheet Skipped because Employee doesn\'t exist: ', array("id" => $inventorySheet['id'], "UserName" => $inventorySheet['employee']['username'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }

                $createdAt = new \DateTime($inventorySheet['createdAt']['date']);

                $inventorySheetEntity = $this->em->getRepository(InventorySheet::class)->findOneBy(
                    array(
                        "importId" => $inventorySheet['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );

                if (!$inventorySheetEntity) {
                    $inventorySheetEntity = new InventorySheet();
                } else {
                    $isUpdate = true;
                }

                if (array_key_exists('sheetModel', $inventorySheet)) {
                    $sheetModel = $this->em->getRepository(SheetModel::class)->findOneBy(
                        array(
                            "importId" => $inventorySheet['sheetModel']['id'] . "_" . $this->restaurantCode,
                            "originRestaurant" => $this->restaurant
                        )
                    );
                    if (!$sheetModel) {
                        $skippedInventorySheets++;
                        $this->logger->info('Inventory Sheet Skipped because Sheet Model doesn\'t exist: ', array("id" => $inventorySheet['id'], "sheetModelId" => $inventorySheet['sheetModel']['id'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                    $inventorySheetEntity->setSheetModel($sheetModel);
                }else{
                    $skippedInventorySheets++;
                    $this->logger->info('Inventory Sheet Skipped because Sheet Model not setted: ', array("id" => $inventorySheet['id'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }

                $inventorySheetEntity
                    ->setCreatedAt($createdAt)
                    ->setSheetModelLabel($inventorySheet['sheetModelLabel'])
                    ->setStatus($inventorySheet['status'])
                    ->setFiscalDate(new \DateTime($inventorySheet['fiscalDate']['date']))
                    ->setOriginRestaurant($this->restaurant);

                $inventorySheetEntity->setEmployee($employee);
                $inventorySheetEntity->setImportId($inventorySheet['id'] . "_" . $this->restaurantCode);

                foreach ($inventorySheet['lines'] as $line) {
                    $inventoryLine = $this->em->getRepository(InventoryLine::class)->findOneBy(array('importId' => $line['id'] . "_" . $this->restaurantCode));
                    if (!$inventoryLine) {
                        $inventoryLine = new InventoryLine();
                        $inventorySheetEntity->addLine($inventoryLine);
                    }
                    $product = $this->em->getRepository(ProductPurchased::class)->findOneBy(array('globalProductID' => $line['productGlobalId'], "originRestaurant" => $this->restaurant));
                    if ($product) {
                        $inventoryLine->setProduct($product);
                    } else {
                        $this->logger->info('Product not found for inventorySheet Line : ', array("inventorySheetId" => $inventorySheet['id'], "inventoryLineId" => $line['id'], "productGlobalId" => $line['productGlobalId'], "Restaurant" => $this->restaurant->getName()));
                        $inventorySheetEntity->removeLine($inventoryLine);
                        continue;
                    }
                    if ($line['productPurchasedHistoric']) {
                        $productPurchasedHistoric = $this->em->getRepository(ProductPurchasedHistoric::class)->findOneBy(
                            array(
                                'externalId' => $line['productPurchasedHistoric']['external_id'],
                                'name' => $line['productPurchasedHistoric']['name'],
                                'originRestaurant' => $this->restaurant
                            )
                        );
                        if ($productPurchasedHistoric) {
                            $inventoryLine->setProductPurchasedHistoric($productPurchasedHistoric);
                        }
                    }
                    $inventoryLine
                        ->setTotalInventoryCnt(floatval($line['totalInventoryCnt']))
                        ->setInventoryCnt(floatval($line['inventoryCnt']))
                        ->setUsageCnt(floatval($line['usageCnt']))
                        ->setExpedCnt(floatval($line['expedCnt']));

                    $inventoryLine->setImportId($line['id'] . "_" . $this->restaurantCode);
                    $this->em->persist($inventoryLine);

                }

                $this->em->persist($inventorySheetEntity);
                $isUpdate ? $updatedInventorySheets++ : $addedInventorySheets++;
                $this->flush($batchCounter);
            }


            $progress->finish();
            $output->writeln("");
            $output->writeln("--> " . $addedInventorySheets . " inventory sheets were added successfully for [" . $this->restaurant->getName() . "] restaurant.");
            $output->writeln("--> " . $updatedInventorySheets . " inventory sheets were updated successfully for [" . $this->restaurant->getName() . "] restaurant.");
            $output->writeln("--> " . $skippedInventorySheets . " invenotory sheets were skipped because of missing data or they already exist in [" . $this->restaurant->getName() . "] restaurant.");

            $this->em->flush();


            /////////////////////////////////////////////
            //import loss sheets
            $output->writeln("Importing Loss Sheets...");
            $lossSheets = $stockData['lossSheet'];

            $progress = new ProgressBar($output, count($lossSheets));
            $progress->start();
            $addedLossSheets = 0;
            $updatedLossSheets = 0;
            $skippedLossSheets = 0;
            foreach ($lossSheets as $lossSheet) {
                $batchCounter++;
                $isUpdate = false;
                $progress->advance();
                if (empty($lossSheet) || !array_key_exists('id', $lossSheet)) {
                    continue;
                }
                if (!array_key_exists('sheetModel', $lossSheet)) {
                    $skippedLossSheets++;
                    $this->logger->info('Loss Sheet Skipped because it doesnt has Sheet Model :', array("id" => $lossSheet['id'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }

                $sheetModel = $this->em->getRepository(SheetModel::class)->findOneBy(
                    array(
                        "importId" => $lossSheet['sheetModel']['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );
                if (!$sheetModel) {
                    $skippedLossSheets++;
                    $this->logger->info('Loss Sheet Skipped because Sheet Model doesn\'t exist: ', array("id" => $lossSheet['id'], "sheetModelId" => $lossSheet['sheetModel']['id'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }

                unset($userName);
                if(empty($lossSheet['employee']['wyndId'])){
                    $userName=$lossSheet['employee']['username'];
                }else{
                    $userName=$this->restaurantCode."_".$lossSheet['employee']['username'];
                }
                $employee = $this->em->getRepository(Employee::class)->findOneBy(
                    array(
                        "username" => $userName
                    )
                );
                if (!$employee) {
                    $skippedLossSheets++;
                    $this->logger->info('Loss Sheet Skipped because Employee doesn\'t exist: ', array("id" => $lossSheet['id'], "UserName" => $lossSheet['employee']['username'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }

                $createdAt = new \DateTime($lossSheet['createdAt']['date']);

                $lossSheetEntity = $this->em->getRepository(LossSheet::class)->findOneBy(
                    array(
                        "importId" => $lossSheet['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );

                if (!$lossSheetEntity) {
                    $lossSheetEntity = new LossSheet();
                } else {
                    $isUpdate = true;
                }

                $lossSheetEntity
                    ->setCreatedAt($createdAt)
                    ->setEntryDate(new \DateTime($lossSheet['entryDate']['date']))
                    ->setType($lossSheet['type'])
                    ->setStatus($lossSheet['status'])
                    ->setSheetModelLabel($lossSheet['sheetModelLabel'])
                    ->setOriginRestaurant($this->restaurant);

                $lossSheetEntity->setModel($sheetModel);
                $lossSheetEntity->setEmployee($employee);
                $lossSheetEntity->setImportId($lossSheet['id'] . "_" . $this->restaurantCode);

                foreach ($lossSheet['lossLines'] as $line) {
                    $lossLine = $this->em->getRepository(LossLine::class)->findOneBy(array('importId' => $line['id'] . "_" . $this->restaurantCode));
                    if (!$lossLine) {
                        $lossLine = new LossLine();
                        $lossSheetEntity->addLossLine($lossLine);
                    }

                    $product = $this->em->getRepository(Product::class)->findOneBy(array('globalProductID' => $line['productGlobalId'], "originRestaurant" => $this->restaurant));
                    if ($product) {
                        $lossLine->setProduct($product);
                    } else {
                        $this->logger->info('Product not found for  lossSheet : ', array("lossSheetId" => $lossSheet['id'], "lossLineId" => $line['id'], "productGlobalId" => $line['productGlobalId'], "Restaurant" => $this->restaurant->getName()));
                        $lossSheetEntity->removeLossLine($lossLine);
                        continue;
                    }
                    if ($line['productPurchasedHistoric']) {
                        $productPurchasedHistoric = $this->em->getRepository(ProductPurchasedHistoric::class)->findOneBy(
                            array(
                                'externalId' => $line['productPurchasedHistoric']['external_id'],
                                'name' => $line['productPurchasedHistoric']['name'],
                                'originRestaurant' => $this->restaurant
                            )
                        );
                        if ($productPurchasedHistoric) {
                            $lossLine->setProductPurchasedHistoric($productPurchasedHistoric);
                        }
                    }
                    if ($line['soldingCanal']) {
                        $soldingCanal = $this->em->getRepository(SoldingCanal::class)->findOneBy(array('label' => $line['soldingCanal']));
                        if ($soldingCanal) {
                            $lossLine->setSoldingCanal($soldingCanal);
                        }
                    }
                    if ($line['recipe']) {
                        $recipe=$this->em
                            ->getRepository(Recipe::class)
                            ->createQueryBuilder('r')
                            ->join('r.productSold','p')
                            ->where('r.globalId = :globalId')
                            ->andWhere("p.originRestaurant = :restaurant")
                            ->setParameter("globalId",$line['recipe'])
                            ->setParameter("restaurant",$this->restaurant)
                            ->getQuery()->getOneOrNullResult();
                       //$recipe = $this->em->getRepository(Recipe::class)->findOneBy(array('globalId' => $line['recipe']));
                        if ($recipe) {
                            $lossLine->setRecipe($recipe);
                        }
                    }
                    if ($line['recipeHistoricGlobalId']) {
                        $recipeHistoric = $this->em->getRepository(RecipeHistoric::class)->findOneBy(array('globalId' => $line['recipeHistoricGlobalId']));
                        if ($recipeHistoric) {
                            $lossLine->setRecipeHistoric($recipeHistoric);
                        }
                    }

                    $lossLine
                        ->setFirstEntry(floatval($line['firstEntry']))
                        ->setSecondEntry(floatval($line['secondEntry']))
                        ->setThirdEntry(floatval($line['thirdEntry']))
                        ->setTotalLoss(floatval($line['totalLoss']))
                        ->setTotalRevenuePrice(floatval($line['totalRevenuePrice']));
                    $lossLine->setImportId($line['id'] . "_" . $this->restaurantCode);
                    $lossLine->setLossSheet($lossSheetEntity);
                    $this->em->persist($lossLine);

                }

                $this->em->persist($lossSheetEntity);
                $isUpdate ? $updatedLossSheets++ : $addedLossSheets++;
                $this->flush($batchCounter);
            }

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> " . $addedLossSheets . " loss sheets were added successfully for [" . $this->restaurant->getName() . "] restaurant.");
            $output->writeln("--> " . $updatedLossSheets . " loss sheets were updated successfully for [" . $this->restaurant->getName() . "] restaurant.");
            $output->writeln("--> " . $skippedLossSheets . " loss sheets were skipped because of missing data or they already exist in [" . $this->restaurant->getName() . "] restaurant.");

            $this->em->flush();
            $this->em->clear();


        } catch (\Exception $e) {
            $output->writeln("");
            $output->writeln("Command failed ! ");
            $output->writeln($e->getMessage());
            return;
        }

        $progress->finish();
        $output->writeln("\n====> Restaurant [" . $this->restaurant->getName() . "] stock data imported successfully.");

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
