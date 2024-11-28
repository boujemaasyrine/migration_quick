<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 02/03/2016
 * Time: 17:02
 */

namespace AppBundle\Merchandise\Repository;

use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;

class TransferRepository extends EntityRepository
{

    public function getList($currentRestaurant, $criteria, $order = null, $offset = null, $limit = null)
    {

        $qb = $this->createQueryBuilder('t');
        $qb->join('t.restaurant', 'r')
            ->join('t.employee', 'e')
            ->where("t.originRestaurant = :restaurant")
            ->setParameter("restaurant", $currentRestaurant)
            ->andWhere('1=1');


        $qb1 = clone $qb;
        $total = $qb1->select('count(t)')->getQuery()->getSingleScalarResult();

        //Filters
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'restaurant')) {
                $qb->andWhere("r.id = :destination ")
                    ->setParameter("destination", $criteria['restaurant']);
            }

            if (Utilities::exist($criteria, 'numTransfer')) {
                $qb->andWhere("t.numTransfer like :numTransfer ")
                    ->setParameter("numTransfer", "%".strtoupper($criteria['numTransfer'])."%");
            }

            if (Utilities::exist($criteria, 'type')) {
                $qb->andWhere("t.type like :type ")
                    ->setParameter("type", "%".$criteria['type']."%");
            }

            if (Utilities::exist($criteria, 'date_transfer_inf')) {
                $qb->andWhere("t.dateTransfer >= :minDate ")
                    ->setParameter("minDate", \DateTime::createFromFormat('d/m/Y', $criteria['date_transfer_inf']));
            }

            if (Utilities::exist($criteria, 'date_transfer_sup')) {
                $qb->andWhere("t.dateTransfer <= :maxDate ")
                    ->setParameter("maxDate", \DateTime::createFromFormat('d/m/Y', $criteria['date_transfer_sup']));
            }
        }

        $qb2 = clone $qb;
        $filtredTotal = $qb2->select("count(t)")->getQuery()->getSingleScalarResult();

        //Sort
        if ($order !== null && is_array($order) && count($order) > 0) {
            if (Utilities::exist($order, 'col')) {
                if (Utilities::exist($order, 'dir')) {
                    $orderDir = $order['dir'];
                } else {
                    $orderDir = 'asc';
                }

                $mapping = array(
                    'num' => 't.numTransfer',
                    'restaurant' => 'r.name',
                    'date' => 't.dateTransfer',
                    'responsible' => 'e.firstName',
                    'type' => 't.type',
                    'val' => 't.valorization',
                );
                $qb->orderBy($mapping[$order['col']], $orderDir);
            }
        }

        //limit & offset
        if ($limit !== null) {
            $qb->setMaxResults(intval($limit));
        }

        if ($offset !== null) {
            $qb->setFirstResult(intval($offset));
        }

        return array(
            'list' => $qb->getQuery()->getResult(),
            'total' => $total,
            'filtred' => $filtredTotal,
        );
    }

    public function getFiltredTransfers($filter, $type)
    {

        $conn = $this->_em->getConnection();
        $D1 = $filter['beginDate']; //  transform to D1-1j
        $D2 = $filter['endDate'];
        $sql = "SELECT
            T.id AS transferID,
            R.Id as RestaurantId,
            R.Name as RestaurantName,
            T.num_transfer as invoice,
            T.valorization as valorization,
            T.date_transfer as transferDate,
            T.type as type,
            R.code as code


            From public.transfer T
            LEFT JOIN public.restaurant R on R.id = T.restaurant_id

            where T.origin_restaurant_id = :origin_restaurant_id and T.date_transfer >= :D1 and T.date_transfer <= :D2 and T.type = :type
            ORDER BY T.type";

        // bind

        $stm = $conn->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('type', $type);
        $stm->bindParam("origin_restaurant_id", $filter["currentRestaurantId"]);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }

    /**
     * @param $criteria
     * @param $offset
     * @param $limit
     * @return array
     */
    public function getTransferBi($criteria, $offset, $limit)
    {
        $qb = $this->createQueryBuilder('e')
            ->join('e.originRestaurant', 'r')
            ->orderBy('r.code');

        //filtering
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (isset($criteria['restaurants'])) {
                /**
                 * @var Restaurant[] $restaurants
                 */
                $restaurants = $criteria['restaurants'];
                $codes = array();
                foreach ($restaurants as $restaurant) {
                    $codes[] = $restaurant->getCode();
                }
                $qb->andWhere('r.code in (:orId)')
                    ->setParameter('orId', $codes);
            }

            if (Utilities::exist($criteria, 'startDate') && Utilities::exist($criteria, 'endDate')) {
                $startDate = \DateTime::createFromFormat('d/m/Y', $criteria['startDate']);
                $startDate = $startDate->format('Y-m-d');

                $endDate = \DateTime::createFromFormat('d/m/Y', $criteria['endDate']);
                $endDate = $endDate->format('Y-m-d');

                $from = new \DateTime($startDate . " 00:00:00");
                $to = new \DateTime($endDate . " 23:59:59");
                if ($startDate != null && $endDate != null) {
                    $qb
                        ->andWhere('e.dateTransfer BETWEEN :from AND :to ')
                        ->setParameter('from', $from)
                        ->setParameter('to', $to);
                } elseif ($startDate != null && $endDate == null) {
                    $qb
                        ->andWhere('e.dateTransfer > :from')
                        ->setParameter('from', $from);
                } elseif ($startDate == null && $endDate != null) {
                    $qb
                        ->andWhere('e.dateTransfer < :to')
                        ->setParameter('to', $to);
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

        return $qb->getQuery()->getResult();
    }
}
