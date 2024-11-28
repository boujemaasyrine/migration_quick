<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 10/10/2016
 * Time: 10:27
 */

namespace AppBundle\Report\Service;


use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;
use Liuggio\ExcelBundle\Factory;

class ReportDiscountService
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
            $hoursArray[$i] = (($i >= 24) ? ($i - 24) : $i).":00";
        }

        return $hoursArray;
    }

    public function getDiscountList($filter)
    {
        $restaurantId = $this->restaurantService->getCurrentRestaurant()->getId();
        $startHour = (is_null($filter['startHour'])) ? 0 : $filter['startHour'];
        $endHour = (is_null($filter['endHour'])) ? 23 : $filter['endHour'];
        $dateStart = $filter['startDate'];
        $dateEnd = $filter['endDate'];
        $cashier = $filter['cashier'];
        $invoice = $filter['InvoiceNumber'];

            $sql = "
              SELECT
              ticket.invoicenumber           AS invoice_number,
              ticket.date                    AS fiscal_date,
              ticket.enddate                 AS ticket_date,
              quick_user.first_name          AS firstname,
              quick_user.last_name           AS lastname,
              quick_user.matricule           AS matricule,
              sum( ticket_line.discount_ttc) AS discount_ttc,
              ticket.totalttc as total_ttc,
             (sum( ticket_line.discount_ttc) / nullif(SUM (ticket_line.totalttc),0)) AS discount_percentage
              FROM ticket
              JOIN ticket_line ON ticket.id = ticket_line.ticket_id
	          JOIN quick_user ON  ticket.operator= quick_user.wynd_id
	          JOIN user_restaurant ON quick_user.id = user_restaurant.user_id AND restaurant_id  = :restaurant_id 
              WHERE ticket_line.is_discount = TRUE
              AND ticket.origin_restaurant_id = :restaurant_id 
              AND ticket.status <> :canceled
              AND ticket.status <> :abondon
              AND ticket.date BETWEEN :startDate AND :endDate
              AND ticket_line.origin_restaurant_id = :restaurant_id 
              AND ticket_line.status <> :canceled
              AND ticket_line.status <> :abondon
              AND ticket_line.date BETWEEN :startDate AND :endDate
              ";

            if (!is_null($invoice)) {
                $sql .= " AND ticket.invoicenumber = :invoiceNumber";
            }
            if (!is_null($cashier)) {
                $sql .= " AND ticket.operator = :operator";
            }
            if (isset($filter['startHour'])) {
                $sql .= " AND date_part('HOUR',ticket.enddate) >= :startHour ";
            }
            if (isset($filter['endHour'])) {
                $sql .= " AND date_part('HOUR',ticket.enddate) <= :endHour ";
            }

            $sql .= " GROUP BY ticket.invoicenumber, ticket.enddate, quick_user.first_name, quick_user.last_name , quick_user.matricule, ticket.totalttc, ticket.date ORDER BY quick_user.first_name,quick_user.last_name ";

            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('restaurant_id', $restaurantId);
            $start = $dateStart->format('Y-m-d');
            $stm->bindParam('startDate', $start);
            $end = $dateEnd->format('Y-m-d');
            $stm->bindParam('endDate', $end);
            $canceled = Ticket::CANCEL_STATUS_VALUE;
            $abondon = Ticket::ABONDON_STATUS_VALUE;
            $stm->bindParam('canceled', $canceled);
            $stm->bindParam('abondon', $abondon);

            if (!is_null($invoice)) {
                $stm->bindParam('invoiceNumber', $invoice);
            }
            if (!is_null($cashier)) {
                $wyndId = $cashier->getWyndId();
                $stm->bindParam('operator', $wyndId);
            }
            if (isset($filter['startHour'])) {
                $stm->bindParam('startHour', $filter['startHour']);
            }
            if (isset($filter['endHour'])) {
                $stm->bindParam('endHour', $filter['endHour']);
            }

            $stm->execute();
            $results = $stm->fetchAll();

        if (!is_null($cashier)) {
            $cashierName = $cashier->getFirstName()." ".$cashier->getLastName();
        } else {
            $cashierName = "Tous";
        }

        $output['report'] = $this->serializeList($results, $filter);
        $output['startDate'] = $filter['startDate']->format('d-m-Y');
        $output['endDate'] = $filter['endDate']->format('d-m-Y');
        $output['startHour'] = $startHour;
        $output['endHour'] = $endHour;
        $output['cashier'] = $cashierName;
        $output['discountMin'] = $filter['discountPerCentMin'];
        $output['discountMax'] = $filter['discountPerCentMax'];

        return $output;
    }


    public function serializeList($data, $filter)
    {
        $list = array();
        foreach ($data as $element) {
            $tmpLine = array();
            $tmpLine['discountTTC'] = 0;
            $tmpLine['discountTTC'] = $element['discount_ttc'];
            $tmpLine['invoiceNumber'] = $element['invoice_number'];
            $tmpDate = new \DateTime($element['ticket_date']);
            $tmpLine['date'] = $element['fiscal_date'];
            $tmpLine['hour'] = $tmpDate->format('H:i:s');

            $tmpLine['cashier'] = $element['firstname']." ".$element['lastname'];
            $tmpLine['matricule'] = $element['matricule'];
            $tmpLine['amount'] = $element['total_ttc'] + ABS($element['discount_ttc']);
            $tmpLine['discountPerCent']=$tmpLine['amount'] > 0 ? $tmpLine['discountTTC']/$tmpLine['amount'] : 0;
//            $tmpLine['discountPerCent'] = (is_null($element['discount_percentage'])) ? 0 : floatval(
//                $element['discount_percentage']
//            );
            if (is_null($filter['discountPerCentMin']) && is_null($filter['discountPerCentMax'])) {
                $list[] = $tmpLine;
            }
            else {
                if (is_null($filter['discountPerCentMin']) && !is_null($filter['discountPerCentMax'])) {
                    if (floatval(number_format(abs($tmpLine['discountPerCent'])*100)) <= floatval($filter['discountPerCentMax'])) {
                        $list[] = $tmpLine;
                    }
                } else {
                    if (!is_null($filter['discountPerCentMin']) && is_null($filter['discountPerCentMax'])) {
                        if (floatval(number_format(abs($tmpLine['discountPerCent'])*100)) >= floatval($filter['discountPerCentMin'])) {
                            $list[] = $tmpLine;
                        }
                    } else {
                        if (floatval(number_format(abs($tmpLine['discountPerCent'])*100)) >= floatval($filter['discountPerCentMin'] ) && floatval(number_format(abs($tmpLine['discountPerCent'])*100)) <= floatval($filter['discountPerCentMax'])) {
                            $list[] = $tmpLine;
                        }
                    }
                }
            }
        }

        return $list;
    }


    public function findCashier($id)
    {
        $result = $this->em->getRepository('Staff:Employee')->findBy(array('wyndId' => $id));
        if (is_null($result)) {
            $output['name'] = "";
            $output['matricule'] = "";
        } else {
            $output['name'] = $result[0]->getFirstName()." ".$result[0]->getLastName();
            $output['matricule'] = $result[0]->getGlobalEmployeeID();
        }

        return $output;
    }


    public function generateExcelFile($result, $filter, Restaurant $currentRestaurant, $logoPath)
    {

        $colorOne = "ECECEC";
        $colorTwo = "E5CFAB";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet
            ->getHeaderFooter()->setOddFooter('&R&F Page &P / &N');
        $sheet
            ->getHeaderFooter()->setEvenFooter('&R&F Page &P / &N');
        $sheet->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->setTitle(substr($this->translator->trans('discount_report.discount_report'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('discount_report.discount_report');
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

        //reduction max
        $sheet->mergeCells("E13:G13");
        ExcelUtilities::setFont($sheet->getCell('E13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E13"), $colorOne);
        $sheet->setCellValue('E13', $this->translator->trans('discount_report.discount_max').":");

        ExcelUtilities::setFont($sheet->getCell('H13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H13"), $colorOne);
        $sheet->setCellValue('H13', $filter['discountPerCentMax']);

        //reduction min
        $sheet->mergeCells("I13:K13");
        ExcelUtilities::setFont($sheet->getCell('I13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I13"), $colorOne);
        $sheet->setCellValue('I13', $this->translator->trans('discount_report.discount_min').":");

        ExcelUtilities::setFont($sheet->getCell('L13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L13"), $colorOne);
        $sheet->setCellValue('L13', $filter['discountPerCentMin']);


        //Content
        $i = 15;
        //Invoice number
        $sheet->mergeCells('A'.$i.':B'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('discount_report.invoice_number'));
        //Date
        $sheet->mergeCells('C'.$i.':D'.$i);
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorOne);
        $sheet->setCellValue('C'.$i, $this->translator->trans('discount_report.date'));
        //Hour
        ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorOne);
        $sheet->setCellValue('E'.$i, $this->translator->trans('discount_report.hour'));
        //Cashier
        $sheet->mergeCells('F'.$i.':G'.$i);
        ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorOne);
        $sheet->setCellValue('F'.$i, $this->translator->trans('discount_report.cashier'));

        //Discount value
        $sheet->mergeCells('H'.$i.':I'.$i);
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorOne);
        $sheet->setCellValue('H'.$i, $this->translator->trans('discount_report.discount_value'));

        //Before discount
        $sheet->mergeCells('J'.$i.':K'.$i);
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), $colorOne);
        $sheet->setCellValue('J'.$i, $this->translator->trans('discount_report.before_discount'));

        //Perc discount
        $sheet->mergeCells('L'.$i.':M'.$i);
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorOne);
        $sheet->setCellValue('L'.$i, $this->translator->trans('discount_report.discount_per_cent'));

        //Border
        $cell = 'A';
        while ($cell != 'N') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }

        //Content
        $i = 16;
        $total = 0;
        $totalDiscount = 0;
        foreach ($result['report'] as $line) {

            $total = $total + $line['amount'];
            $totalDiscount = $totalDiscount + $line['discountTTC'];
            //Invoice number
            $sheet->mergeCells('A'.$i.':B'.$i);
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            $sheet->setCellValue('A'.$i, $line['invoiceNumber']);
            ExcelUtilities::setFormat($sheet->getCell('A'.$i),\PHPExcel_Cell_DataType::TYPE_STRING);
            //$sheet->getStyle('A'.$i)->getNumberFormat()->applyFromArray(array('code' => \PHPExcel_Style_NumberFormat::FORMAT_NUMBER));
            //Date
            $sheet->mergeCells('C'.$i.':D'.$i);
            ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
            $sheet->setCellValue('C'.$i, $line['date']);
            //Hour
            ExcelUtilities::setFont($sheet->getCell('E'.$i), 10, true);
            $sheet->setCellValue('E'.$i, $line['hour']);
            //Cashier
            $sheet->mergeCells('F'.$i.':G'.$i);
            ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
            $sheet->setCellValue('F'.$i, $line['cashier']);

            //Discount value
            $sheet->mergeCells('H'.$i.':I'.$i);
            ExcelUtilities::setFormat($sheet->getCell('H'.$i), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
            $sheet->setCellValue('H'.$i, $line['discountTTC']);

            //Before discount
            $sheet->mergeCells('J'.$i.':K'.$i);
            ExcelUtilities::setFormat($sheet->getCell('J'.$i), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
            $sheet->setCellValue('J'.$i, $line['amount']);

            //Perc discount
            $sheet->mergeCells('L'.$i.':M'.$i);
            ExcelUtilities::setFormat($sheet->getCell('L'.$i), \PHPExcel_Cell_DataType::TYPE_NUMERIC);
            ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
            $sheet->setCellValue('L'.$i, number_format($line['discountPerCent'] * 100));

            //Border
            $cell = 'A';
            while ($cell != 'N') {
                ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                $cell++;
            }
            $i++;
        }
        $sheet->mergeCells('A'.$i.':G'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('discount_report.total'));
        $sheet->mergeCells('H'.$i.':I'.$i);
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorOne);
        $sheet->setCellValue('H'.$i, $totalDiscount);
        $sheet->mergeCells('J'.$i.':K'.$i);
        ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J".$i), $colorOne);
        $sheet->setCellValue('J'.$i, $total);
        $sheet->mergeCells('L'.$i.':M'.$i);
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorOne);
//        $sheet->setCellValue('L'.$i, $total);

        $filename = "Rapport_reduction_".date('dmY_His').".xls";
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