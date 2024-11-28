<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 17/10/2016
 * Time: 12:04
 */

namespace AppBundle\Report\Service;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;
use AppBundle\Administration\Service\ParameterService;

class ReportTakeoutService
{
    private $em;
    private $translator;
    private $paramService;
    private $restaurantService;
    private $phpExcel;

    /**
     * ReportTakeoutService constructor.
     * @param $em
     * @param $translator
     * @param $paramService
     */
    public function __construct(EntityManager $em, Translator $translator, ParameterService $paramService, RestaurantService $restaurantService, Factory $factory)
    {
        $this->em = $em;
        $this->translator = $translator;
        $this->paramService = $paramService;
        $this->restaurantService = $restaurantService;
        $this->phpExcel = $factory;
    }


    public function getList($filter){
        $startDate =$filter['startDate'];
        $endDate =$filter['endDate'];
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $query= $this->em->getRepository('Financial:Ticket')->createQueryBuilder('t')
                ->where('t.type =:type')
                ->andWhere('t.date >= :startDate')
                ->andWhere('t.date <= :endDate')
                ->andWhere('t.status <> :canceled')
                ->andWhere('t.status <> :abandonment')
                ->andWhere('t.originRestaurant = :restaurant')
                ->setParameter('type',Ticket::ORDER)
                ->setParameter('startDate',$startDate)
                ->setParameter('endDate', $endDate)
                ->setParameter('abandonment', Ticket::ABONDON_STATUS_VALUE)
                ->setParameter('canceled',Ticket::CANCEL_STATUS_VALUE )
                ->setParameter('restaurant', $currentRestaurant);
        if(isset($filter['cashier']) && ! is_null($filter['cashier'])){
                $query->andWhere("t.operator = :operator")
                    ->setParameter("operator", $filter['cashier']->getWyndId());
        }
        $allTickets = $query
                ->getQuery()
                ->getResult();
        //serialization
        $report=array();
        $totalTickets = 0;
        foreach($allTickets as $ticket){
            /**
             * @var Ticket $ticket
             */
            $user = $this->em->getRepository('Staff:Employee')
                             ->createQueryBuilder('e')
                             ->where('e.wyndId = :operator')
                             ->andWhere(':restaurant MEMBER OF e.eligibleRestaurants')
                             ->setParameter('operator', $ticket->getOperator())
                             ->setParameter('restaurant', $currentRestaurant)
                            ->getQuery()
                            ->getSingleResult()
                            ->getName();
            if(! isset($report[$user]['report'][$ticket->getDate()->format("Y-m-d")])) {
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['takein'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['takeout'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['drive'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['delivery'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['ptakein'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['ptakeout'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['pdrive'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['pdelivery'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['kioskin'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['pkioskin'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['kioskout'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['pkioskout'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['e_ordering_in'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['pe_ordering_in'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['e_ordering_out'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['pe_ordering_out'] = 0;
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['total'] = 0;
            }
            $mealVoucher = 0;
            foreach ($ticket->getPayments() as $payment) {
                /**
                 * @var TicketPayment $payment
                 */
                if($payment->getIdPayment() == TicketPayment::MEAL_TICKET)
                {
                    $mealVoucher += $payment->getAmount();
                }
            }

            $amount = $ticket->getTotalTtc() - $mealVoucher;
            $totalTickets += $amount;
            $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['total'] += $amount;
            //TAKE IN
            if(  ($ticket->getOrigin() == SoldingCanal::POS and $ticket->getDestination() == SoldingCanal::EATIN)
                or ($ticket->getOrigin() == null and $ticket->getDestination() == SoldingCanal::TAKE_IN)
                or ($ticket->getOrigin() == '' and $ticket->getDestination() == SoldingCanal::TAKE_IN)
                or ($ticket->getOrigin() == null and $ticket->getDestination() == null)
                or ($ticket->getOrigin() == '' and $ticket->getDestination() == '')){
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['takein'] += $amount;
            }
            //TAKE OUT
            if(($ticket->getOrigin() == SoldingCanal::POS and $ticket->getDestination() == SoldingCanal::TAKE_AWAY)
                or ($ticket->getOrigin() == '' and $ticket->getDestination() == SoldingCanal::TAKE_OUT) or ($ticket->getOrigin() == null and $ticket->getDestination() == SoldingCanal::TAKE_OUT)){
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['takeout'] += $amount;
            }
            // add new destinations for drive and delivery  2022
            //DRIVE
            if( ($ticket->getOrigin()== SoldingCanal::DRIVE &&  $ticket->getDestination() == SoldingCanal::DRIVE)
                or ($ticket->getOrigin()== SoldingCanal::origin_e_ordering &&  $ticket->getDestination() == SoldingCanal::MQDRIVE)
                or  ($ticket->getOrigin()== SoldingCanal::origin_e_ordering &&  $ticket->getDestination() == SoldingCanal::MQCURBSIDE)
            ){
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['drive'] += $amount;
            }

            //DELIVERY
            if(($ticket->getOrigin() == SoldingCanal::POS && strtolower($ticket->getDestination()) == strtolower(SoldingCanal::DELIVERY))
                or ($ticket->getOrigin() == SoldingCanal::origin_e_ordering && strtolower($ticket->getDestination()) == strtolower(SoldingCanal::ATOUBEREATS))
                or ($ticket->getOrigin() == SoldingCanal::origin_e_ordering && strtolower($ticket->getDestination()) == strtolower(SoldingCanal::ATODELIVEROO))
                or ($ticket->getOrigin() == SoldingCanal::origin_e_ordering && strtolower($ticket->getDestination()) == strtolower(SoldingCanal::ATOTAKEAWAY))
                or ($ticket->getOrigin() == SoldingCanal::origin_e_ordering && strtolower($ticket->getDestination()) == strtolower(SoldingCanal::ATOHELLOUGO))
                or ($ticket->getOrigin() == SoldingCanal::origin_e_ordering && strtolower($ticket->getDestination()) == strtolower(SoldingCanal::ATOEASY2EAT))
                or ($ticket->getOrigin() == SoldingCanal::origin_e_ordering && strtolower($ticket->getDestination()) == strtolower(SoldingCanal::ATOGOOSTY))
                or ($ticket->getOrigin() == SoldingCanal::origin_e_ordering && strtolower($ticket->getDestination()) == strtolower(SoldingCanal::ATOWOLT))
            ){
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['delivery'] += $amount;
            }
            //KIOSK IN
            if($ticket->getOrigin() == SoldingCanal::KIOSK and $ticket->getDestination() == SoldingCanal::EATIN){
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['kioskin'] += $amount;
            }
            //KIOSK OUT
            if($ticket->getOrigin() == SoldingCanal::KIOSK and $ticket->getDestination() == SoldingCanal::TAKE_AWAY){
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['kioskout'] += $amount;
            }
            //E-Ordering IN
            if($ticket->getOrigin() == SoldingCanal::origin_e_ordering and $ticket->getDestination() == SoldingCanal::e_ordering_in){
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['e_ordering_in'] += $amount;
            }

            //E-Ordering OUT
            if($ticket->getOrigin() == SoldingCanal::origin_e_ordering and $ticket->getDestination() == SoldingCanal::e_ordering_out){
                $report[$user]['report'][$ticket->getDate()->format("Y-m-d")]['e_ordering_out'] += $amount;
            }
        }

        //Calcul des pourcentages et le total par jour
        $totalPerDay = array();
        foreach($report as $user => $data) {
            foreach ($data['report'] as $date => $stats) {

                if ($report[$user]['report'][$date]['total'] > 0) {
                    $report[$user]['report'][$date]['ptakein'] = ($report[$user]['report'][$date]['takein'] / $report[$user]['report'][$date]['total']) * 100;
                    $report[$user]['report'][$date]['ptakeout'] = ($report[$user]['report'][$date]['takeout'] / $report[$user]['report'][$date]['total']) * 100;
                    $report[$user]['report'][$date]['pdrive'] = ($report[$user]['report'][$date]['drive'] / $report[$user]['report'][$date]['total']) * 100;
                    $report[$user]['report'][$date]['pdelivery'] = ($report[$user]['report'][$date]['delivery'] / $report[$user]['report'][$date]['total']) * 100;
                    $report[$user]['report'][$date]['pkioskin'] = ($report[$user]['report'][$date]['kioskin'] / $report[$user]['report'][$date]['total']) * 100;
                    $report[$user]['report'][$date]['pkioskout'] = ($report[$user]['report'][$date]['kioskout'] / $report[$user]['report'][$date]['total']) * 100;
                    $report[$user]['report'][$date]['pe_ordering_in'] = ($report[$user]['report'][$date]['e_ordering_in'] / $report[$user]['report'][$date]['total']) * 100;
                    $report[$user]['report'][$date]['pe_ordering_out'] = ($report[$user]['report'][$date]['e_ordering_out'] / $report[$user]['report'][$date]['total']) * 100;
                } else {
                    $report[$user]['report'][$date]['ptakein'] = 0;
                    $report[$user]['report'][$date]['ptakeout'] = 0;
                    $report[$user]['report'][$date]['pdrive'] = 0;
                    $report[$user]['report'][$date]['pdelivery'] = 0;
                    $report[$user]['report'][$date]['pkioskin'] = 0;
                    $report[$user]['report'][$date]['pkioskout'] = 0;
                    $report[$user]['report'][$date]['pe_ordering_in'] = 0;
                    $report[$user]['report'][$date]['pe_ordering_out'] = 0;
                }
                // calculate the total per day
                if(!isset($totalPerDay[$date]))
                {
                    $totalPerDay[$date]['totalTTC'] = 0;
                    $totalPerDay[$date]['takein'] = 0;
                    $totalPerDay[$date]['takeout'] = 0;
                    $totalPerDay[$date]['drive'] = 0;
                    $totalPerDay[$date]['delivery'] = 0;
                    $totalPerDay[$date]['kioskin'] = 0;
                    $totalPerDay[$date]['kioskout'] = 0;
                    $totalPerDay[$date]['e_ordering_in'] = 0;
                    $totalPerDay[$date]['e_ordering_out'] = 0;
                }

                $totalPerDay[$date]['totalTTC'] += $report[$user]['report'][$date]['total'];
                $totalPerDay[$date]['takein'] += $report[$user]['report'][$date]['takein'];
                $totalPerDay[$date]['takeout'] += $report[$user]['report'][$date]['takeout'];
                $totalPerDay[$date]['drive'] += $report[$user]['report'][$date]['drive'];
                $totalPerDay[$date]['delivery'] += $report[$user]['report'][$date]['delivery'];
                $totalPerDay[$date]['kioskin'] += $report[$user]['report'][$date]['kioskin'];
                $totalPerDay[$date]['kioskout'] += $report[$user]['report'][$date]['kioskout'];
                $totalPerDay[$date]['e_ordering_in'] += $report[$user]['report'][$date]['e_ordering_in'];
                $totalPerDay[$date]['e_ordering_out'] += $report[$user]['report'][$date]['e_ordering_out'];
            }
        }
        $output=array();
       $output['startDate'] = $startDate->format('d/m/Y');
       $output['endDate'] = $endDate->format('d/m/Y');
        if(isset($filter['cashier'])){
            $output['cashier'] = $filter['cashier'];
        }
        $output['report']=$report;
        ksort($totalPerDay);
        $output['totalPerDay'] = $totalPerDay;
        $output['totalTTC'] = $totalTickets;

        return $output;
    }

    public function generateTakeoutReportExcelFile($result,$startDate, $endDate, $logoPath)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $topHeaderColor = "666699"; //old: B3B3F2
        $colorOne = "ECECEC";
        $userColor = "FFFF66";
        $userTotalColor = "5F91CB"; //old: 8FBFF7
        $totalColor = "BDDAF3";


        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentRH = \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('takeout_report.title'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('takeout_report.title');
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
        // START DATE
        ExcelUtilities::setFont($sheet->getCell('B10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B10"), $colorOne);
        $sheet->setCellValue('B10', $this->translator->trans('keyword.from').":");
        $sheet->mergeCells("C10:D10");
        ExcelUtilities::setFont($sheet->getCell('C10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C10"), $colorOne);
        $sheet->setCellValue('C10', $startDate->format('d-m-Y'));
        // END DATE
        ExcelUtilities::setFont($sheet->getCell('E10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E10"), $colorOne);
        $sheet->setCellValue('E10', $this->translator->trans('keyword.to').":");
        $sheet->mergeCells("F10:G10");
        ExcelUtilities::setFont($sheet->getCell('F10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F10"), $colorOne);
        $sheet->setCellValue('F10', $endDate->format('d-m-Y'));


        //CONTENT
        $startCell = 1;
        $startLine = 14;
        // top headers
        //Cashier
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $this->translator->trans('takeout_report.cashier'));
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell+2).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        //Date
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $this->translator->trans('takeout_report.date'));
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        //CA Net TTC
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $this->translator->trans('takeout_report.ca_net_ttc'));
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        //Take in
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $this->translator->trans('takeout_report.takein'));
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        // percentage
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, "%");
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        // takeout
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $this->translator->trans('takeout_report.takeout'));
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        // percentage
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, "%");
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        // drive
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $this->translator->trans('takeout_report.drive'));
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        // percentage
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, "%");
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        // delivery
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $this->translator->trans('takeout_report.delivery'));
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        // percentage
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, "%");
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        // kiosk in
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $this->translator->trans('takeout_report.kiosk_in'));
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        // percentage
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, "%");
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        // kiosk out
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $this->translator->trans('takeout_report.kiosk_out'));
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        // percentage
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, "%");
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        //new canal e_ordering IN
        $startCell += 1;

        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $this->translator->trans('takeout_report.e_ordering_in'));
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        // percentage
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, "%");
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        //body
        //new canal e_ordering OUT
        $startCell += 1;

        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $this->translator->trans('takeout_report.e_ordering_out'));
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startCell += 1;
        // percentage
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, "%");
        $sheet->getColumnDimensionByColumn($startCell)->setAutoSize(true);
        ExcelUtilities::setFont($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), 11, true);
        ExcelUtilities::setTextColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $topHeaderColor);
        $startLine++;

        $totalTakeIn = 0;
        $totalTakeOut = 0;
        $totalDrive = 0;
        $totalDelivery = 0;
        $totalKioskIn = 0;
        $totalKioskOut = 0;
        $totalEorderingIN = 0;
        $totalEorderingOUT = 0;
        $totalTTC = $result["totalTTC"];
        foreach ($result["report"] as $user => $data)
        {
            $startCell = 1;
            $subTotalTakeIn = 0;
            $subTotalTakeOut = 0;
            $subTotalDrive = 0;
            $subTotalDelivery = 0;
            $subTotalKioskIn = 0;
            $subTotalKioskOut = 0;
            $subTotalEorderingIn=0;
            $subTotalEorderingOut=0;
            $subTotalTTC = 0;

            //user
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $user);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell+2).($startLine + count($data["report"]) - 1)));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userColor);

            foreach ($data["report"] as $date => $row)
            {
                $subTotalTakeIn += $row["takein"];
                $subTotalTakeOut += $row["takeout"];
                $subTotalDrive += $row["drive"];
                $subTotalDelivery += $row["delivery"];
                $subTotalKioskIn += $row["kioskin"];
                $subTotalKioskOut += $row["kioskout"];
                $subTotalEorderingIn += $row["e_ordering_in"];
                $subTotalEorderingOut += $row["e_ordering_out"];
                $subTotalTTC += $row["total"];
                $startCell = 2;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $date);
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["total"],2,'.',''));
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["takein"], 2,'.',''));
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["ptakein"], 2,'.','')." %");
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["takeout"], 2,'.',''));
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["ptakeout"], 2,'.','')." %");
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["drive"], 2,'.',''));
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["pdrive"], 2,'.','')." %");
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["delivery"], 2,'.',''));
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["pdelivery"], 2,'.','')." %");
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["kioskin"], 2,'.',''));
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["pkioskin"], 2,'.','')." %");
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["kioskout"], 2,'.',''));
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["pkioskout"], 2,'.','')." %");
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                //add new canal e_ordering IN
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["e_ordering_in"], 2,'.',''));
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["pe_ordering_in"], 2,'.','')." %");
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));

                //add new canal e_ordering OUT
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["e_ordering_out"], 2,'.',''));
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
                $startCell +=1;
                $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["pe_ordering_out"], 2,'.','')." %");
                ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
                ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));


                $startLine++;
            }

            $totalTakeIn += $subTotalTakeIn;
            $totalTakeOut += $subTotalTakeOut;
            $totalDrive += $subTotalDrive;
            $totalDelivery += $subTotalDelivery;
            $totalKioskIn += $subTotalKioskIn;
            $totalKioskOut += $subTotalKioskOut;
            $totalEorderingIN += $subTotalEorderingIn ;
            $totalEorderingOUT += $subTotalEorderingOut ;

            $pSubTotalTakeIn = 0;
            $pSubTotalTakeOut = 0;
            $pSubTotalDrive = 0;
            $pSubTotalDelivery = 0;
            $pSubTotalKioskIn = 0;
            $pSubTotalKioskOut = 0;
            $pSubTotalEorderingIn = 0;
            $pSubTotalEorderingOut = 0;
            if($subTotalTTC > 0)
            {
                $pSubTotalTakeIn = ($subTotalTakeIn / $subTotalTTC) * 100;
                $pSubTotalTakeOut = ($subTotalTakeOut / $subTotalTTC) * 100;
                $pSubTotalDrive = ($subTotalDrive/ $subTotalTTC) * 100;
                $pSubTotalDelivery = ($subTotalDelivery/ $subTotalTTC) * 100;
                $pSubTotalKioskIn = ($subTotalKioskIn / $subTotalTTC) * 100;
                $pSubTotalKioskOut = ($subTotalKioskOut / $subTotalTTC) * 100;
                $pSubTotalEorderingIn = ($subTotalEorderingIn / $subTotalTTC) * 100;
                $pSubTotalEorderingOut = ($subTotalEorderingOut / $subTotalTTC) * 100;
            }
            //sub total
            $startCell = 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $user." - Total");
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell+3).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;

            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;

            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($subTotalTTC, 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($subTotalTakeIn, 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($pSubTotalTakeIn, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($subTotalTakeOut, 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($pSubTotalTakeOut, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($subTotalDrive, 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($pSubTotalDrive, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($subTotalDelivery, 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($pSubTotalDelivery, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($subTotalKioskIn, 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($pSubTotalKioskIn, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($subTotalKioskOut, 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($pSubTotalKioskOut, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            ExcelUtilities::setTextColor($sheet->getStyle(ExcelUtilities::getNameFromNumber(1).$startLine.":".ExcelUtilities::getNameFromNumber(15).$startLine));
           //new canal e_ordering IN
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($subTotalEorderingIn, 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($pSubTotalEorderingIn, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            ExcelUtilities::setTextColor($sheet->getStyle(ExcelUtilities::getNameFromNumber(1).$startLine.":".ExcelUtilities::getNameFromNumber(15).$startLine));

            //new canal e_ordering OUT
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($subTotalEorderingOut, 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($pSubTotalEorderingOut, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $userTotalColor);
            ExcelUtilities::setTextColor($sheet->getStyle(ExcelUtilities::getNameFromNumber(1).$startLine.":".ExcelUtilities::getNameFromNumber(15).$startLine));
            $startLine++;
        }

        foreach ($result["totalPerDay"] as $date => $row)
        {
            //total per day
            $startCell = 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, $date." - Total");
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell+3).$startLine));
            $startCell += 2;

            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["totalTTC"], 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["takein"], 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($row["totalTTC"] > 0) ? ($row["takein"]/$row["totalTTC"]) * 100 : 0, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["takeout"], 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($row["totalTTC"] > 0) ? ($row["takeout"]/$row["totalTTC"]) * 100 : 0, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["drive"], 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($row["totalTTC"] > 0) ? ($row["drive"]/$row["totalTTC"]) * 100 : 0, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["delivery"], 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($row["totalTTC"] > 0) ? ($row["delivery"]/$row["totalTTC"]) * 100 : 0, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["kioskin"], 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($row["totalTTC"] > 0) ? ($row["kioskin"]/$row["totalTTC"]) * 100 : 0, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["kioskout"], 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($row["totalTTC"] > 0) ? ($row["kioskout"]/$row["totalTTC"]) * 100 : 0, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            //new canal E-ordering IN
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["e_ordering_in"], 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($row["totalTTC"] > 0) ? ($row["e_ordering_in"]/$row["totalTTC"]) * 100 : 0, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));

            //new canal E-ordering OUT
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($row["e_ordering_out"], 2,'.',''));
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
            $startCell += 1;
            $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($row["totalTTC"] > 0) ? ($row["e_ordering_out"]/$row["totalTTC"]) * 100 : 0, 2,'.','')." %");
            ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
            ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));

            $startLine++;
        }

        //total
        $startCell = 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, "Total");
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell+3).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;

        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;

        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($totalTTC, 2,'.',''));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($totalTakeIn, 2,'.',''));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($totalTTC > 0) ? ($totalTakeIn/$totalTTC) * 100 : 0, 2,'.','')." %");
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($totalTakeOut, 2,'.',''));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($totalTTC > 0) ? ($totalTakeOut/$totalTTC) * 100 : 0, 2,'.','')." %");
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($totalDrive, 2,'.',''));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($totalTTC > 0) ? ($totalDrive/$totalTTC) * 100 : 0, 2,'.','')." %");
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($totalDelivery, 2,'.',''));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($totalTTC > 0) ? ($totalDelivery/$totalTTC) * 100 : 0, 2,'.','')." %");
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($totalKioskIn, 2,'.',''));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($totalTTC > 0) ? ($totalKioskIn/$totalTTC) * 100 : 0, 2,'.','')." %");
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($totalKioskOut, 2,'.',''));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($totalTTC > 0) ? ($totalKioskOut/$totalTTC) * 100 : 0, 2,'.','')." %");
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
//new canal e_ordering_in
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($totalEorderingIN, 2,'.',''));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($totalTTC > 0) ? ($totalEorderingIN/$totalTTC) * 100 : 0, 2,'.','')." %");
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);

        //new canal e_ordering_out
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format($totalEorderingOUT, 2,'.',''));
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        $startCell += 1;
        $sheet->setCellValue(ExcelUtilities::getNameFromNumber($startCell).$startLine, number_format(($totalTTC > 0) ? ($totalEorderingOUT/$totalTTC) * 100 : 0, 2,'.','')." %");
        ExcelUtilities::setCellAlignment($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $alignmentRH);
        ExcelUtilities::setBorder($sheet->getStyle(ExcelUtilities::getNameFromNumber($startCell).$startLine.":".ExcelUtilities::getNameFromNumber($startCell).$startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell(ExcelUtilities::getNameFromNumber($startCell).$startLine), $totalColor);
        foreach(range('B','Q') as $columnID) {
            $phpExcelObject->getActiveSheet()->getColumnDimension($columnID)
                ->setAutoSize(true);
        }


        $filename = "Rapport_takeout_".date('dmY_His').".xls";
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