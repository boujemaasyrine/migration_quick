<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 03/05/2016
 * Time: 11:05
 */

namespace AppBundle\Report\Service;

use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Entity\FinancialRevenue;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Model\DayIncome;
use AppBundle\Financial\Service\CashboxService;
use AppBundle\Financial\Service\ChestService;
use AppBundle\Merchandise\Entity\CaPrev;
use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;
use AppBundle\ToolBox\Utils\DateUtilities;

class ReportDailyResultsService
{

    private $em;
    private $translator;
    private $cashboxService;
    private $parameterService;
    private $chestService;
    private $phpExcel;

    public function __construct(
        EntityManager $em,
        Translator $translator,
        CashboxService $cashboxService,
        ParameterService $parameterService,
        ChestService $chestService,
        Factory $factory
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->cashboxService = $cashboxService;
        $this->parameterService = $parameterService;
        $this->chestService = $chestService;
        $this->phpExcel = $factory;
    }

    /**
     * @param \DateTime $date
     * @return \DateTime $lastDate
     */
    public function getDateOfLastYear($date)
    {
        $lastDate = new \DateTime();
        $lastDate->setTimestamp($date->getTimestamp() - (86400 * 364));

        return $lastDate;
    }

    public function getAllDailyResults($filter)
    {
        $currentRestaurant = $this->em->getRepository(Restaurant::class)->find($filter["currentRestaurantId"]);

        $result = array();
        $startDate= clone $filter['startDate'];
        $endDate= clone $filter['endDate'];
        $days = DateUtilities::getDays($filter['startDate'], $filter['endDate']);
        $daysComp = DateUtilities::getDays($filter['compareStartDate'], $filter['compareEndDate']);
        $j = 0;
        $k = 0;
        $m = 0;
        $n = 0;
        $caNetHtCompTotalWeek = 0;
        $weekStartDate= clone $startDate;
        $monthStartDate= clone $startDate;
        for ($i = 0; $i < count($days); $i++) {
            if ($i == 0) {
                $this->intialiseData($result['weeks'][$j], $days[$i]->format('W'));
                $this->intialiseData($result['months'][$m], $days[$i]->format('m'));
                $this->intialiseData($result['total'], 1);
                $isOneWeek=false;
                for ($d = 1; $d < count($days); $d++) {
                    if ($days[$d]->format('N') == 1) {

                        $result['weeks'][$j]['takeOutPercentage'] = $this->em->getRepository(Ticket::class)->getTakeOutSalePercentageForDaillyResult($weekStartDate,$days[$d-1], $currentRestaurant);
                        $isOneWeek=true;
                        $weekStartDate= clone $days[$d];
                        break;
                    }
                }
                if(!$isOneWeek){

                    $result['weeks'][$j]['takeOutPercentage'] = $this->em->getRepository(Ticket::class)->getTakeOutSalePercentageForDaillyResult($weekStartDate,$endDate, $currentRestaurant);
                }
                $isOneMonth=false;
                for ($d = 1; $d < count($days); $d++) {
                    if ($days[$d]->format('d') == '01') {

                        $result['months'][$m]['takeOutPercentage'] = $this->em->getRepository(Ticket::class)->getTakeOutSalePercentageForDaillyResult($monthStartDate,$days[$d-1], $currentRestaurant);
                        $isOneMonth=true;
                        $monthStartDate= clone $days[$d];
                        break;
                    }
                }
                if(!$isOneMonth){

                    $result['months'][$m]['takeOutPercentage'] =  $this->em->getRepository(Ticket::class)->getTakeOutSalePercentageForDaillyResult($monthStartDate,$endDate, $currentRestaurant);
                }



            } elseif ($days[$i] != $filter['endDate']) {

                if ($days[$i]->format('N') == 1) {// test if it's monday
                    $k = 0;
                    $j = $j + 1;
                    $this->intialiseData($result['weeks'][$j], $days[$i]->format('W'));
                    //generate the % takeOut for the next week
                    $counterDate=clone $days[$i];
                    $endOfWeek=clone $counterDate;
                    $counterDate->modify('+1 day');
                    while($counterDate->format('N') != 1 && $endOfWeek < $endDate){
                        $endOfWeek = clone $counterDate;
                        $counterDate->modify('+1 day');
                    }

                    $result['weeks'][$j]['takeOutPercentage'] = $this->em->getRepository(Ticket::class)->getTakeOutSalePercentageForDaillyResult($weekStartDate,$endOfWeek, $currentRestaurant);
                    $weekStartDate= clone $counterDate;
                }


                if ($days[$i]->format('d') == '01') {// test if it's first day of month
                    $n = 0;
                    $m = $m + 1;
                    $this->intialiseData($result['months'][$m], $days[$i]->format('m'));
                    //generate the % takeOut for the next month
                    $counterDate=clone $days[$i];
                    $endOfMonth=clone $counterDate;
                    $counterDate->modify('+1 day');
                    while($counterDate->format('d') != '01' && $endOfMonth < $endDate){
                        $endOfMonth = clone $counterDate;
                        $counterDate->modify('+1 day');
                    }

                    $result['months'][$m]['takeOutPercentage'] = $this->em->getRepository(Ticket::class)->getTakeOutSalePercentageForDaillyResult($monthStartDate,$endOfMonth, $currentRestaurant);
                    $monthStartDate= clone $counterDate;

                }
            }
            $k++;
            $n++;
            $filter = [
                'beginDate' => $days[$i]->format('Y-m-d'),
                'endDate' => $days[$i]->format('Y-m-d'),
                'currentRestaurantId' => $filter["currentRestaurantId"],
                'comment' => $filter['comment'],
            ];

            //Days
            $budget = $this->em->getRepository(CaPrev::class)->getAmountByDate($days[$i], $currentRestaurant);

            $fRev = $this->em->getRepository(FinancialRevenue::class)->findOneBy(
                array(
                    'date' => $days[$i],
                    'originRestaurant' => $currentRestaurant,
                )
            );
            $fRevComp = $this->em->getRepository(FinancialRevenue::class)->findOneBy(
                array(
                    'date' => $daysComp[$i],
                    'originRestaurant' => $currentRestaurant,
                )
            );
            $caNetHtComp = $fRevComp != null ? $fRevComp->getNetHT() : 0;
            $caNetHtCompTotalWeek += $fRevComp != null ? $fRevComp->getNetHT() : 0;

//            $nbrTicketsComp = $this->em->getRepository(Ticket::class)->getTotalPerDay(
//                $daysComp[$i],
//                true,
//                $currentRestaurant
//            );

            if($fRevComp != null){
                $nbrTicketsComp=$fRevComp->getTicketNumber()!=0 ? $fRevComp->getTicketNumber() :  $nbrTicketsComp = $this->em->getRepository(Ticket::class)->getTotalPerDay($daysComp[$i], true, $currentRestaurant);
            }else{
                $nbrTicketsComp=0;
            }
            $avgTicketComp = ($nbrTicketsComp > 0) ? $caNetHtComp / $nbrTicketsComp :
                0;

            $result['days'][$i]['day'] = $days[$i];
            $result['days'][$i]['dayComp'] = $daysComp[$i];
            $result['days'][$i]['isComp'] = $this->em->getRepository(AdministrativeClosing::class)->isComparable(
                $daysComp[$i],
                $currentRestaurant
            );
            $result['days'][$i]['isCompThisDate'] = $this->em->getRepository(
                AdministrativeClosing::class
            )->isComparable($days[$i], $currentRestaurant);
            $result['days'][$i]['budget'] = $budget ? $budget : 0;
            $result['days'][$i]['pub'] = $fRev != null ? $fRev->getDiscount() : 0;
            $result['days'][$i]['caBrutTtc'] = $fRev != null ? $fRev->getBrutTTC() : 0;
            $result['days'][$i]['VA'] = $fRev != null ? $fRev->getCaVA() : 0;
            $result['days'][$i]['br'] = $fRev != null ? $fRev->getBr() : 0;
            $result['days'][$i]['caBrutHt'] = $fRev != null ? $fRev->getBrutHT() : 0;
            $result['days'][$i]['caNetHt'] = $fRev != null ? $fRev->getNetHT() : 0;
            $result['days'][$i]['caNetNOne'] = $caNetHtComp;
            $result['days'][$i]['caNetPerCentNOne'] = ($caNetHtComp != 0) ? ($result['days'][$i]['caNetHt'] - $caNetHtComp) / $caNetHtComp * 100 : null;
            $result['days'][$i]['chestError'] = $this->chestService->getChestGap($days[$i], null, $currentRestaurant);
            $result['days'][$i]['cashboxTotalGap'] = $this->cashboxService->calculateCashBoxTotalGap($days[$i], null, $currentRestaurant);
//            $result['days'][$i]['nbrTickets'] = $this->em->getRepository(Ticket::class)->getTotalPerDay($days[$i], true, $currentRestaurant);
            if($fRev != null){
                $result['days'][$i]['nbrTickets'] = $fRev->getTicketNumber() != 0 ?$fRev->getTicketNumber() : $this->em->getRepository(Ticket::class)->getTotalPerDay($days[$i], true, $currentRestaurant);
            }else{
                $result['days'][$i]['nbrTickets']=0;
            }
            $result['days'][$i]['nbrTicketsNOne'] = $nbrTicketsComp;
            $result['days'][$i]['nbrTicketsPerCentNOne'] = ($nbrTicketsComp > 0) ? ($result['days'][$i]['nbrTickets'] - $nbrTicketsComp) / $nbrTicketsComp * 100 : null;
            $result['days'][$i]['avgTicket'] = ($result['days'][$i]['nbrTickets'] > 0) ? $result['days'][$i]['caNetHt'] / $result['days'][$i]['nbrTickets'] : 0;
            $result['days'][$i]['avgTicketNOne'] = $avgTicketComp;
            $result['days'][$i]['avgTicketPerCentNOne'] = ($avgTicketComp > 0) ? ($result['days'][$i]['avgTicket'] - $avgTicketComp) / $avgTicketComp * 100 : null;

            $result['days'][$i]['takeOutPercentage'] = $this->em->getRepository(Ticket::class)->getTakeOutSalePercentageForDaillyResult($days[$i],$days[$i], $currentRestaurant);
            if($filter['comment'] == 1){
                $result['days'][$i]['comment'] = $this->em->getRepository(AdministrativeClosing::class)->getComment(
                    $days[$i],
                    $currentRestaurant
                );
            }else{
                $result['days'][$i]['comment'] = null;
            }

            $invLossVal = $this->em->getRepository(LossLine::class)->getFiltredLossLine($filter, true, false);
            $invLossVal = $invLossVal ? $invLossVal : 0;
            $result['days'][$i]['invLossVal'] = ($result['days'][$i]['caNetHt'] != 0) ? $invLossVal / $result['days'][$i]['caNetHt'] * 100 : -1;
            $arraySoldLossVal = $this->em->getRepository(LossLine::class)->getFiltredLossLineSold($filter, true, false);
            $soldLossVal = $arraySoldLossVal['lossvalorization'];
            $soldLossVal = $soldLossVal ? $soldLossVal : 0;
            $result['days'][$i]['soldLossVal'] = ($result['days'][$i]['caNetHt'] != 0) ? $soldLossVal / $result['days'][$i]['caNetHt'] * 100 : -1;

            //Weeks
            $result['weeks'][$j]['budget'] += $result['days'][$i]['budget'];
            $result['weeks'][$j]['caBrutTtc'] += $result['days'][$i]['caBrutTtc'];
           // $result['weeks'][$j]['VA'] += $result['days'][$i]['VA'];
            $result['weeks'][$j]['VA'] += isset($result['days'][$i]['VA']) ? $result['days'][$i]['VA'] : 0;
            $result['weeks'][$j]['pub'] += $result['days'][$i]['pub'];
            $result['weeks'][$j]['br'] += $result['days'][$i]['br'];
            $result['weeks'][$j]['caNetHt'] += $result['days'][$i]['caNetHt'];
            $result['weeks'][$j]['nbrTickets'] += $result['days'][$i]['nbrTickets'];
            $result['weeks'][$j]['nbrTicketsNOne'] += $result['days'][$i]['nbrTicketsNOne'];
            $result['weeks'][$j]['avgTicket'] += $result['days'][$i]['avgTicket'];
            $result['weeks'][$j]['invLossVal'] += $invLossVal;
            $result['weeks'][$j]['soldLossVal'] += $soldLossVal;
            $result['weeks'][$j]['caNetNOne'] += $result['days'][$i]['caNetNOne'];
            $result['weeks'][$j]['caNetPerCentNOne'] += $result['days'][$i]['caNetPerCentNOne'];
            $result['weeks'][$j]['cashboxTotalGap'] += $result['days'][$i]['cashboxTotalGap'];
            $result['weeks'][$j]['chestError'] += $result['days'][$i]['chestError'];
            $result['weeks'][$j]['avgTicketPerCentNOne'] += $result['days'][$i]['avgTicketPerCentNOne'];
            if ($days[$i]->format('N') == 7 || !isset($days[$i + 1])) {
                $result['weeks'][$j]['avgTicket'] = $result['weeks'][$j]['nbrTickets'] > 0 ?
                    $result['weeks'][$j]['caNetHt'] / $result['weeks'][$j]['nbrTickets'] : 0;
                $result['weeks'][$j]['avgTicketNOne'] = $result['weeks'][$j]['nbrTicketsNOne'] > 0 ?
                    $result['weeks'][$j]['caNetNOne'] / $result['weeks'][$j]['nbrTicketsNOne'] : 0;
                $result['weeks'][$j]['invLossVal'] = ($result['weeks'][$j]['caNetHt'] != 0) ?
                    $result['weeks'][$j]['invLossVal'] / $result['weeks'][$j]['caNetHt'] * 100 : -1;
                $result['weeks'][$j]['soldLossVal'] = ($result['weeks'][$j]['caNetHt'] != 0) ?
                    $result['weeks'][$j]['soldLossVal'] / $result['weeks'][$j]['caNetHt'] * 100 : -1;
                $result['weeks'][$j]['caNetPerCentNOne'] = $result['weeks'][$j]['caNetNOne'] > 0 ?
                    ($result['weeks'][$j]['caNetHt'] - $result['weeks'][$j]['caNetNOne']) / $result['weeks'][$j]['caNetNOne'] * 100 : -1;
                $result['weeks'][$j]['nbrTicketsPerCentNOne'] = ($result['weeks'][$j]['nbrTicketsNOne'] > 0) ?
                    ($result['weeks'][$j]['nbrTickets'] - $result['weeks'][$j]['nbrTicketsNOne']) / $result['weeks'][$j]['nbrTicketsNOne'] * 100 : null;

                $result['weeks'][$j]['avgTicketPerCentNOne'] = ($result['weeks'][$j]['avgTicketNOne'] > 0) ?
                    ($result['weeks'][$j]['avgTicket'] - $result['weeks'][$j]['avgTicketNOne']) / $result['weeks'][$j]['avgTicketNOne'] * 100 : null;
            }

            //Months
            $result['months'][$m]['budget'] += $result['days'][$i]['budget'];
            $result['months'][$m]['caBrutTtc'] += $result['days'][$i]['caBrutTtc'];
           // $result['months'][$m]['VA'] += $result['days'][$i]['VA'];
            $result['months'][$m]['VA'] += isset($result['days'][$i]['VA']) ? $result['days'][$i]['VA'] : 0;
            $result['months'][$m]['pub'] += $result['days'][$i]['pub'];
            $result['months'][$m]['br'] += $result['days'][$i]['br'];
            $result['months'][$m]['caNetHt'] += $result['days'][$i]['caNetHt'];
            $result['months'][$m]['caNetNOne'] += $result['days'][$i]['caNetNOne'];
            $result['months'][$m]['nbrTickets'] += $result['days'][$i]['nbrTickets'];
            $result['months'][$m]['nbrTicketsNOne'] += $result['days'][$i]['nbrTicketsNOne'];
            $result['months'][$m]['avgTicket'] += $result['days'][$i]['avgTicket'];
            $result['months'][$m]['invLossVal'] += $invLossVal;
            $result['months'][$m]['soldLossVal'] += $soldLossVal;
            $result['months'][$m]['caNetPerCentNOne'] += $result['days'][$i]['caNetPerCentNOne'];
            $result['months'][$m]['cashboxTotalGap'] += $result['days'][$i]['cashboxTotalGap'];
            $result['months'][$m]['chestError'] += $result['days'][$i]['chestError'];
            $result['months'][$m]['avgTicketPerCentNOne'] += $result['days'][$i]['avgTicketPerCentNOne'];

            if (!isset($days[$i + 1]) || (isset($days[$i + 1]) && $days[$i]->format('m') != $days[$i + 1]->format(
                        'm'
                    ))) {
                $result['months'][$m]['invLossVal'] = ($result['months'][$m]['caNetHt'] != 0) ?
                    $result['months'][$m]['invLossVal'] / $result['months'][$m]['caNetHt'] * 100 : -1;
                $result['months'][$m]['soldLossVal'] = ($result['months'][$m]['caNetHt'] != 0) ?
                    $result['months'][$m]['soldLossVal'] / $result['months'][$m]['caNetHt'] * 100 : -1;
                $result['months'][$m]['caNetPerCentNOne'] = $result['months'][$m]['caNetNOne'] ?
                    ($result['months'][$m]['caNetHt'] - $result['months'][$m]['caNetNOne']) / $result['months'][$m]['caNetNOne'] * 100 : 0;
                $result['months'][$m]['nbrTicketsPerCentNOne'] = ($result['months'][$m]['nbrTicketsNOne'] > 0) ?
                    ($result['months'][$m]['nbrTickets'] - $result['months'][$m]['nbrTicketsNOne']) / $result['months'][$m]['nbrTicketsNOne'] * 100 : null;

                $result['months'][$m]['avgTicket'] = $result['months'][$m]['nbrTickets'] > 0 ?
                    $result['months'][$m]['caNetHt'] / $result['months'][$m]['nbrTickets'] : 0;
                $result['months'][$m]['avgTicketNOne'] = $result['months'][$m]['nbrTicketsNOne'] > 0 ?
                    $result['months'][$m]['caNetNOne'] / $result['months'][$m]['nbrTicketsNOne'] : 0;
                $result['months'][$m]['avgTicketPerCentNOne'] = ($result['months'][$m]['avgTicketNOne'] > 0) ?
                    ($result['months'][$m]['avgTicket'] - $result['months'][$m]['avgTicketNOne']) / $result['months'][$m]['avgTicketNOne'] * 100 : null;
            }

            //Total
            $result['total']['budget'] += $result['days'][$i]['budget'];
            $result['total']['caBrutTtc'] += $result['days'][$i]['caBrutTtc'];
            //$result['total']['VA'] += $result['days'][$i]['VA'];
            $result['total']['VA'] += isset($result['days'][$i]['VA']) ? $result['days'][$i]['VA'] : 0;
            $result['total']['pub'] += $result['days'][$i]['pub'];
            $result['total']['br'] += $result['days'][$i]['br'];
            $result['total']['caNetHt'] += $result['days'][$i]['caNetHt'];
            $result['total']['caNetNOne'] += $result['days'][$i]['caNetNOne'];
            $result['total']['nbrTickets'] += $result['days'][$i]['nbrTickets'];
            $result['total']['nbrTicketsNOne'] += $result['days'][$i]['nbrTicketsNOne'];
            $result['total']['avgTicket'] += $result['days'][$i]['avgTicket'];
            $result['total']['invLossVal'] += $invLossVal;
            $result['total']['soldLossVal'] += $soldLossVal;
            $result['total']['caNetPerCentNOne'] += $result['days'][$i]['caNetPerCentNOne'];
            $result['total']['cashboxTotalGap'] += $result['days'][$i]['cashboxTotalGap'];
            $result['total']['chestError'] += $result['days'][$i]['chestError'];
            $result['total']['avgTicketPerCentNOne'] += $result['days'][$i]['avgTicketPerCentNOne'];

            if (!isset($days[$i + 1])) {
                $result['total']['invLossVal'] = ($result['total']['caNetHt'] != 0) ?
                    $result['total']['invLossVal'] / $result['total']['caNetHt'] * 100 : -1;
                $result['total']['soldLossVal'] = ($result['total']['caNetHt'] != 0) ?
                    $result['total']['soldLossVal'] / $result['total']['caNetHt'] * 100 : -1;
                $result['total']['caNetPerCentNOne'] = $result['total']['caNetNOne'] ?
                    ($result['total']['caNetHt'] - $result['total']['caNetNOne']) / $result['total']['caNetNOne'] * 100 : 0;
                $result['total']['nbrTicketsPerCentNOne'] = ($result['total']['nbrTicketsNOne'] > 0) ?
                    ($result['total']['nbrTickets'] - $result['total']['nbrTicketsNOne']) / $result['total']['nbrTicketsNOne'] * 100 : null;
                $result['total']['avgTicket'] = $result['total']['nbrTickets'] > 0 ?
                    $result['total']['caNetHt'] / $result['total']['nbrTickets'] : 0;

                $result['total']['avgTicketNOne'] = $result['total']['nbrTicketsNOne'] > 0 ?
                    $result['total']['caNetNOne'] / $result['total']['nbrTicketsNOne'] : 0;
                $result['total']['avgTicketPerCentNOne'] = ($result['total']['avgTicketNOne'] > 0) ?
                    ($result['total']['avgTicket'] - $result['total']['avgTicketNOne']) / $result['total']['avgTicketNOne'] * 100 : null;
            }
        }

        $result['total']['takeOutPercentage'] =$this->em->getRepository(Ticket::class)->getTakeOutSalePercentageForDaillyResult($startDate,$endDate, $currentRestaurant);
        return $result;
    }

