<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 19/10/2016
 * Time: 11:22
 */

namespace AppBundle\Report\Service;


use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\TicketIntervention;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class ReportCorrectionsService
{
    private $em;
    private $translator;
    private $paramService;
    private $restaurantService;
    private $phpExcel;

    /**
     * ReportDiscountService constructor.
     * @param $em
     * @param $translator
     * @param $paramService
     */
    public function __construct(
        EntityManager $em,
        Translator $translator,
        ParameterService $paramService,
        RestaurantService $restaurantService,
        Factory $factory
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->paramService = $paramService;
        $this->restaurantService = $restaurantService;
        $this->phpExcel = $factory;
    }

    public function getCurrentRestaurant()
    {
        return $this->restaurantService->getCurrentRestaurant();
    }
//    public function getHoursList(){
//        $currentRestaurant = $this->getCurrentRestaurant();
//        $openingHour = ($this->paramService->getRestaurantOpeningHour($currentRestaurant)==null)?0: $this->paramService->getRestaurantOpeningHour($currentRestaurant);
//        $closingHour = ($this->paramService->getRestaurantClosingHour($currentRestaurant)==null)?23: $this->paramService->getRestaurantClosingHour($currentRestaurant);
//        $hoursArray=array();
//        for($i=$openingHour; $i<=$closingHour; $i++){
//            $hoursArray[$i]=$i.":00";
//        }
//        return $hoursArray;
//    }
    public function getHoursList()
    {
        $currentRestaurant = $this->getCurrentRestaurant();
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
            $hoursArray[$i] = (($i >= 24) ? ($i - 24) : $i).":00";
        }

        return $hoursArray;
    }

    public function getCaReel($filter){
        $restaurantId = $this->getCurrentRestaurant()->getId();
        $sql = "SELECT
                      SUM (ticket.totalttc) 
                      FROM ticket
                   WHERE (
                      ticket.origin_restaurant_id = :restaurant_id AND
                      ticket.date >= :startDate              AND
                      ticket.date <= :endDate )";
        if (isset($filter['startHour'])) {
            $sql .= " AND CAST(date_part('HOUR', ticket.enddate) as integer) >= :startHour";
        }
        if (isset($filter['endHour'])) {
            $sql .= " AND CAST(date_part('HOUR', ticket.enddate) as integer) <= :endHour";
        }
        $start = $filter['startDate']->format('Y-m-d');
        $end = $filter['endDate']->format('Y-m-d');
        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('restaurant_id', $restaurantId);
        $stm->bindParam("startDate", $start);
        $stm->bindParam("endDate", $end);
        $stm->execute();
        $results = $stm->fetchAll();
        return $results[0]['sum'];
    }

    public function getList($filter)
    {
        $restaurantId = $this->getCurrentRestaurant()->getId();
        $sql = "SELECT
                      ticket.date AS fiscal_date,
                      ticket.enddate AS end_date,
                      ticket_intervention.itemprice             AS item_price,
                      ticket_intervention.itemqty               AS item_qty,
                      ticket_intervention.itemid                AS item_id,
                      ticket_intervention.posttotal             AS post_total,
                      ticket_intervention.itemlabel             AS item_label,
                      RESPONSIBLE.first_name                    AS responsible_first_name,
                      RESPONSIBLE.last_name                     AS responsible_last_name,
                      RESPONSIBLE.matricule                     AS responsible_matricule,
                      CASHIER.first_name                        AS cashier_first_name,
                      CASHIER.last_name                         AS cashier_last_name,
                      CASHIER.matricule                         AS cashier_matricule
                      FROM ticket_intervention
                      JOIN ticket ON ticket_intervention.ticket_id = ticket.id
                      JOIN quick_user  RESPONSIBLE ON RESPONSIBLE.wynd_id =  CAST(coalesce(ticket_intervention.managerid, '0') AS INTEGER)
                      JOIN quick_user  CASHIER ON CASHIER.wynd_id = ticket.operator
                      JOIN user_restaurant user_responsable ON RESPONSIBLE.id = user_responsable.user_id AND user_responsable.restaurant_id  = :restaurant_id 
                      JOIN user_restaurant user_cashier ON CASHIER.id = user_cashier.user_id AND user_cashier.restaurant_id  = :restaurant_id 
                      WHERE (
                      ticket_intervention.action= :action_type    AND
                      ticket.origin_restaurant_id = :restaurant_id AND
                      ticket.date >= :startDate              AND
                      ticket.date <= :endDate ";

//        $start = $filter['startDate']->format('Y-m-d');
//        $end = $filter['endDate']->format('Y-m-d');

        if (isset($filter['cashier'])) {
            $sql .= " AND ticket.operator = :operator";
        }
//                            if(isset($filter['responsible'])){
//                                $sql .= " AND ticket_intervention.managerid = :manager";
//                            }
        if (isset($filter['amountMin'])) {
            $sql .= " AND (ticket_intervention.itemqty * ticket_intervention.itemprice)>= :amountMin";
        }
        if (isset($filter['amountMax'])) {
            $sql .= " AND (ticket_intervention.itemqty * ticket_intervention.itemprice)<= :amountMax";
        }
        if (isset($filter['startHour'])) {
            $sql .= " AND CAST(date_part('HOUR', ticket.enddate) as integer) >= :startHour";
        }
        if (isset($filter['endHour'])) {
            $sql .= " AND CAST(date_part('HOUR', ticket.enddate) as integer) <= :endHour";
        }

        $sql .= ") order by fiscal_date ASC, date_part('HOUR', ticket.endDate) ASC, date_part('MINUTE', ticket.endDate) ASC ,  date_part('SECOND', ticket.endDate) ASC";

        $start = $filter['startDate']->format('Y-m-d');
        $end = $filter['endDate']->format('Y-m-d');
        $stm = $this->em->getConnection()->prepare($sql);
        if (isset($filter['cashier'])) {
            $cashier = $filter['cashier']->getWyndId();
            $stm->bindParam("operator", $cashier);
        }
//        if(isset($filter['responsible'])){
//            $responsible=$filter['responsible']->getWyndId();
//            $stm->bindParam("manager", $responsible);
//        }
        if (isset($filter['amountMin'])) {
            $stm->bindParam("amountMin", $filter['amountMin']);
        }
        if (isset($filter['amountMax'])) {
            $stm->bindParam("amountMax", $filter['amountMax']);
        }
        if (isset($filter['startHour'])) {
            $stm->bindParam("startHour", $filter['startHour']);
        }
        if (isset($filter['endHour'])) {
            $stm->bindParam("endHour", $filter['endHour']);
        }
        $stm->bindParam('restaurant_id', $restaurantId);
        $stm->bindParam("startDate", $start);
        $stm->bindParam("endDate", $end);
        $action = TicketIntervention::DELETE_ACTION;
        $stm->bindParam("action_type", $action);
        $stm->execute();
        $results = $stm->fetchAll();
        //Serialisation
        $report = array();
        $reportTwo = array();
        $reportThree = array();
        $totalCount = 0;
        $totalAmount = 0;
        foreach ($results as $result) {
            $totalCount++;
            $date = $result['fiscal_date'];
//            if ($result['post_total']) {
//                $type = "after";
//            } else {
//                $type = "before";
//            }
            $type = "after";
            $responsibleName = $result['responsible_first_name']." ".$result['responsible_last_name'];
            $cashierName = $result['cashier_first_name']." ".$result['cashier_last_name'];
            $product = $result['item_label'];
            $tmp = array();
            $tmp['cashier'] = $cashierName;
            $tmp['responsible'] = $responsibleName;
            if (!is_null($product)) {
                $tmp['product'] = $product;
            } else {
                $tmp['product'] = null;
            }
            $tmpDate = new \DateTime($result['end_date']);
            $tmp['hour'] = $tmpDate->format('H:i:s');
            $tmp['amount'] = $result['item_qty'] * $result['item_price'];
            $totalAmount += $tmp['amount'];
            $report[$date][$type][] = $tmp;
            //tableau 2
            if (isset($reportTwo[$responsibleName])) {
                $reportTwo[$responsibleName]['count']++;
                $reportTwo[$responsibleName]['amount'] += $result['item_qty'] * $result['item_price'];
                $reportTwo[$responsibleName]['matricule'] = $result['responsible_matricule'];
            } else {
                $reportTwo[$responsibleName]['matricule'] = $result['responsible_matricule'];
                $reportTwo[$responsibleName]['count'] = 1;
                $reportTwo[$responsibleName]['amount'] = $result['item_qty'] * $result['item_price'];
            }
            //tableau 3
            if (isset($reportThree[$cashierName])) {
                $reportThree[$cashierName]['count']++;
                $reportThree[$cashierName]['amount'] += $result['item_qty'] * $result['item_price'];
                $reportThree[$cashierName]['matricule'] = $result['cashier_matricule'];
            } else {
                $reportThree[$cashierName]['matricule'] = $result['cashier_matricule'];
                $reportThree[$cashierName]['count'] = 1;
                $reportThree[$cashierName]['amount'] = $result['item_qty'] * $result['item_price'];
            }
        }

        $output = array();
        $output['startDate'] = $filter['startDate']->format('Y-m-d');
        $output['endDate'] = $filter['startDate']->format('Y-m-d');
        if (!is_null($filter['cashier'])) {
            $output['cashier'] = $filter['cashier'];
        }
//        if(!is_null($filter['responsible'])) {
//            $output['responsible'] = $filter['responsible'];
//        }
        if (!is_null($filter['startHour'])) {
            $output['startHour'] = $filter['startHour'];
        }
        if (!is_null($filter['endHour'])) {
            $output['endHour'] = $filter['endHour'];
        }
        if (!is_null($filter['amountMin'])) {
            $output['amountMin'] = $filter['amountMin'];
        }
        if (!is_null($filter['amountMax'])) {
            $output['amountMax'] = $filter['amountMax'];
        }
        $output['report'] = $report;
        $output['reportTwo'] = $reportTwo;
        $output['reportThree'] = $reportThree;
        $output['totalCount'] = $totalCount;
        $output['totalAmount'] = $totalAmount;

        return $output;
    }

    public function generateExcelFile($result, $filter, Restaurant $currentRestaurant, $logoPath)
    {

        $colorOne = "ECECEC";
        $colorTwo = "E5CFAB";
        $colorThree = "DCDAF8";
        $colorTiltle = "288CCD";
        $colotTotal = "C0CAFF";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        $period=date_diff($filter['startDate'],$filter['endDate'])->format('%a')+1;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('corrections_report.title'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('corrections_report.title');
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
        $sheet->setCellValue('C11', $filter['startDate']->format('Y-m-d'));    // START DATE


        // END DATE
        $sheet->mergeCells("E11:F11");
        ExcelUtilities::setFont($sheet->getCell('E11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E11"), $colorOne);
        $sheet->setCellValue('E11', $this->translator->trans('keyword.to').":");
        $sheet->mergeCells("G11:H11");
        ExcelUtilities::setFont($sheet->getCell('G11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G11"), $colorOne);
        $sheet->setCellValue('G11', $filter['endDate']->format('Y-m-d'));

        // START DATE
        $sheet->mergeCells("A12:B12");
        ExcelUtilities::setFont($sheet->getCell('A12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A12"), $colorOne);
        $sheet->setCellValue('A12', $this->translator->trans('keyword.from').":");
        $sheet->mergeCells("C12:D12");
        ExcelUtilities::setFont($sheet->getCell('C12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C12"), $colorOne);
        $sheet->setCellValue('C12', $filter['startHour']);


        // END DATE
        $sheet->mergeCells("E12:F12");
        ExcelUtilities::setFont($sheet->getCell('E12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E12"), $colorOne);
        $sheet->setCellValue('E12', $this->translator->trans('keyword.to').":");
        $sheet->mergeCells("G12:H12");
        ExcelUtilities::setFont($sheet->getCell('G12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G12"), $colorOne);
        $sheet->setCellValue('G12', $filter['endHour']);

        $sheet->mergeCells("I11:L11");
        ExcelUtilities::setFont($sheet->getCell('I11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I11"), $colorOne);

        $sheet->mergeCells("I12:L12");
        ExcelUtilities::setFont($sheet->getCell('I12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I12"), $colorOne);


        //Equipier
        $sheet->mergeCells("A13:B13");
        ExcelUtilities::setFont($sheet->getCell('A13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A13"), $colorOne);
        $sheet->setCellValue('A13', $this->translator->trans('label.member').":");
        $sheet->mergeCells("C13:D13");
        ExcelUtilities::setFont($sheet->getCell('C13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C13"), $colorOne);
        $sheet->setCellValue('C13', $filter['cashier']);

        //amount min
        $sheet->mergeCells("E13:G13");
        ExcelUtilities::setFont($sheet->getCell('E13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E13"), $colorOne);
        $sheet->setCellValue('E13', $this->translator->trans('corrections_report.amount_min').":");

        ExcelUtilities::setFont($sheet->getCell('H13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H13"), $colorOne);
        $sheet->setCellValue('H13', $filter['amountMin']);

        //amount max
        $sheet->mergeCells("I13:K13");
        ExcelUtilities::setFont($sheet->getCell('I13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I13"), $colorOne);
        $sheet->setCellValue('I13', $this->translator->trans('corrections_report.amount_max').":");

        ExcelUtilities::setFont($sheet->getCell('L13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L13"), $colorOne);
        $sheet->setCellValue('L13', $filter['amountMax']);


        //Content
        $i = 15;
        //Date
        $sheet->mergeCells('A'.$i.':B'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTiltle);
        $sheet->setCellValue('A'.$i, $this->translator->trans('corrections_report.date'));
        //correction
        $sheet->mergeCells('C'.$i.':D'.$i);
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorTiltle);
        $sheet->setCellValue('C'.$i, $this->translator->trans('corrections_report.correction'));
        //Hour
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorTiltle);
        $sheet->setCellValue('E'.$i, $this->translator->trans('corrections_report.hour'));
        //Responsible
        $sheet->mergeCells('F'.$i.':G'.$i);
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorTiltle);
        $sheet->setCellValue('F'.$i, $this->translator->trans('corrections_report.responsible'));

        //Cashier
        $sheet->mergeCells('H'.$i.':I'.$i);
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorTiltle);
        $sheet->setCellValue('H'.$i, $this->translator->trans('corrections_report.cashier'));

        //Product
        $sheet->mergeCells('J'.$i.':K'.$i);
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), $colorTiltle);
        $sheet->setCellValue('J'.$i, $this->translator->trans('corrections_report.product'));

        //amount
        $sheet->mergeCells('L'.$i.':M'.$i);
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorTiltle);
        $sheet->setCellValue('L'.$i, $this->translator->trans('corrections_report.amount'));
        //Border
        $cell = 'A';
        while ($cell != 'N') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }

        $i = 16;
        $j = 16;
        $total = 0;
        foreach ($result['report'] as $date => $detail) {
            $total_date = 0;
            foreach ($detail as $type => $data) {
                $total_type = 0;
                foreach ($data as $line) {
                    //Hour
                    ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
                    $sheet->setCellValue('E'.$i, $line['hour']);
                    //Responsible
                    $sheet->mergeCells('F'.$i.':G'.$i);
                    ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                    $sheet->setCellValue('F'.$i, $line['responsible']);

                    //Cashier
                    $sheet->mergeCells('H'.$i.':I'.$i);
                    ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                    $sheet->setCellValue('H'.$i, $line['cashier']);

                    //Product
                    $sheet->mergeCells('J'.$i.':K'.$i);
                    ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                    $sheet->setCellValue('J'.$i, $line['product']);

                    //amount
                    $sheet->mergeCells('L'.$i.':M'.$i);
                    ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
                    $sheet->setCellValue('L'.$i, number_format($line['amount'],2));
                    $total_type = $total_type + $line['amount'];

                    //Border
                    $cell = 'A';
                    while ($cell != 'N') {
                        ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                        $cell++;
                    }

                    $i++;
                }
                $k = $i - 1;

                //correction
                $sheet->mergeCells('C'.$j.':D'.$k);
                ExcelUtilities::setFont($sheet->getCell('C'.$j), 10, true);
                $sheet->setCellValue('C'.$j, $this->translator->trans('corrections_report.'.$type));

//                //total type
                $sheet->mergeCells('C'.$i.':K'.$i);
                ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorThree);
                $sheet->setCellValue('C'.$i, $this->translator->trans('corrections_report.total'));
                $sheet->mergeCells('L'.$i.':M'.$i);
                ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorThree);
                ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
                $sheet->setCellValue('L'.$i, number_format($total_type,2));

                $total_date = $total_date + $total_type;
            }
            //date
            $sheet->mergeCells('A'.$j.':B'.$i);
            ExcelUtilities::setFont($sheet->getCell('A'.$j), 10, true);
            $sheet->setCellValue('A'.$j, $date);
            //Border
            $cell = 'A';
            while ($cell != 'N') {
                ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                $cell++;
            }
            $i++;
            //total date
            $sheet->mergeCells('A'.$i.':K'.$i);
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
            $sheet->setCellValue('A'.$i, $this->translator->trans('corrections_report.total').' '.$date);
            $sheet->mergeCells('L'.$i.':M'.$i);
            ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorOne);
            $sheet->setCellValue('L'.$i, number_format($total_date,2));
            $total = $total + $total_date;
            //Border
            $cell = 'A';
            while ($cell != 'N') {
                ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                $cell++;
            }
            $i++;
            $j = $i;
        }

        //total
        $sheet->mergeCells('A'.$i.':K'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colotTotal);
        $sheet->setCellValue('A'.$i, $this->translator->trans('corrections_report.total'));
        $sheet->mergeCells('L'.$i.':M'.$i);
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colotTotal);
        $sheet->setCellValue('L'.$i, $total);
        //Border
        $cell = 'A';
        while ($cell != 'N') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }
        $i++;
        //moyenne
        $sheet->mergeCells('A'.$i.':K'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colotTotal);
        $sheet->setCellValue('A'.$i, $this->translator->trans('corrections_report.moyen'));
        $sheet->mergeCells('L'.$i.':M'.$i);
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colotTotal);
        $sheet->setCellValue('L'.$i, number_format($total/$period,2));
        //Border
        $cell = 'A';
        while ($cell != 'N') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }

        $i = $i + 2;

        //Responsible
        $sheet->mergeCells('A'.$i.':D'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTiltle);
        $sheet->setCellValue('A'.$i, $this->translator->trans('corrections_report.responsible'));
        //number
        $sheet->mergeCells('E'.$i.':F'.$i);
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorTiltle);
        $sheet->setCellValue('E'.$i, $this->translator->trans('corrections_report.number'));
        //perc_number
        $sheet->mergeCells('G'.$i.':K'.$i);
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), $colorTiltle);
        $sheet->setCellValue('G'.$i, $this->translator->trans('corrections_report.percent_number'));
        //perc_amount
        $sheet->mergeCells('L'.$i.':M'.$i);
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorTiltle);
        $sheet->setCellValue('L'.$i, $this->translator->trans('corrections_report.percent_amount'));
        //Border
        $cell = 'A';
        while ($cell != 'N') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }
        $i++;
        //Border
        $cell = 'A';
        while ($cell != 'N') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }
        $total_nbr = 0;
        $total_percent_nbr = 0;
        $total_percent_amount = 0;

        foreach ($result['reportTwo'] as $user => $stats) {
            $total_nbr = $total_nbr + $stats['count'];
            //Responsible
            $sheet->mergeCells('A'.$i.':D'.$i);
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            $sheet->setCellValue('A'.$i, $user);

            //number
            $sheet->mergeCells('E'.$i.':F'.$i);
            ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
            $sheet->setCellValue('E'.$i, $stats['count']);

            //perc_number
            $sheet->mergeCells('G'.$i.':K'.$i);
            ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
            if ($result['totalCount'] > 0) {
                $total_percent_nbr = $total_percent_nbr + (($stats['count'] / $result['totalCount']) * 100);
                $per_number = number_format(($stats['count'] / $result['totalCount']) * 100,2);
                $sheet->setCellValue('G'.$i, $per_number);
            } else {
                $sheet->setCellValue('G'.$i, 0);
            }

            //perc_amount
            $sheet->mergeCells('L'.$i.':M'.$i);
            ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
            if ($result['totalAmount'] > 0) {
                $total_percent_amount = $total_percent_amount + (($stats['amount'] / $result['totalAmount']) * 100);
                $per_amount = number_format(($stats['amount'] / $result['totalAmount']) * 100,2);
                $sheet->setCellValue('L'.$i, $per_amount);
            } else {
                $sheet->setCellValue('L'.$i, 0);
            }
            $i++;
            //Border
            $cell = 'A';
            while ($cell != 'N') {
                ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                $cell++;
            }
        }
        //Total
        $sheet->mergeCells('A'.$i.':D'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorThree);
        $sheet->setCellValue('A'.$i, $this->translator->trans('corrections_report.total'));
        // Total number
        $sheet->mergeCells('E'.$i.':F'.$i);
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorThree);
        $sheet->setCellValue('E'.$i, $total_nbr);
        // total perc_number
        $sheet->mergeCells('G'.$i.':K'.$i);
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), $colorThree);
        $sheet->setCellValue('G'.$i, $total_percent_nbr);
        // total perc_amount
        $sheet->mergeCells('L'.$i.':M'.$i);
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorThree);
        $sheet->setCellValue('L'.$i, $total_percent_amount);

        $i = $i + 2;
        //cashier
        $sheet->mergeCells('A'.$i.':D'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTiltle);
        $sheet->setCellValue('A'.$i, $this->translator->trans('corrections_report.cashier'));
        //number
        $sheet->mergeCells('E'.$i.':F'.$i);
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorTiltle);
        $sheet->setCellValue('E'.$i, $this->translator->trans('corrections_report.number'));
        //perc_number
        $sheet->mergeCells('G'.$i.':K'.$i);
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), $colorTiltle);
        $sheet->setCellValue('G'.$i, $this->translator->trans('corrections_report.percent_number'));
        //perc_amount
        $sheet->mergeCells('L'.$i.':M'.$i);
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorTiltle);
        $sheet->setCellValue('L'.$i, $this->translator->trans('corrections_report.percent_amount'));
        //Border
        $cell = 'A';
        while ($cell != 'N') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }
        $i++;

        $total_nbr = 0;
        $total_percent_nbr = 0;
        $total_percent_amount = 0;

        foreach ($result['reportThree'] as $user => $stats) {
            $total_nbr = $total_nbr + $stats['count'];
            //Responsible
            $sheet->mergeCells('A'.$i.':D'.$i);
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            $sheet->setCellValue('A'.$i, $user);

            //number
            $sheet->mergeCells('E'.$i.':F'.$i);
            ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
            $sheet->setCellValue('E'.$i, $stats['count']);

            //perc_number
            $sheet->mergeCells('G'.$i.':K'.$i);
            ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
            if ($result['totalCount'] > 0) {
                $total_percent_nbr = $total_percent_nbr + (($stats['count'] / $result['totalCount']) * 100);
                $per_number = number_format(($stats['count'] / $result['totalCount']) * 100,2);
                $sheet->setCellValue('G'.$i, $per_number);
            } else {
                $sheet->setCellValue('G'.$i, 0);
            }

            //perc_amount
            $sheet->mergeCells('L'.$i.':M'.$i);
            ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
            if ($result['totalAmount'] > 0) {
                $total_percent_amount = $total_percent_amount + (($stats['amount'] / $result['totalAmount']) * 100);
                $per_amount = number_format(($stats['amount'] / $result['totalAmount']) * 100,2);
                $sheet->setCellValue('L'.$i, $per_amount);
            } else {
                $sheet->setCellValue('L'.$i, 0);
            }
            //Border
            $cell = 'A';
            while ($cell != 'N') {
                ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                $cell++;
            }
            $i++;
        }
        //Total
        $sheet->mergeCells('A'.$i.':D'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorThree);
        $sheet->setCellValue('A'.$i, $this->translator->trans('corrections_report.total'));
        // Total number
        $sheet->mergeCells('E'.$i.':F'.$i);
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorThree);
        $sheet->setCellValue('E'.$i, $total_nbr);
        // total perc_number
        $sheet->mergeCells('G'.$i.':K'.$i);
        ExcelUtilities::setFont($sheet->getCell('G'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), $colorThree);
        $sheet->setCellValue('G'.$i, number_format($total_percent_nbr,2));
        // total perc_amount
        $sheet->mergeCells('L'.$i.':M'.$i);
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorThree);
        $sheet->setCellValue('L'.$i, number_format($total_percent_amount,2));

        //Border
        $cell = 'A';
        while ($cell != 'N') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }
        $filename = "Rapport_corrections_".date('dmY_His').".xls";
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