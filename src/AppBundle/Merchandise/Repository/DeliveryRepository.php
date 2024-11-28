<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 29/02/2016
 * Time: 17:32
 */

namespace AppBundle\Merchandise\Repository;

use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

class DeliveryRepository extends EntityRepository
{
    public function getList($criteria = null, $order = null, $offset = null, $limit = null, $onlyList = false)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->join('d.order', 'o', Join::WITH)
            ->join('o.supplier', 's')
            ->where('1=1');
        if (!$onlyList) {
            $qb1 = clone $qb;
            if (isset($criteria['restaurant'])) {
                $qb1->andWhere(":restaurant = d.originRestaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
            $total = $qb1->select('count(d)')->getQuery()->getSingleScalarResult();
        }

        //Filters
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'supplier')) {
                $qb->andWhere("s.name like :supplier ")
                    ->setParameter("supplier", "%".$criteria['supplier']."%");
            }

            if (Utilities::exist($criteria, 'delivery_date_min')) {
                $qb->andWhere("d.date >= :delivery_date_min ")
                    ->setParameter("delivery_date_min", $criteria['delivery_date_min']);
            }


            if (Utilities::exist($criteria, 'delivery_date_max')) {
                $qb->andWhere("d.date <= :delivery_date_max ")
                    ->setParameter("delivery_date_max", $criteria['delivery_date_max']);
            }

            if (Utilities::exist($criteria, 'num_order')) {
                $qb->andWhere("o.numOrder like :num_order ")
                    ->setParameter("num_order", "%".$criteria['num_order']."%");
            }

            if (Utilities::exist($criteria, 'num_delivery')) {
                $qb->andWhere("d.deliveryBordereau like :num_delivery ")
                    ->setParameter("num_delivery", "%".$criteria['num_delivery']."%");
            }

            if (Utilities::exist($criteria, 'min_valorization')) {
                $qb->andWhere("d.valorization >= :min_valorization ")
                    ->setParameter("min_valorization", $criteria['min_valorization']);
            }

            if (Utilities::exist($criteria, 'max_valorization')) {
                $qb->andWhere("d.valorization <= :max_valorization ")
                    ->setParameter("max_valorization", $criteria['max_valorization']);
            }

            if (isset($criteria['restaurant'])) {
                $qb->andWhere(":restaurant = d.originRestaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
        }
        if (!$onlyList) {
            $qb2 = clone $qb;
            $filtredTotal = $qb2->select("count(d)")->getQuery()->getSingleScalarResult();
        }

        //Sort
        if ($order !== null && is_array($order) && count($order) > 0) {
            if (Utilities::exist($order, 'col')) {
                if (Utilities::exist($order, 'dir')) {
                    $orderDir = $order['dir'];
                } else {
                    $orderDir = 'asc';
                }

                switch ($order['col']) {
                    case 'num_order':
                        $qb->orderBy('o.numOrder', $orderDir);
                        break;
                    case 'supplier':
                        $qb->orderBy('s.name', $orderDir);
                        break;
                    case 'order_date':
                        $qb->orderBy('o.dateOrder', $orderDir);
                        break;
                    case 'num_delivery':
                        $qb->orderBy('d.deliveryBordereau', $orderDir);
                        break;
                    case 'delivery_date':
                        $qb->orderBy('d.date', $orderDir);
                        break;
                    case 'valorization':
                        $qb->orderBy('d.valorization', $orderDir);
                        break;
                    case 'responsible':
                        $qb->orderBy('d.employee', $orderDir);
                        break;
                }
            }
        }

        //limit & offset
        if ($limit !== null) {
            $qb->setMaxResults(intval($limit));
        }

        if ($offset !== null) {
            $qb->setFirstResult(intval($offset));
        }

        if ($onlyList) {
            return $qb->getQuery()->getResult();
        } else {
            return array(
                'list' => $qb->getQuery()->getResult(),
                'total' => $total,
                'filtred' => $filtredTotal,
            );
        }
    }

    public function getFiltredDeliveries($filter)
    {

        $conn = $this->_em->getConnection();
        $D1 = $filter['beginDate']; //  transform to D1-1j
        $D2 = $filter['endDate'];
        $sql = "SELECT
                    D.id AS deliveryId,
                    S.Id as SupplierId,
                    S.Name as SupplierName,
                    D.deliverybordereau as invoice,
                    D.valorization as valorization,
                    D.date as deliveryDate


                    From public.delivery D
                    LEFT JOIN public.orders O on O.id = D.order_id
                    LEFT JOIN public.supplier S ON S.id = O.supplier_id

                    where D.origin_restaurant_id = :origin_restaurant_id and D.date >= :D1 and D.date <= :D2";

        // bind

        $stm = $conn->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam("origin_restaurant_id", $filter["currentRestaurantId"]);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }
}
