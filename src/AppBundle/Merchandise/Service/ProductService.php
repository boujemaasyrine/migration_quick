<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 29/02/2016
 * Time: 10:11
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\General\Service\FiscalDateService;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\UnitNeedProducts;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Acl\Exception\Exception;

class ProductService
{

    private $em;

    private $fiscaleDateService;

    private $sqlRepo;

    private $logger;

    private $restaurantService;

    public function __construct(
        EntityManager $em,
        FiscalDateService $fiscalDateService,
        $sqlRepo,
        Logger $logger,
        RestaurantService $restaurantService
    ) {
        $this->em = $em;
        $this->fiscaleDateService = $fiscalDateService;
        $this->sqlRepo = $sqlRepo;
        $this->logger = $logger;
        $this->restaurantService = $restaurantService;
    }


    /**
     * @param Product     $product
     * @param  int           $variation -/+
     * @param string      $unit
     * @param Recipe|null $concernedRecipe
     * @param bool        $secondary
     *
     * @throws \Exception
     */
    public function updateStock(
        Product $product,
        $variation,
        $unit = Product::INVENTORY_UNIT,
        Recipe $concernedRecipe = null,
        $secondary=false
    ) {
        if ($product instanceof ProductSold) {
            $product->modifyStock($variation, $concernedRecipe);
        } else {
            if ($unit == Product::USE_UNIT) {
                if ($product instanceof ProductPurchased) {
                } elseif ($product instanceof UnitNeedProducts) {
                    throw new \Exception("Expect Product to be PurchasedProduct Got UnitNeedProducts");
                }
            } elseif ($unit == Product::EXPED_UNIT) {
                if ($product instanceof ProductPurchased) {
                    if ($secondary) {
                        if($product->getUsageQty()!=0){
                            $variation=$variation /$product->getUsageQty();
                        }
                        else {
                            throw new \Exception("Division by zero expecting Inv to Usage Coefficient to be <> 0");
                        }


                    } else {
                        $variation = $variation * $product->getInventoryQty();
                    }
                } else {
                    throw new \Exception("Expect Product to be PurchasedProduct got ".get_class($product));
                }
            }
            $product->modifyStock($variation);
        }

        $this->em->flush();
    }

    public function isProductOrderable(ProductPurchased $product, \DateTime $orderDate)
    {
        $startDateCmd = $product->getStartDateCmd();
        $endDateCmd = $product->getEndDateCmd();

        // Si l'une ou les deux dates sont vides, le produit est commandable
        if (empty($startDateCmd) || empty($endDateCmd)) {
            return true;
        }

        // Si les deux dates sont non vides, on vérifie qu'elles sont valides
        if (!$startDateCmd instanceof \DateTime) {
            try {
                $startDateCmd = new \DateTime($startDateCmd);
            } catch (\Exception $e) {
                throw new \Exception("La date de début n'est pas valide.");
            }
        }

        if (!$endDateCmd instanceof \DateTime) {
            try {
                $endDateCmd = new \DateTime($endDateCmd);
            } catch (\Exception $e) {
                throw new \Exception("La date de fin n'est pas valide.");
            }
        }

        // Comparaison des dates sans les heures
        $startDateCmdFormatted = $startDateCmd->format('Y-m-d');
        $endDateCmdFormatted = $endDateCmd->format('Y-m-d');
        $orderDateFormatted = $orderDate->format('Y-m-d');

        // Vérification si la date de commande est dans l'intervalle entre startDateCmd et endDateCmd, incluant $endDateCmd
        if ($orderDateFormatted >= $startDateCmdFormatted && $orderDateFormatted <= $endDateCmdFormatted) {
            return true; // Le produit est commandable
        }

        // Si la date de commande n'est pas dans l'intervalle, le produit n'est pas commandable
        return false;
    }

    public function getRTStockQty(ProductPurchased $product)
    {
        $restaurant = $this->restaurantService->getCurrentRestaurant();
        $lastADay = $this->em->getRepository("Financial:AdministrativeClosing")->findOneBy(
            ['originRestaurant' => $restaurant],
            ["date" => "desc"]
        );
        if (is_null($lastADay)) {
            $lastADay = Utilities::getDateFromDate(new \DateTime(), -1);
        } else {
            $lastADay = $lastADay->getDate();
        }
        $stock = $this->getStockForProductInDate($product, $lastADay);

        $data = array(
            'qty' => $stock['stock'],
            'type' => $stock['isRealStock'] ? 'real' : 'theory',
        );

        return $data;
    }