    public function getDailyResults($date = null)
    {
        $result = array();

        if ($date == null) {
            $day = Utilities::getDateFromDate($date, -1);
        } else {
            $day = $date;
        }

        $compDay = Utilities::getDateFromDate($date, -52 * 7);
        $filter = ['beginDate' => $day->format('Y-m-d'), 'endDate' => $day->format('Y-m-d')];

        $details = $this->em->getRepository('Financial:Ticket')->getCaTicket($filter);
        $detailsVoucher = $this->em->getRepository('Financial:Ticket')->getCaTicket($filter);

        $result['day'] = $day;

        $result['pub'] = $details['data'][0]['totaldiscount'] ? $details['data'][0]['totaldiscount'] : 0;
        $result['br'] = $detailsVoucher['data'][0]['totalvoucher'] ? $detailsVoucher['data'][0]['totalvoucher'] : 0;
        $result['brHt'] = $detailsVoucher['data'][0]['total_voucher_ht'] ? $detailsVoucher['data'][0]['total_voucher_ht'] : 0;
        $result['caBrutHt'] = $details['data'][0]['cabrutht'] ? $details['data'][0]['cabrutht'] : 0;
        $result['caNetHt'] = $result['caBrutHt'] - $result['brHt'] + $result['pub'];
        $result['nbrTickets'] = $this->em->getRepository('Financial:Ticket')->getTotalPerDay($day, true);
        $nbrTicketsComp = $this->em->getRepository('Financial:Ticket')->getTotalPerDay($compDay, true);
        $result['nbrTicketsPerCentNOne'] = ($nbrTicketsComp > 0) ? ($result['nbrTickets'] - $nbrTicketsComp) / $nbrTicketsComp * 100 : null;
        $result['avgTicket'] = ($result['nbrTickets'] > 0) ? $result['caNetHt'] / $result['nbrTickets'] :
            0;

        $compCA = $this->em->getRepository('Financial:FinancialRevenue')->findOneBy(array('date' => $compDay));
        $result['budget'] = $compCA ? $compCA->getAmount() : 0;

        $dayIncome = new DayIncome();
        $dayIncome->setDate($day);
        $dayIncome->setCashboxCounts($this->cashboxService->findCashboxCountsByDate($dayIncome->getDate()));

        $invLossVal = $this->em->getRepository('Merchandise:LossLine')->getFiltredLossLine($filter, true, false);
        $invLossVal = $invLossVal ? $invLossVal : 0;
        $result['invLossVal'] = ($result['caNetHt'] != 0) ? $invLossVal / $result['caNetHt'] * 100 : 0;

        $arraySoldLossVal = $this->em->getRepository('Merchandise:LossLine')->getFiltredLossLineSold($filter, true, false);
        $soldLossVal = $arraySoldLossVal['lossvalorization'];
        $soldLossVal = $soldLossVal ? $soldLossVal : 0;
        $result['soldLossVal'] = ($result['caNetHt'] != 0) ? $soldLossVal / $result['caNetHt'] * 100 : 0;

        return $result;
    }

