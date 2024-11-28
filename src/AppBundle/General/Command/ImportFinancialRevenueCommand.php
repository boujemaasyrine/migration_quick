<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 17/04/2016
 * Time: 20:24
 */

namespace AppBundle\General\Command;

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Financial\Service\AdministrativeClosingService;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use AppBundle\Financial\Entity\FinancialRevenue;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportFinancialRevenueCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var AdministrativeClosingService
     */
    private $adminClosingService;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:financial:revenue:import')
            ->addOption('startDate', 'start', InputOption::VALUE_OPTIONAL, 'The start date.', '')
            ->addOption('endDate', 'end', InputOption::VALUE_OPTIONAL, 'The end date.', '')
            //if restaurantId is set, only this restaurant tickets are imported
            ->addOption('restaurantId', 'r', InputOption::VALUE_OPTIONAL, 'The restaurant id.', '')
            ->addOption('historic', 'historic', InputOption::VALUE_OPTIONAL, 'The historic flag.')
            ->setDescription('Import Financial Revenue From Wynd Tickets.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->logger = $this->getContainer()->get('monolog.logger.app_commands');
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->adminClosingService = $this->getContainer()->get('administrative.closing.service');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $currentRestaurant = null;
        if ($input->hasOption('restaurantId') && !empty($input->getOption('restaurantId'))) {
            $restaurantId = $input->getOption('restaurantId');
            $currentRestaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);
            if ($currentRestaurant == null) {
                $this->logger->addAlert('Restaurant not found with id: '.$restaurantId, ['quick:financial:revenue:import']);

                return;
            }
        }

        if ($input->hasOption('startDate') && $input->hasOption('endDate')) {
            $startDate = $input->getOption('startDate');
            $endDate = $input->getOption('endDate');
        } else {
            $startDate = null;
            $endDate = null;
        }

        $isHistoric = false;
        if ($input->getOption('historic') == 'true')
        {
            $isHistoric = true;
        }


        if($currentRestaurant == null)
        {
            $restaurants = $this->em->getRepository(Restaurant::class)->getOpenedRestaurants();
            if(sizeof($restaurants)!==0){
                $firstRestaurant=$restaurants[0];
                $date=$this->adminClosingService->getLastWorkingEndDate($firstRestaurant);
                if($date){
                    $startDate=$endDate=$date->format("Y-m-d");
                }

            }

            foreach ($restaurants as $restaurant)
            {
                $this->calculateFinancialRevenue($startDate, $endDate, $restaurant, $isHistoric);
            }
        }
        else
        {
            $supportedFormat = "Y-m-d";
            $start = date_create_from_format($supportedFormat, $startDate);
            $start->setTime(0,0,0);
            if($start> new \DateTime("2019-03-01")){
                $this->calculateFinancialRevenue($startDate, $endDate, $currentRestaurant, $isHistoric);
           }
        }
    }

    private function calculateFinancialRevenue($startDate, $endDate, Restaurant $restaurant, $isHistoric = false)
    {
        $t1 = time();
        $supportedFormat = "Y-m-d";
        if (!is_null($startDate) && !is_null($endDate) && Utilities::isValidDateFormat($startDate, $supportedFormat) && Utilities::isValidDateFormat($endDate, $supportedFormat)) {
            $startDate = date_create_from_format($supportedFormat, $startDate);
            $endDate = date_create_from_format($supportedFormat, $endDate);
        } else {
            $startDate = $this->adminClosingService->getLastWorkingEndDate($restaurant);
            $endDate = $this->adminClosingService->getLastWorkingEndDate($restaurant);
        }
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        for ($i = 0; $i <= $endDate->diff($startDate)->days; $i++) {
            $date = Utilities::getDateFromDate($startDate, $i);
            $caTtc = $this->em->getRepository(Ticket::class)->getTotalPerDay($date, false, $restaurant);
            $filter['beginDate'] = $date->format('Y-m-d');
            $filter['endDate'] = $date->format('Y-m-d');
            $caVenteAnnexe = $this->em->getRepository(TicketLine::class)->getCaVenteAnnexe($filter, $restaurant->getId());
            $caVA= $caVenteAnnexe['data'][0]['ca_va'] ? $caVenteAnnexe['data'][0]['ca_va']: 0;
            $caNetHTResult = $this->em->getRepository(Ticket::class)->getCaTicket($filter, $restaurant->getId());
            $voucherResult = $this->em->getRepository(Ticket::class)->getVoucherTicket($filter, $restaurant->getId());
            $br = $voucherResult['data'][0]['totalvoucher'] ? $voucherResult['data'][0]['totalvoucher'] : 0;
            $brHt= $voucherResult['data'][0]['total_voucher_ht'] ? $voucherResult['data'][0]['total_voucher_ht'] : 0;
            $caHt = $caNetHTResult['data'][0]['cabrutht'] ? $caNetHTResult['data'][0]['cabrutht'] : 0;
            $caTtc = $caNetHTResult['data'][0]['cabrutttc'] ? $caNetHTResult['data'][0]['cabrutttc'] : 0;
            $discountHT=$caNetHTResult['data'][0]['totaldiscountht'] ? abs($caNetHTResult['data'][0]['totaldiscountht']) : 0;
            $VA_HT=$caNetHTResult['data'][0]['va_ht'] ? $caNetHTResult['data'][0]['va_ht'] : 0;
            $VA_TTC=$caNetHTResult['data'][0]['va_ttc'] ? $caNetHTResult['data'][0]['va_ttc'] : 0;
            $totalDiscount = $caNetHTResult['data'][0]['totaldiscount'] ? abs($caNetHTResult['data'][0]['totaldiscount']) : 0;
            $ticketNumber= $this->em->getRepository(Ticket::class)->getTicketsCount( $date,  $date, $restaurant);
            $rounding = $this->em->getRepository(Ticket::class)->getRounding($filter, $restaurant->getId());
            $rounding=$rounding['data'][0]['discount_amount'];
//            $caNetHT = $caHt - $brHt;
            $caNetTTC = $caTtc - $br-$rounding;
            if (!$isHistoric) {
                //$nHistoricVoucherResult= $this->em->getRepository(Ticket::class)->getNHistoricVoucherTicket($filter, $restaurant->getId());
                //$brHt = $nHistoricVoucherResult['data'][0]['total_voucher_ht'] ? $nHistoricVoucherResult['data'][0]['total_voucher_ht'] : 0;
                $caBrutTTC = $caTtc + $totalDiscount-$rounding;
                $caBrutHt = $caHt + $totalDiscount;
                $caNetHT = $caHt - $brHt-$rounding;
            } else {
                $brHt = $voucherResult['data'][0]['total_voucher_ht'] ? $voucherResult['data'][0]['total_voucher_ht'] : 0;
                $caNetHT = $caHt - ($brHt + $discountHT + $VA_HT);
                $caBrutTTC = $caTtc;
                $caBrutHt = $caHt;
                $caNetTTC -= $totalDiscount + $VA_TTC;
            }
            if ($caTtc === null) {
                $caTtc = 0;
            }
            $revenue_date = $this->em->getRepository(FinancialRevenue::class)->findOneBy(array('date' => $date, 'originRestaurant' => $restaurant));
            if (is_null($revenue_date)) {
                $revenue_date = new FinancialRevenue();
                $revenue_date->setDate($date);
                $revenue_date->setOriginRestaurant($restaurant);
            }
            $revenue_date->setAmount($caTtc);
            $revenue_date->setNetHT($caNetHT)->setNetTTC($caNetTTC)->setBrutTTC($caBrutTTC)->setBr($br)->setBrHt($brHt)->setDiscount($totalDiscount)->setBrutHT($caBrutHt)->setCaVA($caVA);
            $revenue_date->setTicketNumber($ticketNumber);
            $this->em->persist($revenue_date);
            $this->em->flush();
            $this->em->refresh($revenue_date);
            $t2=time();
            if($isHistoric)
            {
                echo "Historical financial revenue for restaurant: " . $restaurant->getCode() . "  and date: " . date_format($date, 'Y-m-d') . " updated with success \n";
                 $this->logger->addInfo("Historical financial revenue for restaurant: " . $restaurant->getCode() . "  and date: " . date_format($date, 'Y-m-d') . " updated with success in ".
                 ($t2 - $t1)."seconds", ['quick:financial:revenue:import']);


            }
            else
            {
                echo "Financial Revenue for restaurant: " . $restaurant->getCode() . "  and " . date_format($date, 'Y-m-d') . " updated with success \n";
                $this->logger->addInfo("Financial Revenue for restaurant: " . $restaurant->getCode() . "  and date: " . date_format($date, 'Y-m-d') . " updated with success in ".                             ($t2 - $t1)."seconds", ['quick:financial:revenue:import']);


            }

        }
    }
}