    public function getStockForProductInDate(ProductPurchased $productPurchased, \DateTime $date)
    {
        $restaurantID = $this->restaurantService->getCurrentRestaurant()->getId();

        $sqlFile = $this->sqlRepo."/real_theo_stock_for_product_by_date.v2.sql"; //v2
        if (!file_exists($sqlFile)) {
            throw new \Exception($sqlFile." Not found");
        }
        $dateString = $date->format('Y-m-d 23:59:59');
        $id = $productPurchased->getId();
        $stm = $this->em->getConnection()->prepare(file_get_contents($sqlFile));
        $stm->bindParam('targetDate', $dateString, \PDO::PARAM_STR);
        $stm->bindParam('product_id', $id);
        $stm->bindParam('restaurantID', $restaurantID);
        $stm->execute();
        $result = $stm->fetch();

        //begin v2
        if ($result) {
            $historicProductData = $this->getHistoricProduct($productPurchased, $date);

            if(!empty($historicProductData['inventory_qty'])) {
                $result['delivered_qty'] = $result['delivered_qty'] * $historicProductData['inventory_qty'];
            }
            else {
                $result['delivered_qty']=0;
            }

            if (!empty($historicProductData['usage_qty'])){
                $result['transfer_in'] =
                    $result['transfer_in_inv'] +
                    ($result['transfer_in_exp'] * $historicProductData['inventory_qty']) +
                    ($result['transfer_in_use'] / $historicProductData['usage_qty']);

                $result['transfer_out'] =
                    $result['transfer_out_inv'] +
                    ($result['transfer_out_exp'] * $historicProductData['inventory_qty']) +
                    ($result['transfer_out_use'] / $historicProductData['usage_qty']);

                $result['retours'] =
                    $result['retours_inv'] +
                    ($result['retours_exp'] * $historicProductData['inventory_qty']) +
                    ($result['retours_use'] / $historicProductData['usage_qty']);

                $result['consomation_non_transformed'] = $result['consomation_non_transformed'] / $historicProductData['usage_qty'];
                $result['consomation_transformed'] = $result['consomation_transformed'] / $historicProductData['usage_qty'];
            }

            else{
                $result['transfer_in'] =
                    $result['transfer_in_inv'] +
                    ($result['transfer_in_exp'] * $historicProductData['inventory_qty']);

                $result['transfer_out'] =
                    $result['transfer_out_inv'] +
                    ($result['transfer_out_exp'] * $historicProductData['inventory_qty']);

                $result['retours'] =
                    $result['retours_inv'] +
                    ($result['retours_exp'] * $historicProductData['inventory_qty']);

                $result['consomation_non_transformed'] = 0;
                $result['consomation_transformed'] = 0;
            }


            $result['entree'] = $result['delivered_qty'] + $result['transfer_in'];
            $result['sortie'] = $result['transfer_out'] + $result['retours'] + $result['consomation_non_transformed'] + $result['consomation_transformed'];
            $result['stock_theorique'] = $result['last_inventory_total'] + $result['entree'] - $result['sortie'];
            if ($result['stock_theorique'] < 0) {
                $result['stock_theorique'] = 0;
            }
        }

        //end v2

        return array(
            'lastInventory' => $result['last_inventory_total'],
            'lastInventoryDate' => $result['last_inventory_date'],
            'isRealStock' => $result['real_stock_exist'],
            'stock' => $result['stock_theorique'],
            'rawData' => $result,
        );
    }

    /*  public function getInitialStockValorizationAtDate(\DateTime $date, $currentRestaurantId)
      {

          $dateString = $date->format('Y-m-d 00:00:00');
          $sql = '
                  select COALESCE(sum(SUB_QUERY.initial_variation_value),0) as valorization from
                  (SELECT	INITIAL_THEORICAL_STOCK.product_id,
                  INITIAL_THEORICAL_STOCK.last_inventory_date,

                  CASE WHEN (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation)< 0 THEN 0 ELSE (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation) * INITIAL_THEORICAL_STOCK.inventory_buying_cost end  initial_variation_value,
                  CASE WHEN (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation)< 0 THEN 0 ELSE (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation) END theorical_initial_stock
                  FROM (
                  SELECT MAX(INITIAL_INVENTORY.product_id) as product_id,
                  //MAX(INITIAL_INVENTORY.inventory_buying_cost) as inventory_buying_cost,
                  INITIAL_INVENTORY.inventory_buying_cost as inventory_buying_cost,
                   MAX(INITIAL_INVENTORY.date_time) as last_inventory_date,
                   case when  MAX(INITIAL_INVENTORY.stock_qty) < 0 then 0 else MAX(INITIAL_INVENTORY.stock_qty) end as initial_stock,
                    COALESCE(SUM(MVMTS.variation),0) as variation, COALESCE(SUM(MVMTS.buying_value),0) as buying_value


                  FROM (
                  SELECT DISTINCT ON (product_id)
                  id, product_id, date_time, stock_qty , (buying_cost / inventory_qty) as inventory_buying_cost
                  FROM   product_purchased_mvmt
                  where origin_restaurant_id = :origin_restaurant_id and deleted = false and type = \'inventory\' and date_time < :D1 and stock_qty is not null
                  and product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true)
                  ORDER  BY product_id, date_time DESC, id DESC) INITIAL_INVENTORY
                  LEFT JOIN (
                      SELECT product_purchased_mvmt.product_id, product_purchased_mvmt.date_time , product_purchased_mvmt.variation, (product_purchased_mvmt.buying_cost / product_purchased_mvmt.inventory_qty) as buying_value
                      FROM product_purchased_mvmt
                      where origin_restaurant_id = :origin_restaurant_id and deleted = false and type != \'inventory\' and date_time < :D1
                      and product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true)
                  ) as MVMTS on INITIAL_INVENTORY.product_id = MVMTS.product_id and DATE(MVMTS.date_time) > DATE(INITIAL_INVENTORY.date_time)
                  GROUP BY INITIAL_INVENTORY.product_id, INITIAL_INVENTORY.inventory_buying_cost
              ) as INITIAL_THEORICAL_STOCK) as SUB_QUERY
              ';

          $stm = $this->em->getConnection()->prepare($sql);
          $stm->bindParam('D1', $dateString);
          $stm->bindParam("origin_restaurant_id", $currentRestaurantId);

          $stm->execute();
          $data = $stm->fetchAll();

          return $data[0]['valorization'];
      }*/


