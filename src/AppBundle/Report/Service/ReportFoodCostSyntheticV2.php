<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/04/2016
 * Time: 18:15
 */

namespace AppBundle\Report\Service;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Report\Entity\SyntheticFoodCostLine;
use AppBundle\Report\Entity\SyntheticFoodCostRapport;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use AppBundle\Merchandise\Service\ProductService;

class ReportFoodCostSyntheticV2 implements ReportFoodCostInterface
{

    /**
     * @var EntityManager
     */
    private $em;

    private $sqlQueriesDir;

    /**
     * @var ProductService
     */
    private $productService;

    /**
     * @var ReportFoodCostService
     */
    private $reportFoodCostService;

    public function __construct(
        EntityManager $entityManager,
        ProductService $productService,
        ReportFoodCostService $reportFoodCostService,
        $sqlQueryDir
    )
    {
        $this->reportFoodCostService = $reportFoodCostService;
        $this->em = $entityManager;
        $this->productService = $productService;
        $this->sqlQueriesDir = $sqlQueryDir;
    }

    public function getSyntheticFoodCost(
        $currentRestaurantId,
        \DateTime $startDate,
        \DateTime $endDate,
        ImportProgression $progression = null,
        $force = 0
    )
    {

        $treadedDays = [];

        echo "START \n";

        $startDateString = $startDate->format('Y-m-d');
        $endDateString = $endDate->format('Y-m-d');
        $output = $this->em->getRepository('Financial:FinancialRevenue')->getFinancialRevenuesBetweenDates(
            $startDateString,
            $endDateString,
            $currentRestaurantId
        );

        $result = [];

        $currentRestaurant = $this->em->getRepository('Merchandise:Restaurant')->find($currentRestaurantId);

        foreach ($output as $r) {
            $tmp = array();
            $filter['beginDate'] = $r->getDate()->format('Y-m-d');
            $filter['endDate'] = $r->getDate()->format('Y-m-d');
            $filter['lastDate'] = $endDateString;
            $filter['currentRestaurantId'] = $currentRestaurantId;
            $soldLoss = $this->em->getRepository('Merchandise:LossLine')->getFiltredLossLineSold($filter, true, true);
            $revenuePrice = $this->reportFoodCostService->getRevenuePriceSold($filter);
            $perte_i_inv = $this->em->getRepository('Merchandise:LossLine')->getFiltredLossLine($filter, true, true);
            $entree = $this->reportFoodCostService->getInValorization($filter)['totalin'];
            $sortie = $this->reportFoodCostService->getOutValorization($filter)['totalout'];

            $tmp['ca_net_ht'] = $r->getNetHT();
            $tmp['ca_brut_ttc'] = $r->getBrutTTC();
            //NEW
            $tmp['ca_br'] = $r->getBr();
            $tmp['br'] = ($tmp['ca_brut_ttc']) ? ($revenuePrice['totalrevenueprice'] / $tmp['ca_brut_ttc'] * $r->getBr()) : 0;
            $tmp['discount'] = $r->getDiscount();


            $tmp['month'] = $r->getDate()->format('m');
            $tmp['week'] = $r->getDate()->format('W');
            $tmp['date'] = $r->getDate();

            $tmp['ventes_pr'] = ($revenuePrice['totalrevenueprice'] == null) ? 0 : $revenuePrice['totalrevenueprice'];

            $tmp['pertes_i_inv'] = ($perte_i_inv == null) ? 0 : $perte_i_inv;
            $tmp['pertes_i_vtes'] = ($soldLoss['lossvalorization'] == null) ? 0 : $soldLoss['lossvalorization'];


            $tmp['entree'] = ($entree == null) ? 0 : $entree;
            $tmp['sortie'] = ($sortie == null) ? 0 : $sortie;
            echo "\n date start= " . $filter['beginDate'] . " date end=" . $filter['endDate'] . "net=" . $r->getNetHT() . "\n";
            $result[] = $tmp;
        }

        $nbreDates = count($result);
        $dateStepPerc = 1 / (($nbreDates) ? $nbreDates : 1);

        echo "NOMBRE DATES $nbreDates \n";

        $today = new \DateTime();
        //Foreach result
        $syntheticFoodCostTotal = $this->em->getRepository("Report:SyntheticFoodCostLine")
            ->findOneBy(
                array(
                    'date' => $startDate,
                    'endDate' => $endDate,
                    'originRestaurant' => $currentRestaurantId,
                )
            );
        if (!$syntheticFoodCostTotal) {

            $res = [];
            $finalStock = $this->productService->getFinalStockValorizationAtDate(array(
                //'beginDate' => $r['date']->format('Y-m-d'),
                'beginDate' => $startDateString,
                'lastDate' => $endDateString
            ), $currentRestaurantId);
           
            ($finalStock) ? $res['stockFinalTotal'] = $finalStock : $res['stockFinalTotal'] = 0;

          $initialStock = $this->productService->getInitialStockValorizationAtDate(
                        array(
                            'beginDate' => $startDateString,
                            'lastDate' =>$endDateString
                            //'lastDate' => $r['date']->format('Y-m-d')
                        ),
                        $currentRestaurantId
                    );
            ($initialStock) ? $res['stockInitialTotal'] = $initialStock : $res['stockInitialTotal'] = 0;

            $line = new SyntheticFoodCostLine();
            $line->setDate($startDate)
                ->setEndDate($endDate)
                ->setData($res)
                ->setOriginRestaurant($currentRestaurant);
            $this->em->persist($line);
            $this->em->flush();

        } else if ($force != 0 && $syntheticFoodCostTotal) {

            $this->em->remove($syntheticFoodCostTotal);
            $this->em->flush();
            $res = [];
            $finalStock = $this->productService->getFinalStockValorizationAtDate(array(
                //'beginDate' => $r['date']->format('Y-m-d'),
                'beginDate' => $startDateString,
                'lastDate' => $endDateString
            ), $currentRestaurantId);
            
            ($finalStock) ? $res['stockFinalTotal'] = $finalStock : $res['stockFinalTotal'] = 0;

            $initialStock = $this->productService->getInitialStockValorizationAtDate(
                        array(
                            'beginDate' => $startDateString,
                            'lastDate' =>$endDateString
                            //'lastDate' => $r['date']->format('Y-m-d')
                        ),
                        $currentRestaurantId
                    );
 
            ($initialStock) ? $res['stockInitialTotal'] = $initialStock : $res['stockInitialTotal'] = 0;

            $line = new SyntheticFoodCostLine();
            $line->setDate($startDate)
                ->setEndDate($endDate)
                ->setData($res)
                ->setOriginRestaurant($currentRestaurant);
            $this->em->persist($line);
            $this->em->flush();
        }
        $firstDayOfLastestMonth = date('Y-m-01',strtotime($endDateString));
        echo 'first day of lasteest month is '.$firstDayOfLastestMonth;
        foreach ($result as $key => $r) {
            $date = $r['date'];
            $treadedDays[] = $date->format('Y-m-d');

            //Test if there's synthetic food cost line safed in the database
            $foodCostLine = $this->em->getRepository("Report:SyntheticFoodCostLine")
                ->findBy(
                    array(
                        'date' => $date,
                        'endDate' => null,
                        'originRestaurant' => $currentRestaurantId,
                    )
                );

          //  if (($force != 0 && $foodCostLine) || $date->format('Y-m-d') === $today->format('Y-m-d')) {
                if (($force != 0 && $foodCostLine && ($date->format('Y-m-d') >= $firstDayOfLastestMonth) ) || $date->format('Y-m-d') === $today->format('Y-m-d')) {
                    foreach ($foodCostLine as $line) {
                        $this->em->remove($line);
                    }

                    $this->em->flush();
                    $foodCostLine = null;
                }

                if (!$foodCostLine) {
                    echo "Processing DATE " . $r['date']->format('Y-m-d') . " \n";

                    $result['final'] = 0;

                    //updated by belsem 29/11/2020
                    $initialStock = $this->productService->getInitialStockValorizationAtDate(
                        array(
                            'beginDate' => $r['date']->format('Y-m-d'),
                            'lastDate' => $r['date']->format('Y-m-d')
                            //'lastDate' => $r['date']->format('Y-m-d')
                        ),
                        $currentRestaurantId
                    );

                    ($initialStock) ? $result['initial'] = $initialStock : $result['initial'] = 0;
                    $finalStock = $this->productService->getFinalStockValorizationAtDate(array(
                        //'beginDate' => $r['date']->format('Y-m-d'),
                        'beginDate' => $r['date']->format('Y-m-d'),
                        'lastDate' => $r['date']->format('Y-m-d')
                    ), $currentRestaurantId);
                    ($finalStock) ? $result['final'] = $finalStock : $result['final'] = 0;

                    //Calculating
                    // - pertes_connues
                    // - fc_mix
                    // - fc_ideal

                    $result[$key]['fc_mix'] = 100 * $result[$key]['ventes_pr'] / $result[$key]['ca_brut_ttc'];
                    $result[$key]['fc_ideal'] = 100 * $result[$key]['ventes_pr'] / $result[$key]['ca_net_ht'];
                    $result[$key]['pertes_connues'] = $result[$key]['pertes_i_inv'] + $result[$key]['pertes_i_vtes'];
                    //$initialStock set initial stock
                    $result[$key]['initialStock'] = $initialStock;
                    //Get stock final set final stock
                    $result[$key]['finalStock'] = $finalStock;
                    //set consomation realle
                    $result[$key]['conso_real'] = $initialStock + $result[$key]['entree'] - $result[$key]['sortie'] - $finalStock;
                    //calculer fc real = consomation real / ca_ht
                    $result[$key]['fc_real'] = 100 * $result[$key]['conso_real'] / floatval($result[$key]['ca_net_ht']);
                    //calculer marge reelle 100 - fc_real
                    $result[$key]['marge_real'] = 100 - $result[$key]['fc_real'];
                    //calculer perte totales =>  fc_ideal - fc_real
                    $result[$key]['pertes_totales'] = $result[$key]['conso_real'] - $result[$key]['ventes_pr'];
                    $result[$key]['pertes_totales'] = $result[$key]['conso_real'] - $result[$key]['ventes_pr'];

                    //calculer pertes inconnues => pertes totales  - pertes connues
                    /*
                    $result[$key]['pertes_inconnues'] = abs(
                        $result[$key]['pertes_totales'] - $result[$key]['pertes_connues']
                    );
                    */
//                //added by belsem
//                // Stock final total pour toute la periode
//               // $result[$key]['final_stock_total'] =$finalStockTotal;


                    $result[$key]['pertes_inconnues'] =
                        $result[$key]['pertes_totales'] - $result[$key]['pertes_connues'];

                    //calculer les pourcentages des pertes
                    $result[$key]['pertes_inv_pourcentage'] = 100 * $result[$key]['pertes_i_inv'] / $result[$key]['ca_net_ht'];
                    $result[$key]['pertes_vtes_pourcentage'] = 100 * $result[$key]['pertes_i_vtes'] / $result[$key]['ca_net_ht'];
                    $result[$key]['pertes_connues_pourcentage'] = 100 * $result[$key]['pertes_connues'] / $result[$key]['ca_net_ht'];
                    $result[$key]['pertes_inconnues_pourcentage'] = 100 * $result[$key]['pertes_inconnues'] / $result[$key]['ca_net_ht'];
                    $result[$key]['pertes_totales_pourcentage'] = $result[$key]['fc_real'] - $result[$key]['fc_ideal'];
                    //calculer fc pertes inventaires
                    $result[$key]['fc_pertes_inv'] = $result[$key]['pertes_i_inv'] / $result[$key]['ca_net_ht'];
                    //calculer fc perte ventes
                    $result[$key]['fc_pertes_vtes'] = $result[$key]['pertes_i_vtes'] / $result[$key]['ca_net_ht'];
                    $result[$key]['fc_theo'] = $result[$key]['fc_ideal'] + (100 * $result[$key]['pertes_connues'] / $result[$key]['ca_net_ht']);
                    $result[$key]['marge_theo'] = 100 - $result[$key]['fc_theo'];
                    $result[$key]['pr_pub'] = ($result[$key]['fc_mix'] * $result[$key]['discount']) / 100;
                    $result[$key]['fc_pub'] = $result[$key]['pr_pub'] / $result[$key]['ca_net_ht'];
                    $result[$key]['pr_br'] = 100 * ($result[$key]['br'] / $result[$key]['ca_net_ht']);
                    $result[$key]['fc_br'] = $result[$key]['pr_br'] / $result[$key]['ca_net_ht'];
                    $result[$key]['discount_pourcentage'] = 100 * $result[$key]['pr_pub'] / $result[$key]['ca_net_ht'];
                    $result[$key]['br_pourcentage'] = 100 * $result[$key]['br'] / $result[$key]['ca_net_ht'];
                    $result[$key]['fc_real_net'] = $result[$key]['fc_real'] - $result[$key]['br_pourcentage'] - $result[$key]['discount_pourcentage'];
                    $result[$key]['marge_brute'] = 100 - $result[$key]['fc_real_net'];
                    $line = new SyntheticFoodCostLine();
                    $line->setDate($date)
                        ->setData($result[$key])
                        ->setOriginRestaurant($currentRestaurant);
                    $this->em->persist($line);
                    $this->em->flush();
                    echo "test 5\n";

                    if ($progression) {
                        $progression->incrementPercentProgression($dateStepPerc * 100);
                        $this->em->flush();
                    }
                } else {
                    //increment progression
                    if ($progression) {
                        $progression->incrementPercentProgression($dateStepPerc * 100);
                        $this->em->flush();
                    }
                }
            }

            if ($progression) {
                $progression->setProgress(100);
                $this->em->flush();
            }

            $periode = $endDate->diff($startDate)->days;

            for ($i = 0; $i <= $periode; $i++) {
                $testDate = Utilities::getDateFromDate($startDate, $i);
                if (!in_array($testDate->format('Y-m-d'), $treadedDays)) {
                    $line = new SyntheticFoodCostLine();
                    $line->setDate($testDate)
                        ->setData(null);
                    $this->em->persist($line);
                    $this->em->flush();
                }
            }
        }

