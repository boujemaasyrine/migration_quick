<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 07/04/2016
 * Time: 14:39
 */

namespace AppBundle\Financial\Repository;

use AppBundle\Financial\Entity\Deposit;
use AppBundle\Financial\Entity\Envelope;
use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;

class EnvelopeRepository extends EntityRepository
{

    public function getEnvelopesFilteredOrdered(
        $criteria,
        $order,
        $offset,
        $limit,
        $search = null,
        $onlyList = false,
        $type = Envelope::TYPE_CASH
    ) {

        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder->leftJoin('e.owner', 'o');
        $queryBuilder->leftJoin('e.cashier', 'c')
            ->andWhere('upper(e.type) = upper(:type)')
            ->setParameter('type', $type);

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
            if (Utilities::exist($criteria, 'envelope_search[source')) {
                $queryBuilder->andWhere("upper(e.source) = upper(:source) ")
                    ->setParameter("source", $criteria['envelope_search[source']);
            }

            if (Utilities::exist($criteria, 'envelope_search[sousType')) {
                $queryBuilder->andWhere("upper(e.sousType) = upper(:sousType) ")
                    ->setParameter("sousType", $criteria['envelope_search[sousType']);
            }

            if (Utilities::exist($criteria, 'envelope_search[status')) {
                $queryBuilder->andWhere("e.status = :status ")
                    ->setParameter("status", $criteria['envelope_search[status']);
            }

            if (Utilities::exist($criteria, 'envelope_search[startDate') && Utilities::exist(
                $criteria,
                'envelope_search[endDate'
            )) {
                $startDate = \DateTime::createFromFormat('j/m/Y', $criteria['envelope_search[startDate']);
                $startDate = $startDate->format('Y-m-d');

                $endDate = \DateTime::createFromFormat('j/m/Y', $criteria['envelope_search[endDate']);
                $endDate = $endDate->format('Y-m-d');

                $from = new \DateTime($startDate." 00:00:00");
                $to = new \DateTime($endDate." 23:59:59");
                $queryBuilder
                    ->andWhere('e.createdAt BETWEEN :from AND :to ')
                    ->setParameter('from', $from)
                    ->setParameter('to', $to);
            }
            if (Utilities::exist($criteria, 'envelope_search[owner')) {
                $queryBuilder->andWhere("e.owner = :owner ")
                    ->setParameter("owner", $criteria['envelope_search[owner']);
            }


            if ($search) {
                $queryBuilder
                    ->andWhere(
                        '(
                    LOWER(o.firstName) like :search
                    or LOWER(o.lastName) like :search
                    or LOWER(c.firstName) like :search
                    or LOWER(c.lastName) like :search
                    or LOWER(STRING(e.reference)) like :search
                    or LOWER(STRING(e.amount)) like :search
                    or LOWER(STRING(e.numEnvelope)) like :search
                    or DATE_STRING(e.createdAt) like :search
                    )'
                    )
                    ->setParameter('search', '%'.strtolower($search).'%');
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
                    case 'number':
                        $queryBuilder->orderBy('e.numEnvelope', $orderDir);
                        break;
                    case 'amount':
                        $queryBuilder->orderBy('e.amount', $orderDir);
                        break;
                    case 'date':
                        $queryBuilder->orderBy('e.createdAt', $orderDir);
                        break;
                    case 'owner':
                        $queryBuilder->orderBy('o.firstName', $orderDir);
                        break;
                    case 'cashier':
                        $queryBuilder->orderBy('c.firstName', $orderDir);
                        break;
                    case 'ref':
                        $queryBuilder->orderBy('e.reference', $orderDir);
                        break;
                    case 'status':
                        $queryBuilder->orderBy('e.status', $orderDir);
                        break;
                    case 'source':
                        $queryBuilder->orderBy('e.source', $orderDir);
                        break;
                    case 'sousType':
                        $queryBuilder->orderBy('e.sousType', $orderDir);
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

    public function getTotalFromCashBox($cashier)
    {

        $from = new \DateTime(date("Y-m-d")." 00:00:00");
        $to = new \DateTime(date("Y-m-d")." 23:59:59");

        $queryBuilder = $this->createQueryBuilder('e');

        $queryBuilder
            ->andWhere('e.source = :cashBox')
            ->setParameter('cashBox', Envelope::CASHBOX_COUNTS);

        $queryBuilder
            ->andWhere('e.cashier = :cashier')
            ->setParameter('cashier', $cashier);

        $queryBuilder->andWhere('e.createdAt between :from and :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        $queryBuilder->select('SUM(e.amount) AS total');
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }

    public function getEnvelopeToday($sousType)
    {

        $from = new \DateTime(date("Y-m-d")." 00:00:00");
        $to = new \DateTime(date("Y-m-d")." 23:59:59");

        $queryBuilder = $this->createQueryBuilder('e');

        $queryBuilder
            ->andWhere('e.type = :type')
            ->setParameter('type', Envelope::TYPE_TICKET);

        $queryBuilder
            ->andWhere('e.sousType = :sousType')
            ->setParameter('sousType', $sousType);

        $queryBuilder->andWhere('e.createdAt between :from and :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    public function getEnvelopesCriteria($type, $status, $sousType = null, $restaurant = null)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->where('e.status = :status')
            ->setParameter('status', $status)
            ->andWhere('e.type = :type')
            ->setParameter('type', $type)
            ->orderBy('e.createdAt', 'asc');

        if ($sousType) {
            $qb->andWhere('e.sousType = :sousType')
                ->setParameter('sousType', $sousType);
        }

        if (isset($restaurant)) {
            $qb->andWhere("e.originRestaurant= :restaurant")
                ->setParameter("restaurant", $restaurant);
        }

        return $qb->getQuery()->getResult();
    }

    public function getTotalNotVersed($type, $sousType = null, $restaurant = null)
    {
        $qb = $this->createQueryBuilder('e');

        $qb->select('SUM(e.amount) as total')
            ->where('e.status = :status')
            ->andWhere('e.type = :type')
            ->setParameter('status', Envelope::NOT_VERSED)
            ->setParameter('type', $type);
        ;
        if ($sousType != null) {
            $qb->andWhere('e.sousType = :sousType')
                ->setParameter('sousType', $sousType);
        }
        if (isset($restaurant)) {
            $qb->andWhere('e.originRestaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }
        $result = $qb->getQuery()->getScalarResult();

        return $result;
    }

    public function getNotCounted($type, $paymentId = null, $restaurant = null)
    {
        $qb = $this->createQueryBuilder('e');

        $qb->where('e.type = :type')
            ->setParameter('type', $type)
            ->andWhere('e.chestCount is null');

        if (isset($restaurant)) {
            $qb->andWhere("e.originRestaurant = :restaurant")
                ->setParameter('restaurant', $restaurant);
        }


        if ($paymentId) {
            $qb->andWhere('e.sousType = :paymentId')
                ->setParameter('paymentId', $paymentId);
        }

        return $qb->getQuery()->getResult();
    }

    public function getEnvelopesDeposit(Deposit $deposit)
    {
        $qb = $this->createQueryBuilder('e');

        $qb->where('e.deposit = :deposit')
            ->setParameter('deposit', $deposit)
            ->orderBy('e.createdAt');

        $result = $qb->getQuery()->getResult();

        return $result;
    }
}
