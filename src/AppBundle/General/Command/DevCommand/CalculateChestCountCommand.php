<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 11:56
 */

namespace AppBundle\General\Command\DevCommand;

use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Financial\Service\AdministrativeClosingService;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalculateChestCountCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var AdministrativeClosingService
     */
    private $adminClosingService;
    private $dataDir;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick_dev:count:chest')->setDefinition(
            []
        )
            ->addArgument('restaurantId',InputArgument::REQUIRED)
            ->addArgument('startDate', InputArgument::OPTIONAL)
            ->addArgument('endDate', InputArgument::OPTIONAL)
            ->setDescription('Calculate Chest Count Real/Theorical/Gap.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/";
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('logger');
        $this->adminClosingService = $this->getContainer()->get('administrative.closing.service');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $supportedFormat = "Y-m-d";

        $restaurantId=$input->getArgument('restaurantId');
        $restaurant=$this->em->getRepository(Restaurant::class)->find($restaurantId);

        if(!$restaurant){
            echo 'restaurant not found with id'. $restaurantId;
            return;
        }

        if ($input->hasArgument('startDate') && $input->hasArgument('endDate')) {
            $startDate = $input->getArgument('startDate');
            $endDate = $input->getArgument('endDate');
        } else {
            $startDate = null;
            $endDate = null;
        }

        if (!is_null($startDate) && !is_null($endDate) && Utilities::isValidDateFormat(
            $startDate,
            $supportedFormat
        ) && Utilities::isValidDateFormat($endDate, $supportedFormat)) {
            $startDate = date_create_from_format($supportedFormat, $startDate);
            $endDate = date_create_from_format($supportedFormat, $endDate);
        } else {
                $startDate = $this->adminClosingService->getLastWorkingEndDate($restaurant);
                $endDate = $this->adminClosingService->getLastWorkingEndDate($restaurant);

        }
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        // 2) Update Cashbox Counts calculated values
        $cbcs = $this->em->getRepository("Financial:ChestCount")->createQueryBuilder("cc")
            ->select('COUNT(cc.id)')
            ->where('cc.date between :start and :end')
            ->andWhere('cc.originRestaurant= :restaurant')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('restaurant',$restaurant)
            ->getQuery()->getSingleScalarResult();

        echo "1) Update $cbcs Chest Counts calculated values\n";
        $this->logger->info("1) Update $cbcs Chest Counts calculated values", ['CalculateChestCountCommand']);

        $y = $cbcs;
        $x = 0;
        while ($x < $y / 10) {
            $cbcs = $this->em->getRepository("Financial:ChestCount")->createQueryBuilder("cc")
                ->where('cc.date between :start and :end')
                ->andWhere('cc.originRestaurant= :restaurant')
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate)
                ->setParameter('restaurant',$restaurant)
                ->orderBy('cc.id')
                ->setMaxResults(10)->setFirstResult($x * 10)
                ->getQuery()->getResult();
            $progress = new ProgressBar($output, count($cbcs));
            echo "\nStep ".($x + 1);

            foreach ($cbcs as $cbc) {

                file_put_contents($this->dataDir."/CalculateChest.txt",$cbc->getId()."\n",FILE_APPEND);



                /**
                 * @var ChestCount $cbc
                 */
                $real = $cbc->calculateRealTotal($restaurant);
                $theo = $cbc->calculateTheoricalTotal($restaurant);
                file_put_contents($this->dataDir."/CalculateChest.txt","real total: ".$real."\n",FILE_APPEND);
                file_put_contents($this->dataDir."/CalculateChest.txt","theorical total: ".$theo."\n",FILE_APPEND);
                $cbc->setRealTotal($real)
                    ->setTheoricalTotal($theo)
                    ->setGap($real - $theo);
                $cbc->getTirelire()->calculateGap($restaurant);
                $cbc->getSmallChest()->calculateGap($restaurant);
                $cbc->getExchangeFund()->calculateGap($restaurant);
                $progress->advance();
            }
            $this->em->flush();
            $this->em->clear();
            $x++;
        }

        echo "Finished\n";
        $this->logger->info("Finished", ['CalculateChestCountCommand']);
    }
}