    public function intialiseData(&$array, $index)
    {
        $array = [
            'index' => $index,
            'budget' => 0,
            'caBrutTtc' => 0,
            'pub' => 0,
            'br' => 0,
            'caBrutHt' => 0,
            'caNetHt' => 0,
            'caNetNOne' => 0,
            'nbrTickets' => 0,
            'nbrTicketsNOne' => 0,
            'avgTicket' => 0,
            'invLossVal' => 0,
            'soldLossVal' => 0,
            'caNetPerCentNOne' => 0,
            'cashboxTotalGap' => 0,
            'chestError' => 0,
            'nbrTicketsPerCentNOne' => 0,
            'avgTicketPerCentNOne' => 0,
        ];
    }

    public function serializeDailyResultsReportResult($result)
    {
        $serializedResult = [];
        foreach ($result['days'] as $line) {
            $serializedResult[] = [
                $this->translator->trans('days.'.$line['day']->format('D')).' '.$line['day']->format('d/m/Y'),
                $this->translator->trans('days.'.$line['dayComp']->format('D')).' '.$line['dayComp']->format('d/m/Y'),
                number_format($line['budget'], 2, ',', ' '),
                number_format($line['caBrutTtc'], 2, ',', ' '),
                number_format($line['VA'], 2, ',', ' '),
                number_format($line['pub'], 2, ',', ' '),
                number_format($line['br'], 2, ',', ' '),
                number_format($line['caNetHt'], 2, ',', ' '),
                $line['caNetPerCentNOne'] ? number_format($line['caNetPerCentNOne'], 2, ',', ' ') : '*',
                $line['nbrTickets'],
                $line['nbrTicketsPerCentNOne'] ? number_format($line['nbrTicketsPerCentNOne'], 2, ',', ' ') : '*',
                number_format($line['avgTicket'], 2, ',', ' '),
                $line['avgTicketPerCentNOne'] ? number_format($line['avgTicketPerCentNOne'], 2, ',', ' ') : '*',
                number_format($line['cashboxTotalGap'], 2, ',', ' '),
                number_format($line['chestError'], 2, ',', ' '),
                ($line['soldLossVal'] != -1) ? number_format($line['soldLossVal'], 2, ',', ' ') : '*',
                ($line['invLossVal'] != -1) ? number_format($line['invLossVal'], 2, ',', ' ') : '*',
                number_format($line['takeOutPercentage'], 2, ',', ' '),
                $line['comment'],

            ];
        }

        $serializedResult[] = [];
        $serializedResult[] = [
            $this->translator->trans('keyword.week'),
            '',
            $this->translator->trans('budget_label'),
            $this->translator->trans('report.ca.ca_brut_ttc'),
            $this->translator->trans('report.daily_result.vente_annexe'),
            $this->translator->trans('report.discount'),
            $this->translator->trans('report.br'),
            $this->translator->trans('report.ca.ca_net_ht'),
            '% (-1)',
            $this->translator->trans('report.sales.hour_by_hour.tickets'),
            '% (-1)',
            $this->translator->trans('report.daily_result.avg_net_ticket'),
            '% (-1)',
            $this->translator->trans('cashbox_counts_anomalies.report_labels.diff_caisse'),
            $this->translator->trans('report.daily_result.chest_error'),
            $this->translator->trans('report.daily_result.sold_loss'),
            $this->translator->trans('report.daily_result.inventory_loss'),
            $this->translator->trans('report.daily_result.takeout_percentage'),
        ];

        foreach ($result['weeks'] as $line) {
            $serializedResult[] = [
                $line['index'],
                '',
                number_format($line['budget'], 2, ',', ' '),
                number_format($line['caBrutTtc'], 2, ',', ' '),
                number_format($line['VA'], 2, ',', ' '),
                number_format($line['pub'], 2, ',', ' '),
                number_format($line['br'], 2, ',', ' '),
                number_format($line['caNetHt'], 2, ',', ' '),
                $line['caNetPerCentNOne'] ? number_format($line['caNetPerCentNOne'], 2, ',', ' ') : '*',
                $line['nbrTickets'],
                $line['nbrTicketsPerCentNOne'] ? number_format($line['nbrTicketsPerCentNOne'], 2, ',', ' ') : '*',
                number_format($line['avgTicket'], 2, ',', ' '),
                $line['avgTicketPerCentNOne'] ? number_format($line['avgTicketPerCentNOne'], 2, ',', ' ') : '*',
                number_format($line['cashboxTotalGap'], 2, ',', ' '),
                number_format($line['chestError'], 2, ',', ' '),
                ($line['soldLossVal'] != -1) ? number_format($line['soldLossVal'], 2, ',', ' ') : '*',
                ($line['invLossVal'] != -1) ? number_format($line['invLossVal'], 2, ',', ' ') : '*',
                number_format($line['takeOutPercentage'], 2, ',', ' '),
                '',

            ];
        }
        $serializedResult[] = [];
        $serializedResult[] = [
            $this->translator->trans('keyword.month'),
            '',
            $this->translator->trans('budget_label'),
            $this->translator->trans('report.ca.ca_brut_ttc'),
            $this->translator->trans('report.daily_result.vente_annexe'),
            $this->translator->trans('report.discount'),
            $this->translator->trans('report.br'),
            $this->translator->trans('report.ca.ca_net_ht'),
            '% (-1)',
            $this->translator->trans('report.sales.hour_by_hour.tickets'),
            '% (-1)',
            $this->translator->trans('report.daily_result.avg_net_ticket'),
            '% (-1)',
            $this->translator->trans('cashbox_counts_anomalies.report_labels.diff_caisse'),
            $this->translator->trans('report.daily_result.chest_error'),
            $this->translator->trans('report.daily_result.sold_loss'),
            $this->translator->trans('report.daily_result.inventory_loss'),
            $this->translator->trans('report.daily_result.takeout_percentage'),
        ];

        foreach ($result['months'] as $line) {
            $serializedResult[] = [
                $line['index'],
                '',
                number_format($line['budget'], 2, ',', ' '),
                number_format($line['caBrutTtc'], 2, ',', ' '),
                number_format($line['VA'], 2, ',', ' '),
                number_format($line['pub'], 2, ',', ' '),
                number_format($line['br'], 2, ',', ' '),
                number_format($line['caNetHt'], 2, ',', ' '),
                $line['caNetPerCentNOne'] ? number_format($line['caNetPerCentNOne'], 2, ',', ' ') : '*',
                $line['nbrTickets'],
                $line['nbrTicketsPerCentNOne'] ? number_format($line['nbrTicketsPerCentNOne'], 2, ',', ' ') : '*',
                number_format($line['avgTicket'], 2, ',', ' '),
                $line['avgTicketPerCentNOne'] ? number_format($line['avgTicketPerCentNOne'], 2, ',', ' ') : '*',
                number_format($line['cashboxTotalGap'], 2, ',', ' '),
                number_format($line['chestError'], 2, ',', ' '),
                ($line['soldLossVal'] != -1) ? number_format($line['soldLossVal'], 2, ',', ' ') : '*',
                ($line['invLossVal'] != -1) ? number_format($line['invLossVal'], 2, ',', ' ') : '*',
                number_format($line['takeOutPercentage'], 2, ',', ' '),
                '',

            ];
        }

        $serializedResult[] = [];
        $serializedResult[] = [
            $this->translator->trans('keyword.total'),
            '',
            $this->translator->trans('budget_label'),
            $this->translator->trans('report.ca.ca_brut_ttc'),
            $this->translator->trans('report.daily_result.vente_annexe'),
            $this->translator->trans('report.discount'),
            $this->translator->trans('report.br'),
            $this->translator->trans('report.ca.ca_net_ht'),
            '% (-1)',
            $this->translator->trans('report.sales.hour_by_hour.tickets'),
            '% (-1)',
            $this->translator->trans('report.daily_result.avg_net_ticket'),
            '% (-1)',
            $this->translator->trans('cashbox_counts_anomalies.report_labels.diff_caisse'),
            $this->translator->trans('report.daily_result.chest_error'),
            $this->translator->trans('report.daily_result.sold_loss'),
            $this->translator->trans('report.daily_result.inventory_loss'),
            $this->translator->trans('report.daily_result.takeout_percentage'),
        ];
        $serializedResult[] = [
            '',
            '',
            number_format($result['total']['budget'], 2, ',', ' '),
            number_format($result['total']['caBrutTtc'], 2, ',', ' '),
            number_format($result['total']['VA'], 2, ',', ' '),
            number_format($result['total']['pub'], 2, ',', ' '),
            number_format($result['total']['br'], 2, ',', ' '),
            number_format($result['total']['caNetHt'], 2, ',', ' '),
            $result['total']['caNetPerCentNOne'] ? number_format(
                $result['total']['caNetPerCentNOne'],
                2,
                ',',
                ' '
            ) : '*',
            $result['total']['nbrTickets'],
            $result['total']['nbrTicketsPerCentNOne'] ? number_format(
                $result['total']['nbrTicketsPerCentNOne'],
                2,
                ',',
                ' '
            ) : '*',
            number_format($result['total']['avgTicket'], 2, ',', ' '),
            $result['total']['avgTicketPerCentNOne'] ? number_format(
                $result['total']['avgTicketPerCentNOne'],
                2,
                ',',
                ' '
            ) : '*',
            number_format($result['total']['cashboxTotalGap'], 2, ',', ' '),
            number_format($result['total']['chestError'], 2, ',', ' '),
            ($result['total']['soldLossVal'] != -1) ? number_format($result['total']['soldLossVal'], 2, ',', ' ') : '*',
            ($result['total']['invLossVal'] != -1) ? number_format($result['total']['invLossVal'], 2, ',', ' ') : '*',
            number_format($result['total']['takeOutPercentage'], 2, ',', ' '),
            '',

        ];

        return $serializedResult;
    }

