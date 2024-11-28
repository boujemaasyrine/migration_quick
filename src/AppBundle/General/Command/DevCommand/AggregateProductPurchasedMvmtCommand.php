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
use AppBundle\Merchandise\Entity\ProductPurchasedMvmt;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AggregateProductPurchasedMvmtCommand extends ContainerAwareCommand
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

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick_dev:aggregate:mvmt')->setDefinition(
            []
        )
            ->addArgument('startDate', InputArgument::OPTIONAL)
            ->addArgument('endDate', InputArgument::OPTIONAL)
            ->setDescription('Calculate Cashbox Count Real/Theorical/Gap.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('logger');
        $this->adminClosingService = $this->getContainer()->get(
            'administrative.closing.service'
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $supportedFormat = "Y-m-d";
        if ($input->hasArgument('startDate')
            && $input->hasArgument(
                'endDate'
            )
        ) {
            $startDate = $input->getArgument('startDate');
            $endDate = $input->getArgument('endDate');
        } else {
            $startDate = null;
            $endDate = null;
        }

        if (!is_null($startDate) && !is_null($endDate)
            && Utilities::isValidDateFormat(
                $startDate,
                $supportedFormat
            )
            && Utilities::isValidDateFormat($endDate, $supportedFormat)
        ) {
            $startDate = date_create_from_format($supportedFormat, $startDate);
            $endDate = date_create_from_format($supportedFormat, $endDate);
        } else {
            $startDate = $this->adminClosingService->getLastClosingDate();
            $endDate = $this->adminClosingService->getLastClosingDate();
        }

        $restaurants = $this->em->getRepository(Restaurant::class)->findAll();

        if (isset($restaurants) && !empty($restaurants)) {
            foreach ($restaurants as $restaurant) {
                for ($i = 0; $i <= $endDate->diff($startDate)->days; $i++) {
                    $date = Utilities::getDateFromDate($startDate, $i);

                    echo "\nStart date: ".date_format($date, 'Y/m/d')."\n";

                    $this->em->getConnection()->getConfiguration()
                        ->setSQLLogger(
                            null
                        );

                    $products = $this->em->getRepository(
                        "Merchandise:ProductPurchasedMvmt"
                    )->createQueryBuilder("mvmt")
                        ->select('p.id')
                        ->join('mvmt.product', 'p')
                        ->where(
                            'DATE(mvmt.dateTime) = :date and mvmt.type = :type'
                        )
                        ->andWhere('mvmt.originRestaurant=:restaurant')
                        ->setParameter('date', $date)
                        ->setParameter('type', 'sold')
                        ->setParameter('restaurant',$restaurant)
                        ->groupBy('p.id')
                        ->having('count(mvmt.id) > 1')
                        ->getQuery()->getArrayResult();
                    if (count($products) > 0) {
                        $progress = new ProgressBar($output, count($products));
                        $progress->start();
                        foreach ($products as $product) {
                            $productId = $product['id'];
                            /**
                             *
                             *
                             * @var ProductPurchasedMvmt[] $mvmts
                             */
                            $mvmts = $this->em->getRepository(
                                "Merchandise:ProductPurchasedMvmt"
                            )->createQueryBuilder("mvmt")
                                ->join('mvmt.product', 'p')
                                ->where(
                                    'p.id = :id and DATE(mvmt.dateTime) = :date and mvmt.type = :type'
                                )
                                ->andWhere('mvmt.deleted != true')
                                ->andWhere('mvmt.originRestaurant=:restaurant')
                                ->setParameter('id', $productId)
                                ->setParameter('date', $date)
                                ->setParameter('type', 'sold')
                                ->setParameter('restaurant',$restaurant)
                                ->getQuery()->getResult();
                            if (count($mvmts) > 0) {
                                /**
                                 *
                                 *
                                 * @var ProductPurchasedMvmt $newMvmt
                                 */
                                $newMvmt = clone $mvmts[0];
                                $newMvmt->setDateTime($date)
                                    ->setOriginRestaurant($restaurant)
                                    ->setVariation(0)
                                    ->setSynchronized(false);
                                $ids = array();
                                foreach ($mvmts as $mvmt) {
                                    $newMvmt->setVariation(
                                        $mvmt->getVariation()
                                        + $newMvmt->getVariation()
                                    );
                                    $ids[] = $mvmt->getId();
                                    $this->em->remove($mvmt);
                                }
                                $this->getContainer()->get(
                                    'sync.remove_movement.service'
                                )->removeMovement($ids);
                                $this->em->persist($newMvmt);
                                $this->em->flush();
                            }
                            $this->em->clear();
                            $progress->advance();
                        }
                    }
                }
            }
        }
        echo "\nFinished\n";
        $this->logger->info(
            "Finished",
            ['AggregateProductPurchasedMvmtCommand']
        );
    }
}
