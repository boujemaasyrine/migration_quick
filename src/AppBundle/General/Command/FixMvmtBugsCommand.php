<?php

namespace AppBundle\General\Command;

use AppBundle\General\Service\FixMvmtBugsService;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\ProductPurchasedMvmt;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Transfer;
use AppBundle\Merchandise\Service\ProductPurchasedMvmtService;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixMvmtBugsCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var FixMvmtBugsService
     */
    private $fixMvmtBugService;

    /**
     * @var ProductPurchasedMvmtService
     */
    private $productPurchasedMvmtService;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('quick:fix:mvmt:bugs')
            ->setDefinition([])
            ->addArgument('restaurantId',InputArgument::REQUIRED)
            ->addArgument('startDate', InputArgument::REQUIRED)
            ->addArgument('endDate', InputArgument::REQUIRED)
            ->setDescription('Fix mvmt bugs');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->fixMvmtBugService = $this->getContainer()->get('fix.mvmt.bugs.service');
        $this->productPurchasedMvmtService = $this->getContainer()->get('product_purchased_mvmt.service');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $restaurantId = $input->getArgument('restaurantId');
        $currentRestaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);
        if ($currentRestaurant == null) {
            $output->writeln('Restaurant not found with id: '.$restaurantId, ['quick:wynd:rest:import']);
            return;
        }
        echo "Starting process ... \n";
        $intervalStartDate = $input->getArgument('startDate');
        $intervalEndDate = $input->getArgument('endDate');
        $tmpDate = $intervalStartDate;
        while (strtotime($tmpDate) <= strtotime($intervalEndDate)) {
            echo "Processing date:".$tmpDate."\n";
            $startingDate = new \DateTime($tmpDate);
            $startingDate->setTime(0, 0, 0);
            $endingDate = new \DateTime($tmpDate);
            $endingDate->setTime(23, 59, 59);
            $date = new \DateTime($tmpDate);
 
            echo " ==> Ticket process <== \n";
            echo "Step 1/3: Clearing mvmt \n";
            $this->fixMvmtBugService->clearMvmt($startingDate, $endingDate, 'sold',$currentRestaurant);
            echo "==> mvmt cleared ==> \n";
            echo "Step 2/3: Creating new mvmts \n";
             $ticketlines = $this->em->getRepository('Financial:TicketLine')->createQueryBuilder('tl')	
                ->where('tl.date = :date')	
                ->setParameter(':date', $date)	
                ->andWhere('tl.originRestaurantId= :restaurant')	
                ->setParameter('restaurant',$currentRestaurant)	
                ->getQuery()	
                ->getResult();	
            $ticketsCount = count($ticketlines);	
            $done = 0;	
            $progress = new ProgressBar($output, $ticketsCount / 50);	
            foreach ($ticketlines as $line) {	
                $done++;	
                $this->productPurchasedMvmtService->createMvmtEntryForTicketLine($line,$currentRestaurant);
                if ($done == 50) {
                    $progress->advance();
                    $this->em->flush();
                    $done = 0;
                }
            }
            $progress->advance();
            $this->em->flush();


            echo "\n==> new mvmt created ==> \n";
            echo "Step 3/3: Updating Revenue prices for ticket lines \n";
            $command = $this->getApplication()->find('quick:init:prices:revenues:ticketLines');

            $arguments = array(
                'command' => 'quick:init:prices:revenues:ticketLines',
                'restaurantId'=>$currentRestaurant->getId(),
                'startDate' => $tmpDate,
                'endDate' => $tmpDate,
            );

            $input = new ArrayInput($arguments);
            $command->run($input, $output);


     /* 

            echo " ==> Returns process <== \n";
            echo "Step 1/3: Clearing mvmt \n";
            $this->fixMvmtBugService->clearMvmt($startingDate, $endingDate, 'returns',$currentRestaurant);
            echo "==> mvmt cleared ==> \n";
            echo "Step 2/3: Creating new mvmts \n";

            $returns = $this->em->getRepository('Merchandise:Returns')->createQueryBuilder('r')
                ->where('r.date = :date')
                ->setParameter('date', $tmpDate)
                ->andWhere('r.originRestaurant= :restaurant')
                ->setParameter('restaurant',$currentRestaurant)
                ->getQuery()
                ->getResult();
            foreach ($returns as $return) {
                $this->productPurchasedMvmtService->createMvmtEntryForReturn($return,$currentRestaurant);
            }
            $this->em->flush();
            echo "Step 3/3: Updating returns valorisations \n";
            foreach ($returns as $return) {
                $val = 0;
                foreach ($return->getLines() as $l) {
                    $val += $l->getValorization();
                }
                $return->setValorization($val);
            }
            $this->em->flush();


            echo " ==> Delivery process <== \n";
            echo "Step 1/3: Clearing mvmt \n";
            $this->fixMvmtBugService->clearMvmt($startingDate, $endingDate, 'delivery',$currentRestaurant);
            echo "==> mvmt cleared ==> \n";
            echo "Step 2/3: Creating new mvmts \n";
            $deliveries = $this->em->getRepository('Merchandise:Delivery')->createQueryBuilder('d')
                ->where('d.date >= :startDate')
                ->andWhere('d.date <= :endDate')
                ->setParameter(':startDate', $startingDate)
                ->setParameter(':endDate', $endingDate)
                ->andWhere('d.originRestaurant= :restaurant')
                ->setParameter('restaurant',$currentRestaurant)
                ->getQuery()
                ->getResult();
            foreach ($deliveries as $delivery) {
                $this->productPurchasedMvmtService->createMvmtEntryForDelivery($delivery,$currentRestaurant);
            }
            $this->em->flush();
            echo "Step 3/3: Updating deliveries valorizations \n";

            foreach ($deliveries as $delivery) {
                $deliveryValorization = 0;
                foreach ($delivery->getLines() as $line) {
                    if ($line->getProduct()->getPrimaryItem() != null) {
                        $lineValorisation = $line->getQty() * $line->getProduct()->getPrimaryItem()->getBuyingCost();
                    } else {
                        $lineValorisation = $line->getQty() * $line->getProduct()->getBuyingCost();
                    }

                    $line->setValorization($lineValorisation);
                    $deliveryValorization += $lineValorisation;
                }
                $delivery->setValorization($deliveryValorization);
            }
            $this->em->flush();


            echo " ==> Transfer process <== \n";
            echo "Step 1/6 : Clearing mvmt [Transfer IN] \n";
            $this->fixMvmtBugService->clearMvmt($startingDate, $endingDate, ProductPurchasedMvmt::TRANSFER_IN_TYPE,$currentRestaurant);
            echo "==> mvmt cleared ==> \n";
            echo "Step 2/6: Creating new mvmts [Transfer IN] \n";
            $transfers = $this->em->getRepository('Merchandise:Transfer')->createQueryBuilder('t')
                ->where('t.dateTransfer = :date')
                ->andWhere('t.type = :transfer_in')
                ->setParameter('date', $tmpDate)
                ->setParameter('transfer_in', Transfer::TRANSFER_IN)
                ->andWhere('t.originRestaurant= :restaurant')
                ->setParameter('restaurant',$currentRestaurant)
                ->getQuery()
                ->getResult();
            foreach ($transfers as $transfer) {
                $this->productPurchasedMvmtService->createMvmtEntryForTransfer($transfer,$currentRestaurant);
            }
            $this->em->flush();
            echo "Step 3/6: Updating Transfer valorizations [Transfer IN] \n";
            foreach ($transfers as $transfer) {
                $val = 0;
                foreach ($transfer->getLines() as $t) {
                    $val += $t->getValorization();
                }
                $transfer->setValorization($val);
            }
            $this->em->flush();

            echo "Step 4/6 : Clearing mvmt [Transfer OUT] \n";
            $this->fixMvmtBugService->clearMvmt($startingDate, $endingDate, ProductPurchasedMvmt::TRANSFER_OUT_TYPE,$currentRestaurant);
            echo "==> mvmt cleared ==> \n";
            echo "Step 5/6: Creating new mvmts [Transfer OUT] \n";
            $transfers = $this->em->getRepository('Merchandise:Transfer')->createQueryBuilder('t')
                ->where('t.dateTransfer = :date')
                ->andWhere('t.type = :transfer_in')
                ->setParameter('date', $date)
                ->setParameter('transfer_in', Transfer::TRANSFER_OUT)
                ->andWhere('t.originRestaurant= :restaurant')
                ->setParameter('restaurant',$currentRestaurant)
                ->getQuery()
                ->getResult();
            foreach ($transfers as $transfer) {
                $this->productPurchasedMvmtService->createMvmtEntryForTransfer($transfer,$currentRestaurant);
            }
            $this->em->flush();
            echo "Step 6/6: Updating Transfer valorizations [Transfer OUT] \n";
            foreach ($transfers as $transfer) {
                $val = 0;
                foreach ($transfer->getLines() as $t) {
                    $val += $t->getValorization();
                }
                $transfer->setValorization($val);
            }
            $this->em->flush();


            echo " ==> LOSS process <== \n";
            echo "Step 1/6 : Clearing mvmt [Sold Loss] \n";
            $this->fixMvmtBugService->clearMvmt($startingDate, $endingDate, ProductPurchasedMvmt::SOLD_LOSS_TYPE,$currentRestaurant);
            echo "==> mvmt cleared ==> \n";
            echo "Step 2/6: Creating new mvmts [Sold Loss] \n";
            $lossSheets = $this->em->getRepository('Merchandise:LossSheet')->createQueryBuilder('ls')
                ->where('ls.entryDate >= :dateStart')
                ->andWhere('ls.entryDate <= :dateEnd')
                ->andWhere('ls.type = :final')
                ->setParameter('dateStart', $startingDate)
                ->setParameter('dateEnd', $endingDate)
                ->setParameter(':final', LossSheet::FINALPRODUCT)
                ->andWhere('ls.originRestaurant = :restaurant')
                ->setParameter('restaurant',$currentRestaurant)
                ->getQuery()
                ->getResult();
            foreach ($lossSheets as $lossSheet) {
                $this->productPurchasedMvmtService->createMvmtEntryForLossSheet($lossSheet,$currentRestaurant);
            }
            $this->em->flush();
            echo "Step 3/6: Updating Loss Lines valorizations [Sold Loss] \n";
            foreach ($lossSheets as $lossSheet) {
                foreach ($lossSheet->getLossLines() as $ll) {
                    $ll->calculateLossTotalRevenue();
                }
                $this->em->flush();
            }

            echo "Step 4/6 : Clearing mvmt [Inventory Loss] \n";
            $this->fixMvmtBugService->clearMvmt($startingDate, $endingDate, ProductPurchasedMvmt::PURCHASED_LOSS_TYPE,$currentRestaurant);
            echo "==> mvmt cleared ==> \n";
            echo "Step 5/6: Creating new mvmts [Inventory Loss] \n";
            $lossSheets = $this->em->getRepository('Merchandise:LossSheet')->createQueryBuilder('ls')
                ->where('ls.entryDate >= :dateStart')
                ->andWhere('ls.entryDate <= :dateEnd')
                ->andWhere('ls.type = :article')
                ->setParameter('dateStart', $startingDate)
                ->setParameter('dateEnd', $endingDate)
                ->setParameter('article', LossSheet::ARTICLE)
                ->andWhere('ls.originRestaurant= :restaurant')
                ->setParameter('restaurant',$currentRestaurant)
                ->getQuery()
                ->getResult();
            foreach ($lossSheets as $lossSheet) {
                $this->productPurchasedMvmtService->createMvmtEntryForLossSheet($lossSheet,$currentRestaurant);
            }
            $this->em->flush();
            echo "Step 6/6: Updating Loss Lines valorizations [Inventory Loss] \n";
            foreach ($lossSheets as $lossSheet) {
                foreach ($lossSheet->getLossLines() as $ll) {
                    $ll->calculateLossTotalRevenue();
                }
                $this->em->flush();
            }
           */
      
            echo "End processing date:".$tmpDate."\n";
            $tmpDate = date("Y-m-d", strtotime("+1 day", strtotime($tmpDate)));

        }//while
        echo "Ending process ... \n";
    }
}
