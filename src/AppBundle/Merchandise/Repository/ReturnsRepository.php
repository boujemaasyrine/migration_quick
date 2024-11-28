<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 04/03/2016
 * Time: 12:45
 */

namespace AppBundle\Merchandise\Repository;

use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;

class ReturnsRepository extends EntityRepository
{

    public function getList(
        $currentRestaurant,
        $criteria,
        $order = null,
        $offset = null,
        $limit = null,
        $onlyList = false
    ) {
        $qb = $this->createQueryBuilder('t');
        $qb->join('t.supplier', 's')
            ->join('t.employee', 'e')
            ->where("t.originRestaurant = :currentRestaurant")
            ->setParameter("currentRestaurant", $currentRestaurant);

        if (!$onlyList) {
            $qb1 = clone $qb;
            $total = $qb1->select('count(t)')->getQuery()->getSingleScalarResult();
        }

        //Filters
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'supplier')) {
                $qb->andWhere("s.name like :supplier ")
                    ->setParameter("supplier", "%".strtoupper($criteria['supplier'])."%");
            }

            if (Utilities::exist($criteria, 'date')) {
                $qb->andWhere("t.date = :date ")
                    ->setParameter("date", \DateTime::createFromFormat("d/m/Y", $criteria['date']));
            }
        }

        if (!$onlyList) {
            $qb2 = clone $qb;
            $filtredTotal = $qb2->select("count(t)")->getQuery()->getSingleScalarResult();
        }

        //Sort
        if ($order !== null && is_array($order) && count($order) > 0) {
            if (Utilities::exist($order, 'col')) {
                if (Utilities::exist($order, 'dir')) {
                    $orderDir = $order['dir'];
                } else {
                    $orderDir = 'asc';
                }

                $mapping = [
                    'supplier' => 's.name',
                    'date' => 't.date',
                    'responsible' => 'e.firstName',
                    'valorization' => 't.valorization',
                ];
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
        if (!$onlyList) {
            return array(
                'list' => $qb->getQuery()->getResult(),
                'total' => $total,
                'filtred' => $filtredTotal,
            );
        } else {
            return $qb->getQuery()->getResult();
        }
    }

    public function getFiltredReturns($filter)
    {

        $conn = $this->_em->getConnection();
        $D1 = $filter['beginDate']; //  transform to D1-1j
        $D2 = $filter['endDate'];
        $sql = "SELECT
                    R.id AS returnID,
                    S.Id as SupplierId,
                    S.Name as SupplierName,
                    R.valorization as valorization,
                    R.date as returnDate


                    From public.returns R
                    LEFT JOIN public.Supplier S on S.id = R.supplier_id

                    where R.origin_restaurant_id = :origin_restaurant_id and R.date >= :D1 and R.date <= :D2";

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