    //Added by Belsem
    public function getInitialStockValorizationAtDate($filter, $currentRestaurantId)
    {
        $dateString = $filter['beginDate']." 00:00:00";
        $D2 = $filter['lastDate']." 23:59:59";
        $sql = '
                select COALESCE(sum(SUB_QUERY.initial_variation_value),0) as valorization from
                (SELECT	INITIAL_THEORICAL_STOCK.product_id,
                INITIAL_THEORICAL_STOCK.last_inventory_date,

                CASE WHEN (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation)< 0 THEN 0 ELSE (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation) * INITIAL_THEORICAL_STOCK.inventory_buying_cost end  initial_variation_value,
                CASE WHEN (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation)< 0 THEN 0 ELSE (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation) END theorical_initial_stock
                FROM (
                SELECT MAX(INITIAL_INVENTORY.product_id) as product_id,
                /*MAX(INITIAL_INVENTORY.inventory_buying_cost) as inventory_buying_cost,*/
                INITIAL_INVENTORY.inventory_buying_cost as inventory_buying_cost,
                 MAX(INITIAL_INVENTORY.date_time) as last_inventory_date,
                 case when  MAX(INITIAL_INVENTORY.stock_qty) < 0 then 0 else MAX(INITIAL_INVENTORY.stock_qty) end as initial_stock,
                  COALESCE(SUM(MVMTS.variation),0) as variation, COALESCE(SUM(MVMTS.buying_value),0) as buying_value


                FROM (
                SELECT DISTINCT ON (product_id)
                id, product_id, date_time, stock_qty , (buying_cost / inventory_qty) as inventory_buying_cost
                FROM   product_purchased_mvmt
                where origin_restaurant_id = :origin_restaurant_id and deleted = false and type = \'inventory\' and date_time < :D1 and stock_qty is not null
                and product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true
              and (pp.status = \'active\' OR pp.status = \'toInactive\' OR pp.deactivation_date >= :D2)
                )
                ORDER  BY product_id, date_time DESC, id DESC) INITIAL_INVENTORY
                LEFT JOIN (
                    SELECT product_purchased_mvmt.product_id, product_purchased_mvmt.date_time , product_purchased_mvmt.variation, (product_purchased_mvmt.buying_cost / product_purchased_mvmt.inventory_qty) as buying_value
                    FROM product_purchased_mvmt
                    where origin_restaurant_id = :origin_restaurant_id and deleted = false and type != \'inventory\' and date_time < :D1
                    and product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true
                     and (pp.status = \'active\' OR pp.status = \'toInactive\' OR pp.deactivation_date >= :D2)
                    )
                ) as MVMTS on INITIAL_INVENTORY.product_id = MVMTS.product_id and DATE(MVMTS.date_time) > DATE(INITIAL_INVENTORY.date_time)
                GROUP BY INITIAL_INVENTORY.product_id, INITIAL_INVENTORY.inventory_buying_cost
            ) as INITIAL_THEORICAL_STOCK) as SUB_QUERY
            ';

        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('D1', $dateString);
        $stm->bindParam('D2', $D2);
        $stm->bindParam("origin_restaurant_id", $currentRestaurantId);

        $stm->execute();
        $data = $stm->fetchAll();

        return $data[0]['valorization'];
    }

