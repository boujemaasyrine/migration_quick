<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 23/03/2016
 * Time: 18:29
 */

namespace AppBundle\Financial\Repository;

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketIntervention;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Utils\DateUtilities;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

/**
 * Class TicketRepository
 * @package AppBundle\Financial\Repository
 */
class TicketRepository extends EntityRepository
{

    /**
     * @param $criteria
     * @param Restaurant $currentRestaurant
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCaTicketsPerHourAndOrigin($criteria, Restaurant $currentRestaurant)
    {
        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0))) + ABS(Sum(COALESCE(RIGHT_RESULT.totalDiscount, 0))) AS CA,
                    LEFT_RESULT.origin AS origin, LEFT_RESULT.entryHour AS entryHour,
                    Count(LEFT_RESULT.ticketId) AS countTicket FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC,
                    EXTRACT(HOUR FROM T.enddate) AS entryHour,
                    CASE 
                    WHEN ( (T.origin = 'DriveThru' AND T.destination = 'DriveThru') OR T.destination = 'DRIVE' OR (T.origin = 'MyQuick' AND T.destination = 'MQDrive') OR (T.origin = 'MyQuick' AND T.destination = 'MQCurbside'))  THEN 'drive'
                    WHEN ((T.origin = 'MyQuick' AND T.destination = 'MyQuickEatIn') OR (T.origin = 'MyQuick' AND T.destination = 'MyQuickTakeout')) THEN 'e-ordering' 
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'EatIn') THEN 'kiosk' 
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'TakeOut') THEN 'kiosk'
                    WHEN ((T.origin = 'POS' AND T.destination = 'Delivery') OR (T.origin = 'MyQuick' AND T.destination = 'ATOUberEats') OR (T.origin = 'MyQuick' AND T.destination = 'ATODeliveroo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOTakeAway') OR (T.origin = 'MyQuick' AND T.destination = 'ATOHelloUgo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOEasy2Eat') OR (T.origin = 'MyQuick' AND T.destination = 'ATOGoosty') OR (T.origin = 'MyQuick' AND T.destination = 'ATOWolt')) THEN 'delivery'
                    ELSE 'pos'
                    END
                    AS origin
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id 
                    AND ( T.status <> :canceled AND T.status <> :abandonment AND T.counted_canceled <> TRUE )
                    AND T.date >= :from AND T.date <= :to
                     ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TL.ticket_id AS ticketId,
                    SUM(TL.discount_ttc) AS totalDiscount
                    FROM ticket_line TL
				    WHERE TL.origin_restaurant_id = :origin_restaurant_id AND TL.is_discount = TRUE 
                        AND TL.date >= :from AND TL.date <= :to
                        GROUP BY TL.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
                GROUP BY entryHour, origin
                ORDER BY entryHour ";

        // bind

        $from = $criteria['from']." 00:00:00";
        $to = $criteria['to']." 23:59:59";
        $stm = $conn->prepare($sql);
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $currentRestaurantId = $currentRestaurant->getId();
        $stm->bindParam("origin_restaurant_id", $currentRestaurantId);
        $stm->execute();
        $data = $stm->fetchAll();
        // select canceled tickets grouped also by entry hour and origin
        $sql = "SELECT  LEFT_RESULT.origin AS origin, LEFT_RESULT.entryHour AS entryHour,
                    Count(LEFT_RESULT.ticketId) AS countTicket FROM (
                SELECT
                    T.id AS ticketId,
                    EXTRACT(HOUR FROM T.enddate) AS entryHour,
                   CASE 
                    WHEN ( (T.origin = 'DriveThru' AND T.destination = 'DriveThru') OR T.destination = 'DRIVE' OR (T.origin = 'MyQuick' AND T.destination = 'MQDrive') OR (T.origin = 'MyQuick' AND T.destination = 'MQCurbside'))  THEN 'drive'
                    WHEN ((T.origin = 'MyQuick' AND T.destination = 'MyQuickEatIn') OR (T.origin = 'MyQuick' AND T.destination = 'MyQuickTakeout')) THEN 'e-ordering' 
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'EatIn') THEN 'kiosk' 
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'TakeOut') THEN 'kiosk'
                    WHEN ((T.origin = 'POS' AND T.destination = 'Delivery') OR (T.origin = 'MyQuick' AND T.destination = 'ATOUberEats') OR (T.origin = 'MyQuick' AND T.destination = 'ATODeliveroo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOTakeAway') OR (T.origin = 'MyQuick' AND T.destination = 'ATOHelloUgo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOEasy2Eat') OR (T.origin = 'MyQuick' AND T.destination = 'ATOGoosty') OR (T.origin = 'MyQuick' AND T.destination = 'ATOWolt')) THEN 'delivery'
                    ELSE 'pos'
                    END
                    AS origin
                    FROM public.ticket T
                    WHERE ( T.origin_restaurant_id = :origin_restaurant_id AND T.status <> :canceled AND T.status <> :abandonment 
                    AND T.invoiceCancelled = '1' )
                     AND T.date >= :from AND T.date <= :to
                     ) AS  LEFT_RESULT
                     
                GROUP BY entryHour, origin
                ORDER BY entryHour";

        // bind

        $stm = $conn->prepare($sql);
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->bindParam("origin_restaurant_id", $currentRestaurantId);
        $stm->execute();
        $cancels = $stm->fetchAll();
        $hours = array_column($data, 'entryhour');
        foreach ($cancels as $value) {
            $key = array_search($value['entryhour'], $hours);
            while ($data[$key]['origin'] != $value['origin']) {
                $key = array_search($value['entryhour'], array_slice($hours, $key + 1, null, true));
            }
            $data[$key]["countticket"] -= (2 * $value['countticket']);
        }

        return $data;
    }

    /**
     * @param $criteria
     * @param Restaurant $restaurant
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCaHTvaPerHourAndOrigin($criteria, Restaurant $restaurant)
    {
        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;

        $sql = "
                SELECT SUM((COALESCE(ca_ht, 0) - COALESCE(br_ht, 0))) AS CA, entryhour, origin, Count(LEFT_RESULT.ticketId) AS countTicket
                 FROM (
                        SELECT
                        T.id AS ticketId,
                        T.totalht AS CA_HT,
                        EXTRACT(HOUR FROM T.enddate) AS entryHour,
                        CASE 
                    WHEN ( (T.origin = 'DriveThru' AND T.destination = 'DriveThru') OR T.destination = 'DRIVE' OR (T.origin = 'MyQuick' AND T.destination = 'MQDrive') OR (T.origin = 'MyQuick' AND T.destination = 'MQCurbside') )  THEN 'drive'
                    WHEN ((T.origin = 'MyQuick' AND T.destination = 'MyQuickEatIn') OR (T.origin = 'MyQuick' AND T.destination = 'MyQuickTakeout')) THEN 'e-ordering'
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'EatIn') THEN 'kiosk' 
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'TakeOut') THEN 'kiosk'
                    WHEN ((T.origin = 'POS' AND T.destination = 'Delivery') OR (T.origin = 'MyQuick' AND T.destination = 'ATOUberEats') OR (T.origin = 'MyQuick' AND T.destination = 'ATODeliveroo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOTakeAway') OR (T.origin = 'MyQuick' AND T.destination = 'ATOHelloUgo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOEasy2Eat') OR (T.origin = 'MyQuick' AND T.destination = 'ATOGoosty') OR (T.origin = 'MyQuick' AND T.destination = 'ATOWolt')) THEN 'delivery'
                    ELSE 'pos'
                    END
                    AS origin
                        FROM public.ticket T
                        WHERE T.origin_restaurant_id = :restaurant_id AND ( T.status <> :canceled 
                        AND T.status <> :abandonment AND T.counted_canceled <> TRUE )
                         AND T.date >= :from AND T.date <= :to
                     ) AS  LEFT_RESULT
                LEFT JOIN (

			SELECT SS1.id AS ticket_id, SUM(total_payment * percent_tva * percent_payment * ((100 - tva) / 100)) AS br_ht
			FROM(
				SELECT S2.id, total_amount AS total_payment
				    , CASE WHEN S2.total_amount = 0 
				        THEN 100 
				        ELSE (S1.voucher_amount / S2.total_amount) END AS percent_payment
				FROM(   
					SELECT t.id AS id_ticket, SUM(TP.amount) AS voucher_amount 
					FROM public.ticket_payment TP
					LEFT JOIN  public.ticket t ON t.id = TP.ticket_id
					WHERE (TP.id_payment = :mealTicket AND t.date >= :from AND t.date <= :to AND t.origin_restaurant_id = :restaurant_id) GROUP BY t.id
				) AS S1
				LEFT JOIN(
					SELECT t.id, SUM(tp.amount) AS total_amount
					FROM ticket_payment tp JOIN ticket t ON t.id = tp.ticket_id
					WHERE t.date >= :from AND t.date <= :to AND t.origin_restaurant_id = :restaurant_id
					GROUP BY t.id
				) AS S2
				ON S2.id = S1.id_ticket
			) AS SS1
			LEFT JOIN(
				SELECT S3.id, S3.tva AS tva, CASE WHEN S4.totalttc = 0 THEN 100 ELSE (S3.totalttc_tva / S4.totalttc) END AS percent_tva
				FROM(
					SELECT tl.ticket_id as id, tl.tva, SUM(tl.totalttc) AS totalttc_tva
					FROM ticket_line tl
					WHERE tl.date >= :from AND tl.date <= :to AND tl.origin_restaurant_id = :restaurant_id
					GROUP BY tl.ticket_id, tl.tva
					ORDER BY tl.ticket_id
				) AS S3
				LEFT JOIN(
					SELECT tl.ticket_id as id, SUM(tl.totalttc) AS totalttc
					FROM ticket_line tl 
					WHERE tl.date >= :from AND tl.date <= :to AND tl.origin_restaurant_id = :restaurant_id
					GROUP BY tl.ticket_id
					ORDER BY tl.ticket_id
				)AS S4
				ON S3.id = S4.id
			) AS SS2
			ON SS1.id = SS2.id
			GROUP BY SS1.id

                ) AS BR_HT_RESULT 
		ON BR_HT_RESULT.ticket_id = LEFT_RESULT.TicketId
		GROUP BY entryhour, origin
		ORDER BY entryhour";

        // bind

        $stm = $conn->prepare($sql);
        $from = $criteria['from']." 00:00:00";
        $to = $criteria['to']." 23:59:59";
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('mealTicket', $mealTicket);
        $restaurant_id = $restaurant->getId();
        $stm->bindParam('restaurant_id', $restaurant_id);

        $stm->execute();
        $data = $stm->fetchAll();

        // select canceled tickets grouped also by entry hour and origin
        $sql = "SELECT  LEFT_RESULT.origin AS origin, LEFT_RESULT.entryHour AS entryHour,
                    Count(LEFT_RESULT.ticketId) AS countTicket FROM (
                SELECT
                    T.id AS ticketId,
                    EXTRACT(HOUR FROM T.enddate) AS entryHour,
                    CASE 
                    WHEN ( (T.origin = 'DriveThru' AND T.destination = 'DriveThru') OR T.destination = 'DRIVE' OR (T.origin = 'MyQuick' AND T.destination = 'MQDrive') OR (T.origin = 'MyQuick' AND T.destination = 'MQCurbside'))  THEN 'drive'
                    WHEN ((T.origin = 'MyQuick' AND T.destination = 'MyQuickEatIn') OR (T.origin = 'MyQuick' AND T.destination = 'MyQuickTakeout')) THEN 'e-ordering'  
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'EatIn') THEN 'kiosk' 
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'TakeOut') THEN 'kiosk'
                    WHEN ((T.origin = 'POS' AND T.destination = 'Delivery') OR (T.origin = 'MyQuick' AND T.destination = 'ATOUberEats') OR (T.origin = 'MyQuick' AND T.destination = 'ATODeliveroo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOTakeAway') OR (T.origin = 'MyQuick' AND T.destination = 'ATOHelloUgo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOEasy2Eat') OR (T.origin = 'MyQuick' AND T.destination = 'ATOGoosty') OR (T.origin = 'MyQuick' AND T.destination = 'ATOWolt')) THEN 'delivery'
                    ELSE 'pos'
                    END
                    AS origin
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :restaurant_id AND ( T.status <> :canceled AND T.status <> :abandonment AND T.invoiceCancelled = '1' )
                     AND T.date >= :from AND T.date <= :to
                     ) AS  LEFT_RESULT
                     
                GROUP BY entryHour, origin
                ORDER BY entryHour";

        // bind

        $stm = $conn->prepare($sql);
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->bindParam('restaurant_id', $restaurant_id);
        $stm->execute();
        $cancels = $stm->fetchAll();

        $hours = array_column($data, 'entryhour');
        foreach ($cancels as $value) {
            $key = array_search($value['entryhour'], $hours);
            while ($data[$key]['origin'] != $value['origin']) {
                $key = array_search($value['entryhour'], array_slice($hours, $key + 1, null, true));
            }
            $data[$key]["countticket"] -= (2 * $value['countticket']);
        }

        return $data;
    }

    /**
     * @param $criteria
     * @param Restaurant $restaurant
     * @param $schedule
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCaHTvaPerHalfOrQuarterHourAndOrigin($criteria, Restaurant $restaurant, $schedule)
    {
        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;

        $sql = "
                SELECT SUM((COALESCE(ca_ht, 0) - COALESCE(br_ht, 0))) AS CA, entryhour, origin, Count(LEFT_RESULT.ticketId) AS countTicket, schedule
                 FROM (
                        SELECT
                        T.id AS ticketId,
                        T.totalht AS CA_HT,
                        EXTRACT(HOUR FROM T.enddate) AS entryHour,
                        CASE ";

        if ($schedule == 1) {
            $sql .= "WHEN extract(minute from enddate) >= 30 THEN 1 ";
        } else {
            $sql .= "WHEN extract(minute from enddate) >= 15 and extract(minute from enddate) < 30 THEN 1
		              WHEN extract(minute from enddate) >= 30 and extract(minute from enddate) < 45 THEN 2
		              WHEN extract(minute from enddate) >= 45 THEN 3 ";
        }

        $sql .= "
                    ELSE
                     0
                    END as schedule,
                    CASE 
                    WHEN ( (T.origin = 'DriveThru' AND T.destination = 'DriveThru') OR T.destination = 'DRIVE' OR (T.origin = 'MyQuick' AND T.destination = 'MQDrive') OR (T.origin = 'MyQuick' AND T.destination = 'MQCurbside') )  THEN 'drive'
                    WHEN ((T.origin = 'MyQuick' AND T.destination = 'MyQuickEatIn') OR (T.origin = 'MyQuick' AND T.destination = 'MyQuickTakeout')) THEN 'e-ordering'  
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'EatIn') THEN 'kiosk' 
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'TakeOut') THEN 'kiosk'
                    WHEN ((T.origin = 'POS' AND T.destination = 'Delivery') OR (T.origin = 'MyQuick' AND T.destination = 'ATOUberEats') OR (T.origin = 'MyQuick' AND T.destination = 'ATODeliveroo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOTakeAway') OR (T.origin = 'MyQuick' AND T.destination = 'ATOHelloUgo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOEasy2Eat') OR (T.origin = 'MyQuick' AND T.destination = 'ATOGoosty') OR (T.origin = 'MyQuick' AND T.destination = 'ATOWolt')) THEN 'delivery'
                    ELSE 'pos'
                    END
                    AS origin
                        FROM public.ticket T
                        WHERE T.origin_restaurant_id = :restaurant_id and ( T.status <> :canceled and T.status <> :abandonment and T.counted_canceled <> TRUE ) and
                         T.date >= :from and T.date <= :to
                     ) as  LEFT_RESULT
                left join (

			SELECT SS1.id as ticket_id, SUM(total_payment * percent_tva * percent_payment * ((100 - tva) / 100)) as br_ht
			FROM(
				SELECT S2.id, total_amount as total_payment, Case when S2.total_amount = 0 then 100 Else (S1.voucher_amount / S2.total_amount) End as percent_payment
				FROM(   
					SELECT t.id as id_ticket, SUM(TP.amount) as voucher_amount 
					From public.ticket_payment TP
					LEFT JOIN  public.ticket t on t.id = TP.ticket_id
					WHERE (TP.id_payment = :mealTicket and t.date >= :from and t.date <= :to AND t.origin_restaurant_id = :restaurant_id) GROUP BY t.id
				) as S1
				LEFT JOIN(
					SELECT t.id, SUM(tp.amount) as total_amount
					FROM ticket_payment tp JOIN ticket t on t.id = tp.ticket_id
					WHERE t.date >= :from and t.date <= :to AND t.origin_restaurant_id = :restaurant_id
					GROUP BY t.id
				) as S2
				ON S2.id = S1.id_ticket
			) as SS1
			LEFT JOIN(
				SELECT S3.id, S3.tva as tva, Case when S4.totalttc = 0 then 100 Else (S3.totalttc_tva / S4.totalttc) End as percent_tva
				FROM(
					SELECT tl.ticket_id as id, tl.tva, SUM(tl.totalttc) as totalttc_tva
					FROM ticket_line tl 
					WHERE tl.date >= :from and tl.date <= :to AND tl.origin_restaurant_id = :restaurant_id
					GROUP BY tl.ticket_id, tl.tva
					ORDER BY tl.ticket_id
				) as S3
				LEFT JOIN(
					SELECT tl.ticket_id as id, SUM(tl.totalttc) as totalttc
					FROM ticket_line tl 
					WHERE tl.date >= :from and tl.date <= :to  AND tl.origin_restaurant_id = :restaurant_id
					GROUP BY tl.ticket_id
					ORDER BY tl.ticket_id
				)as S4
				ON S3.id = S4.id
			) as SS2
			ON SS1.id = SS2.id
			GROUP BY SS1.id

                ) as BR_HT_RESULT 
		on BR_HT_RESULT.ticket_id = LEFT_RESULT.TicketId
		group by entryhour, origin, schedule
		order by entryhour, schedule";

        // bind

        $stm = $conn->prepare($sql);
        $from = $criteria['from']." 00:00:00";
        $to = $criteria['to']." 23:59:59";
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('mealTicket', $mealTicket);
        $restaurant_id = $restaurant->getId();
        $stm->bindParam('restaurant_id', $restaurant_id);

        $stm->execute();
        $data = $stm->fetchAll();

        // select canceled tickets grouped also by entry hour and origin
        $sql = "SELECT  LEFT_RESULT.origin AS origin, LEFT_RESULT.entryHour AS entryHour, schedule,
                    Count(LEFT_RESULT.ticketId) AS countTicket FROM (
                SELECT
                    T.id AS ticketId,
                    EXTRACT(HOUR FROM T.enddate) AS entryHour,
                    CASE ";
        if ($schedule == 1) {
            $sql .= "WHEN extract(minute from enddate) >= 30 THEN 1 ";
        } else {
            $sql .= "WHEN extract(minute from enddate) >= 15 and extract(minute from enddate) < 30 THEN 1
		              WHEN extract(minute from enddate) >= 30 and extract(minute from enddate) < 45 THEN 2
		              WHEN extract(minute from enddate) >= 45 THEN 3 ";
        }

        $sql .= "ELSE
                     0
                    END as schedule,
                    CASE 
                    WHEN ( (T.origin = 'DriveThru' AND T.destination = 'DriveThru') OR T.destination = 'DRIVE' OR (T.origin = 'MyQuick' AND T.destination = 'MQDrive') OR (T.origin = 'MyQuick' AND T.destination = 'MQCurbside') )  THEN 'drive'
                    WHEN ((T.origin = 'MyQuick' AND T.destination = 'MyQuickEatIn') OR (T.origin = 'MyQuick' AND T.destination = 'MyQuickTakeout')) THEN 'e-ordering'  
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'EatIn') THEN 'kiosk' 
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'TakeOut') THEN 'kiosk'
                    WHEN ((T.origin = 'POS' AND T.destination = 'Delivery') OR (T.origin = 'MyQuick' AND T.destination = 'ATOUberEats') OR (T.origin = 'MyQuick' AND T.destination = 'ATODeliveroo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOTakeAway') OR (T.origin = 'MyQuick' AND T.destination = 'ATOHelloUgo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOEasy2Eat') OR (T.origin = 'MyQuick' AND T.destination = 'ATOGoosty') OR (T.origin = 'MyQuick' AND T.destination = 'ATOWolt')) THEN 'delivery'
                    ELSE 'pos'
                    END
                    AS origin
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :restaurant_id and ( T.status <> :canceled and T.status <> :abandonment and T.invoiceCancelled = '1' )
                     and T.date >= :from and T.date <= :to
                     ) as  LEFT_RESULT
                     
                GROUP BY entryHour, origin, schedule
                ORDER BY entryHour, schedule";

        // bind

        $stm = $conn->prepare($sql);
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->bindParam('restaurant_id', $restaurant_id);
        $stm->execute();
        $cancels = $stm->fetchAll();
        $hours = array_column($data, 'entryhour');
        foreach ($cancels as $value) {
            $key = array_search($value['entryhour'], $hours);
            while ($data[$key]['schedule'] != $value['schedule'] or $data[$key]['origin'] != $value['origin']) {
                $key = array_search($value['entryhour'], array_slice($hours, $key + 1, null, true));
            }
            $data[$key]["countticket"] -= (2 * $value['countticket']);
        }

        return $data;
    }

    /**
     * @param $criteria
     * @param Restaurant $currentRestaurant
     * @param $schedule
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getCaTicketsPerHalfOrQuarterHourAndOrigin($criteria, Restaurant $currentRestaurant, $schedule)
    {
        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;

        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0) + ABS(COALESCE(RIGHT_RESULT.totalDiscount, 0)))) AS CA,
                    LEFT_RESULT.origin AS origin, LEFT_RESULT.schedule, LEFT_RESULT.hour AS entryhour,
                    Count(LEFT_RESULT.ticketId) AS countTicket FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC,
                    CASE ";
        if ($schedule == 1) {
            $sql .= "WHEN extract(minute from enddate) >= 30 THEN 1 ";
        } else {
            $sql .= "WHEN extract(minute from enddate) >= 15 and extract(minute from enddate) < 30 THEN 1
		              WHEN extract(minute from enddate) >= 30 and extract(minute from enddate) < 45 THEN 2
		              WHEN extract(minute from enddate) >= 45 THEN 3 ";
        }

        $sql .= "
                    ELSE
                     0
                    END as schedule,
                    extract(hour from enddate) as hour,
                    CASE 
                    WHEN ( (T.origin = 'DriveThru' AND T.destination = 'DriveThru') OR T.destination = 'DRIVE' OR (T.origin = 'MyQuick' AND T.destination = 'MQDrive') OR (T.origin = 'MyQuick' AND T.destination = 'MQCurbside') )  THEN 'drive'
                    WHEN ((T.origin = 'MyQuick' AND T.destination = 'MyQuickEatIn') OR (T.origin = 'MyQuick' AND T.destination = 'MyQuickTakeout')) THEN 'e-ordering'
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'EatIn') THEN 'kiosk' 
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'TakeOut') THEN 'kiosk'
                    WHEN ((T.origin = 'POS' AND T.destination = 'Delivery') OR (T.origin = 'MyQuick' AND T.destination = 'ATOUberEats') OR (T.origin = 'MyQuick' AND T.destination = 'ATODeliveroo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOTakeAway') OR (T.origin = 'MyQuick' AND T.destination = 'ATOHelloUgo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOEasy2Eat') OR (T.origin = 'MyQuick' AND T.destination = 'ATOGoosty') OR (T.origin = 'MyQuick' AND T.destination = 'ATOWolt')) THEN 'delivery'
                    ELSE 'pos'
                    END
                    AS origin
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id and ( T.status <> :canceled and T.status <> :abandonment and T.counted_canceled <> TRUE )
                    and T.date >= :from and T.date <= :to
                     ) as  LEFT_RESULT

                left join (
                    SELECT
                    TL.ticket_id as ticketId,
                    SUM(TL.discount_ttc) as totalDiscount
                    from ticket_line TL
                        where TL.origin_restaurant_id = :origin_restaurant_id and TL.is_discount = true  and TL.date >= :from and TL.date <= :to 
                        GROUP BY TL.ticket_id
                ) as RIGHT_RESULT
                on LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
                GROUP BY schedule, entryhour, origin
                ORDER BY entryhour, schedule";

        // bind

        $from = $criteria['from']." 00:00:00";
        $to = $criteria['to']." 23:59:59";
        $stm = $conn->prepare($sql);
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $currentRestaurantId = $currentRestaurant->getId();
        $stm->bindParam("origin_restaurant_id", $currentRestaurantId);
        $stm->execute();
        $data = $stm->fetchAll();

        // select canceled tickets grouped also by entry hour and origin
        $sql = "SELECT  LEFT_RESULT.origin AS origin, LEFT_RESULT.schedule, LEFT_RESULT.hour AS entryhour,
                Count(LEFT_RESULT.ticketId) AS countTicket FROM (
                SELECT
                    T.id AS ticketId,
                    CASE ";
        if ($schedule == 1) {
            $sql .= "WHEN extract(minute from enddate) >= 30 THEN 1 ";
        } else {
            $sql .= "WHEN extract(minute from enddate) >= 15 and extract(minute from enddate) < 30 THEN 1
		              WHEN extract(minute from enddate) >= 30 and extract(minute from enddate) < 45 THEN 2
		              WHEN extract(minute from enddate) >= 45 THEN 3 ";
        }
        $sql .= "
                    ELSE
                     0
                    END as schedule,
                     extract(hour from enddate) as hour,
                    CASE 
                    WHEN ( (T.origin = 'DriveThru' AND T.destination = 'DriveThru') OR T.destination = 'DRIVE' OR (T.origin = 'MyQuick' AND T.destination = 'MQDrive') OR (T.origin = 'MyQuick' AND T.destination = 'MQCurbside'))  THEN 'drive'
                    WHEN ((T.origin = 'MyQuick' AND T.destination = 'MyQuickEatIn') OR (T.origin = 'MyQuick' AND T.destination = 'MyQuickTakeout')) THEN 'e-ordering'
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'EatIn') THEN 'kiosk' 
                    WHEN (T.origin = 'KIOSK' AND T.destination = 'TakeOut') THEN 'kiosk'
                    WHEN ((T.origin = 'POS' AND T.destination = 'Delivery') OR (T.origin = 'MyQuick' AND T.destination = 'ATOUberEats') OR (T.origin = 'MyQuick' AND T.destination = 'ATODeliveroo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOTakeAway') OR (T.origin = 'MyQuick' AND T.destination = 'ATOHelloUgo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOEasy2Eat') OR (T.origin = 'MyQuick' AND T.destination = 'ATOGoosty') OR (T.origin = 'MyQuick' AND T.destination = 'ATOWolt')) THEN 'delivery'
                    ELSE 'pos'
                    END
                    AS origin
                    FROM public.ticket T
                    WHERE ( T.origin_restaurant_id = :origin_restaurant_id and T.status <> :canceled and T.status <> :abandonment and T.invoiceCancelled = '1' )
                     and T.date >= :from and T.date <= :to
                     ) as  LEFT_RESULT
                     
                GROUP BY schedule, entryhour, origin
                ORDER BY entryhour, schedule";

        // bind

        $stm = $conn->prepare($sql);
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->bindParam("origin_restaurant_id", $currentRestaurantId);
        $stm->execute();
        $cancels = $stm->fetchAll();
        $hours = array_column($data, 'entryhour');
        foreach ($cancels as $value) {
            $key = array_search($value['entryhour'], $hours);
            while ($data[$key]['schedule'] != $value['schedule'] or $data[$key]['origin'] != $value['origin']) {
                $key = array_search($value['entryhour'], array_slice($hours, $key + 1, null, true));
            }
            $data[$key]["countticket"] -= (2 * $value['countticket']);
        }

        return $data;
    }

     /**
     * @param $previousDate
     * @return array
     */
    public function getTotalPerDayWeek($previousDate)
    {
        $firstDateFrom = $previousDate['0']->format('Y-m-d')." 00:00:00";
        $firstDateTo = $previousDate['0']->format('Y-m-d')." 23:59:59";
        $secondDateFrom = $previousDate['1']->format('Y-m-d')." 00:00:00";
        $secondDateTo = $previousDate['1']->format('Y-m-d')." 23:59:59";
        $thirdDateFrom = $previousDate['2']->format('Y-m-d')." 00:00:00";
        $thirdDateTo = $previousDate['2']->format('Y-m-d')." 23:59:59";
        $fourthDateFrom = $previousDate['3']->format('Y-m-d')." 00:00:00";
        $fourthDateTo = $previousDate['3']->format('Y-m-d')." 23:59:59";


        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $queryBuilder = $this->createQueryBuilder('t');

        $queryBuilder->andWhere(
            '(t.endDate >= :dateFrom0 and t.endDate <= :dateTo0) or (t.endDate >= :dateFrom1 and t.endDate <= :dateTo1)
                or (t.endDate >= :dateFrom2 and t.endDate <= :dateTo2) or (t.endDate >= :dateFrom3 and t.endDate <= :dateTo3)'
        )
            ->setParameter('dateFrom0', $firstDateFrom)
            ->setParameter('dateTo0', $firstDateTo)
            ->setParameter('dateFrom1', $secondDateFrom)
            ->setParameter('dateTo1', $secondDateTo)
            ->setParameter('dateFrom2', $thirdDateFrom)
            ->setParameter('dateTo2', $thirdDateTo)
            ->setParameter('dateFrom3', $fourthDateFrom)
            ->setParameter('dateTo3', $fourthDateTo);
        $queryBuilder->andWhere('t.status <> :canceled and t.status <> :abandonment  and t.countedCanceled <> TRUE')
            ->setParameter('canceled', $canceled)
            ->setParameter('abandonment', $abandonment);

        $queryBuilder->select('SUM(t.totalTTC) AS total');
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    /**
     * @param $previousDate
     * @param Restaurant $currentRestaurant
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTotalPerHour($previousDate, Restaurant $currentRestaurant)
    {

        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0) + ABS(COALESCE(RIGHT_RESULT.totalDiscount, 0)))) AS total,
                  LEFT_RESULT.entryHour AS entryHour FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC,
                    EXTRACT(HOUR FROM T.enddate) AS entryHour

                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id AND ( T.status <> :canceled 
                    AND T.status <> :abandonment  AND T.counted_canceled <> TRUE)  AND T.date IN (:date0, :date1, :date2, :date3)
                     ) AS  LEFT_RESULT

                LEFT JOIN (
                  SELECT
                    TL.ticket_id AS ticketId,
                    SUM(TL.discount_ttc) AS totalDiscount
                    FROM ticket_line TL 
					WHERE TL.origin_restaurant_id = :origin_restaurant_id AND TL.is_discount = TRUE AND TL.date IN (:date0, :date1, :date2, :date3)
                         GROUP BY TL.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
                GROUP BY entryHour
                ORDER BY entryHour";

        //        $sql = "  SELECT SUM((COALESCE(LEFT_RESULT.total, 0) + ABS(COALESCE(RIGHT_RESULT.totalDiscount, 0)))) as total,
        //                    LEFT_RESULT.entryHour as entryHour,
        //                    Count(LEFT_RESULT.ticketId) as countTicket FROM (
        //                  SELECT
        //                    T.id as ticketId,
        //                    SUM(T.totalttc) AS total,
        //                    EXTRACT(HOUR FROM T.enddate) as entryHour
        //
        //                    FROM public.ticket T
        //                    WHERE ( T.status <> :canceled and T.status <> :abandonment ) and
        //                    (t.date in (:date0, :date1, :date2, :date3))) as LEFT_RESULT
        //                    left join (
        //                    SELECT
        //                    TL.ticket_id as ticketId,
        //                    SUM(TL.discount_ttc) as totalDiscount
        //                    from ticket_line TL
        //                    LEFT JOIN ticket T on T.id = TL.ticket_id
        //                        where TL.is_discount = true GROUP BY TL.ticket_id
        //                ) as RIGHT_RESULT
        //                on LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
        //                    GROUP BY entryHour
        //                    ORDER BY entryHour";
        $stm = $conn->prepare($sql);
        if($previousDate['0'] != null){
            $date0 = $previousDate['0']->format('Y-m-d');
        }else{
            $date0 = null;
        }
        if($previousDate['1'] != null){
            $date1 = $previousDate['1']->format('Y-m-d');
        }else{
            $date1 = null;
        }
        if($previousDate['2'] != null){
            $date2 = $previousDate['2']->format('Y-m-d');
        }else{
            $date2 = null;
        }
        if($previousDate['3'] != null){
            $date3 = $previousDate['3']->format('Y-m-d');
        }else{
            $date3 = null;
        }
        $stm->bindParam('date0', $date0);
        $stm->bindParam('date1', $date1);
        $stm->bindParam('date2', $date2);
        $stm->bindParam('date3', $date3);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $currentRestaurantId = $currentRestaurant->getId();
        $stm->bindParam("origin_restaurant_id", $currentRestaurantId);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }

    public function getTotalPerHalfOrQuarterHour($previousDate, Restaurant $currentRestaurant, $schedule)
    {

        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;

        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0) + ABS(COALESCE(RIGHT_RESULT.totalDiscount, 0)))) AS total,
                  LEFT_RESULT.entryHour AS entryHour, schedule FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC,
		    CASE ";
        if ($schedule == 1) {
            $sql .= "WHEN extract(minute from enddate) >= 30 THEN 1 ";
        } else {
            $sql .= "WHEN extract(minute from enddate) >= 15 and extract(minute from enddate) < 30 THEN 1
		              WHEN extract(minute from enddate) >= 30 and extract(minute from enddate) < 45 THEN 2
		              WHEN extract(minute from enddate) >= 45 THEN 3 ";
        }
        $sql .= " ELSE 0
                    END as schedule,
                    EXTRACT(HOUR FROM T.enddate) as entryHour

                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id and ( T.status <> :canceled 
                    and T.status <> :abandonment  and T.counted_canceled <> TRUE) 
                    and T.date in (:date0, :date1, :date2, :date3)
                     ) as  LEFT_RESULT

                left join (
                    SELECT
                    TL.ticket_id as ticketId,
                    SUM(TL.discount_ttc) as totalDiscount
                    from ticket_line TL                  
                        where TL.origin_restaurant_id = :origin_restaurant_id and TL.is_discount = true 
                        and TL.date in (:date0, :date1, :date2, :date3)
                        GROUP BY TL.ticket_id
                ) as RIGHT_RESULT
                on LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
                GROUP BY entryHour, schedule
                ORDER BY entryHour, schedule";

        $stm = $conn->prepare($sql);
        if($previousDate['0'] != null){
            $date0 = $previousDate['0']->format('Y-m-d');
        }else{
            $date0 = null;
        }
        if($previousDate['1'] != null){
            $date1 = $previousDate['1']->format('Y-m-d');
        }else{
            $date1 = null;
        }
        if($previousDate['2'] != null){
            $date2 = $previousDate['2']->format('Y-m-d');
        }else{
            $date2 = null;
        }
        if($previousDate['3'] != null){
            $date3 = $previousDate['3']->format('Y-m-d');
        }else{
            $date3 = null;
        }


        $stm->bindParam('date0', $date0);
        $stm->bindParam('date1', $date1);
        $stm->bindParam('date2', $date2);
        $stm->bindParam('date3', $date3);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $currentRestaurantId = $currentRestaurant->getId();
        $stm->bindParam("origin_restaurant_id", $currentRestaurantId);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }

    public function getTotalPerDay($date, $onlyNbrTicket = false, $restaurant = null)
    {
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $queryBuilder = $this->createQueryBuilder('t');

        $queryBuilder->andWhere('t.date = :date')
            ->setParameter('date', $date);

        $queryBuilder->andWhere('t.status <> :canceled and t.status <> :abandonment  and t.countedCanceled <> TRUE')
            ->setParameter('canceled', $canceled)
            ->setParameter('abandonment', $abandonment);

        if ($restaurant != null) {
            $queryBuilder->andWhere('t.originRestaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        if ($onlyNbrTicket) {
            $queryBuilder->select('count(t) AS nbrTicket');
            $nbtickets = $queryBuilder->getQuery()->getSingleScalarResult();
            $queryBuilder = $this->createQueryBuilder('t');
            $queryBuilder->andWhere('t.date = :date')
                ->setParameter('date', $date)
                ->andWhere('t.invoiceCancelled = :true')
                ->setParameter('true', '1');
            if ($restaurant != null) {
                $queryBuilder->andWhere("t.originRestaurant = :restaurant")
                    ->setParameter("restaurant", $restaurant);
            }

            $queryBuilder->select('count(t) AS nbrCancels');
            $cancelledTickets = $queryBuilder->getQuery()->getSingleScalarResult();

            return $nbtickets - (2 * $cancelledTickets);
        } else {
            $queryBuilder->select('SUM(t.totalTTC) AS total');
        }
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }

    public function getUserPerDay($date, $restaurant = null)
    {

        $queryBuilder = $this->createQueryBuilder('t');

        $queryBuilder->orWhere('t.date = :date')
            ->setParameter('date', $date);

        $queryBuilder->andWhere('t.counted = :false')
            ->setParameter('false', false);

        if (isset($restaurant)) {
            $queryBuilder->andWhere('t.originRestaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        $queryBuilder->select('t.operator');

        $result = $queryBuilder->getQuery()->getArrayResult();

        return $result;
    }

    /**
     * This function will return all cashier tickets that their endDate is between the start of the day and $dateTime
     *
     * @param  \DateTime $dateTime
     * @param  Employee $cashier
     * @return array
     */
    public function getDayTicketsForCashier(
        \DateTime $dateTime,
        Employee $cashier = null,
        $allTheDayTickets = false,
        $restaurant = null
    ) {
        $startDay = clone $dateTime;
        $startDay->setTime(0, 0, 0);
        $endDay = clone $dateTime;

        if ($allTheDayTickets) {
            $endDay->add(new \DateInterval('P1D'));
            $endDay->setTime(0, 0, 0);
        } else {
            if (DateUtilities::isToday($dateTime)) {
                $endDay = new \DateTime('now');
            }
        }

        $qb = $this->_em->getRepository('Financial:Ticket')->createQueryBuilder('ticket');
        $qb->select('ticket')
            ->where('ticket.date = :startDay')
            ->setParameter('startDay', $startDay)
            //->andWhere('ticket.endDate < :endDate')
            //->setParameter('endDate', $endDay)
            ->andWhere('ticket.counted = FALSE');
        if (!is_null($cashier)) {
            $qb->andWhere('ticket.operator = :operator')
                ->setParameter('operator', $cashier->getWyndId());
        }

        if (isset($restaurant)) {
            $qb->andWhere('ticket.originRestaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        $tickets = $qb->getQuery()->getResult();

        return $tickets;
    }

    /**
     * @param \DateTime $dateTime
     * @param null $origin
     * @param null $destination
     * @return array
     */
    public function getDayTicketsForCanal(
        \DateTime $dateTime = null,
        $origin = null,
        $destination = null,
        $restaurant = null
    ) {
        if (is_null($dateTime)) {
            $dateTime = new \DateTime('now');
        }

        $startDay = clone $dateTime;
        $startDay->setTime(0, 0, 0);

        $endDay = clone $dateTime;
        $endDay->add(new \DateInterval('P1D'));
        $endDay->setTime(0, 0, 0);

        $qb = $this->_em->getRepository('Financial:Ticket')->createQueryBuilder('ticket');
        $qb->select('COALESCE(SUM(ticket.totalTTC), 0)')
            ->where('ticket.date = :startDay')
            ->setParameter('startDay', $startDay)
            ->andWhere('ticket.endDate < :endDate')
            ->setParameter('endDate', $endDay);
        if ($origin) {
            $qb->andWhere('ticket.origin like :origin')
                ->setParameter('origin', $origin);
        }
        if ($destination) {
            $qb->andWhere('ticket.destination = :destination')
                ->setParameter('destination', $destination);
        }

        if (isset($restaurant)) {
            $qb->andWhere('ticket.originRestaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }


        $tickets = $qb->getQuery()->getSingleScalarResult();

        return $tickets;
    }

    public function getCaTicket($filter, $restaurant_id = null)
    {

        $conn = $this->_em->getConnection();

        $D1 = $filter['beginDate']; //  transform to D1-1j
        $D2 = $filter['endDate'];
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $true = true;

        $sql = "SELECT SUM( LEFT_RESULT.totalht ) AS caBrutHt, SUM( LEFT_RESULT.totalttc ) AS caBrutTtc,
                SUM( LAST_RESULT.discount_amount ) AS totalDiscount,SUM( LAST_RESULT.discount_ht ) AS totalDiscountHt, SUM( VA_RESULT.VA_HT ) AS VA_HT,  SUM( VA_RESULT.VA_TTC ) AS VA_TTC FROM (
                SELECT
                t.id AS ticket_id,
                t.totalht AS totalht,
                t.totalttc AS totalttc

                FROM public.ticket t
                WHERE ( T.status <> :canceled AND T.status <> :abandonment AND T.counted_canceled <> TRUE AND T.origin_restaurant_id = :restaurant) AND t.date >= :D1 AND t.date <= :D2 ) AS LEFT_RESULT

                LEFT JOIN
                (
                    SELECT
					TL.ticket_id AS id_ticket,
					SUM(TL.discount_ttc::NUMERIC) AS discount_amount,
                    SUM(TL.discount_ht) AS discount_ht
                    FROM public.ticket_line TL
					WHERE (TL.is_discount = :true and discount_id != :rounding AND TL.date >= :D1 AND TL.date <= :D2 AND TL.combo = FALSE ) GROUP BY TL.ticket_id
                ) AS LAST_RESULT
                ON LEFT_RESULT.ticket_id = LAST_RESULT.id_ticket
                 LEFT JOIN
                (
                   SELECT
					tl.ticket_id AS id_ticket,
					SUM(tl.totalht) AS VA_HT,
                    SUM(tl.totalttc) AS VA_TTC
                    FROM public.ticket_line tl
					WHERE (tl.division = 1 AND tl.date >= :D1 AND tl.date <= :D2 AND tl.combo = FALSE ) GROUP BY tl.ticket_id
                ) AS VA_RESULT
                ON LEFT_RESULT.ticket_id = VA_RESULT.id_ticket

               ";

       $rounding='5061';
        $stm = $conn->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->bindParam('restaurant', $restaurant_id);
        $stm->bindParam('true', $true);
        $stm->bindParam('rounding', $rounding);
        $stm->execute();
        $data = $stm->fetchAll();
        $result = [
            "data" => $data,
        ];

        return $result;
    }

    public function getVoucherTicket($filter, $restaurant_id)
    {

        $conn = $this->_em->getConnection();

        $D1 = $filter['beginDate']; //  transform to D1-1j
        $D2 = $filter['endDate'];
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $true = true;

        $sql = "SELECT SUM( VOUCHER_RESULT.br_ttc ) AS totalvoucher, SUM(VOUCHER_RESULT.br_ht) AS total_voucher_ht
                        FROM(
			             SELECT tl.ticket_id, sum(amount_br) AS br_ttc, sum(amount_br_ht) AS br_ht
	                              FROM ticket_line tl
	                           JOIN ( SELECT  tl.id AS tl_id,  CASE WHEN br.ticket_id IS NOT NULL THEN tl.totalttc
							    ELSE 0
							    END AS amount_br, CASE WHEN br.ticket_id IS NOT NULL THEN tl.totalht
							    ELSE 0
							    END AS amount_br_ht
				      FROM ticket_line tl  LEFT JOIN ( 
														SELECT ticket_id FROM ticket_payment
																WHERE id_payment = :mealTicket
																) AS br ON br.ticket_id = tl.ticket_id where tl.origin_restaurant_id = :restaurant
				 ) AS bon_repas ON bon_repas.tl_id = tl.id
				 WHERE tl.status <> :canceled AND tl.status <> :abandonment AND tl.counted_canceled <> TRUE  AND tl.date >= :D1 AND tl.date <= :D2 AND tl.origin_restaurant_id = :restaurant 
				 GROUP BY tva, tl.ticket_id                    
               ) AS VOUCHER_RESULT
               ";

        $stm = $conn->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->bindParam('restaurant', $restaurant_id);
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('mealTicket', $mealTicket);
        $stm->execute();
        $data = $stm->fetchAll();
        $result = [
            "data" => $data,
        ];

        return $result;
    }

    public function getDiscountTicket($ticket)
    {

        $conn = $this->_em->getConnection();

        $sql = "SELECT
                COALESCE(SUM(discount_ttc::NUMERIC), 0) AS discount_ttc,
                COALESCE(SUM(discount_ht::NUMERIC), 0) AS discount_ht,
                COALESCE(SUM(totalttc::NUMERIC), 0) AS total_ttc,
                COALESCE(SUM(totalht::NUMERIC), 0) AS total_ht
                FROM public.ticket_line
                WHERE ticket_id = :id;";
        $stm = $conn->prepare($sql);
        $stm->bindParam('id', $ticket);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data[0];
    }

    public function getCaNetTicket($filter)
    {

        $conn = $this->_em->getConnection();

        $D1 = $filter['beginDate']; //  transform to D1-1j
        $D2 = $filter['endDate'];
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $mealTicket = TicketPayment::MEAL_TICKET;

        $sql = "SELECT SUM( LEFT_RESULT.totalht ) AS caBrutHt,
                    SUM( LEFT_RESULT.totalttc ) AS caBrutTtc,
                    SUM( RIGHT_RESULT.voucher_amount ) AS totalVoucher FROM (
                    SELECT
                    t.id AS ticket_id,
                    t.totalht AS totalht,
                    t.totalttc AS totalttc
                    FROM public.ticket t
                    WHERE ( T.status <> :canceled AND T.status <> :abandonment AND T.counted_canceled <> TRUE ) AND t.date >= :D1 AND t.date <= :D2 ) AS LEFT_RESULT
                    LEFT JOIN
                    (
                        SELECT
                        t.id AS id_ticket,
                        SUM(TP.amount) AS voucher_amount

                        FROM public.ticket_payment TP
                        LEFT JOIN  public.ticket t ON t.id = TP.ticket_id

                        WHERE (TP.id_payment = :mealTicket AND t.date >= :D1 AND t.date <= :D2 ) GROUP BY t.id ) AS RIGHT_RESULT

                    ON LEFT_RESULT.ticket_id = RIGHT_RESULT.id_ticket

                   ";
        $stm = $conn->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->bindParam('mealTicket', $mealTicket);
        $stm->execute();
        $data = $stm->fetchAll();
        $result = [
            "data" => $data,
        ];

        return $result;
    }

    public function getCaHtPerDayWeek()
    {
    }

    public function findPlusThatAreNotExistingInProductSoldTable($filter, $currentRestaurant)
    {
        /**
         *
         * @var \DateTime $D1
         * @var \DateTime $D2
         */
        $D1 = $filter["startDate"];
        $D2 = $filter["endDate"];
        $startDate = $D1->format('Y-m-d');
        $endDate = $D2->format('Y-m-d');
        $allPlus = $this->_em->getRepository(ProductSold::class)->retrieveAllPlus($currentRestaurant);
        $excludedPLU = array_unique(array_merge($allPlus, ProductSold::IGNORED_PLUS), SORT_REGULAR);
        $currentRestaurantId=$currentRestaurant->getId();
        $qb = $this->_em->getRepository(TicketLine::class)
            ->createQueryBuilder('ticketLine');
        $qb
            ->leftJoin('ticketLine.ticket', 'ticket')
            ->select('ticketLine.plu', 'MAX(ticketLine.description) as description')
            //->where($qb->expr()->notIn('ticketLine.plu', $allPlus))
            ->where('ticketLine.plu NOT IN (:allPlu)')
            ->andWhere(" ticketLine.plu != '' ")// NOT EMPTY
            //->andWhere($qb->expr()->notIn('ticketLine.plu',ProductSold::IGNORED_PLUS))
            ->andWhere('ticketLine.product<90000 OR ticketLine.product>99999 and ticketLine.product<900000 ')
            ->andWhere('ticket.num >= 0')
            ->andWhere('ticket.date >= :startDate')
            ->andWhere('ticketLine.date >= :startDate')
            ->andWhere('ticket.date <= :endDate')
            ->andWhere('ticketLine.date <= :endDate')
            ->andWhere("ticket.originRestaurant = :restaurant")
            ->andWhere("ticketLine.originRestaurantId = :restaurantId")
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter("restaurant", $currentRestaurant)
            ->setParameter("restaurantId", $currentRestaurantId)
            ->setParameter("allPlu", $excludedPLU)
            ->groupBy('ticketLine.plu');

        $result = $qb->getQuery()->getArrayResult();

        return $result;
    }

    public function getWorkingCashboxesNumberByDate(\DateTime $dateTime)
    {
        return count(
            $this->_em->getRepository('Financial:Ticket')
                ->createQueryBuilder('ticket')
                ->select('ticket.workstation')
                ->where('ticket.date = :date')
                ->setParameter('date', $dateTime)
                ->groupBy('ticket.workstation')
                ->getQuery()->getResult()
        );
    }

    /**************************
     *
     * Supervision section
     ************************/

    public function getSupervisionCaTicketsPerHourAndOrigin($data)
    {

        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0) + ABS(COALESCE(RIGHT_RESULT.totalDiscount, 0)))) AS CA_BRUT_TTC,
                    LEFT_RESULT.origin AS origin, LEFT_RESULT.entryHour AS entryHour,
                    Count(LEFT_RESULT.ticketId) AS countTicket FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC,
                    EXTRACT(HOUR FROM T.enddate) AS entryHour,
                    LOWER(T.origin) AS origin

                    FROM public.ticket T
                    WHERE ( T.status <> :canceled AND T.status <> :abandonment ) AND T.date = :date
                    AND T.origin_restaurant_id = :restaurant ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TL.ticket_id AS ticketId,
                    SUM(TL.discount_ttc) AS totalDiscount
                    FROM ticket_line TL
                    LEFT JOIN ticket T ON T.id = TL.ticket_id
                        WHERE TL.is_discount = TRUE GROUP BY TL.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
                GROUP BY entryHour, origin
                ORDER BY entryHour";

        // bind
        $date = $data['date']->format('Y-m-d');
        $restaurant = $data['restaurant']->getId();
        $stm = $conn->prepare($sql);
        $stm->bindParam('date', $date);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->bindParam('restaurant', $restaurant);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }

    public function getSupervisionTotalPerHourFourPreviousWeek($previousDate, $restaurant)
    {

        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;

        $sql = "SELECT
                    SUM(T.totalttc) AS total,
                    EXTRACT(HOUR FROM T.enddate) AS entryHour

                    FROM public.ticket T
                    WHERE ( T.status <> :canceled AND T.status <> :abandonment AND T.origin_restaurant_id = :restaurantId ) AND
                    (T.date = :date0 OR T.date = :date1 OR T.date = :date2 OR T.date = :date3)
                    GROUP BY entryHour
                    ORDER BY entryHour";
        $stm = $conn->prepare($sql);
        $date0 = $previousDate['0']->format('Y-m-d');
        $date1 = $previousDate['1']->format('Y-m-d');
        $date2 = $previousDate['2']->format('Y-m-d');
        $date3 = $previousDate['3']->format('Y-m-d');
        $stm->bindParam('date0', $date0);
        $stm->bindParam('date1', $date1);
        $stm->bindParam('date2', $date2);
        $stm->bindParam('date3', $date3);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $restaurantId = $restaurant->getId();
        $stm->bindParam('restaurantId', $restaurantId);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }

    public function getSupervisionTotalPerDay($date, $restaurants = null, $onlyNbrTicket = false)
    {
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $queryBuilder = $this->createQueryBuilder('t');

        if ($restaurants and count($restaurants) > 0) {
            $restaurantsIds = array();

            foreach ($restaurants as $restaurant) {
                $restaurantsIds[] = $restaurant->getId();
            }
            $queryBuilder
                ->andWhere('t.originRestaurant IN (:restaurants)')
                ->setParameter('restaurants', $restaurantsIds);
        }

        $queryBuilder->andWhere('t.date = :date')
            ->setParameter('date', $date);

        $queryBuilder->andWhere('t.status <> :canceled and t.status <> :abandonment')
            ->setParameter('canceled', $canceled)
            ->setParameter('abandonment', $abandonment);

        if ($onlyNbrTicket) {
            $queryBuilder->select('count(t) AS nbrTicket');
        } else {
            $queryBuilder->select('SUM(t.totalTTC) AS total');
        }
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }

    public function getCaTicketPerTaxeAndSoldingCanal($filter)
    {

        $conn = $this->_em->getConnection();

        $D1 = $filter['startDate'];
        $D2 = $filter['endDate'];
        $restaurant_code = $filter['restaurant'];
        $restaurant = $this->_em->getRepository(Restaurant::class)->findOneByCode($restaurant_code);
        $restaurant_id=$restaurant->getId();

        $sql ="SELECT Restaurant, commercial_date, canal_vente, taxe, CA_BRUT_TTC ,((CA_BRUT_TTC/(1+taxe)) * taxe) AS CA_BRUT_TVA,(CA_BRUT_TTC/(1+taxe)) AS CA_BRUT_HT,
Disc_BPub_TTC,Disc_BPub_TVA,Disc_BPub_HT,
Disc_BRep_TTC,(Disc_BRep_TTC - Disc_BRep_HT) AS Disc_BRep_TVA,Disc_BRep_HT,
CA_NET_TTC,((CA_NET_TTC/(1+taxe)) * taxe) AS CA_NET_TVA ,(CA_NET_TTC/(1+taxe)) AS CA_NET_HT 
FROM ( 
SELECT  Restaurant, commercial_date, canal_vente, taxe, 
SUM(totalttc) AS CA_BRUT_TTC, 
(SUM(totalttc) + SUM(discount_ttc) - SUM(amount_br) ) AS CA_NET_TTC, 
(ABS(SUM(discount_ttc))) AS Disc_BPub_TTC,
(ABS(SUM(discount_tva))) AS Disc_BPub_TVA, 
(ABS(SUM(discount_ht))) AS Disc_BPub_HT,  
SUM(amount_br) AS Disc_BRep_TTC,
(COALESCE(SUM(amount_br), 0 )/(1+taxe)) AS Disc_BRep_HT

                FROM (
	                    SELECT 
	                    R.code AS Restaurant , 
	                    T.date AS commercial_date, 
	                    (CASE 
	                    WHEN ( (T.origin = 'POS' AND T.destination = 'EatIn') OR T.destination = 'TAKE IN' OR (T.origin = '' AND T.destination = '') OR (T.origin IS NULL AND T.destination IS NULL) ) THEN 'EatIn'
                        WHEN ( (T.origin = 'DriveThru' AND T.destination = 'DriveThru') OR T.destination = 'DRIVE' OR (T.origin = 'MyQuick' AND T.destination = 'MQDrive') OR (T.origin = 'MyQuick' AND T.destination = 'MQCurbside') )  THEN 'DriveThru'
                        WHEN ( (T.origin = 'POS' AND T.destination = 'TakeOut') OR T.destination = 'TAKE OUT') THEN 'TakeOut'
	                    WHEN (T.origin = 'KIOSK' AND T.destination = 'EatIn') THEN 'KioskIn'
	                    WHEN (T.origin = 'KIOSK' AND T.destination = 'TakeOut') THEN 'KioskOut'
	                    WHEN ((T.origin = 'POS' AND T.destination = 'Delivery') OR (T.origin = 'MyQuick' AND T.destination = 'ATOUberEats') OR (T.origin = 'MyQuick' AND T.destination = 'ATODeliveroo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOTakeAway')
                          OR (T.origin = 'MyQuick' AND T.destination = 'ATOHelloUgo') OR (T.origin = 'MyQuick' AND T.destination = 'ATOEasy2Eat') OR (T.origin = 'MyQuick' AND T.destination = 'ATOGoosty') OR (T.origin = 'MyQuick' AND T.destination = 'ATOWolt')) THEN 'Delivery'
                        ELSE 'EatIn'
                        END) AS canal_vente, 
	                    TL.tva AS taxe, 
	                    TL.totalttc AS totalttc,
	                    TL.discount_ttc AS discount_ttc,
	                    TL.discount_tva AS discount_tva,
	                    TL.discount_ht AS discount_ht,
	                    CASE WHEN TP.amount IS NOT NULL THEN TL.totalttc ELSE 0 END AS amount_br

	                    FROM ticket T JOIN ticket_line TL ON T.id = TL.ticket_id AND T.origin_restaurant_id= :restaurant_id AND TL.origin_restaurant_id= :restaurant_id JOIN restaurant R ON R.id = T.origin_restaurant_id LEFT JOIN ticket_payment TP ON T.id = TP.ticket_id AND TP.id_payment= :bon_repas_id
	                       
	                    WHERE T.date >= :D1 AND T.date <= :D2 AND R.code = :restaurant_code AND TL.combo = FALSE AND TL.date >= :D1 AND TL.date <= :D2 
	                    )  AS SUB_RESULT
	                   GROUP BY canal_vente, taxe, Restaurant, commercial_date  
) AS RESULT ORDER BY Restaurant,commercial_date, canal_vente,taxe;";


        $stm = $conn->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('bon_repas_id', $mealTicket);
        $stm->bindParam('restaurant_id', $restaurant_id);
        $stm->bindParam('restaurant_code', $restaurant_code);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }

    /**
     * @param $canal
     * @param $restaurantId
     * @param null $startDate
     * @param null $endDate
     * @param null $starHour
     * @param null $endHour
     * @return bool|string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTicketCountPerCanal(
        $canal,
        $restaurantId,
        $startDate = null,
        $endDate = null,
        $starHour = null,
        $endHour = null
    ) {
        $conn = $this->_em->getConnection();
        $sql = "SELECT COUNT(*) FROM public.ticket WHERE origin_restaurant_id= :restaurantId AND ticket.status != :canceled AND ticket.status != :abondon ";
        if ($startDate) {
            $sql .= " AND ticket.date >= :startDate ";
        }
        if ($endDate) {
            $sql .= " AND ticket.date <= :endDate ";
        }
        if ($starHour) {
            $sql .= " AND date_part('HOUR',ticket.enddate) >= CAST( :startHour as INTEGER) ";
        }
        if ($endHour) {
            $sql .= " AND date_part('HOUR',ticket.enddate) <= CAST( :endHour as INTEGER) ";
        }



        switch (strtolower($canal)) {

            case 'drive' :
                $sql .= " AND ( (ticket.origin = 'DriveThru' AND ticket.destination = 'DriveThru') OR ticket.destination = 'DRIVE' OR (ticket.origin = 'MyQuick' AND ticket.destination = 'MQDrive') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'MQCurbside') ) ";
                break;
            case 'eatin' :
                $sql .= " AND ( (ticket.origin = 'POS' AND ticket.destination = 'EatIn') OR ticket.destination = 'TAKE IN' OR (ticket.origin = '' AND ticket.destination = '') OR (ticket.origin IS NULL AND ticket.destination IS NULL) ) ";
                break;
            case 'takeout':
                $sql .= " AND ((ticket.origin = 'POS' AND ticket.destination = 'TakeOut') OR ticket.destination = 'TAKE OUT') ";
                break;
            case 'kioskin':
                $sql .= " AND ticket.origin = 'KIOSK' AND ticket.destination = 'EatIn' ";
                break;
            case 'kioskout':
                $sql .= " AND ticket.origin = 'KIOSK' AND ticket.destination = 'TakeOut' ";
                break;
            case 'delivery' :
                $sql .= " AND (  (ticket.origin = 'POS' AND ticket.destination = 'Delivery') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'ATOUberEats') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'ATODeliveroo') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'ATOTakeAway')  OR (ticket.origin = 'MyQuick' AND ticket.destination = 'ATOHelloUgo') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'ATOEasy2Eat') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'ATOGoosty') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'ATOWolt')) ";
                break;
            case 'e_ordering_in' :
                $sql .= " AND (  ticket.origin = 'MyQuick' AND ticket.destination = 'MyQuickEatIn'  ) ";
                break;
            case 'e_ordering_out' :
                $sql .= " AND (  ticket.origin = 'MyQuick' AND ticket.destination = 'MyQuickTakeout'  ) ";
                break;

        }

        $stm = $conn->prepare($sql);


        $stm->bindParam('restaurantId', $restaurantId);

        $canceled = Ticket::CANCEL_STATUS_VALUE;

        $stm->bindParam('canceled', $canceled);

        $abondon = Ticket::ABONDON_STATUS_VALUE;
        $stm->bindParam('abondon', $abondon);
        if ($startDate) {
            $stm->bindParam('startDate', $startDate);
        }
        if ($endDate) {
            $stm->bindParam('endDate', $endDate);
        }
        if ($starHour) {
            $stm->bindParam('startHour', $starHour);
        }
        if ($endHour) {
            $stm->bindParam('endHour', $endHour);
        }

        $stm->execute();
        $allTicket = $stm->fetchColumn(0);

        $sqlCancelled = $sql." AND ticket.invoiceCancelled = '1' ;";
        $stm = $conn->prepare($sqlCancelled);

        $stm->bindParam('restaurantId', $restaurantId);

        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $stm->bindParam('canceled', $canceled);
        $abondon = Ticket::ABONDON_STATUS_VALUE;
        $stm->bindParam('abondon', $abondon);
        if ($startDate) {
            $stm->bindParam('startDate', $startDate);
        }
        if ($endDate) {
            $stm->bindParam('endDate', $endDate);
        }
        if ($starHour) {
            $stm->bindParam('startHour', $starHour);
        }
        if ($endHour) {
            $stm->bindParam('endHour', $endHour);
        }
        $stm->execute();
        $cancelledTicket = $stm->fetchColumn(0);

        return $allTicket - (2 * $cancelledTicket);
    }

    public function getCaBrutTicketsPerHour($criteria, Restaurant $currentRestaurant)
    {
        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0) + ABS(COALESCE(RIGHT_RESULT.totalDiscount, 0)))) AS CA,
                 LEFT_RESULT.entryHour AS entryHour
                 FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC,
                    EXTRACT(HOUR FROM T.enddate) AS entryHour
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id 
                    AND ( T.status <> :canceled AND T.status <> :abandonment AND T.counted_canceled <> TRUE )
                    AND T.date >= :from AND T.date <= :to
                     ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TL.ticket_id AS ticketId,
                    SUM(TL.discount_ttc) AS totalDiscount
                    FROM ticket_line TL
                        WHERE TL.origin_restaurant_id = :origin_restaurant_id AND TL.is_discount = TRUE 
                        AND TL.date >= :from AND TL.date <= :to
                        GROUP BY TL.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
                GROUP BY entryHour
                ORDER BY entryHour";

        // bind
        $from = $criteria['from']." 00:00:00";
        $to = $criteria['to']." 23:59:59";
        $stm = $conn->prepare($sql);
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $currentRestaurantId = $currentRestaurant->getId();
        $stm->bindParam("origin_restaurant_id", $currentRestaurantId);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }


    public function getHistoricCaBrutTicketsPerHour($criteria, Restaurant $currentRestaurant)
    {
        $conn = $this->_em->getConnection();
        $sql = "SELECT brut_ttc AS CA,
                 EXTRACT(HOUR FROM date_hour ) AS entryHour
                 FROM financial_revenue_by_hour_hist 
                  where origin_restaurant_id = :origin_restaurant_id
                  AND date_day  >= :from AND date_day  <= :to
               ";

        // bind
        $from = $criteria['from']." 00:00:00";
        $to = $criteria['to']." 23:59:59";
        $stm = $conn->prepare($sql);
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $currentRestaurantId = $currentRestaurant->getId();
        $stm->bindParam("origin_restaurant_id", $currentRestaurantId);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }



    public function getCaBrutTicketsPerHalfOrQuarterHour($criteria, Restaurant $currentRestaurant, $schedule)
    {
        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;

        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0) + ABS(COALESCE(RIGHT_RESULT.totalDiscount, 0)))) AS CA,
                 LEFT_RESULT.schedule, LEFT_RESULT.hour AS entryhour FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC,
                    CASE ";
        if ($schedule == 1) {
            $sql .= "WHEN extract(minute from enddate) >= 30 THEN 1 ";
        } else {
            $sql .= "WHEN extract(minute from enddate) >= 15 and extract(minute from enddate) < 30 THEN 1
		              WHEN extract(minute from enddate) >= 30 and extract(minute from enddate) < 45 THEN 2
		              WHEN extract(minute from enddate) >= 45 THEN 3 ";
        }

        $sql .= "
                    ELSE
                     0
                    END as schedule,
                    extract(hour from enddate) as hour
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id and ( T.status <> :canceled and T.status <> :abandonment and T.counted_canceled <> TRUE )
                    and T.date >= :from and T.date <= :to
                     ) as  LEFT_RESULT

                left join (
                    SELECT
                    TL.ticket_id as ticketId,
                    SUM(TL.discount_ttc) as totalDiscount
                    from ticket_line TL
                        where TL.origin_restaurant_id = :origin_restaurant_id and TL.is_discount = true  and TL.date >= :from and TL.date <= :to 
                        GROUP BY TL.ticket_id
                ) as RIGHT_RESULT
                on LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
                GROUP BY schedule, entryhour
                ORDER BY entryhour, schedule";

        // bind

        $from = $criteria['from']." 00:00:00";
        $to = $criteria['to']." 23:59:59";
        $stm = $conn->prepare($sql);
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $currentRestaurantId = $currentRestaurant->getId();
        $stm->bindParam("origin_restaurant_id", $currentRestaurantId);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }

    public function getCaHTvaPerSliceHour($criteria, Restaurant $restaurant)
    {
        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;

        $sql = "
                SELECT SUM((COALESCE(ca_ht, 0) - COALESCE(br_ht, 0))) AS CA, entryhour 
                 FROM (
                        SELECT
                        T.id AS ticketId,
                        T.totalht AS CA_HT,
                        EXTRACT(HOUR FROM T.enddate) AS entryHour
                        FROM public.ticket T
                        WHERE T.origin_restaurant_id = :restaurant_id AND ( T.status <> :canceled 
                        AND T.status <> :abandonment AND T.counted_canceled <> TRUE )
                         AND T.date >= :from AND T.date <= :to
                     ) AS  LEFT_RESULT
                LEFT JOIN (

			SELECT SS1.id AS ticket_id, SUM(total_payment * percent_tva * percent_payment * ((100 - tva) / 100)) AS br_ht
			FROM(
				SELECT S2.id, total_amount AS total_payment
				    , CASE WHEN S2.total_amount = 0 
				        THEN 100 
				        ELSE (S1.voucher_amount / S2.total_amount) END AS percent_payment
				FROM(   
					SELECT t.id AS id_ticket, SUM(TP.amount) AS voucher_amount 
					FROM public.ticket_payment TP
					LEFT JOIN  public.ticket t ON t.id = TP.ticket_id
					WHERE (TP.id_payment = :mealTicket AND t.date >= :from AND t.date <= :to) GROUP BY t.id
				) AS S1
				LEFT JOIN(
					SELECT t.id, SUM(tp.amount) AS total_amount
					FROM ticket_payment tp JOIN ticket t ON t.id = tp.ticket_id
					WHERE t.date >= :from AND t.date <= :to
					GROUP BY t.id
				) AS S2
				ON S2.id = S1.id_ticket
			) AS SS1
			LEFT JOIN(
				SELECT S3.id, S3.tva AS tva, CASE WHEN S4.totalttc = 0 THEN 100 ELSE (S3.totalttc_tva / S4.totalttc) END AS percent_tva
				FROM(
					SELECT tl.ticket_id AS id, tl.tva, SUM(tl.totalttc) AS totalttc_tva
					FROM ticket_line tl
					WHERE tl.date >= :from AND tl.date <= :to AND tl.origin_restaurant_id = :restaurant_id
					GROUP BY tl.ticket_id, tl.tva
					ORDER BY tl.ticket_id
				) AS S3
				LEFT JOIN(
					SELECT tl.ticket_id AS id, SUM(tl.totalttc) AS totalttc
					FROM ticket_line tl
					WHERE tl.date >= :from AND tl.date <= :to AND tl.origin_restaurant_id = :restaurant_id
					GROUP BY tl.ticket_id
					ORDER BY tl.ticket_id
				)AS S4
				ON S3.id = S4.id
			) AS SS2
			ON SS1.id = SS2.id
			GROUP BY SS1.id

                ) AS BR_HT_RESULT 
		ON BR_HT_RESULT.ticket_id = LEFT_RESULT.TicketId
		GROUP BY entryhour
		ORDER BY entryhour";

        // bind
        $stm = $conn->prepare($sql);
        $from = $criteria['from']." 00:00:00";
        $to = $criteria['to']." 23:59:59";
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('mealTicket', $mealTicket);
        $restaurant_id = $restaurant->getId();
        $stm->bindParam('restaurant_id', $restaurant_id);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }



    public function getHistoricCaHTvaPerSliceHour($criteria, Restaurant $restaurant)
    {
        $conn = $this->_em->getConnection();

        $sql = "SELECT brut_ht AS CA,
                 EXTRACT(HOUR FROM date_hour ) AS entryHour
                 FROM financial_revenue_by_hour_hist 
                  where origin_restaurant_id = :restaurant_id
                  AND date_day  >= :from AND date_day  <= :to
               ";

        // bind
        $stm = $conn->prepare($sql);
        $from = $criteria['from']." 00:00:00";
        $to = $criteria['to']." 23:59:59";
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $restaurant_id = $restaurant->getId();
        $stm->bindParam('restaurant_id', $restaurant_id);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }




    public function getCaHTvaPerHalfOrQuarterSliceHour($criteria, Restaurant $restaurant, $schedule)
    {
        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;

        $sql = "
                SELECT SUM((COALESCE(ca_ht, 0) - COALESCE(br_ht, 0))) AS CA, entryhour, schedule
                 FROM (
                        SELECT
                        T.id AS ticketId,
                        T.totalht AS CA_HT,
                        EXTRACT(HOUR FROM T.enddate) AS entryHour,
                        CASE ";

        if ($schedule == 1) {
            $sql .= "WHEN extract(minute from enddate) >= 30 THEN 1 ";
        } else {
            $sql .= "WHEN extract(minute from enddate) >= 15 and extract(minute from enddate) < 30 THEN 1
		              WHEN extract(minute from enddate) >= 30 and extract(minute from enddate) < 45 THEN 2
		              WHEN extract(minute from enddate) >= 45 THEN 3 ";
        }

        $sql .= "
                    ELSE
                     0
                    END as schedule
                        FROM public.ticket T
                        WHERE T.origin_restaurant_id = :restaurant_id and ( T.status <> :canceled and T.status <> :abandonment and T.counted_canceled <> TRUE ) and
                         T.date >= :from and T.date <= :to
                     ) as  LEFT_RESULT
                left join (

			SELECT SS1.id as ticket_id, SUM(total_payment * percent_tva * percent_payment * ((100 - tva) / 100)) as br_ht
			FROM(
				SELECT S2.id, total_amount as total_payment, Case when S2.total_amount = 0 then 100 Else (S1.voucher_amount / S2.total_amount) End as percent_payment
				FROM(   
					SELECT t.id as id_ticket, SUM(TP.amount) as voucher_amount 
					From public.ticket_payment TP
					LEFT JOIN  public.ticket t on t.id = TP.ticket_id
					WHERE (TP.id_payment = :mealTicket and t.date >= :from and t.date <= :to) GROUP BY t.id
				) as S1
				LEFT JOIN(
					SELECT t.id, SUM(tp.amount) as total_amount
					FROM ticket_payment tp JOIN ticket t on t.id = tp.ticket_id
					WHERE t.date >= :from and t.date <= :to
					GROUP BY t.id
				) as S2
				ON S2.id = S1.id_ticket
			) as SS1
			LEFT JOIN(
				SELECT S3.id, S3.tva as tva, Case when S4.totalttc = 0 then 100 Else (S3.totalttc_tva / S4.totalttc) End as percent_tva
				FROM(
					SELECT tl.ticket_id as id, tl.tva, SUM(tl.totalttc) as totalttc_tva
					FROM ticket_line tl 
					WHERE tl.date >= :from and tl.date <= :to AND tl.origin_restaurant_id = :restaurant_id
					GROUP BY tl.ticket_id, tl.tva
					ORDER BY tl.ticket_id
				) as S3
				LEFT JOIN(
					SELECT tl.ticket_id as id, SUM(tl.totalttc) as totalttc
					FROM ticket_line tl 
					WHERE tl.date >= :from and tl.date <= :to AND tl.origin_restaurant_id = :restaurant_id
					GROUP BY tl.ticket_id
					ORDER BY tl.ticket_id
				)as S4
				ON S3.id = S4.id
			) as SS2
			ON SS1.id = SS2.id
			GROUP BY SS1.id

                ) as BR_HT_RESULT 
		on BR_HT_RESULT.ticket_id = LEFT_RESULT.TicketId
		group by entryhour, schedule
		order by entryhour, schedule";

        // bind
        $stm = $conn->prepare($sql);
        $from = $criteria['from']." 00:00:00";
        $to = $criteria['to']." 23:59:59";
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('mealTicket', $mealTicket);
        $restaurant_id = $restaurant->getId();
        $stm->bindParam('restaurant_id', $restaurant_id);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }


    public function getNHistoricVoucherTicket($filter, $restaurant_id)
    {

        $conn = $this->_em->getConnection();

        $D1 = $filter['beginDate']; //  transform to D1-1j
        $D2 = $filter['endDate'];
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;

        $sql = "
                SELECT SUM(COALESCE(br_ht, 0)) AS total_voucher_ht
                 FROM (
                        SELECT
                        T.id AS ticketId
                        FROM public.ticket T
                        WHERE T.origin_restaurant_id = :restaurant_id AND ( T.status <> :canceled 
                        AND T.status <> :abandonment AND T.counted_canceled <> TRUE )
                         AND T.date >= :from AND T.date <= :to
                     ) AS  LEFT_RESULT
                LEFT JOIN (

			SELECT SS1.id AS ticket_id, SUM(total_payment * percent_tva * percent_payment * ((100 - tva) / 100) ) AS br_ht
			FROM(
				SELECT S2.id, total_amount AS total_payment
				    , CASE WHEN S2.total_amount = 0 
				        THEN 100 
				        ELSE (S1.voucher_amount / S2.total_amount) END AS percent_payment
				FROM(   
					SELECT t.id AS id_ticket, SUM(TP.amount) AS voucher_amount 
					FROM public.ticket_payment TP
					LEFT JOIN  public.ticket t ON t.id = TP.ticket_id
					WHERE (TP.id_payment = :mealTicket AND t.date >= :from AND t.date <= :to) GROUP BY t.id
				) AS S1
				LEFT JOIN(
					SELECT t.id, SUM(tp.amount) AS total_amount
					FROM ticket_payment tp JOIN ticket t ON t.id = tp.ticket_id
					WHERE t.date >= :from AND t.date <= :to
					GROUP BY t.id
				) AS S2
				ON S2.id = S1.id_ticket
			) AS SS1
			LEFT JOIN(
				SELECT S3.id, S3.tva AS tva, CASE WHEN S4.totalttc = 0 THEN 100 ELSE (S3.totalttc_tva / S4.totalttc) END AS percent_tva
				FROM(
					SELECT t.id, tl.tva, SUM(tl.totalttc) AS totalttc_tva
					FROM ticket_line tl JOIN ticket t ON t.id = tl.ticket_id
					WHERE t.origin_restaurant_id = :restaurant_id AND t.date >= :from AND t.date <= :to AND tl.origin_restaurant_id = :restaurant_id AND tl.date >= :from and tl.date <= :to
					GROUP BY t.id, tl.tva
					ORDER BY t.id
				) AS S3
				LEFT JOIN(
					SELECT t.id, SUM(tl.totalttc) AS totalttc
					FROM ticket_line tl JOIN ticket t ON t.id = tl.ticket_id
					WHERE  t.origin_restaurant_id = :restaurant_id AND t.date >= :from AND t.date <= :to AND tl.origin_restaurant_id = :restaurant_id AND tl.date >= :from and tl.date <= :to
					GROUP BY t.id
					ORDER BY t.id
				)AS S4
				ON S3.id = S4.id
			) AS SS2
			ON SS1.id = SS2.id
			GROUP BY SS1.id

                ) AS BR_HT_RESULT 
		ON BR_HT_RESULT.ticket_id = LEFT_RESULT.TicketId";

        $stm = $conn->prepare($sql);
        $stm->bindParam('from', $D1);
        $stm->bindParam('to', $D2);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->bindParam('restaurant_id', $restaurant_id);
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('mealTicket', $mealTicket);
        $stm->execute();
        $data = $stm->fetchAll();
        $result = array(
            "data" => $data,
        );

        return $result;
    }


    public function getCaTicketPerTva($filter)
    {

        $conn = $this->_em->getConnection();

        
        $D1 = $filter['startDate'];
        $D2 = $filter['endDate'];
        $restaurant_id = $filter['restaurant'];
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;


        $sql = 'SELECT  "canal_vente", "tva" AS "taxe", SUM(totalttc) AS "CA_BRUT_TTC",(SUM(totalttc) + SUM(discount_ttc) - SUM(amount_br)) AS "CA_NET_TTC", ABS(SUM(discount_ttc)) AS "Disc_TTC", SUM(amount_br) AS "br"   
                   FROM (        
                      SELECT 
	                    CASE 
	                    WHEN ( (t.origin = \'POS\' AND t.destination = \'EatIn\') OR t.destination = \'TAKE IN\' OR (t.origin = \'\' AND t.destination = \'\') OR (t.origin IS NULL AND t.destination IS NULL) ) THEN \'EatIn\'
                        WHEN ( (t.origin = \'DriveThru\' AND t.destination = \'DriveThru\') OR (t.destination = \'DRIVE\') OR (t.origin = \'MyQuick\' AND t.destination = \'MQDrive\') OR (t.origin = \'MyQuick\' AND t.destination = \'MQCurbside\'))  THEN \'DriveThru\'
                        WHEN ( (t.origin = \'POS\' AND t.destination = \'TakeOut\') OR t.destination = \'TAKE OUT\') THEN \'TakeOut\'
	                    WHEN (t.origin = \'KIOSK\' AND t.destination = \'EatIn\') THEN \'KioskIn\'
	                    WHEN (t.origin = \'KIOSK\' AND t.destination = \'TakeOut\') THEN \'KioskOut\'
	                    WHEN ((t.origin = \'POS\' AND t.destination = \'Delivery\') OR (t.origin = \'MyQuick\' AND t.destination = \'ATOUberEats\') OR (t.origin = \'MyQuick\' AND t.destination = \'ATODeliveroo\') OR (t.origin = \'MyQuick\' AND t.destination = \'ATOTakeAway\') 
	                          OR (T.origin = \'MyQuick\' AND T.destination = \'ATOHelloUgo\') OR (T.origin = \'MyQuick\'  AND T.destination = \'ATOEasy2Eat\') OR (T.origin = \'MyQuick\' AND T.destination = \'ATOGoosty\') OR (T.origin = \'MyQuick\' AND T.destination = \'ATOWolt\')) THEN \'Delivery\'
	                     WHEN (t.origin = \'MyQuick\' AND t.destination = \'MyQuickEatIn\') THEN \'e_ordering_in\'
	                     WHEN (t.origin = \'MyQuick\' AND t.destination = \'MyQuickTakeout\') THEN \'e_ordering_out\'
                        ELSE \'EatIn\'
                        END
	                    AS "canal_vente",
	                    TL.tva AS "tva", 
	                    TL.totalttc AS "totalttc",
	                    TL.discount_ttc AS "discount_ttc",
	                    CASE WHEN TP.amount IS NOT NULL THEN TL.totalttc ELSE 0 END AS "amount_br"
	                   
	                    FROM ticket_line TL JOIN ticket T ON T.id = TL.ticket_id LEFT JOIN ticket_payment TP ON T.id = TP.ticket_id AND TP.id_payment= :bon_repas_id
	                   
	                    WHERE T.date >= :D1 AND T.date <= :D2 AND T.origin_restaurant_id = :restaurant_id  AND  T.status <> :canceled
                        AND T.status <> :abandonment AND T.counted_canceled <> TRUE AND TL.date >= :D1 AND TL.date <= :D2 AND TL.origin_restaurant_id = :restaurant_id AND TL.combo = FALSE) AS RESULT
                   GROUP BY  canal_vente, tva ';


        $stm = $conn->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('bon_repas_id', $mealTicket);
        $stm->bindParam('restaurant_id', $restaurant_id);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }

//    public function getTotalPerPeriod($filter)
//    {
//        $canceled = Ticket::CANCEL_STATUS_VALUE;
//        $abandonment = Ticket::ABONDON_STATUS_VALUE;
//        $restaurant = $filter['restaurant'];
//        $d1 = $filter['startDate'];
//        $d2 = $filter['endDate'];
//        $queryBuilder = $this->createQueryBuilder('t');
//
//        $queryBuilder->andWhere('t.date >= :D1')
//            ->andWhere('t.date <= :D2')
//            ->setParameter('D1', $d1)
//            ->setParameter('D2', $d2);
//
//        $queryBuilder->andWhere('t.status <> :canceled and t.status <> :abandonment  and t.countedCanceled <> TRUE')
//            ->setParameter('canceled', $canceled)
//            ->setParameter('abandonment', $abandonment);
//
//        $queryBuilder->andWhere('t.originRestaurant = :restaurant')
//            ->setParameter('restaurant', $restaurant);
//        $queryBuilder->select('count(t) AS nbrTicket');
//        $nbtickets = $queryBuilder->getQuery()->getSingleScalarResult();
//        $queryBuilder = $this->createQueryBuilder('t');
//        $queryBuilder->andWhere('t.date >= :D1')
//            ->andWhere('t.date <= :D2')
//            ->setParameter('D1', $d1)
//            ->setParameter('D2', $d2)
//            ->andWhere('t.invoiceCancelled = :true')
//            ->setParameter('true', '1');
//        if ($restaurant != null) {
//            $queryBuilder->andWhere("t.originRestaurant = :restaurant")
//                ->setParameter("restaurant", $restaurant);
//        }
//
//        $queryBuilder->select('count(t) AS nbrCancels');
//        $cancelledTickets = $queryBuilder->getQuery()->getSingleScalarResult();
//
//        return $nbtickets - (2 * $cancelledTickets);
//
//    }

        public function getTotalPerPeriod($filter){
        $res =array();
            $conn = $this->_em->getConnection();
            $canceled = Ticket::CANCEL_STATUS_VALUE;
            $abandonment = Ticket::ABONDON_STATUS_VALUE;
            $restaurant = $filter['restaurant'];
            $d1 = $filter['startDate'];
            $d2 = $filter['endDate'];
            $sql="select count(*),(
	                    CASE 
	                    WHEN ( (t.origin = 'POS' AND t.destination = 'EatIn') OR t.destination = 'TAKE IN' OR (t.origin = '' AND t.destination = '') OR (t.origin IS NULL AND t.destination IS NULL) ) THEN 'EatIn'
                        WHEN ( (t.origin = 'DriveThru' AND t.destination = 'DriveThru') OR t.destination = 'DRIVE' OR  (t.origin = 'MyQuick' AND t.destination = 'MQDrive') OR (t.origin = 'MyQuick' AND t.destination = 'MQCurbside'))  THEN 'DriveThru'
                        WHEN ( (t.origin = 'POS' AND t.destination = 'TakeOut') OR t.destination = 'TAKE OUT') THEN 'TakeOut'
	                    WHEN (t.origin = 'KIOSK' AND t.destination = 'EatIn') THEN 'KioskIn'
	                    WHEN (t.origin = 'KIOSK' AND t.destination = 'TakeOut') THEN 'KioskOut'
	                    WHEN ((t.origin = 'POS' AND t.destination = 'Delivery') OR  (t.origin = 'MyQuick' AND t.destination = 'ATOUberEats') OR (t.origin = 'MyQuick' AND t.destination = 'ATODeliveroo') OR (t.origin = 'MyQuick' AND t.destination = 'ATOTakeAway')
	                        OR (t.origin = 'MyQuick' AND t.destination = 'ATOHelloUgo') OR (t.origin = 'MyQuick'  AND t.destination = 'ATOEasy2Eat') OR (t.origin = 'MyQuick' AND t.destination = 'ATOGoosty') OR (t.origin = 'MyQuick' AND t.destination = 'ATOWolt')) THEN 'Delivery'
	                    WHEN (t.origin = 'MyQuick' AND t.destination = 'MyQuickEatIn') THEN 'e_ordering_in'
	                    WHEN (t.origin = 'MyQuick' AND t.destination = 'MyQuickTakeout') THEN 'e_ordering_out'
                        ELSE 'EatIn'
                        END
	                    ) AS canal_vente from ticket t where t.status <> :canceled and t.status <> :abandonment  and t.counted_canceled <> TRUE and t.date >= :D1 AND t.date <= :D2 AND t.origin_restaurant_id = :restaurant_id group by canal_vente";
            $stm = $conn->prepare($sql);
            $stm->bindParam('D1', $d1);
            $stm->bindParam('D2', $d2);
            $stm->bindParam('restaurant_id', $restaurant);
            $stm->bindParam('canceled', $canceled);
            $stm->bindParam('abandonment', $abandonment);
            $stm->execute();
            $res1=$stm->fetchAll();
            $sql2="select count(*),(
	                    CASE 
	                    WHEN ( (t.origin = 'POS' AND t.destination = 'EatIn') OR t.destination = 'TAKE IN' OR (t.origin = '' AND t.destination = '') OR (t.origin IS NULL AND t.destination IS NULL) ) THEN 'EatIn'
                        WHEN ( (t.origin = 'DriveThru' AND t.destination = 'DriveThru') OR t.destination = 'DRIVE' OR  (t.origin = 'MyQuick' AND t.destination = 'MQDrive') OR (t.origin = 'MyQuick' AND t.destination = 'MQCurbside') )  THEN 'DriveThru'
                        WHEN ( (t.origin = 'POS' AND t.destination = 'TakeOut') OR t.destination = 'TAKE OUT') THEN 'TakeOut'
	                    WHEN (t.origin = 'KIOSK' AND t.destination = 'EatIn') THEN 'KioskIn'
	                    WHEN (t.origin = 'KIOSK' AND t.destination = 'TakeOut') THEN 'KioskOut'
	                    WHEN (t.origin = 'POS' AND t.destination = 'Delivery') THEN 'Delivery'
	                    WHEN ((t.origin = 'MyQuick' AND t.destination = 'MyQuickEatIn') OR  (t.origin = 'MyQuick' AND t.destination = 'ATOUberEats') OR (t.origin = 'MyQuick' AND t.destination = 'ATODeliveroo') OR (t.origin = 'MyQuick' AND t.destination = 'ATOTakeAway') OR (t.origin = 'MyQuick' AND t.destination = 'ATOHelloUgo') OR (t.origin = 'MyQuick'  AND t.destination = 'ATOEasy2Eat') OR (t.origin = 'MyQuick' AND t.destination = 'ATOGoosty') OR (t.origin = 'MyQuick' AND t.destination = 'ATOWolt') ) THEN 'e_ordering_in'
	                    WHEN (t.origin = 'MyQuick' AND t.destination = 'MyQuickTakeout') THEN 'e_ordering_out'
                        ELSE 'EatIn'
                        END
	                    ) AS canal_vente from ticket t where t.invoicecancelled ='1' and t.date >= :D1 AND t.date <= :D2 AND t.origin_restaurant_id = :restaurant_id group by canal_vente";
            $stm2 = $conn->prepare($sql2);
            $stm2->bindParam('D1', $d1);
            $stm2->bindParam('D2', $d2);
            $stm2->bindParam('restaurant_id', $restaurant);
            $stm2->execute();
            $res2= $stm2->fetchAll();
            foreach ($res1 as $key => $value){
                $type=$value['canal_vente'];
                if(isset($res2[$key])){
                    $res[$type]= $value['count'] - 2* $res2[$key]['count'];
                }else{
                    $res[$type]=intval($value['count']);
                }

            }
           return $res;
             }


    public function getDiscountKiosk($filter)
    {
        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $kiosk = 'KIOSK';
        $restaurant = $filter['restaurant'];
        $d1 = $filter['startDate'];
        $d2 = $filter['endDate'];
        $sql = " SELECT
                    ABS(SUM(TL.discount_ttc)) AS totalDiscount
                    FROM ticket_line TL
                    LEFT JOIN ticket T ON T.id = TL.ticket_id
                        WHERE T.origin_restaurant_id = :restaurant_id AND TL.is_discount = TRUE AND TL.date >= :D1 AND TL.date <= :D2 AND TL.origin_restaurant_id = :restaurant_id
                        AND T.origin= :origin AND T.status <> :canceled 
                        AND T.status <> :abandonment AND T.counted_canceled <> TRUE 
                        AND T.date >= :D1 AND T.date <= :D2";

        $stm = $conn->prepare($sql);
        $stm->bindParam('D1', $d1);
        $stm->bindParam('D2', $d2);
        $stm->bindParam('restaurant_id', $restaurant);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->bindParam('origin', $kiosk);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }

    /**
     * @param $criteria
     * @param Restaurant $currentRestaurant
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */

    public function getCaPerEmployeePerHour($criteria, Restaurant $currentRestaurant)
    {
        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $mealTicket = TicketPayment::MEAL_TICKET;

        $sql = "SELECT 
       Q1.employee_id                                       AS employee_id,
       Q1.employee                                          AS employee,
       Q1.entryHour                                         AS hour,
       COALESCE(COUNT(Q1.ticketID), 0) - (2 * COALESCE(SUM(Q1.cancelled), 0))                   AS ticket_count,
       COALESCE(SUM(Q1.cancelled), 0)                       AS cancelled,
       COALESCE(SUM(Q2.totalDiscountTTC), 0)                AS discountTTC,
       COALESCE(SUM(Q1.CA_TTC), 0)                          AS ca_ttc,
       COALESCE(SUM(Q1.CA_HT), 0)                           AS ca_ht,
       COALESCE(SUM(Q1.CA_TTC)+SUM(Q2.totalDiscountTTC), 0) AS CA_BRUT_TTC,
       COALESCE(SUM(Q3.CA_NET_HTVA), 0)                     AS CA_NET_HTVA,
       COALESCE(SUM(Q2.qty)   ,0)                           AS item_qty
FROM
  (SELECT U.id AS employee_id,
          concat(U.first_name, ' ', U.last_name) AS employee,
          SUM(T.totalttc) AS CA_TTC,
          SUM(T.totalht) AS CA_HT,
          EXTRACT(HOUR FROM T.enddate) AS entryHour,
          T.id AS ticketID,
          NULLIF(T.invoicecancelled , '')::INT AS cancelled
   FROM public.ticket T
   LEFT JOIN quick_user U ON T.operator=U.wynd_id
   JOIN user_restaurant R ON R.user_id=U.id
   AND R.restaurant_id= :restaurant_id
   WHERE T.origin_restaurant_id = :restaurant_id
     AND (T.status <> :canceled
          AND T.status <> :abandoned
          AND T.counted_canceled <> TRUE)
     AND T.enddate >= :startDate
     AND T.enddate <= :endDate
   GROUP BY employee_id,
            employee,
            entryHour,
            ticketID,
            cancelled
   ORDER BY employee) AS Q1
LEFT JOIN
  (SELECT TL.ticket_id AS ticketId,
          ABS(SUM(TL.discount_ttc)) AS totalDiscountTTC,
          SUM(TL.qty) AS qty
   FROM ticket_line TL
   WHERE TL.origin_restaurant_id = :restaurant_id
     AND TL.enddate >= :startDate
     AND TL.enddate <= :endDate
   GROUP BY TL.ticket_id) AS Q2 ON Q1.ticketID = Q2.ticketId
LEFT JOIN
  (SELECT T.id AS ticketid,
          T.totalht AS CA_NET_HTVA
   FROM public.ticket T
   LEFT JOIN ticket_payment TP ON TP.ticket_id=T.id
   WHERE T.origin_restaurant_id = :restaurant_id
     AND T.status <> :canceled
     AND T.status <> :abandoned
     AND T.counted_canceled <> TRUE
     AND T.enddate >= :startDate
     AND T.enddate <= :endDate
     AND TP.id_payment != :employee_meal
   GROUP BY T.id,T.totalht) AS Q3 ON Q1.ticketID = Q3.ticketid
GROUP BY employee_id,employee,hour ORDER BY employee; ";

        // bind
        $from = $criteria['from']." 00:00:00";
        $to = $criteria['to']." 23:59:59";
        $currentRestaurantId = $currentRestaurant->getId();
        $stm = $conn->prepare($sql);
        $stm->bindParam('startDate', $from);
        $stm->bindParam('endDate', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandoned', $abandonment);
        $stm->bindParam('employee_meal', $mealTicket);
        $stm->bindParam("restaurant_id", $currentRestaurantId);

        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }

    /**
     * @param $criteria
     * @param Restaurant $currentRestaurant
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */

    public function getCaPerEmployeePerQuartHour($criteria, Restaurant $currentRestaurant)
    {
        $conn = $this->_em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $mealTicket = TicketPayment::MEAL_TICKET;

        $sql = "SELECT 
       Q1.employee_id                                       AS employee_id,
       Q1.employee                                          AS employee,
       Q1.entryHour                                         AS hour,
       Q1.schedule                                         AS schedule,
       COALESCE(COUNT(Q1.ticketID), 0) - (2 * COALESCE(SUM(Q1.cancelled), 0))                   AS ticket_count,
       COALESCE(SUM(Q1.cancelled), 0)                       AS cancelled,
       COALESCE(SUM(Q2.totalDiscountTTC), 0)                AS discountTTC,
       COALESCE(SUM(Q1.CA_TTC), 0)                          AS ca_ttc,
       COALESCE(SUM(Q1.CA_HT), 0)                           AS ca_ht,
       COALESCE(SUM(Q1.CA_TTC)+SUM(Q2.totalDiscountTTC), 0) AS CA_BRUT_TTC,
       COALESCE(SUM(Q3.CA_NET_HTVA), 0)                     AS CA_NET_HTVA,
       COALESCE(SUM(Q2.qty)   ,0)                           AS item_qty
FROM
  (SELECT U.id AS employee_id,
          concat(U.first_name, ' ', U.last_name) AS employee,
          SUM(T.totalttc) AS CA_TTC,
          SUM(T.totalht) AS CA_HT,
          CASE WHEN extract(minute from T.enddate) >= 15 and extract(minute from T.enddate) < 30 THEN 1 
               WHEN extract(minute from T.enddate) >= 30 and extract(minute from T.enddate) < 45 THEN 2
               WHEN extract(minute from T.enddate) >= 45 THEN 3
               ELSE 0 END as schedule,
          EXTRACT(HOUR FROM T.enddate) AS entryHour,
          T.id AS ticketID,
          NULLIF(T.invoicecancelled , '')::INT AS cancelled
   FROM public.ticket T
   LEFT JOIN quick_user U ON T.operator=U.wynd_id
   JOIN user_restaurant R ON R.user_id=U.id
   AND R.restaurant_id= :restaurant_id
   WHERE T.origin_restaurant_id = :restaurant_id
     AND (T.status <> :canceled
          AND T.status <> :abandoned
          AND T.counted_canceled <> TRUE)
     AND T.enddate >= :startDate
     AND T.enddate <= :endDate
   GROUP BY employee_id,
            employee,
            entryHour,
            schedule,
            ticketID,
            cancelled
   ORDER BY employee) AS Q1
LEFT JOIN
  (SELECT TL.ticket_id AS ticketId,
          ABS(SUM(TL.discount_ttc)) AS totalDiscountTTC,
          SUM(TL.qty) AS qty
   FROM ticket_line TL
   WHERE TL.origin_restaurant_id = :restaurant_id
     AND TL.enddate >= :startDate
     AND TL.enddate <= :endDate
   GROUP BY TL.ticket_id) AS Q2 ON Q1.ticketID = Q2.ticketId
LEFT JOIN
  (SELECT T.id AS ticketid,
          T.totalht AS CA_NET_HTVA
   FROM public.ticket T
   LEFT JOIN ticket_payment TP ON TP.ticket_id=T.id
   WHERE T.origin_restaurant_id = :restaurant_id
     AND T.status <> :canceled
     AND T.status <> :abandoned
     AND T.counted_canceled <> TRUE
     AND T.enddate >= :startDate
     AND T.enddate <= :endDate
     AND TP.id_payment != :employee_meal
   GROUP BY T.id,T.totalht) AS Q3 ON Q1.ticketID = Q3.ticketid
GROUP BY employee_id,employee,schedule, hour ORDER BY employee, hour,schedule; ";

        // bind
        $from = $criteria['from']." 00:00:00";
        $to = $criteria['to']." 23:59:59";
        $currentRestaurantId = $currentRestaurant->getId();
        $stm = $conn->prepare($sql);
        $stm->bindParam('startDate', $from);
        $stm->bindParam('endDate', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandoned', $abandonment);
        $stm->bindParam('employee_meal', $mealTicket);
        $stm->bindParam("restaurant_id", $currentRestaurantId);

        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }



    public function getTakeOutSalePercentage(\DateTime $startDate, \DateTime $endDate, Restaurant $restaurant = null)
    {
        $startDate = $startDate->format('Y-m-d');
        $endDate = $endDate->format('Y-m-d');
        if (is_null($restaurant)) {
            return 0;
        }
        $currentRestaurantId = $restaurant->getId();
        $connection = $this->_em->getConnection();

        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0))) - ABS(Sum(COALESCE(RIGHT_RESULT.totalBr, 0))) AS totalttc FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id AND  T.status <> :canceled AND T.status <> :abandonment  AND T.counted_canceled <> TRUE 
                 AND T.date >= :startDate AND T.date <= :endDate AND ((T.origin = 'POS' AND T.destination = 'TakeOut') OR T.destination = 'TAKE OUT')
                     ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TP.ticket_id AS ticketId,
                    SUM(TP.amount) AS totalBr
                    FROM ticket_payment TP LEFT JOIN ticket t on TP.ticket_id = t.id
				    WHERE TP.id_payment= :bon_repas_id and t.origin_restaurant_id = :origin_restaurant_id AND  t.status <> :canceled AND t.status <> :abandonment  AND t.counted_canceled <> TRUE 
                 AND t.date >= :startDate AND t.date <= :endDate
                        GROUP BY TP.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
               ";

        $stm = $connection->prepare($sql);
        $stm->bindParam('startDate', $startDate);
        $stm->bindParam('endDate', $endDate);
        $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('bon_repas_id', $mealTicket);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->execute();
        $takeOutTTC = $stm->fetchColumn(0);


        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0))) - ABS(Sum(COALESCE(RIGHT_RESULT.totalBr, 0))) AS totalttc FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id AND  T.status <> :canceled AND T.status <> :abandonment  AND T.counted_canceled <> TRUE 
                 AND T.date >= :startDate AND T.date <= :endDate   ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TP.ticket_id AS ticketId,
                    SUM(TP.amount) AS totalBr
                    FROM ticket_payment TP LEFT JOIN ticket t on TP.ticket_id = t.id
				    WHERE TP.id_payment= :bon_repas_id and t.origin_restaurant_id = :origin_restaurant_id AND  t.status <> :canceled AND t.status <> :abandonment  AND t.counted_canceled <> TRUE 
                 AND t.date >= :startDate AND t.date <= :endDate
                        GROUP BY TP.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
               ";

        $stm = $connection->prepare($sql);
        $stm->bindParam('startDate', $startDate);
        $stm->bindParam('endDate', $endDate);
        $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('bon_repas_id', $mealTicket);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->execute();
        $totalTTC = $stm->fetchColumn(0);

        if ($totalTTC != 0) {
            return ($takeOutTTC * 100) / $totalTTC;
        } else {
            return 0;
        }
    }

    public function getTakeOutSalePercentageForDaillyResult(\DateTime $startDate, \DateTime $endDate, Restaurant $restaurant = null)
    {
        $startDate = $startDate->format('Y-m-d');
        $endDate = $endDate->format('Y-m-d');
        if (is_null($restaurant)) {
            return 0;
        }
        $currentRestaurantId = $restaurant->getId();
        $connection = $this->_em->getConnection();

        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0))) - ABS(Sum(COALESCE(RIGHT_RESULT.totalBr, 0))) AS totalttc FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id AND  T.status <> :canceled AND T.status <> :abandonment  AND T.counted_canceled <> TRUE 
                 AND T.date >= :startDate AND T.date <= :endDate AND (
                      ( (T.origin = 'POS' AND T.destination = 'TakeOut') OR T.destination = 'TAKE OUT')
                       OR (T.origin = 'KIOSK' AND T.destination = 'TakeOut') OR ( (T.origin = 'DriveThru' AND T.destination = 'DriveThru') OR T.destination = 'DRIVE' OR (T.origin = 'MyQuick' AND T.destination = 'MQDrive') OR (T.origin = 'MyQuick' AND T.destination = 'MQCurbside')) OR 
                       (T.destination = 'Delivery'  OR T.destination = 'ATOUberEats' OR  T.destination = 'ATODeliveroo'  OR   T.destination = 'ATOTakeAway' 
                        OR  T.destination = 'ATOHelloUgo' OR  T.destination = 'ATOEasy2Eat'  OR  T.destination = 'ATOGoosty' OR  T.destination = 'ATOWolt') OR (  T.origin = 'MyQuick' AND T.destination = 'MyQuickTakeout'  ))
                     ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TP.ticket_id AS ticketId,
                    SUM(TP.amount) AS totalBr
                    FROM ticket_payment TP LEFT JOIN ticket t on TP.ticket_id = t.id
				    WHERE TP.id_payment= :bon_repas_id and t.origin_restaurant_id = :origin_restaurant_id AND  t.status <> :canceled AND t.status <> :abandonment  AND t.counted_canceled <> TRUE 
                 AND t.date >= :startDate AND t.date <= :endDate 
                        GROUP BY TP.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
               ";

        $stm = $connection->prepare($sql);
        $stm->bindParam('startDate', $startDate);
        $stm->bindParam('endDate', $endDate);
        $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('bon_repas_id', $mealTicket);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->execute();
        $takeOutTTC = $stm->fetchColumn(0);


        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0))) - ABS(Sum(COALESCE(RIGHT_RESULT.totalBr, 0))) AS totalttc FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id AND  T.status <> :canceled AND T.status <> :abandonment  AND T.counted_canceled <> TRUE 
                 AND T.date >= :startDate AND T.date <= :endDate 
                     ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TP.ticket_id AS ticketId,
                    SUM(TP.amount) AS totalBr
                    FROM ticket_payment TP LEFT JOIN ticket t on TP.ticket_id = t.id
				    WHERE TP.id_payment= :bon_repas_id and t.origin_restaurant_id = :origin_restaurant_id AND  t.status <> :canceled AND t.status <> :abandonment  AND t.counted_canceled <> TRUE 
                 AND t.date >= :startDate AND t.date <= :endDate
                        GROUP BY TP.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
               ";

        $stm = $connection->prepare($sql);
        $stm->bindParam('startDate', $startDate);
        $stm->bindParam('endDate', $endDate);
        $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('bon_repas_id', $mealTicket);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->execute();
        $totalTTC = $stm->fetchColumn(0);

        if ($totalTTC != 0) {
            return ($takeOutTTC * 100) / $totalTTC;
        } else {
            return 0;
        }
    }

    public function getDriveSalePercentage(\DateTime $startDate, \DateTime $endDate, Restaurant $restaurant = null)
    {
        $startDate = $startDate->format('Y-m-d');
        $endDate = $endDate->format('Y-m-d');
        if (is_null($restaurant)) {
            return 0;
        }
        $currentRestaurantId = $restaurant->getId();
        $connection = $this->_em->getConnection();


        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0))) - ABS(Sum(COALESCE(RIGHT_RESULT.totalBr, 0))) AS totalttc FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id AND  T.status <> :canceled AND T.status <> :abandonment  AND T.counted_canceled <> TRUE 
                 AND T.date >= :startDate AND T.date <= :endDate AND (( (T.origin = 'DriveThru' AND T.destination = 'DriveThru') OR T.destination = 'DRIVE' OR (T.origin = 'MyQuick' AND T.destination = 'MQDrive') OR (T.origin = 'MyQuick' AND T.destination = 'MQCurbside') ) )
                     ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TP.ticket_id AS ticketId,
                    SUM(TP.amount) AS totalBr
                    FROM ticket_payment TP LEFT JOIN ticket t on TP.ticket_id = t.id
				    WHERE TP.id_payment= :bon_repas_id and t.origin_restaurant_id = :origin_restaurant_id AND  t.status <> :canceled AND t.status <> :abandonment  AND t.counted_canceled <> TRUE 
                 AND t.date >= :startDate AND t.date <= :endDate 
                        GROUP BY TP.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
               ";
        $stm = $connection->prepare($sql);
        $stm->bindParam('startDate', $startDate);
        $stm->bindParam('endDate', $endDate);
        $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('bon_repas_id', $mealTicket);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->execute();
        $driveTTC = $stm->fetchColumn(0);


        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0))) - ABS(Sum(COALESCE(RIGHT_RESULT.totalBr, 0))) AS totalttc FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id AND  T.status <> :canceled AND T.status <> :abandonment  AND T.counted_canceled <> TRUE 
                 AND T.date >= :startDate AND T.date <= :endDate 
                     ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TP.ticket_id AS ticketId,
                    SUM(TP.amount) AS totalBr
                    FROM ticket_payment TP LEFT JOIN ticket t on TP.ticket_id = t.id
				    WHERE TP.id_payment= :bon_repas_id and t.origin_restaurant_id = :origin_restaurant_id AND  t.status <> :canceled AND t.status <> :abandonment  AND t.counted_canceled <> TRUE 
                 AND t.date >= :startDate AND t.date <= :endDate
                        GROUP BY TP.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
               ";
        $stm = $connection->prepare($sql);
        $stm->bindParam('startDate', $startDate);
        $stm->bindParam('endDate', $endDate);
        $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('bon_repas_id', $mealTicket);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->execute();
        $totalTTC = $stm->fetchColumn(0);

        if ($totalTTC != 0) {
            return ($driveTTC * 100) / $totalTTC;
        } else {
            return 0;
        }
    }

    public function getKioskSalePercentage(\DateTime $startDate, \DateTime $endDate, Restaurant $restaurant = null)
    {
        $startDate = $startDate->format('Y-m-d');
        $endDate = $endDate->format('Y-m-d');
        if (is_null($restaurant)) {
            return 0;
        }
        $currentRestaurantId = $restaurant->getId();
        $connection = $this->_em->getConnection();

        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0))) - ABS(Sum(COALESCE(RIGHT_RESULT.totalBr, 0))) AS totalttc FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id AND  T.status <> :canceled AND T.status <> :abandonment  AND T.counted_canceled <> TRUE 
                 AND T.date >= :startDate AND T.date <= :endDate AND T.origin = 'KIOSK' AND T.destination = 'TakeOut'
                     ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TP.ticket_id AS ticketId,
                    SUM(TP.amount) AS totalBr
                    FROM ticket_payment TP LEFT JOIN ticket t on TP.ticket_id = t.id
				    WHERE TP.id_payment= :bon_repas_id and t.origin_restaurant_id = :origin_restaurant_id AND  t.status <> :canceled AND t.status <> :abandonment  AND t.counted_canceled <> TRUE 
                 AND t.date >= :startDate AND t.date <= :endDate 
                        GROUP BY TP.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
               ";
        $stm = $connection->prepare($sql);
        $stm->bindParam('startDate', $startDate);
        $stm->bindParam('endDate', $endDate);
        $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('bon_repas_id', $mealTicket);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->execute();
        $kioskTTC = $stm->fetchColumn(0);

        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0))) - ABS(Sum(COALESCE(RIGHT_RESULT.totalBr, 0))) AS totalttc FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id AND  T.status <> :canceled AND T.status <> :abandonment  AND T.counted_canceled <> TRUE 
                 AND T.date >= :startDate AND T.date <= :endDate 
                     ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TP.ticket_id AS ticketId,
                    SUM(TP.amount) AS totalBr
                    FROM ticket_payment TP LEFT JOIN ticket t on TP.ticket_id = t.id
				    WHERE TP.id_payment= :bon_repas_id and t.origin_restaurant_id = :origin_restaurant_id AND  t.status <> :canceled AND t.status <> :abandonment  AND t.counted_canceled <> TRUE 
                 AND t.date >= :startDate AND t.date <= :endDate
                        GROUP BY TP.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
               ";
        $stm = $connection->prepare($sql);
        $stm->bindParam('startDate', $startDate);
        $stm->bindParam('endDate', $endDate);
        $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('bon_repas_id', $mealTicket);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->execute();
        $totalTTC = $stm->fetchColumn(0);

        if ($totalTTC != 0) {
            return ($kioskTTC * 100) / $totalTTC;
        } else {
            return 0;
        }
    }


