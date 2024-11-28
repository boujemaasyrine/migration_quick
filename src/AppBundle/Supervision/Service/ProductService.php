<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 29/02/2016
 * Time: 10:11
 */

namespace AppBundle\Supervision\Service;

use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\Supervision\Entity\ProductSupervision;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductService
{

    private $em;

    private $sqlRepo;

    public function __construct(EntityManager $em, $sqlRepo)
    {
        $this->em = $em;
        $this->sqlRepo = $sqlRepo;
    }

    public function getStockForProductInDate(
        ProductPurchased $productPurchased,
        \DateTime $date,
        Restaurant $restaurant
    ) {
        $sqlFile = $this->sqlRepo."/real_theo_stock_for_product_by_date_supervision.v2.sql";
        if (!file_exists($sqlFile)) {
            throw new \Exception();
        }

        $dateString = $date->format('Y-m-d');
        $id = $productPurchased->getId();
        $restaurantID = $restaurant->getId();

        $stm = $this->em->getConnection()->prepare(file_get_contents($sqlFile));
        $stm->bindParam('targetDate', $dateString, \PDO::PARAM_STR);

        $stm->bindParam('restaurant', $restaurantID);
        $stm->bindParam('product_id', $id);
        $stm->execute();
        $result = $stm->fetch();
        //begin v2
        if ($result) {
            $historicProductData = $this->getHistoricProduct($productPurchased, $date);
            $result['delivered_qty'] = $result['delivered_qty'] / $historicProductData['inventory_qty'];
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

        $sql = "SELECT * FROM product_purchased_historic_view
                WHERE
                  id = :product_id and
                  date::date <= :date
                ORDER BY date DESC
                LIMIT 1";

        $sql2 = "SELECT * FROM product_purchased_historic_view
                WHERE
                  id = :product_id
                ORDER BY date DESC
                LIMIT 1";

        try {
            $dateS = $date->format('Y-m-d');
            $productId = $productPurchased->getId();

            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('date', $dateS);
            $stm->bindParam('product_id', $productId);
            $stm->execute();
            $data = $stm->fetch();
            if (!$data) {
                $stm = $this->em->getConnection()->prepare($sql2);
                $stm->bindParam('product_id', $productId);
                $stm->execute();
                $data = $stm->fetch();
            }

            $data['originalProduct'] = $productPurchased;
            $data['originalDate'] = $date;

            return $data;
        } catch (\Exception $e) {
            //            $this->logger->addError(get_class($this) . ":getHistoricProduct() => " . $e->getMessage());
            return null;
        }
    }

    /**
     * @param ProductPurchased $product
     * @param \DateTime        $startDate
     * @param \DateTime        $endDate
     * @param Restaurant       $restaurant
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
    public function getConsomationFormProduct(
        ProductPurchased $product,
        \DateTime $startDate,
        \DateTime $endDate,
        Restaurant $restaurant
    ) {

        $sqlFile = $this->sqlRepo."/consomation_supervision.sql";
        $sql = file_get_contents($sqlFile);

        $starDateString = $startDate->format('Y-m-d');
        $endDateString = $endDate->format('Y-m-d');
        $productID = $product->getId();
        $restaurantID = $restaurant->getId();

        try {
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('endDate', $endDateString, \PDO::PARAM_STR);
            $stm->bindParam('startDate', $starDateString, \PDO::PARAM_STR);
            $stm->bindParam('productID', $productID);
            $stm->bindParam('productID', $productID);
            $stm->bindParam('restaurant_id', $restaurantID);
            $stm->execute();
            $result = $stm->fetch();
        } catch (\Exception $e) {
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
            'unit_price' => $p->getBuyingCost(),
            'category_id' => $p->getProductCategory()->getId(),
            'category_name' => $p->getProductCategory()->getName(),
            'eligible_cat' => $p->getProductCategory()->getEligible(),
        );
    }


    public function getCoefForPP(
        ProductPurchased $product,
        \DateTime $startDate,
        \DateTime $endDate,
        $ca,
        Restaurant $restaurant
    ) {

        //Consomation data
        $consomation = $this->getConsomationFormProduct($product, $startDate, $endDate, $restaurant);

        if (count($consomation) == 0) {
            throw new NotFoundHttpException(
                "Product with Code ".$product->getExternalId()." is not found in the Portion Control"
            );
        }

        $consoReal = 0;
        $consoTheo = floatval($consomation['consomation_non_transformed']) + floatval(
            $consomation['consomation_transformed']
        );

        $stockInitial = $this->getStockForProductInDate($product, $startDate, $restaurant);
        $stockFinal = $this->getStockForProductInDate($product, $endDate, $restaurant);

        if ($stockFinal['isRealStock']) {
            $consoReal = ($stockInitial['stock'] === null) ? 0 : intval($stockInitial['stock']);
            $consoReal += $consomation['delivered_qty'];
            $consoReal += $consomation['transfer_in'];
            $consoReal -= $consomation['transfer_out'];
            $consoReal -= $consomation['pertes'];
            $consoReal -= $consomation['retours'];
            $consoReal -= ($stockFinal['stock'] === null) ? 0 : intval($stockFinal['stock']);
            $finalStockExist = true;
        } else {
            $finalStockExist = false;
        }

        $realStock = ($stockFinal['stock'] == null) ? null : (intval($stockFinal['stock']));
        $theoStock = intval($stockFinal['stock']);

        $fixed = false;
        if ($finalStockExist) {
            $type = 'real';
            $coeff = ($consoReal != 0) ? ($ca / $consoReal) : 0;
        } else {
            $type = 'theo';
            $coeff = ($consoTheo != 0) ? ($ca / $consoTheo) : 0;
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

    public function getStockForProductsAtDate(\DateTime $date, $products = null, $restaurant)
    {
        $dateString = $date->format('Y-m-d');
        $ids = $products;
        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT
               product_purchased.id product_id,
               _INITIAL_THEORICAL_STOCK.last_inventory_date,
               COALESCE(_INITIAL_THEORICAL_STOCK.theorical_initial_stock,0) as initial_stock,

               COALESCE(INITIAL_VALUE.buying_cost , product_purchased.buying_cost) initial_buying_cost,
               COALESCE(INITIAL_VALUE.usage_qty, product_purchased.usage_qty) initial_usage_qty,
               COALESCE(INITIAL_VALUE.label_unit_usage, product_purchased.label_unit_usage) initial_label_unit,
               COALESCE(INITIAL_VALUE.inventory_qty, product_purchased.inventory_qty) initial_inventory_qty,
               COALESCE(INITIAL_VALUE.label_unit_inventory, product_purchased.label_unit_inventory) initial_label_unit_inventory,
               COALESCE(INITIAL_VALUE.label_unit_exped, product_purchased.label_unit_exped) initial_label_unit_exped

               FROM product_purchased left join
        (
        SELECT	INITIAL_THEORICAL_STOCK.product_id,
            INITIAL_THEORICAL_STOCK.last_inventory_date,
            (INITIAL_THEORICAL_STOCK.initial_stock + INITIAL_THEORICAL_STOCK.variation) theorical_initial_stock
            FROM (
            SELECT MAX(INITIAL_INVENTORY.product_id) as product_id, MAX(INITIAL_INVENTORY.date_time) as last_inventory_date, MAX(INITIAL_INVENTORY.stock_qty) as initial_stock, COALESCE(SUM(MVMTS.variation),0) as variation  FROM (
            SELECT DISTINCT ON (product_id)
            id, product_id, date_time, stock_qty
            FROM   product_purchased_mvmt
            where type = 'inventory' and date_time <= ? and origin_restaurant_id = ?
            ORDER  BY product_id, date_time DESC, id) INITIAL_INVENTORY
            LEFT JOIN (
                SELECT product_purchased_mvmt.product_id,
                       product_purchased_mvmt.date_time,
                       product_purchased_mvmt.variation,
                       product_purchased_mvmt.variation * (product_purchased_mvmt.buying_cost * product_purchased_mvmt.inventory_qty) as variation_value
                FROM product_purchased_mvmt where type != 'inventory' and date_time <= ? and origin_restaurant_id = ?
            ) as MVMTS on INITIAL_INVENTORY.product_id = MVMTS.product_id and MVMTS.date_time > INITIAL_INVENTORY.date_time
            GROUP BY INITIAL_INVENTORY.product_id
        ) as INITIAL_THEORICAL_STOCK ) as _INITIAL_THEORICAL_STOCK
        ON product_purchased.id = _INITIAL_THEORICAL_STOCK.product_id

        LEFT JOIN (
            SELECT DISTINCT ON (product_id)
            id, product_id, date_time, buying_cost, usage_qty, label_unit_usage, inventory_qty, label_unit_inventory, label_unit_exped
            FROM   product_purchased_mvmt
            where date_time <= ? and origin_restaurant_id = ?
            ORDER  BY product_id, date_time DESC, id
        ) INITIAL_VALUE on product_purchased.id = INITIAL_VALUE.product_id

        WHERE  product_purchased.id in (".$inQuery.")";

        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindValue(1, $dateString, \PDO::PARAM_STR);
        $stm->bindValue(2, $restaurant);
        $stm->bindValue(3, $dateString, \PDO::PARAM_STR);
        $stm->bindValue(4, $restaurant);
        $stm->bindValue(5, $dateString, \PDO::PARAM_STR);
        $stm->bindValue(6, $restaurant);
        foreach ($ids as $k => $id) {
            $stm->bindValue(($k + 7), $id);
        }
        $stm->execute();
        $result = $stm->fetchAll();

        return $result;
    }

    public function getInitialStockValorizationAtDate(\DateTime $date, $restaurant)
    {
        $dateString = $date->format('Y-m-d 00:00:00');
        $restaurantId = $restaurant->getId();
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
                where  origin_restaurant_id=:restaurant and type = \'inventory\' and DATE(date_time) < :D1 and stock_qty is not null
                ORDER  BY product_id, date_time DESC, id DESC) INITIAL_INVENTORY
                LEFT JOIN (
                    SELECT product_purchased_mvmt.product_id, product_purchased_mvmt.date_time , product_purchased_mvmt.variation, (product_purchased_mvmt.buying_cost / product_purchased_mvmt.inventory_qty) as buying_value
                    FROM product_purchased_mvmt where origin_restaurant_id=:restaurant and type != \'inventory\' and DATE(date_time) < :D1
                ) as MVMTS on INITIAL_INVENTORY.product_id = MVMTS.product_id and DATE(MVMTS.date_time) > DATE(INITIAL_INVENTORY.date_time)
                GROUP BY INITIAL_INVENTORY.product_id, INITIAL_INVENTORY.inventory_buying_cost
            ) as INITIAL_THEORICAL_STOCK) as SUB_QUERY
            ';

        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('D1', $dateString);
        $stm->bindParam('restaurant', $restaurantId);

        $stm->execute();
        $data = $stm->fetchAll();

        return $data[0]['valorization'];
    }

    public function getFinalStockValorizationAtDate(\DateTime $date, $restaurant)
    {
        $dateString = $date->format('Y-m-d 23:59:59');
        $restaurantId = $restaurant->getId();
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
                    /*MAX(FINAL_INVENTORY.inventory_buying_cost) as inventory_buying_cost*/
                   FINAL_INVENTORY.inventory_buying_cost as inventory_buying_cost
                    FROM (
                        SELECT DISTINCT ON (product_id)
                        id, product_id, date_time, stock_qty, (buying_cost / inventory_qty) inventory_buying_cost
                        FROM   product_purchased_mvmt
                        where origin_restaurant_id=:restaurant and type = \'inventory\' and DATE(date_time) <= :D2 and stock_qty is not null
                        ORDER  BY product_id, date_time DESC, id DESC
                    ) FINAL_INVENTORY
                    LEFT JOIN (
                        SELECT product_purchased_mvmt.product_id, product_purchased_mvmt.date_time , product_purchased_mvmt.variation, product_purchased_mvmt.variation * (product_purchased_mvmt.buying_cost * product_purchased_mvmt.inventory_qty) as variation_value
                        FROM product_purchased_mvmt where origin_restaurant_id=:restaurant and type != \'inventory\' and DATE(date_time) <= :D2
                    ) as MVMTS on FINAL_INVENTORY.product_id = MVMTS.product_id and DATE(MVMTS.date_time) > DATE(FINAL_INVENTORY.date_time)
                    GROUP BY FINAL_INVENTORY.product_id,  FINAL_INVENTORY.inventory_buying_cost
            ) as FINAL_THEORICAL_STOCK) as SUB_QUERY';


        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('D2', $dateString);
        $stm->bindParam('restaurant', $restaurantId);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data[0]['valorization'];
    }
}
