<?php

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\CoefBase;
use AppBundle\Merchandise\Entity\Coefficient;
use AppBundle\Merchandise\Entity\Delivery;
use AppBundle\Merchandise\Entity\DeliveryLine;
use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\OrderLine;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\ReturnLine;
use AppBundle\Merchandise\Entity\Returns;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Entity\Transfer;
use AppBundle\Merchandise\Entity\TransferLine;
use AppBundle\Staff\Entity\Employee;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ImportBoPurchaseDataCommand extends ContainerAwareCommand
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
            ->setName('saas:import:bo:purchase:data')
            ->addArgument('restaurantCode', InputArgument::OPTIONAL)
            ->setDescription('Import restaurant purchase data form json file exported by a BO instance.');
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
                'Please enter restaurant code (found at the end of json file name : purchaseData_restaurant_xxxx.json ) :'
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
        $filename = "purchaseData_restaurant_".$this->restaurantCode.".json";
        $filePath = $this->dataDir.$filename;

        if (!file_exists($filePath)) {
            $output->writeln("No import file with the '".$this->restaurantCode."' restaurant code found !");
            return;
        }
        try {
            $fileData = file_get_contents($filePath);
            $purchaseData = json_decode($fileData, true);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return;
        }


        /************ Start the import process *****************/


        try {
            $this->restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($purchaseData['restaurant_code']);

            if (!$this->restaurant) {
                $output->writeln("No restaurant with the '".$purchaseData['restaurant_code']."' exist! Command failed... ");
                $output->writeln("->Please add this restaurant first.");
                return;
            }
            $output->writeln("Restaurant ".$this->restaurant->getName()." purchase data import started...");
            $batchCounter = 0;


            //import Deliveries
            $output->writeln("Importing Deliveries...");
            $deliveries=$purchaseData['deliveries'];

            $progress = new ProgressBar($output, count($deliveries));
            $progress->start();
            $addedDeliveries=0;
            $updatedDeliveries=0;
            $skippedDeliveries=0;
            foreach ($deliveries as $delivery) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($delivery) || !array_key_exists('id', $delivery)) {
                    continue;
                }

                $deliveryEntity = $this->em->getRepository(Delivery::class)->findOneBy(
                    array(
                        "importId" => $delivery['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );
                if (!$deliveryEntity) {
                    $deliveryEntity = new Delivery();
                } else {
                    $isUpdate = true;
                }

                if(empty($delivery['employee']['wyndId'])){
                    $userName=$delivery['employee']['username'];
                }else{
                    $userName=$this->restaurantCode."_".$delivery['employee']['username'];
                }
                $employee = $this->em->getRepository(Employee::class)->findOneBy(
                    array(
                        "username" => $userName
                    )
                );
                if ($employee) {
                    $deliveryEntity->setEmployee($employee);
                } else {
                    $skippedDeliveries++;
                    $this->logger->info('Delivery Skipped because employee doesn\'t exist : ', array("deliveryId" => $delivery['id'], "username" => $delivery['employee']['username'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }



                $deliveryEntity->setOriginRestaurant($this->restaurant);
                $deliveryEntity->setImportId($delivery['id'] . "_" . $this->restaurantCode);

                $createdAt = new \DateTime($delivery['createdAt']['date']);
                $date = new \DateTime($delivery['date']['date']);

                $deliveryEntity
                    ->setDeliveryBordereau($delivery['deliveryBordereau'])
                    ->setValorization($delivery['valorization'])
                    ->setSynchronized(boolval($delivery['synchronized']))
                    ->setCreatedAt($createdAt)
                    ->setDate($date);

                foreach ($delivery['lines'] as $line) {
                    $deliveryLine = $this->em->getRepository(DeliveryLine::class)->findOneBy(array('importId' => $line['id'] . "_" . $this->restaurantCode));
                    if(!$deliveryLine){
                        $deliveryLine = new DeliveryLine();
                        $deliveryEntity->addLine($deliveryLine);
                    }

                    $product = $this->em->getRepository(ProductPurchased::class)->findOneBy(array('globalProductID' => $line['productGlobalId'], "originRestaurant" => $this->restaurant));
                    if ($product) {
                        $deliveryLine->setProduct($product);
                    } else {
                        $this->logger->info('Product not found for delivery line :  ', array("deliveryId" => $delivery['id'], "lineId" => $line['id'], "productGlobalId" => $line['productGlobalId'], "Restaurant" => $this->restaurant->getName()));
                        //$deliveryEntity->removeLine($deliveryLine);
                        //continue;
                    }
                    $createdAt = new \DateTime($line['createdAt']['date']);
                    $deliveryLine
                        ->setValorization($line['valorization'])
                        ->setQty($line['qty'])
                        ->setCreatedAt($createdAt);
                    $deliveryLine->setImportId($line['id'] . "_" . $this->restaurantCode);

                    $this->em->persist($deliveryLine);

                }
                $this->em->persist($deliveryEntity);
                $isUpdate ? $updatedDeliveries++ : $addedDeliveries++;
                $this->flush($batchCounter);

            }


            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedDeliveries." deliveries were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedDeliveries." deliveries were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedDeliveries." deliveries were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            $this->em->flush();


            /////////////////////////////////////////////
            //import orders
            $output->writeln("Importing Orders...");
            $orders=$purchaseData['orders'];

            $progress = new ProgressBar($output, count($orders));
            $progress->start();
            $addedOrders=0;
            $updatedOrders=0;
            $skippedOrders=0;
            foreach ($orders as $order) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($order) || !array_key_exists('id', $order)) {
                    continue;
                }

                unset($userName);
                if(empty($order['employee']['wyndId'])){
                    $userName=$order['employee']['username'];
                }else{
                    $userName=$this->restaurantCode."_".$order['employee']['username'];
                }
                $employee = $this->em->getRepository(Employee::class)->findOneBy(
                    array(
                        "username" => $userName
                    )
                );
                if (!$employee) {
                    $skippedOrders++;
                    $this->logger->info('Order Skipped because Employee doesn\'t exist: ', array("orderId" => $order['id'], "UserName" => $order['employee']['username'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }

                $orderEntity = $this->em->getRepository(Order::class)->findOneBy(
                    array(
                        "importId" => $order['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );

                if (!$orderEntity) {
                    $orderEntity = new Order();
                } else {
                    $isUpdate = true;
                }

                $createdAt = new \DateTime($order['createdAt']['date']);
                $dateOrder = new \DateTime($order['dateOrder']['date']);
                $dateDelivery = new \DateTime($order['dateDelivery']['date']);

                //$supplier = $this->em->getRepository(Supplier::class)->findOneByCode($order['supplier']['code']);
                $supplier =$this->em->getRepository(Supplier::class)->createQueryBuilder('s')
                    ->where("s.code = :code")
                    ->andWhere(":restaurant MEMBER OF s.restaurants ")
                    ->setParameter("code",$order['supplier']['code'])
                    ->setParameter("restaurant",$this->restaurant)
                    ->getQuery()->getOneOrNullResult();
                if ($supplier) {
                    $orderEntity->setSupplier($supplier);
                    $supplier->addOrder($orderEntity);
                    $this->em->persist($supplier);
                } else {
                    $skippedOrders++;
                    $this->logger->info('Order Skipped because Supplier doesn\'t exist: ', array("orderId" => $order['id'], "SupplierCode" => $order['supplier']['code'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }

                if (array_key_exists('deliveryId', $order)) {
                    $delivery = $this->em->getRepository(Delivery::class)->findOneBy(
                        array(
                            "importId" => $order['deliveryId'] . "_" . $this->restaurantCode,
                            "originRestaurant" => $this->restaurant
                        )
                    );
                    if ($delivery) {
                        $orderEntity->setDelivery($delivery);
                        $delivery->setOrder($orderEntity);
                        $this->em->persist($delivery);
                    }
                }

                $orderEntity
                    ->setCreatedAt($createdAt)
                    ->setDateDelivery($dateDelivery)
                    ->setDateOrder($dateOrder)
                    ->setNumOrder($order['numOrder'])
                    ->setStatus($order['status'])
                    ->setSynchronized(boolval($order['synchronized']));

                $orderEntity->setOriginRestaurant($this->restaurant);
                $orderEntity->setEmployee($employee);
                $orderEntity->setImportId($order['id'] . "_" . $this->restaurantCode);

                foreach ($order['lines'] as $line) {
                    $orderLine = $this->em->getRepository(OrderLine::class)->findOneBy(array('importId' => $line['id'] . "_" . $this->restaurantCode));
                    if(!$orderLine){
                        $orderLine = new OrderLine();
                        $orderEntity->addLine($orderLine);
                    }

                    $product = $this->em->getRepository(ProductPurchased::class)->findOneBy(array('globalProductID' => $line['productGlobalId'], "originRestaurant" => $this->restaurant));
                    if ($product) {
                        $orderLine->setProduct($product);
                    } else {
                        $this->logger->info('Product not found for order line : ', array("orderId" => $order['id'], "lineId" => $line['id'], "productGlobalId" => $line['productGlobalId'], "Restaurant" => $this->restaurant->getName()));
                        //$orderEntity->removeLine($orderLine);
                        //continue;
                    }
                    $createdAt = new \DateTime($line['createdAt']['date']);
                    $orderLine
                        ->setQty($line['qty'])
                        ->setCreatedAt($createdAt);
                    $orderLine->setImportId($line['id'] . "_" . $this->restaurantCode);
                    $this->em->persist($orderLine);

                }

                $this->em->persist($orderEntity);
                $isUpdate ? $updatedOrders++ : $addedOrders++;
                $this->flush($batchCounter);
            }


            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedOrders." orders were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedOrders." orders were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedOrders." orders were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            $this->em->flush();

            /////////////////////////////////////////////
            //import transfers
            $output->writeln("Importing transfers...");
            $transfers=$purchaseData['transfers'];

            $progress = new ProgressBar($output, count($transfers));
            $progress->start();
            $addedTransfers=0;
            $updatedTransfers=0;
            $skippedTransfers=0;
            foreach ($transfers as $transfer) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($transfer) || !array_key_exists('id', $transfer)) {
                    continue;
                }

                unset($userName);
                if(empty($transfer['employee']['wyndId'])){
                    $userName=$transfer['employee']['username'];
                }else{
                    $userName=$this->restaurantCode."_".$transfer['employee']['username'];
                }
                $employee = $this->em->getRepository(Employee::class)->findOneBy(
                    array(
                        "username" => $userName
                    )
                );
                if (!$employee) {
                    $skippedTransfers++;
                    $this->logger->info('Transfer Skipped because Employee doesn\'t exist: ', array("transferId" => $transfer['id'], "UserName" => $transfer['employee']['username'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }

                $transferEntity = $this->em->getRepository(Transfer::class)->findOneBy(
                    array(
                        "importId" => $transfer['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );

                if (!$transferEntity) {
                    $transferEntity = new Transfer();
                } else {
                    $isUpdate = true;
                }

                $createdAt = new \DateTime($transfer['createdAt']['date']);
                $dateTransfer = new \DateTime($transfer['dateTransfer']['date']);

                $resto = $this->em->getRepository(Restaurant::class)->findOneByCode($transfer['restaurant']['code']);
                if ($resto) {
                    $transferEntity->setRestaurant($resto);
                } else {
                    //$skippedTransfers++;
                    $this->logger->info('Transfer Restaurant doesn\'t exist: ', array("transferId" => $transfer['id'], "restaurantCode" => $transfer['restaurant']['code'], "Restaurant" => $this->restaurant->getName()));
                }

                $transferEntity
                    ->setCreatedAt($createdAt)
                    ->setDateTransfer($dateTransfer)
                    ->setType($transfer['type'])
                    ->setValorization($transfer['valorization'])
                    ->setNumTransfer($transfer['numTransfer'])
                    ->setMailSent(boolval($transfer['mailSent']));


                $transferEntity->setOriginRestaurant($this->restaurant);
                $transferEntity->setEmployee($employee);
                $transferEntity->setImportId($transfer['id'] . "_" . $this->restaurantCode);

                foreach ($transfer['lines'] as $line) {
                    $transferLine = $this->em->getRepository(TransferLine::class)->findOneBy(array('importId' => $line['id'] . "_" . $this->restaurantCode));
                    if(!$transferLine){
                        $transferLine = new TransferLine();
                        $transferEntity->addLine($transferLine);
                    }
                    $product = $this->em->getRepository(ProductPurchased::class)->findOneBy(array('globalProductID' => $line['productGlobalId'], "originRestaurant" => $this->restaurant));
                    if ($product) {
                        $transferLine->setProduct($product);
                    } else {
                        $this->logger->info('Product not found for transfer line : ', array("transferId" => $transfer['id'], "lineId" => $line['id'], "productGlobalId" => $line['productGlobalId'], "Restaurant" => $this->restaurant->getName()));
                        //$transferEntity->removeLine($transferLine);
                        //continue;
                    }
                    $createdAt = new \DateTime($line['createdAt']['date']);
                    $transferLine
                        ->setQty($line['qty'])
                        ->setQtyExp($line['qtyExp'])
                        ->setQtyUse($line['qtyUse'])
                        ->setCreatedAt($createdAt);
                    $transferLine->setImportId($line['id'] . "_" . $this->restaurantCode);
                    $this->em->persist($transferLine);
                }

                $this->em->persist($transferEntity);
                $isUpdate ? $updatedTransfers++ : $addedTransfers++;
                $this->flush($batchCounter);

            }

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedTransfers." transfers were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedTransfers." transfers were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedTransfers." transfers were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            $this->em->flush();

            /////////////////////////////////////////////
            //import returns
            $output->writeln("Importing returns...");
            $returns=$purchaseData['returns'];

            $progress = new ProgressBar($output, count($returns));
            $progress->start();
            $addedReturns=0;
            $updatedReturns=0;
            $skippedReturns=0;
            foreach ($returns as $return) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($return) || !array_key_exists('id', $return)) {
                    continue;
                }

                unset($userName);
                if(empty($return['employee']['wyndId'])){
                    $userName=$return['employee']['username'];
                }else{
                    $userName=$this->restaurantCode."_".$return['employee']['username'];
                }
                $employee = $this->em->getRepository(Employee::class)->findOneBy(
                    array(
                        "username" => $userName
                    )
                );
                if (!$employee) {
                    $skippedReturns++;
                    $this->logger->info('Transfer Skipped because Employee doesn\'t exist: ', array("returnId" => $return['id'], "UserName" => $return['employee']['username'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }

                $returnEntity = $this->em->getRepository(Returns::class)->findOneBy(
                    array(
                        "importId" => $return['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );

                if (!$returnEntity) {
                    $returnEntity = new Returns();
                } else {
                    $isUpdate = true;
                }

                $createdAt = new \DateTime($return['createdAt']['date']);
                $date = new \DateTime($return['date']['date']);

                $supplier = $this->em->getRepository(Supplier::class)->findOneByCode($return['supplier']['code']);
                if ($supplier) {
                    $returnEntity->setSupplier($supplier);
                } else {
                    $skippedReturns++;
                    $this->logger->info('Return Skipped because Supplier doesn\'t exist: ', array("returnId" => $return['id'], "supplierCode" => $return['supplier']['code'], "Restaurant" => $this->restaurant->getName()));
                }


                $returnEntity
                    ->setCreatedAt($createdAt)
                    ->setDate($date)
                    ->setValorization($return['valorization'])
                    ->setComment($return['comment']);


                $returnEntity->setOriginRestaurant($this->restaurant);
                $returnEntity->setEmployee($employee);
                $returnEntity->setImportId($return['id'] . "_" . $this->restaurantCode);

                foreach ($return['lines'] as $line) {
                    $returnLine = $this->em->getRepository(ReturnLine::class)->findOneBy(array('importId' => $line['id'] . "_" . $this->restaurantCode));
                    if(!$returnLine){
                        $returnLine = new ReturnLine();
                        $returnEntity->addLine($returnLine);
                    }
                    $product = $this->em->getRepository(ProductPurchased::class)->findOneBy(array('globalProductID' => $line['productGlobalId'], "originRestaurant" => $this->restaurant));
                    if ($product) {
                        $returnLine->setProduct($product);
                    } else {
                        $this->logger->info('Product not found for return line : ', array("returnId" => $return['id'], "lineId" => $line['id'], "productGlobalId" => $line['productGlobalId'], "Restaurant" => $this->restaurant->getName()));
                        //$returnEntity->removeLine($returnLine);
                        //continue;
                    }
                    $createdAt = new \DateTime($line['createdAt']['date']);

                    $returnLine
                        ->setQtyExp($line['qtyExp'])
                        ->setQtyUse($line['qtyUse'])
                        ->setQty($line['qty'])
                        ->setCreatedAt($createdAt);
                    $returnLine->setImportId($line['id'] . "_" . $this->restaurantCode);
                    $this->em->persist($returnLine);
                }

                $this->em->persist($returnEntity);
                $isUpdate ? $updatedReturns++ : $addedReturns++;
                $this->flush($batchCounter);

            }


            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedReturns." returns were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedReturns." returns were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedReturns." returns were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            $this->em->flush();


            /////////////////////////////////////////////
            //import coefBases
            $output->writeln("Coefficient Bases returns...");
            $coefBases=$purchaseData['coefBases'];

            $progress = new ProgressBar($output, count($coefBases));
            $progress->start();
            $addedCoefBases=0;
            $updatedCoefBases=0;
            $skippedCoefBases=0;
            foreach ($coefBases as $coefBase) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($coefBase) || !array_key_exists('id', $coefBase)) {
                    continue;
                }

                $coefBaseEntity = $this->em->getRepository(CoefBase::class)->findOneBy(
                    array(
                        "importId" => $coefBase['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );

                if (!$coefBaseEntity) {
                    $coefBaseEntity = new CoefBase();
                } else {
                    $isUpdate = true;
                }

                $createdAt = new \DateTime($coefBase['createdAt']['date']);
                $startDate = new \DateTime($coefBase['startDate']['date']);
                $endDate = new \DateTime($coefBase['endDate']['date']);

                $coefBaseEntity
                    ->setCreatedAt($createdAt)
                    ->setStartDate($startDate)
                    ->setEndDate($endDate)
                    ->setCa($coefBase['ca'])
                    ->setWeek($coefBase['week'])
                    ->setType($coefBase['type'])
                    ->setLocked(boolval($coefBase['locked']));

                $coefBaseEntity->setOriginRestaurant($this->restaurant);
                $coefBaseEntity->setImportId($coefBase['id'] . "_" . $this->restaurantCode);

                foreach ($coefBase['coefs'] as $coef) {
                    $coefEntity = $this->em->getRepository(Coefficient::class)->findOneBy(array('importId' => $coef['id'] . "_" . $this->restaurantCode));
                    if(!$coefEntity){
                        $coefEntity = new Coefficient();
                        $coefBaseEntity->addCoef($coefEntity);
                        $coefEntity->setBase($coefBaseEntity);
                    }
                    $product = $this->em->getRepository(Product::class)->findOneBy(array('globalProductID' => $coef['productGlobalId'], "originRestaurant" => $this->restaurant));
                    if ($product) {
                        $coefEntity->setProduct($product);
                    } else {
                        $this->logger->info('Product not found for Coefficient : ', array("CoefficientBaseId" => $coefBase['id'], "CoefficientId" => $coef['id'], "productGlobalId" => $coef['productGlobalId'], "Restaurant" => $this->restaurant->getName()));
                        //$coefBaseEntity->removeCoef($coefEntity);
                        //continue;
                    }

                    $coefEntity
                        ->setType($coef['type'])
                        ->setHebTheo($coef['hebTheo'])
                        ->setHebReal($coef['hebReal'])
                        ->setCoef($coef['coef'])
                        ->setFixed(boolval($coef['fixed']))
                        ->setRealStock($coef['realStock'])
                        ->setTheoStock($coef['theoStock'])
                        ->setStockFinalExist(boolval($coef['stockFinalExist']));
                    $coefEntity->setImportId($coef['id'] . "_" . $this->restaurantCode);
                    $this->em->persist($coefEntity);
                }

                $this->em->persist($coefBaseEntity);
                $isUpdate ? $updatedCoefBases++ : $addedCoefBases++;
                $this->flush($batchCounter);

            }
            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedCoefBases." CoefBases were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedCoefBases." CoefBases were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedCoefBases." CoefBases were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            $this->em->flush();
            $this->em->clear();

        }catch (\Exception $e){
            $output->writeln("");
            $output->writeln("Command failed !");
            $output->writeln($e->getMessage());
            return;
        }

        $progress->finish();
        $output->writeln("\n====> Restaurant [".$this->restaurant->getName()."] purchase data imported successfully.");

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
