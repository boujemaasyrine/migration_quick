<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 24/10/2016
 * Time: 11:16
 */

namespace AppBundle\Report\Service;


use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class ReportStrikeRateService
{

    private $translator;
    private $em;
    private $restaurantService;
    private $phpExcel;

    /**
     * ReportStrikeRateService constructor.
     * @param $translator
     * @param $em
     */
    public function __construct(EntityManager $em, Translator $translator, RestaurantService $restaurantService,Factory $factory)
    {
        $this->translator = $translator;
        $this->em = $em;
        $this->restaurantService = $restaurantService;
        $this->phpExcel = $factory;
    }


    public function getList($filter)
    {
        $restaurantId = $this->restaurantService->getCurrentRestaurant()->getId();
        $output = array();
        $start = $filter['startDate']->format('Y-m-d');
        $end = $filter['endDate']->format('Y-m-d');

        $sql = "SELECT
                DISTINCT (description)                        AS ITEM,
                plu                                           AS ITEM_PLU ,
                composition                                   AS COMPOSITION,
                sum(qty)                                      AS QTY ,
                ticket_id                                     AS TICKET_ID
                FROM ticket_line 
                WHERE date BETWEEN :startDate AND :endDate AND
                      status <> :canceled AND 
                      status <> :abondon  AND
                      counted_canceled <> TRUE AND
                      origin_restaurant_id = :restaurant_id
                GROUP BY description,plu,ticket_id, composition,QTY,CATEGORY ORDER BY description ASC ";

        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('startDate', $start);
        $stm->bindParam('endDate', $end);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $stm->bindParam('canceled', $canceled);
        $abondon = Ticket::ABONDON_STATUS_VALUE;
        $stm->bindParam('abondon', $abondon);
        $stm->bindParam('restaurant_id', $restaurantId);

        $stm->execute();
        $results = $stm->fetchAll();

        //get the total tickets count and the cancelled tickets count to calculate the valid tickets count
        $query = $this->em->getRepository('Financial:Ticket')->createQueryBuilder('t')
            ->select('t')
            ->andWhere('t.date >= :startDate')
            ->andWhere('t.date <= :endDate')
            ->andWhere('t.status <> :canceled AND t.status <> :abandonment  AND t.countedCanceled <> TRUE')
            ->andWhere('t.originRestaurant = :restaurant')
            ->setParameter('startDate', $start)
            ->setParameter('endDate', $end)
            ->setParameter('canceled', $canceled)
            ->setParameter('abandonment', $abondon)
            ->setParameter('restaurant', $restaurantId);
        $query->select('count(t) AS nbrTicket');
        $nbtickets = $query->getQuery()->getSingleScalarResult();

        $query->andWhere("t.invoiceCancelled = '1' ");
        $cancelledTickets = $query->getQuery()->getSingleScalarResult();
        $output['report'] = $this->serializeList($results);
        $output['startDate'] = $filter['startDate']->format('d-m-Y');
        $output['endDate'] = $filter['endDate']->format('d-m-Y');
        $output['report']['total_ticket_count'] = $nbtickets - (2 * $cancelledTickets);

        return $output;
    }

    private function serializeList($data)
    {
        $report = array();
        $item_total_count=0;
        foreach ($data as $line) {
            $ticketId = $line['ticket_id'];

            if (isset($report[$line['item']]['sale_qty'])) {
                $report[$line['item']]['sale_qty'] += $line['qty'];
                $item_total_count+= $line['qty'];
            } else {
                $report[$line['item']]['sale_qty'] = 0;
                $report[$line['item']]['sale_qty'] += $line['qty'];
                $item_total_count+= $line['qty'];
            }

            if (!isset($report[$line['item']]['tickets'])) {
                $report[$line['item']]['tickets'] = array();
            }
            if (!in_array($ticketId, $report[$line['item']]['tickets'], true)) {
                array_push($report[$line['item']]['tickets'], $ticketId);
            }

        }


        return array(
            "items_count"=>count($report),
            "items_qty"=>$item_total_count,
            "report" => $report,
        );
    }

    /**
     * @param \DateTime $date
     * @return \DateTime $lastDate
     */
    public function getDateOfLastYear($date)
    {
        $lastDate = new \DateTime();
        $lastDate->setTimestamp($date->getTimestamp() - (86400 * 364));

        return $lastDate;
    }

    public function getPyramidList($filter)
    {


        $reportThisYear = array();
        $reportLastYear = array();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abondon = Ticket::ABONDON_STATUS_VALUE;
        $start = $filter['startDate']->format('Y-m-d');
        $end = $filter['endDate']->format('Y-m-d');
        $comparableStart = $filter['compareStartDate']->format('Y-m-d');
        $comparableEnd = $filter['compareEndDate']->format('Y-m-d');
        $ticket_type = 'invoice';
        // (2)
        $sql_2 = "SELECT  CAST (COALESCE (SUM( ABS(ticket_line.discount_ttc)),0) AS INTEGER ) AS discount_ttc
                FROM ticket
                JOIN ticket_line ON ticket_line.ticket_id= ticket.id
                WHERE
                ticket.date BETWEEN :startDate AND :endDate AND
                      ticket.status <> :canceled AND
                      ticket.status <> :abondon  AND
                      ticket.type = :ticket_type";
        $stm_2 = $this->em->getConnection()->prepare($sql_2);
        $stm_2->bindParam('startDate', $start);
        $stm_2->bindParam('endDate', $end);
        $stm_2->bindParam('canceled', $canceled);
        $stm_2->bindParam('abondon', $abondon);
        $stm_2->bindParam('ticket_type', $ticket_type);
        $stm_2->execute();
        $reportThisYear['ligne_2'] = ($stm_2->fetch(0)['discount_ttc']) / 1000;

        $stm_2 = $this->em->getConnection()->prepare($sql_2);
        $stm_2->bindParam('startDate', $comparableStart);
        $stm_2->bindParam('endDate', $comparableEnd);
        $stm_2->bindParam('canceled', $canceled);
        $stm_2->bindParam('abondon', $abondon);
        $stm_2->bindParam('ticket_type', $ticket_type);
        $stm_2->execute();

        $reportLastYear['ligne_2'] = ($stm_2->fetch(0)['discount_ttc']) / 1000;

        if ($reportLastYear['ligne_2'] != 0) {
            $reportVariation['ligne_2'] = (($reportThisYear['ligne_2'] - $reportLastYear['ligne_2']) / $reportLastYear['ligne_2']) * 100;
        } else {
            $reportVariation['ligne_2'] = 0;
        }

        //*******************************************************//
        // (3)
        $sql_3 = " SELECT CAST (COALESCE (sum(ticket.totalttc),0) AS INTEGER ) AS br_ttc
                 FROM ticket
                 JOIN ticket_payment ON ticket.id= ticket_payment.ticket_id
                 WHERE
                 ticket.date BETWEEN :startDate AND :endDate AND
                 ticket.status <> :canceled AND
                 ticket.status <> :abondon  AND
                 ticket.type = :ticket_type AND
                 (
                 ticket_payment.id_payment= :meal_ticket OR
                 ticket_payment.id_payment= :restaurent_ticket
                 )
                 ";

        $mealTicket = TicketPayment::MEAL_TICKET;
        $restaurantTicket = TicketPayment::CHECK_RESTAURANT;

        $stm_3 = $this->em->getConnection()->prepare($sql_3);
        $stm_3->bindParam('startDate', $start);
        $stm_3->bindParam('endDate', $end);
        $stm_3->bindParam('canceled', $canceled);
        $stm_3->bindParam('abondon', $abondon);
        $stm_3->bindParam('ticket_type', $ticket_type);
        $stm_3->bindParam('meal_ticket', $mealTicket);
        $stm_3->bindParam('restaurent_ticket', $restaurantTicket);
        $stm_3->execute();
        $reportThisYear['ligne_3'] = ($stm_3->fetch(0)['br_ttc'] / 1000);

        $stm_3 = $this->em->getConnection()->prepare($sql_3);
        $stm_3->bindParam('startDate', $comparableStart);
        $stm_3->bindParam('endDate', $comparableEnd);
        $stm_3->bindParam('canceled', $canceled);
        $stm_3->bindParam('abondon', $abondon);
        $stm_3->bindParam('ticket_type', $ticket_type);
        $stm_3->bindParam('meal_ticket', $mealTicket);
        $stm_3->bindParam('restaurent_ticket', $restaurantTicket);
        $stm_3->execute();
        $reportLastYear['ligne_3'] = ($stm_3->fetch(0)['br_ttc'] / 1000);

        if ($reportLastYear['ligne_3'] != 0) {
            $reportVariation['ligne_3'] = (($reportThisYear['ligne_3'] - $reportLastYear['ligne_3']) / $reportLastYear['ligne_3']) * 100;
        } else {
            $reportVariation['ligne_3'] = 0;
        }
        //*****************************************************//
        $sqlTVA = "SELECT CAST (COALESCE(sum(ticket_line.totaltva),0) AS INTEGER ) AS tva
                  FROM ticket_line
                  JOIN ticket ON ticket.id = ticket_line.ticket_id
                  WHERE
                  ticket.date BETWEEN :startDate AND :endDate AND
                  ticket.status <> :canceled AND
                  ticket.status <> :abondon  AND
                  ticket.type = :ticket_type ";
        $stmTVA = $this->em->getConnection()->prepare($sqlTVA);
        $stmTVA->bindParam('startDate', $start);
        $stmTVA->bindParam('endDate', $end);
        $stmTVA->bindParam('canceled', $canceled);
        $stmTVA->bindParam('abondon', $abondon);
        $stmTVA->bindParam('ticket_type', $ticket_type);
        $stmTVA->execute();
        $tvaThisYear = $stmTVA->fetch(0)['tva'];

        $stmTVA = $this->em->getConnection()->prepare($sqlTVA);
        $stmTVA->bindParam('startDate', $comparableStart);
        $stmTVA->bindParam('endDate', $comparableEnd);
        $stmTVA->bindParam('canceled', $canceled);
        $stmTVA->bindParam('abondon', $abondon);
        $stmTVA->bindParam('ticket_type', $ticket_type);
        $stmTVA->execute();
        $tvaLastYear = $stmTVA->fetch(0)['tva'];

        //**********************************************************************//
        // (7)
        $sql_7 = " SELECT CAST (COALESCE(count(id),0) AS INTEGER ) AS ticket_count
                  FROM ticket
                  WHERE
                  ticket.date BETWEEN :startDate AND :endDate AND
                  ticket.status <> :canceled AND
                  ticket.status <> :abondon  AND
                  ticket.type = :ticket_type";
        $stm_7 = $this->em->getConnection()->prepare($sql_7);
        $stm_7->bindParam('startDate', $start);
        $stm_7->bindParam('endDate', $end);
        $stm_7->bindParam('canceled', $canceled);
        $stm_7->bindParam('abondon', $abondon);
        $stm_7->bindParam('ticket_type', $ticket_type);
        $stm_7->execute();
        $reportThisYear['ligne_7'] = $stm_7->fetch(0)['ticket_count'];

        $stm_7 = $this->em->getConnection()->prepare($sql_7);
        $stm_7->bindParam('startDate', $comparableStart);
        $stm_7->bindParam('endDate', $comparableEnd);
        $stm_7->bindParam('canceled', $canceled);
        $stm_7->bindParam('abondon', $abondon);
        $stm_7->bindParam('ticket_type', $ticket_type);
        $stm_7->execute();
        $reportLastYear['ligne_7'] = $stm_7->fetch(0)['ticket_count'];

        if ($reportLastYear['ligne_7'] != 0) {
            $reportVariation['ligne_7'] = (($reportThisYear['ligne_7'] - $reportLastYear['ligne_7']) / $reportLastYear['ligne_7']) * 100;
        } else {
            $reportVariation['ligne_7'] = 0;
        }
        //*******************************************************//
        // (10) = PV TTC (EUR) ARTICLE hors divers
        $sql_10 = "SELECT CAST (COALESCE(sum(ticket_line.totalttc),0) AS INTEGER ) AS pv_ttc
                 FROM ticket_line
                 JOIN ticket ON ticket.id=ticket_line.ticket_id
                 WHERE
                  ticket_line.category <> 'DIVERS' AND
                  ticket.date BETWEEN :startDate AND :endDate AND
                  ticket.status <> :canceled AND
                  ticket.status <> :abondon  AND
                  ticket.type = :ticket_type";
        $stm_10 = $this->em->getConnection()->prepare($sql_10);
        $stm_10->bindParam('startDate', $start);
        $stm_10->bindParam('endDate', $end);
        $stm_10->bindParam('canceled', $canceled);
        $stm_10->bindParam('abondon', $abondon);
        $stm_10->bindParam('ticket_type', $ticket_type);
        $stm_10->execute();
        $reportThisYear['ligne_10'] = $stm_10->fetch(0)['pv_ttc'];

        $stm_10 = $this->em->getConnection()->prepare($sql_10);
        $stm_10->bindParam('startDate', $comparableStart);
        $stm_10->bindParam('endDate', $comparableEnd);
        $stm_10->bindParam('canceled', $canceled);
        $stm_10->bindParam('abondon', $abondon);
        $stm_10->bindParam('ticket_type', $ticket_type);
        $stm_10->execute();
        $reportLastYear['ligne_10'] = $stm_10->fetch(0)['pv_ttc'];

        if ($reportLastYear['ligne_10'] != 0) {
            $reportVariation['ligne_10'] = (($reportThisYear['ligne_10'] - $reportLastYear['ligne_10']) / $reportLastYear['ligne_10']) * 100;
        } else {
            $reportVariation['ligne_10'] = 0;
        }
        //*************************************************//

        $sqlArticles = "SELECT CAST (COALESCE(count(*),0) AS INTEGER )AS articles
                       FROM ticket_line
                       JOIN ticket ON ticket.id = ticket_line.ticket_id
                       WHERE
                       ticket.date BETWEEN :startDate AND :endDate AND
                       ticket.status <> :canceled AND
                       ticket.status <> :abondon  AND
                       ticket.type = :ticket_type";
        $stmArticles = $this->em->getConnection()->prepare($sqlArticles);
        $stmArticles->bindParam('startDate', $start);
        $stmArticles->bindParam('endDate', $end);
        $stmArticles->bindParam('canceled', $canceled);
        $stmArticles->bindParam('abondon', $abondon);
        $stmArticles->bindParam('ticket_type', $ticket_type);
        $stmArticles->execute();
        $articleThisYear = $stmArticles->fetch(0)['articles'];

        $stmArticles = $this->em->getConnection()->prepare($sqlArticles);
        $stmArticles->bindParam('startDate', $comparableStart);
        $stmArticles->bindParam('endDate', $comparableEnd);
        $stmArticles->bindParam('canceled', $canceled);
        $stmArticles->bindParam('abondon', $abondon);
        $stmArticles->bindParam('ticket_type', $ticket_type);
        $stmArticles->execute();
        $articleLastYear = $stm_10->fetch(0)['articles'];

        //*************************************************//
        // (11) = PP HT (EUR) / ARTICLE hors divers
        $sql_PPHT = "SELECT CAST (COALESCE(sum(ticket_line.totalht),0) AS INTEGER )AS pp_ht
                 FROM ticket_line
                 JOIN ticket ON ticket.id=ticket_line.ticket_id
                  WHERE
                  ticket.date BETWEEN :startDate AND :endDate AND
                  ticket.status <> :canceled AND
                  ticket.status <> :abondon  AND
                  ticket.type = :ticket_type";

        if ($articleThisYear == 0) {
            $reportThisYear['ligne_11'] = 0;
        } else {
            $stm_PPHT = $this->em->getConnection()->prepare($sql_PPHT);
            $stm_PPHT->bindParam('startDate', $start);
            $stm_PPHT->bindParam('endDate', $end);
            $stm_PPHT->bindParam('canceled', $canceled);
            $stm_PPHT->bindParam('abondon', $abondon);
            $stm_PPHT->bindParam('ticket_type', $ticket_type);
            $stm_PPHT->execute();

            $reportThisYear['ligne_11'] = $stm_PPHT->fetch(0)['pp_ht'] / $articleThisYear;
        }
        if ($articleLastYear == 0) {
            $reportLastYear['ligne_11'] = 0;
        } else {
            $stm_PPHT = $this->em->getConnection()->prepare($sql_PPHT);
            $stm_PPHT->bindParam('startDate', $comparableStart);
            $stm_PPHT->bindParam('endDate', $comparableEnd);
            $stm_PPHT->bindParam('canceled', $canceled);
            $stm_PPHT->bindParam('abondon', $abondon);
            $stm_PPHT->bindParam('ticket_type', $ticket_type);
            $stm_PPHT->execute();

            $reportLastYear['ligne_11'] = $stm_PPHT->fetch(0)['pp_ht'] / $articleLastYear;
        }

        if ($reportLastYear['ligne_11'] != 0) {
            $reportVariation['ligne_11'] = (($reportThisYear['ligne_11'] - $reportLastYear['ligne_11']) / $reportLastYear['ligne_11']) * 100;
        } else {
            $reportVariation['ligne_11'] = 0;
        }
        //*********************************************************//
        // (12) = NOMBRE ARTICLES / TICKET Non- rééclatés hors divers
        $sqlArticlesNonReeclate = "SELECT CAST (COALESCE(count(*),0) AS INTEGER ) AS articles_non_reeclate FROM ticket_line
                                  JOIN ticket ON ticket.id = ticket_line.ticket_id
                                  WHERE
                                  ticket.date BETWEEN :startDate AND :endDate AND
                                  ticket.status <> :canceled     AND
                                  ticket.status <> :abondon      AND
                                  ticket.type = :ticket_type     AND
                                  ticket_line.composition =FALSE AND
                                  ticket_line.category <>'DIVERS'";
        $stmArticlesNonReeclate = $this->em->getConnection()->prepare($sqlArticlesNonReeclate);
        $stmArticlesNonReeclate->bindParam('startDate', $start);
        $stmArticlesNonReeclate->bindParam('endDate', $end);
        $stmArticlesNonReeclate->bindParam('canceled', $canceled);
        $stmArticlesNonReeclate->bindParam('abondon', $abondon);
        $stmArticlesNonReeclate->bindParam('ticket_type', $ticket_type);
        $stmArticlesNonReeclate->execute();
        $articleNonReeclateThisYear = $stmArticlesNonReeclate->fetch(0)['articles_non_reeclate'];
        if ($articleNonReeclateThisYear > 0) {
            $reportThisYear['ligne_12'] = $articleThisYear / $articleNonReeclateThisYear;
        } else {
            $reportThisYear['ligne_12'] = 0;
        }

        $stmArticlesNonReeclate = $this->em->getConnection()->prepare($sqlArticlesNonReeclate);
        $stmArticlesNonReeclate->bindParam('startDate', $comparableStart);
        $stmArticlesNonReeclate->bindParam('endDate', $comparableEnd);
        $stmArticlesNonReeclate->bindParam('canceled', $canceled);
        $stmArticlesNonReeclate->bindParam('abondon', $abondon);
        $stmArticlesNonReeclate->bindParam('ticket_type', $ticket_type);
        $stmArticlesNonReeclate->execute();
        $articleNonReeclateLastYear = $stmArticlesNonReeclate->fetch(0)['articles_non_reeclate'];
        if ($articleNonReeclateLastYear > 0) {
            $reportLastYear['ligne_12'] = $articleLastYear / $articleNonReeclateLastYear;
        } else {
            $reportLastYear['ligne_12'] = 0;
        }

        if ($reportLastYear['ligne_12'] != 0) {
            $reportVariation['ligne_12'] = (($reportThisYear['ligne_12'] - $reportLastYear['ligne_12']) / $reportLastYear['ligne_12']) * 100;
        } else {
            $reportVariation['ligne_12'] = 0;
        }
        //*******************************************************************//
        // (13) = NOMBRE ARTICLES / TICKET Rééclatés hors divers

        $sqlArticlesReeclate = "SELECT CAST (COALESCE(count(*),0) AS INTEGER ) AS articles_reeclate FROM ticket_line
                                  JOIN ticket ON ticket.id = ticket_line.ticket_id
                                  WHERE
                                  ticket.date BETWEEN :startDate AND :endDate AND
                                  ticket.status <> :canceled     AND
                                  ticket.status <> :abondon      AND
                                  ticket.type = :ticket_type     AND
                                  ticket_line.composition =TRUE AND
                                  ticket_line.category <>'DIVERS'";
        $stmArticlesReeclate = $this->em->getConnection()->prepare($sqlArticlesReeclate);
        $stmArticlesReeclate->bindParam('startDate', $start);
        $stmArticlesReeclate->bindParam('endDate', $end);
        $stmArticlesReeclate->bindParam('canceled', $canceled);
        $stmArticlesReeclate->bindParam('abondon', $abondon);
        $stmArticlesReeclate->bindParam('ticket_type', $ticket_type);
        $stmArticlesReeclate->execute();
        $articleReeclateThisYear = $stmArticlesReeclate->fetch(0)['articles_reeclate'];
        if ($articleReeclateThisYear > 0) {
            $reportThisYear['ligne_13'] = $articleThisYear / $articleReeclateThisYear;
        } else {
            $reportThisYear['ligne_13'] = 0;
        }
        $stmArticlesReeclate = $this->em->getConnection()->prepare($sqlArticlesReeclate);
        $stmArticlesReeclate->bindParam('startDate', $comparableStart);
        $stmArticlesReeclate->bindParam('endDate', $comparableEnd);
        $stmArticlesReeclate->bindParam('canceled', $canceled);
        $stmArticlesReeclate->bindParam('abondon', $abondon);
        $stmArticlesReeclate->bindParam('ticket_type', $ticket_type);
        $stmArticlesReeclate->execute();
        $articleReeclateLastYear = $stmArticlesReeclate->fetch(0)['articles_reeclate'];
        if ($articleReeclateLastYear > 0) {
            $reportLastYear['ligne_13'] = $articleLastYear / $articleReeclateLastYear;
        } else {
            $reportLastYear['ligne_13'] = 0;
        }

        if ($reportLastYear['ligne_13'] != 0) {
            $reportVariation['ligne_13'] = (($reportThisYear['ligne_13'] - $reportLastYear['ligne_13']) / $reportLastYear['ligne_13']) * 100;
        } else {
            $reportVariation['ligne_13'] = 0;
        }
        //*******************************************//


        // (8) = (10)*(12)
        $reportThisYear['ligne_8'] = $reportThisYear['ligne_10'] * $reportThisYear['ligne_12'];
        $reportLastYear['ligne_8'] = $reportLastYear['ligne_10'] * $reportLastYear['ligne_12'];

        if ($reportLastYear['ligne_8'] != 0) {
            $reportVariation['ligne_8'] = (($reportThisYear['ligne_8'] - $reportLastYear['ligne_8']) / $reportLastYear['ligne_8']) * 100;
        } else {
            $reportVariation['ligne_8'] = 0;
        }

        // (4) = (7) * (8)
        $reportThisYear['ligne_4'] = ($reportThisYear['ligne_7'] * $reportThisYear['ligne_8']) / 1000;
        $reportLastYear['ligne_4'] = ($reportLastYear['ligne_7'] * $reportLastYear['ligne_8']) / 1000;
        if ($reportLastYear['ligne_4'] != 0) {
            $reportVariation['ligne_4'] = (($reportThisYear['ligne_4'] - $reportLastYear['ligne_4']) / $reportLastYear['ligne_4']) * 100;
        } else {
            $reportVariation['ligne_4'] = 0;
        }

        // (1) = (4)-TVA-(2)-(3)
        $reportThisYear['ligne_1'] = ($reportThisYear['ligne_4'] - $tvaThisYear - $reportThisYear['ligne_2'] - $reportThisYear['ligne_3']) / 1000;
        $reportLastYear['ligne_1'] = ($reportLastYear['ligne_4'] - $tvaLastYear - $reportLastYear['ligne_2'] - $reportLastYear['ligne_3']) / 1000;

        if ($reportLastYear['ligne_1'] != 0) {
            $reportVariation['ligne_1'] = (($reportThisYear['ligne_1'] - $reportLastYear['ligne_1']) / $reportLastYear['ligne_1']) * 100;
        } else {
            $reportVariation['ligne_1'] = 0;
        }
        //(9) = (11)*(12)
        $reportThisYear['ligne_9'] = $reportThisYear['ligne_11'] * $reportThisYear['ligne_12'];
        $reportLastYear['ligne_9'] = $reportLastYear['ligne_11'] * $reportLastYear['ligne_12'];

        if ($reportLastYear['ligne_9'] != 0) {
            $reportVariation['ligne_9'] = (($reportThisYear['ligne_9'] - $reportLastYear['ligne_9']) / $reportLastYear['ligne_9']) * 100;
        } else {
            $reportVariation['ligne_9'] = 0;
        }
        // (5) = (7)*(9)
        $reportThisYear['ligne_5'] = ($reportThisYear['ligne_7'] * $reportThisYear['ligne_9']) / 1000;
        $reportLastYear['ligne_5'] = ($reportLastYear['ligne_7'] * $reportLastYear['ligne_9']) / 1000;

        if ($reportLastYear['ligne_5'] != 0) {
            $reportVariation['ligne_5'] = (($reportThisYear['ligne_5'] - $reportLastYear['ligne_5']) / $reportLastYear['ligne_5']) * 100;
        } else {
            $reportVariation['ligne_5'] = 0;
        }
        //(6) = (5)/((4)-TVA)
        if (($reportLastYear['ligne_4'] - $tvaLastYear) > 0) {
            $reportLastYear['ligne_6'] = $reportLastYear['ligne_5'] / ($reportLastYear['ligne_4'] - $tvaLastYear);
        } else {
            $reportLastYear['ligne_6'] = 0;
        }

        if (($reportThisYear['ligne_4'] - $tvaThisYear) > 0) {
            $reportThisYear['ligne_6'] = $reportThisYear['ligne_5'] / ($reportThisYear['ligne_4'] - $tvaThisYear);
        } else {
            $reportThisYear['ligne_6'] = 0;
        }

        if ($reportLastYear['ligne_6'] != 0) {
            $reportVariation['ligne_6'] = (($reportThisYear['ligne_6'] - $reportLastYear['ligne_6']) / $reportLastYear['ligne_6']) * 100;
        } else {
            $reportVariation['ligne_6'] = 0;
        }
        //(14)=((4)-(2))/(7)*1000
        if ($reportThisYear['ligne_7'] > 0) {
            $reportThisYear['ligne_14'] = ($reportThisYear['ligne_4'] - $reportThisYear['ligne_2']) / ($reportThisYear['ligne_7'] * 1000);
        } else {
            $reportThisYear['ligne_14'] = 0;
        }

        if ($reportLastYear['ligne_7'] > 0) {
            $reportLastYear['ligne_14'] = ($reportLastYear['ligne_4'] - $reportLastYear['ligne_2']) / ($reportLastYear['ligne_7'] * 1000);
        } else {
            $reportLastYear['ligne_14'] = 0;
        }

        if ($reportLastYear['ligne_14'] != 0) {
            $reportVariation['ligne_14'] = (($reportThisYear['ligne_14'] - $reportLastYear['ligne_14']) / $reportLastYear['ligne_14']) * 100;
        } else {
            $reportVariation['ligne_14'] = 0;
        }
        //(15)=(14)*(1-TVA)
        $reportThisYear['ligne_15'] = $reportThisYear['ligne_14'] * (1 - $tvaThisYear);
        $reportLastYear['ligne_15'] = $reportLastYear['ligne_14'] * (1 - $tvaLastYear);

        if ($reportLastYear['ligne_15'] != 0) {
            $reportVariation['ligne_15'] = (($reportThisYear['ligne_15'] - $reportLastYear['ligne_15']) / $reportLastYear['ligne_15']) * 100;
        } else {
            $reportVariation['ligne_15'] = 0;
        }

        $output = array();
        $output['startDate'] = $filter['startDate']->format('d-m-Y');
        $output['endDate'] = $filter['startDate']->format('d-m-Y');
        $output['compareStartDate'] = $filter['compareStartDate']->format('d-m-Y');
        $output['compareEndDate'] = $filter['compareEndDate']->format('d-m-Y');
        $output['this_year'] = $reportThisYear;
        $output['last_year'] = $reportLastYear;
        $output['variation'] = $reportVariation;

        return $output;
    }


    public function generateExcelFile($result, $data, $logoPath){
        $colorOne = "ECECEC";
        $colorTwo = "E5CFAB";
        $colorThree = "1F8AFF"; //old: "637AFB"
        $colorTiltle = "D3BB72";
        $currentRestaurant=$data['currentRestaurant'];
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('strike_rate.title'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('strike_rate.title');
        $sheet->setCellValue('B5', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("B5"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B5"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 5), 22, true);

        //logo
        $objDrawing = new \PHPExcel_Worksheet_Drawing();
        $objDrawing->setName('Logo');
        $objDrawing->setDescription('Logo');
        $objDrawing->setPath($logoPath);
        $objDrawing->setOffsetX(35);
        $objDrawing->setOffsetY(0);
        $objDrawing->setCoordinates('A2');
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 2), 12, true);
        $objDrawing->setWidth(28);                 //set width, height
        $objDrawing->setHeight(32);
        $objDrawing->setWorksheet($sheet);
        //restaurant name
        $sheet->mergeCells("B2:F2");
        $content = $currentRestaurant->getCode().' '.$currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);

        //FILTER ZONE

        //Periode
        $sheet->mergeCells("A10:L10");
        ExcelUtilities::setFont($sheet->getCell('A10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A10"), $colorTwo);
        $sheet->setCellValue('A10', $this->translator->trans('report.period').":");
        ExcelUtilities::setCellAlignment($sheet->getCell("A10"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A10"), $alignmentV);

        $sheet->mergeCells("A11:B11");
        ExcelUtilities::setFont($sheet->getCell('A11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A11"), $colorOne);
        $sheet->setCellValue('A11', $this->translator->trans('keyword.from').":");
        $sheet->mergeCells("C11:D11");
        ExcelUtilities::setFont($sheet->getCell('C11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C11"), $colorOne);
        $sheet->setCellValue('C11', $data['startDate']->format('Y-m-d'));    // START DATE


        // END DATE
        $sheet->mergeCells("E11:F11");
        ExcelUtilities::setFont($sheet->getCell('E11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E11"), $colorOne);
        $sheet->setCellValue('E11', $this->translator->trans('keyword.to').":");
        $sheet->mergeCells("G11:H11");
        ExcelUtilities::setFont($sheet->getCell('G11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G11"), $colorOne);
        $sheet->setCellValue('G11', $data['endDate']->format('Y-m-d'));

        // item name
        $sheet->mergeCells("A12:C12");
        ExcelUtilities::setFont($sheet->getCell('A12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A12"), $colorOne);
        $sheet->setCellValue('A12', $this->translator->trans('strike_rate.item_name').":");
        $sheet->mergeCells("D12:F12");
        ExcelUtilities::setFont($sheet->getCell('D12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D12"), $colorOne);
        $sheet->setCellValue('D12', $data['itemName']);

        $sheet->mergeCells("I11:L11");
        ExcelUtilities::setFont($sheet->getCell('I11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I11"), $colorOne);

        $sheet->mergeCells("G12:L12");
        ExcelUtilities::setFont($sheet->getCell('G12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G12"), $colorOne);

        // nbr ticket
        $sheet->mergeCells("A14:E14");
        ExcelUtilities::setFont($sheet->getCell('A14'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A14"), $colorThree);
        $sheet->setCellValue('A14', $this->translator->trans('strike_rate.nbr_total_ticket'));
        $sheet->mergeCells("F14:G14");
        ExcelUtilities::setFont($sheet->getCell('F14'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F14"), $colorThree);
        $sheet->setCellValue('F14', $result['report']['total_ticket_count']);
        //Content
        $i = 16;
        //Items
        $sheet->mergeCells('A'.$i.':C'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTiltle);
        $sheet->setCellValue('A'.$i, $this->translator->trans('strike_rate.items'));
        //Qty
        $sheet->mergeCells('D'.$i.':E'.$i);
        ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D".$i), $colorTiltle);
        $sheet->setCellValue('D'.$i, $this->translator->trans('strike_rate.qty'));
        //Produit
        $sheet->mergeCells('F'.$i.':G'.$i);
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorTiltle);
        $sheet->setCellValue('F'.$i, $this->translator->trans('strike_rate.pr_product'));
        //Par 100 ticket
        $sheet->mergeCells('H'.$i.':J'.$i);
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorTiltle);
        $sheet->setCellValue('H'.$i, $this->translator->trans('strike_rate.per_100_product'));
        //Border
        $cell = 'A';
        while ($cell != 'K') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }
        $i++;

        $total_per=0;
        $total_per_per_ticket=0;
        $qty_total = 0;
        foreach ($result['report']['report'] as $item =>$value)
        {
           if(empty($data['itemName'])){

               //Items
               $sheet->mergeCells('A'.$i.':C'.$i);
               ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
               $sheet->setCellValue('A'.$i, $item);
               //Qty
               $sheet->mergeCells('D'.$i.':E'.$i);
               ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
               $sheet->setCellValue('D'.$i, $value['sale_qty']);
               $pr = (($value['sale_qty'])/ $result['report']['items_qty'] )*100;
               //Produit
               $sheet->mergeCells('F'.$i.':G'.$i);
               ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
               $sheet->setCellValue('F'.$i, number_format($pr,'2','.',''));
               $total_per = $total_per + $pr;

               $pr = (($value['sale_qty'])/$result['report']['total_ticket_count'] )*100;
               //Par 100 ticket
               $sheet->mergeCells('H'.$i.':J'.$i);
               ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
               $sheet->setCellValue('H'.$i, number_format($pr,'2','.',''));
               $total_per_per_ticket = $total_per_per_ticket + $pr;
               //Border
               $cell = 'A';
               while ($cell != 'K') {
                   ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                   $cell++;
               }
               $i++;
               $qty_total = $qty_total + $value['sale_qty'];
           }elseif($data['itemName'] == $item){
            //Items
            $sheet->mergeCells('A'.$i.':C'.$i);
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            $sheet->setCellValue('A'.$i, $item);
            //Qty
            $sheet->mergeCells('D'.$i.':E'.$i);
            ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
            $sheet->setCellValue('D'.$i, $value['sale_qty']);
            $pr = (($value['sale_qty'])/ $result['report']['items_qty'] )*100;
            //Produit
            $sheet->mergeCells('F'.$i.':G'.$i);
            ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
            $sheet->setCellValue('F'.$i, number_format($pr,'2','.',''));
            $total_per = $total_per + $pr;

            $pr = (($value['sale_qty'])/$result['report']['total_ticket_count'] )*100;
            //Par 100 ticket
            $sheet->mergeCells('H'.$i.':J'.$i);
            ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
            $sheet->setCellValue('H'.$i, number_format($pr, '2','.',''));
            $total_per_per_ticket = $total_per_per_ticket + $pr;
               //Border
               $cell = 'A';
               while ($cell != 'K') {
                   ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                   $cell++;
               }
               $i++;
               $qty_total = $qty_total + $value['sale_qty'];
           }

        }
        //Total
        $sheet->mergeCells('A'.$i.':C'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTiltle);
        $sheet->setCellValue('A'.$i, $this->translator->trans('strike_rate.total'));
        //Qty
        $sheet->mergeCells('D'.$i.':E'.$i);
        ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D".$i), $colorTiltle);
        $sheet->setCellValue('D'.$i, $qty_total);
        //Produit
        $sheet->mergeCells('F'.$i.':G'.$i);
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorTiltle);
        $sheet->setCellValue('F'.$i, number_format($total_per,'2','.',''));

        //Par 100 ticket
        $sheet->mergeCells('H'.$i.':J'.$i);
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorTiltle);
        $sheet->setCellValue('H'.$i, '-');

        //Border
        $cell = 'A';
        while ($cell != 'K') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }


        $filename = "Rapport_strike_rate_".date('dmY_His').".xls";
        // create the writer
        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }
}