<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 23/02/2016
 * Time: 11:07
 */

namespace AppBundle\Merchandise\Repository;

use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Gedmo\Translatable\TranslatableListener;

class OrderRepository extends EntityRepository
{

    public function getList($delivered = false, $criteria = null, $order = null, $offset = null, $limit = null)
    {

        $result = array();
        $queryBuilder = $this->createQueryBuilder('o')
            ->join("o.supplier", "s")
            ->join('o.employee', 'e');

        if ($delivered === true) {
            $queryBuilder->where("o.status = :delivered")
                ->setParameter("delivered", Order::DELIVERED);
        } else {
            $queryBuilder->where("o.status != :delivered")
                ->andWhere("o.status != :canceled")
                ->setParameter("canceled", Order::CANCELED)
                ->setParameter("delivered", Order::DELIVERED);
        }
        $totalQueryBuilder = clone $queryBuilder;
        if (isset($criteria['restaurant'])) {
            $totalQueryBuilder->andWhere("o.originRestaurant = :restaurant")
                ->setParameter("restaurant", $criteria['restaurant']);
        }
        $result['total'] = intval($totalQueryBuilder->select('count(o)')->getQuery()->getSingleScalarResult());

        if ($criteria) {
            if (Utilities::exist($criteria, 'supplier')) {
                $queryBuilder->andWhere("s.name like :supplier_name")
                    ->setParameter("supplier_name", "%".strtoupper($criteria['supplier'])."%");
            }

            if (Utilities::exist($criteria, 'num_order')) {
                $queryBuilder->andWhere("o.numOrder = :num_order ")
                    ->setParameter("num_order", $criteria['num_order']);
            }

            if (Utilities::exist($criteria, 'date_order')) {
                $queryBuilder->andWhere("o.dateOrder = :date_order ")
                    ->setParameter("date_order", date_create_from_format('d/m/Y', $criteria['date_order']));
            }

            if (Utilities::exist($criteria, 'date_delivery')) {
                $queryBuilder->andWhere("o.dateDelivery = :date_delivery ")
                    ->setParameter("date_delivery", date_create_from_format('d/m/Y', $criteria['date_delivery']));
            }

            if (Utilities::exist($criteria, 'status')) {
                $queryBuilder->andWhere("o.status = :status")
                    ->setParameter("status", $criteria['status']);
            }

            if (isset($criteria['restaurant'])) {
                $queryBuilder->andWhere("o.originRestaurant = :currentRestaurant")
                    ->setParameter("currentRestaurant", $criteria['restaurant']);
            }
        }

        $queryBuilderFilteredCount = clone $queryBuilder;
        $result['filteredCount'] = intval(
            $queryBuilderFilteredCount->select('count(o)')->getQuery()->getSingleScalarResult()
        );


        if ($order && Utilities::exist($order, 'col')) {
            $dir = 'ASC';
            if (Utilities::exist($order, 'dir')) {
                if (strtoupper($order['dir']) == 'DESC') {
                    $dir = 'DESC';
                }
            }

            switch ($order['col']) {
                case 'num_cmd':
                    $queryBuilder->orderBy('o.numOrder', $dir);
                    break;
                case 'supplier':
                    $queryBuilder->orderBy('s.name', $dir);
                    break;
                case 'date_order':
                    $queryBuilder->orderBy('o.dateOrder', $dir);
                    break;
                case 'date_delivery':
                    $queryBuilder->orderBy('o.dateDelivery', $dir);
                    break;
                case 'responsible':
                    $queryBuilder->orderBy('e.firstName', $dir);
                    break;
                case 'status':
                    $queryBuilder->orderBy('o.status', $dir);
                    break;
            }
        }
        if ($limit !== null) {
            $queryBuilder->setMaxResults(intval($limit));
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult(intval($offset));
        }
        $result['data'] = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    /**
     * @return null|Order
     */
    public function getOrderWithTheSoonerDelivery($restaurant = null)
    {
        $idOrder = $this->getIdOrderWithSoonerDelivery(true, $restaurant);
        if ($idOrder === null) {
            $idOrder = $this->getIdOrderWithSoonerDelivery(false, $restaurant);
        }
        if ($idOrder !== null) {
            return $this->find($idOrder);
        } else {
            return null;
        }
    }

    public function getIdOrderWithSoonerDelivery($futureDelivery = true, Restaurant $restaurant = null)
    {
        $conditions = "";
        if (isset($restaurant)) {
            $conditions = " o.origin_restaurant_id = :restaurantId AND ( o.status = :modified OR o.status = :sended )";
        } else {
            $conditions = " o.status = :modified OR o.status = :sended ";
        }

        if ($futureDelivery) {
            $sql = "SELECT o.id , MIN (o.datedelivery - NOW()) as diff FROM orders o WHERE ".$conditions." GROUP BY o.id ORDER BY diff ASC LIMIT 1";
        } else {
            $sql = "SELECT o.id , MIN (NOW() - o.datedelivery ) as diff FROM orders o WHERE ".$conditions." GROUP BY o.id ORDER BY diff ASC LIMIT 1";
        }
        try {
            $stm = $this->_em->getConnection()->prepare($sql);
            $stm->bindValue("modified", Order::MODIFIED, \PDO::PARAM_STR);
            $stm->bindValue("sended", Order::SENDED, \PDO::PARAM_STR);
            $stm->bindValue("restaurantId", $restaurant->getId());
            $stm->execute();
            $result = $stm->fetch(\PDO::FETCH_COLUMN);
            if ($result) {
                return $result;
            }
        } catch (\Exception $e) {
        }

        return null;
    }

    public function getPendingsOrderBySupplier(Supplier $supplier, $restaurant = null,$locale=null)
    {

        $qb = $this->createQueryBuilder("o")
            ->addSelect('l','partial p.{id,externalId}','partial s.{id,name}','partial e.{id,firstName,lastName}')
            ->join("o.lines","l")
            ->join("o.supplier","s")
            ->join("o.employee","e")
            ->join("l.product","p")
            ->where("o.supplier = :supplier")
            ->andWhere("o.status != :delivered")
            ->andWhere("o.status != :cancelled")
            ->setParameter("delivered", Order::DELIVERED)
            ->setParameter("cancelled", Order::CANCELED)
            ->setParameter("supplier", $supplier);

        if (isset($restaurant)) {
            $qb->andWhere("o.originRestaurant = :restaurant")
                ->setParameter("restaurant", $restaurant);
        }
        $query=$qb->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker');
        if(!$locale){
            $locale='fr';
        }
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);

        return $query->getResult();
    }

    public function getRejectedOrder($restaurant){
        $date=new \DateTime('today');
         $date=$date->format('Y-m-d');
        $qb = $this->createQueryBuilder("o")
            ->where("o.status = :rejected")
             ->andWhere("o.originRestaurant = :restaurant")
             ->andWhere("o.dateDelivery >= :datenow")
               ->setParameter("rejected", Order::REJECTED)
                 ->setParameter("restaurant", $restaurant)
                ->setParameter("datenow",  $date);
        $query=$qb->getQuery();
        return $query->getResult();
    }

    public function getRejectedOrders(){
        $date=new \DateTime('today');
        $date=$date->format('Y-m-d');
        $qb = $this->createQueryBuilder("o")
            ->where("o.status = :rejected")
            ->andWhere("o.updatedAt >= :datenow")
            ->setParameter("rejected", Order::REJECTED)
            ->setParameter("datenow",  $date);
        $query=$qb->getQuery();
        return $query->getResult();
    }

}
