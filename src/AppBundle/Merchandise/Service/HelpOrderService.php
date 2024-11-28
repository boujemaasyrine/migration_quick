<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/03/2016
 * Time: 12:05
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\Administration\Service\CaPrevService;
use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\OrderHelpMask;
use AppBundle\Merchandise\Entity\OrderHelpMaskProduct;
use AppBundle\Merchandise\Entity\OrderHelpProducts;
use AppBundle\Merchandise\Entity\OrderHelpSupplier;
use AppBundle\Merchandise\Entity\OrderHelpTmp;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\Bundle\DoctrineCacheBundle\DependencyInjection\Definition\PredisDefinition;
use Doctrine\ORM\EntityManager;
use AppBundle\Merchandise\Service\OrderService;

class HelpOrderService
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var CaPrevService
     */
    private $caPrevService;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var
     */
    private $productService;

    private $restaurantService;

    public function __construct(
        EntityManager $entityManager,
        CaPrevService $caPrev,
        OrderService $orderService,
        ProductService $productService,
        RestaurantService $restaurantService
    ) {
        $this->em = $entityManager;
        $this->caPrevService = $caPrev;
        $this->orderService = $orderService;
        $this->productService = $productService;
        $this->restaurantService = $restaurantService;
    }

    public function init(OrderHelpTmp $tmp, ImportProgression $progression)
    {

        $tmp->setLocked(true);
        $this->em->flush();

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $base = $this->em->getRepository("Merchandise:CoefBase")->findOneBy(
            array(),
            array(
                'week' => 'DESC',
            )
        );

        //Reset existing data for the current TMP
        $this->resetOrderTmp($tmp);

        //Récupération des founissuers actifs
        $suppliers = $this->em->getRepository("Merchandise:Supplier")->findBy(
            array(
                'active' => true,
            )
        );

        //Calculer le nombre du produits sur les quels on va travailler
        $totalProducts = 0;
        foreach ($suppliers as $s) {
            if (count($s->getPlannings()) > 0) {
                $suppliersProducts = $this->em->getRepository("Merchandise:ProductPurchased")
                    ->getActiveProductForOrderHelp($s);
                $totalProducts = $totalProducts + count($suppliersProducts);
            }
        }
        $progression->setTotalElements($totalProducts)
            ->setProceedElements(0);

        //Récupération des catégories éligibles à la commande
        $eligibleCategories = $this->em->getRepository("Merchandise:ProductCategories")->findBy(
            array(
                'eligible' => true,
            )
        );

        //Récupération des budgets sur la période j+20 avec j est le lundi de la semaine courante
        $j = new \DateTime();
        $budgets = [];
        for ($i = 1; $i <= 20; $i++) {
            $auxDateTimeStamp = mktime(
                0,
                0,
                0,
                intval($j->format('m')),
                intval($j->format('d')) + $i - 1,
                intval($j->format('Y'))
            );
            $auxDate = new \DateTime();
            $auxDate->setTimestamp($auxDateTimeStamp);
            $budgets[$i] = $this->caPrevService->createIfNotExsit($auxDate);
        }

        $proceddedProducts = 0;
        $progression->setProceedElements(0);
        foreach ($suppliers as $s) {
            try {
                if (count($s->getPlannings()) > 0) {
                    $suppliersProducts = $this->em->getRepository("Merchandise:ProductPurchased")
                        ->getActiveProductForOrderHelp($s);

                    if (count($suppliersProducts) == 0) {
                    } else {
                        $supplier = new OrderHelpSupplier();
                        $supplier
                            ->setSupplier($s)
                            ->setOrderHelp($tmp);
                        $this->em->persist($supplier);

                        //Initiating days/masks
                        foreach ($s->getPlannings() as $ppp) {
                            if ($ppp->isEligible()) {
                                $range = $ppp->getDeliveryDay() - $ppp->getOrderDay() + 1;
                                if ($range < 0) {
                                    $range = 7 + $range;
                                }
                                $bud = 0;

                                //Calculating absolute order day and absolute delivery day
                                $jx = intval(date('w'));

                                if ($ppp->getOrderDay() >= $jx) {
                                    $absolute_order_day = $ppp->getOrderDay() - $jx + 1;
                                } else {
                                    $absolute_order_day = 7 - $jx + $ppp->getOrderDay() + 1;
                                }

                                $absolute_delivery_day = $absolute_order_day + $range - 1;


                                for ($i = $absolute_order_day; $i <= $absolute_delivery_day; $i++) {
                                    $bud = $bud + $budgets[$i];
                                }

                                if (count($ppp->getCategories()) > 0) {
                                    foreach ($ppp->getCategories() as $catg) {
                                        if ($catg->getEligible()) {
                                            $mask = new OrderHelpMask();
                                            $mask->setAbsoluteDeliveryDay($absolute_delivery_day)
                                                ->setAbsoluteOrderDay($absolute_order_day)
                                                ->setStartDate(new \DateTime())
                                                ->setCategory($catg)
                                                ->setSupplier($supplier)
                                                ->setHelpTmp($tmp)
                                                ->setBudget($bud)
                                                ->setOrderDay($ppp->getOrderDay())
                                                ->setDeliveryDay($ppp->getdeliveryDay())
                                                ->setRange($range);
                                            $this->em->persist($mask);
                                            $this->em->flush();
                                        }
                                    }
                                } else {
                                    foreach ($eligibleCategories as $catg) {
                                        $mask = new OrderHelpMask();
                                        $mask->setAbsoluteDeliveryDay($absolute_delivery_day)
                                            ->setAbsoluteOrderDay($absolute_order_day)
                                            ->setStartDate(new \DateTime())
                                            ->setCategory($catg)
                                            ->setSupplier($supplier)
                                            ->setHelpTmp($tmp)
                                            ->setBudget($bud)
                                            ->setOrderDay($ppp->getOrderDay())
                                            ->setDeliveryDay($ppp->getdeliveryDay())
                                            ->setRange($range);
                                        $this->em->persist($mask);
                                        $this->em->flush();
                                    }
                                }
                            }
                        }

                        //Initiating products
                        foreach ($suppliersProducts as $p) {
                            $proceddedProducts++;
                            echo "Process product : ".$p->getName()."\n";
                            //Getting Coefficient
                            $coef = $this->em->getRepository("Merchandise:Coefficient")->findOneBy(
                                array(
                                    'base' => $base,
                                    'product' => $p,
                                )
                            );

                            if (!$coef) {
                                continue;
                            }

                            $product = new OrderHelpProducts();
                            $stockTheorique = $coef->getTheoStock() / $p->getInventoryQty();
                            if ($stockTheorique < 0) {
                                $stockTheorique = 0;
                            }
                            $product
                                ->setCoeff($coef->getCoef() * $p->getInventoryQty())
                                ->setStockQtyTheo($stockTheorique)
                                ->setStockQtyReal($coef->getRealStock() / $p->getInventoryQty())
                                ->setOrderHelp($tmp)
                                ->setProduct($p)
                                ->setSupplier($supplier);
                            $this->em->persist(clone $product);

                            $progression->setProgress(($proceddedProducts / $totalProducts) * 100);
                            $progression->setProceedElements($proceddedProducts);

                            $this->em->flush();
                        }
                    }
                    $this->em->flush();
                }
            } catch (\Exception $e) {
            }
        }

        $tmp->setLocked(false);
        $this->em->flush();
    }

    /**
     * @param OrderHelpTmp $tmp
     * Initialize and order help tmp table
     */
    public function createOrderTmp(OrderHelpTmp $tmp, ImportProgression $progression)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $tmp->setLocked(true);
        $this->em->flush();

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        //Reset existing data for the current TMP
        $this->resetOrderTmp($tmp);

        $ca = 0;

        //Calcul du CA sur la période indiquée
        //      ** Lecture de la table directement ****
        $cas = $this->em
            ->getRepository("Financial:FinancialRevenue")
            ->getFinancialRevenueBetweenDates(
                $tmp->getStartDateLastWeek(),
                $tmp->getEndDateLastWeek(),
                $currentRestaurant
            );
        foreach ($cas as $c) {
            $ca += $c->getAmount();
        }

        $tmp->setCa($ca);
        $this->em->persist($tmp);

        //Récupération des founissuers actifs
        $suppliers = $this->em->getRepository("Merchandise:Supplier")->getRestaurantSuppliers($currentRestaurant);

        //Calculer le nombre du produits sur les quels on va travailler
        $totalProducts = 0;
        foreach ($suppliers as $s) {
            if (count($s->getPlannings()) > 0) {
                $suppliersProducts = $this->em->getRepository("Merchandise:ProductPurchased")
                    ->getActiveProductForOrderHelp($s, $currentRestaurant);
                $totalProducts = $totalProducts + count($suppliersProducts);
            }
        }
        $progression->setTotalElements($totalProducts)
            ->setProceedElements(0);

        //Récupération des catégories éligibles à la commande
        $eligibleCategories = $this->em->getRepository("Merchandise:ProductCategories")->findBy(
            array(
                'eligible' => true,
            )
        );

        //Getting lastAdminsitrativeDay
        $lastADay = $this->em->getRepository("Financial:AdministrativeClosing")->findOneBy(
            ["originRestaurant" => $currentRestaurant],
            ["date" => "desc"]
        );
        if ($lastADay == null) {
            $lastADay = Utilities::getDateFromDate(new \DateTime(), -1);
        }

        //Houni dharb s7i7 !!
        $proceddedProducts = 0;
        $progression->setProceedElements(0);
        foreach ($suppliers as $s) {
            try {
                if (count($s->getPlanningsByRestaurant($currentRestaurant)) > 0) {
                    $suppliersProducts = $this->em->getRepository("Merchandise:ProductPurchased")
                        ->getActiveProductForOrderHelp($s, $currentRestaurant);

                    if (count($suppliersProducts) > 0) {
                        $supplier = new OrderHelpSupplier();
                        $supplier
                            ->setSupplier($s)
                            ->setOrderHelp($tmp);
                        $this->em->persist($supplier);

                        //Initiating days/masks
                        foreach ($s->getPlanningsByRestaurant($currentRestaurant) as $ppp) {
                            if ($ppp->isEligible()) {
                                //Calculating absolute order day
                                $jx = intval(date('w'));

                                if ($ppp->getOrderDay() >= $jx) {
                                    $absolute_order_day = $ppp->getOrderDay() - $jx;
                                } else {
                                    $absolute_order_day = 7 - $jx + $ppp->getOrderDay();
                                }


                                if (count($ppp->getCategories()) > 0) {
                                    foreach ($ppp->getCategories() as $catg) {
                                        if ($catg->getEligible()) {
                                            $mask = new OrderHelpMask();
                                            $mask
                                                ->setAbsoluteOrderDay($absolute_order_day)
                                                ->setStartDate(new \DateTime())
                                                ->setCategory($catg)
                                                ->setSupplier($supplier)
                                                ->setHelpTmp($tmp)
                                                ->setOrderDay($ppp->getOrderDay())
                                                ->setDeliveryDay($ppp->getdeliveryDay());
                                            $this->em->persist($mask);
                                            $this->em->flush();
                                        }
                                    }
                                } else {
                                    foreach ($eligibleCategories as $catg) {
                                        $mask = new OrderHelpMask();
                                        $mask
                                            ->setAbsoluteOrderDay($absolute_order_day)
                                            ->setStartDate(new \DateTime())
                                            ->setCategory($catg)
                                            ->setSupplier($supplier)
                                            ->setHelpTmp($tmp)
                                            ->setOrderDay($ppp->getOrderDay())
                                            ->setDeliveryDay($ppp->getdeliveryDay());
                                        $this->em->persist($mask);
                                        $this->em->flush();
                                    }
                                }
                            }
                        }//End foreach fournisseur Planning

                        $masks = $this->em->getRepository("Merchandise:OrderHelpMask")->findBy(
                            array(
                                'supplier' => $supplier,
                                'helpTmp' => $tmp,
                            )
                        );
                        $this->arrangeMask($masks);
                        $this->em->flush();


                        //Initiating products
                        foreach ($suppliersProducts as $p) {
                            $proceddedProducts++;
                            echo "Process product : ".$p->getName()."\n";
                            $product = new OrderHelpProducts();
                            $product
                                ->setOrderHelp($tmp)
                                ->setProduct($p)
                                ->setSupplier($supplier);

                            //Getting stock in the last administrative closing
                            $lastStock = $this->productService->getStockForProductInDate(
                                $p,
                                $lastADay instanceof AdministrativeClosing ? $lastADay->getDate() : $lastADay
                            );
                            $product->setLastStockQty(floatval($lastStock['stock']) / $p->getInventoryQty());
                            $product->setLastStockQtyIsReal($lastStock['isRealStock']);

                            $this->em->persist(clone $product);

                            $progression->setProgress(($proceddedProducts / $totalProducts) * 100);
                            $progression->setProceedElements($proceddedProducts);

                            $this->em->flush();
                        }
                    }
                    $this->em->flush();
                }
            } catch (\Exception $e) {
                //var_dump($e->getMessage());
            }
        }

        $tmp->setLocked(false);
        $this->em->flush();
    }

    /**
     * @param OrderHelpMask[] $masks
     */
    public function arrangeMask($masks)
    {

        usort(
            $masks,
            function (OrderHelpMask $e1, OrderHelpMask $e2) {
                if ($e1->getOrderDate()->format('Ymd') < $e2->getOrderDate()->format('Ymd')) {
                    return -1;
                } else {
                    return 1;
                }
            }
        );

        $n = count($masks);
        for ($i = 0; $i < $n; $i++) {
            $category = $masks[$i]->getCategory();
            $treaded = false;
            echo "Jour => ".$masks[$i]->getOrderDay()." Category  ".$category->getName()."\n";
            //Search in right part of the table
            for ($j = $i + 1; $j < $n; $j++) {
                echo "Checking Jour => ".$masks[$j]->getOrderDay()." \n";
                if ($masks[$j]->getCategory() == $category) {
                    echo "Found IN RIGHT PART \n";
                    $jDeliveryDate = $masks[$j]->getDeliveryDate();
                    $range = $jDeliveryDate->diff($masks[$i]->getOrderDate())->days + 1;
                    $absoluteDeliveryDay = $masks[$i]->getAbsoluteOrderDay() + $range - 1;
                    $treaded = true;
                    break;
                }
            }

            //Search on the left par of the table
            if (!$treaded) {
                for ($j = 0; $j <= $i; $j++) {
                    echo "Checking Jour => ".$masks[$j]->getOrderDay()." \n";
                    if ($masks[$j]->getCategory() == $category) {
                        echo "Found IN LEFT PART \n";
                        $jDeliveryDate = $masks[$j]->getDeliveryDate();
                        $range = $jDeliveryDate->diff($masks[$i]->getOrderDate())->days + 8;
                        $absoluteDeliveryDay = $masks[$i]->getAbsoluteOrderDay() + $range - 1;
                        $treaded = true;
                        break;
                    }
                }
            }

            //            //Only one day for this category for the supplier
            //            if (!$treaded){
            //                    echo "Only one day for this category for the supplier => ".$masks[$j]->getOrderDay()." \n";
            //                        $range = $masks[$i]->getDeliveryDay() - $masks[$i]->getOrderDay() ;
            //                        if ($range<0){
            //                            $range = 7 - $masks[$i]->getOrderDay() + $masks[$i]->getDeliveryDay() ;
            //                        }
            //                        $range++;
            //                        $absoluteDeliveryDay = $masks[$i]->getAbsoluteOrderDay() + $range - 1;
            //            }

            $masks[$i]->setRange($range);
            $masks[$i]->setAbsoluteDeliveryDay($absoluteDeliveryDay);
            $budget = $this->caPrevService->getCumulCaPrevBetweenDate(
                $masks[$i]->getAbsoluteOrderDate(),
                $masks[$i]->getAbsoluteDeliveryDate()
            );

            echo "Range $range AbsoluteDelivery Day => $absoluteDeliveryDay  Budget => $budget \n";


            $masks[$i]->setBudget($budget);
            $this->em->flush();
        }
    }

    public function setCoefficients(OrderHelpTmp $tmp, ImportProgression $progression)
    {
        $tmp->setLocked(true);
        $this->em->flush();

        $progression
            ->setTotalElements(count($tmp->getProducts()))
            ->setProceedElements(0);

        if (count($tmp->getProducts()) == 0) {
            $progression->setProgress(100);
            $progression->setProceedElements(0);
        }
        $this->em->flush();
        $proceedElement = 0;
        //Parcourir les élements
        foreach ($tmp->getProducts() as $helpProduct) {
            try {
                echo "Proceed product ".$helpProduct->getProduct()->getName()."\n";

                $coefData = $this->productService
                    ->getCoefForPP(
                        $helpProduct->getProduct(),
                        $tmp->getStartDateLastWeek(),
                        $tmp->getEndDateLastWeek(),
                        $tmp->getCa()
                    );

                $coef = $coefData['coef'] * $helpProduct->getProduct()->getInventoryQty();
                $consoReal = $coefData['conso_real'] / $helpProduct->getProduct()->getInventoryQty();
                $consoTheo = $coefData['conso_theo'] / $helpProduct->getProduct()->getInventoryQty();
                $stockReal = $coefData['realStock'] / $helpProduct->getProduct()->getInventoryQty();
                $stockTheo = $coefData['theoStock'] / $helpProduct->getProduct()->getInventoryQty();
                if ($stockTheo < 0) {
                    $stockTheo = 0;
                }
                $helpProduct
                    ->setFixed($coefData['fixed'])
                    ->setCoeff($coef)
                    ->setHebReal($consoReal)
                    ->setHebTheo($consoTheo)
                    ->setStockFinalExist($coefData['finalStockExist'])
                    ->setStockQtyReal($stockReal)
                    ->setStockQtyTheo($stockTheo)
                    ->setType($coefData['type']);


                $proceedElement++;
                $progression->setProgress($proceedElement / $progression->getTotalElements() * 100);
                $progression->setProceedElements($proceedElement);
                $this->em->flush();
                echo "======= \n";
            } catch (\Exception $e) {
            }
        }

        $tmp->setLocked(false);
        $this->em->flush();
    }

    public function setResults(OrderHelpTmp $tmp, ImportProgression $progression)
    {
        $tmp->setLocked(true);
        $this->em->flush();

        //Reset HelpMaskProduct
        $this->resetHelpMaskProduct($tmp);

        try {
            $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
            $products = $tmp->getProducts();

            $masks = $tmp->getMasks();

            $progression->setTotalElements(count($products))
                ->setProceedElements(0)
                ->setStartDateTime(new \DateTime("NOW"))
                ->setStatus('pending');

            foreach ($products as $p) {
                //GetMask for the current product
                foreach ($masks as $m) {
                    if ($m->getCategory() == $p->getProduct()->getProductCategory()
                        && $m->getSupplier() == $p->getSupplier()
                    ) {
                        $pM = new OrderHelpMaskProduct();

                        $pM->setMask($m)
                            ->setHelpProduct($p)
                            ->setOrderHelp($tmp)
                            ->setSupplier($m->getSupplier());

                        //Constrcution du J1 et J2
                        $j2 = Utilities::getDateFromDate($m->getStartDate(), ceil($m->getAbsoluteDeliveryDay()));

                        //Recupérer la LP Avant J2
                        //Récupérer les quantités des commande "envoyé" et "en cours d'envoi" et "modifie aprs envoie"
                        $orderlines = $this->em->getRepository("Merchandise:OrderLine")->getOrderLineToBeDelivered(
                            $p->getProduct(),
                            $j2,
                            $currentRestaurant
                        );

                        $lp = 0;
                        foreach ($orderlines as $ol) {
                            $lp = $lp + $ol->getQty();
                        }

                        $pM->setLp($lp);
                        if ($lp > 0) {
                            echo "LP for ".$p->getProduct()->getName()." ".$lp."\n";
                        }

                        //Calculer le Besoin
                        if (!$p->getCoeff()) {
                            $need = 0;
                        } else {
                            if ($pM->getMask() != null) {
                                $need = $pM->getMask()->getBudget() / $p->getCoeff();
                            } else {
                                $need = 0;
                            }
                        }
                        $pM->setNeed($need);

                        //Calculer la qté à commande
                        $qty = $need - ($p->getLastStockQty() + $lp);

                        $qty = intval($qty);
                        $pM->setQtyToBeOrdred($qty);

                        $this->em->persist($pM);
                        $this->em->flush();
                    }
                }


                //Ordonner la liste des produits selon la date
                $helpProducts = $p->getHelpMaskProducts();

                for ($i = 1; $i < count($helpProducts); $i++) {
                    $lpExtra = 0;
                    for ($j = 0; $j < $i; $j++) {
                        if ($helpProducts[$j]->getMask()->getDeliveryDate()->format('Ymd') < $helpProducts[$i]->getMask(
                        )->getOrderDate()->format('Ymd')
                        ) {
                            if ($helpProducts[$j]->getQtyToBeOrdred() > 0) {
                                $lpExtra += $helpProducts[$j]->getQtyToBeOrdred();
                            }
                        }
                    }
                    $helpProducts[$i]->setLp($helpProducts[$i]->getLp() + $lpExtra);
                    $helpProducts[$i]->setQtyToBeOrdred($helpProducts[$i]->getQtyToBeOrdred() - $lpExtra);
                }

                //Incrementer progression
                //echo "Product ".$p->getProduct()->getName()."\n";
                $progression->incrementProgression();
                $this->em->flush();
            }
        } catch (\Exception $e) {
        }
        $tmp->setLocked(false);
        $this->em->flush();
    }

    public function resetOrderTmp(OrderHelpTmp $tmp)
    {
        $sql = [];

        $sql[] = "DELETE FROM order_help_mask_product WHERE order_help_id = :id;";
        $sql[] = "DELETE FROM order_help_products WHERE order_help_id = :id;";
        $sql[] = "DELETE FROM order_help_mask WHERE help_tmp_id = :id;";
        $sql[] = "DELETE FROM order_help_supplier WHERE order_help_id = :id;";


        foreach ($sql as $s) {
            echo "DELETE $s \n";
            $stm = $this->em->getConnection()->prepare($s);
            $stm->bindParam('id', $tmp->getId(), \PDO::PARAM_INT);
            try {
                $stm->execute();
            } catch (\Exception $e) {
                return false;
            }
        }
    }

    private function resetHelpMaskProduct(OrderHelpTmp $tmp)
    {
        try {
            $stm = $this->em->getConnection()->prepare(
                "DELETE FROM order_help_mask_product WHERE order_help_id = :id;"
            );
            $stm->bindParam('id', $tmp->getId(), \PDO::PARAM_INT);
            $stm->execute();
        } catch (\Exception $e) {
            return false;
        }
    }

    function verifyAvailability($day, $supplierId)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $helpOrder = $this->em->getRepository("Merchandise:OrderHelpTmp")->findOneBy(
            array(
                'originRestaurant' => $currentRestaurant,
                'week' => intval(date('W')),
            )
        );
        $startDate = $helpOrder->getMasks()[0]->getStartDate();

        $result = [];
        $supplier = $this->em->getRepository("Merchandise:Supplier")->find($supplierId);

        $dayOrder = $this->getOrderDate($startDate, $day);

        $result['orderDay'] = $dayOrder;

        $order = $this->em->getRepository("Merchandise:Order")->findBy(
            array(
                'originRestaurant' => $currentRestaurant,
                'supplier' => $supplier,
                'dateOrder' => $dayOrder,
            )
        );

        //Se débarsser des commandes annulées
        foreach ($order as $key => $o) {
            if ($o->getStatus() == Order::CANCELED) {
                unset($order[$key]);
            }
        }
        $order = array_values($order);
        if (count($order) == 0) {
            $result['code'] = 'free';
        } else {
            if (count($order) == 1) {
                $order = $order[0];
                switch ($order->getStatus()) {
                    case Order::CANCELED:
                        $result['code'] = 'free';
                        break;
                    case Order::DRAFT:
                        $result['code'] = 'not_free_with_modification';
                        $result['id'] = $order->getId();
                        break;
                    case Order::SENDING:
                        $result['code'] = 'not_free_with_modification';
                        $result['id'] = $order->getId();
                        break;
                    case Order::REJECTED:
                        $result['code'] = 'not_free_with_modification';
                        $result['id'] = $order->getId();
                        break;
                    case Order::DELIVERED:
                        $result['code'] = 'not_free_no_modification';
                        $result['id'] = $order->getId();
                        break;
                    case Order::MODIFIED:
                        $result['code'] = 'not_free_no_modification';
                        $result['id'] = $order->getId();
                        break;
                    case Order::SENDED:
                        $canBeForced = $this->orderService->canBeForced($order);
                        if ($canBeForced === true) {
                            $result['code'] = 'not_free_with_modification';
                            $result['id'] = $order->getId();
                        } else {
                            $result['code'] = 'not_free_no_modification';
                            $result['id'] = $order->getId();
                        }
                        break;
                }
            } else {
                $result['code'] = 'not_free_no_modification';
            }
        }

        return $result;
    }

    function getOrderDate(\DateTime $startDate, $day)
    {
        $startDateDay = intval($startDate->format('w'));
        if ($startDateDay <= $day) {
            $diff = $day - $startDateDay;
        } else {
            $diff = 7 - $startDateDay + $day;
        }
        $dayOrder = Utilities::getDateFromDate($startDate, $diff);

        return $dayOrder;
    }


    /**
     *
     * V2 ***
     */
    public function setResultsV2(OrderHelpTmp $tmp, ImportProgression $progression)
    {
        $tmp->setLocked(true);
        $this->em->flush();
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();

        //Reset HelpMaskProduct
        $this->resetHelpMaskProduct($tmp);

        $base = $this->em->getRepository("Merchandise:CoefBase")->findOneBy(
            array('originRestaurant' => $currentRestaurant),
            array('id' => 'DESC')
        );

        try {
            $products = $tmp->getProducts();

            $masks = $tmp->getMasks();

            $progression->setTotalElements(count($products))
                ->setProceedElements(0)
                ->setStartDateTime(new \DateTime("NOW"))
                ->setStatus('pending');

            foreach ($products as $p) {
                //GetMask for the current product
                foreach ($masks as $m) {
                    if ($m->getCategory() == $p->getProduct()->getProductCategory()
                        && $m->getSupplier() == $p->getSupplier()
                    ) {
                        $pM = new OrderHelpMaskProduct();

                        $pM->setMask($m)
                            ->setHelpProduct($p)
                            ->setOrderHelp($tmp)
                            ->setSupplier($m->getSupplier());

                        //Constrcution du J1 et J2
                        $j2 = Utilities::getDateFromDate($m->getStartDate(), ceil($m->getAbsoluteDeliveryDay()));

                        //Recupérer la LP Avant J2
                        //Récupérer les quantités des commande "envoyé" et "en cours d'envoi" et "modifie aprés envoie"
                        $orderlines = $this->em->getRepository("Merchandise:OrderLine")->getOrderLineToBeDelivered(
                            $p->getProduct(),
                            $j2,
                            $currentRestaurant
                        );

                        $lp = 0;
                        foreach ($orderlines as $ol) {
                            $lp = $lp + $ol->getQty();
                        }

                        $pM->setLp($lp);
                        if ($lp > 0) {
                            echo "LP for ".$p->getProduct()->getName()." ".$lp."\n";
                        }

                        //Getting Coef

                        $coeffLine = $this->em->getRepository("Merchandise:Coefficient")->findOneBy(
                            array('product' => $p->getProduct(), 'base' => $base)
                        );

                        $p->setCoeff($coeffLine->getCoef() * $p->getProduct()->getInventoryQty());

                        //Calculer le Besoin
                        if (!$p->getCoeff()) {
                            $need = 0;
                        } else {
                            if ($pM->getMask() != null) {
                                $need = $pM->getMask()->getBudget() / $p->getCoeff();
                            } else {
                                $need = 0;
                            }
                        }
                        $pM->setNeed($need);

                        //Calculer la qté à commande
                        if ($p->getLastStockQtyIsReal()) {
                            $inStock = $p->getLastStockQty();
                        } else {
                            $inStock = ($p->getLastStockQty() < 0) ? 0 : $p->getLastStockQty();
                        }
                        $qty = $need - ($inStock + $lp);

                        $qty = intval(ceil($qty));
                        $pM->setQtyToBeOrdred($qty);

                        $this->em->persist($pM);
                        $this->em->flush();
                    }
                }


                //Ordonner la liste des produits selon la date
                $helpProducts = $p->getHelpMaskProducts();

                for ($i = 1; $i < count($helpProducts); $i++) {
                    $lpExtra = 0;
                    for ($j = 0; $j < $i; $j++) {
                        if ($helpProducts[$j]->getMask()->getDeliveryDate()->format('Ymd') < $helpProducts[$i]->getMask(
                        )->getOrderDate()->format('Ymd')
                        ) {
                            if ($helpProducts[$j]->getQtyToBeOrdred() > 0) {
                                $lpExtra += $helpProducts[$j]->getQtyToBeOrdred();
                            }
                        }
                    }
                    $helpProducts[$i]->setLp($helpProducts[$i]->getLp() + $lpExtra);
                    $helpProducts[$i]->setQtyToBeOrdred($helpProducts[$i]->getQtyToBeOrdred() - $lpExtra);
                }

                //Incrementer progression
                //echo "Product ".$p->getProduct()->getName()."\n";
                $progression->incrementProgression();
                $this->em->flush();
            }
        } catch (\Exception $e) {
            echo($e->getMessage());
        }
        $tmp->setLocked(false);
        $this->em->flush();
    }
}
