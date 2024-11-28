<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/04/2016
 * Time: 18:15
 */

namespace AppBundle\Supervision\Service\Reports;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\FinancialRevenue;
use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Report\Entity\SyntheticFoodCostLine;
use AppBundle\Report\Entity\SyntheticFoodCostRapport;
use AppBundle\Supervision\Service\ProductService;
use AppBundle\Supervision\Utils\Utilities;
use Doctrine\ORM\EntityManager;

class ReportFoodCostSynthetic
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
     * @var MarginFoodCostService
     */
    private $marginFoodCostService;

    public function __construct(
        EntityManager $entityManager,
        ProductService $productService,
        MarginFoodCostService $marginFoodCostService,
        $sqlQueryDir
    ) {
        $this->em = $entityManager;
        $this->productService = $productService;
        $this->sqlQueriesDir = $sqlQueryDir;
        $this->marginFoodCostService = $marginFoodCostService;
    }

    public function getSyntheticFoodCost(
        \DateTime $startDate,
        \DateTime $endDate,
        Restaurant $restaurant,
        ImportProgression $progression = null,
        $force = 0
    ) {

        $param = $this->em->getRepository(Parameter::class)->findOneBy(
            array(
                'type' => 'synthetic_food_cost',
            )
        );
        $output = $this->em->getRepository(FinancialRevenue::class)->getSupervisionFinancialRevenueBetweenDates(
            $startDate,
            $endDate,
            [$restaurant]
        );
        $result = [];
        $treadedDays = [];
        foreach ($output as $r) {
            $tmp = array();
            $filter['beginDate'] = $r->getDate()->format('Y-m-d');
            $filter['endDate'] = $r->getDate()->format('Y-m-d');
            $filter['restaurants'] = array($restaurant);
            $soldLoss = $this->em->getRepository(LossLine::class)->getSupervisionFiltredLossLineSold($filter, true);

            $filter2['restaurant'] = $restaurant;
            $filter2['beginDate'] = $r->getDate();
            $filter2['endDate'] = $r->getDate();
            $revenuePrice = $this->marginFoodCostService->getRevenuePriceSold($filter2);
            $perte_i_inv = $this->em->getRepository(LossLine::class)->getSupervisionFiltredLossLine($filter, true);

            $entree = $this->marginFoodCostService->getInValorization($filter2)['totalin'];
            $sortie = $this->marginFoodCostService->getOutValorization($filter2)['totalout'];
            $tmp['ca_net_ht'] = $r->getNetHT();
            $tmp['ca_brut_ttc'] = $r->getBrutTTC();
            $tmp['ca_br'] = $r->getBr();
            $tmp['br'] = ($tmp['ca_brut_ttc']) ? ($revenuePrice['totalrevenueprice'] / $tmp['ca_brut_ttc'] * $r->getBr(
            )) : 0;
            $tmp['discount'] = $r->getDiscount();
            $tmp['month'] = $r->getDate()->format('m');
            $tmp['week'] = $r->getDate()->format('W');
            $tmp['date'] = $r->getDate();
            $tmp['ventes_pr'] = ($revenuePrice['totalrevenueprice'] == null) ? 0 : $revenuePrice['totalrevenueprice'];
            $tmp['pertes_i_inv'] = ($perte_i_inv == null) ? 0 : $perte_i_inv;
            $tmp['pertes_i_vtes'] = ($soldLoss['lossvalorization'] == null) ? 0 : $soldLoss['lossvalorization'];
            $tmp['entree'] = ($entree == null) ? 0 : $entree;
            $tmp['sortie'] = ($sortie == null) ? 0 : $sortie;
            echo "\n date start= ".$filter['beginDate']." date end=".$filter['endDate']." net=".$r->getNetHT()."\n";
            $result[] = $tmp;
        }

        $nbreDates = count($result);
        $dateStepPerc = 1 / (($nbreDates) ? $nbreDates : 1);
        echo "NOMBRE DATES $nbreDates \n";
        //Foreach result
        $today = new \DateTime();
        foreach ($result as $key => $r) {
            $date = $r['date'];
            $treadedDays[] = $date->format('Y-m-d');
            //Test if there's synthetic food cost line safed in the database
            $foodCostLine = $this->em->getRepository(SyntheticFoodCostLine::class)
                ->findBy(
                    array(
                        'date' => $date,
                        'originRestaurant' => $restaurant,
                    )
                );

            if (($force != 0 && $foodCostLine) || $date->format('Y-m-d') === $today->format('Y-m-d')) {
                foreach ($foodCostLine as $line) {
                    $this->em->remove($line);
                    $this->em->flush();
                    $foodCostLine = null;
                }
            }
            if (!$foodCostLine) {
                echo "Processing DATE ".$r['date']->format('Y-m-d')." \n";

                $result['final'] = 0;


                $initialStock = $this->productService->getInitialStockValorizationAtDate(
                    $date,
                    $filter['restaurants'][0]
                );
                if (is_null($initialStock)) {
                    $initialStock = 0;
                }
                $finalStock = $this->productService->getFinalStockValorizationAtDate($date, $filter['restaurants'][0]);
                if (is_null($finalStock)) {
                    $finalStock = 0;
                }
                if ($progression) {
                    $progression->incrementPercentProgression($dateStepPerc * 100);
                    $this->em->flush();
                }

                //Calculating
                // - pertes_connues
                // - fc_mix
                // - fc_ideal
                $result[$key]['fc_mix'] = 100 * $result[$key]['ventes_pr'] / $result[$key]['ca_brut_ttc'];
                $result[$key]['fc_ideal'] = 100 * $result[$key]['ventes_pr'] / $result[$key]['ca_net_ht'];
                $result[$key]['pertes_connues'] = $result[$key]['pertes_i_inv'] + $result[$key]['pertes_i_vtes'];
                $result[$key]['initialStock'] = $initialStock;
                $result[$key]['finalStock'] = $finalStock;
                $result[$key]['conso_real'] = $initialStock + $result[$key]['entree'] - $result[$key]['sortie'] - $finalStock;
                $result[$key]['fc_real'] = 100 * $result[$key]['conso_real'] / floatval($result[$key]['ca_net_ht']);
                $result[$key]['marge_real'] = 100 - $result[$key]['fc_real'];
                $result[$key]['pertes_totales'] = $result[$key]['conso_real'] - $result[$key]['ventes_pr'];
                $result[$key]['pertes_inconnues'] = abs(
                    $result[$key]['pertes_totales'] - $result[$key]['pertes_connues']
                );
                $result[$key]['pertes_inv_pourcentage'] = 100 * $result[$key]['pertes_i_inv'] / $result[$key]['ca_net_ht'];
                $result[$key]['pertes_vtes_pourcentage'] = 100 * $result[$key]['pertes_i_vtes'] / $result[$key]['ca_net_ht'];
                $result[$key]['pertes_connues_pourcentage'] = 100 * $result[$key]['pertes_connues'] / $result[$key]['ca_net_ht'];
                $result[$key]['pertes_inconnues_pourcentage'] = 100 * $result[$key]['pertes_inconnues'] / $result[$key]['ca_net_ht'];
                $result[$key]['pertes_totales_pourcentage'] = $result[$key]['fc_real'] - $result[$key]['fc_ideal'];
                $result[$key]['fc_pertes_inv'] = $result[$key]['pertes_i_inv'] / $result[$key]['ca_net_ht'];
                $result[$key]['fc_pertes_vtes'] = $result[$key]['pertes_i_vtes'] / $result[$key]['ca_net_ht'];
                $result[$key]['fc_theo'] = $result[$key]['fc_ideal'] + (100 * $result[$key]['pertes_connues'] / $result[$key]['ca_net_ht']);
                $result[$key]['marge_theo'] = 100 - $result[$key]['fc_theo'];
                $result[$key]['pr_pub'] = ($result[$key]['fc_mix'] * $result[$key]['discount']) / 100;
                $result[$key]['fc_pub'] = $result[$key]['pr_pub'] / $result[$key]['ca_net_ht'];
                $result[$key]['pr_br'] = 100 * ($result[$key]['br'] / $result[$key]['ca_net_ht']);
                $result[$key]['fc_br'] = $result[$key]['pr_br'] / $result[$key]['ca_net_ht'];
                $result[$key]['discount_pourcentage'] = 100 * $result[$key]['pr_pub'] / $result[$key]['ca_net_ht'];
                $result[$key]['br_pourcentage'] = 100 * $result[$key]['br'] / $result[$key]['ca_net_ht'];
                //calculer fc_reel_net
                $result[$key]['fc_real_net'] = $result[$key]['fc_real'] - $result[$key]['br_pourcentage'] - $result[$key]['discount_pourcentage'];

                //calculer marge_brute 100 - fc_reel_net
                $result[$key]['marge_brute'] = 100 - $result[$key]['fc_real_net'];

                $line = new SyntheticFoodCostLine();
                $line->setDate($date)
                    ->setOriginRestaurant($restaurant)
                    ->setData($result[$key]);
                $this->em->persist($line);
                $this->em->flush();
            } else {
                //increment progression
                if ($progression) {
                    $progression->incrementPercentProgression($dateStepPerc * 100);
                    $this->em->flush();
                }
            }
            $now = new \DateTime();
            $param->setUpdatedAt($now);
            $this->em->persist($param);
            $this->em->flush($param);
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

        /**
         * @var SyntheticFoodCostLine[]
         */
        $lines = $this->em->getRepository(SyntheticFoodCostLine::class)->createQueryBuilder("f")
            ->where("f.date <= :endDate ")
            ->andWhere("f.date >= :startDate")
            ->andWhere("f.originRestaurant = :restaurant")
            ->setParameter("startDate", $rapportTmp->getStartDate())
            ->setParameter("endDate", $rapportTmp->getEndDate())
            ->setParameter("restaurant", $rapportTmp->getOriginRestaurant())
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
                    $perWeek[$r['week']][$key] = $perWeek[$r['week']][$key] + floatval($value);
                }
            }
            if (!isset($perWeek[$r['week']]['nbres_lines'])) {
                $perWeek[$r['week']]['nbres_lines'] = 1;
            } else {
                $perWeek[$r['week']]['nbres_lines']++;
            }
        }


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
        ];

        foreach ($perWeek as $weekKey => $week) {
            foreach ($week as $key => $value) {
                if (in_array($key, $pourcentagesArray)) {
                    $perWeek[$weekKey][$key] = $value / $week['nbres_lines'];
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
                    $perMonth[$mKey][$key] = $value / $month['nbres_lines'];
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
                    $total[$key] = $total[$key] + floatval($value);
                }
            }
        }

        $n = count($result);
        foreach ($total as $mKey => $totalLine) {
            if (in_array($mKey, $pourcentagesArray)) {
                $total[$mKey] = $totalLine / $n;
            }
        }

        $data['total'] = $total;

        unset($result);

        return $data;
    }

    public function checkLocked()
    {
        $param = $this->em->getRepository(Parameter::class)->findOneBy(
            array(
                'type' => 'synthetic_food_cost',
                "originRestaurant" => null,
            )
        );
        $now = new \DateTime('now');
        if (!$param || $param == null || $param->getValue() == 0) {
            if (!$param) {
                $param = new Parameter();
                $param->setType('synthetic_food_cost');
                $param->setCreatedAt($now);
                $param->setUpdatedAt($now);
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
