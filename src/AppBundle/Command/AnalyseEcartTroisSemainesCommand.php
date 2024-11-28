<?php


namespace AppBundle\Command;


use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Report\Entity\MargeFoodCostLine;
use AppBundle\Report\Entity\ThreeWeekReportLine;
use AppBundle\Report\Service\ReportStockService;
use AppBundle\ToolBox\Utils\Utilities;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnalyseEcartTroisSemainesCommand  extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var ReportStockService $reportSockService
     */
    private   $reportSockService;

    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName("saas:report:stock:ecart")
            ->setDescription('Auto generate pc report for all restaurants')
            ->addArgument('date', InputArgument::REQUIRED)
//            ->addArgument('restaurantId', InputArgument::OPTIONAL)
        ;

    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->reportSockService = $this->getContainer()->get('report.stock.service');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $restaurant = null;
        if ($input->hasArgument('restaurantId') && !empty($input->getArgument('restaurantId'))) {
            $restaurantId = $input->getArgument('restaurantId');
            $restaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);
            if ($restaurant == null) {
                $this->logger->addDebug('restaurant not found with id '.$restaurantId,['saas:report:stock:ecart']);
                return;
            }
        }


        $date = $input->getArgument('date');
        // Calculer les dates de début et de fin pour les trois semaines
        $startOfWeek1 = date('Y-m-d', strtotime('last monday', strtotime($date)));
        $endOfWeek1 = date('Y-m-d', strtotime('next sunday', strtotime($startOfWeek1)));

        $startOfWeek2 = date('Y-m-d', strtotime('-1 week', strtotime($startOfWeek1)));
        $endOfWeek2 = date('Y-m-d', strtotime('next sunday', strtotime($startOfWeek2)));

        $startOfWeek3 = date('Y-m-d', strtotime('-1 week', strtotime($startOfWeek2)));
        $endOfWeek3 = date('Y-m-d', strtotime('next sunday', strtotime($startOfWeek3)));

        // Appeler la méthode getDataForWeek pour chacune des trois semaines
        $myService = $this->getContainer()->get('report.stock.service');
        // Récupérer tous les restaurants
        $restaurants = $this->em->getRepository(Restaurant::class)->getOpenedRestaurants();

        // Pour chaque restaurant, appeler la méthode calculate
        foreach ($restaurants as $restaurant) {
            $myService->calculate($startOfWeek3, $endOfWeek3, $restaurant, 3, '_minus_3');
            $myService->calculate($startOfWeek2, $endOfWeek2, $restaurant, 2, '_minus_2');
            $myService->calculate($startOfWeek1, $endOfWeek1, $restaurant, 1, '_minus_1');
        }

    }



}