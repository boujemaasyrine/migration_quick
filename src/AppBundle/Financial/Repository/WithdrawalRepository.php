<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 29/03/2016
 * Time: 10:39
 */

namespace AppBundle\Financial\Repository;

use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Utils\Utilities;

class WithdrawalRepository extends \Doctrine\ORM\EntityRepository
{
    public function findTotalPreviousAmount($member, $id = null, $date = null, $restaurant = null)
    {
        if (is_null($date)) {
            $date = new \DateTime(date("Y-m-d"));
        }

        $queryBuilder = $this->createQueryBuilder('w');

        $queryBuilder
            ->andWhere('w.statusCount = :notCounted')
            ->setParameter('notCounted', Withdrawal::NOT_COUNTED)
            ->andWhere('w.member = :member')
            ->setParameter('member', $member)
            ->andWhere('w.date = :date')
            ->setParameter('date', $date->format('Y-m-d'));

        if ($id) {
            $queryBuilder
                ->andWhere('w.id != :id')
                ->setParameter('id', $id);
        }

        if (isset($restaurant)) {
            $queryBuilder
                ->andWhere('w.originRestaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        $queryBuilder->orderBy('w.date', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    public function getWithdrawalsFiltredOrdered($criteria, $order, $offset, $limit, $onlyList = false)
    {

        $queryBuilder = $this->createQueryBuilder('w');
        $queryBuilder->leftJoin('w.member', 'm');
        $queryBuilder->leftJoin('w.responsible', 'r');

        if (!$onlyList) {
            $qb1 = clone $queryBuilder;
            if (isset($criteria['restaurant'])) {
                $qb1->andWhere("w.originRestaurant= :restaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
            $total = $qb1->select('count(w)')
                ->getQuery()->getSingleScalarResult();
        }

        //filtering

        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'withdrawal_search[statusCount')) {
                $queryBuilder->andWhere("w.statusCount = :statusCount ")
                    ->setParameter("statusCount", $criteria['withdrawal_search[statusCount']);
            }

            if (isset($criteria['withdrawal_search[envelope'])) {
                $OU = array();
                if (in_array('true', $criteria['withdrawal_search[envelope'])) {
                    $OU[] = "w.envelopeId IS NOT NULL";
                }
                if (in_array('false', $criteria['withdrawal_search[envelope'])) {
                    $OU[] = "w.envelopeId IS NULL";
                }
                $OU = implode(' or ', $OU);
                $queryBuilder->andWhere($OU);
            }


            if (Utilities::exist($criteria, 'withdrawal_search[startDate')) {
                $date = \DateTime::createFromFormat('j/m/Y', $criteria['withdrawal_search[startDate']);
                $date = $date->format('Y-m-d');

                $from = new \DateTime($date . " 00:00:00");
                $queryBuilder
                    ->andWhere('w.date >= :from ')
                    ->setParameter('from', $from);
            }

            if (Utilities::exist($criteria, 'withdrawal_search[endDate')) {
                $date = \DateTime::createFromFormat('j/m/Y', $criteria['withdrawal_search[endDate']);
                $date = $date->format('Y-m-d');

                $to = new \DateTime($date . " 23:59:59");
                $queryBuilder
                    ->andWhere('w.date <=  :to ')
                    ->setParameter('to', $to);
            }

            if (Utilities::exist($criteria, 'withdrawal_search[owner')) {
                $queryBuilder->andWhere("w.responsible = :owner ")
                    ->setParameter("owner", $criteria['withdrawal_search[owner']);
            }

            if (Utilities::exist($criteria, 'withdrawal_search[member')) {
                $queryBuilder->andWhere("m.id = :member ")
                    ->setParameter("member", $criteria['withdrawal_search[member']);
            }

            if (isset($criteria['restaurant'])) {
                $queryBuilder->andWhere("w.originRestaurant = :restaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
        }

        if (!$onlyList) {
            $qb2 = clone $queryBuilder;
            $filtredTotal = $qb2->select('count(w)')
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
                    case 'responsible':
                        $queryBuilder->orderBy('r.firstName', $orderDir);
                        break;
                    case 'member':
                        $queryBuilder->orderBy('m.firstName', $orderDir);
                        break;
                    case 'date':
                        $queryBuilder->orderBy('w.date', $orderDir);
                        break;
                    case 'amount':
                        $queryBuilder->orderBy('w.amountWithdrawal', $orderDir);
                        break;
                    case 'statusCount':
                        $queryBuilder->orderBy('w.statusCount', $orderDir);
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
                'filtred' => $filtredTotal,
            );
        }
    }

    /**
     * This method will calculate the total of withdrawals of a given cashier at a given date
     *
     * @param  \DateTime $date
     * @param  Employee $employee
     * @return float
     */
    public function calculatePendingWithdrawalTotalByOperatorByDate(
        \DateTime $date,
        Employee $employee,
        $getWithdrawals = false,
        $restaurant = null
    )
    {
        $qb = $this->_em->getRepository('Financial:Withdrawal')
            ->createQueryBuilder('withdrawal');

        if ($getWithdrawals) {
            $qb->select('withdrawal');
        } else {
            $qb->select('SUM(withdrawal.amountWithdrawal)');
        }

        $qb->where('withdrawal.statusCount = :pendingStatus')
            ->setParameter('pendingStatus', Withdrawal::NOT_COUNTED)
            ->andWhere('withdrawal.member = :employee')
            ->setParameter('employee', $employee);

        $qb->andWhere('withdrawal.date = :date')
            ->setParameter('date', $date->format('Y-m-d'));

        if (isset($restaurant)) {
            $qb->andWhere('withdrawal.originRestaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        if ($getWithdrawals) {
            return $qb->getQuery()->getResult();
        } else {
            return floatval($qb->getQuery()->getSingleScalarResult());
        }
    }

    public function getTotalPendingAmount($restaurant, $id = null, $cashier = null)
    {

        $from = new \DateTime(date("Y-m-d") . " 00:00:00");
        $to = new \DateTime(date("Y-m-d") . " 23:59:59");

        $queryBuilder = $this->createQueryBuilder('w');

        $queryBuilder->andWhere('w.statusCount = :notCounted')
            ->setParameter('notCounted', Withdrawal::NOT_COUNTED);

        $queryBuilder->andWhere('w.date between :from and :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);
        $queryBuilder->andWhere('w.originRestaurant=:restaurant')
            ->setParameter('restaurant', $restaurant);

        if ($id) {
            $queryBuilder
                ->andWhere('w.id != :id')
                ->setParameter('id', $id);
        }

        if ($cashier) {
            $queryBuilder->andWhere('w.member = :cashier')
                ->setParameter('cashier', $cashier);
        }


        $queryBuilder->select('SUM(w.amountWithdrawal) AS total');
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }

    public function getCashiers()
    {

        $queryBuilder = $this->createQueryBuilder('w');
        $queryBuilder->leftJoin('w.member', 'm');

        $queryBuilder->select('Distinct (w.member)');
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    /**
     * set envelopeId to null
     * @param $eID
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function updateWithdrawalEnvelopeID($eID)
    {
        $this->createQueryBuilder('w')
            ->update()
            ->set('w.envelopeId', 'null')
            ->where('w.envelopeId=:eID')
            ->setParameter('eID', $eID)
            ->getQuery()->getSingleScalarResult();
    }
}
