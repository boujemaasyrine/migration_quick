<?php

namespace AppBundle\Supervision\Service\Reports;

use Doctrine\ORM\EntityManager;
use AppBundle\Merchandise\Repository\ProductPurchasedRepository;
use AppBundle\Supervision\Utils\Utilities;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class ProductPurchasedMvmtService
{

    private $em;
    private $translator;
    private $phpExcel;

    public function __construct(EntityManager $em, Translator $translator, Factory $factory)
    {
        $this->em = $em;
        $this->translator = $translator;
        $this->phpExcel = $factory;
    }

    public function getProductPurchasedReportExcelFile($beginDate, $endDate, $restaurants, $logoPath)
    {
        /**
         * @var ProductPurchasedRepository $ppRepo
         */
        $ppRepo = $this->em->getRepository("Merchandise:ProductPurchased");
        list($dataPPMVMTDelivery, $dataPPMVMTTransfer) = $ppRepo->getDataForProductPurchasedReport($beginDate, $endDate, $restaurants);
        $startDate = $beginDate;
        $endDate = $endDate;
        $topHeaderColor = "CA9E67";
        $colorOne = "ECECEC";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();

        //FILTER ZONE
        // START DATE
        ExcelUtilities::setFont($sheet->getCell('A1'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A1"), $colorOne);
        $sheet->setCellValue('A1', $this->translator->trans('keyword.from') . ":");
        $sheet->mergeCells("B1:C1");
        ExcelUtilities::setFont($sheet->getCell('B1'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B1"), $colorOne);
        $sheet->setCellValue('B1', $startDate->format('d-m-Y'));
        // END DATE
        ExcelUtilities::setFont($sheet->getCell('D1'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D1"), $colorOne);
        $sheet->setCellValue('D1', $this->translator->trans('keyword.to') . ":");
        $sheet->mergeCells("E1:F1");
        ExcelUtilities::setFont($sheet->getCell('E1'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E1"), $colorOne);
        $sheet->setCellValue('E1', $endDate->format('d-m-Y'));

        //CONTENT
        $startCell = 0;
        $startLine = 2;
        $sheet->getRowDimension($startLine)->setRowHeight(17);
        // top headers
        //num resto
        $this->setHeaderCellContent($sheet, $startCell, $startLine, $alignmentH, $topHeaderColor, $this->translator->trans('report.product_purchase.restaurant_number', array(), 'supervision'));
        $startCell += 2;
        //supplier
        $this->setHeaderCellContent($sheet, $startCell, $startLine, $alignmentH, $topHeaderColor, $this->translator->trans('keyword.supplier', array(), 'supervision'));
        $startCell += 2;
        //order_date
        $this->setHeaderCellContent($sheet, $startCell, $startLine, $alignmentH, $topHeaderColor, $this->translator->trans('report.product_purchase.order_date', array(), 'supervision'));
        $startCell += 2;
        //type
        $this->setHeaderCellContent($sheet, $startCell, $startLine, $alignmentH, $topHeaderColor, $this->translator->trans('label.type', array(), 'supervision'));
        $startCell += 2;
        //delibery_number
        $this->setHeaderCellContent($sheet, $startCell, $startLine, $alignmentH, $topHeaderColor, $this->translator->trans('report.product_purchase.delivery_number', array(), 'supervision'));
        $startCell += 2;
        //delivery_date
        $this->setHeaderCellContent($sheet, $startCell, $startLine, $alignmentH, $topHeaderColor, $this->translator->trans('report.product_purchase.delivery_date', array(), 'supervision'));
        $startCell += 2;
        //transfer_number
        $this->setHeaderCellContent($sheet, $startCell, $startLine, $alignmentH, $topHeaderColor, $this->translator->trans('report.product_purchase.transfer_number', array(), 'supervision'));
        $startCell += 2;
        //delivery_date
        $this->setHeaderCellContent($sheet, $startCell, $startLine, $alignmentH, $topHeaderColor, $this->translator->trans('report.product_purchase.transfer_date', array(), 'supervision'));
        $startCell += 2;
        //item_number
        $this->setHeaderCellContent($sheet, $startCell, $startLine, $alignmentH, $topHeaderColor, $this->translator->trans('report.product_purchase.item_number', array(), 'supervision'));
        $startCell += 2;
        //item_decription
        $this->setHeaderCellContent($sheet, $startCell, $startLine, $alignmentH, $topHeaderColor, $this->translator->trans('report.product_purchase.item_description', array(), 'supervision'));
        $startCell += 2;
        //Quantity
        $this->setHeaderCellContent($sheet, $startCell, $startLine, $alignmentH, $topHeaderColor, $this->translator->trans('report.product_purchase.quantity', array(), 'supervision'));
        $startCell += 2;
        //price
        $this->setHeaderCellContent($sheet, $startCell, $startLine, $alignmentH, $topHeaderColor, $this->translator->trans('keyword.price', array(), 'supervision'));
        $startCell += 2;
        //value
        $this->setHeaderCellContent($sheet, $startCell, $startLine, $alignmentH, $topHeaderColor, $this->translator->trans('labels.value', array(), 'supervision'));

        //body
        foreach ($dataPPMVMTDelivery as $dataDelivery) {
            $startCell = 0;
            $startLine++;
            $sheet->getRowDimension($startLine)->setRowHeight(17);
            //num resto
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataDelivery['rcode']);
            $startCell += 2;
            //Supplier
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataDelivery['sname'] . '(' . $dataDelivery['scode'] . ')');
            $startCell += 2;
            //order date
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataDelivery['odateorder']);
            $startCell += 2;
            //type
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $this->translator->trans('report.product_purchase.' . $dataDelivery['ppmvmttype'], array(), 'supervision'));
            $startCell += 2;
            //delivery_number
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataDelivery['dbordereau']);
            $startCell += 2;
            //delivery_date
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataDelivery['ddate']);
            $startCell += 2;
            //transfer_number
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, '');
            $startCell += 2;
            //transfer_date
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, '');
            $startCell += 2;
            //item_number
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataDelivery['ppexternalid']);
            $startCell += 2;
            //item_description
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataDelivery['pname']);
            $startCell += 2;
            //Quantity
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataDelivery['ppmvmtvariation']);
            $startCell += 2;
            //price
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataDelivery['ppmvmtbuyingcost']);
            $startCell += 2;
            //value
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, (float)$dataDelivery['ppmvmtbuyingcost'] * (float)$dataDelivery['ppmvmtvariation']);
            $startCell += 2;

        }

        foreach ($dataPPMVMTTransfer as $dataTransfer) {
            $startCell = 0;
            $startLine++;
            $sheet->getRowDimension($startLine)->setRowHeight(17);
            //num resto
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataTransfer['rcode']);
            $startCell += 2;
            //Supplier
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, '');
            $startCell += 2;
            //order date
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, '');
            $startCell += 2;
            //type
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $this->translator->trans('report.product_purchase.' . $dataTransfer['ppmvmttype'], array(), 'supervision'));
            $startCell += 2;
            //delivery_number
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, '');
            $startCell += 2;
            //delivery_date
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, '');
            $startCell += 2;
            //transfer_number
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataTransfer['tnumtransfer'], true);
            $startCell += 2;
            //transfer_date
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataTransfer['tdate']);
            $startCell += 2;
            //item_number
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataTransfer['ppexternalid']);
            $startCell += 2;
            //item_description
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataTransfer['pname']);
            $startCell += 2;
            //Quantity
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataTransfer['ppmvmtvariation']);
            $startCell += 2;
            //price
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, $dataTransfer['ppmvmtbuyingcost']);
            $startCell += 2;
            //value
            $sheet = $this->setCellContent($sheet, $startCell, $startLine, $alignmentH, (float)$dataTransfer['ppmvmtbuyingcost'] * (float)$dataTransfer['ppmvmtvariation']);
            $startCell += 2;
        }
        $startCell += 2;
        ExcelUtilities::setBorder($sheet->getStyle($this->getNameFromNumber($startCell) . $startLine . ":" . $this->getNameFromNumber($startCell + 7) . $startLine));

        $filename = "Rapport_Achat_produits" . date('dmY_His') . ".xls";
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

    private function getNameFromNumber($num)
    {
        $numeric = $num % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval($num / 26);
        if ($num2 > 0) {
            return $this->getNameFromNumber($num2 - 1) . $letter;
        } else {
            return $letter;
        }
    }

    private function setHeaderCellContent($sheet, $startCell, $startLine, $alignmentH, $topHeaderColor, $data)
    {

        $sheet->mergeCells($this->getNameFromNumber($startCell) . $startLine . ":" . $this->getNameFromNumber($startCell + 1) . $startLine);
        $sheet->setCellValue($this->getNameFromNumber($startCell) . $startLine, $data);
        ExcelUtilities::setFont($sheet->getCell($this->getNameFromNumber($startCell) . $startLine), 7, true);
        ExcelUtilities::setCellAlignment($sheet->getCell($this->getNameFromNumber($startCell) . $startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle($this->getNameFromNumber($startCell) . $startLine . ":" . $this->getNameFromNumber($startCell + 1) . $startLine));
        ExcelUtilities::setBackgroundColor($sheet->getCell($this->getNameFromNumber($startCell) . $startLine), $topHeaderColor);
        return $sheet;
    }

    private function setCellContent($sheet, $startCell, $startLine, $alignmentH, $data, $isTNum = false)
    {
        $cellIndex = $this->getNameFromNumber($startCell) . $startLine . ":" . $this->getNameFromNumber($startCell + 1) . $startLine;
        $sheet->mergeCells($cellIndex);
        $sheet->setCellValue($this->getNameFromNumber($startCell) . $startLine, $data);
        if ($isTNum) {
            $sheet->getStyle($cellIndex)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        }
        ExcelUtilities::setFont($sheet->getCell($this->getNameFromNumber($startCell) . $startLine), 7, true);
        ExcelUtilities::setCellAlignment($sheet->getCell($this->getNameFromNumber($startCell) . $startLine), $alignmentH);
        ExcelUtilities::setBorder($sheet->getStyle($cellIndex));
        return $sheet;
    }
}