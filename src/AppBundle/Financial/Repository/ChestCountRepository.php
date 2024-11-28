<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 17/03/2016
 * Time: 16:40
 */

namespace AppBundle\Financial\Repository;

use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Validator\Constraints\DateTime;

class ChestCountRepository extends \Doctrine\ORM\EntityRepository
{
    public function getLastChestCount($restaurant = null)
    {
        try {
            $qb = $this->_em->getRepository('Financial:ChestCount')->createQueryBuilder('chestCount');
            $qb->orderBy('chestCount.id', 'desc')->setMaxResults(1);

            if (isset($restaurant)) {
                $qb->andWhere("chestCount.originRestaurant = :restaurant")
                    ->setParameter("restaurant", $restaurant);
            }

            $chestCount = $qb->getQuery()->getSingleResult();

            return $chestCount;
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function getLastChestCountByClosureDate($restaurant)
    {
        try {
            $qb = $this->_em->getRepository('Financial:ChestCount')->createQueryBuilder('chestCount');
            $qb->orderBy('chestCount.id', 'desc')->setMaxResults(1);
            $qb->andWhere("chestCount.originRestaurant = :restaurant")
                ->setParameter("restaurant", $restaurant);


            $chestCount = $qb->getQuery()->getSingleResult();

            return $chestCount;
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function getChestCountsFilteredOrdered($criteria, $order, $offset, $limit, $search = null, $onlyList = false)
    {
        $queryBuilder = $this->createQueryBuilder('cc');
        $queryBuilder->leftJoin('cc.owner', 'o');
        if (!$onlyList) {
            $qb1 = clone $queryBuilder;
            if (isset($criteria['restaurant'])) {
                $qb1->andWhere(":restaurant = cc.originRestaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
            $total = $qb1->select('count(cc)')
                ->getQuery()->getSingleScalarResult();
        }

        //filtering
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'chest_counts_search[startDate') && Utilities::exist(
                    $criteria,
                    'chest_counts_search[endDate'
                )) {
                $startDate = \DateTime::createFromFormat('j/m/Y', $criteria['chest_counts_search[startDate']);
                $startDate = $startDate->format('Y-m-d');

                $endDate = \DateTime::createFromFormat('j/m/Y', $criteria['chest_counts_search[endDate']);
                $endDate = $endDate->format('Y-m-d');

                $from = new \DateTime($startDate . " 00:00:00");
                $to = new \DateTime($endDate . " 23:59:59");
                $queryBuilder
                    ->andWhere('cc.date BETWEEN :from AND :to ')
                    ->setParameter('from', $from)
                    ->setParameter('to', $to);
            }
            if (Utilities::exist($criteria, 'chest_counts_search[owner')) {
                $queryBuilder->andWhere("cc.owner = :owner ")
                    ->setParameter("owner", $criteria['chest_counts_search[owner']);
            }
            if (isset($criteria['restaurant'])) {
                $queryBuilder->andWhere(":restaurant = cc.originRestaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
        }

        if (!$onlyList) {
            $qb2 = clone $queryBuilder;
            $filteredTotal = $qb2->select('count(cc)')
                ->getQuery()->getSingleScalarResult();
        }

        $queryBuilder->select(
            'cc.id, o.firstName, o.lastName, cc.date, cc.realTotal, cc.gap, cc.closure, cc.closureDate'
        );

        //ordering
        if ($order !== null && is_array($order) && count($order) > 0) {
            if (Utilities::exist($order, 'col')) {
                if (Utilities::exist($order, 'dir')) {
                    $orderDir = $order['dir'];
                } else {
                    $orderDir = 'asc';
                }
                switch ($order['col']) {
                    case 'date':
                        $queryBuilder->orderBy('cc.date', $orderDir);
                        break;
                    case 'owner':
                        $queryBuilder->orderBy('o.firstName', $orderDir);
                        break;
                    case 'realCounted':
                        $queryBuilder->orderBy('cc.realTotal', $orderDir);
                        break;
                    case 'gap':
                        $queryBuilder->orderBy('cc.gap', $orderDir);
                        break;
                    case 'closured':
                        $queryBuilder->orderBy('cc.closure', $orderDir);
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

    public function getChestByDate($date)
    {
        $to = date_create($date->format('Y-m-d 23:59:59'));
        $queryBuilder = $this->createQueryBuilder('cc');

        $queryBuilder->andWhere('cc.date >= :date AND cc.date <= :to')
            ->setParameter('date', $date)
            ->setParameter('to', $to);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param \DateTime $date
     * @return null
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLastChestCountInDate($date)
    {
        $to = clone $date;
        $to->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('c');
        $qb->where('c.date >= :date and c.date <= :to')
            ->setParameter('date', $date)
            ->setParameter('to', $to);
        $qb->orderBy('c.createdAt', 'desc');
        $qb->setMaxResults(1);
        try {
            $chestCount = $qb->getQuery()->getSingleResult();

            return $chestCount;
        } catch (NoResultException $e) {
            return null;
        }
    }

    /**
     * @param \DateTime $date
     * @return mixed|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getChestCountForClosedDate($date, Restaurant $currentRestaurant = null)
    {

        $qb = $this->createQueryBuilder('c');
        $qb->where('c.closureDate = :date and c.closure = :true')
            ->setParameter('date', $date)
            ->setParameter('true', true);
        $qb->orderBy('c.createdAt', 'desc');

        if ($currentRestaurant != null) {
            $qb->andWhere("c.originRestaurant = :restaurant")
                ->setParameter("restaurant", $currentRestaurant);
        }

        $qb->setMaxResults(1);
        try {
            $chestCount = $qb->getQuery()->getSingleResult();

            return $chestCount;
        } catch (NoResultException $e) {
            return null;
        }
    }
  public function getChestCountById($id, Restaurant $currentRestaurant)
    {

        $qb = $this->createQueryBuilder('c');
        $qb->where('c.id = :id and c.closure = :false')
            ->setParameter('id', $id)
            ->setParameter('false', false);
        $qb->orderBy('c.createdAt', 'desc');

        if ($currentRestaurant != null) {
            $qb->andWhere("c.originRestaurant = :restaurant")
                ->setParameter("restaurant", $currentRestaurant);
        }

        $qb->setMaxResults(1);
        try {
            $chestCount = $qb->getQuery()->getSingleResult();

            return $chestCount;
        } catch (NoResultException $e) {
            return null;
        }
    }
    public function findFirstChestCountInClosureAdministrative($restaurant)
    {
        $qb = $this->createQueryBuilder('chestCount')
            ->where('chestCount.closure = :true')
            ->andWhere('chestCount.originRestaurant = :restaurant')
            ->setParameter('restaurant', $restaurant)
            ->setParameter('true', true)
            ->orderBy('chestCount.date', 'asc');

        try {
            $qb->setMaxResults(1);
            $result = $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            $result = null;
        }

        return $result;
    }

    /**
     * @param \DateTime $starDate
     * @param \DateTime $endDate
     * @return null
     * @throws NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getChestGap($starDate, $endDate = null, $currentRestaurant = null)
    {
        if (is_null($endDate)) {
            $endDate = date_create($starDate->format('Y-m-d 23:59:59'));
        }

        $queryBuilder = $this->createQueryBuilder('cc');
        $queryBuilder->andWhere('cc.date >= :date AND cc.date <= :to')
            ->select('SUM(cc.gap)')
            ->setParameter('date', $starDate)
            ->setParameter('to', $endDate);
        if ($currentRestaurant != null) {
            $queryBuilder->andWhere("cc.originRestaurant =:restaurant")
                ->setParameter("restaurant", $currentRestaurant);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function calculateRealTotal(ChestCount $currentChestCount, Restaurant $currentRestaurant = null)
    {
        $qb = $this->_em->getRepository('Financial:Envelope')->createQueryBuilder('e');
        $qb->select('sum(e.amount)')
            ->leftJoin('e.deposit', 'd')
            ->where('e.createdAt < :chestDate and (d.createdAt > :chestDate or e.deposit is null)')
            ->setParameter('chestDate', $currentChestCount->getDate());
        if ($currentRestaurant != null) {
            $qb->andWhere("e.originRestaurant = :restaurant")
                ->setParameter("restaurant", $currentRestaurant);
        }

        $totalEnveloppe = $qb->getQuery()->getSingleScalarResult();

        $total = 0.0;
        $total += $totalEnveloppe;
        $total += $currentChestCount->getSmallChest()->calculateRealTotal();
        $total += $currentChestCount->getExchangeFund()->calculateRealTotal();
        $total += $currentChestCount->getCashboxFund()->calculateRealTotal();

        return $total;
    }

}
