<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/04/2016
 * Time: 18:15
 */

namespace AppBundle\Report\Service;

use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\Transfer;
use AppBundle\Report\Entity\RapportLineTmp;
use AppBundle\Report\Entity\RapportTmp;
use AppBundle\Report\Entity\SyntheticFoodCostLine;
use AppBundle\Report\Entity\SyntheticFoodCostRapport;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use AppBundle\Merchandise\Service\ProductService;

class ReportFoodCostSynthetic implements ReportFoodCostInterface
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

    public function __construct(EntityManager $entityManager, ProductService $productService, $sqlQueryDir)
    {
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
    ) {

        $treadedDays = [];

        echo "START \n";
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $sqlQueryFile = $this->sqlQueriesDir."/new_synthetic_food_cost.sql";

        if (!file_exists($sqlQueryFile)) {
            throw new \Exception("FILE SYNTHETIC FOOD COST DOESN'T EXSIT");
        }

        $sql = file_get_contents($sqlQueryFile);

        $discountLabelType = TicketPayment::DISCOUNT_TYPE;
        $brLabelType = TicketPayment::MEAL_TICKET;
        $lsArticleType = LossSheet::ARTICLE;
        $lsFinalProductType = LossSheet::FINALPRODUCT;

        $startDateString = $startDate->format('Y-m-d');
        $endDateString = $endDate->format('Y-m-d');

        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('startDate', $startDateString);
        $stm->bindParam('endDate', $endDateString);
        $stm->bindParam('discount_label_type', $discountLabelType);
        $stm->bindParam('bon_repas_label_type', $brLabelType);
        $stm->bindParam('loss_sheet_article_type', $lsArticleType);
        $stm->bindParam('loss_sheet_final_product_type', $lsFinalProductType);

        $stm->execute();
        $result = $stm->fetchAll();

        $nbreDates = count($result);
        $dateStepPerc = 1 / (($nbreDates) ? $nbreDates : 1);

        echo "NOMBRE DATES $nbreDates \n";

        //Foreach result
        foreach ($result as $key => $r) {
            $date = date_create_from_format('Y-m-d', $r['date']);
            $treadedDays[] = $date->format('Y-m-d');

            //Test if there's synthetic food cost line safed in the database
            $foodCostLine = $this->em->getRepository("Report:SyntheticFoodCostLine")
                ->findOneBy(
                    array(
                        'date' => $date,
                    )
                );

            if ($force != 0 && $foodCostLine) {
                $this->em->remove($foodCostLine);
                $this->em->flush();
                $foodCostLine = null;
            }

            if (!$foodCostLine) {
                echo "Processing DATE ".$r['date']." \n";
                //Get stock initial productService get STock for date-1
                $date_1 = Utilities::getDateFromDate($date, -1);

                //Récupération de tt les produits actives et en cours de désactivation dans le jour date_1
                $activatedProducts = $this->em
                    ->getRepository("Merchandise:ProductPurchased")
                    ->getActivatedProductsInDay($date);

                $nbreProduct = count($activatedProducts);
                $productStep = $dateStepPerc / (($nbreProduct) ? $nbreProduct : 1);

                $initialStock = 0;
                $finalStock = 0;
                $entree = 0;
                $sortie = 0;
                foreach ($activatedProducts as $p) {
                    echo "  Processing Product ".$p->getName()." \n";
                    //Initital stock by product
                    $initialStockData = $this->productService->getStockForProductInDate($p, $date_1);
                    $initialStockValByProduct = ($initialStockData['stock'] != null) ?
                        floatval($initialStockData['stock']) * $p->getBuyingCost() / $p->getInventoryQty() :
                        0;
                    $initialStock += $initialStockValByProduct;

                    //final stock by product
                    $finalStockData = $this->productService->getStockForProductInDate($p, $date);
                    $finalStockValByProduct = ($finalStockData['stock'] != null) ?
                        floatval($finalStockData['stock']) * $p->getBuyingCost() / $p->getInventoryQty() :
                        0;
                    $finalStock += $finalStockValByProduct;

                    //Entree sortie
                    $consomation = $this->productService->getConsomationFormProduct($p, $date, $date);
                    $entree = $entree
                        + (($consomation['delivered_qty'] != null) ? floatval(
                            $consomation['delivered_qty']
                        ) / $p->getInventoryQty() * $p->getBuyingCost() : 0)
                        + (($consomation['transfer_in'] != null) ? floatval(
                            $consomation['transfer_in']
                        ) / $p->getInventoryQty() * $p->getBuyingCost() : 0);

                    $sortie = $sortie
                        + (($consomation['transfer_out'] != null) ? floatval(
                            $consomation['transfer_out']
                        ) / $p->getInventoryQty() * $p->getBuyingCost() : 0)
                        + (($consomation['retours'] != null) ? floatval($consomation['retours']) / $p->getInventoryQty(
                        ) * $p->getBuyingCost() : 0);

                    //increment progression
                    if ($progression) {
                        $progression->incrementPercentProgression($productStep * 100);
                        $this->em->flush();
                    }
                }

                //$initialStock set initial stock
                $result[$key]['initialStock'] = $initialStock;

                //Get stock final set final stock
                $result[$key]['finalStock'] = $finalStock;

                //Get entree sortie set entree  set sortie
                $result[$key]['entree'] = $entree;
                $result[$key]['sortie'] = $sortie;

                //set consomation realle
                $result[$key]['conso_real'] = $initialStock + $entree - $sortie - $finalStock;

                //calculer fc real = consomation real / ca_ht
                $result[$key]['fc_real'] = 100 * $result[$key]['conso_real'] / floatval($result[$key]['ca_net_ht']);

                //calculer marge reelle 100 - fc_real
                $result[$key]['marge_real'] = 100 - $result[$key]['fc_real'];

                //calculer perte totales =>  fc_ideal - fc_real
                $result[$key]['pertes_totales'] = $result[$key]['fc_real'] - $result[$key]['fc_ideal'];

                //calculer pertes inconnues => pertes totales  - pertes connues
                $result[$key]['pertes_inconnues'] = $result[$key]['pertes_totales'] - $result[$key]['pertes_connues'];

                //calculer les pourcentages des pertes
                $result[$key]['pertes_inv_pourcentage'] = $result[$key]['pertes_i_inv'] / $result[$key]['pertes_totales'];
                $result[$key]['pertes_vtes_pourcentage'] = $result[$key]['pertes_i_vtes'] / $result[$key]['pertes_totales'];
                $result[$key]['pertes_connues_pourcentage'] = $result[$key]['pertes_connues'] / $result[$key]['pertes_totales'];
                $result[$key]['pertes_inconnues_pourcentage'] = $result[$key]['pertes_inconnues'] / $result[$key]['pertes_totales'];


                $result[$key]['pertes_totales_pourcentage'] = 100 * $result[$key]['pertes_totales'] / $result[$key]['ca_net_ht'];

                //calculer fc pertes inventaires
                $result[$key]['fc_pertes_inv'] = $result[$key]['pertes_i_inv'] / $result[$key]['ca_net_ht'];

                //calculer fc perte ventes
                $result[$key]['fc_pertes_vtes'] = $result[$key]['pertes_i_vtes'] / $result[$key]['ca_net_ht'];

                //calculer fc theorique = fc_ideal + fc_perte_inv + fc_perte_vts
                $result[$key]['fc_theo'] = $result[$key]['fc_ideal'] + (100 * $result[$key]['pertes_connues'] / $result[$key]['ca_net_ht']);

                //calculer marge theorique 100 - fc_theorique
                $result[$key]['marge_theo'] = 100 - $result[$key]['fc_theo'];

                //calculer fc_pub = $result[$key]['discount_pourcentage']
                $result[$key]['pr_pub'] = ($result[$key]['fc_mix'] * $result[$key]['discount']) / 100;
                $result[$key]['fc_pub'] = $result[$key]['pr_pub'] / $result[$key]['ca_net_ht'];

                //calculer fc_repas = $result[$key]['br_pourcentage']
                $result[$key]['pr_br'] = ($result[$key]['fc_mix'] * $result[$key]['br']) / 100;
                $result[$key]['fc_br'] = $result[$key]['pr_br'] / $result[$key]['ca_net_ht'];


                //calculer fc_reel_net
                $result[$key]['fc_real_net'] = $result[$key]['fc_real'] - $result[$key]['fc_br'] - $result[$key]['fc_pub'];

                //calculer marge_brute 100 - fc_reel_net
                $result[$key]['marge_brute'] = 100 - $result[$key]['fc_real_net'];

                $line = new SyntheticFoodCostLine();
                $line->setDate($date)
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

        $lines = $this->em->getRepository("Report:SyntheticFoodCostLine")->createQueryBuilder("f")
            ->where("f.date <= :endDate ")
            ->andWhere("f.date >= :startDate")
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
}
