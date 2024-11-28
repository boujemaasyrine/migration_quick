<?php
/**
 * Created by PhpStorm.
 * User: hmnaouar
 * Date: 02/10/2017
 * Time: 15:53
 */

namespace AppBundle\General\Repository;

use AppBundle\General\Entity\SyncCmdQueue;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Utils\Utilities;
use Doctrine\ORM\EntityRepository;

class SyncCmdQueueRepository extends EntityRepository
{
    public function getCmdForToday()
    {
        return $this->_em->createQuery(
            "
                SELECT s
                FROM General:SyncCmdQueue s
                WHERE
                 s.status = :waiting
                   AND ( ( s.syncDate is null )
                  OR ( s.syncDate = :today )  )"
        )
            ->setParameter("waiting", SyncCmdQueue::WAITING)
            ->setParameter("today", new \DateTime("today"))
            ->getResult();
    }

    public function getList(
        $restaurants,
        $criteria = null,
        $restaurant = null,
        $search = null,
        $order = null,
        $offset = null,
        $limit = null,
        $onlyList = false
    ) {
        $qb = $this
            ->createQueryBuilder('d')
            ->join('d.originRestaurant', 'r')
            ->where('d.status != :waiting')
            ->setParameter('waiting', SyncCmdQueue::WAITING);
            $qb->andWhere("d.originRestaurant in (:restaurants)")
                ->setParameter("restaurants", $restaurants);

        if ($restaurant && $restaurant instanceof Restaurant) {
            $qb->andWhere('d.originRestaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        } elseif (is_array($restaurant) && count($restaurant) > 0) {
            $restaurantIds = [];
            foreach ($restaurant as $rr) {
                if ($rr instanceof Restaurant) {
                    $restaurantIds[] = $rr->getId();
                }
            }
            if (count($restaurantIds) > 0) {
                $qb->andWhere('r.id in (:restaurantId) ')
                    ->setParameter('restaurantId', $restaurantIds);
            }
        }

        if (!$onlyList) {
            $qb1 = clone $qb;
            $total = $qb1->select('count(d)')->getQuery()->getSingleScalarResult();
        }

        //search
        if ($search && trim($search) != '') {
            $qb->join('d.product', 'p')
                ->andWhere('LOWER(p.name) like :search ')
                ->setParameter('search', "%".strtolower($search)."%");
        }

        //Filters
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'download-type')) {
                $qb->andWhere("d.cmd = :type ")
                    ->setParameter("type", $criteria['download-type']);
            }

            if (Utilities::exist($criteria, 'date-synchro')) {
                $syncDay = \DateTime::createFromFormat('d/m/Y H:i:s', $criteria['date-synchro']." 00:00:00");
                $nextDay = Utilities::getDateFromDate($syncDay, 1);
                $qb->andWhere("d.updatedAt < :nextDay ")
                    ->andWhere('d.updatedAt >= :syncDay')
                    ->setParameter("nextDay", $nextDay)
                    ->setParameter("syncDay", $syncDay);
            }

            if (Utilities::exist($criteria, 'status')) {
                $qb->andWhere("d.status = :status ")
                    ->setParameter("status", $criteria['status']);
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
                    case 'date':
                        $qb->orderBy('d.updatedAt', $orderDir);
                        break;
                    case 'status':
                        $qb->orderBy('d.status', $orderDir);
                        break;
                    case 'type':
                        $qb->orderBy('d.cmd', $orderDir);
                        break;
                    case 'restaurant':
                        $qb->orderBy('r.name', $orderDir);
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
}
