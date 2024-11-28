<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 27/05/2016
 * Time: 12:14
 */

namespace AppBundle\Supervision\Service\Reports;

use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Entity\Expense;
use AppBundle\Financial\Entity\FinancialRevenue;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Merchandise\Entity\CaPrev;
use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Supervision\Utils\DateUtilities;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Liuggio\ExcelBundle\Factory;

class DailyResultsService
{

    private $em;
    private $translator;
    private $phpExcel;

    public function __construct(EntityManager $em, Translator $translator, Factory $factory)
    {
        $this->em = $em;
        $this->translator = $translator;
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
        $result = array();
        $days = DateUtilities::getDays($filter['startDate'], $filter['endDate']);
        $daysComp = DateUtilities::getDays($filter['compareStartDate'], $filter['compareEndDate']);
        $j = 0;
        $k = 0;
        $m = 0;
        $n = 0;

        for ($i = 0; $i < count($days); $i++) {
            if ($i == 0) {
                $this->intialiseData($result['weeks'][$j], $days[$i]->format('W'));
                $this->intialiseData($result['months'][$m], $days[$i]->format('m'));
                $this->intialiseData($result['total'], 1);
            } elseif ($days[$i] != $filter['endDate']) {
                if ($days[$i]->format('N') == 1) {
                    $k = 0;
                    $j = $j + 1;
                    $this->intialiseData($result['weeks'][$j], $days[$i]->format('W'));
                }
                if ($days[$i]->format('d') == '01') {
                    $n = 0;
                    $m = $m + 1;
                    $this->intialiseData($result['months'][$m], $days[$i]->format('m'));
                }
            }
            $k++;
            $n++;
            $filter = [
                'beginDate' => $days[$i]->format('Y-m-d'),
                'endDate' => $days[$i]->format('Y-m-d'),
                'restaurants' => $filter['restaurants'],
            ];
            $filterComp = [
                'beginDate' => $daysComp[$i]->format('Y-m-d'),
                'endDate' => $daysComp[$i]->format('Y-m-d'),
                'restaurants' => $filter['restaurants'],
            ];

            //Days
            $budget = $this->em->getRepository(CaPrev::class)->getSupervisionAmountByDate(
                $days[$i],
                $filter['restaurants']
            );
            $fRev = $this->em->getRepository(FinancialRevenue::class)->getSupervisionByDateAndRestaurants(
                $days[$i],
                $filter['restaurants']
            );

            $fRevComp = $this->em->getRepository(FinancialRevenue::class)->getSupervisionByDateAndRestaurants(
                $daysComp[$i],
                $filter['restaurants']
            );
            $caNetHtComp = $fRevComp['netHT'];
            $nbrTicketsComp = $this->em->getRepository(Ticket::class)->getSupervisionTotalPerDay(
                $daysComp[$i],
                $filter['restaurants'],
                true
            );
            $avgTicketComp = ($nbrTicketsComp > 0) ? $caNetHtComp / $nbrTicketsComp :
                0;

            $positiveChestError = $this->em->getRepository(
                RecipeTicket::class
            )->getSupervisionTotalChestErrorRecipeTicketByDate($days[$i], $filter['restaurants']);
            $negativeChestError = $this->em->getRepository(Expense::class)->getSupervisionTotalChestErrorExpenseByDate(
                $days[$i],
                $filter['restaurants']
            );
            $positiveChestError = $positiveChestError ? $positiveChestError : 0;
            $negativeChestError = $negativeChestError ? $negativeChestError : 0;

            $positiveCashBoxError = $this->em->getRepository(
                RecipeTicket::class
            )->getSupervisionTotalCashBoxErrorRecipeTicketByDate($days[$i], $filter['restaurants']);
            $negativeCashBoxError = $this->em->getRepository(
                Expense::class
            )->getSupervisionTotalCashBoxErrorExpenseByDate($days[$i], $filter['restaurants']);
            $positiveCashBoxError = $positiveCashBoxError ? $positiveCashBoxError : 0;
            $negativeCashBoxError = $negativeCashBoxError ? $negativeCashBoxError : 0;

            $result['days'][$i]['day'] = $days[$i];
            $result['days'][$i]['dayComp'] = $daysComp[$i];
            $result['days'][$i]['isComp'] = $this->em->getRepository(AdministrativeClosing::class)->isComparable(
                $daysComp[$i]
            );
            $result['days'][$i]['isCompThisDate'] = $this->em->getRepository(
                AdministrativeClosing::class
            )->isComparable($days[$i]);
            $result['days'][$i]['budget'] = $budget ? $budget : 0;
            $result['days'][$i]['pub'] = $fRev['discount'];
            $result['days'][$i]['caBrutTtc'] = $fRev['brutTTC'];
            $result['days'][$i]['br'] = $fRev['br'];
            $result['days'][$i]['caBrutHt'] = $fRev['brutHT'];
            $result['days'][$i]['caNetHt'] = $fRev['netHT'];
            $result['days'][$i]['caNetNOne'] = $caNetHtComp;
            $result['days'][$i]['caNetPerCentNOne'] = ($caNetHtComp != 0) ? ($result['days'][$i]['caNetHt'] - $caNetHtComp) / $caNetHtComp * 100 : null;
            $result['days'][$i]['chestError'] = abs($positiveChestError) - abs($negativeChestError);
            $result['days'][$i]['cashboxTotalGap'] = abs($positiveCashBoxError) - abs($negativeCashBoxError);
            $result['days'][$i]['nbrTickets'] = $this->em->getRepository(Ticket::class)->getSupervisionTotalPerDay(
                $days[$i],
                $filter['restaurants'],
                true
            );
            $result['days'][$i]['nbrTicketsNOne'] = $nbrTicketsComp;
            $result['days'][$i]['nbrTicketsPerCentNOne'] = ($nbrTicketsComp > 0) ? ($result['days'][$i]['nbrTickets'] - $nbrTicketsComp) / $nbrTicketsComp * 100 : null;
            $result['days'][$i]['avgTicket'] = ($result['days'][$i]['nbrTickets'] > 0) ? $result['days'][$i]['caNetHt'] / $result['days'][$i]['nbrTickets'] :
                0;
            $result['days'][$i]['avgTicketPerCentNOne'] = ($avgTicketComp > 0) ? ($result['days'][$i]['avgTicket'] - $avgTicketComp) / $avgTicketComp * 100 : null;
            $result['days'][$i]['comment'] = $this->em->getRepository(AdministrativeClosing::class)->getComment(
                $days[$i]
            );
            $invLossVal = $this->em->getRepository(LossLine::class)->getSupervisionFiltredLossLine($filter, true);
            $invLossVal = $invLossVal ? $invLossVal : 0;
            $result['days'][$i]['invLossVal'] = ($result['days'][$i]['caNetHt'] != 0) ? $invLossVal / $result['days'][$i]['caNetHt'] * 100 : -1;
            $arraySoldLossVal = $this->em->getRepository(LossLine::class)->getSupervisionFiltredLossLineSold(
                $filter,
                true
            );
            $soldLossVal = $arraySoldLossVal['lossvalorization'];
            $soldLossVal = $soldLossVal ? $soldLossVal : 0;
            $result['days'][$i]['soldLossVal'] = ($result['days'][$i]['caNetHt'] != 0) ? $soldLossVal / $result['days'][$i]['caNetHt'] * 100 : -1;

            //Weeks

            $result['weeks'][$j]['budget'] += $result['days'][$i]['budget'];
            $result['weeks'][$j]['caBrutTtc'] += $result['days'][$i]['caBrutTtc'];
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
            $result['weeks'][$j]['nbrTicketsPerCentNOne'] += $result['days'][$i]['nbrTicketsPerCentNOne'];
            $result['weeks'][$j]['avgTicketPerCentNOne'] += $result['days'][$i]['avgTicketPerCentNOne'];
            if ($days[$i]->format('N') == 7 or !isset($days[$i + 1])) {
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
            $result['months'][$m]['nbrTicketsPerCentNOne'] += $result['days'][$i]['nbrTicketsPerCentNOne'];
            $result['months'][$m]['avgTicketPerCentNOne'] += $result['days'][$i]['avgTicketPerCentNOne'];

            if (!isset($days[$i + 1]) or (isset($days[$i + 1]) and $days[$i]->format('m') != $days[$i + 1]->format(
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
            'caNetNOne' => 0,
            'caNetHt' => 0,
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
                $this->translator->trans('dayShort.'.$line['day']->format('D')).' '.$line['day']->format('d/m/Y'),
                $this->translator->trans('dayShort.'.$line['dayComp']->format('D')).' '.$line['dayComp']->format(
                    'd/m/Y'
                ),
                number_format($line['budget'], 2, '.', ''),
                number_format($line['caBrutTtc'], 2, '.', ''),
                number_format($line['pub'], 2, '.', ''),
                number_format($line['br'], 2, '.', ''),
                number_format($line['caNetHt'], 2, '.', ''),
                $line['caNetPerCentNOne'] ? number_format($line['caNetPerCentNOne'], 2, '.', '') : '*',
                $line['nbrTickets'],
                $line['nbrTicketsPerCentNOne'] ? number_format($line['nbrTicketsPerCentNOne'], 2, '.', '') : '*',
                number_format($line['avgTicket'], 2, ',', ' '),
                $line['avgTicketPerCentNOne'] ? number_format($line['avgTicketPerCentNOne'], 2, '.', '') : '*',
                number_format($line['cashboxTotalGap'], 2, ',', ' '),
                number_format($line['chestError'], 2, ',', ' '),
                ($line['soldLossVal'] != -1) ? number_format($line['soldLossVal'], 2, '.', '') : '*',
                ($line['invLossVal'] != -1) ? number_format($line['invLossVal'], 2, '.', '') : '*',

            ];
        }

        $serializedResult[] = [];
        $serializedResult[] = [
            $this->translator->trans('keywords.week', [], 'supervision'),
            '',
            $this->translator->trans('budget_label', [], 'supervision'),
            $this->translator->trans('report.ca.ca_brut_ttc', [], 'supervision'),
            $this->translator->trans('report.discount', [], 'supervision'),
            $this->translator->trans('report.br', [], 'supervision'),
            $this->translator->trans('report.ca.ca_net_ht', [], 'supervision'),
            '% (-1)',
            $this->translator->trans('report.sales.hour_by_hour.tickets', [], 'supervision'),
            '% (-1)',
            $this->translator->trans('report.daily_result.avg_net_ticket', [], 'supervision'),
            '% (-1)',
            $this->translator->trans('report.daily_result.diff_caisse', [], 'supervision'),
            $this->translator->trans('report.daily_result.chest_error', [], 'supervision'),
            $this->translator->trans('report.daily_result.sold_loss', [], 'supervision'),
            $this->translator->trans('report.daily_result.inventory_loss', [], 'supervision'),
        ];

        foreach ($result['weeks'] as $line) {
            $serializedResult[] = [
                $line['index'],
                '',
                number_format($line['budget'], 2, '.', ''),
                number_format($line['caBrutTtc'], 2, '.', ''),
                number_format($line['pub'], 2, '.', ''),
                number_format($line['br'], 2, '.', ''),
                number_format($line['caNetHt'], 2, '.', ' '),
                $line['caNetPerCentNOne'] ? number_format($line['caNetPerCentNOne'], 2, '.', '') : '*',
                $line['nbrTickets'],
                $line['nbrTicketsPerCentNOne'] ? number_format($line['nbrTicketsPerCentNOne'], 2, '.', '') : '*',
                number_format($line['avgTicket'], 2, ',', ''),
                $line['avgTicketPerCentNOne'] ? number_format($line['avgTicketPerCentNOne'], 2, '.', '') : '*',
                number_format($line['cashboxTotalGap'], 2, '.', ''),
                number_format($line['chestError'], 2, '.', ''),
                ($line['soldLossVal'] != -1) ? number_format($line['soldLossVal'], 2, '.', '') : '*',
                ($line['invLossVal'] != -1) ? number_format($line['invLossVal'], 2, '.', '') : '*',

            ];
        }
        $serializedResult[] = [];
        $serializedResult[] = [
            $this->translator->trans('keywords.month', [], 'supervision'),
            '',
            $this->translator->trans('budget_label', [], 'supervision'),
            $this->translator->trans('report.ca.ca_brut_ttc', [], 'supervision'),
            $this->translator->trans('report.discount', [], 'supervision'),
            $this->translator->trans('report.br', [], 'supervision'),
            $this->translator->trans('report.ca.ca_net_ht', [], 'supervision'),
            '% (-1)',
            $this->translator->trans('report.sales.hour_by_hour.tickets', [], 'supervision'),
            '% (-1)',
            $this->translator->trans('report.daily_result.avg_net_ticket', [], 'supervision'),
            '% (-1)',
            $this->translator->trans('report.daily_result.diff_caisse', [], 'supervision'),
            $this->translator->trans('report.daily_result.chest_error', [], 'supervision'),
            $this->translator->trans('report.daily_result.sold_loss', [], 'supervision'),
            $this->translator->trans('report.daily_result.inventory_loss', [], 'supervision'),
        ];

        foreach ($result['months'] as $line) {
            $serializedResult[] = [
                $line['index'],
                '',
                number_format($line['budget'], 2, '.', ''),
                number_format($line['caBrutTtc'], 2, '.', ''),
                number_format($line['pub'], 2, '.', ''),
                number_format($line['br'], 2, '.', ''),
                number_format($line['caNetHt'], 2, '.', ''),
                $line['caNetPerCentNOne'] ? number_format($line['caNetPerCentNOne'], 2, '.', '') : '*',
                $line['nbrTickets'],
                $line['nbrTicketsPerCentNOne'] ? number_format($line['nbrTicketsPerCentNOne'], 2, '.', '') : '*',
                number_format($line['avgTicket'], 2, '.', ' '),
                $line['avgTicketPerCentNOne'] ? number_format($line['avgTicketPerCentNOne'], 2, '.', '') : '*',
                number_format($line['cashboxTotalGap'], 2, '.', ''),
                number_format($line['chestError'], 2, '.', ''),
                ($line['soldLossVal'] != -1) ? number_format($line['soldLossVal'], 2, '.', '') : '*',
                ($line['invLossVal'] != -1) ? number_format($line['invLossVal'], 2, '.', '') : '*',

            ];
        }

        $serializedResult[] = [];
        $serializedResult[] = [
            $this->translator->trans('keywords.total', [], 'supervision'),
            '',
            $this->translator->trans('budget_label', [], 'supervision'),
            $this->translator->trans('report.ca.ca_brut_ttc', [], 'supervision'),
            $this->translator->trans('report.discount', [], 'supervision'),
            $this->translator->trans('report.br', [], 'supervision'),
            $this->translator->trans('report.ca.ca_net_ht', [], 'supervision'),
            '% (-1)',
            $this->translator->trans('report.sales.hour_by_hour.tickets', [], 'supervision'),
            '% (-1)',
            $this->translator->trans('report.daily_result.avg_net_ticket', [], 'supervision'),
            '% (-1)',
            $this->translator->trans('report.daily_result.diff_caisse', [], 'supervision'),
            $this->translator->trans('report.daily_result.chest_error', [], 'supervision'),
            $this->translator->trans('report.daily_result.sold_loss', [], 'supervision'),
            $this->translator->trans('report.daily_result.inventory_loss', [], 'supervision'),
        ];
        $serializedResult[] = [
            '',
            '',
            number_format($result['total']['budget'], 2, '.', ''),
            number_format($result['total']['caBrutTtc'], 2, '.', ''),
            number_format($result['total']['pub'], 2, '.', ''),
            number_format($result['total']['br'], 2, '.', ''),
            number_format($result['total']['caNetHt'], 2, '.', ''),
            $result['total']['caNetPerCentNOne'] ? number_format(
                $result['total']['caNetPerCentNOne'],
                2,
                '.',
                ''
            ) : '*',
            $result['total']['nbrTickets'],
            $result['total']['nbrTicketsPerCentNOne'] ? number_format(
                $result['total']['nbrTicketsPerCentNOne'],
                2,
                '.',
                ''
            ) : '*',
            number_format($result['total']['avgTicket'], 2, '.', ''),
            $result['total']['avgTicketPerCentNOne'] ? number_format(
                $result['total']['avgTicketPerCentNOne'],
                2,
                '.',
                ''
            ) : '*',
            number_format($result['total']['cashboxTotalGap'], 2, '.', ''),
            number_format($result['total']['chestError'], 2, '.', ''),
            ($result['total']['soldLossVal'] != -1) ? number_format($result['total']['soldLossVal'], 2, '.', '') : '*',
            ($result['total']['invLossVal'] != -1) ? number_format($result['total']['invLossVal'], 2, '.', '') : '*',

        ];


        return $serializedResult;
    }

    public function generateExcelFile($result, $filter)
    {
        $colorOne = "ECECEC";
        $colorTwo = "E5CFAB";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('report.daily_result.title', [], 'supervision'), 0, 30));

        $sheet->mergeCells("B3:K6");
        $content = $this->translator->trans('report.daily_result.title', [], 'supervision');
        $sheet->setCellValue('B3', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("B3"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B3"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 3), 22, true);

        //FILTER ZONE

        //Periode
        $sheet->mergeCells("A8:D8");
        ExcelUtilities::setFont($sheet->getCell('A8'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A8"), $colorTwo);
        $sheet->setCellValue('A8', $this->translator->trans('report.period', [], 'supervision').":");
        ExcelUtilities::setCellAlignment($sheet->getCell("A8"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A8"), $alignmentV);

        // START DATE
        $sheet->mergeCells("A9:B9");
        ExcelUtilities::setFont($sheet->getCell('A9'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A9"), $colorOne);
        $sheet->setCellValue('A9', $this->translator->trans('keywords.from', [], 'supervision').":");
        $sheet->mergeCells("C9:D9");
        ExcelUtilities::setFont($sheet->getCell('C9'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C9"), $colorOne);
        $sheet->setCellValue('C9', $filter['startDate']->format('Y-m-d'));


        // END DATE
        $sheet->mergeCells("A10:B10");
        ExcelUtilities::setFont($sheet->getCell('A10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A10"), $colorOne);
        $sheet->setCellValue('A10', $this->translator->trans('keywords.to', [], 'supervision').":");
        $sheet->mergeCells("C10:D10");
        ExcelUtilities::setFont($sheet->getCell('C10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C10"), $colorOne);
        $sheet->setCellValue('C10', $filter['endDate']->format('Y-m-d'));

        //comparabilité
        $sheet->mergeCells("F8:I8");
        ExcelUtilities::setFont($sheet->getCell('F8'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F8"), $colorTwo);
        $sheet->setCellValue('F8', $this->translator->trans('report.period_to_compare', [], 'supervision').":");
        ExcelUtilities::setCellAlignment($sheet->getCell("F8"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("F8"), $alignmentV);


        // START DATE
        $sheet->mergeCells("F9:G9");
        ExcelUtilities::setFont($sheet->getCell('F9'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F9"), $colorOne);
        $sheet->setCellValue('F9', $this->translator->trans('keywords.from', [], 'supervision').":");
        $sheet->mergeCells("H9:I9");
        ExcelUtilities::setFont($sheet->getCell('H9'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H9"), $colorOne);
        $sheet->setCellValue('H9', $filter['compareStartDate']->format('Y-m-d'));


        // END DATE
        $sheet->mergeCells("F10:G10");
        ExcelUtilities::setFont($sheet->getCell('F10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F10"), $colorOne);
        $sheet->setCellValue('F10', $this->translator->trans('keywords.to', [], 'supervision').":");
        $sheet->mergeCells("H10:I10");
        ExcelUtilities::setFont($sheet->getCell('H10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H10"), $colorOne);
        $sheet->setCellValue('H10', $filter['compareEndDate']->format('Y-m-d'));


        //Restaurants
        $i = 12;

        $restCell = 'C';
        $restCount = 1;
        foreach ($filter['restaurants'] as $restaurant) {
            $nextCell = $restCell;
            $nextCell++;
            $sheet->mergeCells($restCell.$i.':'.$nextCell.$i);
            $sheet->setCellValue($restCell.$i, $restaurant->getName());
            ExcelUtilities::setBackgroundColor($sheet->getCell($restCell.$i), $colorOne);
            $restCount++;
            if ($restCount > 5) {
                $restCount = 1;
                $restCell = 'C';
                $i++;
            } else {
                $restCell = $nextCell;
                $restCell++;
            }
        }
        $sheet->mergeCells("A12:B".$i);
        $sheet->setCellValue("A12", "Restaurants");
        ExcelUtilities::setFont($sheet->getCell('A12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A12"), $colorTwo);
        ExcelUtilities::setCellAlignment($sheet->getCell("A12"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A12"), $alignmentV);
        if ($nextCell != 'M') {
            while ($nextCell != 'M') {
                ExcelUtilities::setBackgroundColor($sheet->getCell($nextCell.$i), $colorOne);
                $nextCell++;
            }
        }
        $i += 2;
        //Content
        //Date
        $sheet->mergeCells('A'.$i.':B'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('keywords.date', [], 'supervision'));
        //Date comparable
        $sheet->mergeCells('C'.$i.':D'.$i);
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorOne);
        $sheet->setCellValue('C'.$i, $this->translator->trans('keywords.date_comp', [], 'supervision'));
        //Budget
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorOne);
        $sheet->setCellValue('E'.$i, $this->translator->trans('budget_label', [], 'supervision'));
        //Brut ttc
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorOne);
        $sheet->setCellValue('F'.$i, $this->translator->trans('report.ca.ca_brut_ttc', [], 'supervision'));

        //Discount
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), $colorOne);
        $sheet->setCellValue('G'.$i, $this->translator->trans('report.discount', [], 'supervision'));

        //BR
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorOne);
        $sheet->setCellValue('H'.$i, $this->translator->trans('report.br', [], 'supervision'));

        //NET HT
        ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), $colorOne);
        $sheet->setCellValue('I'.$i, $this->translator->trans('report.ca.ca_net_ht', [], 'supervision'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), $colorOne);
        $sheet->setCellValue('J'.$i, '%(-1)');
        //ticket
        ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K".$i), $colorOne);
        $sheet->setCellValue('K'.$i, $this->translator->trans('report.sales.hour_by_hour.tickets', [], 'supervision'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorOne);
        $sheet->setCellValue('L'.$i, '% (-1)');
        //tm brut
        ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M".$i), $colorOne);
        $sheet->setCellValue('M'.$i, $this->translator->trans('report.daily_result.avg_net_ticket', [], 'supervision'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N".$i), $colorOne);
        $sheet->setCellValue('N'.$i, '% (-1)');
        //DIFF CAISSE
        ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O".$i), $colorOne);
        $sheet->setCellValue('O'.$i, $this->translator->trans('report.daily_result.diff_caisse', [], 'supervision'));

        //ERR COFFRE
        ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P".$i), $colorOne);
        $sheet->setCellValue('P'.$i, $this->translator->trans('report.daily_result.chest_error', [], 'supervision'));
        //CORRECTIONS
        ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q".$i), $colorOne);
        $sheet->setCellValue('Q'.$i, $this->translator->trans('report.daily_result.sold_loss', [], 'supervision'));

        ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R".$i), $colorOne);
        $sheet->setCellValue('R'.$i, $this->translator->trans('report.daily_result.inventory_loss', [], 'supervision'));

        //Border
        $cell = 'A';
        while ($cell != 'S') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }

        //Content
        $i++;
        foreach ($result['days'] as $line) {
            if (!is_null($line['comment']) && $line['comment'] != '') {
                $k = $i + 1;
            } else {
                $k = $i;
            }
            $sheet->mergeCells('A'.$i.':B'.$k);
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            if ($line['isCompThisDate'] === false) {
                $sheet->setCellValue(
                    'A'.$i,
                    $this->translator->trans('dayShort.'.$line['day']->format('D')).' '.$line['day']->format(
                        'd/m/Y'
                    ).' (!)'
                );
            } else {
                $sheet->setCellValue(
                    'A'.$i,
                    $this->translator->trans('dayShort.'.$line['day']->format('D')).' '.$line['day']->format('d/m/Y')
                );
            }
            ExcelUtilities::setCellAlignment($sheet->getCell('A'.$i), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('A'.$i), $alignmentV);

            //Date comparable
            $sheet->mergeCells('C'.$i.':D'.$i);
            ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
            $sheet->setCellValue(
                'C'.$i,
                $this->translator->trans('dayShort.'.$line['dayComp']->format('D')).' '.$line['dayComp']->format(
                    'd/m/Y'
                )
            );

            //Budget
            ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
            $sheet->setCellValue('E'.$i, round($line['budget'], 2));
            //Brut ttc
            ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
            $sheet->setCellValue('F'.$i, round($line['caBrutTtc'], 2));

            //Discount
            ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
            $sheet->setCellValue('G'.$i, round($line['pub'], 2));

            //BR
            ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
            $sheet->setCellValue('H'.$i, round($line['br'], 2));

            //NET HT
            ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
            $sheet->setCellValue('I'.$i, round($line['caNetHt'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
            if (is_null($line['caNetPerCentNOne'])) {
                $sheet->setCellValue('J'.$i, '*');
            } else {
                $sheet->setCellValue('J'.$i, round($line['caNetPerCentNOne'], 2));
            }
            //ticket
            ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
            $sheet->setCellValue('K'.$i, round($line['nbrTickets'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
            if (is_null($line['nbrTicketsPerCentNOne'])) {
                $sheet->setCellValue('L'.$i, '*');
            } else {
                $sheet->setCellValue('L'.$i, round($line['nbrTicketsPerCentNOne'], 2));
            }
            //tm brut
            ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
            $sheet->setCellValue('M'.$i, round($line['avgTicket'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
            if (is_null($line['avgTicketPerCentNOne'])) {
                $sheet->setCellValue('N'.$i, '*');
            } else {
                $sheet->setCellValue('N'.$i, round($line['avgTicketPerCentNOne'], 2));
            }
            //DIFF CAISSE
            ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
            $sheet->setCellValue('O'.$i, round($line['cashboxTotalGap'], 2));

            //ERR COFFRE
            ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
            $sheet->setCellValue('P'.$i, round($line['chestError'], 2));
            //CORRECTIONS
            ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
            if ($line['soldLossVal'] == -1) {
                $sheet->setCellValue('Q'.$i, '*');
            } else {
                $sheet->setCellValue('Q'.$i, round($line['soldLossVal'], 2));
            }
            ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
            if ($line['invLossVal'] == -1) {
                $sheet->setCellValue('R'.$i, '*');
            } else {
                $sheet->setCellValue('R'.$i, round($line['invLossVal'], 2));
            }
            if (!is_null($line['comment']) && $line['comment'] != '') {
                $i++;
                $sheet->mergeCells('C'.$i.':R'.$i);
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
        $sheet->mergeCells('A'.$i.':B'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('keywords.week', [], 'supervision'));
        //
        $sheet->mergeCells('C'.$i.':D'.$i);
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorOne);
        //Budget
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorOne);
        $sheet->setCellValue('E'.$i, $this->translator->trans('budget_label', [], 'supervision'));
        //Brut ttc
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorOne);
        $sheet->setCellValue('F'.$i, $this->translator->trans('report.ca.ca_brut_ttc', [], 'supervision'));
        //DIFF BUDG

        //Discount
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), $colorOne);
        $sheet->setCellValue('G'.$i, $this->translator->trans('report.discount', [], 'supervision'));

        //BR
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorOne);
        $sheet->setCellValue('H'.$i, $this->translator->trans('report.br', [], 'supervision'));

        //NET HT
        ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), $colorOne);
        $sheet->setCellValue('I'.$i, $this->translator->trans('report.ca.ca_net_ht', [], 'supervision'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), $colorOne);
        $sheet->setCellValue('J'.$i, '%(-1)');
        //ticket
        ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K".$i), $colorOne);
        $sheet->setCellValue('K'.$i, $this->translator->trans('report.sales.hour_by_hour.tickets', [], 'supervision'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorOne);
        $sheet->setCellValue('L'.$i, '% (-1)');
        //tm brut
        ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M".$i), $colorOne);
        $sheet->setCellValue('M'.$i, $this->translator->trans('report.daily_result.avg_net_ticket', [], 'supervision'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N".$i), $colorOne);
        $sheet->setCellValue('N'.$i, '% (-1)');
        //DIFF CAISSE

        ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O".$i), $colorOne);
        $sheet->setCellValue('O'.$i, $this->translator->trans('report.daily_result.diff_caisse', [], 'supervision'));

        ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P".$i), $colorOne);
        $sheet->setCellValue('P'.$i, $this->translator->trans('report.daily_result.chest_error', [], 'supervision'));

        ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q".$i), $colorOne);
        $sheet->setCellValue('Q'.$i, $this->translator->trans('report.daily_result.inventory_loss', [], 'supervision'));

        //ERR COFFRE
        ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R".$i), $colorOne);
        $sheet->setCellValue('R'.$i, $this->translator->trans('report.daily_result.sold_loss', [], 'supervision'));

        //Border
        $cell = 'A';
        while ($cell != 'S') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }

        $i++;
        //Week content
        foreach ($result['weeks'] as $line) {
            $sheet->mergeCells('A'.$i.':B'.$i);
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            $sheet->setCellValue('A'.$i, $line['index']);
            ExcelUtilities::setCellAlignment($sheet->getCell('A'.$i), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('A'.$i), $alignmentV);
            //
            $sheet->mergeCells('C'.$i.':D'.$i);
            ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
            //Budget
            ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
            $sheet->setCellValue('E'.$i, round($line['budget'], 2));
            //Brut ttc
            ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
            $sheet->setCellValue('F'.$i, round($line['caBrutTtc'], 2));

            //Discount
            ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
            $sheet->setCellValue('G'.$i, round($line['pub'], 2));

            //BR
            ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
            $sheet->setCellValue('H'.$i, round($line['br'], 2));

            //NET HT
            ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
            $sheet->setCellValue('I'.$i, round($line['caNetHt'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
            if (is_null($line['caNetPerCentNOne'])) {
                $sheet->setCellValue('J'.$i, '*');
            } else {
                $sheet->setCellValue('J'.$i, round($line['caNetPerCentNOne'], 2));
            }
            //ticket
            ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
            $sheet->setCellValue('K'.$i, round($line['nbrTickets'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
            if (is_null($line['nbrTicketsPerCentNOne'])) {
                $sheet->setCellValue('L'.$i, '*');
            } else {
                $sheet->setCellValue('L'.$i, round($line['nbrTicketsPerCentNOne'], 2));
            }
            //tm brut
            ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
            $sheet->setCellValue('M'.$i, round($line['avgTicket'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
            if (is_null($line['avgTicketPerCentNOne'])) {
                $sheet->setCellValue('N'.$i, '*');
            } else {
                $sheet->setCellValue('N'.$i, round($line['avgTicketPerCentNOne'], 2));
            }
            //DIFF CAISSE
            ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
            $sheet->setCellValue('O'.$i, round($line['cashboxTotalGap'], 2));

            //ERR COFFRE
            ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
            $sheet->setCellValue('P'.$i, round($line['chestError'], 2));
            //CORRECTIONS

            ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
            if ($line['soldLossVal'] == -1) {
                $sheet->setCellValue('Q'.$i, '*');
            } else {
                $sheet->setCellValue('Q'.$i, round($line['soldLossVal'], 2));
            }
            ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
            if ($line['invLossVal'] == -1) {
                $sheet->setCellValue('R'.$i, '*');
            } else {
                $sheet->setCellValue('R'.$i, round($line['invLossVal'], 2));
            }
            //Border
            $cell = 'A';
            while ($cell != 'S') {
                ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                $cell++;
            }
            $i++;
        }
        //MONTH
        $sheet->mergeCells('A'.$i.':B'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('keywords.month', [], 'supervision'));
        //
        $sheet->mergeCells('C'.$i.':D'.$i);
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorOne);
        //Budget
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorOne);
        $sheet->setCellValue('E'.$i, $this->translator->trans('budget_label', [], 'supervision'));
        //Brut ttc
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorOne);
        $sheet->setCellValue('F'.$i, $this->translator->trans('report.ca.ca_brut_ttc', [], 'supervision'));

        //Discount
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), $colorOne);
        $sheet->setCellValue('G'.$i, $this->translator->trans('report.discount', [], 'supervision'));

        //BR
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorOne);
        $sheet->setCellValue('H'.$i, $this->translator->trans('report.br', [], 'supervision'));

        //NET HT
        ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), $colorOne);
        $sheet->setCellValue('I'.$i, $this->translator->trans('report.ca.ca_net_ht', [], 'supervision'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), $colorOne);
        $sheet->setCellValue('J'.$i, '%(-1)');
        //ticket
        ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K".$i), $colorOne);
        $sheet->setCellValue('K'.$i, $this->translator->trans('report.sales.hour_by_hour.tickets', [], 'supervision'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorOne);
        $sheet->setCellValue('L'.$i, '% (-1)');
        //tm brut
        ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M".$i), $colorOne);
        $sheet->setCellValue('M'.$i, $this->translator->trans('avg_tm_brut', [], 'supervision'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N".$i), $colorOne);
        $sheet->setCellValue('N'.$i, '% (-1)');
        //DIFF CAISSE
        ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O".$i), $colorOne);
        $sheet->setCellValue('O'.$i, $this->translator->trans('report.daily_result.diff_caisse', [], 'supervision'));

        //ERR COFFRE
        ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P".$i), $colorOne);
        $sheet->setCellValue('P'.$i, $this->translator->trans('report.daily_result.chest_error', [], 'supervision'));
        //CORRECTIONS
        ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q".$i), $colorOne);
        $sheet->setCellValue('Q'.$i, $this->translator->trans('report.daily_result.inventory_loss', [], 'supervision'));

        ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R".$i), $colorOne);
        $sheet->setCellValue('R'.$i, $this->translator->trans('report.daily_result.sold_loss', [], 'supervision'));

        //Border
        $cell = 'A';
        while ($cell != 'S') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }


        $i++;
        //month content

        foreach ($result['months'] as $key => $line) {
            $sheet->mergeCells('A'.$i.':B'.$i);
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            $sheet->setCellValue('A'.$i, $line['index']);
            ExcelUtilities::setCellAlignment($sheet->getCell('A'.$i), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('A'.$i), $alignmentV);


            $sheet->mergeCells('C'.$i.':D'.$i);
            ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
            //Budget
            ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
            $sheet->setCellValue('E'.$i, round($line['budget'], 2));
            //Brut ttc
            ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
            $sheet->setCellValue('F'.$i, round($line['caBrutTtc'], 2));

            //Discount
            ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
            $sheet->setCellValue('G'.$i, round($line['pub'], 2));

            //BR
            ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
            $sheet->setCellValue('H'.$i, round($line['br'], 2));

            //NET HT
            ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
            $sheet->setCellValue('I'.$i, round($line['caNetHt'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
            if (is_null($line['caNetPerCentNOne'])) {
                $sheet->setCellValue('J'.$i, '*');
            } else {
                $sheet->setCellValue('J'.$i, round($line['caNetPerCentNOne'], 2));
            }
            //ticket
            ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
            $sheet->setCellValue('K'.$i, round($line['nbrTickets'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
            if (is_null($line['nbrTicketsPerCentNOne'])) {
                $sheet->setCellValue('L'.$i, '*');
            } else {
                $sheet->setCellValue('L'.$i, round($line['nbrTicketsPerCentNOne'], 2));
            }
            //tm brut
            ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
            $sheet->setCellValue('M'.$i, round($line['avgTicket'], 2));
            //% (-1)
            ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
            if (is_null($line['avgTicketPerCentNOne'])) {
                $sheet->setCellValue('N'.$i, '*');
            } else {
                $sheet->setCellValue('N'.$i, round($line['avgTicketPerCentNOne'], 2));
            }
            //DIFF CAISSE
            ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
            $sheet->setCellValue('O'.$i, round($line['cashboxTotalGap'], 2));

            //ERR COFFRE
            ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
            $sheet->setCellValue('P'.$i, round($line['chestError'], 2));
            //CORRECTIONS
            ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
            if ($line['soldLossVal'] == -1) {
                $sheet->setCellValue('Q'.$i, '*');
            } else {
                $sheet->setCellValue('Q'.$i, round($line['soldLossVal'], 2));
            }

            ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
            if ($line['invLossVal'] == -1) {
            } else {
                $sheet->setCellValue('R'.$i, round($line['invLossVal'], 2));
            }
            //Border
            $cell = 'A';
            while ($cell != 'S') {
                ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                $cell++;
            }
            $i++;
        }

        //TOTAL
        $sheet->mergeCells('A'.$i.':B'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('keywords.total', [], 'supervision'));
        //
        $sheet->mergeCells('C'.$i.':D'.$i);
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorOne);
        //Budget
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorOne);
        $sheet->setCellValue('E'.$i, $this->translator->trans('budget_label', [], 'supervision'));
        //Brut ttc
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorOne);
        $sheet->setCellValue('F'.$i, $this->translator->trans('report.ca.ca_brut_ttc', [], 'supervision'));

        //Discount
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), $colorOne);
        $sheet->setCellValue('G'.$i, $this->translator->trans('report.discount', [], 'supervision'));

        //BR
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorOne);
        $sheet->setCellValue('H'.$i, $this->translator->trans('report.br', [], 'supervision'));

        //NET HT
        ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), $colorOne);
        $sheet->setCellValue('I'.$i, $this->translator->trans('report.ca.ca_net_ht', [], 'supervision'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), $colorOne);
        $sheet->setCellValue('J'.$i, '%(-1)');
        //ticket
        ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K".$i), $colorOne);
        $sheet->setCellValue('K'.$i, $this->translator->trans('report.sales.hour_by_hour.tickets', [], 'supervision'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorOne);
        $sheet->setCellValue('L'.$i, '% (-1)');
        //tm brut
        ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M".$i), $colorOne);
        $sheet->setCellValue('M'.$i, $this->translator->trans('avg_tm_brut', [], 'supervision'));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N".$i), $colorOne);
        $sheet->setCellValue('N'.$i, '% (-1)');
        //DIFF CAISSE
        ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O".$i), $colorOne);
        $sheet->setCellValue('O'.$i, $this->translator->trans('report.daily_result.diff_caisse', [], 'supervision'));

        //ERR COFFRE
        ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P".$i), $colorOne);
        $sheet->setCellValue('P'.$i, $this->translator->trans('report.daily_result.chest_error', [], 'supervision'));
        //CORRECTIONS
        ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q".$i), $colorOne);
        $sheet->setCellValue('Q'.$i, $this->translator->trans('report.daily_result.inventory_loss', [], 'supervision'));

        ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R".$i), $colorOne);
        $sheet->setCellValue('R'.$i, $this->translator->trans('report.daily_result.sold_loss', [], 'supervision'));

        //Border
        $cell = 'A';
        while ($cell != 'S') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }
        $i++;
        //total content
        $line = $result['total'];
        $sheet->mergeCells('A'.$i.':B'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setCellAlignment($sheet->getCell('A'.$i), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('A'.$i), $alignmentV);
        //
        $sheet->mergeCells('C'.$i.':D'.$i);
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        //Budget
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        $sheet->setCellValue('E'.$i, round($line['budget'], 2));
        //Brut ttc
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        $sheet->setCellValue('F'.$i, round($line['caBrutTtc'], 2));

        //Discount
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        $sheet->setCellValue('G'.$i, round($line['pub'], 2));

        //BR
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        $sheet->setCellValue('H'.$i, round($line['br'], 2));

        ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
        $sheet->setCellValue('I'.$i, round($line['caNetHt'], 2));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        if (is_null($line['caNetPerCentNOne'])) {
            $sheet->setCellValue('J'.$i, '*');
        } else {
            $sheet->setCellValue('J'.$i, round($line['caNetPerCentNOne'], 2));
        }
        //ticket
        ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
        $sheet->setCellValue('K'.$i, round($line['nbrTickets'], 2));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        if (is_null($line['nbrTicketsPerCentNOne'])) {
            $sheet->setCellValue('L'.$i, '*');
        } else {
            $sheet->setCellValue('L'.$i, round($line['nbrTicketsPerCentNOne'], 2));
        }
        //tm brut
        ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
        $sheet->setCellValue('M'.$i, round($line['avgTicket'], 2));
        //% (-1)
        ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
        if (is_null($line['avgTicketPerCentNOne'])) {
            $sheet->setCellValue('N'.$i, '*');
        } else {
            $sheet->setCellValue('N'.$i, round($line['avgTicketPerCentNOne'], 2));
        }
        //DIFF CAISSE
        ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
        $sheet->setCellValue('O'.$i, round($line['cashboxTotalGap'], 2));

        //ERR COFFRE
        ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
        $sheet->setCellValue('P'.$i, round($line['chestError'], 2));
        //CORRECTIONS
        ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);

        if ($line['soldLossVal'] == -1) {
            $sheet->setCellValue('Q'.$i, '*');
        } else {
            $sheet->setCellValue('Q'.$i, round($line['soldLossVal'], 2));
        }

        ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
        if ($line['invLossVal'] == -1) {
            $sheet->setCellValue('R'.$i, '*');
        } else {
            $sheet->setCellValue('R'.$i, round($line['invLossVal'], 2));
        }

        //Border
        $cell = 'A';
        while ($cell != 'S') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
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