public function getEorderingSalePercentage(\DateTime $startDate, \DateTime $endDate, Restaurant $restaurant = null)
{
    $startDate = $startDate->format('Y-m-d');
    $endDate = $endDate->format('Y-m-d');
    if (is_null($restaurant)) {
        return 0;
    }
    $currentRestaurantId = $restaurant->getId();
    $connection = $this->_em->getConnection();

    $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0))) - ABS(Sum(COALESCE(RIGHT_RESULT.totalBr, 0))) AS totalttc FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id AND  T.status <> :canceled AND T.status <> :abandonment  AND T.counted_canceled <> TRUE 
                 AND T.date >= :startDate AND T.date <= :endDate AND T.origin = 'MyQuick' AND T.destination = 'MyQuickTakeout'
                     ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TP.ticket_id AS ticketId,
                    SUM(TP.amount) AS totalBr
                    FROM ticket_payment TP LEFT JOIN ticket t on TP.ticket_id = t.id
				    WHERE TP.id_payment= :bon_repas_id and t.origin_restaurant_id = :origin_restaurant_id AND  t.status <> :canceled AND t.status <> :abandonment  AND t.counted_canceled <> TRUE 
                 AND t.date >= :startDate AND t.date <= :endDate 
                        GROUP BY TP.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
               ";
    $stm = $connection->prepare($sql);
    $stm->bindParam('startDate', $startDate);
    $stm->bindParam('endDate', $endDate);
    $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
    $canceled = Ticket::CANCEL_STATUS_VALUE;
    $abandonment = Ticket::ABONDON_STATUS_VALUE;
    $mealTicket = TicketPayment::MEAL_TICKET;
    $stm->bindParam('bon_repas_id', $mealTicket);
    $stm->bindParam('canceled', $canceled);
    $stm->bindParam('abandonment', $abandonment);
    $stm->execute();
    $eOrderingTTC = $stm->fetchColumn(0);


    $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0))) - ABS(Sum(COALESCE(RIGHT_RESULT.totalBr, 0))) AS totalttc FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id AND  T.status <> :canceled AND T.status <> :abandonment  AND T.counted_canceled <> TRUE 
                 AND T.date >= :startDate AND T.date <= :endDate 
                     ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TP.ticket_id AS ticketId,
                    SUM(TP.amount) AS totalBr
                    FROM ticket_payment TP LEFT JOIN ticket t on TP.ticket_id = t.id
				    WHERE TP.id_payment= :bon_repas_id and t.origin_restaurant_id = :origin_restaurant_id AND  t.status <> :canceled AND t.status <> :abandonment  AND t.counted_canceled <> TRUE 
                 AND t.date >= :startDate AND t.date <= :endDate
                        GROUP BY TP.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
               ";
    $stm = $connection->prepare($sql);
    $stm->bindParam('startDate', $startDate);
    $stm->bindParam('endDate', $endDate);
    $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
    $canceled = Ticket::CANCEL_STATUS_VALUE;
    $abandonment = Ticket::ABONDON_STATUS_VALUE;
    $mealTicket = TicketPayment::MEAL_TICKET;
    $stm->bindParam('bon_repas_id', $mealTicket);
    $stm->bindParam('canceled', $canceled);
    $stm->bindParam('abandonment', $abandonment);
    $stm->execute();
    $totalTTC = $stm->fetchColumn(0);

    if ($totalTTC != 0) {
        return ($eOrderingTTC * 100) / $totalTTC;
    } else {
        return 0;
    }
}

    public function getDeliverySalePercentage(\DateTime $startDate, \DateTime $endDate, Restaurant $restaurant = null)
    {
        $startDate = $startDate->format('Y-m-d');
        $endDate = $endDate->format('Y-m-d');
        if (is_null($restaurant)) {
            return 0;
        }
        $currentRestaurantId = $restaurant->getId();
        $connection = $this->_em->getConnection();

        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0))) - ABS(Sum(COALESCE(RIGHT_RESULT.totalBr, 0))) AS totalttc FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id AND  T.status <> :canceled AND T.status <> :abandonment  AND T.counted_canceled <> TRUE 
                 AND T.date >= :startDate AND T.date <= :endDate AND (T.destination = 'Delivery' OR T.destination = 'ATOUberEats' OR T.destination = 'ATODeliveroo' OR T.destination = 'ATOTakeAway' OR  T.destination = 'ATOHelloUgo' OR  T.destination = 'ATOEasy2Eat' OR  T.destination = 'ATOGoosty' OR  T.destination = 'ATOWolt' )
                     ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TP.ticket_id AS ticketId,
                    SUM(TP.amount) AS totalBr
                    FROM ticket_payment TP LEFT JOIN ticket t on TP.ticket_id = t.id
				    WHERE TP.id_payment= :bon_repas_id and t.origin_restaurant_id = :origin_restaurant_id AND  t.status <> :canceled AND t.status <> :abandonment  AND t.counted_canceled <> TRUE 
                 AND t.date >= :startDate AND t.date <= :endDate 
                        GROUP BY TP.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
               ";
        $stm = $connection->prepare($sql);
        $stm->bindParam('startDate', $startDate);
        $stm->bindParam('endDate', $endDate);
        $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('bon_repas_id', $mealTicket);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->execute();
        $deliveryTTC = $stm->fetchColumn(0);


        $sql = "SELECT SUM((COALESCE(LEFT_RESULT.CA_TTC, 0))) - ABS(Sum(COALESCE(RIGHT_RESULT.totalBr, 0))) AS totalttc FROM (
                SELECT
                    T.id AS ticketId,
                    T.totalttc AS CA_TTC
                    FROM public.ticket T
                    WHERE T.origin_restaurant_id = :origin_restaurant_id AND  T.status <> :canceled AND T.status <> :abandonment  AND T.counted_canceled <> TRUE 
                 AND T.date >= :startDate AND T.date <= :endDate 
                     ) AS  LEFT_RESULT

                LEFT JOIN (
                    SELECT
                    TP.ticket_id AS ticketId,
                    SUM(TP.amount) AS totalBr
                    FROM ticket_payment TP LEFT JOIN ticket t on TP.ticket_id = t.id
				    WHERE TP.id_payment= :bon_repas_id and t.origin_restaurant_id = :origin_restaurant_id AND  t.status <> :canceled AND t.status <> :abandonment  AND t.counted_canceled <> TRUE 
                 AND t.date >= :startDate AND t.date <= :endDate
                        GROUP BY TP.ticket_id
                ) AS RIGHT_RESULT
                ON LEFT_RESULT.TicketId = RIGHT_RESULT.ticketId
               ";
        $stm = $connection->prepare($sql);
        $stm->bindParam('startDate', $startDate);
        $stm->bindParam('endDate', $endDate);
        $stm->bindParam('origin_restaurant_id', $currentRestaurantId);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm->bindParam('bon_repas_id', $mealTicket);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->execute();
        $totalTTC = $stm->fetchColumn(0);

        if ($totalTTC != 0) {
            return ($deliveryTTC * 100) / $totalTTC;
        } else {
            return 0;
        }
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param Restaurant|null $restaurant
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCancellation(\DateTime $startDate, \DateTime $endDate, Restaurant $restaurant = null)
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder->select('count(t) AS nbr_cancels, COALESCE(SUM(ABS(t.totalTTC)),0) AS total_ttc');
        $queryBuilder->andWhere('t.date >= :from')
            ->andWhere('t.date <= :to')
            ->andWhere('t.invoiceCancelled = :true')
            ->setParameter('from', $startDate)
            ->setParameter('to', $endDate)
            ->setParameter('true', '1');
        if ($restaurant != null) {
            $queryBuilder->andWhere("t.originRestaurant = :restaurant")
                ->setParameter("restaurant", $restaurant);
        }

        $cancelledTickets = $queryBuilder->getQuery()->getResult();

        return $cancelledTickets[0];
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param Restaurant|null $restaurant
     * @return mixed
     */
    public function getAbandons(\DateTime $startDate, \DateTime $endDate, Restaurant $restaurant = null)
    {
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder->andWhere('t.date >= :from')
            ->andWhere('t.date <= :to')
            ->andWhere('t.status = :abandon')
            ->setParameter('from', $startDate)
            ->setParameter('to', $endDate)
            ->setParameter('abandon', $abandonment);
        if ($restaurant != null) {
            $queryBuilder->andWhere("t.originRestaurant = :restaurant")
                ->setParameter("restaurant", $restaurant);
        }

        $queryBuilder->select('count(t) AS nbr_abandons, COALESCE(SUM(t.totalTTC),0) AS total_ttc');
        $abandonsTickets = $queryBuilder->getQuery()->getResult();

        return  $abandonsTickets[0];
    }

    /*
     *
     */
    public function getCorrections(\DateTime $startDate, \DateTime $endDate, Restaurant $restaurant = null)
    {
        $deleteAction=TicketIntervention::DELETE_ACTION;
        $queryBuilder = $this->createQueryBuilder('t')->join("t.interventions","interventions");
        $queryBuilder->andWhere('t.date >= :from')
            ->andWhere('t.date <= :to')
            ->andWhere('interventions.action = :action')
            ->setParameter('from', $startDate)
            ->setParameter('to', $endDate)
            ->setParameter('action', $deleteAction);
        if ($restaurant != null) {
            $queryBuilder->andWhere("t.originRestaurant = :restaurant")
                ->setParameter("restaurant", $restaurant);
        }

        $queryBuilder->select('COALESCE(SUM(interventions.itemQty),0) AS nbr_corrections, COALESCE(SUM(interventions.itemQty*interventions.itemPrice),0) AS total_ttc');
        $ticketsCorrections = $queryBuilder->getQuery()->getResult();

        return $ticketsCorrections[0];
    }

    public function getTicketsCount(\DateTime $startDate, \DateTime $endDate, Restaurant $restaurant = null)
    {
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $queryBuilder = $this->createQueryBuilder('t');

        $queryBuilder->andWhere('t.date BETWEEN :from and :to')
            ->setParameter('from', $startDate)
            ->setParameter('to', $endDate);

        $queryBuilder->andWhere('t.status <> :canceled and t.status <> :abandonment  and t.countedCanceled <> TRUE')
            ->setParameter('canceled', $canceled)
            ->setParameter('abandonment', $abandonment);

        if ($restaurant != null) {
            $queryBuilder->andWhere('t.originRestaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        $queryBuilder->select('count(t) AS nbrTicket');
        $nbtickets = $queryBuilder->getQuery()->getSingleScalarResult();

        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder->andWhere('t.date BETWEEN :from and :to')
            ->setParameter('from', $startDate)
            ->setParameter('to', $endDate)
            ->andWhere('t.invoiceCancelled = :true')
            ->setParameter('true', '1');
        if ($restaurant != null) {
            $queryBuilder->andWhere("t.originRestaurant = :restaurant")
                ->setParameter("restaurant", $restaurant);
        }

        $queryBuilder->select('count(t) AS nbrCancels');
        $cancelledTickets = $queryBuilder->getQuery()->getSingleScalarResult();

        return $nbtickets - (2 * $cancelledTickets);

    }


    public function getCaPerHourForHisto( $criteria, Restaurant $currentRestaurant){
        $from = $criteria['from'];
        $to = $criteria['to'];
        $restaurantId=$currentRestaurant->getId();
        $conn = $this->_em->getConnection();
        $sql = " SELECT sum(FRH.brut_ttc) as CA , EXTRACT(HOUR FROM FRH.date_hour) AS entryHour,sum(FRH.ticket_number) as countTicket from financial_revenue_by_hour_hist FRH
                  where FRH.date_day >= :from and FRH.date_day <= :to and FRH.origin_restaurant_id= :restaurant GROUP BY entryHour ORDER BY entryHour";
        $stm = $conn->prepare($sql);
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('restaurant',$restaurantId );
        $stm->execute();
        $data = $stm->fetchAll();
        return $data;
    }
    public function getCaHTVAPerHourForHisto( $criteria, Restaurant $currentRestaurant){
        $from = $criteria['from'];
        $to = $criteria['to'];
        $restaurantId=$currentRestaurant->getId();
        $conn = $this->_em->getConnection();
        $sql = " SELECT sum(FRH.net_ht) as CA , EXTRACT(HOUR FROM FRH.date_hour) AS entryHour,sum(FRH.ticket_number) as countTicket from financial_revenue_by_hour_hist FRH
                  where FRH.date_day >= :from and FRH.date_day <= :to and FRH.origin_restaurant_id= :restaurant GROUP BY entryHour ORDER BY entryHour";
        $stm = $conn->prepare($sql);
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('restaurant',$restaurantId );
        $stm->execute();
        $data = $stm->fetchAll();
        return $data;
    }

  ////done by belsem 2019

    public function getTicketsAnnulationsPerCanal
    ($canal,
     $restaurantId,
     $startDate = null,
     $endDate = null,
     $starHour = null,
     $endHour = null)
    {
        $conn = $this->_em->getConnection();
        $sql = "SELECT ABS(SUM(totalttc)) FROM public.ticket WHERE origin_restaurant_id= :restaurantId  ";
        if ($startDate) {
            $sql .= " AND ticket.date >= :startDate ";
        }
        if ($endDate) {
            $sql .= " AND ticket.date <= :endDate ";
        }
        if ($starHour) {
            $sql .= " AND date_part('HOUR',ticket.enddate) >= CAST( :startHour as INTEGER) ";
        }
        if ($endHour) {
            $sql .= " AND date_part('HOUR',ticket.enddate) <= CAST( :endHour as INTEGER) ";
        }
        switch (strtolower($canal)) {
            case 'drive' :
                $sql .= " AND ( (ticket.origin = 'DriveThru' AND ticket.destination = 'DriveThru') OR ticket.destination = 'DRIVE' OR (ticket.origin = 'MyQuick' AND ticket.destination = 'MQDrive') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'MQCurbside') ) ";
                break;
            case 'eatin' :
                $sql .= " AND ( (ticket.origin = 'POS' AND ticket.destination = 'EatIn') OR ticket.destination = 'TAKE IN' OR (ticket.origin = '' AND ticket.destination = '') OR (ticket.origin IS NULL AND ticket.destination IS NULL) ) ";
                break;
            case 'takeout':
                $sql .= " AND ((ticket.origin = 'POS' AND ticket.destination = 'TakeOut') OR ticket.destination = 'TAKE OUT') ";
                break;
            case 'kioskin':
                $sql .= " AND ticket.origin = 'KIOSK' AND ticket.destination = 'EatIn' ";
                break;
            case 'kioskout':
                $sql .= " AND ticket.origin = 'KIOSK' AND ticket.destination = 'TakeOut' ";
                break;
            case 'delivery' :
                $sql .= " AND (  (ticket.origin = 'POS' AND ticket.destination = 'Delivery') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'ATOUberEats') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'ATODeliveroo') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'ATOTakeAway') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'ATOHelloUgo') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'ATOEasy2Eat') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'ATOGoosty') OR (ticket.origin = 'MyQuick' AND ticket.destination = 'ATOWolt')) ";
                break;
            case 'e_ordering_in' :
                $sql .= " AND (  ticket.origin = 'MyQuick' AND ticket.destination = 'MyQuickEatIn'  ) ";
                break;
            case 'e_ordering_out' :
                $sql .= " AND (  ticket.origin = 'MyQuick' AND ticket.destination = 'MyQuickTakeout'  ) ";
                break;
        }

        $sqlCancelled = $sql . " AND ticket.invoiceCancelled = '1' ;";
        $stm = $conn->prepare($sqlCancelled);

        $stm->bindParam('restaurantId', $restaurantId);
        if ($startDate) {
            $stm->bindParam('startDate', $startDate);
        }
        if ($endDate) {
            $stm->bindParam('endDate', $endDate);
        }
        if ($starHour) {
            $stm->bindParam('startHour', $starHour);
        }
        if ($endHour) {
            $stm->bindParam('endHour', $endHour);
        }
        $stm->execute();
        $cancelledTicket = $stm->fetchColumn(0);

        return $cancelledTicket;
    }


 public function getTicketsCorrectionsPerCanal
    ($canal,
     $restaurantId,
     $startDate = null,
     $endDate = null,
     $starHour = null,
     $endHour = null)
    {
        $conn = $this->_em->getConnection();

        $deleteAction = TicketIntervention::DELETE_ACTION;
        $sql = 'SELECT
                COALESCE(SUM(ti.itemQty*ti.itemPrice),0) 
                from ticket_intervention ti
                join ticket t  ON ti.ticket_id =  t.id
                where ti.action= :action_type and t.origin_restaurant_id = :restaurantId ';
        if ($startDate) {
            $sql .= " AND t.date >= :startDate ";
        }
        if ($endDate) {
            $sql .= " AND t.date <= :endDate ";
        }
        if ($starHour) {
            $sql .= " AND date_part('HOUR',t.enddate) >= CAST( :startHour as INTEGER) ";
        }
        if ($endHour) {
            $sql .= " AND date_part('HOUR',t.enddate) <= CAST( :endHour as INTEGER) ";
        }
        //new canal


        switch (strtolower($canal)) {
            case 'drive' :
                $sql .= " AND ( (t.origin = 'DriveThru' AND t.destination = 'DriveThru') OR t.destination = 'DRIVE' OR (t.origin = 'MyQuick' AND t.destination = 'MQDrive') OR (t.origin = 'MyQuick' AND t.destination = 'MQCurbside')) ";
                break;
            case 'eatin' :
                $sql .= " AND ( (t.origin = 'POS' AND t.destination = 'EatIn') OR t.destination = 'TAKE IN' OR (t.origin = '' AND t.destination = '') OR (t.origin IS NULL AND t.destination IS NULL) ) ";
                break;
            case 'takeout':
                $sql .= " AND ((t.origin = 'POS' AND t.destination = 'TakeOut') OR t.destination = 'TAKE OUT') ";
                break;
            case 'kioskin':
                $sql .= " AND t.origin = 'KIOSK' AND t.destination = 'EatIn' ";
                break;
            case 'kioskout':
                $sql .= " AND t.origin = 'KIOSK' AND t.destination = 'TakeOut' ";
                break;
            case 'delivery' :
                $sql .= " AND (  (t.origin = 'POS' AND t.destination = 'Delivery') OR (t.origin = 'MyQuick' AND t.destination = 'ATOUberEats') OR (t.origin = 'MyQuick' AND t.destination = 'ATODeliveroo') OR (t.origin = 'MyQuick' AND t.destination = 'ATOTakeAway') OR (t.origin = 'MyQuick' AND t.destination = 'ATOHelloUgo') OR (t.origin = 'MyQuick' AND t.destination = 'ATOEasy2Eat') OR (t.origin = 'MyQuick' AND t.destination = 'ATOGoosty') OR (t.origin = 'MyQuick' AND t.destination = 'ATOWolt')) ";
                break;
            case 'e_ordering_in' :
            $sql .= " AND (  t.origin = 'MyQuick' AND t.destination = 'MyQuickEatIn'  ) ";
            break;
            case 'e_ordering_out' :
                $sql .= " AND (  t.origin = 'MyQuick' AND t.destination = 'MyQuickTakeout'  ) ";
                break;


        }
        $stm = $conn->prepare($sql);

        $stm->bindParam('restaurantId', $restaurantId);
        $stm->bindParam('action_type', $deleteAction);
        if ($startDate) {
            $stm->bindParam('startDate', $startDate);
        }
        if ($endDate) {
            $stm->bindParam('endDate', $endDate);
        }
        if ($starHour) {
            $stm->bindParam('startHour', $starHour);
        }
        if ($endHour) {
            $stm->bindParam('endHour', $endHour);
        }
        $stm->execute();
        $correctedTicket = $stm->fetchColumn(0);

        return $correctedTicket;


    }
//added by belsem 
	public function getKioskEorderingTickets(\DateTime $date,	
                                         Restaurant $restaurant,	
                                         $counted = false)	
{	
    $ticketsqb=$this->getEntityManager()->getRepository(Ticket::class)->createQueryBuilder('t');	
    $ticketsqb->select('t')	
        ->where('t.origin IN (:origin) and t.originRestaurant=:restaurant and t.date= :date and t.counted =:counted')	
        ->setParameter('restaurant', $restaurant)	
        ->setParameter('date', $date->setTime(0, 0, 0))	
        ->setParameter('counted', $counted)	
        ->setParameter('origin', array(SoldingCanal::KIOSK,SoldingCanal::origin_e_ordering));	
    $tickets = $ticketsqb->getQuery()->getResult();	
    return $tickets;	
}
  public function getRounding($filter, $restaurant_id = null)
    {

        $conn = $this->_em->getConnection();

        $D1 = $filter['beginDate']; //  transform to D1-1j
        $D2 = $filter['endDate'];
        $true = true;

        $sql = "
                    SELECT
			    	SUM(TL.discount_ttc::NUMERIC) AS discount_amount,
                    SUM(TL.discount_ht) AS discount_ht
                    FROM public.ticket_line TL
					WHERE (TL.is_discount = True and TL.discount_id ='5061' AND TL.date >= :D1 AND TL.date <= :D2 AND TL.combo = FALSE 
					and TL.origin_restaurant_id= :restaurant)
              
               ";

        $rounding='5061';
        $stm = $conn->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('restaurant', $restaurant_id);
        $stm->execute();
        $data = $stm->fetchAll();
        $result = [
            "data" => $data,
        ];

        return $result;
    }
}