    /* public function getFinalStockValorizationAtDate(\DateTime $date, $currentRestaurantId)
      {
          $dateString = $date->format('Y-m-d 23:59:59');

          $sql = ' select COALESCE(sum(SUB_QUERY.final_value),0) as valorization from
   (
                  SELECT FINAL_THEORICAL_STOCK.product_id, FINAL_THEORICAL_STOCK.last_final_inventory_date,
                  CASE WHEN (FINAL_THEORICAL_STOCK.final_stock + FINAL_THEORICAL_STOCK.variation) < 0 THEN 0 ELSE (FINAL_THEORICAL_STOCK.final_stock + FINAL_THEORICAL_STOCK.variation) END theorical_final_stock,
                   CASE WHEN (FINAL_THEORICAL_STOCK.final_stock + FINAL_THEORICAL_STOCK.variation) < 0 THEN 0 ELSE ((FINAL_THEORICAL_STOCK.final_stock + FINAL_THEORICAL_STOCK.variation) * FINAL_THEORICAL_STOCK.inventory_buying_cost) end final_value
                  FROM (
                      SELECT MAX(FINAL_INVENTORY.product_id) as product_id,
                      MAX(FINAL_INVENTORY.id) as op_id,
                      MAX(FINAL_INVENTORY.date_time) as last_final_inventory_date,
                     CASE WHEN MAX(FINAL_INVENTORY.stock_qty) < 0 THEN 0 ELSE MAX(FINAL_INVENTORY.stock_qty) END as final_stock,
                      COALESCE(SUM(MVMTS.variation),0) as variation,
                      //MAX(FINAL_INVENTORY.inventory_buying_cost) as inventory_buying_cost
                     FINAL_INVENTORY.inventory_buying_cost as inventory_buying_cost
                      FROM (
                          SELECT DISTINCT ON (product_id)
                          id, product_id, date_time, stock_qty, (buying_cost / inventory_qty) inventory_buying_cost
                          FROM   product_purchased_mvmt
                          where origin_restaurant_id = :origin_restaurant_id and deleted = false and type = \'inventory\' and date_time <= :D2 and stock_qty is not null
                          and product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true)
                          ORDER  BY product_id, date_time DESC, id DESC
                      ) FINAL_INVENTORY
                      LEFT JOIN (
                          SELECT product_purchased_mvmt.product_id, product_purchased_mvmt.date_time , product_purchased_mvmt.variation, product_purchased_mvmt.variation * (product_purchased_mvmt.buying_cost * product_purchased_mvmt.inventory_qty) as variation_value
                          FROM product_purchased_mvmt where origin_restaurant_id = :origin_restaurant_id and deleted = false and type != \'inventory\' and date_time <= :D2
                          and product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true)
                      ) as MVMTS on FINAL_INVENTORY.product_id = MVMTS.product_id and DATE(MVMTS.date_time) > DATE(FINAL_INVENTORY.date_time)
                      GROUP BY FINAL_INVENTORY.product_id,  FINAL_INVENTORY.inventory_buying_cost
              ) as FINAL_THEORICAL_STOCK) as SUB_QUERY';


          $stm = $this->em->getConnection()->prepare($sql);
          $stm->bindParam('D2', $dateString);
          $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
          $stm->execute();
          $data = $stm->fetchAll();

          return $data[0]['valorization'];
      }*/
//Added by Belsem
    public function getFinalStockValorizationAtDate($filter, $currentRestaurantId)
    {
        $dateString = $filter['beginDate']." 00:00:00";
        $lastDate = $filter['lastDate']." 23:59:59";

        //var_dump($dateString,$lastDate);die;
        $sql = 'SELECT COALESCE(SUM(final_valorization),0)AS valorization  FROM ( 
 
        SELECT
        /* Product Id */   P.id as PRODUCT_ID,
        /* Category id */  PG.id as category_id,
	    /*Final valorization*/ COALESCE(FINAL.final_value,COALESCE(COALESCE(INITIAL.initial_variation_value,0) + COALESCE(ENTREE.in_variation_value,0) - COALESCE(SORTIE.out_variation_value,0),0 )) as final_valorization,
       
        PP.status as product_status,
        PP.deactivation_date as date_desactivation
        FROM public.product_purchased PP
        /* Category name */
        LEFT JOIN public.product P ON P.id = PP.id 
        LEFT JOIN public.product_categories PG ON PG.id = PP.product_category_id

        /* INITIAL */
        LEFT JOIN (   
           SELECT	INITIAL_THEORICAL_STOCK.product_id,
                INITIAL_THEORICAL_STOCK.last_inventory_date,

                CASE WHEN (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation)< 0 THEN 0 ELSE (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation) * INITIAL_THEORICAL_STOCK.inventory_buying_cost end  initial_variation_value,
                CASE WHEN (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation)< 0 THEN 0 ELSE (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation) END theorical_initial_stock
                FROM (
                SELECT MAX(INITIAL_INVENTORY.product_id) as product_id,
                /*MAX(INITIAL_INVENTORY.inventory_buying_cost) as inventory_buying_cost,*/
                INITIAL_INVENTORY.inventory_buying_cost as inventory_buying_cost,
                 MAX(INITIAL_INVENTORY.date_time) as last_inventory_date,
                 case when  MAX(INITIAL_INVENTORY.stock_qty) < 0 then 0 else MAX(INITIAL_INVENTORY.stock_qty) end as initial_stock,
                  COALESCE(SUM(MVMTS.variation),0) as variation, COALESCE(SUM(MVMTS.buying_value),0) as buying_value


                FROM (
                SELECT DISTINCT ON (product_id)
                id, product_id, date_time, stock_qty , (buying_cost / inventory_qty) as inventory_buying_cost
                FROM   product_purchased_mvmt
                where origin_restaurant_id = :origin_restaurant_id and deleted = false and type = \'inventory\' and date_time < :D1 and stock_qty is not null
                and product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true
				 
				)
                ORDER  BY product_id, date_time DESC, id DESC) INITIAL_INVENTORY
                LEFT JOIN (
                    SELECT product_purchased_mvmt.product_id, product_purchased_mvmt.date_time , product_purchased_mvmt.variation, (product_purchased_mvmt.buying_cost / product_purchased_mvmt.inventory_qty) as buying_value
                    FROM product_purchased_mvmt
                    where origin_restaurant_id = :origin_restaurant_id and deleted = false and type != \'inventory\' and date_time < :D1
                    and product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true
					 
					)
                ) as MVMTS on INITIAL_INVENTORY.product_id = MVMTS.product_id and DATE(MVMTS.date_time) > DATE(INITIAL_INVENTORY.date_time)
                GROUP BY INITIAL_INVENTORY.product_id, INITIAL_INVENTORY.inventory_buying_cost
            ) as INITIAL_THEORICAL_STOCK
        ) INITIAL on INITIAL.product_id = P.id



        /* ENTREES */
        LEFT JOIN (
     SELECT product_purchased_mvmt.product_id,
                   SUM(product_purchased_mvmt.variation) qty,
                   SUM(product_purchased_mvmt.variation * (product_purchased_mvmt.buying_cost / product_purchased_mvmt.inventory_qty)) as in_variation_value
            FROM product_purchased_mvmt where product_purchased_mvmt.origin_restaurant_id = :origin_restaurant_id and deleted = false and type in (\'transfer_in\', \'delivery\') and date_time >= :D1 and date_time <= :D2
            GROUP BY product_id
       
		
		
		) ENTREE ON ENTREE.product_id = P.id
		
		
		 /* Sorties */
        LEFT JOIN (
            SELECT  product_purchased_mvmt.product_id,
                SUM(ABS(product_purchased_mvmt.variation)) qty,
                SUM(ABS(product_purchased_mvmt.variation) * (product_purchased_mvmt.buying_cost / product_purchased_mvmt.inventory_qty)) as out_variation_value
            FROM product_purchased_mvmt where product_purchased_mvmt.origin_restaurant_id = :origin_restaurant_id and deleted = false and type in (\'transfer_out\', \'returns\') and date_time >= :D1 and date_time <= :D2
            GROUP BY product_id
          
        ) SORTIE ON SORTIE.product_id = P.id
		
		
		
		
        /* FINAL */
        LEFT JOIN (
          SELECT FINAL_THEORICAL_STOCK.product_id, FINAL_THEORICAL_STOCK.last_final_inventory_date,
                CASE WHEN (FINAL_THEORICAL_STOCK.final_stock + FINAL_THEORICAL_STOCK.variation) < 0 THEN 0 ELSE (FINAL_THEORICAL_STOCK.final_stock + FINAL_THEORICAL_STOCK.variation) END theorical_final_stock,
                 CASE WHEN (FINAL_THEORICAL_STOCK.final_stock + FINAL_THEORICAL_STOCK.variation) < 0 THEN 0 ELSE ((FINAL_THEORICAL_STOCK.final_stock + FINAL_THEORICAL_STOCK.variation) * FINAL_THEORICAL_STOCK.inventory_buying_cost) end final_value
                FROM (
                    SELECT MAX(FINAL_INVENTORY.product_id) as product_id,
                    MAX(FINAL_INVENTORY.id) as op_id,
                    MAX(FINAL_INVENTORY.date_time) as last_final_inventory_date,
                   CASE WHEN MAX(FINAL_INVENTORY.stock_qty) < 0 THEN 0 ELSE MAX(FINAL_INVENTORY.stock_qty) END as final_stock,
                    COALESCE(SUM(MVMTS.variation),0) as variation,
                    /*MAX(FINAL_INVENTORY.inventory_buying_cost) as inventory_buying_cost*/
                   FINAL_INVENTORY.inventory_buying_cost as inventory_buying_cost
                    FROM (
                        SELECT DISTINCT ON (product_id)
                        id, product_id, date_time, stock_qty, (buying_cost / inventory_qty) inventory_buying_cost
                        FROM   product_purchased_mvmt
                        where product_purchased_mvmt.origin_restaurant_id = :origin_restaurant_id and deleted = false and type = \'inventory\' and date_time <= :D2 and stock_qty is not null
                            and product_purchased_mvmt.product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true
						 
		 )
					   ORDER  BY product_id, date_time DESC, id DESC
                    ) FINAL_INVENTORY
                    LEFT JOIN (
                        SELECT product_purchased_mvmt.product_id, product_purchased_mvmt.date_time , product_purchased_mvmt.variation, product_purchased_mvmt.variation * (product_purchased_mvmt.buying_cost * product_purchased_mvmt.inventory_qty) as variation_value
                        FROM product_purchased_mvmt where product_purchased_mvmt.origin_restaurant_id = :origin_restaurant_id and deleted = false and type != \'inventory\' and date_time <= :D2
                    and product_purchased_mvmt.product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true
					 
		 )
				   ) as MVMTS on FINAL_INVENTORY.product_id = MVMTS.product_id and DATE(MVMTS.date_time) > DATE(FINAL_INVENTORY.date_time)
                    GROUP BY FINAL_INVENTORY.product_id,  FINAL_INVENTORY.inventory_buying_cost
            ) as FINAL_THEORICAL_STOCK 
        )  as FINAL ON FINAL.product_id = P.id
 WHERE P.origin_restaurant_id = :origin_restaurant_id  
              
        ) SUB_QUERY WHERE (SUB_QUERY.product_status = \'active\' OR SUB_QUERY.product_status = \'toInactive\' OR  SUB_QUERY.date_desactivation >= :D2)
		AND (SUB_QUERY.product_id >0) AND SUB_QUERY.category_id in (select id from product_categories where category_group_id in (select id from category_group where is_food_cost =true))';


        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('D1', $dateString);
        $stm->bindParam('D2', $lastDate);
        $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
        $stm->execute();
        $data = $stm->fetchAll();


        return $data[0]['valorization'];
    }
    
      public function getFinalAndInitialTotalStockValorization($filter, $currentRestaurantId)
    {
        $dateString = $filter['beginDate']." 00:00:00";
        $lastDate = $filter['lastDate']." 23:59:59";

        //var_dump($dateString,$lastDate);die;
        $sql = 'SELECT COALESCE(SUM(final_valorization),0)AS final_valorization,COALESCE(SUM(initial_valorization),0)AS initial_valorization   FROM ( 
 
        SELECT
        /* Product Id */   P.id as PRODUCT_ID,
        /* Category id */  PG.id as category_id,
	    /*Final valorization*/ COALESCE(FINAL.final_value,COALESCE(COALESCE(INITIAL.initial_variation_value,0) + COALESCE(ENTREE.in_variation_value,0) - COALESCE(SORTIE.out_variation_value,0),0 )) as final_valorization,
       /* Initial Stock */ COALESCE(INITIAL.initial_variation_value,0) as initial_valorization,
        PP.status as product_status,
        PP.deactivation_date as date_desactivation
        FROM public.product_purchased PP
        /* Category name */
        LEFT JOIN public.product P ON P.id = PP.id 
        LEFT JOIN public.product_categories PG ON PG.id = PP.product_category_id

        /* INITIAL */
        LEFT JOIN (   
           SELECT	INITIAL_THEORICAL_STOCK.product_id,
                INITIAL_THEORICAL_STOCK.last_inventory_date,

                CASE WHEN (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation)< 0 THEN 0 ELSE (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation) * INITIAL_THEORICAL_STOCK.inventory_buying_cost end  initial_variation_value,
                CASE WHEN (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation)< 0 THEN 0 ELSE (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation) END theorical_initial_stock
                FROM (
                SELECT MAX(INITIAL_INVENTORY.product_id) as product_id,
                /*MAX(INITIAL_INVENTORY.inventory_buying_cost) as inventory_buying_cost,*/
                INITIAL_INVENTORY.inventory_buying_cost as inventory_buying_cost,
                 MAX(INITIAL_INVENTORY.date_time) as last_inventory_date,
                 case when  MAX(INITIAL_INVENTORY.stock_qty) < 0 then 0 else MAX(INITIAL_INVENTORY.stock_qty) end as initial_stock,
                  COALESCE(SUM(MVMTS.variation),0) as variation, COALESCE(SUM(MVMTS.buying_value),0) as buying_value


                FROM (
                SELECT DISTINCT ON (product_id)
                id, product_id, date_time, stock_qty , (buying_cost / inventory_qty) as inventory_buying_cost
                FROM   product_purchased_mvmt
                where origin_restaurant_id = :origin_restaurant_id and deleted = false and type = \'inventory\' and date_time < :D1 and stock_qty is not null
                and product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true
				 
				)
                ORDER  BY product_id, date_time DESC, id DESC) INITIAL_INVENTORY
                LEFT JOIN (
                    SELECT product_purchased_mvmt.product_id, product_purchased_mvmt.date_time , product_purchased_mvmt.variation, (product_purchased_mvmt.buying_cost / product_purchased_mvmt.inventory_qty) as buying_value
                    FROM product_purchased_mvmt
                    where origin_restaurant_id = :origin_restaurant_id and deleted = false and type != \'inventory\' and date_time < :D1
                    and product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true
					 
					)
                ) as MVMTS on INITIAL_INVENTORY.product_id = MVMTS.product_id and DATE(MVMTS.date_time) > DATE(INITIAL_INVENTORY.date_time)
                GROUP BY INITIAL_INVENTORY.product_id, INITIAL_INVENTORY.inventory_buying_cost
            ) as INITIAL_THEORICAL_STOCK
        ) INITIAL on INITIAL.product_id = P.id



        /* ENTREES */
        LEFT JOIN (
     SELECT product_purchased_mvmt.product_id,
                   SUM(product_purchased_mvmt.variation) qty,
                   SUM(product_purchased_mvmt.variation * (product_purchased_mvmt.buying_cost / product_purchased_mvmt.inventory_qty)) as in_variation_value
            FROM product_purchased_mvmt where product_purchased_mvmt.origin_restaurant_id = :origin_restaurant_id and deleted = false and type in (\'transfer_in\', \'delivery\') and date_time >= :D1 and date_time <= :D2
            GROUP BY product_id
       
		
		
		) ENTREE ON ENTREE.product_id = P.id
		
		
		 /* Sorties */
        LEFT JOIN (
            SELECT  product_purchased_mvmt.product_id,
                SUM(ABS(product_purchased_mvmt.variation)) qty,
                SUM(ABS(product_purchased_mvmt.variation) * (product_purchased_mvmt.buying_cost / product_purchased_mvmt.inventory_qty)) as out_variation_value
            FROM product_purchased_mvmt where product_purchased_mvmt.origin_restaurant_id = :origin_restaurant_id and deleted = false and type in (\'transfer_out\', \'returns\') and date_time >= :D1 and date_time <= :D2
            GROUP BY product_id
          
        ) SORTIE ON SORTIE.product_id = P.id
		
		
		
		
        /* FINAL */
        LEFT JOIN (
          SELECT FINAL_THEORICAL_STOCK.product_id, FINAL_THEORICAL_STOCK.last_final_inventory_date,
                CASE WHEN (FINAL_THEORICAL_STOCK.final_stock + FINAL_THEORICAL_STOCK.variation) < 0 THEN 0 ELSE (FINAL_THEORICAL_STOCK.final_stock + FINAL_THEORICAL_STOCK.variation) END theorical_final_stock,
                 CASE WHEN (FINAL_THEORICAL_STOCK.final_stock + FINAL_THEORICAL_STOCK.variation) < 0 THEN 0 ELSE ((FINAL_THEORICAL_STOCK.final_stock + FINAL_THEORICAL_STOCK.variation) * FINAL_THEORICAL_STOCK.inventory_buying_cost) end final_value
                FROM (
                    SELECT MAX(FINAL_INVENTORY.product_id) as product_id,
                    MAX(FINAL_INVENTORY.id) as op_id,
                    MAX(FINAL_INVENTORY.date_time) as last_final_inventory_date,
                   CASE WHEN MAX(FINAL_INVENTORY.stock_qty) < 0 THEN 0 ELSE MAX(FINAL_INVENTORY.stock_qty) END as final_stock,
                    COALESCE(SUM(MVMTS.variation),0) as variation,
                    /*MAX(FINAL_INVENTORY.inventory_buying_cost) as inventory_buying_cost*/
                   FINAL_INVENTORY.inventory_buying_cost as inventory_buying_cost
                    FROM (
                        SELECT DISTINCT ON (product_id)
                        id, product_id, date_time, stock_qty, (buying_cost / inventory_qty) inventory_buying_cost
                        FROM   product_purchased_mvmt
                        where product_purchased_mvmt.origin_restaurant_id = :origin_restaurant_id and deleted = false and type = \'inventory\' and date_time <= :D2 and stock_qty is not null
                            and product_purchased_mvmt.product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true
						 
		 )
					   ORDER  BY product_id, date_time DESC, id DESC
                    ) FINAL_INVENTORY
                    LEFT JOIN (
                        SELECT product_purchased_mvmt.product_id, product_purchased_mvmt.date_time , product_purchased_mvmt.variation, product_purchased_mvmt.variation * (product_purchased_mvmt.buying_cost * product_purchased_mvmt.inventory_qty) as variation_value
                        FROM product_purchased_mvmt where product_purchased_mvmt.origin_restaurant_id = :origin_restaurant_id and deleted = false and type != \'inventory\' and date_time <= :D2
                    and product_purchased_mvmt.product_id in (select pp.id from product_purchased pp join product_categories pc on pp.product_category_id = pc.id join category_group cg on cg.id = pc.category_group_id where cg.is_food_cost = true
					 
		 )
				   ) as MVMTS on FINAL_INVENTORY.product_id = MVMTS.product_id and DATE(MVMTS.date_time) > DATE(FINAL_INVENTORY.date_time)
                    GROUP BY FINAL_INVENTORY.product_id,  FINAL_INVENTORY.inventory_buying_cost
            ) as FINAL_THEORICAL_STOCK 
        )  as FINAL ON FINAL.product_id = P.id
 WHERE P.origin_restaurant_id = :origin_restaurant_id  
              
        ) SUB_QUERY WHERE (SUB_QUERY.product_status = \'active\' OR SUB_QUERY.product_status = \'toInactive\' OR  SUB_QUERY.date_desactivation >= :D2)
		AND (SUB_QUERY.product_id >0) AND SUB_QUERY.category_id in (select id from product_categories where category_group_id in (select id from category_group where is_food_cost =true))';


        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('D1', $dateString);
        $stm->bindParam('D2', $lastDate);
        $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
        $stm->execute();
        $data = $stm->fetchAll();


        return $data[0];
    }
    public function getStockForProductsAtDate(\DateTime $date, $products = null, $idIndexed = false)
    {
        if (count($products)) {
            $dateString = $date->format('Y-m-d');

            $ids = $products;
            $restaurant = $this->restaurantService->getCurrentRestaurant();
            $inQuery = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT
               product_purchased.id product_id,
               COALESCE(INITIAL.theorical_initial_stock,0) as initial_stock,
              /* COALESCE(INITIAL_VALUE.buying_cost , product_purchased.buying_cost) initial_buying_cost,
               COALESCE(INITIAL_VALUE.usage_qty, product_purchased.usage_qty) initial_usage_qty,
               COALESCE(INITIAL_VALUE.label_unit_usage, product_purchased.label_unit_usage) initial_label_unit,
               COALESCE(INITIAL_VALUE.inventory_qty, product_purchased.inventory_qty) initial_inventory_qty,*/
               product_purchased.buying_cost initial_buying_cost,
               product_purchased.usage_qty initial_usage_qty,
               product_purchased.label_unit_usage initial_label_unit,
               product_purchased.inventory_qty initial_inventory_qty,
               COALESCE(INITIAL_VALUE.label_unit_inventory, product_purchased.label_unit_inventory) initial_label_unit_inventory,
               COALESCE(INITIAL_VALUE.label_unit_exped, product_purchased.label_unit_exped) initial_label_unit_exped
               FROM product_purchased

                LEFT JOIN(

                SELECT	INITIAL_THEORICAL_STOCK.product_id,
                INITIAL_THEORICAL_STOCK.last_inventory_date,
                case when (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation)<0 then 0 else (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation) end theorical_initial_stock FROM (
                SELECT MAX(INITIAL_INVENTORY.product_id) as product_id, MAX(INITIAL_INVENTORY.date_time) as last_inventory_date, MAX(INITIAL_INVENTORY.stock_qty) as initial_stock, COALESCE(SUM(MVMTS.variation),0) as variation  FROM (
                SELECT DISTINCT ON (product_id)
                id, product_id, date_time, stock_qty
                FROM   product_purchased_mvmt
                where origin_restaurant_id = ? and deleted = false and type = 'inventory' and date_time <= ? and stock_qty is not null
                ORDER  BY product_id, date_time DESC, id DESC) INITIAL_INVENTORY
                LEFT JOIN (
                    SELECT product_purchased_mvmt.product_id, product_purchased_mvmt.date_time , product_purchased_mvmt.variation, product_purchased_mvmt.variation * (product_purchased_mvmt.buying_cost * product_purchased_mvmt.inventory_qty) as variation_value
                    FROM product_purchased_mvmt where origin_restaurant_id = ? and deleted = false and type != 'inventory' and date_time <= ?
                ) as MVMTS on INITIAL_INVENTORY.product_id = MVMTS.product_id and DATE(MVMTS.date_time) > DATE(INITIAL_INVENTORY.date_time)
                GROUP BY INITIAL_INVENTORY.product_id
            ) as INITIAL_THEORICAL_STOCK
        ) INITIAL on INITIAL.product_id = product_purchased.id
        LEFT JOIN (
            SELECT DISTINCT ON (product_id)
            id, product_id, date_time, buying_cost, usage_qty, label_unit_usage, inventory_qty, label_unit_inventory, label_unit_exped
            FROM   product_purchased_mvmt
            where origin_restaurant_id = ? and deleted = false and date_time <= ?
            ORDER  BY product_id, date_time DESC, id
        ) INITIAL_VALUE on product_purchased.id = INITIAL_VALUE.product_id

                WHERE product_purchased.id in (".$inQuery.")";

            $stm = $this->em->getConnection()->prepare($sql);
            /*****/
            $stm->bindValue(1, $restaurant->getId(), \PDO::PARAM_INT);
            $stm->bindValue(2, $dateString, \PDO::PARAM_STR);
            $stm->bindValue(3, $restaurant->getId(), \PDO::PARAM_INT);
            $stm->bindValue(4, $dateString, \PDO::PARAM_STR);
            $stm->bindValue(5, $restaurant->getId(), \PDO::PARAM_INT);
            $stm->bindValue(6, $dateString, \PDO::PARAM_STR);
            /*****/

            foreach ($ids as $k => $id) {
                $stm->bindValue(($k + 7), $id);
            }
            $stm->execute();
            $result = $stm->fetchAll();
            if ($idIndexed) {
                $indexedIdResult = [];
                foreach ($result as $item) {
                    $item['initial_stock'] = round($item['initial_stock'], 2);
                    $indexedIdResult[$item['product_id']] = $item;
                }

                return $indexedIdResult;
            } else {
                return $result;
            }
        } else {
            return [];
        }
    }

    public function getCoefForPP(ProductPurchased $product, \DateTime $startDate, \DateTime $endDate, $ca, $loss = null)
    {
        $restaurant = $this->restaurantService->getCurrentRestaurant();
        //Consomation data
        $startDateYesterday = Utilities::getDateFromDate($startDate, -1);
        $consomation = $this->getConsomationFormProduct($product, $startDateYesterday, $endDate);
        if (count($consomation) == 0) {
            throw new NotFoundHttpException(
                "Product with Code ".$product->getExternalId()." is not found in the Portion Control"
            );
        }

        $consoReal = 0;
        if (is_null($loss)){
            $consoTheo = floatval($consomation['consomation']) ;
        }else{
            $consoTheo = floatval($consomation['consomation']) +
                floatval($consomation['pertes']);
        }

        $stockInitial = $this->getStockForProductInDate($product, $startDateYesterday);
        $stockFinal = $this->getStockForProductInDate($product, $endDate);
        if ($stockFinal['isRealStock']) {
            $consoReal = ($stockInitial['stock'] === null) ? 0 : floatval($stockInitial['stock']);
            $consoReal += $consomation['delivered_qty'];
            $consoReal += $consomation['transfer_in'];
            $consoReal -= $consomation['transfer_out'];
            //consoReal -= $consomation['pertes'];
            $consoReal -= $consomation['retours'];
            $consoReal -= ($stockFinal['stock'] === null) ? 0 : floatval($stockFinal['stock']);
            $finalStockExist = true;
        } else {
            $finalStockExist = false;
        }

        $realStock = is_null($stockFinal['stock']) ? null : (floatval($stockFinal['stock']));
        $theoStock = (floatval($stockFinal['stock']) < 0) ? 0 : floatval($stockFinal['stock']);
        $fixedCoef = $this->em->getRepository("Merchandise:OrderHelpFixedCoef")->findOneBy(
            array(
                'product' => $product,
                "originRestaurant" => $restaurant,
            )
        );
        if (is_null($fixedCoef)) {
            $fixed = false;
            if ($finalStockExist) {
                $type = 'real';
                $coeff = ($consoReal != 0) ? ($ca / $consoReal) : 0;
            } else {
                $type = 'theo';
                $coeff = ($consoTheo != 0) ? ($ca / $consoTheo) : 0;
            }
        } else {
            //Set the FIXED TYPE R/T
            if ($fixedCoef->getReal()) {
                $type = 'real';
            } else {
                $type = 'theo';
            }

            if (!is_null($fixedCoef->getCoef())) {
                $coeff = $fixedCoef->getCoef();
                $fixed = true;
            } else {
                $fixed = false;
                if ($fixedCoef->getReal()) {
                    $coeff = ($consoReal != 0) ? ($ca / $consoReal) : 0;
                } else {
                    $coeff = ($consoTheo != 0) ? ($ca / $consoTheo) : 0;
                }
            }
        }

        return array(
            'coef' => floatval($coeff),
            'conso_real' => floatval($consoReal),
            'conso_theo' => floatval($consoTheo),
            'finalStockExist' => $finalStockExist,
            'realStock' => floatval($realStock),
            'theoStock' => floatval($theoStock),
            'fixed' => $fixed,
            'type' => $type,
        );
    }

    /**
     * @param ProductPurchased $product
     * @param \DateTime        $startDate
     * @param \DateTime        $endDate
     * @return mixed
     * @throws \Exception
     *   return
     *     - delivered_qty
     *     - transfer_in
     *     - transfer_out
     *     - pertes
     *     - retours
     *     - consomation_non_transformed
     *     - consomation_transformed
     */
    public function getConsomationFormProduct(ProductPurchased $product, \DateTime $startDate, \DateTime $endDate)
    {
        //$sqlFile = $this->sqlRepo."/consomation.sql";
        $sqlFile = $this->sqlRepo."/consomation.v2.sql"; //v2
        $sql = file_get_contents($sqlFile);

        $starDateString = $startDate->format('Y-m-d 00:00:00');
        $endDateString = $endDate->format('Y-m-d 23:59:59');
        $productID = $product->getId();
        $restaurantID = $this->restaurantService->getCurrentRestaurant()->getId();

        try {
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('endDate', $endDateString, \PDO::PARAM_STR);
            $stm->bindParam('startDate', $starDateString, \PDO::PARAM_STR);
            $stm->bindParam('productID', $productID);
            $stm->bindParam('restaurantID', $restaurantID);
            $stm->execute();
            $result = $stm->fetch();

            //Begin v2
            if ($result) {
                $historicProductData = $this->getHistoricProduct($product, $endDate);

                $result['delivered_qty'] = $result['delivered_qty'] * $historicProductData['inventory_qty'];
                $result['transfer_in'] =
                    $result['transfer_in_inv'] +
                    ($result['transfer_in_exp'] * $historicProductData['inventory_qty']) +
                    ($result['transfer_in_use'] / $historicProductData['usage_qty']);

                $result['transfer_out'] =
                    $result['transfer_out_inv'] +
                    ($result['transfer_out_exp'] * $historicProductData['inventory_qty']) +
                    ($result['transfer_out_use'] / $historicProductData['usage_qty']);

                $result['retours'] =
                    $result['retours_inv'] +
                    ($result['retours_exp'] * $historicProductData['inventory_qty']) +
                    ($result['retours_use'] / $historicProductData['usage_qty']);

                // Commenting the return of two consumptions (transformed and non transformed ) since we got now one consumption out of query and in the inventory Unit

                /*$result['consomation_non_transformed'] = $result['consomation_non_transformed'] / $historicProductData['usage_qty'];
                $result['consomation_transformed'] = $result['consomation_transformed'] / $historicProductData['usage_qty'];*/
            }
            //end v2
        } catch (\Exception $e) {
            $this->logger->addError(get_class($this).": getConsomationFormProduct => ".$e->getMessage());
            throw $e;
        }

        return $result;
    }

    public function serializeProduct(ProductPurchased $p)
    {
        return array(
            'code' => $p->getExternalId(),
            'id' => $p->getId(),
            'name' => $p->getName(),
            'unitExp' => $p->getLabelUnitExped(),
            'unitInv' => $p->getLabelUnitInventory(),
            'unitUse' => $p->getLabelUnitUsage(),
            'inv_ratio' => $p->getInventoryQty(),
            'use_ratio' => $p->getUsageQty(),
            'stock' => $p->getStockCurrentQty(),
            'unit_price' => $p->getBuyingCost(),
            'category_id' => $p->getProductCategory()->getId(),
            'category_name' => $p->getProductCategory()->getName(),
            'eligible_cat' => $p->getProductCategory()->getEligible(),

        );
    }

    /**
     * @param ProductPurchased $productPurchased
     * @param \DateTime        $date
     * @return array |null
     *    - date (datetime)
     *    - id
     *    - name
     *    - active (boolean)
     *    - global_product_id
     *    - primary_item_id
     *    - secondary_item_id
     *    - supplier_id
     *    - product_category_id
     *    - external_id
     *    - buying_cost
     *    - status
     *    - label_unit_exped
     *    - label_unit_inventory
     *    - label_unit_usage
     *    - inventory_qty
     *    - usage_qty
     *    - id_item_inv
     */
    public function getHistoricProduct(ProductPurchased $productPurchased, \DateTime $date)
    {
        $currentRestaurantId = $this->restaurantService->getCurrentRestaurant()->getId();
        $sql = "SELECT * FROM product_purchased_historic_view
                WHERE
                  id = :product_id AND 
                  date::date <= :date
                  AND origin_restaurant_id = :currentRestaurantId
                ORDER BY date DESC
                LIMIT 1";

        $sql2 = "SELECT * FROM product_purchased_historic_view
                WHERE
                  id = :product_id AND origin_restaurant_id = :currentRestaurantId
                ORDER BY date DESC
                LIMIT 1";

        try {

            $dateS = $date->format('Y-m-d');
            $productId = $productPurchased->getId();

            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('date', $dateS);
            $stm->bindParam('product_id', $productId);
            $stm->bindParam('currentRestaurantId', $currentRestaurantId);
            $stm->execute();
            $data = $stm->fetch();
            if (!$data) {
                $stm = $this->em->getConnection()->prepare($sql2);
                $stm->bindParam('product_id', $productId);
                $stm->bindParam('currentRestaurantId', $currentRestaurantId);
                $stm->execute();
                $data = $stm->fetch();
            }

            $data['originalProduct'] = $productPurchased;
            $data['originalDate'] = $date;

            return $data;
        } catch (\Exception $e) {
            $this->logger->addError(get_class($this).":getHistoricProduct() => ".$e->getMessage());
            return null;
        }
    }

    /**
     * @param ProductPurchased $productPurchased
     * @param \DateTime        $date
     * @return ProductPurchased|\AppBundle\Merchandise\Entity\ProductPurchasedHistoric|null|object
     */
    public function getHistoricProductAsEntity(ProductPurchased $productPurchased, \DateTime $date)
    {
        $product = null;
        $histoP = $this->getHistoricProduct($productPurchased, $date);
        if ($histoP && isset($histoP['historical_product'])) {
            if (boolval($histoP['historical_product'])) {
                $product = $this->em->getRepository('Merchandise:ProductPurchasedHistoric')
                    ->find(intval($histoP['item_id']));
            } else {
                $product = $this->em->getRepository('Merchandise:ProductPurchased')
                    ->find(intval($histoP['id_item_inv']));
            }

            return $product;
        } else {
            return $productPurchased;
        }
    }
}
