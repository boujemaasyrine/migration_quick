<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 05/04/2016
 * Time: 10:48
 */

namespace AppBundle\Financial\Repository;

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\Query;

class TicketPaymentRepository extends \Doctrine\ORM\EntityRepository
{
    public function getTotalPaymentPerDay($date, $restaurant, $cashier = null)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->leftJoin('p.ticket', 't');

        $queryBuilder->andWhere('t.date = :date')
            ->setParameter('date', $date);

        $queryBuilder->andWhere('t.status <> :canceled and t.status <> :retired AND t.countedCanceled <> :true')
            ->setParameter('canceled', -1)
            ->setParameter('retired', 5);

        $queryBuilder->andWhere('t.counted != :true')
            ->setParameter('true', true);

        $queryBuilder->andWhere('t.originRestaurant=:restaurant')
            ->setParameter('restaurant',$restaurant);


        if ($cashier) {
            $queryBuilder->andWhere('t.operator = :cashier')
                ->setParameter('cashier', $cashier);
        }

        $queryBuilder->andWhere('p.type = :payment')
            ->setParameter('payment', TicketPayment::PAYMENT_TYPE);

        $queryBuilder->andWhere('p.idPayment = :espece')
            ->setParameter('espece', TicketPayment::REAL_CASH);

        $queryBuilder->select('SUM(p.amount) AS total');
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }

    /**
     * @param \DateTime $dateTime
     * @param $paymentMethod
     * @param Employee|null $employee
     * @param bool $allTheDayPaymentTickets
     * @return array
     */
    public function getNotCountedPaymentTicketsByDateByCashier(
        \DateTime $dateTime,
        $paymentMethod,
        Employee $employee = null,
        $allTheDayPaymentTickets = false,
        $restaurant = null
    ) {
        $startDay = clone $dateTime;
        $startDay->setTime(0, 0, 0);
        $endDay = clone $dateTime;
        if ($allTheDayPaymentTickets) {
            $endDay->add(new \DateInterval('P1D'));
            $endDay->setTime(0, 0, 0);
        }
        $qb = $this->_em->getRepository('Financial:TicketPayment')->createQueryBuilder('ticketPayment');
        $qb->select('ticketPayment')
            ->leftJoin('ticketPayment.ticket', 'ticket')
            ->where('ticket.counted = false')
            ->andWhere('ticket.date = :date')
            ->setParameter('date', $startDay)
            //->andWhere('ticket.endDate < :endDate')
            //->setParameter('endDate', $endDay)
            ->andwhere('LOWER(ticketPayment.type) LIKE :payment')
            ->setParameter('payment', TicketPayment::PAYMENT_TYPE);

        if (isset($restaurant)) {
            $qb->andWhere('ticket.originRestaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        if (is_array($paymentMethod)) {
            $qb->andWhere('ticketPayment.idPayment in (:paymentMethod)')
                ->setParameter('paymentMethod', $paymentMethod);
        } else {
            $qb->andWhere('ticketPayment.idPayment = :paymentMethod')
                ->setParameter('paymentMethod', $paymentMethod);
        }

        if (!is_array($employee)) {
            $qb->andWhere('ticket.operator = :wyndId')
                ->setParameter('wyndId', $employee->getWyndId());
        }
        $tickets = $qb->getQuery()->getResult();

        return $tickets;
    }

    public function getPaymentByMedia($filter)
    {
        $queryBuilder = $this->createQueryBuilder('tp');
        $queryBuilder->leftJoin('tp.ticket', 't');

        $queryBuilder->andWhere('t.status <> :canceled and t.status <> :retired AND t.countedCanceled <> :TRUE')
            ->setParameter('canceled', -1)
            ->setParameter('retired', 5)
            ->setParameter('TRUE', true);

        $queryBuilder->andWhere('t.date >= :startDate and t.date <= :endDate')
            ->setParameter('startDate', $filter['startDate'])
            ->setParameter('endDate', $filter['endDate']);

        $queryBuilder->select('SUM(tp.amount) as totalAmount, tp.idPayment as method');

        $queryBuilder->groupBy('tp.idPayment');
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    public function getTotalMealTicket($date, Restaurant $currentRestaurant = null)
    {

        $date = $date->format('Y-m-d');
        $mealTicket = TicketPayment::MEAL_TICKET;
        $queryBuilder = $this->createQueryBuilder('tp');
        $queryBuilder->leftJoin('tp.ticket', 't');


        $queryBuilder->where('t.date = :date')
            ->setParameter('date', $date);

        if ($currentRestaurant != null) {
            $queryBuilder->andWhere("t.originRestaurant = :restaurant")
                ->setParameter("restaurant", $currentRestaurant);
        }

        $queryBuilder->andWhere('t.status <> :canceled and t.status <> :abandonment AND t.countedCanceled <> :true')
            ->setParameter('canceled', Ticket::CANCEL_STATUS_VALUE)
            ->setParameter('abandonment', Ticket::ABONDON_STATUS_VALUE);

        $queryBuilder->andWhere('t.counted = :true')
            ->setParameter('true', true);

        $queryBuilder->andWhere('tp.idPayment = :bonRepas')
            ->setParameter('bonRepas', $mealTicket);

        $queryBuilder->select('SUM(tp.amount) as totalAMount');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
