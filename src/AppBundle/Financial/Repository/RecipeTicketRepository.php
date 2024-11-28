<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 07/04/2016
 * Time: 14:39
 */

namespace AppBundle\Financial\Repository;

use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;

class RecipeTicketRepository extends EntityRepository
{

    public function getRecipeTicketsFilteredOrdered(
        $criteria,
        $order,
        $offset,
        $limit,
        $search = null,
        $onlyList = false
    ) {

        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->leftJoin('e.owner', 'o');

        $queryBuilder->andWhere('e.deleted = :false OR e.deleted IS NULL')
            ->setParameter('false', false);

        if (!$onlyList) {
            $qb1 = clone $queryBuilder;
            if (isset($criteria['restaurant'])) {
                $qb1->andWhere(":restaurant = e.originRestaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
            $total = $qb1->select('count(e)')
                ->getQuery()->getSingleScalarResult();
        }

        if ($search) {
            $queryBuilder
                ->andWhere(
                    '(UPPER(o.firstName) like :search or UPPER(o.lastName) like :search or STRING(e.id) like :search or STRING(e.amount) like :search) '
                )
                ->setParameter('search', '%'.strtoupper($search).'%');
        }

        //filtering
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'recipe_ticket_search[label')) {
                $queryBuilder->andWhere("e.label = :label ")
                    ->setParameter("label", $criteria['recipe_ticket_search[label']);
            }

            if (Utilities::exist($criteria, 'recipe_ticket_search[owner')) {
                $queryBuilder->andWhere("o.id = :owner ")
                    ->setParameter("owner", $criteria['recipe_ticket_search[owner']);
            }

            if (Utilities::exist($criteria, 'recipe_ticket_search[startDate') && Utilities::exist(
                $criteria,
                'recipe_ticket_search[endDate'
            )) {
                $startDate = \DateTime::createFromFormat('d/m/Y', $criteria['recipe_ticket_search[startDate']);
                $startDate = $startDate->format('Y-m-d');

                $endDate = \DateTime::createFromFormat('d/m/Y', $criteria['recipe_ticket_search[endDate']);
                $endDate = $endDate->format('Y-m-d');

                $from = new \DateTime($startDate." 00:00:00");
                $to = new \DateTime($endDate." 23:59:59");
                $queryBuilder
                    ->andWhere('e.date BETWEEN :from AND :to ')
                    ->setParameter('from', $from)
                    ->setParameter('to', $to);
            }

            if (Utilities::exist($criteria, 'recipe_ticket_search[startDate') && !Utilities::exist(
                $criteria,
                'recipe_ticket_search[endDate'
            )) {
                $startDate = \DateTime::createFromFormat('d/m/Y', $criteria['recipe_ticket_search[startDate']);
                $startDate = $startDate->format('Y-m-d');

                $from = new \DateTime($startDate." 00:00:00");
                $queryBuilder
                    ->andWhere('e.ticketDate >= :from ')
                    ->setParameter('from', $from);
            }

            if (isset($criteria['restaurant'])) {
                $queryBuilder->andWhere(":restaurant = e.originRestaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
        }

        if (!$onlyList) {
            $qb2 = clone $queryBuilder;
            $filteredTotal = $qb2->select('count(e)')
                ->getQuery()->getSingleScalarResult();
        }

        //ordering
        if ($order !== null && is_array($order) && count($order) > 0) {
            if (Utilities::exist($order, 'col')) {
                if (Utilities::exist($order, 'dir')) {
                    $orderDir = $order['dir'];
                } else {
                    $orderDir = 'asc';
                }
                switch ($order['col']) {
                    case 'id':
                        $queryBuilder->orderBy('e.id', $orderDir);
                        break;
                    case 'amount':
                        $queryBuilder->orderBy('e.amount', $orderDir);
                        break;
                    case 'owner':
                        $queryBuilder->orderBy('o.firstName', $orderDir);
                        break;
                    case 'date':
                        $queryBuilder->orderBy('e.date', $orderDir);
                        break;
                }
            }
        }
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
                'filtered' => $filteredTotal,
            );
        }
    }

    public function getTotalChestErrorRecipeTicketByDate($date, Restaurant $currentRestaurant = null)
    {
        $queryBuilder = $this->createQueryBuilder('rt');

        $queryBuilder->where('rt.date = :date')
            ->setParameter('date', $date);

        $queryBuilder->andWhere('rt.label = :chestError')
            ->setParameter('chestError', RecipeTicket::CHEST_ERROR);

        if ($currentRestaurant != null) {
            $queryBuilder->andWhere("rt.originRestaurant = :restaurant")
                ->setParameter("restaurant", $currentRestaurant);
        }
        $queryBuilder->select('SUM(rt.amount) as totalAMount');
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }

    public function getTotalCashBoxErrorRecipeTicketByDate($date, $currentRestaurant = null)
    {
        $queryBuilder = $this->createQueryBuilder('rt');

        $queryBuilder->where('rt.date = :date')
            ->setParameter('date', $date);


        $queryBuilder->andWhere('rt.label = :cashBoxError')
            ->setParameter('cashBoxError', RecipeTicket::CASHBOX_ERROR);

        if ($currentRestaurant != null) {
            $queryBuilder->andWhere("rt.originRestaurant = :restaurant")
                ->setParameter("restaurant", $currentRestaurant);
        }

        $queryBuilder->select('SUM(rt.amount) as totalAMount');
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }

    /**
     * @param \DateTime $date
     * @param bool      $byLabel
     * @return $result
     */
    public function getAllRecipeByDate($date, $byLabel = false, $currentRestaurant = null)
    {

        $queryBuilder = $this->createQueryBuilder('r');

        $queryBuilder->andWhere('r.deleted = :false OR r.deleted IS NULL')
            ->setParameter('false', false);

        $queryBuilder->andWhere('r.date = :date')
            ->setParameter('date', $date);
        $queryBuilder->andWhere('r.label != :chestError')
            ->setParameter('chestError', RecipeTicket::CHEST_ERROR);
        $queryBuilder->andWhere('r.label != :cashError')
            ->setParameter('cashError', RecipeTicket::CASHBOX_ERROR);
        $queryBuilder->andWhere('r.label != :recipe')
            ->setParameter('recipe', RecipeTicket::CACHBOX_RECIPE);
        if ($currentRestaurant != null) {
            $queryBuilder->andWhere("r.originRestaurant = :restaurant")
                ->setParameter("restaurant", $currentRestaurant);
        }

        if ($byLabel) {
            $queryBuilder->groupBy('r.label');
            $queryBuilder->select('SUM(r.amount) as totalAmount, r.label as label');
            $queryBuilder->orderBy('label');

            $result = $queryBuilder->getQuery()->getResult();
        } else {
            $queryBuilder->select('Sum(r.amount) as totalAmount');
            $queryBuilder->setMaxResults(1);
            $result = $queryBuilder->getQuery()->getSingleScalarResult();
        }

        return $result;
    }

    /**************
     *
     * Supervision Section
     ******************/

    public function getSupervisionTotalChestErrorRecipeTicketByDate($date, $restaurants = null)
    {
        $queryBuilder = $this->createQueryBuilder('rt');

        if ($restaurants and count($restaurants) > 0) {
            $restaurantsIds = array();

            foreach ($restaurants as $restaurant) {
                $restaurantsIds[] = $restaurant->getId();
            }
            $queryBuilder
                ->andWhere('rt.originRestaurant IN (:restaurants)')
                ->setParameter('restaurants', $restaurantsIds);
        }

        $queryBuilder->andWhere('rt.date = :date')
            ->setParameter('date', $date);

        $queryBuilder->andWhere('rt.label = :chestError')
            ->setParameter('chestError', RecipeTicket::CHEST_ERROR);

        $queryBuilder->select('SUM(rt.amount) as totalAMount');
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }

    public function getSupervisionTotalCashBoxErrorRecipeTicketByDate($date, $restaurants = null)
    {
        $queryBuilder = $this->createQueryBuilder('rt');

        if ($restaurants and count($restaurants) > 0) {
            $restaurantsIds = array();

            foreach ($restaurants as $restaurant) {
                $restaurantsIds[] = $restaurant->getId();
            }
            $queryBuilder
                ->andWhere('rt.originRestaurant IN (:restaurants)')
                ->setParameter('restaurants', $restaurantsIds);
        }

        $queryBuilder->andWhere('rt.date = :date')
            ->setParameter('date', $date);

        $queryBuilder->andWhere('rt.label = :cashBoxError')
            ->setParameter('cashBoxError', RecipeTicket::CASHBOX_ERROR);

        $queryBuilder->select('SUM(rt.amount) as totalAMount');
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }

    public function getRecipeBi($criteria, $offset, $limit)
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->join('e.originRestaurant', 'r')
            ->orderBy('r.code')
            ->addOrderBy('e.date');

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
                $queryBuilder->andWhere('r.code in (:orId)')
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
                    $queryBuilder
                        ->andWhere('e.date BETWEEN :from AND :to ')
                        ->setParameter('from', $from)
                        ->setParameter('to', $to);
                } elseif ($startDate != null && $endDate == null) {
                    $queryBuilder
                        ->andWhere('e.date > :from')
                        ->setParameter('from', $from);
                } elseif ($startDate == null && $endDate != null) {
                    $queryBuilder
                        ->andWhere('e.date < :to')
                        ->setParameter('to', $to);
                }
            }
        }

        if ($limit !== null) {
            $queryBuilder->setMaxResults(intval($limit));
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult(intval($offset));
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
