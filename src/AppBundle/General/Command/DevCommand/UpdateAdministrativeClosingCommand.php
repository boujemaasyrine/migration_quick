<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 11:56
 */

namespace AppBundle\General\Command\DevCommand;

use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Report\Service\ReportCashBookService;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAdministrativeClosingCommand extends ContainerAwareCommand
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
     * @var ReportCashBookService
     */
    private $cashBookReport;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick_dev:administrative:closing:update')->setDefinition(
            []
        )
            ->addArgument('restaurantId',InputArgument::REQUIRED)
            ->addArgument('startDate', InputArgument::OPTIONAL)
            ->addArgument('endDate', InputArgument::OPTIONAL)
            ->setDescription('Update Administrative Closing Command');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('logger');
        $this->cashBookReport = $this->getContainer()->get('cash.book.report');
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

        if (!is_null($startDate) && !is_null($endDate) && Utilities::isValidDateFormat($startDate, $supportedFormat)
            && Utilities::isValidDateFormat($endDate, $supportedFormat)
        ) {
            $startDate = date_create_from_format($supportedFormat, $startDate);
            $endDate = date_create_from_format($supportedFormat, $endDate);
            /**
             * @var ChestCount $firstChestCount
             */
            $firstChestCount = $this->em->getRepository(
                'Financial:ChestCount'
            )->findFirstChestCountInClosureAdministrative($restaurant);
            if ($firstChestCount) {
                if (Utilities::compareDates($firstChestCount->getClosureDate(), $startDate) >= 0) {
                    echo "Date de la première est: ".$firstChestCount->getClosureDate()->format('d-m-Y')."\n";
                    $startDate = clone $firstChestCount->getClosureDate();
                    $startDate = Utilities::getDateFromDate($startDate, 0);
                    echo "premiere date ".$startDate->format('d-m-Y')."\n";
                }
            }
        } else {
            $startDate = new \DateTime('today');
            $endDate = new \DateTime('today');
        }
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        if (Utilities::compareDates($endDate, $startDate) >= 0) {
            for ($i = 0; $i <= $endDate->diff($startDate)->days; $i++) {
                $date = Utilities::getDateFromDate($startDate, $i);
                echo "Date: ".$date->format('d/m/Y')."\n";

                $adminClosing = $this->em->getRepository("Financial:AdministrativeClosing")
                    ->findOneBy(
                        array(
                            'date' => $date,
                            'originRestaurant'=>$restaurant
                        )
                    );
                if ($adminClosing) {
                    $cred = $this->cashBookReport->generateCashbookReport($date,$restaurant);
                    $adminClosing->setCreditAmount($cred);
                }

                $this->em->flush();
                $this->em->clear();
            }

            echo "Finished\n";
        } else {
            echo "Veuillez vérifier les dates saisies \n";
        }
    }
}
