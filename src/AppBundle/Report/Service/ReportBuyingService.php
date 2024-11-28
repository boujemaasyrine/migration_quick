<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 22/03/2016
 * Time: 08:39
 */

namespace AppBundle\Report\Service;

use AppBundle\Merchandise\Entity\Delivery;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Returns;
use AppBundle\Merchandise\Entity\Transfer;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class ReportBuyingService
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

    public function generateInOutReport($filter)
    {
        $result = array();

        if (empty($filter['type']) || in_array('delivery', $filter['type'])) {
            $divisionsRaw['delivery'] = $this->em->getRepository(Delivery::class)->getFiltredDeliveries($filter);
        }

        if (empty($filter['type']) || in_array('return', $filter['type'])) {
            $divisionsRaw['returns'] = $this->em->getRepository(Returns::class)->getFiltredReturns($filter);
        }

        if (empty($filter['type']) || in_array('delivery', $filter['type'])) {
            $i = 0;
            foreach ($divisionsRaw['delivery'] as $raw) {
                $j = 0;
                $init = 0;
                foreach ($divisionsRaw['delivery'] as $secondRawKey => $secondRaw) {
                    if ($raw['supplierid'] == $secondRaw['supplierid']) {
                        $this->setSupplierLine($divisionsRaw, $result, $i, $j, $init, $raw, $secondRaw, $secondRawKey);
                    }
                }

                if (empty($filter['type']) || in_array('return', $filter['type'])) {
                    foreach ($divisionsRaw['returns'] as $secondRawKey => $secondRaw) {
                        if ($raw['supplierid'] == $secondRaw['supplierid']) {
                            $this->setReturn($result, $i, $raw, $j, $secondRaw, $init, $divisionsRaw, $secondRawKey);
                        }
                    }
                }
                $i++;
            }
        }

        if (empty($filter['type']) || in_array('return', $filter['type'])) {
            if (!isset($i)) {
                $i = 0;
            }
            foreach ($divisionsRaw['returns'] as $raw) {
                $j = 0;
                $init = 0;

                foreach ($divisionsRaw['returns'] as $secondRawKey => $secondRaw) {
                    if ($raw['supplierid'] == $secondRaw['supplierid']) {
                        $this->setReturn($result, $i, $raw, $j, $secondRaw, $init, $divisionsRaw, $secondRawKey);
                    }
                }
                $i++;
            }
        }

        if (empty($filter['type']) || in_array('transferIn', $filter['type'])) {
            $divisionsRaw['transfers_in'] = $this->em->getRepository(Transfer::class)->getFiltredTransfers(
                $filter,
                'transfer_in'
            );
        }

        if (empty($filter['type']) || in_array('transferOut', $filter['type'])) {
            $divisionsRaw['transfers_out'] = $this->em->getRepository('Merchandise:Transfer')->getFiltredTransfers(
                $filter,
                'transfer_out'
            );
        }


        if (empty($filter['type']) || in_array('transferIn', $filter['type'])) {
            $k = 0;
            foreach ($divisionsRaw['transfers_in'] as $raw) {
                $j = 0;
                $init = 0;
                foreach ($divisionsRaw['transfers_in'] as $secondRawKey => $secondRaw) {
                    if ($raw['restaurantid'] == $secondRaw['restaurantid']) {
                        $this->setTransferIn($result, $k, $raw, $j, $secondRaw, $init, $divisionsRaw, $secondRawKey);
                    }
                }

                if (empty($filter['type']) || in_array('transferOut', $filter['type'])) {
                    foreach ($divisionsRaw['transfers_out'] as $secondRawKey => $secondRaw) {
                        if ($raw['restaurantid'] == $secondRaw['restaurantid']) {
                            $this->setTransferOut(
                                $result,
                                $k,
                                $raw,
                                $j,
                                $secondRaw,
                                $init,
                                $divisionsRaw,
                                $secondRawKey
                            );
                        }
                    }
                }

                $k++;
            }
        }

        if (empty($filter['type']) || in_array('transferOut', $filter['type'])) {
            if (!isset($k)) {
                $k = 0;
            }
            foreach ($divisionsRaw['transfers_out'] as $raw) {
                $j = 0;
                $init = 0;
                foreach ($divisionsRaw['transfers_out'] as $secondRawKey => $secondRaw) {
                    if ($raw['restaurantid'] == $secondRaw['restaurantid']) {
                        $this->setTransferOut($result, $k, $raw, $j, $secondRaw, $init, $divisionsRaw, $secondRawKey);
                    }
                }

                $k++;
            }
        }

        return $result;
    }

    public function setSupplierLine(&$divisionsRaw, &$result, $i, &$j, &$init, $raw, $secondRaw, $secondRawKey)
    {
        $result['delivery'][$i]['name'] = $raw['suppliername'];
        $result['delivery'][$i][$j]['invoice'] = $secondRaw['invoice'];
        $result['delivery'][$i][$j]['date'] = $secondRaw['deliverydate'];
        $result['delivery'][$i][$j]['valorization'] = $secondRaw['valorization'];
        $init = $init + $secondRaw['valorization'];
        $result['delivery'][$i]['totalValorization'] = $init;
        unset($divisionsRaw['delivery'][$secondRawKey]);
        $j++;
    }

    public function setReturn(&$result, $i, $raw, &$j, $secondRaw, &$init, &$divisionsRaw, $secondRawKey)
    {
        $result['delivery'][$i]['name'] = $raw['suppliername'];
        $result['delivery'][$i][$j]['date'] = $secondRaw['returndate'];
        $result['delivery'][$i][$j]['invoice'] = $this->translator->trans(
            'keyword.prefix_return'
        ).'-'.$secondRaw['returndate'].'-'.$secondRaw['returnid'];
        $result['delivery'][$i][$j]['valorization'] = -$secondRaw['valorization'];
        $init = $init - $secondRaw['valorization'];
        $result['delivery'][$i]['totalValorization'] = $init;
        unset($divisionsRaw['returns'][$secondRawKey]);
        $j++;
    }

    public function setTransferIn(&$result, $k, $raw, &$j, $secondRaw, &$init, &$divisionsRaw, $secondRawKey)
    {

        $result['transfer'][$k]['name'] = $raw['restaurantname'].' ['.$raw['code'].']';
        $result['transfer'][$k][$j]['invoice'] = $this->translator->trans('keyword.transferIn').'-'.$secondRaw['transferdate'].'-'.$secondRaw['invoice'];
        $result['transfer'][$k][$j]['date'] = $secondRaw['transferdate'];
        $result['transfer'][$k][$j]['valorization'] = $secondRaw['valorization'];
        $init = $init + $secondRaw['valorization'];
        $result['transfer'][$k]['totalValorization'] = $init;
        unset($divisionsRaw['transfers_in'][$secondRawKey]);
        $j++;
    }

    public function setTransferOut(&$result, $k, $raw, &$j, $secondRaw, &$init, &$divisionsRaw, $secondRawKey)
    {

        $result['transfer'][$k]['name'] = $raw['restaurantname'].' ['.$raw['code'].']';
        $result['transfer'][$k][$j]['invoice'] = $secondRaw['invoice'];
        $result['transfer'][$k][$j]['date'] = $secondRaw['transferdate'];
        $result['transfer'][$k][$j]['valorization'] = -$secondRaw['valorization'];
        $init = $init - $secondRaw['valorization'];
        $result['transfer'][$k]['totalValorization'] = $init;
        unset($divisionsRaw['transfers_out'][$secondRawKey]);
        $j++;
    }

    public function serializeInOutReportResult($result)
    {
        $serializedResult = [];
        foreach ($result as $array) {
            foreach ($array as $line) {
                $serializedResult[]['0'] = $line['name'];
                for ($i = 0; $i <= count($line) - 3; $i++) {
                    $serializedResult[] = [
                        '0' => '',
                        '1' => $line[$i]['invoice'],
                        '2' => substr($line[$i]['date'], 0, 10),
                        '3' => $line[$i]['valorization'],
                    ];
                }
                $serializedResult[] = [
                    '0' => '',
                    '1' => '',
                    '2' => '',
                    '3' => $line['totalValorization'],
                ];
            }
        }

        return $serializedResult;
    }

    public function generateExcelFile($result, $filter, Restaurant $currentRestaurant, $logoPath)
    {
        $colorOne = "ECECEC";
        $colorTwo = "E5CFAB";
        $colorThree = "C5923F";
        $colorFour = "FCEF01";
        $colorFive = "FFFCC0";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('report.buying.in_out_title'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('report.buying.in_out_title');
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
        $sheet->mergeCells("A10:B10");
        ExcelUtilities::setFont($sheet->getCell('A10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A10"), $colorOne);
        $sheet->setCellValue('A10', $this->translator->trans('keyword.from').":");
        $sheet->mergeCells("C10:D10");
        ExcelUtilities::setFont($sheet->getCell('C10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C8"), $colorOne);
        $sheet->setCellValue('C10', $filter['beginDate']);


        // END DATE
        $sheet->mergeCells("A11:B11");
        ExcelUtilities::setFont($sheet->getCell('A11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A11"), $colorOne);
        $sheet->setCellValue('A11', $this->translator->trans('keyword.to').":");
        $sheet->mergeCells("C11:D11");
        ExcelUtilities::setFont($sheet->getCell('C11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C11"), $colorOne);
        $sheet->setCellValue('C11', $filter['endDate']);

        // TYPES
        $sheet->mergeCells("E10:E11");
        ExcelUtilities::setFont($sheet->getCell('A11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E10"), $colorOne);
        $sheet->setCellValue('E10', $this->translator->trans('label.type'));
        ExcelUtilities::setCellAlignment($sheet->getCell("E10"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("E10"), $alignmentV);
        //    Livraisons
        $sheet->mergeCells("F10:G10");
        ExcelUtilities::setFont($sheet->getCell('F10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F10"), $colorOne);
        $sheet->setCellValue('F10', $this->translator->trans('keyword.delivery').":");

        ExcelUtilities::setFont($sheet->getCell('H10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H10"), $colorOne);
        if (!isset($filter['type']) || in_array('delivery', $filter['type'])) {
            $sheet->setCellValue('H10', '✔');
        } else {
            $sheet->setCellValue('H10', '---');
        }
        //    Returns
        $sheet->mergeCells("F11:G11");
        ExcelUtilities::setFont($sheet->getCell('F11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F11"), $colorOne);
        $sheet->setCellValue('F11', $this->translator->trans('keyword.return').":");

        ExcelUtilities::setFont($sheet->getCell('H11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H11"), $colorOne);
        if (!isset($filter['type']) || in_array('return', $filter['type'])) {
            $sheet->setCellValue('H11', '✔');
        } else {
            $sheet->setCellValue('H11', '---');
        }

        // BLANC
        $sheet->mergeCells("I10:I11");
        ExcelUtilities::setBackgroundColor($sheet->getCell("i8"), $colorOne);
        //    Transfer in
        $sheet->mergeCells("J10:K10");
        ExcelUtilities::setFont($sheet->getCell('J10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J10"), $colorOne);
        $sheet->setCellValue('J10', $this->translator->trans('keyword.transferIn').":");

        ExcelUtilities::setFont($sheet->getCell('L10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L10"), $colorOne);
        if (!isset($filter['type']) || in_array('transferIn', $filter['type'])) {
            $sheet->setCellValue('L10', '✔');
        } else {
            $sheet->setCellValue('L10', '---');
        }

        //    Transfer out
        $sheet->mergeCells("J11:K11");
        ExcelUtilities::setFont($sheet->getCell('J11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J11"), $colorOne);
        $sheet->setCellValue('J11', $this->translator->trans('keyword.transferOut').":");

        ExcelUtilities::setFont($sheet->getCell('L11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L11"), $colorOne);
        if (!isset($filter['type']) || in_array('transferOut', $filter['type'])) {
            $sheet->setCellValue('L11', '✔');
        } else {
            $sheet->setCellValue('L11', '---');
        }

        //BODY
        $i = 15;
        if (isset($result['delivery'])) {
            foreach ($result['delivery'] as $supplier) {
                $lines = sizeof($supplier);
                $k = $i + 1;
                $sheet->mergeCells("A".$i.":L".$k);
                ExcelUtilities::setFont($sheet->getCell("A".$i), 11, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                $sheet->setCellValue("A".$i, $supplier['name']);
                ExcelUtilities::setCellAlignment($sheet->getCell("A".$i), $alignmentH);
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A".$i), $alignmentV);
                $i = $i + 2;
                //INVOICE HEADER
                $sheet->mergeCells("A".$i.":D".$i);
                ExcelUtilities::setFont($sheet->getCell("A".$i), 11, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
                $sheet->setCellValue("A".$i, $this->translator->trans('keyword.invoice'));
                ExcelUtilities::setBorder($sheet->getCell('A'.$i));
                ExcelUtilities::setBorder($sheet->getCell('B'.$i));
                ExcelUtilities::setBorder($sheet->getCell('C'.$i));
                ExcelUtilities::setBorder($sheet->getCell('D'.$i));

                //INVOICE DATE
                $sheet->mergeCells("E".$i.":H".$i);
                ExcelUtilities::setFont($sheet->getCell("E".$i), 11, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorOne);
                $sheet->setCellValue("E".$i, $this->translator->trans('keyword.date'));
                ExcelUtilities::setBorder($sheet->getCell('E'.$i));
                ExcelUtilities::setBorder($sheet->getCell('F'.$i));
                ExcelUtilities::setBorder($sheet->getCell('G'.$i));
                ExcelUtilities::setBorder($sheet->getCell('H'.$i));

                //INVOICE TOTAL
                $sheet->mergeCells("I".$i.":L".$i);
                ExcelUtilities::setFont($sheet->getCell("I".$i), 11, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), $colorOne);
                $sheet->setCellValue("I".$i, $this->translator->trans('keyword.total'));
                ExcelUtilities::setBorder($sheet->getCell('I'.$i));
                ExcelUtilities::setBorder($sheet->getCell('J'.$i));
                ExcelUtilities::setBorder($sheet->getCell('K'.$i));
                ExcelUtilities::setBorder($sheet->getCell('L'.$i));
                $i++;
                for ($index = 0; $index <= ($lines - 3); $index++) {
                    $sheet->mergeCells("A".$i.":D".$i);
                    ExcelUtilities::setFont($sheet->getCell("A".$i), 11, true);
                    $sheet->setCellValue("A".$i, $supplier[$index]['invoice']);
                    ExcelUtilities::setBorder($sheet->getCell('A'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('B'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('C'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('D'.$i));

                    //INVOICE DATE
                    $sheet->mergeCells("E".$i.":H".$i);
                    ExcelUtilities::setFont($sheet->getCell("E".$i), 11, true);
                    $value = new \DateTime($supplier[$index]['date']);
                    $sheet->setCellValue("E".$i, $value->format('d/m/Y'));
                    ExcelUtilities::setBorder($sheet->getCell('E'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('F'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('G'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('H'.$i));

                    //INVOICE TOTAL
                    $sheet->mergeCells("I".$i.":L".$i);
                    ExcelUtilities::setFont($sheet->getCell("I".$i), 11, true);
                    $sheet->setCellValue("I".$i, $supplier[$index]['valorization']);
                    ExcelUtilities::setBorder($sheet->getCell('I'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('J'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('K'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('L'.$i));
                    $i++;
                }
                //TOTAL
                $sheet->mergeCells("A".$i.":D".$i);
                ExcelUtilities::setFont($sheet->getCell("A".$i), 11, true);
                ExcelUtilities::setBorder($sheet->getCell('A'.$i));
                ExcelUtilities::setBorder($sheet->getCell('B'.$i));
                ExcelUtilities::setBorder($sheet->getCell('C'.$i));
                ExcelUtilities::setBorder($sheet->getCell('D'.$i));

                //INVOICE DATE
                $sheet->mergeCells("E".$i.":H".$i);
                ExcelUtilities::setFont($sheet->getCell("E".$i), 11, true);
                ExcelUtilities::setBorder($sheet->getCell('E'.$i));
                ExcelUtilities::setBorder($sheet->getCell('F'.$i));
                ExcelUtilities::setBorder($sheet->getCell('G'.$i));
                ExcelUtilities::setBorder($sheet->getCell('H'.$i));

                //INVOICE TOTAL
                $sheet->mergeCells("I".$i.":L".$i);
                ExcelUtilities::setFont($sheet->getCell("I".$i), 11, true);
                $sheet->setCellValue("I".$i, $supplier['totalValorization']);
                ExcelUtilities::setBorder($sheet->getCell('I'.$i));
                ExcelUtilities::setBorder($sheet->getCell('J'.$i));
                ExcelUtilities::setBorder($sheet->getCell('K'.$i));
                ExcelUtilities::setBorder($sheet->getCell('L'.$i));
                $i += 2;
            }
        }//END DELIVERY

        if (isset($result['transfer'])) {
            foreach ($result['transfer'] as $restaurantTransfers) {
                $lines = sizeof($restaurantTransfers);
                $k = $i + 1;
                $sheet->mergeCells("A".$i.":L".$k);
                ExcelUtilities::setFont($sheet->getCell("A".$i), 11, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                $sheet->setCellValue("A".$i, $restaurantTransfers['name']);
                ExcelUtilities::setCellAlignment($sheet->getCell("A".$i), $alignmentH);
                ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A".$i), $alignmentV);
                $i = $i + 2;
                //INVOICE HEADER
                $sheet->mergeCells("A".$i.":D".$i);
                ExcelUtilities::setFont($sheet->getCell("A".$i), 11, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
                $sheet->setCellValue("A".$i, $this->translator->trans('keyword.invoice'));
                ExcelUtilities::setBorder($sheet->getCell('A'.$i));
                ExcelUtilities::setBorder($sheet->getCell('B'.$i));
                ExcelUtilities::setBorder($sheet->getCell('C'.$i));
                ExcelUtilities::setBorder($sheet->getCell('D'.$i));

                //INVOICE DATE
                $sheet->mergeCells("E".$i.":H".$i);
                ExcelUtilities::setFont($sheet->getCell("E".$i), 11, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("E".$i), $colorOne);
                $sheet->setCellValue("E".$i, $this->translator->trans('keyword.date'));
                ExcelUtilities::setBorder($sheet->getCell('E'.$i));
                ExcelUtilities::setBorder($sheet->getCell('F'.$i));
                ExcelUtilities::setBorder($sheet->getCell('G'.$i));
                ExcelUtilities::setBorder($sheet->getCell('H'.$i));

                //INVOICE TOTAL
                $sheet->mergeCells("I".$i.":L".$i);
                ExcelUtilities::setFont($sheet->getCell("I".$i), 11, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("I".$i), $colorOne);
                $sheet->setCellValue("I".$i, $this->translator->trans('keyword.total'));
                ExcelUtilities::setBorder($sheet->getCell('I'.$i));
                ExcelUtilities::setBorder($sheet->getCell('J'.$i));
                ExcelUtilities::setBorder($sheet->getCell('K'.$i));
                ExcelUtilities::setBorder($sheet->getCell('L'.$i));
                $i++;
                for ($index = 0; $index <= ($lines - 3); $index++) {
                    $sheet->mergeCells("A".$i.":D".$i);
                    ExcelUtilities::setFont($sheet->getCell("A".$i), 11, true);
                    $sheet->setCellValue("A".$i, $restaurantTransfers[$index]['invoice']);
                    ExcelUtilities::setBorder($sheet->getCell('A'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('B'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('C'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('D'.$i));

                    //INVOICE DATE
                    $sheet->mergeCells("E".$i.":H".$i);
                    ExcelUtilities::setFont($sheet->getCell("E".$i), 11, true);
                    $value = new \DateTime($restaurantTransfers[$index]['date']);
                    $sheet->setCellValue("E".$i, $value->format('d/m/Y'));
                    ExcelUtilities::setBorder($sheet->getCell('E'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('F'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('G'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('H'.$i));

                    //INVOICE TOTAL
                    $sheet->mergeCells("I".$i.":L".$i);
                    ExcelUtilities::setFont($sheet->getCell("I".$i), 11, true);
                    $sheet->setCellValue("I".$i, $restaurantTransfers[$index]['valorization']);
                    ExcelUtilities::setBorder($sheet->getCell('I'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('J'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('K'.$i));
                    ExcelUtilities::setBorder($sheet->getCell('L'.$i));
                    $i++;
                }
                //TOTAL
                $sheet->mergeCells("A".$i.":D".$i);
                ExcelUtilities::setFont($sheet->getCell("A".$i), 11, true);
                ExcelUtilities::setBorder($sheet->getCell('A'.$i));
                ExcelUtilities::setBorder($sheet->getCell('B'.$i));
                ExcelUtilities::setBorder($sheet->getCell('C'.$i));
                ExcelUtilities::setBorder($sheet->getCell('D'.$i));

                //INVOICE DATE
                $sheet->mergeCells("E".$i.":H".$i);
                ExcelUtilities::setFont($sheet->getCell("E".$i), 11, true);
                ExcelUtilities::setBorder($sheet->getCell('E'.$i));
                ExcelUtilities::setBorder($sheet->getCell('F'.$i));
                ExcelUtilities::setBorder($sheet->getCell('G'.$i));
                ExcelUtilities::setBorder($sheet->getCell('H'.$i));

                //INVOICE TOTAL
                $sheet->mergeCells("I".$i.":L".$i);
                ExcelUtilities::setFont($sheet->getCell("I".$i), 11, true);
                $sheet->setCellValue("I".$i, $restaurantTransfers['totalValorization']);
                ExcelUtilities::setBorder($sheet->getCell('I'.$i));
                ExcelUtilities::setBorder($sheet->getCell('J'.$i));
                ExcelUtilities::setBorder($sheet->getCell('K'.$i));
                ExcelUtilities::setBorder($sheet->getCell('L'.$i));
                $i += 2;
            }
        }//END transfer

        //RECAP
        $i++;
        $k = $i + 1;
        $sheet->mergeCells("A".$i.":L".$k);
        ExcelUtilities::setFont($sheet->getCell("A".$i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorThree);
        $sheet->setCellValue("A".$i, $this->translator->trans('label.recap'));
        ExcelUtilities::setCellAlignment($sheet->getCell("A".$i), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A".$i), $alignmentV);
        $i += 2;

        $sheet->mergeCells('A'.$i.':F'.$i);
        ExcelUtilities::setFont($sheet->getCell("A".$i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue("A".$i, $this->translator->trans('label.name'));
        ExcelUtilities::setBorder($sheet->getCell('A'.$i));
        ExcelUtilities::setBorder($sheet->getCell('B'.$i));
        ExcelUtilities::setBorder($sheet->getCell('C'.$i));
        ExcelUtilities::setBorder($sheet->getCell('D'.$i));
        ExcelUtilities::setBorder($sheet->getCell('e'.$i));
        ExcelUtilities::setBorder($sheet->getCell('F'.$i));

        $sheet->mergeCells('G'.$i.':L'.$i);
        ExcelUtilities::setFont($sheet->getCell("G".$i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G".$i), $colorOne);
        $sheet->setCellValue("G".$i, $this->translator->trans('label.name'));
        ExcelUtilities::setBorder($sheet->getCell('G'.$i));
        ExcelUtilities::setBorder($sheet->getCell('H'.$i));
        ExcelUtilities::setBorder($sheet->getCell('I'.$i));
        ExcelUtilities::setBorder($sheet->getCell('J'.$i));
        ExcelUtilities::setBorder($sheet->getCell('K'.$i));
        ExcelUtilities::setBorder($sheet->getCell('L'.$i));
        $i++;
        if (isset($result['delivery'])) {
            foreach ($result['delivery'] as $supplier) {
                //NAME
                $sheet->mergeCells('A'.$i.':F'.$i);
                ExcelUtilities::setFont($sheet->getCell("A".$i), 11, true);
                $sheet->setCellValue("A".$i, $supplier['name']);
                ExcelUtilities::setBorder($sheet->getCell('A'.$i));
                ExcelUtilities::setBorder($sheet->getCell('B'.$i));
                ExcelUtilities::setBorder($sheet->getCell('C'.$i));
                ExcelUtilities::setBorder($sheet->getCell('D'.$i));
                ExcelUtilities::setBorder($sheet->getCell('e'.$i));
                ExcelUtilities::setBorder($sheet->getCell('F'.$i));
                //VALORIZATION
                $sheet->mergeCells('G'.$i.':L'.$i);
                ExcelUtilities::setFont($sheet->getCell("G".$i), 11, true);
                $sheet->setCellValue("G".$i, $supplier['totalValorization']);
                ExcelUtilities::setBorder($sheet->getCell('G'.$i));
                ExcelUtilities::setBorder($sheet->getCell('H'.$i));
                ExcelUtilities::setBorder($sheet->getCell('I'.$i));
                ExcelUtilities::setBorder($sheet->getCell('J'.$i));
                ExcelUtilities::setBorder($sheet->getCell('K'.$i));
                ExcelUtilities::setBorder($sheet->getCell('L'.$i));
                $i++;
            }
        }

        if (isset($result['transfer'])) {
            foreach ($result['transfer'] as $transfer) {
                //NAME
                $sheet->mergeCells('A'.$i.':F'.$i);
                ExcelUtilities::setFont($sheet->getCell("A".$i), 11, true);
                $sheet->setCellValue("A".$i, $transfer['name']);
                ExcelUtilities::setBorder($sheet->getCell('A'.$i));
                ExcelUtilities::setBorder($sheet->getCell('B'.$i));
                ExcelUtilities::setBorder($sheet->getCell('C'.$i));
                ExcelUtilities::setBorder($sheet->getCell('D'.$i));
                ExcelUtilities::setBorder($sheet->getCell('e'.$i));
                ExcelUtilities::setBorder($sheet->getCell('F'.$i));
                //VALORIZATION
                $sheet->mergeCells('G'.$i.':L'.$i);
                ExcelUtilities::setFont($sheet->getCell("G".$i), 11, true);
                $sheet->setCellValue("G".$i, $transfer['totalValorization']);
                ExcelUtilities::setBorder($sheet->getCell('G'.$i));
                ExcelUtilities::setBorder($sheet->getCell('H'.$i));
                ExcelUtilities::setBorder($sheet->getCell('I'.$i));
                ExcelUtilities::setBorder($sheet->getCell('J'.$i));
                ExcelUtilities::setBorder($sheet->getCell('K'.$i));
                ExcelUtilities::setBorder($sheet->getCell('L'.$i));
                $i++;
            }
        }

        $filename = "Rapport_des_entrees_sorties_".date('dmY_His').".xls";
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