        public function formatResultFoodCostSynthetic(SyntheticFoodCostRapport $rapportTmp)
    {
        $lineFinalTotal = $this->em->getRepository("Report:SyntheticFoodCostLine")->createQueryBuilder("f")
            ->where("f.date = :startDate ")
            ->andWhere("f.endDate = :endDate")
            ->andWhere("f.originRestaurant = :restaurant")
            ->setParameter("restaurant", $rapportTmp->getOriginRestaurant())
            ->setParameter("startDate", $rapportTmp->getStartDate())
            ->setParameter("endDate", $rapportTmp->getEndDate())
            ->getQuery()
            ->getSingleResult();

        if ($lineFinalTotal->getData() != null) {
            $finalInitialStockTotal = json_decode($lineFinalTotal->getData(), true);
        }
        $lines = $this->em->getRepository("Report:SyntheticFoodCostLine")->createQueryBuilder("f")
            ->where("f.date <= :endDate ")
            ->andWhere("f.date >= :startDate and f.endDate is null")
            ->andWhere("f.originRestaurant = :restaurant")
            ->setParameter("restaurant", $rapportTmp->getOriginRestaurant())
            ->setParameter("startDate", $rapportTmp->getStartDate())
            ->setParameter("endDate", $rapportTmp->getEndDate())
            ->orderBy("f.date", "ASC")
            ->getQuery()
            ->getResult();
        $result = [];
        foreach ($lines as $l) {
            if ($l->getData() != null) {
                $result[] = json_decode($l->getData(), true);
            }
        }
        $data['result'] = $result;

        $weeks = [];
        foreach ($result as $key => $value) {
            if (array_key_exists($value['week'], $weeks)) {
                $weeks[$value['week']]++;
            } else {
                $weeks[$value['week']] = 1;
            }
        }
        $data['weeks'] = $weeks;

        $perWeek = [];

        foreach ($result as $r) {
            foreach ($r as $key => $value) {
                if (in_array($key, ['week', 'month', 'date'])) {
                    continue;
                }

                if (!isset($perWeek[$r['week']][$key])) {
                    $perWeek[$r['week']][$key] = 0;
                }
                if ($value !== null) {
                    if ($key == 'finalStock') {
                        $perWeek[$r['week']][$key] = floatval($value);

                    } else {
                        if ($key == 'initialStock') {
                            if ($perWeek[$r['week']]['initialStock'] == null) {
                                $perWeek[$r['week']][$key] = floatval($value);
                            }
                        } else {
                            $perWeek[$r['week']][$key] = $perWeek[$r['week']][$key] + floatval($value);
                        }
                    }
                }


            }


            if (!isset($perWeek[$r['week']]['nbres_lines'])) {
                $perWeek[$r['week']]['nbres_lines'] = 1;
            } else {
                $perWeek[$r['week']]['nbres_lines']++;
            }
        }
        //UPDATED
        $pourcentagesArray = [
            'fc_real',
            'fc_real_net',
            'fc_mix',
            'fc_theo',
            'fc_ideal',
            'fc_pertes_inv',
            'fc_pertes_vtes',
            'pertes_totales_pourcentage',
            'pertes_inconnues_pourcentage',
            'pertes_inv_pourcentage',
            'pertes_vtes_pourcentage',
            'pertes_connues_pourcentage',
            'discount_pourcentage',
            'br_pourcentage',
            'marge_brute',
            'marge_theo',
            'marge_real',
            'pr_pub',
            'br',
            'pertes_inconnues',
        ];

        foreach ($perWeek as $weekKey => $week) {
            foreach ($week as $key => $value) {
                if (in_array($key, $pourcentagesArray)) {
                    $perWeek[$weekKey] = $this->calculatePercent($perWeek[$weekKey], $key, $value);
                }
            }
        }

        $data['perWeek'] = $perWeek;

        $perMonth = [];
        foreach ($result as $r) {
            foreach ($r as $key => $value) {
                if (in_array($key, ['week', 'month', 'date'])) {
                    continue;
                }
                if (!isset($perMonth[$r['month']][$key])) {
                    $perMonth[$r['month']][$key] = 0;
                }
                if ($value !== null) {
                    $perMonth[$r['month']][$key] = $perMonth[$r['month']][$key] + floatval($value);
                }
            }

            if (!isset($perMonth[$r['month']]['nbres_lines'])) {
                $perMonth[$r['month']]['nbres_lines'] = 1;
            } else {
                $perMonth[$r['month']]['nbres_lines']++;
            }
        }
        foreach ($perMonth as $mKey => $month) {
            foreach ($month as $key => $value) {
                if (in_array($key, $pourcentagesArray)) {
                    $perMonth[$mKey] = $this->calculatePercent($perMonth[$mKey], $key, $value);
                }
            }
        }

        $data['perMonth'] = $perMonth;


        $total = [];
        foreach ($result as $r) {

            foreach ($r as $key => $value) {
                if (in_array($key, ['week', 'month', 'date'])) {
                    continue;
                }

                if (!isset($total[$key])) {
                    $total[$key] = 0;
                }

                if ($value !== null) {
                    if ($key == 'finalStock') {
                        $total[$key] =  floatval($finalInitialStockTotal['stockFinalTotal']) ;
                    } else {
                        if ($key == 'initialStock') {
                            if ($total['initialStock'] == null) {
                                $total[$key] = floatval($finalInitialStockTotal['stockInitialTotal']);
                            }
                        } else if ($key == 'conso_real') {
                            $total[$key] = $total['initialStock'] + $total['entree'] - $total['sortie'] - $total['finalStock'];
                        } else if ($key == 'pertes_totales' and array_key_exists('conso_real', $total)) {
                            $total[$key] = $total['conso_real'] - $total['ventes_pr'];
                        } else {
                            $total[$key] = $total[$key] + floatval($value);
                        }

                    }

                }

            }
        }

        foreach ($total as $mKey => $totalLine) {
            if (in_array($mKey, $pourcentagesArray)) {
                $total = $this->calculatePercent($total, $mKey, $totalLine);
            }
        }

        $data['total'] = $total;

        unset($result);
        $data['startDate'] = $rapportTmp->getStartDate();
        $data['endDate'] = $rapportTmp->getEndDate();

        return $data;
    }

