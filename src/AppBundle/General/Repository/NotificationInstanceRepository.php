<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 17/05/2016
 * Time: 11:50
 */

namespace AppBundle\General\Repository;

use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;

class NotificationInstanceRepository extends EntityRepository
{

    public function getNotSeenNotification($employee, $restaurant = null)
    {
        $queryBuilder = $this->createQueryBuilder('ni');
        $queryBuilder->leftJoin('ni.notification', 'n');

        $queryBuilder->where('ni.employee = :employee')
            ->setParameter('employee', $employee);

        $queryBuilder->andWhere('ni.seen = :false')
            ->setParameter('false', false);
        if (isset($restaurant)) {
            $queryBuilder->andWhere(":restaurant = n.originRestaurant")
                ->setParameter("restaurant", $restaurant);
        }

        $queryBuilder->orderBy('ni.id', 'DESC');

        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    public function getNotificationsFiltred($criteria, $order, $offset, $limit, $onlyList = false)
    {

        $queryBuilder = $this->createQueryBuilder('ni');
        $queryBuilder->leftJoin('ni.notification', 'n');

        $queryBuilder->where('ni.employee = :employee')
            ->setParameter('employee', $criteria['user']);

        if (!$onlyList) {
            $qb1 = clone $queryBuilder;
            if (isset($criteria['restaurant'])) {
                $qb1->andWhere(":restaurant = n.originRestaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
            $total = $qb1->select('count(ni)')
                ->getQuery()->getSingleScalarResult();
        }

        //filtering

        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (isset($criteria['restaurant'])) {
                $queryBuilder->andWhere(":restaurant = n.originRestaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
        }

        if (!$onlyList) {
            $qb2 = clone $queryBuilder;
            $filtredTotal = $qb2->select('count(ni)')
                ->getQuery()->getSingleScalarResult();
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
            $queryBuilder->orderBy('ni.id', 'DESC');

            return array(
                'list' => $queryBuilder->getQuery()->getResult(),
                'total' => $total,
                'filtred' => $filtredTotal,
            );
        }
    }
}