    /**
     * @param \DateTime $date
     * @return array
     */
    public function calculateCancelsAbandonmentCorrectionsCashBox(\DateTime $date)
    {
        $date->setTime(0, 0, 0); // reset time part, to prevent partial comparison
        $cashboxCounts = $this->em->getRepository('Financial:CashboxCount')
            ->createQueryBuilder('cc')
            ->select(
                'sum(cc.totalAbondons) as totalAbondons, sum(cc.totalCancels) as totalCancels, sum(cc.totalCorrections) as totalCorrections'
            )
            ->where('cc.date = :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getArrayResult();

        $result = [
            'cancelsAmount' => isset($cashboxCounts[0]) ? $cashboxCounts[0]['totalCancels'] : 0,
            'abandonmentAmount' => isset($cashboxCounts[0]) ? $cashboxCounts[0]['totalAbondons'] : 0,
            'correctionsAmount' => isset($cashboxCounts[0]) ? $cashboxCounts[0]['totalCorrections'] : 0,
        ];

        return $result;
    }

    public function generateExcelFile($result, $filter, Restaurant $currentRestaurant, $logoPath)
    {
        $colorOne = "ECECEC";
        $colorTwo = "E5CFAB";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('financial_summary.title'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('financial_summary.title');
        $sheet->setCellValue('B5', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("B5"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B5"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 5), 22, true);

        //logo
        $objDrawing = new \PHPExcel_Worksheet_Drawing();
        $objDrawing->setName('Logo');
        $objDrawing->setDescription('Logo');
        $objDrawing->setPath($logoPath);
        $objDrawing->setOffsetX(35);
        $objDrawing->setOffsetY(0);
        $objDrawing->setCoordinates('A2');
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 2), 12, true);
        $objDrawing->setWidth(28);                 //set width, height
        $objDrawing->setHeight(32);
        $objDrawing->setWorksheet($sheet);
        //restaurant name
        $sheet->mergeCells("B2:F2");
        $content = $currentRestaurant->getCode().' '.$currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);

        //FILTER ZONE

        //Periode
        $sheet->mergeCells("A10:B10");
        ExcelUtilities::setFont($sheet->getCell('A10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A10"), $colorTwo);
        $sheet->setCellValue('A10', $this->translator->trans('report.period').":");
        ExcelUtilities::setCellAlignment($sheet->getCell("A10"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A10"), $alignmentV);

        ExcelUtilities::setFont($sheet->getCell('A11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A11"), $colorOne);
        $sheet->setCellValue('A11', $this->translator->trans('keyword.from').":");
        ExcelUtilities::setFont($sheet->getCell('B11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B11"), $colorOne);
        $sheet->setCellValue('B11', $filter['startDate']->format('Y-m-d'));    // START DATE



        // END DATE
        ExcelUtilities::setFont($sheet->getCell('A12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A12"), $colorOne);
        $sheet->setCellValue('A12', $this->translator->trans('keyword.to').":");
        ExcelUtilities::setFont($sheet->getCell('B12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B12"), $colorOne);
        $sheet->setCellValue('B12', $filter['endDate']->format('Y-m-d'));

        //comparabilité
        $sheet->mergeCells("F10:I10");
        ExcelUtilities::setFont($sheet->getCell('F10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F10"), $colorTwo);
        $sheet->setCellValue('F10', $this->translator->trans('report.period_to_compare').":");
        ExcelUtilities::setCellAlignment($sheet->getCell("F10"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("F10"), $alignmentV);


        // START DATE
        $sheet->mergeCells("F11:G11");
        ExcelUtilities::setFont($sheet->getCell('F11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F11"), $colorOne);
        $sheet->setCellValue('F11', $this->translator->trans('keyword.from').":");
        $sheet->mergeCells("H11:I11");
        ExcelUtilities::setFont($sheet->getCell('H11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H11"), $colorOne);
        $sheet->setCellValue('H11', $filter['compareStartDate']->format('Y-m-d'));


        // END DATE
        $sheet->mergeCells("F12:G12");
        ExcelUtilities::setFont($sheet->getCell('F12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F12"), $colorOne);
        $sheet->setCellValue('F12', $this->translator->trans('keyword.to').":");
        $sheet->mergeCells("H12:I12");
        ExcelUtilities::setFont($sheet->getCell('H12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H12"), $colorOne);
        $sheet->setCellValue('H12', $filter['compareEndDate']->format('Y-m-d'));


        //Content
        //Date
        $i = 14;
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('keyword.date'));
        //Date comparable
        ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B".$i), $colorOne);
        $sheet->setCellValue('B'.$i, $this->translator->trans('keyword.date_comp'));
        //Budget
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorOne);
        $sheet->setCellValue('C'.$i, $this->translator->trans('budget_label'));
        //Brut ttc
        ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D".$i), $colorOne);
        $sheet->setCellValue('D'.$i, $this->translator->trans('report.ca.ca_brut_ttc'));

        //Ventes Annexes
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorOne);
        $sheet->setCellValue('E'.$i, $this->translator->trans('report.daily_result.vente_annexe'));

        //Discount
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorOne);
        $sheet->setCellValue('F'.$i, $this->translator->trans('report.discount'));

        //BR
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), $colorOne);
        $sheet->setCellValue('G'.$i, $this->translator->trans('report.br'));

        //NET HT
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorOne);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->setCellValue('H'.$i, $this->translator->trans('report.ca.ca_net_ht'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), $colorOne);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->setCellValue('I'.$i, '%(-1)');
        //ticket
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), $colorOne);
        $sheet->getColumnDimension('J')->setAutoSize(true);
        $sheet->setCellValue('J'.$i, $this->translator->trans('report.sales.hour_by_hour.tickets'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K".$i), $colorOne);
        $sheet->getColumnDimension('K')->setAutoSize(true);
        $sheet->setCellValue('K'.$i, '% (-1)');
        //tm brut
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorOne);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->setCellValue('L'.$i, $this->translator->trans('report.daily_result.avg_net_ticket'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M".$i), $colorOne);
        $sheet->getColumnDimension('M')->setAutoSize(true);
        $sheet->setCellValue('M'.$i, '% (-1)');
        //DIFF CAISSE
        ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N".$i), $colorOne);
        $sheet->getColumnDimension('N')->setAutoSize(true);
        $sheet->setCellValue('N'.$i, $this->translator->trans('cashbox_counts_anomalies.report_labels.diff_caisse'));

        //ERR COFFRE
        ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O".$i), $colorOne);
        $sheet->getColumnDimension('O')->setAutoSize(true);
        $sheet->setCellValue('O'.$i, $this->translator->trans('report.daily_result.chest_error'));
        //CORRECTIONS
        ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P".$i), $colorOne);
        $sheet->getColumnDimension('P')->setAutoSize(true);
        $sheet->setCellValue('P'.$i, $this->translator->trans('report.daily_result.sold_loss'));

        ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q".$i), $colorOne);
        $sheet->getColumnDimension('Q')->setAutoSize(true);
        $sheet->setCellValue('Q'.$i, $this->translator->trans('report.daily_result.inventory_loss'));

        ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R".$i), $colorOne);
        $sheet->getColumnDimension('R')->setAutoSize(true);
        $sheet->setCellValue('R'.$i, $this->translator->trans('report.daily_result.takeout_percentage'));

        //Border
        $cell = 'A';
        while ($cell != 'S') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }

        //Content
        $i = 15;
        foreach ($result['days'] as $line) {
            if (!is_null($line['comment']) && $line['comment'] != '') {
                $k = $i + 1;
            } else {
                $k = $i;
            }
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            if ($line['isCompThisDate'] === false) {
                $sheet->setCellValue(
                    'A'.$i,
                    $this->translator->trans('days.'.$line['day']->format('D')).' '.$line['day']->format('d/m/Y').' (!)'
                );
            } else {
                $sheet->setCellValue(
                    'A'.$i,
                    $this->translator->trans('days.'.$line['day']->format('D')).' '.$line['day']->format('d/m/Y')
                );
            }
            ExcelUtilities::setCellAlignment($sheet->getCell('A'.$i), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('A'.$i), $alignmentV);

            //Date comparable
            ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
            $sheet->setCellValue(
                'B'.$i,
                $this->translator->trans('days.'.$line['dayComp']->format('D')).' '.$line['dayComp']->format('d/m/Y')
            );

            //Budget
            ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
            $sheet->setCellValue('C'.$i, round($line['budget'], 2));
            //Brut ttc
            ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
            $sheet->setCellValue('D'.$i, round($line['caBrutTtc'], 2));

            //Ventes Annexes
            ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
            $sheet->setCellValue('E'.$i, round($line['VA'], 2));

            //Discount
            ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
            $sheet->setCellValue('F'.$i, round($line['pub'], 2));

            //BR
            ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
            $sheet->setCellValue('G'.$i, round($line['br'], 2));

            //NET HT
            ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
            $sheet->setCellValue('H'.$i, round($line['caNetHt'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
            if (is_null($line['caNetPerCentNOne'])) {
                $sheet->setCellValue('I'.$i, '*');
            } else {
                $sheet->setCellValue('I'.$i, round($line['caNetPerCentNOne'], 2));
            }
            //ticket
            ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
            $sheet->setCellValue('J'.$i, round($line['nbrTickets'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
            if (is_null($line['nbrTicketsPerCentNOne'])) {
                $sheet->setCellValue('K'.$i, '*');
            } else {
                $sheet->setCellValue('K'.$i, round($line['nbrTicketsPerCentNOne'], 2));
            }
            //tm brut
            ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
            $sheet->setCellValue('L'.$i, round($line['avgTicket'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
            if (is_null($line['avgTicketPerCentNOne'])) {
                $sheet->setCellValue('M'.$i, '*');
            } else {
                $sheet->setCellValue('M'.$i, round($line['avgTicketPerCentNOne'], 2));
            }
            //DIFF CAISSE
            ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
            $sheet->setCellValue('N'.$i, round($line['cashboxTotalGap'], 2));

            //ERR COFFRE
            ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
            $sheet->setCellValue('O'.$i, round($line['chestError'], 2));
            //CORRECTIONS
            ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
            if ($line['soldLossVal'] == -1) {
                $sheet->setCellValue('P'.$i, '*');
            } else {
                $sheet->setCellValue('P'.$i, round($line['soldLossVal'], 2));
            }
            ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
            if ($line['invLossVal'] == -1) {
                $sheet->setCellValue('Q'.$i, '*');
            } else {
                $sheet->setCellValue('Q'.$i, round($line['invLossVal'], 2));
            }

            // % TakeOut
            ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
            $sheet->setCellValue('R'.$i, round($line['takeOutPercentage'], 2));

            //Comment
            if (!is_null($line['comment']) && $line['comment'] != '') {
                $i++;
                //  $sheet->mergeCells('C'.$i.':S'.$i);
                $sheet->setCellValue('C'.$i, $line['comment']);
            }
            //Border
            $cell = 'A';
            while ($cell != 'S') {
                if (!is_null($line['comment']) && $line['comment'] != '') {
                    $x = $i - 1;
                    ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                    ExcelUtilities::setBorder($sheet->getCell($cell.$x));
                } else {
                    ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                }
                $cell++;
            }
            $i++;
        }

        //Semaines
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('keyword.week'));
        //
        ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B".$i), $colorOne);
        //Budget
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorOne);
        $sheet->setCellValue('C'.$i, $this->translator->trans('budget_label'));
        //Brut ttc
        ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D".$i), $colorOne);
        $sheet->setCellValue('D'.$i, $this->translator->trans('report.ca.ca_brut_ttc'));
        //Ventes Annexes
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorOne);
        $sheet->setCellValue('E'.$i, $this->translator->trans('report.daily_result.vente_annexe'));

        //DIFF BUDG

        //Discount
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorOne);
        $sheet->setCellValue('F'.$i, $this->translator->trans('report.discount'));

        //BR
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), $colorOne);
        $sheet->setCellValue('G'.$i, $this->translator->trans('report.br'));

        //NET HT
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorOne);
        $sheet->setCellValue('H'.$i, $this->translator->trans('report.ca.ca_net_ht'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), $colorOne);
        $sheet->setCellValue('I'.$i, '%(-1)');
        //ticket
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), $colorOne);
        $sheet->setCellValue('J'.$i, $this->translator->trans('report.sales.hour_by_hour.tickets'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K".$i), $colorOne);
        $sheet->setCellValue('K'.$i, '% (-1)');
        //tm brut
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorOne);
        $sheet->setCellValue('L'.$i, $this->translator->trans('report.daily_result.avg_net_ticket'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M".$i), $colorOne);
        $sheet->setCellValue('M'.$i, '% (-1)');
        //DIFF CAISSE

        ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N".$i), $colorOne);
        $sheet->setCellValue('N'.$i, $this->translator->trans('cashbox_counts_anomalies.report_labels.diff_caisse'));

        ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O".$i), $colorOne);
        $sheet->setCellValue('O'.$i, $this->translator->trans('report.daily_result.chest_error'));

        ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P".$i), $colorOne);
        $sheet->setCellValue('P'.$i, $this->translator->trans('report.daily_result.sold_loss'));

        //ERR COFFRE
        ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q".$i), $colorOne);
        $sheet->setCellValue('Q'.$i, $this->translator->trans('report.daily_result.inventory_loss'));

        //ERR COFFRE
        ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R".$i), $colorOne);
        $sheet->setCellValue('R'.$i, $this->translator->trans('report.daily_result.takeout_percentage'));

        //Border
        $cell = 'A';
        while ($cell != 'S') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }

        $i++;
        //Week content
        foreach ($result['weeks'] as $line) {
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            $sheet->setCellValue('A'.$i, $line['index']);
            ExcelUtilities::setCellAlignment($sheet->getCell('A'.$i), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('A'.$i), $alignmentV);
            ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
            //Budget
            ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
            $sheet->setCellValue('C'.$i, round($line['budget'], 2));
            //Brut ttc
            ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
            $sheet->setCellValue('D'.$i, round($line['caBrutTtc'], 2));

            //Ventes Annexes
            ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
            $sheet->setCellValue('E'.$i, round($line['VA'], 2));

            //Discount
            ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
            $sheet->setCellValue('F'.$i, round($line['pub'], 2));

            //BR
            ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
            $sheet->setCellValue('G'.$i, round($line['br'], 2));

            //NET HT
            ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
            $sheet->setCellValue('H'.$i, round($line['caNetHt'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
            if (is_null($line['caNetPerCentNOne'])) {
                $sheet->setCellValue('I'.$i, '*');
            } else {
                $sheet->setCellValue('I'.$i, round($line['caNetPerCentNOne'], 2));
            }
            //ticket
            ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
            $sheet->setCellValue('J'.$i, round($line['nbrTickets'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
            if (is_null($line['nbrTicketsPerCentNOne'])) {
                $sheet->setCellValue('K'.$i, '*');
            } else {
                $sheet->setCellValue('K'.$i, round($line['nbrTicketsPerCentNOne'], 2));
            }
            //tm brut
            ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
            $sheet->setCellValue('L'.$i, round($line['avgTicket'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
            if (is_null($line['avgTicketPerCentNOne'])) {
                $sheet->setCellValue('M'.$i, '*');
            } else {
                $sheet->setCellValue('M'.$i, round($line['avgTicketPerCentNOne'], 2));
            }
            //DIFF CAISSE
            ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
            $sheet->setCellValue('N'.$i, round($line['cashboxTotalGap'], 2));

            //ERR COFFRE
            ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
            $sheet->setCellValue('O'.$i, round($line['chestError'], 2));
            //CORRECTIONS

            ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
            if ($line['soldLossVal'] == -1) {
                $sheet->setCellValue('P'.$i, '*');
            } else {
                $sheet->setCellValue('P'.$i, round($line['soldLossVal'], 2));
            }
            ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
            if ($line['invLossVal'] == -1) {
                $sheet->setCellValue('Q'.$i, '*');
            } else {
                $sheet->setCellValue('Q'.$i, round($line['invLossVal'], 2));
            }
            // %TakeOut
            ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
            $sheet->setCellValue('R'.$i, round($line['takeOutPercentage'], 2));
            //Border
            $cell = 'A';
            while ($cell != 'S') {
                ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                $cell++;
            }
            $i++;
        }
        //MONTH
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('keyword.month'));
        //
        ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B".$i), $colorOne);
        //Budget
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorOne);
        $sheet->setCellValue('C'.$i, $this->translator->trans('budget_label'));
        //Brut ttc
        ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D".$i), $colorOne);
        $sheet->setCellValue('D'.$i, $this->translator->trans('report.ca.ca_brut_ttc'));

        //Ventes Annexes
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorOne);
        $sheet->setCellValue('E'.$i, $this->translator->trans('report.daily_result.vente_annexe'));


        //Discount
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorOne);
        $sheet->setCellValue('F'.$i, $this->translator->trans('report.discount'));

        //BR
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), $colorOne);
        $sheet->setCellValue('G'.$i, $this->translator->trans('report.br'));

        //NET HT
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorOne);
        $sheet->setCellValue('H'.$i, $this->translator->trans('report.ca.ca_net_ht'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), $colorOne);
        $sheet->setCellValue('I'.$i, '%(-1)');
        //ticket
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), $colorOne);
        $sheet->setCellValue('J'.$i, $this->translator->trans('report.sales.hour_by_hour.tickets'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K".$i), $colorOne);
        $sheet->setCellValue('K'.$i, '% (-1)');
        //tm brut
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorOne);
        $sheet->setCellValue('L'.$i, $this->translator->trans('avg_tm_brut'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M".$i), $colorOne);
        $sheet->setCellValue('M'.$i, '% (-1)');
        //DIFF CAISSE
        ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N".$i), $colorOne);
        $sheet->setCellValue('N'.$i, $this->translator->trans('cashbox_counts_anomalies.report_labels.diff_caisse'));

        //ERR COFFRE
        ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O".$i), $colorOne);
        $sheet->setCellValue('O'.$i, $this->translator->trans('report.daily_result.chest_error'));
        //CORRECTIONS
        ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P".$i), $colorOne);
        $sheet->setCellValue('P'.$i, $this->translator->trans('report.daily_result.sold_loss'));

        ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q".$i), $colorOne);
        $sheet->setCellValue('Q'.$i, $this->translator->trans('report.daily_result.inventory_loss'));

        // % TakeOut
        ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R".$i), $colorOne);
        $sheet->setCellValue('R'.$i, $this->translator->trans('report.daily_result.takeout_percentage'));

        //Border
        $cell = 'A';
        while ($cell != 'S') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }


        $i++;
        //month content

        foreach ($result['months'] as $key => $line) {
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            $sheet->setCellValue('A'.$i, $line['index']);
            ExcelUtilities::setCellAlignment($sheet->getCell('A'.$i), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('A'.$i), $alignmentV);

            ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
            //Budget
            ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
            $sheet->setCellValue('C'.$i, round($line['budget'], 2));
            //Brut ttc
            ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
            $sheet->setCellValue('D'.$i, round($line['caBrutTtc'], 2));

            //Ventes Annexes
            ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
            $sheet->setCellValue('E'.$i, round($line['VA'], 2));

            //Discount
            ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
            $sheet->setCellValue('F'.$i, round($line['pub'], 2));

            //BR
            ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
            $sheet->setCellValue('G'.$i, round($line['br'], 2));

            //NET HT
            ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
            $sheet->setCellValue('H'.$i, round($line['caNetHt'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
            if (is_null($line['caNetPerCentNOne'])) {
                $sheet->setCellValue('I'.$i, '*');
            } else {
                $sheet->setCellValue('I'.$i, round($line['caNetPerCentNOne'], 2));
            }
            //ticket
            ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
            $sheet->setCellValue('J'.$i, round($line['nbrTickets'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
            if (is_null($line['nbrTicketsPerCentNOne'])) {
                $sheet->setCellValue('K'.$i, '*');
            } else {
                $sheet->setCellValue('K'.$i, round($line['nbrTicketsPerCentNOne'], 2));
            }
            //tm brut
            ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
            $sheet->setCellValue('L'.$i, round($line['avgTicket'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
            if (is_null($line['avgTicketPerCentNOne'])) {
                $sheet->setCellValue('M'.$i, '*');
            } else {
                $sheet->setCellValue('M'.$i, round($line['avgTicketPerCentNOne'], 2));
            }
            //DIFF CAISSE
            ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
            $sheet->setCellValue('N'.$i, round($line['cashboxTotalGap'], 2));

            //ERR COFFRE
            ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
            $sheet->setCellValue('O'.$i, round($line['chestError'], 2));
            //CORRECTIONS
            ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
            if ($line['soldLossVal'] == -1) {
                $sheet->setCellValue('P'.$i, '*');
            } else {
                $sheet->setCellValue('P'.$i, round($line['soldLossVal'], 2));
            }

            ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
            if ($line['invLossVal'] == -1) {
            } else {
                $sheet->setCellValue('Q'.$i, round($line['invLossVal'], 2));
            }
            // % TakeOut
            ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
            $sheet->setCellValue('R'.$i, round($line['takeOutPercentage'], 2));

            //Border
            $cell = 'A';
            while ($cell != 'S') {
                ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                $cell++;
            }
            $i++;
        }

        //TOTAL
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('keyword.total'));
        //
        ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B".$i), $colorOne);
        //Budget
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorOne);
        $sheet->setCellValue('C'.$i, $this->translator->trans('budget_label'));
        //Brut ttc
        ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D".$i), $colorOne);
        $sheet->setCellValue('D'.$i, $this->translator->trans('report.ca.ca_brut_ttc'));

        //Vents Annexes
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorOne);
        $sheet->setCellValue('E'.$i, $this->translator->trans('report.daily_result.vente_annexe'));

        //Discount
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorOne);
        $sheet->setCellValue('F'.$i, $this->translator->trans('report.discount'));

        //BR
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), $colorOne);
        $sheet->setCellValue('G'.$i, $this->translator->trans('report.br'));

        //NET HT
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorOne);
        $sheet->setCellValue('H'.$i, $this->translator->trans('report.ca.ca_net_ht'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), $colorOne);
        $sheet->setCellValue('I'.$i, '%(-1)');
        //ticket
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), $colorOne);
        $sheet->setCellValue('J'.$i, $this->translator->trans('report.sales.hour_by_hour.tickets'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K".$i), $colorOne);
        $sheet->setCellValue('K'.$i, '% (-1)');
        //tm brut
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorOne);
        $sheet->setCellValue('L'.$i, $this->translator->trans('avg_tm_brut'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M".$i), $colorOne);
        $sheet->setCellValue('M'.$i, '% (-1)');
        //DIFF CAISSE
        ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N".$i), $colorOne);
        $sheet->setCellValue('N'.$i, $this->translator->trans('cashbox_counts_anomalies.report_labels.diff_caisse'));

        //ERR COFFRE
        ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O".$i), $colorOne);
        $sheet->setCellValue('O'.$i, $this->translator->trans('report.daily_result.chest_error'));
        //CORRECTIONS
        ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P".$i), $colorOne);
        $sheet->setCellValue('P'.$i, $this->translator->trans('report.daily_result.sold_loss'));

        ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q".$i), $colorOne);
        $sheet->setCellValue('Q'.$i, $this->translator->trans('report.daily_result.inventory_loss'));

        // % TakeOut
        ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R".$i), $colorOne);
        $sheet->setCellValue('R'.$i, $this->translator->trans('report.daily_result.takeout_percentage'));

        //Border
        $cell = 'A';
        while ($cell != 'S') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }
        $i++;
        //total content
        $line = $result['total'];
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setCellAlignment($sheet->getCell('A'.$i), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('A'.$i), $alignmentV);
        ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
        //Budget
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        $sheet->setCellValue('C'.$i, round($line['budget'], 2));
        //Brut ttc
        ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
        $sheet->setCellValue('D'.$i, round($line['caBrutTtc'], 2));

        //Ventes Annexes
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        $sheet->setCellValue('E'.$i, round($line['VA'], 2));

        //Discount
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        $sheet->setCellValue('F'.$i, round($line['pub'], 2));

        //BR
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        $sheet->setCellValue('G'.$i, round($line['br'], 2));

        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        $sheet->setCellValue('H'.$i, round($line['caNetHt'], 2));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
        if (is_null($line['caNetPerCentNOne'])) {
            $sheet->setCellValue('I'.$i, '*');
        } else {
            $sheet->setCellValue('I'.$i, round($line['caNetPerCentNOne'], 2));
        }
        //ticket
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        $sheet->setCellValue('J'.$i, round($line['nbrTickets'], 2));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
        if (is_null($line['nbrTicketsPerCentNOne'])) {
            $sheet->setCellValue('K'.$i, '*');
        } else {
            $sheet->setCellValue('K'.$i, round($line['nbrTicketsPerCentNOne'], 2));
        }
        //tm brut
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        $sheet->setCellValue('L'.$i, round($line['avgTicket'], 2));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
        if (is_null($line['avgTicketPerCentNOne'])) {
            $sheet->setCellValue('M'.$i, '*');
        } else {
            $sheet->setCellValue('M'.$i, round($line['avgTicketPerCentNOne'], 2));
        }
        //DIFF CAISSE
        ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
        $sheet->setCellValue('N'.$i, round($line['cashboxTotalGap'], 2));

        //ERR COFFRE
        ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
        $sheet->setCellValue('O'.$i, round($line['chestError'], 2));
        //CORRECTIONS
        ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);

        if ($line['soldLossVal'] == -1) {
            $sheet->setCellValue('P'.$i, '*');
        } else {
            $sheet->setCellValue('P'.$i, round($line['soldLossVal'], 2));
        }

        ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
        if ($line['invLossVal'] == -1) {
            $sheet->setCellValue('Q'.$i, '*');
        } else {
            $sheet->setCellValue('Q'.$i, round($line['invLossVal'], 2));
        }

        // % TakeOut
        ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
        $sheet->setCellValue('R'.$i, round($line['takeOutPercentage'], 2));

        //Border
        $cell = 'A';
        while ($cell != 'S') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }

        foreach(range('A','R') as $columnID) {
            $phpExcelObject->getActiveSheet()->getColumnDimension($columnID)
                ->setAutoSize(true);
        }

        $filename = "Rapport_synthetique_financier_".date('dmY_His').".xls";
        // create the writer
        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }
}
