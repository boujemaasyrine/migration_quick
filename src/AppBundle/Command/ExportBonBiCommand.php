<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 24/07/2018
 * Time: 11:47
 */

namespace AppBundle\Command;


use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportBonBiCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var Logger $logger
     */
    private $logger;

    private $expenseService;

    protected function configure()
    {

        parent::configure();

        $this->setName('saas:exportBonBi:excel')
            ->addArgument('startDate', InputArgument::OPTIONAL)
            ->addArgument('endDate', InputArgument::OPTIONAL)
            ->addArgument('restaurantId', InputArgument::OPTIONAL)
            ->addArgument('force', InputArgument::OPTIONAL)
            ->setDescription('A command that export Bon to BI on excel format');
    }


    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {
        parent::initialize($input, $output);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('monolog.logger.app_commands');
        $this->expenseService = $this->getContainer()->get('bi_api.expense.service');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentRestaurant = null;

        $startDate = $input->getArgument('startDate');
        $endDate = $input->getArgument('endDate');
        $supportedFormat = "Y-m-d";

        $force = false;

        if ($input->hasArgument('force') && !empty($input->getArgument('force'))) {

            $force = true;
        }

        if ($input->hasArgument('restaurantId') && !empty($input->getArgument('restaurantId'))) {

            $restaurantId = $input->getArgument('restaurantId');

            /**
             * @var Restaurant $currentRestaurant
             */
            $currentRestaurant = $this->em->getRepository(Restaurant::class)
                ->find($restaurantId);

            if (null == $currentRestaurant ) {

                $this->logger->addAlert(
                    'Restaurant not found with the Id '.$restaurantId,
                    ['export:CABi:excel']
                );

                return;
            }
            $output->writeln("Exporting BI data for ".$currentRestaurant->getName().' restaurant. | Code = '.$currentRestaurant->getCode());
        }

        if (!is_null($startDate) && !is_null($endDate)) {

            $startDate=\DateTime::createFromFormat("Y-m-d", $startDate);
            $endDate=\DateTime::createFromFormat("Y-m-d", $endDate);
        }

        if (null == $currentRestaurant) {

            $today = new \DateTime();
            $list= array('2780','2418','2710', '2723','2735', '1764', '2771', '2772', '2773', '2777', '1747', '1441','1015', '1291', '1292', '6293', '1294', '6295', '6296', '6297', '6298','1299','1751','1739');
            $restaurants=$this->em->getRepository(Restaurant::class)->createQueryBuilder("r")
                ->join(AdministrativeClosing::class, 'ad', Join::WITH, 'ad.originRestaurant = r')
                ->where('ad.date < :today')
                ->andWhere('r.active = true')
                ->andWhere('r.code IN (:list) ')
                ->setParameter('list', $list)
                ->setParameter('today', $today)->getQuery()
                ->getResult();


            //$restaurants = $this->em->getRepository(Restaurant::class)->getOpenedRestaurants();
            $progress = new ProgressBar($output, count($restaurants));
            $progress->start();
            foreach ($restaurants as $restaurant) {
                /**
                 * @var Restaurant $restaurant
                 */

                if (is_null($startDate) && is_null($endDate)) {

                    $startDate = $endDate = $this->getContainer()->get('administrative.closing.service')->getLastClosingDate($restaurant)->format($supportedFormat);
                }

                $this->logger->addDebug(
                    'Launching export Bon BI excel on restaurant '.$restaurant->getCode(),
                    ['export:CABi:excel']
                );
                $this->exportBonBiExcel($restaurant, $startDate->format('d/m/Y'), $endDate->format('d/m/Y'));

                $this->logger->addDebug(
                    'Finish export Bon BI excel on restaurant '.$restaurant->getCode(),
                    ['export:CABi:excel']
                );
                $progress->advance();
            }
            $this->logger->addDebug(
                '---> Finish export Bon BI excel on all restaurants ',
                ['export:CABi:excel']
            );
            $progress->finish();

        } else {

            if (is_null($startDate) && is_null($endDate)) {

                $startDate = $endDate = $this->getContainer()->get(
                    'administrative.closing.service'
                )->getLastClosingDate($currentRestaurant)->format($supportedFormat);
            }

            $this->logger->addDebug(
                'Launching export Bon BI excel on restaurant '.$currentRestaurant->getCode(),
                ['export:CABi:excel']
            );

            $this->exportBonBiExcel($currentRestaurant, $startDate->format('d/m/Y'), $endDate->format('d/m/Y'));

            $this->logger->addDebug(
                'Finish export Bon BI excel on restaurant '.$currentRestaurant->getCode(),
                ['export:CABi:excel']
            );
        }

    }

    /**
     * @param Restaurant $restaurant
     * @param $startDate
     * @param $endDate
     */
    public function exportBonBiExcel(Restaurant $restaurant, $startDate, $endDate)
    {
            $country = $restaurant->getCountry();
            $fileName = 'Moulinette Bon '.strtoupper($country);

        $path = $this->getContainer()->getParameter('kernel.root_dir')
            ."/../data/export/".$fileName.'.xlsx';


        if (file_exists($path)) {
            $fileExist = true;
        } else {
            $fileExist = false;
        }

        $criteria = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'restaurants' => [$restaurant],
        ];

        $results = $this->expenseService->getExpensesRecipe($criteria,null,null);

        $docType = "BON";

        $this->getContainer()->get('export.bi.excel')->generateExcel(
            $fileName,
            $country,
            $docType,
            $results,
            $path,
            $fileExist
        );

    }

}