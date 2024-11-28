<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 31/03/2016
 * Time: 14:55
 */

namespace AppBundle\Financial\Repository;

use AppBundle\Financial\Entity\Expense;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\Utilities;

class ExpenseRepository extends \Doctrine\ORM\EntityRepository
{

    public function getExpensesFiltredOrdered($criteria, $order, $offset, $limit, $onlyList = false)
    {

        $queryBuilder = $this->createQueryBuilder('e');

        if (!$onlyList) {
            $qb1 = clone $queryBuilder;
            if (isset($criteria['restaurant'])) {
                $qb1->andWhere(":restaurant = e.originRestaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
            $total = $qb1->select('count(e)')
                ->getQuery()->getSingleScalarResult();
        }

        //filtering

        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'expense_search[label')) {
                $queryBuilder->andWhere("e.sousGroup = :sousGroup ")
                    ->setParameter("sousGroup", $criteria['expense_search[label']);
            }
            if (Utilities::exist($criteria, 'expense_search[group')) {
                $queryBuilder->andWhere("e.groupExpense = :group ")
                    ->setParameter("group", $criteria['expense_search[group']);
            }
            if (Utilities::exist($criteria, 'expense_search[startDate') && Utilities::exist(
                $criteria,
                'expense_search[endDate'
            )) {
                $startDate = \DateTime::createFromFormat('d/m/Y', $criteria['expense_search[startDate']);
                $startDate = $startDate->format('Y-m-d');

                $endDate = \DateTime::createFromFormat('d/m/Y', $criteria['expense_search[endDate']);
                $endDate = $endDate->format('Y-m-d');

                $from = new \DateTime($startDate." 00:00:00");
                $to = new \DateTime($endDate." 23:59:59");
                $queryBuilder
                    ->andWhere('e.dateExpense BETWEEN :from AND :to ')
                    ->setParameter('from', $from)
                    ->setParameter('to', $to);
            }
            if (Utilities::exist($criteria, 'expense_search[responsible')) {
                $queryBuilder->andWhere("e.responsible = :responsible ")
                    ->setParameter("responsible", $criteria['expense_search[responsible']);
            }

            if (isset($criteria['restaurant'])) {
                $queryBuilder->andWhere(":restaurant = e.originRestaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
        }

        if (!$onlyList) {
            $qb2 = clone $queryBuilder;
            $filtredTotal = $qb2->select('count(e)')
                ->getQuery()->getSingleScalarResult();
        }

        //ordering
        //        if ($order !== null && is_array($order) && count($order) > 0) {
        //            if (Utilities::exist($order, 'col')) {
        //
        //                if (Utilities::exist($order, 'dir')) {
        //                    $orderDir = $order['dir'];
        //                } else {
        //                    $orderDir = 'asc';
        //                }
        //                switch ($order['col']) {
        //                    case 'ref' :
        //                        $queryBuilder->orderBy('e.reference', $orderDir);
        //                        break;
        //                    case 'label' :
        //                        $queryBuilder->orderBy('e.label', $orderDir);
        //                        break;
        //                    case 'amount' :
        //                        $queryBuilder->orderBy('e.amount', $orderDir);
        //                        break;
        //                    case 'owner' :
        //                        $queryBuilder->orderBy('e.responsible', $orderDir);
        //                        break;
        //                }
        //            }
        //        }


        $queryBuilder->orderBy('e.dateExpense', 'DESC')
            ->addOrderBy('e . groupExpense')
            ->addOrderBy('e . reference', 'DESC');

        if ($limit !== null) {
            $queryBuilder->setMaxResults(intval($limit));
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult(intval($offset));
        }

        if ($onlyList) {
            return $queryBuilder->getQuery()->getResult();
        } else {
            return array(
                'list' => $queryBuilder->getQuery()->getResult(),
                'total' => $total,
                'filtred' => $filtredTotal,
            );
        }
    }

    // Get All Expense Grouped by Sub Group if $subGroup is true

    /**
     * @param \DateTime $date
     * @param bool      $subGroup
     * @return $result
     */
    public function getAllExpenseByDate($date, $subGroup = false, $currentRestaurant = null)
    {

        $queryBuilder = $this->createQueryBuilder('e');

        $queryBuilder->where('e.dateExpense = :date')
            ->setParameter('date', $date);
        $queryBuilder->andWhere('e.sousGroup != :chestError')
            ->setParameter('chestError', Expense::ERROR_CHEST);
        $queryBuilder->andWhere('e.sousGroup != :cashError')
            ->setParameter('cashError', Expense::ERROR_CASHBOX);

        if ($currentRestaurant != null) {
            $queryBuilder->andWhere("e.originRestaurant = :restaurant")
                ->setParameter("restaurant", $currentRestaurant);
        }

        if ($subGroup) {
            $queryBuilder->groupBy('e.sousGroup');
            $queryBuilder->select(
                'SUM(e.amount) as totalAmount, e.sousGroup as subGroup,
                MAX(e.groupExpense) as groupExpense'
            );
            $queryBuilder->orderBy('groupExpense, subGroup');

            return $queryBuilder->getQuery()->getResult();
        }

        $queryBuilder->select('Sum(e.amount) as totalAmount');
        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getTotalChestErrorExpenseByDate($date, Restaurant $currentRestaurant = null)
    {
        $queryBuilder = $this->createQueryBuilder('e');

        $queryBuilder->where('e.dateExpense = :date')
            ->setParameter('date', $date);

        $queryBuilder->andWhere('e.sousGroup = :chestError')
            ->setParameter('chestError', Expense::ERROR_CHEST);

        if ($currentRestaurant != null) {
            $queryBuilder->andWhere("e.originRestaurant = :restaurant")
                ->setParameter("restaurant", $currentRestaurant);
        }

        $queryBuilder->select('SUM(e.amount) as totalAMount');
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }

    public function getTotalCashBoxErrorExpenseByDate($date, Restaurant $currentRestaurant = null)
    {
        $queryBuilder = $this->createQueryBuilder('e');

        $queryBuilder->where('e.dateExpense = :date')
            ->setParameter('date', $date);

        $queryBuilder->andWhere('e.sousGroup = :chestError')
            ->setParameter('chestError', Expense::ERROR_CASHBOX);

        if ($currentRestaurant != null) {
            $queryBuilder->andWhere("e.originRestaurant = :restaurant")
                ->setParameter("restaurant", $currentRestaurant);
        }

        $queryBuilder->select('SUM(e.amount) as totalAMount');
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }

    /********************
     *
     * Supervision Section
     ****************************/

    public function getSupervisionTotalChestErrorExpenseByDate($date, $restaurants = null)
    {
        $queryBuilder = $this->createQueryBuilder('e');

        if ($restaurants and count($restaurants) > 0) {
            $restaurantsIds = array();

            foreach ($restaurants as $restaurant) {
                $restaurantsIds[] = $restaurant->getId();
            }
            $queryBuilder
                ->andWhere('e.originRestaurant IN (:restaurants)')
                ->setParameter('restaurants', $restaurantsIds);
        }

        $queryBuilder->andWhere('e.dateExpense = :date')
            ->setParameter('date', $date);

        $queryBuilder->andWhere('e.sousGroup = :chestError')
            ->setParameter('chestError', Expense::ERROR_CHEST);

        $queryBuilder->select('SUM(e.amount) as totalAMount');
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }

    public function getSupervisionTotalCashBoxErrorExpenseByDate($date, $restaurants = null)
    {
        $queryBuilder = $this->createQueryBuilder('e');

        if ($restaurants and count($restaurants) > 0) {
            $restaurantsIds = array();

            foreach ($restaurants as $restaurant) {
                $restaurantsIds[] = $restaurant->getId();
            }
            $queryBuilder
                ->andWhere('e.originRestaurant IN (:restaurants)')
                ->setParameter('restaurants', $restaurantsIds);
        }

        $queryBuilder->andWhere('e.dateExpense = :date')
            ->setParameter('date', $date);

        $queryBuilder->andWhere('e.sousGroup = :chestError')
            ->setParameter('chestError', Expense::ERROR_CASHBOX);

        $queryBuilder->select('SUM(e.amount) as totalAMount');
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }

    /**
     * @param $criteria
     * @param $offset
     * @param $limit
     * @return array
     */
    public function getExpensesBi($criteria, $offset, $limit)
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->join('e.originRestaurant', 'r')
            ->orderBy('r.code')
            ->addOrderBy('e.dateExpense')
        ;

        //filtering
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (isset($criteria['restaurants'])) {
                /**
                 * @var Restaurant[] $restaurants
                 */
                $restaurants = $criteria['restaurants'];
                $codes = array();
                foreach($restaurants as $restaurant){
                    $codes[] = $restaurant->getCode();
                }
                $queryBuilder->andWhere('r.code in (:orId)')
                    ->setParameter('orId',  $codes);
            }

            if (Utilities::exist($criteria, 'startDate') && Utilities::exist($criteria, 'endDate')) {
                $startDate = \DateTime::createFromFormat('d/m/Y', $criteria['startDate']);
                $startDate = $startDate->format('Y-m-d');

                $endDate = \DateTime::createFromFormat('d/m/Y', $criteria['endDate']);
                $endDate = $endDate->format('Y-m-d');

                $from = new \DateTime($startDate . " 00:00:00");
                $to = new \DateTime($endDate . " 23:59:59");
                if ($startDate != null && $endDate != null) {
                    $queryBuilder
                        ->andWhere('e.dateExpense BETWEEN :from AND :to ')
                        ->setParameter('from', $from)
                        ->setParameter('to', $to);
                } elseif ($startDate != null && $endDate == null) {
                    $queryBuilder
                        ->andWhere('e.dateExpense > :from')
                        ->setParameter('from', $from);
                } elseif ($startDate == null && $endDate != null) {
                    $queryBuilder
                        ->andWhere('e.dateExpense < :to')
                        ->setParameter('to', $to);
                }
            }
        }

        $queryBuilder->orderBy('e.dateExpense', 'DESC');

        if ($limit !== null) {
            $queryBuilder->setMaxResults(intval($limit));
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult(intval($offset));
        }

        $results = $queryBuilder->getQuery()->getResult();

        return $results;
    }
}