        private function calculatePercent(array $tab, $key, $value)
    {

        switch ($key) {
            case 'fc_mix':
                $val = ($tab['ca_brut_ttc'] != 0) ? (100 * $tab['ventes_pr'] / $tab['ca_brut_ttc']) : 0;
                break;
            case 'fc_ideal':
                $val = ($tab['ca_net_ht'] != 0) ? (100 * $tab['ventes_pr'] / $tab['ca_net_ht']) : 0;
                break;
            case 'fc_real':
                $val = (floatval($tab['ca_net_ht']) != 0) ? (100 * $tab['conso_real'] / floatval($tab['ca_net_ht'])) : 0;
                break;
            case 'fc_theo':
                $val = $tab['fc_ideal'] + (($tab['ca_net_ht'] != 0) ? (100 * $tab['pertes_connues'] / $tab['ca_net_ht']) : 0);
                break;
            case 'fc_pertes_inv':
                $val = ($tab['ca_net_ht'] != 0) ? ($tab['pertes_i_inv'] / $tab['ca_net_ht']) : 0;
                break;
            case 'fc_real_net':
                //                $val = $tab['fc_real'] - $tab['pr_br'] - $tab['pr_pub'];
                $val = $tab['fc_real'] - $tab['br_pourcentage'] - $tab['discount_pourcentage'];

                break;
            case 'fc_pertes_vtes':
                $val = ($tab['ca_net_ht'] != 0) ? ($tab['pertes_i_vtes'] / $tab['ca_net_ht']) : 0;
                break;
            case 'pertes_totales_pourcentage':
                $val = $tab['fc_real'] - $tab['fc_ideal'];
                break;
            case 'pertes_inconnues_pourcentage':
                //                $val = $tab['pertes_inconnues'] / $tab['pertes_totales'];
                $val = ($tab['ca_net_ht'] != 0) ? (100 * $tab['pertes_inconnues'] / $tab['ca_net_ht']) : 0;
                break;
            case 'pertes_inv_pourcentage':
                //                $val = $tab['pertes_i_inv'] / $tab['pertes_connues'];
                $val = ($tab['ca_net_ht'] != 0) ? (100 * $tab['pertes_i_inv'] / $tab['ca_net_ht']) : 0;
                break;
            case 'pertes_vtes_pourcentage':
                //                $val = $tab['pertes_i_vtes'] / $tab['pertes_connues'];
                $val = ($tab['ca_net_ht'] != 0) ? (100 * $tab['pertes_i_vtes'] / $tab['ca_net_ht']) : 0;
                break;
            case 'pertes_connues_pourcentage':
                //                $val = $tab['pertes_connues'] / $tab['pertes_totales'];
                $val = ($tab['ca_net_ht'] != 0) ? (100 * $tab['pertes_connues'] / $tab['ca_net_ht']) : 0;
                break;
            case 'discount_pourcentage':
                //                $val = 100 * $tab['discount'] / $tab['ca_net_ht'];
                $val = ($tab['ca_net_ht'] != 0) ? (100 * $tab['pr_pub'] / $tab['ca_net_ht']) : 0;
                break;
            case 'br_pourcentage':
                //                $val = 100 * $tab['br'] / $tab['ca_net_ht'];
                $val = ($tab['ca_net_ht'] != 0) ? (100 * $tab['br'] / $tab['ca_net_ht']) : 0;
                break;
            case 'marge_brute':
                $val = 100 - $tab['fc_real_net'];
                break;
            case 'marge_theo':
                $val = 100 - $tab['fc_theo'];
                break;
            case 'marge_real':
                $val = 100 - $tab['fc_real'];
                break;
            case 'pr_pub':
                $val = $tab['fc_mix'] * $tab['discount'] / 100;
                break;
            //NEW
            case 'br':
                $val = ($tab['ca_brut_ttc'] == 0) ? 0 : $tab['ventes_pr'] / $tab['ca_brut_ttc'] * $tab['ca_br'];
                break;
            //NEW
            case 'pertes_inconnues':
                $val = $tab['pertes_totales'] - $tab['pertes_connues'];
                break;
            default:
                $val = ($tab['nbres_lines'] != 0) ? ($value / $tab['nbres_lines']) : 0;
                break;
        }
        $tab[$key] = $val;

        return $tab;
    }


        public function checkLocked($currentRestaurantId)
    {
        $currentRestaurant = $this->em->getRepository(Restaurant::class)->find($currentRestaurantId);
        $param = $this->em->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => 'synthetic_food_cost',
                'originRestaurant' => $currentRestaurant,
            )
        );
        $now = new \DateTime('now');
        if (!$param || $param == null || $param->getValue() == 0) {
            if (!$param) {
                $param = new Parameter();
                $param->setType('synthetic_food_cost');
                $param->setCreatedAt($now);
                $param->setUpdatedAt($now);
                $param->setOriginRestaurant($currentRestaurant);
                $this->em->persist($param);
                $this->em->flush($param);
            }
        } else {
            if ($param->getValue() == 1) {
                $diffInSeconds = $now->getTimestamp() - $param->getUpdatedAt()->getTimestamp();
                if ($diffInSeconds > 300) {
                    $param->setValue(0);
                    $this->em->flush($param);
                }
            }
        }

        return $param;
    }
    }
