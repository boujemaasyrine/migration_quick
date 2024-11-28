<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 05/04/2016
 * Time: 16:44
 */

namespace AppBundle\Financial\Repository;

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class TicketLineRepository extends EntityRepository
{

    public function getNotCountedDiscountTicketLinesByDateByCashier(
        \DateTime $dateTime,
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
        $qb = $this->_em->getRepository('Financial:TicketLine')->createQueryBuilder('ticketLine');
        $qb->select('ticketLine')
            ->leftJoin('ticketLine.ticket', 'ticket')
            ->where('ticket.counted = false')
            ->andWhere("ticketLine.isDiscount = TRUE ")
            ->andWhere('ticket.date = :date')
            ->andWhere('ticketLine.date = :date')
            ->setParameter('date', $startDay)
            ->andWhere('ticket.endDate < :endDate')
            ->andWhere('ticketLine.endDate < :endDate')
            ->setParameter('endDate', $endDay);

        if (isset($restaurant)) {
            $rId=$restaurant->getId();
            $qb->andWhere('ticket.originRestaurant = :restaurant')
                ->andWhere('ticketLine.originRestaurantId = :restaurantId')
                ->setParameter('restaurant', $restaurant)
                ->setParameter('restaurantId', $rId);
        }

        if (!is_array($employee)) {
            $qb->andWhere('ticket.operator = :wyndId')
                ->setParameter('wyndId', $employee->getWyndId());
        }
        $discounts = $qb->getQuery()->getResult();

        return $discounts;
    }

    public function getTotalCount($startDate = null, $endDate = null,$restaurantId=null)
    {
        $qb = $this->_em->getRepository('Financial:TicketLine')->createQueryBuilder('ticketLine');

        if ($startDate && $endDate) {
            $qb
                ->andWhere('ticketLine.date >= :startDate and ticketLine.date <= :endDate')
                ->andWhere('ticketLine.originRestaurantId = :origin_restaurant_id')
                ->setParameter('origin_restaurant_id', $restaurantId)
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }
        $qb->select('COUNT(ticketLine)')//        ->where('ticketLine.revenuePrice is null')
        ;

        $count = $qb->getQuery()->getSingleScalarResult();

        return $count;
    }

     public function findByDates($startDate = null, $endDate = null,$restaurantId, $step = null, $page = null)
    {
        $qb = $this->createQueryBuilder('tl');

        if ($startDate && $endDate) {
            $qb
                ->andWhere('tl.date >= :startDate and tl.date <= :endDate')
                ->andWhere('tl.originRestaurantId = :origin_restaurant_id')
                ->setParameter('origin_restaurant_id', $restaurantId)
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        if ($step !== null) {
            $qb->setMaxResults(intval($step));
        }

        if ($page !== null) {
            $qb->setFirstResult(intval($page));
        }

        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * @param \DateTime $date
     * @param $tva
     * @return $result
     */
    public function getAmountLinesTicketByDate($date, $tva = false, $currentRestaurant = null)
    {

        $date = $date->format('Y-m-d');
        $queryBuilder = $this->createQueryBuilder('tl');
        $queryBuilder->leftJoin('tl.ticket', 't');

        $queryBuilder->where('t.date = :date and tl.date =:date')
            ->setParameter('date', $date);
        if ($currentRestaurant != null) {
            /**
             * @var Restaurant $currentRestaurant
             */
            $queryBuilder->andWhere("t.originRestaurant = :restaurant and tl.originRestaurantId= :restaurantId")
                ->setParameter("restaurant", $currentRestaurant)
                ->setParameter('restaurantId',$currentRestaurant->getId());
        }

        $queryBuilder->andWhere('t.status <> :canceled and tl.status <>:canceled and t.status <> :abandonment and tl.status<> :abandonment and t.countedCanceled <> :true and tl.countedCanceled <> :true')
            ->setParameter('canceled', Ticket::CANCEL_STATUS_VALUE)
            ->setParameter('abandonment', Ticket::ABONDON_STATUS_VALUE);

        $queryBuilder->andWhere(
            '( tl.composition = :true and tl.combo = :false ) OR ( tl.composition =:false and tl.combo = :false)'
        )
            ->setParameter('true', true)
            ->setParameter('false', false);

        $queryBuilder->andWhere('t.counted = :true')
            ->setParameter('true', true);

        if ($tva) {
            $queryBuilder->groupBy('tl.tva');
            $queryBuilder->select(
                '(Sum(tl.totalTTC) - ABS(SUM(COALESCE(tl.discountTtc, 0)))) as totalAmount, tl.tva as tva'
            );

            return $queryBuilder->getQuery()->getResult();
        }

        $queryBuilder->select('(Sum(tl.totalTTC)  - ABS(SUM(COALESCE(tl.discountTtc, 0)))) as totalAmount');
        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getTicketLine($step, $max, $dateStart = null, $dateEnd = null)
    {

        $dateStart = $dateStart->format('Y-m-d');
        $dateEnd = $dateEnd->format('Y-m-d');
        $queryBuilder = $this->createQueryBuilder('tl');
        $queryBuilder->leftJoin('tl.ticket', 't');

        $queryBuilder->where('t.date between :date_start and :date_end')
            ->setParameter('date_start', $dateStart)
            ->setParameter('date_end', $dateEnd);

        $queryBuilder->andWhere(
            '( tl.composition = :true and tl.combo = :false ) OR ( tl.composition =:false and tl.combo = :false)'
        )
            ->setParameter('true', true)
            ->setParameter('false', false);

        $queryBuilder->andWhere('t.counted = :true')
            ->setParameter('true', true);

        $queryBuilder->setMaxResults($max)
            ->setFirstResult($step * $max);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param \DateTime $dateTime
     * @param $origin
     * @param $destination
     * @param $restaurant
     * @return float|int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCaNetDrive(\DateTime $dateTime = null,$origin, $destination,$restaurant){
        if (is_null($dateTime)) {
            $dateTime = new \DateTime('now');
        }
        $dateTime=$dateTime->format('Y-m-d');

        $sql = " SELECT
                ticket_line.parentline           AS      parentline,
                ticket_line.totalttc             AS      total_TTC,
                ticket_line.discount_ttc         AS      discount,
                ticket_payment.id_payment        AS      id_payment
                FROM ticket_line
                JOIN ticket ON ticket_line.ticket_id = ticket.id
                LEFT JOIN ticket_payment ON ticket.id = CAST(ticket_payment.ticket_id AS NUMERIC) AND CAST(ticket_payment.id_payment AS NUMERIC) = 5
                WHERE 
                      ticket.date BETWEEN :startDate AND :endDate AND  
                       ticket.status <> :canceled AND
                      ticket.status <> :abondon AND 
                      ticket.counted_canceled <> TRUE AND
                      ticket.origin_restaurant_id = :restaurant_id ";
        if (!is_null($origin)) {
            $sql .= " AND ticket.origin like :origin";
        }
        if(!is_null($destination)){
            $sql .= " AND ticket.destination like :destination";
        }

        $stm = $this->getEntityManager()->getConnection()->prepare($sql);
        $stm->bindParam('startDate', $dateTime);
        $stm->bindParam('endDate', $dateTime);
        if (!is_null($origin)) {
            $stm->bindParam('origin', $origin);
        }
        if(!is_null($destination)){
            $stm->bindParam('destination', $destination);
        }

        $stm->bindParam('restaurant_id', $restaurant);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $stm->bindParam('canceled', $canceled);
        $abondon = Ticket::ABONDON_STATUS_VALUE;
        $stm->bindParam('abondon', $abondon);


        $stm->execute();
        $results = $stm->fetchAll();
        $resultNet['br']=0;
        $resultNet['total_ttc'] =0;
        $resultNet['discount'] =0;
        foreach ($results as $result) {
            if ($result['id_payment'] == TicketPayment::MEAL_TICKET) {
                $resultNet['br'] += $result['total_ttc'];
            }
            if(empty($result['parentline']) || 0==$result['parentline']){
                $resultNet['total_ttc'] += $result['total_ttc']+$result['discount'];
            }
            $resultNet['total_ttc'] += abs($result['discount']);
            $resultNet['discount'] += abs($result['discount']);
        }
        $resultNet['total']=$resultNet['total_ttc'] - $resultNet['discount'] - $resultNet['br'];
        return $resultNet['total'];
    }

    public function getCaVenteAnnexe($filter, $restaurant_id )
    {

        $conn = $this->_em->getConnection();

        $D1 = $filter['beginDate']; //  transform to D1-1j
        $D2 = $filter['endDate'];
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;

        $sql = "
                    SELECT SUM(TL.totalttc) AS ca_VA FROM public.ticket_line TL join ticket T on TL.ticket_id = T.id
					WHERE TL.origin_restaurant_id = :restaurant AND TL.date >= :D1 AND TL.date <= :D2 AND TL.combo = FALSE 
					AND T.date >= :D1 AND T.date <= :D2 AND T.origin_restaurant_id = :restaurant and T.status <> :canceled AND T.status <> :abandonment AND T.counted_canceled <> TRUE
					AND TL.flag_va = true 

               ";

        $stm = $conn->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->bindParam('restaurant', $restaurant_id);
        $stm->execute();
        $data = $stm->fetchAll();
        $result = [
            "data" => $data,
        ];

        return $result;
    }


}
