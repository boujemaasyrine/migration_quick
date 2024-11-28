<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/05/2016
 * Time: 17:07
 */

namespace AppBundle\Administration\Service;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\General\Entity\ImportProgression;
use AppBundle\Administration\Entity\Optikitchen\Optikitchen;
use AppBundle\Administration\Entity\Optikitchen\OptikitchenMatrix;
use AppBundle\Administration\Entity\Optikitchen\OptikitchenProduct;
use AppBundle\Administration\Entity\Optikitchen\OptikitchenProductDetails;
use AppBundle\Merchandise\Entity\CaPrev;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Monolog\Handler\Curl\Util;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\Container;

class OptikitchenService
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var CaPrevService
     */
    private $caPrevService;

    private $sqlDir;

    private $tranches;

    private $restaurantService;

    /**
     * @var $container
     */
    private $container;

    /**
     * @var Logger $logger
     */
    private $logger;


    public function __construct(
        EntityManager $entityManager,
        CaPrevService $caPrevService,
        RestaurantService $restaurantService,
        $sqlDir,
        Container $container
    ) {
        $this->em = $entityManager;
        $this->caPrevService = $caPrevService;
        $this->sqlDir = $sqlDir;
        $this->restaurantService = $restaurantService;
        $this->container = $container;
        $this->logger=$this->container->get('logger');
    }

    public function getOpenTime($restaurant = null)
    {
        if (!$restaurant) {
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }
        $paramOpening = $this->em->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => Parameter::RESTAURANT_OPENING_HOUR,
                'originRestaurant' => $restaurant,
            )
        );

        return intval($paramOpening->getValue());
    }

    public function getCloseTime($restaurant = null)
    {
        if (!$restaurant) {
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }
        $paramClosing = $this->em->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => Parameter::RESTAURANT_CLOSING_HOUR,
                'originRestaurant' => $restaurant,
            )
        );

        return intval($paramClosing->getValue());
    }

    public function getTickets(\DateTime $t1, \DateTime $t2, $restaurant = null)
    {

        if (!$restaurant) {
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }

        $tickets = $this->em->getRepository("Financial:Ticket")->createQueryBuilder('t')
            ->where("t.endDate >= :t1")
            ->andWhere("t.endDate <= :t2")
            ->andWhere("t.originRestaurant = :restaurant")
            ->setParameter("restaurant", $restaurant)
            ->setParameter("t1", $t1)
            ->setParameter("t2", $t2);

        return $tickets;
    }

    /**
     * @param ProductPurchased $pp
     * @param \DateTime $t1
     * @param \DateTime $t2
     * @return float
     */
    public function getConsumedQtyForProductPurchased(ProductPurchased $pp, \DateTime $t1, \DateTime $t2, $restaurant)
    {

        $sqlFile = $this->sqlDir."/consomation_pp_between_2_times.sql";
        $sql = file_get_contents($sqlFile);
        $t1Str = $t1->format('Y-m-d H:i:s');
        $t2Str = $t2->format('Y-m-d H:i:s');
        $pID = $pp->getId();

        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('t1', $t1Str);
        $stm->bindParam('t2', $t2Str);
        $stm->bindParam('product_id', $pID);
        $stm->bindParam('origin_restaurant_id', $restaurant->getId());
        $this->logger->addDebug('start executing consomation pp query',["calculatePDetails"]);
        $stm->execute();
        $this->logger->addDebug('finish executing consomation pp query',["calculatePDetails"]);
        $data = $stm->fetch();

        $qty = intval($data['non_transformed']) + intval($data['transformed']);

        echo "Consummed Qty for ".$pp->getId()." $qty \n";

        return $qty;
    }

    /**
     * @param ProductSold $ps
     * @param \DateTime $t1
     * @param \DateTime $t2
     * @return float
     */
    public function getConsumedQtyForProductSold(ProductSold $ps, \DateTime $t1, \DateTime $t2, $restaurant)
    {

        $sqlFile = $this->sqlDir."/consomation_ps_between_2_times.sql";
        $sql = file_get_contents($sqlFile);

        $t1Str = $t1->format('Y-m-d H:i:s');
        $t2Str = $t2->format('Y-m-d H:i:s');
        $plu = $ps->getCodePlu();

        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('t1', $t1Str);
        $stm->bindParam('t2', $t2Str);
        $stm->bindParam('plu', $plu);
        $stm->bindParam('origin_restaurant_id', $restaurant->getId());
        $this->logger->addDebug('start executing consomation ps query',["calculatePDetails"]);
        $stm->execute();
        $this->logger->addDebug('end executing consomation ps query',["calculatePDetails"]);
        $data = $stm->fetch();

        $qty = intval($data['qty']);

        return $qty;
    }

    /**
     * @param \DateTime $t1
     * @param \DateTime $t2
     * @return float
     */
    public function getCaInInterval(\DateTime $t1, \DateTime $t2, $restaurant = null)
    {
        if (!$restaurant) {
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }

        $sql = "SELECT COALESCE(sum(t.totalttc),0) AS ca
                FROM
                  ticket t
                WHERE
                  t.status NOT IN (-1,5) AND t.counted_canceled <> TRUE AND
                  t.enddate <= :t2 AND
                  t.enddate >= :t1 AND t.origin_restaurant_id = :restaurantId";

        $t1Str = $t1->format('Y-m-d H:i:s');
        $t2Str = $t2->format('Y-m-d H:i:s');
        $restaurantId = $restaurant->getId();
        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('t1', $t1Str);
        $stm->bindParam('t2', $t2Str);
        $stm->bindParam('restaurantId', $restaurantId);
        $stm->execute();
        $data = $stm->fetch();

        return floatval($data['ca']);
    }

    public function getCaInIntervalForDays($dates, \DateTime $t1, \DateTime $t2, $restaurant)
    {
        $ca = 0;
        foreach ($dates as $d) {
            $t1S = $this->getTime($d, $t1->format('H'), $t1->format('i'));
            $t2S = $this->getTime($d, $t2->format('H'), $t2->format('i'));
            $ca += $this->getCaInInterval($t1S, $t2S, $restaurant);
        }

        return $ca;
    }

    /**
     * @param \DateTime $t1
     * @param \DateTime $t2
     * @param \DateTime[] $dates
     * @return float
     */
    public function getBudPrevInInterval(\DateTime $date, $dates, \DateTime $t1, \DateTime $t2, $restaurant = null)
    {
        // echo "Get Bud Prev In Interval " . $t1->format('d/m H:is') . "  " . $t2->format('d/m H:is') . " \n";
        $sumCaPart = 0;
        $sumCa = 0;

        if (!$restaurant) {
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }

        foreach ($dates as $d) {
            // echo "Date " . $d->format('d/m/Y') . " \n";
            $ca = $this->em->getRepository("Financial:FinancialRevenue")->findOneBy(
                array(
                    'date' => $d,
                    'originRestaurant' => $restaurant,
                )
            );
            if ($ca) {
                $ca = $ca->getAmount();
            } else {
                $ca = 0;
            }
            // echo "CA " . $ca . " \n";
            $t11 = $this->getTime($d, $t1->format('H'), $t1->format('i'));
            $t22 = $this->getTime($d, $t2->format('H'), $t2->format('i'));
            $caPartiel = $this->getCaInInterval($t11, $t22, $restaurant);

            $sumCaPart += $caPartiel;
            $sumCa += $ca;
        }

        if ($sumCa != 0) {
            $perc = $sumCaPart / $sumCa;
        } else {
            $perc = 0;
        }


        $bud = $this->caPrevService->createIfNotExsit($date, $restaurant);
        // echo "Budget  " . $bud . " \n";

        $budInInter = $bud * $perc;

        return $budInInter;
    }

    /**
     * @param $coef
     * @param $bud
     * @return int
     */
    public function getBinLevel($coef, $bud)
    {
        if(round($coef)==0){
            $coef=0;
        }

        if ($coef == 0) {
            $x = 0;
        } else {
            $x = $bud / $coef;
        }

        echo "x => $x \n";
        try {
            $matrix = $this->em
                ->getRepository("Administration:Optikitchen\\OptikitchenMatrix")
                ->createQueryBuilder('m')
                ->where("m.min <= :x")
                ->andWhere("m.max > :x")
                ->setParameter("x", $x)
                ->getQuery()
                ->setMaxResults(1)
                ->getSingleResult();
        } catch (NoResultException $e) {
            try {
                $matrix = $this->em
                    ->getRepository("Administration:Optikitchen\\OptikitchenMatrix")
                    ->createQueryBuilder('m')
                    ->where("m.max <= :x")
                    ->addOrderBy("m.max", "DESC")
                    ->setParameter("x", $x)
                    ->getQuery()
                    ->setMaxResults(1)
                    ->getSingleResult();
            } catch (NoResultException $e) {
                $matrix = null;
            }
        }
        if ($matrix) {
            echo "BETA =>".$matrix->getAvg()."\n";
            echo "ALPHA =>".$matrix->getValue()."\n";
            echo "Coef =>".$coef."\n";
            $bin = $coef != 0 ? round(($matrix->getAvg() / $coef) * $matrix->getValue()) : 0;
        } else {
            $bin = 0;
        }
        echo "Getting Bin Level coef => $coef  Bud => $bud , bud/coef=> ".$x."  Bin => $bin \n ";

        return $bin;
    }

    /**
     * @param \DateTime $d
     * @return ProductPurchased[]
     */
    public function getActivePurchasedProductInDate(\DateTime $d, $restaurant)
    {
        $pp = $this->em->getRepository("Merchandise:ProductPurchased")->findBy(
            [
                'eligibleForOptikitchen' => true,
                'originRestaurant' => $restaurant,
            ]
        );

        //$pp = $this->em->getRepository("Merchandise:ProductPurchased")->getActivatedProductsInDay($d);
        return $pp;
    }

    /**
     * @param \DateTime $d
     * @return ProductSold[]
     */
    public function getActiveSoldProductInDate(\DateTime $d, $restaurant)
    {
        $ps = $this->em->getRepository("Merchandise:ProductSold")->findBy(
            [
                'eligibleForOptikitchen' => true,
                'originRestaurant' => $restaurant,
            ]
        );

        //$ps = $this->em->getRepository("Merchandise:ProductSold")->getProductSoldWithTicket();
        return $ps;
    }

    /**
     * @param \DateTime $d
     * @return array
     */
    public function getTranchesByDay(\DateTime $d, $restaurant = null)
    {
        if (!$restaurant) {
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }

        $paramOpening = $this->em->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => Parameter::RESTAURANT_OPENING_HOUR,
                'originRestaurant' => $restaurant,
            )
        );

        $paramClsoing = $this->em->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => Parameter::RESTAURANT_CLOSING_HOUR,
                'originRestaurant' => $restaurant,
            )
        );

        return $tranches = [
            [
                'h1' => intval($paramOpening->getValue()),
                'm1' => 0,
                'h2' => 11,
                'm2' => 0,
            ],
            [
                'h1' => 11,
                'm1' => 0,
                'h2' => 14,
                'm2' => 0,
            ],
            [
                'h1' => 14,
                'm1' => 0,
                'h2' => 18,
                'm2' => 0,
            ],
            [
                'h1' => 18,
                'm1' => 0,
                'h2' => 21,
                'm2' => 0,
            ],
            [
                'h1' => 21,
                'm1' => 0,
                'h2' => 23,
                'm2' => 0,
            ],
            [
                'h1' => 23,
                'm1' => 0,
                'h2' => intval($paramClsoing->getValue()),
                'm2' => 0,
            ],
        ];
    }

    /**
     * @param \DateTime $d
     * @return int
     */
    public function getCountAllProduct(\DateTime $d, $restaurant)
    {

        //        $sql = "
        //            SELECT sum(n) as total
        //            FROM
        //              (
        //                SELECT count(*) as n
        //                FROM product_purchased WHERE status <> :inactive
        //                UNION
        //                (
        //                  SELECT count(*) as n
        //                  FROM product_sold
        //                )
        //              ) as sub_query
        //        ";
        //
        //        $stm = $this->em->getConnection()->prepare($sql);
        //        $inactiveLabel = ProductPurchased::INACTIVE ;
        //        $stm->bindParam("inactive",$inactiveLabel);
        //        $stm->execute();
        //        $data = $stm->fetch();

        $n = count($this->getActivePurchasedProductInDate($d, $restaurant)) + count(
                $this->getActiveSoldProductInDate($d, $restaurant)
            );

        return intval($n);
    }

    /**
     * @param \DateTime $d
     * @param $h1
     * @param $m1
     * @return \DateTime
     */
    private function getTime(\DateTime $d, $h1, $m1)
    {
        $t1TS = mktime(
            intval($h1),
            intval($m1),
            0,
            intval($d->format('m')),
            intval($d->format('d')),
            intval($d->format('Y'))
        );
        $t1 = new \DateTime();
        $t1->setTimestamp($t1TS);

        return $t1;
    }

    private function calculatePDetails(OptikitchenProduct &$oProduct, Product $p, $dates, $restaurant, $callback)
    {
        //Calculte coef on a day
        $consomation = 0;
        $caPerDay = 0;
        foreach ($dates as $dd) {
            $this->logger->addDebug("retriving financial revenue",["calculatePDetails"]);
            $financialObj = $this->em->getRepository("Financial:FinancialRevenue")->findOneBy(
                array(
                    'date' => $dd,
                    'originRestaurant' => $restaurant,
                )
            );
            $this->logger->addDebug("End of retriving financial revenue",["calculatePDetails"]);
            if ($financialObj) {
                $caPerDay += $financialObj->getAmount();
            }

            $this->logger->addDebug("getting open time",["calculatePDetails"]);
            $openHour = $this->getOpenTime();
            $this->logger->addDebug("finish getting open time",["calculatePDetails"]);
            $startTime = $this->getTime($dd, $openHour, 0);
            $this->logger->addDebug("getting closing time",["calculatePDetails"]);
            $closeHour = $this->getCloseTime();
            $this->logger->addDebug("finsh getting closing time",["calculatePDetails"]);
            if ($closeHour <= $openHour) {
                $dateClose = Utilities::getDateFromDate($dd, 1);
            } else {
                $dateClose = $dd;
            }
            $endTime = $this->getTime($dateClose, $closeHour, 0);

            $consomation += $this->$callback($p, $startTime, $endTime, $restaurant);
        }

        if ($consomation != 0) {
            $coefByDay = $caPerDay / $consomation;
        } else {
            $coefByDay = 0;
        }

        $oProduct->setCoefByDay($coefByDay);

        foreach ($this->tranches as $t) {
            $consumedQty = 0;
            foreach ($dates as $dd) {
                $t11 = $this->getTime($dd, $t['h1'], $t['m1']);
                $t22 = $this->getTime($dd, $t['h2'], $t['m2']);
                $consumedQty += $this->$callback($p, $t11, $t22, $restaurant);
            }

            if ($consumedQty != 0) {
                $coeff = $t['ca'] / $consumedQty;
            } else {
                $coeff = 0;
            }

            $binQty = $this->getBinLevel($coeff, $t['bud']);

            $pDetails = new OptikitchenProductDetails();
            $pDetails->setConso($consumedQty)
                ->setBud($t['bud'])
                ->setCa($t['ca'])
                ->setCoef($coeff)
                ->setBinQty($binQty)
                ->setOptiProduct($oProduct)
                ->setT1($t['t1'])
                ->setT2($t['t2']);

            $oProduct->addDetail(clone $pDetails);
        }
    }

    public function initTranches(\DateTime $d, $dates, $restaurant)
    {
        $tranches = $this->getTranchesByDay($d, $restaurant);//fixed
        //Calculate CA  & Bud Foreach Tranche
        foreach ($tranches as &$t) {
            $t1 = $this->getTime($d, $t['h1'], $t['m1']);
            if (intval($t['h1']) > intval($t['h2'])) {
                $t2 = $this->getTime(Utilities::getDateFromDate($d, 1), $t['h2'], $t['m2']);
            } else {
                $t2 = $this->getTime($d, $t['h2'], $t['m2']);
            }
            $t['t1'] = $t1;
            $t['t2'] = $t2;
            $t['ca'] = $this->getCaInIntervalForDays($dates, $t1, $t2, $restaurant);
            $t['bud'] = $this->getBudPrevInInterval($d, $dates, $t1, $t2, $restaurant);
            $t['tickets'] = $this->getTickets($t1, $t2, $restaurant);
        }

        $this->tranches = $tranches;
    }

    //Calcul des budgets sur les toutes les 1/4 & les enregistrer dans un tableau car l'intrvall du tmps est variable
    public function initBudgets(Optikitchen &$optikitchen, $dates, $restaurant)
    {
        //Calcul budget sur 1/4
        $openHour = $this->getOpenTime($restaurant);
        $closeHour = $this->getCloseTime($restaurant);
        //Calcul budgets sur les tranches
        $partDays = $optikitchen->getDayParts();
        $budgets = [];
        foreach ($partDays as $partDay) {
            if ($partDay['startH'] >= $openHour) {
                $dateStart = $optikitchen->getDate();
            } else {
                $dateStart = Utilities::getDateFromDate($optikitchen->getDate(), 1);
            }

            if ($partDay['endH'] >= $closeHour) {
                $dateEnd = $optikitchen->getDate();
            } else {
                $dateEnd = Utilities::getDateFromDate($optikitchen->getDate(), 1);
            }

            $t1 = $this->getTime($dateStart, $partDay['startH'], $partDay['startM']);
            $t2 = $this->getTime($dateEnd, $partDay['endH'], $partDay['endM']);

            $budgets[] = $this->getBudPrevInInterval($optikitchen->getDate(), $dates, $t1, $t2, $restaurant);
        }
        $optikitchen->setBudgets($budgets);
    }

    /**
     * @param \DateTime $d
     * @param $dates
     * @param ImportProgression|null $progression
     * @return null|Optikitchen
     */
    public function calculate(\DateTime $d, $dates, $restaurant, ImportProgression $progression = null)
    {
        /*if(!$restaurant) {
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }*/

        $oldOptikitchen = $this->em->getRepository("Administration:Optikitchen\\Optikitchen")
            ->findOneBy(
                array(
                    'date' => $d,
                    'originRestaurant' => $restaurant,
                )
            );

        if ($oldOptikitchen) {
            $this->em->remove($oldOptikitchen);
            $this->em->flush();
        }

        $this->logger->addDebug('start creating caPrev',['optikitchen:service']);
        $caPrev = $this->caPrevService->createIfNotExsit($d, $restaurant);
        $this->logger->addDebug('finish caPrev',['optikitchen:service']);
        $optikitchen = new Optikitchen();
        $optikitchen->setDate($d)
            ->setDate1($dates[0])
            ->setDate2($dates[1])
            ->setDate3($dates[2])
            ->setDate4($dates[3])
            ->setBudPrev($caPrev);

        $paramOpening = $this->em->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => Parameter::RESTAURANT_OPENING_HOUR,
                'originRestaurant' => $restaurant,
            )
        );

        $paramClsoing = $this->em->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => Parameter::RESTAURANT_CLOSING_HOUR,
                'originRestaurant' => $restaurant,
            )
        );

        $meta = [
            'open' => intval($paramOpening->getValue()),
            'close' => intval($paramClsoing->getValue()),
        ];
        $optikitchen->setMeta($meta);
        $optikitchen->setOriginRestaurant($restaurant);
        $this->logger->addDebug('start calling initBudget service',['optikitchen:service']);
        $this->initBudgets($optikitchen, $dates, $restaurant);
        $this->logger->addDebug('finish initBudget',['optikitchen:service']);
        $this->em->persist($optikitchen);
        $this->logger->addDebug('start optikitchen products details calculation',['optikitchen:service']);
        try {
            $optikitchen->setLocked(true)
                ->setSynchronized(false);
            $this->em->flush();
            if ($progression) {
                $progression->setProceedElements(0)
                    ->setTotalElements($this->getCountAllProduct($d, $restaurant));
                $this->em->flush();
            }
            //Getting tranches
            echo " Init Tranches \n";
            $this->initTranches($d, $dates, $restaurant);

            //Product purchased process
            $productsPurchased = $this->getActivePurchasedProductInDate($d, $restaurant);
            $this->logger->addDebug('product purchased actives total number =  '.count($productsPurchased). ' '. 'for  restaurant '. $restaurant->getCode());
            echo "Process Product Purchased \n";
            foreach ($productsPurchased as $p) {
                echo " Product Purchased ".$p->getName()." \n";
                $oProduct = new OptikitchenProduct();
                $oProduct->setOptikitchen($optikitchen)
                    ->setProduct($p)
                    ->setType('purchased');
                $this->logger->addDebug('start calculating details for product purchased '.$p->getId(),['optikitchen:service']);
                $this->calculatePDetails($oProduct, $p, $dates, $restaurant, 'getConsumedQtyForProductPurchased');
                $this->logger->addDebug(' finish calculating details for product purchased '.$p->getId(),['optikitchen:service']);
                if ($progression) {
                    $progression->incrementProgression();
                    $this->em->flush();
                }

                $this->em->persist($oProduct);
                $optikitchen->addProduct($oProduct);
                $this->em->flush();
            }
            unset($productsPurchased);

            //Product sold process
            $productsSold = $this->getActiveSoldProductInDate($d, $restaurant);
            $this->logger->addDebug('product sold actives total number =  '.count($productsSold). ' '. 'for  restaurant '. $restaurant->getCode());
            echo "Process Product Purchased \n";
            foreach ($productsSold as $p) {
                echo " Product Sold ".$p->getName()." \n";
                $oProduct = new OptikitchenProduct();
                $oProduct->setOptikitchen($optikitchen)
                    ->setProduct($p)
                    ->setType('sold');

                $this->logger->addDebug(' start calculating details for product sold '.$p->getId(),['optikitchen:service']);
                $this->calculatePDetails($oProduct, $p, $dates, $restaurant, 'getConsumedQtyForProductSold');
                $this->logger->addDebug(' finish calculating details for product sold '.$p->getId(),['optikitchen:service']);
                if ($progression) {
                    $progression->incrementProgression();
                    $this->em->flush();
                }

                $this->em->persist($oProduct);
                $optikitchen->addProduct($oProduct);
                $this->em->flush();
            }
            unset($productsSold);

            if ($progression) {
                $progression->setEndDateTime(new \DateTime('now'))
                    ->setProgress(100);
                $progression->setStatus('finish');
                $this->em->flush();
            }
        } catch (\Exception $e) {
            $optikitchen->setLocked(false);
            $this->em->flush();
        }
        $optikitchen->setLocked(false);
        $this->em->flush();
        $this->logger->addDebug('finish optikitchen products details calculation',['optikitchen:service']);
        return $optikitchen;
    }

    /**
     * @param \DateTime $d
     * @param null $progress
     * @return Optikitchen|null
     */
    public function launchAutomatic(\DateTime $d, $restaurant, $progress = null)
    {
        $oldOptikitchen = $this->em->getRepository(Optikitchen::class)
            ->findOneBy(
                array(
                    'date' => $d,
                    'originRestaurant' => $restaurant,
                )
            );

        if ($oldOptikitchen) {
            $dates = [
                $oldOptikitchen->getDate1(),
                $oldOptikitchen->getDate2(),
                $oldOptikitchen->getDate3(),
                $oldOptikitchen->getDate4(),
            ];
        } else {
            $dates = $this->getDefaultDates($d, $restaurant);
        }

        $this->logger->addDebug('start calling the calculate service',['optikitchen:service']);
        $optikitchen = $this->calculate($d, $dates, $restaurant, $progress);
        $this->logger->addDebug('end of calculate service execution',['optikitchen:service']);

        return $optikitchen;
    }

    /**
     * @param \DateTime $d
     * @return \DateTime[]
     */
    public function getDefaultDates(\DateTime $d, $restaurant)
    {
        $this->caPrevService->createIfNotExsit($d, $restaurant);

        $caPrev = $this->em->getRepository("Merchandise:CaPrev")->findOneBy(
            array(
                'date' => $d,
                'originRestaurant' => $restaurant,
            )
        );

        $dates = [
            $caPrev->getDate1(),
            $caPrev->getDate2(),
            $caPrev->getDate3(),
            $caPrev->getDate4(),
        ];

        return $dates;
    }

    public function resetEligibiliteForAllProducts($type = null)//fixed
    {
        $currentRestaurantId = $this->restaurantService->getCurrentRestaurant()->getId();
        if ($type == null) {
            $sql = "UPDATE product SET eligible_for_optikitchen = FALSE WHERE origin_restaurant_id= :restaurantId ; ";
        } elseif ($type == 'purchased') {
            $sql = "UPDATE product SET eligible_for_optikitchen = FALSE WHERE product_discr = 'purchased' AND origin_restaurant_id = :restaurantId ; ";
        } else {
            $sql = "UPDATE product SET eligible_for_optikitchen = FALSE WHERE product_discr = 'sold' AND origin_restaurant_id = :restaurantId ; ";
        }

        try {
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('restaurantId', $currentRestaurantId);
            $stm->execute();
        } catch (\Exception $e) {
        }
    }

    public function generateFilename(Optikitchen $o, $restaurant = null)
    {
        if (!$restaurant) {
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }

        if ($restaurant->isLux()) {
            $filename = "LUD".$restaurant->getShortCode()."_".$o->getDate()->format('Ymd')."_Odyssee.xml";
        } else {
            $filename = "BED".$restaurant->getShortCode()."_".$o->getDate()->format('Ymd')."_Odyssee.xml";
        }

        return $filename;
    }

    /**
     * @param Optikitchen $o
     * @return Optikitchen
     */
    public function sendToOptikitchen(Optikitchen $o, $restaurant)
    {

        $logger = $this->container->get('logger');
        //Send To Optikitchen
        $ps = $this->em->getRepository("Administration:Optikitchen\\OptikitchenProduct")
            ->findBy(
                array(
                    'optikitchen' => $o,
                    'type' => 'sold',
                )
            );

        $pp = $this->em->getRepository("Administration:Optikitchen\\OptikitchenProduct")
            ->findBy(
                array(
                    'optikitchen' => $o,
                    'type' => 'purchased',
                )
            );

        $matrix = $this->em->getRepository("Administration:Optikitchen\\OptikitchenMatrix")->findBy(
            [],
            ['level' => 'asc']
        );
        $xml = $this->container->get('templating')->render(
            "@Administration/Optikitchen/XML/optikitchen_main.xml.twig",
            [
                'optikitchen' => $o,
                'sold_products' => $ps,
                'purchased_products' => $pp,
                'matrix' => $matrix,
            ]
        );

        $filename = $this->generateFilename($o, $restaurant);

        //Generating XML for download
        $filePath = $this->container->getParameter('kernel.root_dir')."/../web/uploads/".$filename;
        file_put_contents($filePath, $xml);
        $optikitchenDir = $this->em->getRepository(Parameter::class)->findOneBy(
            array('type' => Parameter::OPTIKITCHEN_PATH, 'originRestaurant' => $restaurant)
        );
        //Generating XML in the shared dir
        if ($optikitchenDir) {
            /**
             * @var Parameter $optikitchenDir
             */
            if (is_dir($optikitchenDir->getValue())) {
                if (is_writable($optikitchenDir->getValue())) {
                    $filePath2 = $optikitchenDir->getValue()."/".$filename;
                    file_put_contents($filePath2, $xml);
                    echo $this->container->get('translator')->trans('optikitchen.coef_sended_with_success');
                    $logger->info(
                        $this->container->get('translator')->trans('optikitchen.coef_sended_with_success'),
                        ['Optikitchen:sendToOptikitchen']
                    );
                } else {
                    echo $this->container->get('translator')->trans(
                        'optikitchen_path_not_writable',
                        ['%1%' => $optikitchenDir->getValue()]
                    );
                    $logger->info(
                        $this->container->get('translator')->trans(
                            'optikitchen_path_not_writable',
                            ['%1%' => $optikitchenDir->getValue()]
                        ),
                        ['Optikitchen:sendToOptikitchen']
                    );
                }
            } else {

                $logger->info('optikitchen path= '.$optikitchenDir->getValue(), ['Optikitchen:sendToOptikitchen']);
                echo $this->container->get('translator')->trans(
                    'optikitchen_path_not_exist',
                    ['%1%' => $optikitchenDir->getValue()]
                );
                $logger->info(
                    $this->container->get('translator')->trans(
                        'optikitchen_path_not_exist',
                        ['%1%' => $optikitchenDir->getValue()]
                    ),
                    ['Optikitchen:sendToOptikitchen']
                );
            }
        } else {
            echo $this->container->get('translator')->trans('optikitchen_path_is_not_set');
            $logger->info(
                $this->container->get('translator')->trans('optikitchen_path_is_not_set'),
                ['Optikitchen:sendToOptikitchen']
            );
        }


        $o->setLastSynchoDate(new \DateTime('now'));
        $this->em->flush();

        return $o;
    }

    public function getCaPrevInDates($dates)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $result = array();
        $minuteInterval = 15;
        $openingHourObject = $this->em->getRepository('Administration:Parameter')->findOneBy(
            [
                'type' => Parameter::RESTAURANT_OPENING_HOUR,
                'originRestaurant' => $currentRestaurant
            ]
        );
        $closingHourObject = $this->em->getRepository('Administration:Parameter')->findOneBy(
            [
                'type' => Parameter::RESTAURANT_CLOSING_HOUR,
                'originRestaurant' => $currentRestaurant
            ]
        );
        $openingHour = $openingHourObject ? $openingHourObject->getValue() : Parameter::RESTAURANT_OPENING_HOUR_DEFAULT;
        $closingHour = $closingHourObject ? $closingHourObject->getValue() : Parameter::RESTAURANT_CLOSING_HOUR_DEFAULT;

        foreach ($dates as $date) {
            $dateAsDate = \DateTime::createFromFormat('Y-m-j', $date);
            /**
             * @var CaPrev $budgetPrev
             */
            $budgetPrev = $this->caPrevService->createIfNotExsit($dateAsDate, $currentRestaurant);
            $caPrev = $this->em->getRepository("Merchandise:CaPrev")->findOneBy(
                array(
                    'date' => $dateAsDate,
                    'originRestaurant' => $currentRestaurant,
                )
            );
            $referenceDates = [
                $caPrev->getDate1(),
                $caPrev->getDate2(),
                $caPrev->getDate3(),
                $caPrev->getDate4(),
            ];
            $firstQuart = new \DateTime($dateAsDate->format('Y-m-j '.$openingHour.':00:00'));
            $lastQuart = $closingHour > 12 ? new \DateTime($dateAsDate->format('Y-m-j '.$closingHour.':00:00'))
                : new \DateTime(Utilities::getDateFromDate($dateAsDate, 1)->format('Y-m-j '.$closingHour.':00:00'));

            $beginInterval = clone $firstQuart;
            $endInterval = clone $beginInterval;
            $strTime = strtotime('15 minutes', strtotime($endInterval->format('Y-m-j H:i:s')));
            $endInterval->setTimeStamp($strTime);
            while ($beginInterval != $lastQuart) {
                $result[$date][$beginInterval->format('H:i')] = $this->getBudPrevInInterval(
                    $dateAsDate,
                    $referenceDates,
                    $beginInterval,
                    $endInterval
                );
                $beginInterval = clone $endInterval;
                $strTime = strtotime('15 minutes', strtotime($endInterval->format('Y-m-j H:i:s')));
                $endInterval->setTimestamp($strTime);
            }
        }

        return $result;
    }
}
