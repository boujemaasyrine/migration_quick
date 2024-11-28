<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 20/10/2016
 * Time: 16:33
 */

namespace AppBundle\Report\Service;


use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Merchandise\Entity\CaPrev;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use AppBundle\Financial\Entity\Ticket;
use Liuggio\ExcelBundle\Factory;
use PHPExcel_Shared_Font;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class ReportItemsPerSoldingCanalsService
{
    private $em;
    private $translator;
    private $paramService;
    private $reportSaleService;
    private $restaurantService;
    private $phpExcel;

    private $report;
    private $reportTwo;
    private $br_tickets1;
    private $br_tickets2;

    /**
     * ReportDiscountService constructor.
     * @param $em
     * @param $translator
     * @param $paramService
     * @param $reportSaleService
     */
    public function __construct(
        EntityManager $em,
        Translator $translator,
        ParameterService $paramService,
        ReportSalesService $reportSaleService,
        RestaurantService $restaurantService,
        Factory $factory
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->paramService = $paramService;
        $this->reportSaleService = $reportSaleService;
        $this->restaurantService = $restaurantService;
        $this->phpExcel = $factory;
    }

    public function getHoursList()
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $openingHour = ($this->paramService->getRestaurantOpeningHour(
                $currentRestaurant
            ) == null)
            ? 0
            : $this->paramService->getRestaurantOpeningHour(
                $currentRestaurant
            );
        $closingHour = ($this->paramService->getRestaurantClosingHour(
                $currentRestaurant
            ) == null)
            ? 23
            : $this->paramService->getRestaurantClosingHour(
                $currentRestaurant
            );
        $hoursArray = array();
        if ($closingHour <= $openingHour) {
            ;
        }
        $closingHour += 24;
        for ($i = intval($openingHour); $i <= intval($closingHour); $i++) {
            $h = (($i >= 24) ? ($i - 24) : $i);
            $hoursArray[$h]=$h.":00";
        }

        return $hoursArray;
    }

    public function getList($filter)
    {
        $output = array();
        $start = $filter['startDate']->format('Y-m-d');
        $end = $filter['endDate']->format('Y-m-d');
        if ($start == $end) {
            $output['type'] = 0;
            $sql = " SELECT
                ticket.id                        AS      ticket_id,
                ticket_line.id                   AS      ticket_line_id,
                ticket_line.parentline           AS      parentline,
                ticket_line.totalttc::numeric             AS      total_TTC,
                ticket_line.totalht::numeric              AS      total_ht,
                ticket_line.discount_ttc::numeric         AS      discount,
                ticket_line.discount_ht::numeric          AS      discount_ht,
                ticket_line.totaltva::numeric             AS      total_tva,
                ticket.destination               AS      destination,
                ticket_line.discount_id                AS        discount_id,
                ticket.origin                    AS      origin,
                ticket.invoicenumber             AS      invoice_number,
                ticket.totalht::numeric                   AS      ticket_totalht, 
                ticket.totalttc::numeric                  AS      ticket_totalttc, 
                date_part('HOUR',ticket.enddate) AS      ticket_time,
                ticket_payment.id_payment        AS      id_payment
                FROM ticket
                JOIN ticket_line ON ticket_line.ticket_id = ticket.id
                LEFT JOIN ticket_payment ON ticket.id = CAST(ticket_payment.ticket_id AS NUMERIC) AND ticket_payment.id_payment = '5'
                
                WHERE (
                      ticket.date BETWEEN :startDate AND :endDate AND 
                      ticket.status <> :canceled AND
                      ticket.status <> :abondon AND 
                      ticket.counted_canceled <> TRUE AND
                      ticket.origin_restaurant_id = :restaurant_id AND
                      ticket_line.origin_restaurant_id = :restaurant_id AND
                      ticket_line.date BETWEEN :startDate AND :endDate AND 
                      ticket_line.id > :line_id AND ticket_line.combo = FALSE ";
        } else {
            $output['type'] = 1;
            $sql = " SELECT
                ticket.id                              AS      ticket_id,
                ticket_line.id                         AS      ticket_line_id,
                ticket_line.parentline                 AS      parentline,
                ticket_line.totalttc::numeric                   AS      total_TTC,
                ticket_line.totalht::numeric                    AS      total_ht,
                ticket_line.discount_ttc::numeric               AS      discount,
                ticket_line.discount_ht::numeric                AS      discount_ht,
                ticket_line.is_discount                AS      is_discount,
                ticket_line.discount_id                AS        discount_id,
                ticket_line.totaltva::numeric                   AS      total_tva,
                ticket.destination                     AS      destination,
                ticket.origin                          AS      origin,
                ticket.invoicenumber                   AS      invoice_number,
                ticket.totalht::numeric                         AS      ticket_totalht, 
                ticket.totalttc::numeric                        AS      ticket_totalttc, 
                to_char(ticket.enddate,'DD-MM-YYYY')   AS      ticket_time,
                ticket_payment.id_payment              AS      id_payment
                FROM ticket
                JOIN ticket_line ON ticket_line.ticket_id = ticket.id
                LEFT JOIN ticket_payment ON ticket.id = CAST(ticket_payment.ticket_id AS NUMERIC) AND ticket_payment.id_payment = '5'
                
                WHERE (
                      ticket.date BETWEEN :startDate AND :endDate AND
                      ticket.status <> :canceled AND
                      ticket.status <> :abondon AND
                      ticket_line.status <> :canceled AND
                      ticket_line.status <> :abondon AND 
                      ticket.counted_canceled <> TRUE AND
                      ticket_line.counted_canceled <> TRUE AND
                      ticket.origin_restaurant_id = :restaurant_id AND
                      ticket_line.origin_restaurant_id = :restaurant_id AND
                      ticket_line.date BETWEEN :startDate AND :endDate AND 
                      ticket_line.id > :line_id AND ticket_line.combo = FALSE ";
        }

        if (isset($filter['startHour'])) {
            $sql .= " AND date_part('HOUR',ticket.enddate) >= CAST( :startHour as INTEGER) ";
        }
        if (isset($filter['endHour'])) {
            $sql .= " AND date_part('HOUR',ticket.enddate) <= CAST( :endHour as INTEGER) ";
        }
          // updated by belsem
        //   $sql .= " ) ORDER BY ticket_line.id ASC LIMIT 25000 ";
        $sql .= " ) ORDER BY ticket_line.id ASC  ";

        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('startDate', $start);
        $stm->bindParam('endDate', $end);
        $restaurantId = $filter['currentRestaurant']->getId();
        $stm->bindParam('restaurant_id', $restaurantId);
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $stm->bindParam('canceled', $canceled);
        $abondon = Ticket::ABONDON_STATUS_VALUE;
        $stm->bindParam('abondon', $abondon);

        if (isset($filter['startHour'])) {
            $stm->bindParam('startHour', $filter['startHour']);
        }
        if (isset($filter['endHour'])) {
            $stm->bindParam('endHour', $filter['endHour']);
        }

        $this->report=array();
        $this->reportTwo=array();
        $this->br_tickets1=array();
        $this->br_tickets2=array();

        // EatIn
        $this->report['eat_in']['total_ttc'] = 0;
        $this->report['eat_in']['discount'] = 0;
        $this->report['eat_in']['total_ht'] = 0;
        $this->report['eat_in']['discount_ht'] = 0;
        $this->report['eat_in']['total_tva'] = 0;
        $this->report['eat_in']['br'] = 0;
        $this->report['eat_in']['br_ht'] = 0;
        $this->report['eat_in']['ticket'] = 0;
        $this->report['eat_in']['annulations'] = 0;
        $this->report['eat_in']['corrections'] = 0;

        //TakeOut
        $this->report['take_out']['total_ttc'] = 0;
        $this->report['take_out']['discount'] = 0;
        $this->report['take_out']['br'] = 0;
        $this->report['take_out']['br_ht'] = 0;
        $this->report['take_out']['ticket'] = 0;
        $this->report['take_out']['total_ht'] = 0;
        $this->report['take_out']['discount_ht'] = 0;
        $this->report['take_out']['total_tva'] = 0;
        $this->report['take_out']['annulations'] = 0;
        $this->report['take_out']['corrections'] = 0;


        $this->report['kiosk_out']['total_ttc'] = 0;
        $this->report['kiosk_out']['discount'] = 0;
        $this->report['kiosk_out']['br'] = 0;
        $this->report['kiosk_out']['br_ht'] = 0;
        $this->report['kiosk_out']['ticket'] = 0;
        $this->report['kiosk_out']['total_ht'] = 0;
        $this->report['kiosk_out']['discount_ht'] = 0;
        $this->report['kiosk_out']['total_tva'] = 0;
        $this->report['kiosk_out']['annulations'] = 0;
        $this->report['kiosk_out']['corrections'] = 0;


        $this->report['kiosk_in']['total_ttc'] = 0;
        $this->report['kiosk_in']['discount'] = 0;
        $this->report['kiosk_in']['br'] = 0;
        $this->report['kiosk_in']['br_ht'] = 0;
        $this->report['kiosk_in']['ticket'] = 0;
        $this->report['kiosk_in']['total_ht'] = 0;
        $this->report['kiosk_in']['discount_ht'] = 0;
        $this->report['kiosk_in']['total_tva'] = 0;
        $this->report['kiosk_in']['annulations'] = 0;
        $this->report['kiosk_in']['corrections'] = 0;

        $this->report['drive']['total_ttc'] = 0;
        $this->report['drive']['discount'] = 0;
        $this->report['drive']['br'] = 0;
        $this->report['drive']['br_ht'] = 0;
        $this->report['drive']['ticket'] = 0;
        $this->report['drive']['total_ht'] = 0;
        $this->report['drive']['discount_ht'] = 0;
        $this->report['drive']['total_tva'] = 0;
        $this->report['drive']['annulations'] = 0;
        $this->report['drive']['corrections'] = 0;

        $this->report['delivery']['total_ttc'] = 0;
        $this->report['delivery']['discount'] = 0;
        $this->report['delivery']['br'] = 0;
        $this->report['delivery']['br_ht'] = 0;
        $this->report['delivery']['ticket'] = 0;
        $this->report['delivery']['total_ht'] = 0;
        $this->report['delivery']['discount_ht'] = 0;
        $this->report['delivery']['total_tva'] = 0;
        $this->report['delivery']['annulations'] = 0;
        $this->report['delivery']['corrections'] = 0;

        $this->report['e_ordering_in']['total_ttc'] = 0;
        $this->report['e_ordering_in']['discount'] = 0;
        $this->report['e_ordering_in']['br'] = 0;
        $this->report['e_ordering_in']['br_ht'] = 0;
        $this->report['e_ordering_in']['ticket'] = 0;
        $this->report['e_ordering_in']['total_ht'] = 0;
        $this->report['e_ordering_in']['discount_ht'] = 0;
        $this->report['e_ordering_in']['total_tva'] = 0;
        $this->report['e_ordering_in']['annulations'] = 0;
        $this->report['e_ordering_in']['corrections'] = 0;

        $this->report['e_ordering_out']['total_ttc'] = 0;
        $this->report['e_ordering_out']['discount'] = 0;
        $this->report['e_ordering_out']['br'] = 0;
        $this->report['e_ordering_out']['br_ht'] = 0;
        $this->report['e_ordering_out']['ticket'] = 0;
        $this->report['e_ordering_out']['total_ht'] = 0;
        $this->report['e_ordering_out']['discount_ht'] = 0;
        $this->report['e_ordering_out']['total_tva'] = 0;
        $this->report['e_ordering_out']['annulations'] = 0;
        $this->report['e_ordering_out']['corrections'] = 0;

        $output=array();
        $line_id=0;
        /******* Start report processing*/

        $caDates = array();
        if ($start == $end) {
            $output['type'] = 0;
            $caDates[] = $start;
        } else {
            $output['type'] = 1;
            $tmpDate = $start;
            while (strtotime($tmpDate) <= strtotime($end)) {
                $caDates[] = $tmpDate;
                $tmpDate = date("Y-m-d", strtotime("+1 day", strtotime($tmpDate)));
            }
        }

        // dump("Begin Memory Usage: " . (memory_get_usage()/1048576) . " MB \n");
        do{
            unset($results);
            $stm->bindParam("line_id",$line_id);
            $stm->execute();
            $results = $stm->fetchAll();
            $this->calculateReport($results);
            $last_row=end($results);
            $line_id=$last_row['ticket_line_id'];
            //dump("Fetch Memory Usage: " . (memory_get_usage()/1048576) . " MB \n");
        }while(!empty($results));

        $this->report['eat_in']['ticket']=$this->em->getRepository(Ticket::class)->getTicketCountPerCanal('eatin',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['take_out']['ticket']=$this->em->getRepository(Ticket::class)->getTicketCountPerCanal('takeout',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['kiosk_in']['ticket']=$this->em->getRepository(Ticket::class)->getTicketCountPerCanal('kioskin',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['kiosk_out']['ticket']=$this->em->getRepository(Ticket::class)->getTicketCountPerCanal('kioskout',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['drive']['ticket']=$this->em->getRepository(Ticket::class)->getTicketCountPerCanal('drive',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['delivery']['ticket']=$this->em->getRepository(Ticket::class)->getTicketCountPerCanal('delivery',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['e_ordering_in']['ticket']=$this->em->getRepository(Ticket::class)->getTicketCountPerCanal('e_ordering_in',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['e_ordering_out']['ticket']=$this->em->getRepository(Ticket::class)->getTicketCountPerCanal('e_ordering_out',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);

        //belsem 2019
        //annulations
        $this->report['eat_in']['annulations']=$this->em->getRepository(Ticket::class)->getTicketsAnnulationsPerCanal('eatin',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['take_out']['annulations']= $this->em->getRepository(Ticket::class)->getTicketsAnnulationsPerCanal('takeout',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['kiosk_in']['annulations']= $this->em->getRepository(Ticket::class)->getTicketsAnnulationsPerCanal('kioskin',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['kiosk_out']['annulations']= $this->em->getRepository(Ticket::class)->getTicketsAnnulationsPerCanal('kioskout',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['drive']['annulations']= $this->em->getRepository(Ticket::class)->getTicketsAnnulationsPerCanal('drive',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['delivery']['annulations']= $this->em->getRepository(Ticket::class)->getTicketsAnnulationsPerCanal('delivery',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['e_ordering_in']['annulations']= $this->em->getRepository(Ticket::class)->getTicketsAnnulationsPerCanal('e_ordering_in',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['e_ordering_out']['annulations']= $this->em->getRepository(Ticket::class)->getTicketsAnnulationsPerCanal('e_ordering_out',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        //corrections
        $this->report['eat_in']['corrections']=$this->em->getRepository(Ticket::class)->getTicketsCorrectionsPerCanal('eatin',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['take_out']['corrections']= $this->em->getRepository(Ticket::class)->getTicketsCorrectionsPerCanal('takeout',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['kiosk_in']['corrections']= $this->em->getRepository(Ticket::class)->getTicketsCorrectionsPerCanal('kioskin',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['kiosk_out']['corrections']= $this->em->getRepository(Ticket::class)->getTicketsCorrectionsPerCanal('kioskout',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['drive']['corrections']= $this->em->getRepository(Ticket::class)->getTicketsCorrectionsPerCanal('drive',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['delivery']['corrections']= $this->em->getRepository(Ticket::class)->getTicketsCorrectionsPerCanal('delivery',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['e_ordering_in']['corrections']= $this->em->getRepository(Ticket::class)->getTicketsCorrectionsPerCanal('e_ordering_in',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);
        $this->report['e_ordering_out']['corrections']= $this->em->getRepository(Ticket::class)->getTicketsCorrectionsPerCanal('e_ordering_out',$restaurantId,$start,$end,$filter['startHour'],$filter['endHour']);



        $totalTTC = $this->report['kiosk_in']['total_ttc']+ $this->report['kiosk_out']['total_ttc']+ $this->report['eat_in']['total_ttc']+  $this->report['take_out']['total_ttc'] + $this->report['drive']['total_ttc'] + $this->report['delivery']['total_ttc'] + $this->report['e_ordering_in']['total_ttc'] + $this->report['e_ordering_out']['total_ttc'];

        $this->report['kiosk_in']['total_net'] = $this->report['kiosk_in']['total_ttc'] - abs($this->report['kiosk_in']['discount']) - $this->report['kiosk_in']['br'];
        $this->report['kiosk_in']['total_net_ht'] = $this->report['kiosk_in']['total_ht'] - $this->report['kiosk_in']['br_ht'];
        $this->report['kiosk_in']['pr_total_ttc'] = ($this->report['kiosk_in']['total_ttc'] == 0) ? 0 : (($this->report['kiosk_in']['total_ttc'] / $totalTTC) * 100);

        $this->report['eat_in']['total_net'] = $this->report['eat_in']['total_ttc'] - abs($this->report['eat_in']['discount']) - $this->report['eat_in']['br'];
        $this->report['eat_in']['total_net_ht'] = $this->report['eat_in']['total_ht'] - $this->report['eat_in']['br_ht'];
        $this->report['eat_in']['pr_total_ttc'] = ($this->report['eat_in']['total_ttc'] == 0) ? 0 : (($this->report['eat_in']['total_ttc'] / $totalTTC) * 100);

        $this->report['take_out']['total_net'] = $this->report['take_out']['total_ttc'] - abs($this->report['take_out']['discount']) - $this->report['take_out']['br'];
        $this->report['take_out']['total_net_ht'] = $this->report['take_out']['total_ht'] - $this->report['take_out']['br_ht'];
        $this->report['take_out']['pr_total_ttc'] = ($this->report['take_out']['total_ttc'] == 0) ? 0 : (($this->report['take_out']['total_ttc'] / $totalTTC) * 100);

        $this->report['kiosk_out']['total_net'] = $this->report['kiosk_out']['total_ttc'] - abs($this->report['kiosk_out']['discount']) - $this->report['kiosk_out']['br'];
        $this->report['kiosk_out']['total_net_ht'] = $this->report['kiosk_out']['total_ht'] - $this->report['kiosk_out']['br_ht'];
        $this->report['kiosk_out']['pr_total_ttc'] = ($this->report['kiosk_out']['total_ttc'] == 0) ? 0 : (($this->report['kiosk_out']['total_ttc'] / $totalTTC) * 100);

        $this->report['drive']['total_net'] = $this->report['drive']['total_ttc'] - abs($this->report['drive']['discount']) - $this->report['drive']['br'];
        $this->report['drive']['total_net_ht'] = $this->report['drive']['total_ht'] - $this->report['drive']['br_ht'];
        $this->report['drive']['pr_total_ttc'] = ($this->report['drive']['total_ttc'] == 0) ? 0 : (($this->report['drive']['total_ttc'] / $totalTTC) * 100);

        $this->report['delivery']['total_net'] = $this->report['delivery']['total_ttc'] - abs($this->report['delivery']['discount']) - $this->report['delivery']['br'];
        $this->report['delivery']['total_net_ht'] = $this->report['delivery']['total_ht'] - $this->report['delivery']['br_ht'];
        $this->report['delivery']['pr_total_ttc'] = ($this->report['delivery']['total_ttc'] == 0) ? 0 : (($this->report['delivery']['total_ttc'] / $totalTTC) * 100);

        $this->report['e_ordering_in']['total_net'] = $this->report['e_ordering_in']['total_ttc'] - abs($this->report['e_ordering_in']['discount']) - $this->report['e_ordering_in']['br'];
        $this->report['e_ordering_in']['total_net_ht'] = $this->report['e_ordering_in']['total_ht'] - $this->report['e_ordering_in']['br_ht'];
        $this->report['e_ordering_in']['pr_total_ttc'] = ($this->report['e_ordering_in']['total_ttc'] == 0) ? 0 : (($this->report['e_ordering_in']['total_ttc'] / $totalTTC) * 100);

        $this->report['e_ordering_out']['total_net'] = $this->report['e_ordering_out']['total_ttc'] - abs($this->report['e_ordering_out']['discount']) - $this->report['e_ordering_out']['br'];
        $this->report['e_ordering_out']['total_net_ht'] = $this->report['e_ordering_out']['total_ht'] - $this->report['e_ordering_out']['br_ht'];
        $this->report['e_ordering_out']['pr_total_ttc'] = ($this->report['e_ordering_out']['total_ttc'] == 0) ? 0 : (($this->report['e_ordering_out']['total_ttc'] / $totalTTC) * 100);

        $totalNet = $this->report['kiosk_in']['total_net'] + $this->report['kiosk_out']['total_net'] + $this->report['eat_in']['total_net'] + $this->report['take_out']['total_net'] + $this->report['drive']['total_net']+$this->report['delivery']['total_net'] +$this->report['e_ordering_in']['total_net'] +$this->report['e_ordering_out']['total_net'];

        $this->report['kiosk_in']['pr_total_net'] = ($this->report['kiosk_in']['total_net'] == 0) ? 0 : (($this->report['kiosk_in']['total_net'] / $totalNet) * 100);
        $this->report['eat_in']['pr_total_net'] = ($this->report['eat_in']['total_net'] == 0) ? 0 : (($this->report['eat_in']['total_net'] / $totalNet) * 100);
        $this->report['take_out']['pr_total_net'] = ($this->report['take_out']['total_net'] == 0) ? 0 : (($this->report['take_out']['total_net'] / $totalNet) * 100);
        $this->report['kiosk_out']['pr_total_net'] = ($this->report['kiosk_out']['total_net'] == 0) ? 0 : (($this->report['kiosk_out']['total_net'] / $totalNet) * 100);
        $this->report['drive']['pr_total_net'] = ($this->report['drive']['total_net'] == 0) ? 0 : (($this->report['drive']['total_net'] / $totalNet) * 100);
        $this->report['delivery']['pr_total_net'] = ($this->report['delivery']['total_net'] == 0) ? 0 : (($this->report['delivery']['total_net'] / $totalNet) * 100);
        $this->report['e_ordering_in']['pr_total_net'] = ($this->report['e_ordering_in']['total_net'] == 0) ? 0 : (($this->report['e_ordering_in']['total_net'] / $totalNet) * 100);
        $this->report['e_ordering_out']['pr_total_net'] = ($this->report['e_ordering_out']['total_net'] == 0) ? 0 : (($this->report['e_ordering_out']['total_net'] / $totalNet) * 100);

        $totalNetHt = $this->report['kiosk_in']['total_net_ht'] + $this->report['kiosk_out']['total_net_ht'] + $this->report['eat_in']['total_net_ht'] + $this->report['take_out']['total_net_ht'] + $this->report['drive']['total_net_ht'] +$this->report['delivery']['total_net_ht'] +$this->report['e_ordering_in']['total_net_ht']   +$this->report['e_ordering_out']['total_net_ht'];

        $this->report['kiosk_in']['pr_total_net_ht'] = ($this->report['kiosk_in']['total_net_ht'] == 0) ? 0 : (($this->report['kiosk_in']['total_net_ht'] / $totalNetHt) * 100);
        $this->report['eat_in']['pr_total_net_ht'] = ($this->report['eat_in']['total_net_ht'] == 0) ? 0 : (($this->report['eat_in']['total_net_ht'] / $totalNetHt) * 100);
        $this->report['take_out']['pr_total_net_ht'] = ($this->report['take_out']['total_net_ht'] == 0) ? 0 : (($this->report['take_out']['total_net_ht'] / $totalNetHt) * 100);
        $this->report['kiosk_out']['pr_total_net_ht'] = ($this->report['kiosk_out']['total_net_ht'] == 0) ? 0 : (($this->report['kiosk_out']['total_net_ht'] / $totalNetHt) * 100);
        $this->report['drive']['pr_total_net_ht'] = ($this->report['drive']['total_net_ht'] == 0) ? 0 : (($this->report['drive']['total_net_ht'] / $totalNetHt) * 100);
        $this->report['delivery']['pr_total_net_ht'] = ($this->report['delivery']['total_net_ht'] == 0) ? 0 : (($this->report['delivery']['total_net_ht'] / $totalNetHt) * 100);
        $this->report['e_ordering_in']['pr_total_net_ht'] = ($this->report['e_ordering_in']['total_net_ht'] == 0) ? 0 : (($this->report['e_ordering_in']['total_net_ht'] / $totalNetHt) * 100);
        $this->report['e_ordering_out']['pr_total_net_ht'] = ($this->report['e_ordering_out']['total_net_ht'] == 0) ? 0 : (($this->report['e_ordering_out']['total_net_ht'] / $totalNetHt) * 100);

        $this->report['kiosk_in']['tm_net'] = ($this->report['kiosk_in']['total_net'] == 0) ? 0 : ($this->report['kiosk_in']['total_net'] / $this->report['kiosk_in']['ticket']);
        $this->report['kiosk_in']['tm_brut'] = ($this->report['kiosk_in']['total_ttc'] == 0) ? 0 : ($this->report['kiosk_in']['total_ttc'] / $this->report['kiosk_in']['ticket']);
        $this->report['kiosk_in']['tm_ht'] = ($this->report['kiosk_in']['total_ht'] == 0) ? 0 : ($this->report['kiosk_in']['total_ht'] / $this->report['kiosk_in']['ticket']);

        $this->report['kiosk_out']['tm_net'] = ($this->report['kiosk_out']['total_net'] == 0) ? 0 : ($this->report['kiosk_out']['total_net'] / $this->report['kiosk_out']['ticket']);
        $this->report['kiosk_out']['tm_brut'] = ($this->report['kiosk_out']['total_ttc'] == 0) ? 0 : ($this->report['kiosk_out']['total_ttc'] / $this->report['kiosk_out']['ticket']);
        $this->report['kiosk_out']['tm_ht'] = ($this->report['kiosk_out']['total_ht'] == 0) ? 0 : ($this->report['kiosk_out']['total_ht'] / $this->report['kiosk_out']['ticket']);

        $this->report['eat_in']['tm_net'] = ($this->report['eat_in']['total_net'] == 0) ? 0 : ($this->report['eat_in']['total_net'] / $this->report['eat_in']['ticket']);
        $this->report['eat_in']['tm_brut'] = ($this->report['eat_in']['total_ttc'] == 0) ? 0 : ($this->report['eat_in']['total_ttc'] / $this->report['eat_in']['ticket']);
        $this->report['eat_in']['tm_ht'] = ($this->report['eat_in']['total_ht'] == 0) ? 0 : ($this->report['eat_in']['total_ht'] / $this->report['eat_in']['ticket']);

        $this->report['take_out']['tm_net'] = ($this->report['take_out']['total_net'] == 0) ? 0 : ($this->report['take_out']['total_net'] / $this->report['take_out']['ticket']);
        $this->report['take_out']['tm_brut'] = ($this->report['take_out']['total_ttc'] == 0) ? 0 : ($this->report['take_out']['total_ttc'] / $this->report['take_out']['ticket']);
        $this->report['take_out']['tm_ht'] = ($this->report['take_out']['total_ht'] == 0) ? 0 : ($this->report['take_out']['total_ht'] / $this->report['take_out']['ticket']);

        $this->report['drive']['tm_net'] = ($this->report['drive']['total_net'] == 0) ? 0 : ($this->report['drive']['total_net'] / $this->report['drive']['ticket']);
        $this->report['drive']['tm_brut'] = ($this->report['drive']['total_ttc'] == 0) ? 0 : ($this->report['drive']['total_ttc'] / $this->report['drive']['ticket']);
        $this->report['drive']['tm_ht'] = ($this->report['drive']['total_ht'] == 0) ? 0 : ($this->report['drive']['total_ht'] / $this->report['drive']['ticket']);

        $this->report['delivery']['tm_net'] = ($this->report['delivery']['total_net'] == 0) ? 0 : ($this->report['delivery']['total_net'] / $this->report['delivery']['ticket']);
        $this->report['delivery']['tm_brut'] = ($this->report['delivery']['total_ttc'] == 0) ? 0 : ($this->report['delivery']['total_ttc'] / $this->report['delivery']['ticket']);
        $this->report['delivery']['tm_ht'] = ($this->report['delivery']['total_ht'] == 0) ? 0 : ($this->report['delivery']['total_ht'] / $this->report['delivery']['ticket']);

        $this->report['e_ordering_in']['tm_net'] = ($this->report['e_ordering_in']['total_net'] == 0) ? 0 : ($this->report['e_ordering_in']['total_net'] / $this->report['e_ordering_in']['ticket']);
        $this->report['e_ordering_in']['tm_brut'] = ($this->report['e_ordering_in']['total_ttc'] == 0) ? 0 : ($this->report['e_ordering_in']['total_ttc'] / $this->report['e_ordering_in']['ticket']);
        $this->report['e_ordering_in']['tm_ht'] = ($this->report['e_ordering_in']['total_ht'] == 0) ? 0 : ($this->report['e_ordering_in']['total_ht'] / $this->report['e_ordering_in']['ticket']);

        $this->report['e_ordering_out']['tm_net'] = ($this->report['e_ordering_out']['total_net'] == 0) ? 0 : ($this->report['e_ordering_out']['total_net'] / $this->report['e_ordering_out']['ticket']);
        $this->report['e_ordering_out']['tm_brut'] = ($this->report['e_ordering_out']['total_ttc'] == 0) ? 0 : ($this->report['e_ordering_out']['total_ttc'] / $this->report['e_ordering_out']['ticket']);
        $this->report['e_ordering_out']['tm_ht'] = ($this->report['e_ordering_out']['total_ht'] == 0) ? 0 : ($this->report['e_ordering_out']['total_ht'] / $this->report['e_ordering_out']['ticket']);

        $totalTicket = $this->report['take_out']['ticket'] + $this->report['eat_in']['ticket'] + $this->report['kiosk_out']['ticket'] + $this->report['kiosk_in']['ticket'] + $this->report['drive']['ticket'] +$this->report['delivery']['ticket'] +$this->report['e_ordering_in']['ticket'] +$this->report['e_ordering_out']['ticket'];

        $this->report['take_out']['pr_ticket'] = (!$this->report['take_out']['ticket']) ? 0 : (($this->report['take_out']['ticket'] / $totalTicket) * 100);
        $this->report['eat_in']['pr_ticket'] = (!$this->report['eat_in']['ticket']) ? 0 : (($this->report['eat_in']['ticket'] / $totalTicket) * 100);
        $this->report['kiosk_out']['pr_ticket'] = (!$this->report['kiosk_out']['ticket']) ? 0 : (($this->report['kiosk_out']['ticket'] / $totalTicket) * 100);
        $this->report['kiosk_in']['pr_ticket'] = (!$this->report['kiosk_in']['ticket']) ? 0 : (($this->report['kiosk_in']['ticket'] / $totalTicket) * 100);
        $this->report['drive']['pr_ticket'] = (!$this->report['drive']['ticket']) ? 0 : (($this->report['drive']['ticket'] / $totalTicket) * 100);
        $this->report['delivery']['pr_ticket'] = (!$this->report['delivery']['ticket']) ? 0 : (($this->report['delivery']['ticket'] / $totalTicket) * 100);
        $this->report['e_ordering_in']['pr_ticket'] = (!$this->report['e_ordering_in']['ticket']) ? 0 : (($this->report['e_ordering_in']['ticket'] / $totalTicket) * 100);
        $this->report['e_ordering_out']['pr_ticket'] = (!$this->report['e_ordering_out']['ticket']) ? 0 : (($this->report['e_ordering_out']['ticket'] / $totalTicket) * 100);

        $output['report'] = $this->report;

        $critertia=array('from'=>$filter['startDate']->format('d-m-Y'),'to'=>$filter['endDate']->format('d-m-Y'));

        $output['startDate'] = $start;
        $output['endDate'] = $end;
        $output['startHour'] = $filter['startHour'];
        $output['endHour'] = $filter['endHour'];

        if ($output['type'] == 0) {
            //par heure
            //CA PREV
            $caPrev = $this->reportSaleService->getCaPrevPerHour($critertia, $filter['currentRestaurant']);

            foreach ($this->reportTwo as $hour => $report) {
                $this->reportTwo[$hour]['ca_prev'] += $caPrev[$hour];
                $this->reportTwo[$hour]['total']['ca_net_ht'] = $this->reportTwo[$hour]['total']['ca_net_ht'] - $this->reportTwo[$hour]['total']['br_ht'] ;
                $this->reportTwo[$hour]['total']['ca_net_ttc'] = $this->reportTwo[$hour]['total']['ca_brut_ttc'] - $this->reportTwo[$hour]['total']['br'] - abs($this->reportTwo[$hour]['total']['discount']);
            }

        } else {

            foreach ($this->reportTwo as $date => $report) {
                $dateCA=new \DateTime($date);
                $caPrev = $this->em->getRepository(CaPrev::class)->findOneBy(array('date' => $dateCA, 'originRestaurant' => $filter['currentRestaurant']));
                $this->reportTwo[$date]['ca_prev'] = $caPrev->getCa();
                $this->reportTwo[$date]['total']['ca_net_ht'] = $this->reportTwo[$date]['total']['ca_net_ht'] - $this->reportTwo[$date]['total']['br_ht'] ;
                $this->reportTwo[$date]['total']['ca_net_ttc'] = $this->reportTwo[$date]['total']['ca_brut_ttc'] - $this->reportTwo[$date]['total']['br'] - abs($this->reportTwo[$date]['total']['discount']);
            }
        }
        $output['report_two'] = $this->reportTwo;


        // dump("End Memory Usage: " . (memory_get_usage()/1048576) . " MB \n");
        unset($this->report);
        unset($this->reportTwo);

        return $output;

    }

    /**
     * @param $results
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    private function calculateReport($results)
    {

        foreach ($results as $result) {
            //---------------------------------------//
            if (isset($this->reportTwo[$result['ticket_time']])) {

                /*if(empty($result['parentline']) || 0==$result['parentline']) {
                    $this->reportTwo[$result['ticket_time']]['total']['ca_brut_ttc'] += $result['total_ttc'] + $result['discount'];
                }*/
                $this->reportTwo[$result['ticket_time']]['total']['ca_brut_ttc'] += $result['total_ttc'];
                $this->reportTwo[$result['ticket_time']]['total']['ca_net_ht'] += $result['total_ht'] ;

                if ($result['id_payment'] == TicketPayment::MEAL_TICKET ) {
                    $this->reportTwo[$result['ticket_time']]['total']['br'] += $result['total_ttc'];
                    $this->reportTwo[$result['ticket_time']]['total']['br_ht']+=$result['total_ht'];
                }
                $this->reportTwo[$result['ticket_time']]['total']['discount'] += $result['discount'];

                $this->reportTwo[$result['ticket_time']]['total']['va'] = 0;
                $this->reportTwo[$result['ticket_time']]['total']['total_tva']  = 0;
                if($result['discount_id'] != '10'){
                    $this->reportTwo[$result['ticket_time']]['total']['total_tva'] += $result['total_tva'];
                }

                /*if ($result['br_ht'] > 0 && !in_array($result['ticket_id'], $this->br_tickets1)) {
                    $this->br_tickets1[]=$result['ticket_id'];
                    $this->reportTwo[$result['ticket_time']]['total']['br_ht']+=$result['br_ht'];
                }*/

            } else {
                // init new value
                $this->reportTwo[$result['ticket_time']]['total']['ca_brut_ttc']=0;
                $this->reportTwo[$result['ticket_time']]['total']['br']=0;
                $this->reportTwo[$result['ticket_time']]['total']['br_ht']=0;
                $this->reportTwo[$result['ticket_time']]['total']['ca_net_ttc']=0;
                $this->reportTwo[$result['ticket_time']]['total']['ca_net_ht']=0 ;
                $this->reportTwo[$result['ticket_time']]['ca_prev']=0;

                $this->reportTwo[$result['ticket_time']]['total']['ca_brut_ttc'] +=$result['total_ttc'];
                $this->reportTwo[$result['ticket_time']]['total']['ca_net_ht']+=$result['total_ht'];

                if ($result['id_payment'] == TicketPayment::MEAL_TICKET) {
                    $this->reportTwo[$result['ticket_time']]['total']['br'] += $result['total_ttc'];
                    $this->reportTwo[$result['ticket_time']]['total']['br_ht'] += $result['total_ht'];
                }

                $this->reportTwo[$result['ticket_time']]['total']['discount'] = $result['discount'];

                $this->reportTwo[$result['ticket_time']]['total']['va'] = 0;
                if($result['discount_id'] != '10') {
                    $this->reportTwo[$result['ticket_time']]['total']['total_tva'] = $result['total_tva'];
                }

            }

            $caBrutTTC=0;
            //////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //////////////////////////////////////////////////////////////////////////////////////////////////////////////
            ////////////////////////////////////take out
            if (
                (strtolower($result['origin']) == strtolower("POS") && strtolower($result['destination']) == strtolower(
                        "TakeOut"
                    )) ||
                (strtolower($result['origin']) == strtolower("NULL") && strtolower(
                        $result['destination']
                    ) == strtolower("TAKE OUT")) ||
                (strtolower($result['origin']) == strtolower("") && strtolower($result['destination']) == strtolower(
                        "TAKE OUT"
                    ))
            ) {

                $this->report['take_out']['total_ttc'] += $result['total_ttc'];
                $this->report['take_out']['discount'] += $result['discount'];
                $this->report['take_out']['discount_ht'] += $result['discount_ht'];
                if($result['discount_id'] != '10') {
                    $this->report['take_out']['total_tva'] += $result['total_tva'];
                }
                $br_ht=0;

                if ($result['id_payment'] == TicketPayment::MEAL_TICKET) {
                    $this->report['take_out']['br'] += $result['total_ttc'];
                    $this->report['take_out']['br_ht'] += $result['total_ht'];
                    $br_ht=$result['total_ht'];
                }
                $this->report['take_out']['total_ht'] += $result['total_ht'];

                if (isset($this->reportTwo[$result['ticket_time']]['take_out'])) {
                    $this->reportTwo[$result['ticket_time']]['take_out']['ca_brut_ttc'] += $result['total_ttc'];
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['take_out']['total_tva'] += $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['take_out']['ht'] += $result['total_ht'] - $br_ht;

                } else {
                    $this->reportTwo[$result['ticket_time']]['take_out']['ca_brut_ttc'] = $result['total_ttc'];
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['take_out']['total_tva'] = $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['take_out']['ht'] = $result['total_ht'] - $br_ht;
                }

                //////////////////////////////////////////Kiosk OUT
            } elseif (strtolower($result['origin']) == strtolower("KIOSK") && strtolower(
                    $result['destination']
                ) == strtolower("TakeOut")) {

                $this->report['kiosk_out']['total_ttc'] += $result['total_ttc'];

                $this->report['kiosk_out']['discount'] += $result['discount'];
                $this->report['kiosk_out']['discount_ht'] += $result['discount_ht'];
                if($result['discount_id'] != '10') {
                    $this->report['kiosk_out']['total_tva'] += $result['total_tva'];
                }
                $br_ht=0;

                if ($result['id_payment'] == TicketPayment::MEAL_TICKET) {
                    $this->report['kiosk_out']['br'] += $result['total_ttc'];
                    $this->report['kiosk_out']['br_ht'] += $result['total_ht'];
                    $br_ht=$result['total_ht'];
                }
                $this->report['kiosk_out']['total_ht'] += $result['total_ht'];

                if (isset($this->reportTwo[$result['ticket_time']]['kiosk_out'])) {
                    $this->reportTwo[$result['ticket_time']]['kiosk_out']['ca_brut_ttc'] += $result['total_ttc'];
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['kiosk_out']['total_tva'] += $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['kiosk_out']['ht'] += $result['total_ht'] - $br_ht;

                } else {
                    $this->reportTwo[$result['ticket_time']]['kiosk_out']['ca_brut_ttc'] = $result['total_ttc'];
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['kiosk_out']['total_tva'] = $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['kiosk_out']['ht'] = $result['total_ht'] - $br_ht;

                }

                //////////////////////////////////////////Kiosk IN
            } elseif (strtolower($result['origin']) == strtolower("KIOSK") && strtolower(
                    $result['destination']
                ) == strtolower("EatIn")) {

                $this->report['kiosk_in']['total_ttc'] += $result['total_ttc'];

                $this->report['kiosk_in']['discount'] += $result['discount'];
                $this->report['kiosk_in']['discount_ht'] += $result['discount_ht'];
                if($result['discount_id'] != '10') {
                    $this->report['kiosk_in']['total_tva'] += $result['total_tva'];
                }
                $br_ht=0;

                if ($result['id_payment'] == TicketPayment::MEAL_TICKET) {
                    $this->report['kiosk_in']['br'] += $result['total_ttc'];
                    $this->report['kiosk_in']['br_ht'] += $result['total_ht'];
                    $br_ht=$result['total_ht'];
                }
                $this->report['kiosk_in']['total_ht'] += $result['total_ht'];

                if (isset($this->reportTwo[$result['ticket_time']]['kiosk_in'])) {
                    $this->reportTwo[$result['ticket_time']]['kiosk_in']['ca_brut_ttc'] += $result['total_ttc'];
                    //TODO
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['kiosk_in']['total_tva'] += $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['kiosk_in']['ht'] += $result['total_ht'] - $br_ht;

                } else {
                    $this->reportTwo[$result['ticket_time']]['kiosk_in']['ca_brut_ttc'] = $result['total_ttc'];
                    //TODO
                    $this->reportTwo[$result['ticket_time']]['kiosk_in']['va'] = 0;
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['kiosk_in']['total_tva'] = $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['kiosk_in']['ht'] = $result['total_ht'] - $br_ht;
                }

                ///////////////////////////////////////drive
            } elseif ((strtolower($result['origin']) == strtolower("DriveThru") && strtolower(
                        $result['destination']
                    ) == strtolower("DriveThru")) ||
                (strtolower($result['origin']) == strtolower("NULL") && strtolower(
                        $result['destination']
                    ) == strtolower("DRIVE")) ||
                (strtolower($result['origin']) == strtolower("") && strtolower($result['destination']) == strtolower(
                        "DRIVE"
                    )) ||
                (strtolower($result['origin']) == strtolower("MyQuick") && strtolower($result['destination']) == strtolower("MQDrive" )) ||
                (strtolower($result['origin']) == strtolower("MyQuick") && strtolower($result['destination']) == strtolower("MQCurbside" ))
            ) {

                $this->report['drive']['total_ttc'] += $result['total_ttc'];
                $this->report['drive']['discount'] += $result['discount'];
                $this->report['drive']['discount_ht'] += $result['discount_ht'];
                if($result['discount_id'] != '10') {
                    $this->report['drive']['total_tva'] += $result['total_tva'];
                }
                $br_ht=0;

                if ($result['id_payment'] == TicketPayment::MEAL_TICKET) {
                    $this->report['drive']['br'] += $result['total_ttc'];
                    $this->report['drive']['br_ht'] += $result['total_ht'];
                    $br_ht=$result['total_ht'];
                }
                $this->report['drive']['total_ht'] += $result['total_ht'];

                if (isset($this->reportTwo[$result['ticket_time']]['drive'])) {
                    $this->reportTwo[$result['ticket_time']]['drive']['ca_brut_ttc'] += $result['total_ttc'];
                    //TODO
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['drive']['total_tva'] += $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['drive']['ht'] += $result['total_ht'] - $br_ht;

                } else {
                    $this->reportTwo[$result['ticket_time']]['drive']['ca_brut_ttc'] = $result['total_ttc'];
                    //TODO
                    $this->reportTwo[$result['ticket_time']]['drive']['va'] = 0;
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['drive']['total_tva'] = $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['drive']['ht'] = $result['total_ht'] - $br_ht;
                }
                /////////////////// Delivery
            } elseif ((strtolower($result['origin']) == strtolower("POS") && strtolower($result['destination']) == strtolower("Delivery")) ||
                (strtolower($result['origin']) == strtolower("MyQuick") && strtolower($result['destination']) == strtolower("ATOUberEats")) ||
                (strtolower($result['origin']) == strtolower("MyQuick") && strtolower($result['destination']) == strtolower("ATODeliveroo")) ||
                (strtolower($result['origin']) == strtolower("MyQuick") && strtolower($result['destination']) == strtolower("ATOTakeAway")) ||
                (strtolower($result['origin']) == strtolower("MyQuick") && strtolower($result['destination']) == strtolower("ATOHelloUgo")) ||
                (strtolower($result['origin']) == strtolower("MyQuick") && strtolower($result['destination']) == strtolower("ATOEasy2Eat")) ||
                (strtolower($result['origin']) == strtolower("MyQuick") && strtolower($result['destination']) == strtolower("ATOGoosty"))   ||
                (strtolower($result['origin']) == strtolower("MyQuick") && strtolower($result['destination']) == strtolower("ATOWolt")))
               {

                $this->report['delivery']['total_ttc'] += $result['total_ttc'];

                $this->report['delivery']['discount'] += $result['discount'];
                $this->report['delivery']['discount_ht'] += $result['discount_ht'];
                if($result['discount_id'] != '10') {
                    $this->report['delivery']['total_tva'] += $result['total_tva'];
                }
                $br_ht=0;

                if ($result['id_payment'] == TicketPayment::MEAL_TICKET) {
                    $this->report['delivery']['br'] += $result['total_ttc'];
                    $this->report['delivery']['br_ht'] += $result['total_ht'];
                    $br_ht=$result['total_ht'];
                }
                $this->report['delivery']['total_ht'] += $result['total_ht'];

                if (isset($this->reportTwo[$result['ticket_time']]['delivery'])) {
                    $this->reportTwo[$result['ticket_time']]['delivery']['ca_brut_ttc'] += $result['total_ttc'];
                    //TODO
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['delivery']['total_tva'] += $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['delivery']['ht'] += $result['total_ht'] - $br_ht;

                } else {
                    $this->reportTwo[$result['ticket_time']]['delivery']['ca_brut_ttc'] = $result['total_ttc'];
                    //TODO
                    $this->reportTwo[$result['ticket_time']]['delivery']['va'] = 0;
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['delivery']['total_tva'] = $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['delivery']['ht'] = $result['total_ht'] - $br_ht;
                }
            }
            elseif ((strtolower($result['origin']) == strtolower("MyQuick") && strtolower($result['destination']) == strtolower("MyQuickEatIn")) )
            {

                $this->report['e_ordering_in']['total_ttc'] += $result['total_ttc'];

                $this->report['e_ordering_in']['discount'] += $result['discount'];
                $this->report['e_ordering_in']['discount_ht'] += $result['discount_ht'];
                if($result['discount_id'] != '10') {
                    $this->report['e_ordering_in']['total_tva'] += $result['total_tva'];
                }
                $br_ht=0;

                if ($result['id_payment'] == TicketPayment::MEAL_TICKET) {
                    $this->report['e_ordering_in']['br'] += $result['total_ttc'];
                    $this->report['e_ordering_in']['br_ht'] += $result['total_ht'];
                    $br_ht=$result['total_ht'];
                }
                $this->report['e_ordering_in']['total_ht'] += $result['total_ht'];

                if (isset($this->reportTwo[$result['ticket_time']]['e_ordering_in'])) {
                    $this->reportTwo[$result['ticket_time']]['e_ordering_in']['ca_brut_ttc'] += $result['total_ttc'];
                    //TODO
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['e_ordering_in']['total_tva'] += $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['e_ordering_in']['ht'] += $result['total_ht'] - $br_ht;

                } else {
                    $this->reportTwo[$result['ticket_time']]['e_ordering_in']['ca_brut_ttc'] = $result['total_ttc'];
                    //TODO
                    $this->reportTwo[$result['ticket_time']]['e_ordering_in']['va'] = 0;
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['e_ordering_in']['total_tva'] = $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['e_ordering_in']['ht'] = $result['total_ht'] - $br_ht;
                }
            }
            elseif ((strtolower($result['origin']) == strtolower("MyQuick") && strtolower($result['destination']) == strtolower("MyQuickTakeout")) )
            {

                $this->report['e_ordering_out']['total_ttc'] += $result['total_ttc'];

                $this->report['e_ordering_out']['discount'] += $result['discount'];
                $this->report['e_ordering_out']['discount_ht'] += $result['discount_ht'];
                if($result['discount_id'] != '10') {
                    $this->report['e_ordering_out']['total_tva'] += $result['total_tva'];
                }
                $br_ht=0;

                if ($result['id_payment'] == TicketPayment::MEAL_TICKET) {
                    $this->report['e_ordering_out']['br'] += $result['total_ttc'];
                    $this->report['e_ordering_out']['br_ht'] += $result['total_ht'];
                    $br_ht=$result['total_ht'];
                }
                $this->report['e_ordering_out']['total_ht'] += $result['total_ht'];

                if (isset($this->reportTwo[$result['ticket_time']]['e_ordering_out'])) {
                    $this->reportTwo[$result['ticket_time']]['e_ordering_out']['ca_brut_ttc'] += $result['total_ttc'];
                    //TODO
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['e_ordering_out']['total_tva'] += $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['e_ordering_out']['ht'] += $result['total_ht'] - $br_ht;

                } else {
                    $this->reportTwo[$result['ticket_time']]['e_ordering_out']['ca_brut_ttc'] = $result['total_ttc'];
                    //TODO
                    $this->reportTwo[$result['ticket_time']]['e_ordering_out']['va'] = 0;
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['e_ordering_out']['total_tva'] = $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['e_ordering_out']['ht'] = $result['total_ht'] - $br_ht;
                }
            }
            ///////////////////////////////////default is EatIn
            else {

                $this->report['eat_in']['total_ttc'] += $result['total_ttc'];
                $this->report['eat_in']['discount_ht'] += $result['discount_ht'];
                $this->report['eat_in']['discount'] += $result['discount'];
                if($result['discount_id'] != '10') {
                    $this->report['eat_in']['total_tva'] += $result['total_tva'];
                }
                $br_ht=0;

                if ($result['id_payment'] == TicketPayment::MEAL_TICKET) {
                    $this->report['eat_in']['br'] += $result['total_ttc'];
                    $this->report['eat_in']['br_ht'] += $result['total_ht'];
                    $br_ht=$result['total_ht'];
                }
                $this->report['eat_in']['total_ht'] += $result['total_ht'];

                if (isset($this->reportTwo[$result['ticket_time']]['eat_in'])) {
                    $this->reportTwo[$result['ticket_time']]['eat_in']['ca_brut_ttc']+=$result['total_ttc'];

                    if (!isset($this->reportTwo[$result['ticket_time']]['eat_in']['total_tva'])) {
                        $this->reportTwo[$result['ticket_time']]['eat_in']['total_tva'] = 0;
                    }

                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['eat_in']['total_tva'] += $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['eat_in']['ht'] += $result['total_ht'] - $br_ht;

                } else {
                    $this->reportTwo[$result['ticket_time']]['eat_in']['ca_brut_ttc'] =$result['total_ttc'];
                    if($result['discount_id'] != '10') {
                        $this->reportTwo[$result['ticket_time']]['eat_in']['total_tva'] = $result['total_tva'];
                    }
                    $this->reportTwo[$result['ticket_time']]['eat_in']['ht'] = $result['total_ht'] - $br_ht;
                }
            }

        }

    }

    public function generateExcelFile($result, $filter, $logoPath)
    {
        $currentRestaurant=$filter['currentRestaurant'];
        $colorOne = "ECECEC";
        $colorTwo = "E5CFAB";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        //PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('items_per_canals.title'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('items_per_canals.title');
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
        $sheet->mergeCells("A10:D10");
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
        $sheet->setCellValue('C11', $result['startDate']);    // START DATE



        // END DATE
        $sheet->mergeCells("A12:B12");
        ExcelUtilities::setFont($sheet->getCell('A12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A12"), $colorOne);
        $sheet->setCellValue('A12', $this->translator->trans('keyword.to').":");
        $sheet->mergeCells("C12:D12");
        ExcelUtilities::setFont($sheet->getCell('C12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C12"), $colorOne);
        $sheet->setCellValue('C12', $result['endDate']);

        if(isset($result['startHour']) || isset($result['endHour'])){
            //comparabilitÃƒÂ©
            $sheet->mergeCells("F10:I10");
            ExcelUtilities::setFont($sheet->getCell('F10'), 11, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("F10"), $colorTwo);
            $sheet->setCellValue('F10', $this->translator->trans('report.period_time').":");
            ExcelUtilities::setCellAlignment($sheet->getCell("F10"), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell("F10"), $alignmentV);

            // START HOUR
            $sheet->mergeCells("F11:G11");
            ExcelUtilities::setFont($sheet->getCell('F11'), 11, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("F11"), $colorOne);
            $sheet->setCellValue('F11', $this->translator->trans('keyword.from').":");
            $sheet->mergeCells("H11:I11");
            ExcelUtilities::setFont($sheet->getCell('H11'), 11, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("H11"), $colorOne);
            $sheet->setCellValue('H11', $result['startHour'] ? $result['startHour'].":00" : "--");

            // END HOUR
            $sheet->mergeCells("F12:G12");
            ExcelUtilities::setFont($sheet->getCell('F12'), 11, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("F12"), $colorOne);
            $sheet->setCellValue('F12', $this->translator->trans('keyword.to').":");
            $sheet->mergeCells("H12:I12");
            ExcelUtilities::setFont($sheet->getCell('H12'), 11, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("H12"), $colorOne);
            $sheet->setCellValue('H12', $result['endHour'] ? $result['endHour'].":00" : "--");
        }


        $i = 15;
        //Centre de Revenu
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), "ffa500");
        $sheet->setCellValue('A'.$i, $this->translator->trans('items_per_canals.revenue_center'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        //CA Brut TTC
        ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B".$i), "ffa500");
        $sheet->setCellValue('B'.$i, $this->translator->trans('items_per_canals.brut_ttc'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('B'.$i), $alignmentH);
        //Pourcentage
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), "00bcd4");
        $sheet->setCellValue('C'.$i, $this->translator->trans('items_per_canals.percentage'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('C'.$i), $alignmentH);
        //Bon de repas
        ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D".$i), "ffa500");
        $sheet->setCellValue('D'.$i, $this->translator->trans('items_per_canals.br'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('D'.$i), $alignmentH);
        //RÃƒÂ©duction
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), "ffa500");
        $sheet->setCellValue('E'.$i, $this->translator->trans('report.discount'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('E'.$i), $alignmentH);
        //Net TTC
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), "ffa500");
        $sheet->setCellValue('F'.$i, $this->translator->trans('items_per_canals.net_ttc'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('F'.$i), $alignmentH);
        //Pourcentage
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), "00bcd4");
        $sheet->setCellValue('G'.$i, $this->translator->trans('items_per_canals.percentage'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('G'.$i), $alignmentH);
        //Taxes
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), "ffa500");
        $sheet->setCellValue('H'.$i, $this->translator->trans('items_per_canals.tax'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('H'.$i), $alignmentH);
        //NET HTVA
        ExcelUtilities::setFont($sheet->getCell('I'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), "ffa500");
        $sheet->setCellValue('I'.$i, $this->translator->trans('items_per_canals.net_ht'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('I'.$i), $alignmentH);
        //Pourcentage
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), "00bcd4");
        $sheet->setCellValue('J'.$i, $this->translator->trans('items_per_canals.percentage'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('J'.$i), $alignmentH);
        //Tickets
        ExcelUtilities::setFont($sheet->getCell('K'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K".$i), "ffa500");
        $sheet->setCellValue('K'.$i, $this->translator->trans('items_per_canals.tickets'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('K'.$i), $alignmentH);
        //Pourcentage
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), "00bcd4");
        $sheet->setCellValue('L'.$i, $this->translator->trans('items_per_canals.percentage'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('L'.$i), $alignmentH);
        //TM Brut
        ExcelUtilities::setFont($sheet->getCell('M'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M".$i), "ffa500");
        $sheet->setCellValue('M'.$i, $this->translator->trans('items_per_canals.tm_brut'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('M'.$i), $alignmentH);
        //TM NET
        ExcelUtilities::setFont($sheet->getCell('N'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N".$i), "ffa500");
        $sheet->setCellValue('N'.$i, $this->translator->trans('items_per_canals.tm_net'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('N')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('N'.$i), $alignmentH);
        //TM HT
        ExcelUtilities::setFont($sheet->getCell('O'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O".$i), "ffa500");
        $sheet->setCellValue('O'.$i, $this->translator->trans('items_per_canals.tm_ht'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('O')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('O'.$i), $alignmentH);
        //ANNULATIONS
        ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P".$i), "ffa500");
        $sheet->setCellValue('P'.$i, $this->translator->trans('cashbox_counts_anomalies.report_labels.annulations'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('P')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('P'.$i), $alignmentH);
        //CORRECTIONS
        ExcelUtilities::setFont($sheet->getCell('Q'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q".$i), "ffa500");
        $sheet->setCellValue('Q'.$i, $this->translator->trans('cashbox_counts_anomalies.report_labels.corrections'));
        $phpExcelObject->getActiveSheet()->getColumnDimension('Q')->setAutoSize(true);
        ExcelUtilities::setCellAlignment($sheet->getCell('Q'.$i), $alignmentH);

        //Border
        $cell = 'A';
        while ($cell != 'R') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }

        $total_ttc=0;
        $pr_total_ttc=0;
        $total_br=0;
        $total_discount=0;
        $total_net=0;
        $pr_total_net=0;
        $pr_total_net_ht=0;
        $total_pr_ticket=0;
        $total_taxes=0;
        $total_net_ht=0;
        $total_ticket=0;
        $total_tm_brut=0;
        $total_tm_net=0;
        $total_tm_ht=0;
        $total_annulations=0;
        $total_corrections=0;

        //Content
        $i = 15;
        foreach ($result['report'] as $key => $line) {

            $sheet->setCellValue('A'.$i, $this->translator->trans('items_per_canals.'.$key));
            ExcelUtilities::setCellAlignment($sheet->getCell('A'.$i), $alignmentH);
            ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), "ffff00");

            $sheet->setCellValue('B'.$i,number_format($line['total_ttc'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('B'.$i), $alignmentV);
            $sheet->getStyle('B'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

            $sheet->setCellValue('C'.$i,number_format($line['pr_total_ttc'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('C'.$i), $alignmentV);

            $sheet->setCellValue('D'.$i,number_format($line['br'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('D'.$i), $alignmentV);
            $sheet->getStyle('D'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

            $sheet->setCellValue('E'.$i,number_format(abs($line['discount']), 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('E'.$i), $alignmentV);
            $sheet->getStyle('E'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

            $sheet->setCellValue('F'.$i,number_format($line['total_net'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('F'.$i), $alignmentV);
            $sheet->getStyle('F'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

            $sheet->setCellValue('G'.$i,number_format($line['pr_total_net'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('G'.$i), $alignmentV);
            $sheet->getStyle('G'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

            $sheet->setCellValue('H'.$i,number_format($line['total_tva'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('H'.$i), $alignmentV);
            $sheet->getStyle('H'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

            $sheet->setCellValue('I'.$i,number_format($line['total_net_ht'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('I'.$i), $alignmentV);
            $sheet->getStyle('I'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

            $sheet->setCellValue('J'.$i,number_format($line['pr_total_net_ht'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('J'.$i), $alignmentV);
            $sheet->getStyle('J'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

            $sheet->setCellValue('K'.$i,$line['ticket']);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('K'.$i), $alignmentV);
            $sheet->getStyle('K'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER);

            $sheet->setCellValue('L'.$i,number_format($line['pr_ticket'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('L'.$i), $alignmentV);
            $sheet->getStyle('L'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

            $sheet->setCellValue('M'.$i,number_format($line['tm_brut'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('N'.$i), $alignmentV);
            $sheet->getStyle('M'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

            $sheet->setCellValue('N'.$i,number_format($line['tm_net'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('O'.$i), $alignmentV);
            $sheet->getStyle('N'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

            $sheet->setCellValue('O'.$i,number_format($line['tm_ht'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('P'.$i), $alignmentV);
            $sheet->getStyle('O'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

            $sheet->setCellValue('P'.$i,number_format($line['annulations'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('Q'.$i), $alignmentV);
            $sheet->getStyle('P'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

            $sheet->setCellValue('Q'.$i,number_format($line['corrections'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('R'.$i), $alignmentV);
            $sheet->getStyle('Q'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);

            $total_ttc+=$line['total_ttc'];
            $pr_total_ttc+=$line['pr_total_ttc'];
            $total_br+=$line['br'];
            $total_discount+=abs($line['discount']);
            $total_net+=$line['total_net'];
            $pr_total_net+=$line['pr_total_net'];
            $pr_total_net_ht+=$line['pr_total_net_ht'];
            $total_pr_ticket+=$line['pr_ticket'];
            $total_taxes+=$line['total_tva'];
            $total_net_ht+=$line['total_net_ht'];
            $total_ticket+=$line['ticket'];
            $total_annulations+=$line['annulations'];
            $total_corrections+=$line['corrections'];


            $i++;
        }

        if($total_ticket > 0 ){
            $total_tm_brut = $total_ttc / $total_ticket;
            $total_tm_net  = $total_net / $total_ticket;
            $total_tm_ht   = $total_net_ht / $total_ticket;
        }

        $sheet->setCellValue('A'.$i, $this->translator->trans('items_per_canals.total'));
        ExcelUtilities::setCellAlignment($sheet->getCell('A'.$i), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('A'.$i), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), "d2d2d2");

        $sheet->setCellValue('B'.$i,number_format($total_ttc, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('B'.$i), $alignmentV);
        $sheet->getStyle('B'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B".$i), "d2d2d2");

        $sheet->setCellValue('C'.$i,number_format($pr_total_ttc, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('C'.$i), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), "d2d2d2");

        $sheet->setCellValue('D'.$i,number_format($total_br, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('D'.$i), $alignmentV);
        $sheet->getStyle('D'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D".$i), "d2d2d2");

        $sheet->setCellValue('E'.$i,number_format($total_discount, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('E'.$i), $alignmentV);
        $sheet->getStyle('E'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), "d2d2d2");

        $sheet->setCellValue('F'.$i,number_format($total_net, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('F'.$i), $alignmentV);
        $sheet->getStyle('F'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), "d2d2d2");

        $sheet->setCellValue('G'.$i,number_format($pr_total_net, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('G'.$i), $alignmentV);
        $sheet->getStyle('G'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), "d2d2d2");

        $sheet->setCellValue('H'.$i,number_format($total_taxes, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('H'.$i), $alignmentV);
        $sheet->getStyle('H'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), "d2d2d2");

        $sheet->setCellValue('I'.$i,number_format($total_net_ht, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('I'.$i), $alignmentV);
        $sheet->getStyle('I'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), "d2d2d2");

        $sheet->setCellValue('J'.$i,number_format($pr_total_net_ht, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('J'.$i), $alignmentV);
        $sheet->getStyle('J'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), "d2d2d2");

        $sheet->setCellValue('K'.$i,$total_ticket);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('K'.$i), $alignmentV);
        $sheet->getStyle('K'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K".$i), "d2d2d2");

        $sheet->setCellValue('L'.$i,number_format($total_pr_ticket, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('L'.$i), $alignmentV);
        $sheet->getStyle('L'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), "d2d2d2");

        $sheet->setCellValue('M'.$i,number_format($total_tm_brut, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('N'.$i), $alignmentV);
        $sheet->getStyle('M'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M".$i), "d2d2d2");

        $sheet->setCellValue('N'.$i,number_format($total_tm_net, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('O'.$i), $alignmentV);
        $sheet->getStyle('N'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N".$i), "d2d2d2");

        $sheet->setCellValue('O'.$i,number_format($total_tm_ht, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('P'.$i), $alignmentV);
        $sheet->getStyle('O'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O".$i), "d2d2d2");

        $sheet->setCellValue('P'.$i,number_format($total_annulations, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('Q'.$i), $alignmentV);
        $sheet->getStyle('P'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P".$i), "d2d2d2");

        $sheet->setCellValue('Q'.$i,number_format($total_corrections, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('R'.$i), $alignmentV);
        $sheet->getStyle('Q'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q".$i), "d2d2d2");


        // Report two start ///////////////////////////
        ////////////////////////////////////////////////////////////////////////////////

        $sheet->mergeCells("A24:D24");
        ExcelUtilities::setFont($sheet->getCell('A24'), 16, true);
        ExcelUtilities::setCellAlignment($sheet->getCell("A24"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A24"), $alignmentV);
        if($result['type']==0){
            $sheet->setCellValue('A24', $this->translator->trans('items_per_canals.details_per_hour'));
        }else{
            $sheet->setCellValue('A24', $this->translator->trans('items_per_canals.details_per_day'));
        }

        $i=26;
        $sheet->mergeCells("B26:H26");
        $sheet->setCellValue('B'.$i, $this->translator->trans('items_per_canals.total'));
        ExcelUtilities::setCellAlignment($sheet->getCell('B'.$i), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('B'.$i), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B".$i), "00FF00");
        $phpExcelObject->getActiveSheet()->getStyle('B26:H26')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        $sheet->mergeCells("I26:K26");
        $sheet->setCellValue('I'.$i, $this->translator->trans('items_per_canals.kiosk_out'));
        ExcelUtilities::setCellAlignment($sheet->getCell('I'.$i), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('I'.$i), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), "ffff00");
        $phpExcelObject->getActiveSheet()->getStyle('I26:K26')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        $sheet->mergeCells("L26:N26");
        $sheet->setCellValue('L'.$i, $this->translator->trans('items_per_canals.kiosk_in'));
        ExcelUtilities::setCellAlignment($sheet->getCell('L'.$i), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('L'.$i), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), "ffff00");
        $phpExcelObject->getActiveSheet()->getStyle('L26:N26')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        $sheet->mergeCells("O26:Q26");
        $sheet->setCellValue('O'.$i, $this->translator->trans('items_per_canals.eat_in'));
        ExcelUtilities::setCellAlignment($sheet->getCell('O'.$i), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('O'.$i), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O".$i), "ffff00");
        $phpExcelObject->getActiveSheet()->getStyle('O26:Q26')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        $sheet->mergeCells("R26:T26");
        $sheet->setCellValue('R'.$i, $this->translator->trans('items_per_canals.take_out'));
        ExcelUtilities::setCellAlignment($sheet->getCell('R'.$i), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('R'.$i), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R".$i), "ffff00");
        $phpExcelObject->getActiveSheet()->getStyle('R26:T26')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        $sheet->mergeCells("U26:W26");
        $sheet->setCellValue('U'.$i, $this->translator->trans('items_per_canals.drive'));
        ExcelUtilities::setCellAlignment($sheet->getCell('U'.$i), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('U'.$i), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("U".$i), "ffff00");
        $phpExcelObject->getActiveSheet()->getStyle('U26:W26')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        $sheet->mergeCells("X26:Z26");
        $sheet->setCellValue('X'.$i, $this->translator->trans('items_per_canals.delivery'));
        ExcelUtilities::setCellAlignment($sheet->getCell('X'.$i), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('X'.$i), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("X".$i), "ffff00");
        $phpExcelObject->getActiveSheet()->getStyle('X26:Z26')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        $sheet->mergeCells("AA26:AC26");
        $sheet->setCellValue('AA'.$i, $this->translator->trans('items_per_canals.e_ordering'));
        ExcelUtilities::setCellAlignment($sheet->getCell('AA'.$i), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('AA'.$i), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("AA".$i), "ffff00");
        $phpExcelObject->getActiveSheet()->getStyle('AA26:AC26')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        $i++;

        ExcelUtilities::setCellAlignment($sheet->getCell('A'.$i), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('A'.$i), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), "d2d2d2");
        if($result['type']==0){
            $sheet->setCellValue('A'.$i, $this->translator->trans('items_per_canals.hour'));
        }else{
            $sheet->setCellValue('A'.$i, $this->translator->trans('items_per_canals.day'));
        }

        $sheet->setCellValue('B'.$i,$this->translator->trans('items_per_canals.ca_prev_ttc'));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('B'.$i), $alignmentV);
        ExcelUtilities::setCellAlignment($sheet->getCell('B'.$i), $alignmentH);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B".$i), "ffa500");

        $sheet->setCellValue('C'.$i,$this->translator->trans('items_per_canals.brut_ttc'));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('C'.$i), $alignmentV);
        ExcelUtilities::setCellAlignment($sheet->getCell('C'.$i), $alignmentH);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), "ffa500");

        $sheet->setCellValue('D'.$i,$this->translator->trans('items_per_canals.br'));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('D'.$i), $alignmentV);
        ExcelUtilities::setCellAlignment($sheet->getCell('D'.$i), $alignmentH);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D".$i), "ffa500");

        $sheet->setCellValue('E'.$i,$this->translator->trans('report.discount'));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('E'.$i), $alignmentV);
        ExcelUtilities::setCellAlignment($sheet->getCell('E'.$i), $alignmentH);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), "ffa500");

        $sheet->setCellValue('F'.$i,$this->translator->trans('items_per_canals.net_ttc'));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('F'.$i), $alignmentV);
        ExcelUtilities::setCellAlignment($sheet->getCell('F'.$i), $alignmentH);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), "ffa500");

        $sheet->setCellValue('G'.$i,$this->translator->trans('items_per_canals.tax'));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('G'.$i), $alignmentV);
        ExcelUtilities::setCellAlignment($sheet->getCell('G'.$i), $alignmentH);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), "ffa500");

        $sheet->setCellValue('H'.$i,$this->translator->trans('items_per_canals.net_ht'));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('H'.$i), $alignmentV);
        ExcelUtilities::setCellAlignment($sheet->getCell('H'.$i), $alignmentH);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), "ffa500");

        $cell='I';
        for($j=0;$j<7;$j++){
            $sheet->setCellValue($cell.$i,$this->translator->trans('items_per_canals.brut_ttc'));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell($cell.$i), $alignmentV);
            ExcelUtilities::setCellAlignment($sheet->getCell($cell.$i), $alignmentH);
            ExcelUtilities::setBackgroundColor($sheet->getCell($cell.$i), "ffa500");
            ExcelUtilities::setFont($sheet->getCell($cell.$i), 8, true);
            $cell++;
            $sheet->setCellValue($cell.$i,$this->translator->trans('items_per_canals.tax'));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell($cell.$i), $alignmentV);
            ExcelUtilities::setCellAlignment($sheet->getCell($cell.$i), $alignmentH);
            ExcelUtilities::setBackgroundColor($sheet->getCell($cell.$i), "ffa500");
            ExcelUtilities::setFont($sheet->getCell($cell.$i), 8, true);
            $cell++;
            $sheet->setCellValue($cell.$i,$this->translator->trans('items_per_canals.net_ht'));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell($cell.$i), $alignmentV);
            ExcelUtilities::setCellAlignment($sheet->getCell($cell.$i), $alignmentH);
            ExcelUtilities::setBackgroundColor($sheet->getCell($cell.$i), "ffa500");
            ExcelUtilities::setFont($sheet->getCell($cell.$i), 8, true);
            $cell++;
        }


        $kiosk_out_total_ttc=0;
        $kiosk_out_total_tax=0;
        $kiosk_out_total_htva=0;

        $kiosk_in_total_ttc=0;
        $kiosk_in_total_tax=0;
        $kiosk_in_total_htva=0;

        $eat_in_total_ttc=0;
        $eat_in_total_tax=0;
        $eat_in_total_htva=0;

        $take_out_total_ttc=0;
        $take_out_total_tax=0;
        $take_out_total_htva=0;

        $drive_total_ttc=0;
        $drive_total_tax=0;
        $drive_total_htva=0;

        $delivery_total_ttc=0;
        $delivery_total_tax=0;
        $delivery_total_htva=0;

        $e_ordering_total_ttc=0;
        $e_ordering_total_tax=0;
        $e_ordering_total_htva=0;

        $total_ca_prev=0;
        $total_ttc=0;
        $total_br=0;
        $total_discount=0;
        $total_net_ttc=0;
        $total_taxes=0;
        $total_net_ht=0;

        $i=28;
        foreach ($result['report_two'] as $date => $line){
            $sheet->setCellValue('A'.$i, $date);
            ExcelUtilities::setCellAlignment($sheet->getCell('A'.$i), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('A'.$i), $alignmentV);

            $sheet->setCellValue('B'.$i,number_format($line['ca_prev'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('B'.$i), $alignmentV);
            $sheet->getStyle('B'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $total_ca_prev+=$line['ca_prev'];

            $sheet->setCellValue('C'.$i,number_format($line['total']['ca_brut_ttc'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('C'.$i), $alignmentV);
            $total_ttc+=$line['total']['ca_brut_ttc'];

            $sheet->setCellValue('D'.$i,number_format($line['total']['br'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('D'.$i), $alignmentV);
            $sheet->getStyle('D'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $total_br+=$line['total']['br'];

            $sheet->setCellValue('E'.$i,number_format(abs($line['total']['discount']), 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('E'.$i), $alignmentV);
            $sheet->getStyle('E'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $total_discount+=abs($line['total']['discount']);

            $sheet->setCellValue('F'.$i,number_format($line['total']['ca_net_ttc'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('F'.$i), $alignmentV);
            $sheet->getStyle('F'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $total_net_ttc+=$line['total']['ca_net_ttc'];

            $sheet->setCellValue('G'.$i,number_format($line['total']['total_tva'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('G'.$i), $alignmentV);
            $sheet->getStyle('G'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $total_taxes+=$line['total']['total_tva'];

            $sheet->setCellValue('H'.$i,number_format($line['total']['ca_net_ht'], 2, '.', ''));
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell('H'.$i), $alignmentV);
            $sheet->getStyle('H'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $total_net_ht+=$line['total']['ca_net_ht'];

            if (array_key_exists('kiosk_out', $line)) {
                $sheet->setCellValue('I'.$i,number_format($line['kiosk_out']['ca_brut_ttc'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('I'.$i), $alignmentV);
                $sheet->getStyle('I'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $kiosk_out_total_ttc+=$line['kiosk_out']['ca_brut_ttc'];

                $sheet->setCellValue('J'.$i,number_format($line['kiosk_out']['total_tva'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('J'.$i), $alignmentV);
                $sheet->getStyle('J'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $kiosk_out_total_tax+=$line['kiosk_out']['total_tva'];

                $sheet->setCellValue('K'.$i,number_format($line['kiosk_out']['ht'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('K'.$i), $alignmentV);
                $sheet->getStyle('K'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $kiosk_out_total_htva+=$line['kiosk_out']['ht'];
            }else{
                $sheet->setCellValue('I'.$i,0);
                $sheet->setCellValue('J'.$i,0);
                $sheet->setCellValue('K'.$i,0);
            }

            if (array_key_exists('kiosk_in', $line)) {
                $sheet->setCellValue('L'.$i,number_format($line['kiosk_in']['ca_brut_ttc'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('L'.$i), $alignmentV);
                $sheet->getStyle('L'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $kiosk_in_total_ttc+=$line['kiosk_in']['ca_brut_ttc'];

                $sheet->setCellValue('M'.$i,number_format($line['kiosk_in']['total_tva'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('M'.$i), $alignmentV);
                $sheet->getStyle('M'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $kiosk_in_total_tax+=$line['kiosk_in']['total_tva'];

                $sheet->setCellValue('N'.$i,number_format($line['kiosk_in']['ht'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('N'.$i), $alignmentV);
                $sheet->getStyle('N'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $kiosk_in_total_htva+=$line['kiosk_in']['ht'];
            }else{
                $sheet->setCellValue('L'.$i,0);
                $sheet->setCellValue('M'.$i,0);
                $sheet->setCellValue('N'.$i,0);
            }

            if (array_key_exists('eat_in', $line)) {
                $sheet->setCellValue('O'.$i,number_format($line['eat_in']['ca_brut_ttc'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('O'.$i), $alignmentV);
                $sheet->getStyle('O'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $eat_in_total_ttc+=$line['eat_in']['ca_brut_ttc'];

                $sheet->setCellValue('P'.$i,number_format($line['eat_in']['total_tva'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('P'.$i), $alignmentV);
                $sheet->getStyle('P'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $eat_in_total_tax+=$line['eat_in']['total_tva'];

                $sheet->setCellValue('Q'.$i,number_format($line['eat_in']['ht'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('Q'.$i), $alignmentV);
                $sheet->getStyle('Q'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $eat_in_total_htva+=$line['eat_in']['ht'];

            }
            else{
                $sheet->setCellValue('O'.$i,0);
                $sheet->setCellValue('P'.$i,0);
                $sheet->setCellValue('Q'.$i,0);
            }

            if (array_key_exists('take_out', $line)) {
                $sheet->setCellValue('R'.$i,number_format($line['take_out']['ca_brut_ttc'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('R'.$i), $alignmentV);
                $sheet->getStyle('R'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $take_out_total_ttc+=$line['take_out']['ca_brut_ttc'];

                $sheet->setCellValue('S'.$i,number_format($line['take_out']['total_tva'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('S'.$i), $alignmentV);
                $sheet->getStyle('S'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $take_out_total_tax+=$line['take_out']['total_tva'];

                $sheet->setCellValue('T'.$i,number_format($line['take_out']['ht'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('T'.$i), $alignmentV);
                $sheet->getStyle('T'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $take_out_total_htva+=$line['take_out']['ht'];
            }else{
                $sheet->setCellValue('R'.$i,0);
                $sheet->setCellValue('S'.$i,0);
                $sheet->setCellValue('T'.$i,0);
            }

            if (array_key_exists('drive', $line)) {
                $sheet->setCellValue('U'.$i,number_format($line['drive']['ca_brut_ttc'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('U'.$i), $alignmentV);
                $sheet->getStyle('U'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $drive_total_ttc+=$line['drive']['ca_brut_ttc'];

                $sheet->setCellValue('V'.$i,number_format($line['drive']['total_tva'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('V'.$i), $alignmentV);
                $sheet->getStyle('V'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $drive_total_tax+=$line['drive']['total_tva'];

                $sheet->setCellValue('W'.$i,number_format($line['drive']['ht'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('W'.$i), $alignmentV);
                $sheet->getStyle('W'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $drive_total_htva+=$line['drive']['ht'];
            }else{
                $sheet->setCellValue('U'.$i,0);
                $sheet->setCellValue('V'.$i,0);
                $sheet->setCellValue('W'.$i,0);
            }

            if (array_key_exists('delivery', $line)) {
                $sheet->setCellValue('X'.$i,number_format($line['delivery']['ca_brut_ttc'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('X'.$i), $alignmentV);
                $sheet->getStyle('X'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $delivery_total_ttc+=$line['delivery']['ca_brut_ttc'];

                $sheet->setCellValue('Y'.$i,number_format($line['delivery']['total_tva'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('Y'.$i), $alignmentV);
                $sheet->getStyle('Y'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $delivery_total_tax+=$line['delivery']['total_tva'];

                $sheet->setCellValue('Z'.$i,number_format($line['delivery']['ht'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('Z'.$i), $alignmentV);
                $sheet->getStyle('Z'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $delivery_total_htva+=$line['delivery']['ht'];
            }
            else{
                $sheet->setCellValue('X'.$i,0);
                $sheet->setCellValue('Y'.$i,0);
                $sheet->setCellValue('Z'.$i,0);
            }

            if (array_key_exists('e_ordering_in', $line) || array_key_exists('e_ordering_out', $line) ) {
                $sheet->setCellValue('AA'.$i,number_format($line['e_ordering_in']['ca_brut_ttc']+$line['e_ordering_out']['ca_brut_ttc'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('AA'.$i), $alignmentV);
                $sheet->getStyle('AA'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $e_ordering_total_ttc+=$line['e_ordering_in']['ca_brut_ttc'] +$line['e_ordering_out']['ca_brut_ttc'];

                $sheet->setCellValue('AB'.$i,number_format($line['e_ordering_in']['total_tva'] +$line['e_ordering_out']['total_tva'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('AB'.$i), $alignmentV);
                $sheet->getStyle('AB'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $e_ordering_total_tax+=$line['e_ordering_in']['total_tva'] +$line['e_ordering_out']['total_tva'];

                $sheet->setCellValue('AC'.$i,number_format($line['e_ordering_in']['ht'] +$line['e_ordering_out']['ht'], 2, '.', ''));
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell('AC'.$i), $alignmentV);
                $sheet->getStyle('AC'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                $e_ordering_total_htva+=$line['e_ordering_in']['ht'] +$line['e_ordering_out']['ht'];
            }
            else{
                $sheet->setCellValue('AA'.$i,0);
                $sheet->setCellValue('AB'.$i,0);
                $sheet->setCellValue('AC'.$i,0);
            }
            $i++;
        }
        $sheet->setCellValue('A'.$i, $this->translator->trans('items_per_canals.total'));
        ExcelUtilities::setCellAlignment($sheet->getCell('A'.$i), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('A'.$i), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), "d2d2d2");

        $sheet->setCellValue('B'.$i,number_format($total_ca_prev, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('B'.$i), $alignmentV);
        $sheet->getStyle('B'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B".$i), "d2d2d2");

        $sheet->setCellValue('C'.$i,number_format($total_ttc, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('C'.$i), $alignmentV);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), "d2d2d2");

        $sheet->setCellValue('D'.$i,number_format($total_br, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('D'.$i), $alignmentV);
        $sheet->getStyle('D'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D".$i), "d2d2d2");

        $sheet->setCellValue('E'.$i,number_format($total_discount, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('E'.$i), $alignmentV);
        $sheet->getStyle('E'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), "d2d2d2");

        $sheet->setCellValue('F'.$i,number_format($total_net_ttc, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('F'.$i), $alignmentV);
        $sheet->getStyle('F'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), "d2d2d2");

        $sheet->setCellValue('G'.$i,number_format($total_taxes, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('G'.$i), $alignmentV);
        $sheet->getStyle('G'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), "d2d2d2");

        $sheet->setCellValue('H'.$i,number_format($total_net_ht, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('H'.$i), $alignmentV);
        $sheet->getStyle('H'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), "d2d2d2");

        // by soldinc canals
        $sheet->setCellValue('I'.$i,number_format($kiosk_out_total_ttc, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('I'.$i), $alignmentV);
        $sheet->getStyle('I'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), "d2d2d2");
        $sheet->setCellValue('J'.$i,number_format($kiosk_out_total_tax, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('J'.$i), $alignmentV);
        $sheet->getStyle('J'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), "d2d2d2");
        $sheet->setCellValue('K'.$i,number_format($kiosk_out_total_htva, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('K'.$i), $alignmentV);
        $sheet->getStyle('K'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K".$i), "d2d2d2");

        $sheet->setCellValue('L'.$i,number_format($kiosk_in_total_ttc, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('L'.$i), $alignmentV);
        $sheet->getStyle('L'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), "d2d2d2");
        $sheet->setCellValue('M'.$i,number_format($kiosk_in_total_tax, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('M'.$i), $alignmentV);
        $sheet->getStyle('M'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M".$i), "d2d2d2");
        $sheet->setCellValue('N'.$i,number_format($kiosk_in_total_htva, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('N'.$i), $alignmentV);
        $sheet->getStyle('N'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N".$i), "d2d2d2");

        $sheet->setCellValue('O'.$i,number_format($eat_in_total_ttc, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('O'.$i), $alignmentV);
        $sheet->getStyle('O'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O".$i), "d2d2d2");
        $sheet->setCellValue('P'.$i,number_format($eat_in_total_tax, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('P'.$i), $alignmentV);
        $sheet->getStyle('P'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P".$i), "d2d2d2");
        $sheet->setCellValue('Q'.$i,number_format($eat_in_total_htva, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('Q'.$i), $alignmentV);
        $sheet->getStyle('Q'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q".$i), "d2d2d2");

        $sheet->setCellValue('R'.$i,number_format($take_out_total_ttc, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('R'.$i), $alignmentV);
        $sheet->getStyle('R'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R".$i), "d2d2d2");

        $sheet->setCellValue('S'.$i,number_format($take_out_total_tax, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('S'.$i), $alignmentV);
        $sheet->getStyle('S'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("S".$i), "d2d2d2");

//var_dump($take_out_total_htva);die;
        $sheet->setCellValue('T'.$i,number_format($take_out_total_htva, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('T'.$i), $alignmentV);
        $sheet->getStyle('T'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("T".$i), "d2d2d2");

        $sheet->setCellValue('U'.$i,number_format($drive_total_ttc, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('U'.$i), $alignmentV);
        $sheet->getStyle('U'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("U".$i), "d2d2d2");
        $sheet->setCellValue('V'.$i,number_format($drive_total_tax, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('V'.$i), $alignmentV);
        $sheet->getStyle('V'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("V".$i), "d2d2d2");
        $sheet->setCellValue('W'.$i,number_format($drive_total_htva, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('W'.$i), $alignmentV);
        $sheet->getStyle('W'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("W".$i), "d2d2d2");

        $sheet->setCellValue('X'.$i,number_format($delivery_total_ttc, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('X'.$i), $alignmentV);
        $sheet->getStyle('X'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("X".$i), "d2d2d2");
        $sheet->setCellValue('Y'.$i,number_format($delivery_total_tax, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('Y'.$i), $alignmentV);
        $sheet->getStyle('Y'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Y".$i), "d2d2d2");
        $sheet->setCellValue('Z'.$i,number_format($delivery_total_htva, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('Z'.$i), $alignmentV);
        $sheet->getStyle('Z'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Z".$i), "d2d2d2");

        $sheet->setCellValue('AA'.$i,number_format($e_ordering_total_ttc, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('AA'.$i), $alignmentV);
        $sheet->getStyle('AA'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("AA".$i), "d2d2d2");
        $sheet->setCellValue('AB'.$i,number_format($e_ordering_total_tax, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('AB'.$i), $alignmentV);
        $sheet->getStyle('AB'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("AB".$i), "d2d2d2");
        $sheet->setCellValue('AC'.$i,number_format($e_ordering_total_htva, 2, '.', ''));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell('AC'.$i), $alignmentV);
        $sheet->getStyle('AC'.$i)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        ExcelUtilities::setBackgroundColor($sheet->getCell("AC".$i), "d2d2d2");

        $filename = "Repartition_des_ventes_par_canal_de_vente_".date('dmY_His').".xls";
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