<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 26/04/2016
 * Time: 09:15
 */

namespace AppBundle\ToolBox\Utils;

class ExcelUtilities
{

    const labelColorBackground = "ECECEC";
    const highlightColorBackground = "ECECED";


    public static function setCellAlignment($obj, $alignment = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
    {

        if ($obj instanceof \PHPExcel_Style) {
            $style = $obj;
        } elseif ($obj instanceof \PHPExcel_Cell) {
            $style = $obj->getStyle();
        }

        $style
            ->getAlignment()
            ->setHorizontal($alignment);
    }

    public static function setVerticalCellAlignment($obj, $alignment = \PHPExcel_Style_Alignment::VERTICAL_CENTER)
    {

        if ($obj instanceof \PHPExcel_Style) {
            $style = $obj;
        } elseif ($obj instanceof \PHPExcel_Cell) {
            $style = $obj->getStyle();
        }

        $style
            ->getAlignment()
            ->setVertical($alignment);
    }

    public static function setFont($obj, $size = null, $bold = false)
    {

        if ($obj instanceof \PHPExcel_Style) {
            $style = $obj;
        } elseif ($obj instanceof \PHPExcel_Cell) {
            $style = $obj->getStyle();
        }

        if ($size) {
            $style->getFont()->setSize($size);
        }

        if ($bold) {
            $style->getFont()->setBold($bold);
        }
    }

    public static function setBorder($obj, $borderStyle = \PHPExcel_Style_Border::BORDER_THIN)
    {
        if ($obj instanceof \PHPExcel_Style) {
            $style = $obj;
        } elseif ($obj instanceof \PHPExcel_Cell) {
            $style = $obj->getStyle();
        }

        $style->getBorders()->getAllBorders()->setBorderStyle($borderStyle);
    }

    public static function setFormat(\PHPExcel_Cell $obj, $type = \PHPExcel_Cell_DataType::TYPE_STRING)
    {
        $obj->setDataType($type);
    }

    public static function setBackgroundColor($obj, $color = 'FFFFFF')
    {
        try {
            if ($obj instanceof \PHPExcel_Style) {
                $style = $obj;
            } elseif ($obj instanceof \PHPExcel_Cell) {
                $style = $obj->getStyle();
            }
            if ($style !== null) {
                $style
                    ->applyFromArray(
                        array(
                            'fill' =>
                                array(
                                    'type' => \PHPExcel_Style_Fill::FILL_SOLID,
                                    'color' =>
                                        array('rgb' => $color),
                                ),
                        )
                    );
                }
            } catch (\Exception $e) { }
    }

    /**
     * @param \PHPExcel_Worksheet $sheet
     * @param $from
     * @param $to
     * @param $value
     * @throws \PHPExcel_Exception
     */
    public static function setTitle(\PHPExcel_Worksheet $sheet, $from, $to, $value)
    {
        $sheet->mergeCells($from . ":" . $to);
        $sheet->setCellValue($from, $value);
        self::setFont($sheet->getCell($from), 12, true);
        self::setCellAlignment($sheet->getCell($from), $alignment = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        self::setVerticalCellAlignment($sheet->getCell($from), $alignment = \PHPExcel_Style_Alignment::VERTICAL_CENTER);
        self::setBackgroundColor($sheet->getCell($from), self::labelColorBackground);
        self::setBorder($sheet->getStyle($from . ":" . $to));
    }

    /**
     * @param \PHPExcel_Worksheet $sheet
     * @param $from
     * @param $to
     * @param $value
     * @throws \PHPExcel_Exception
     */
    public static function setLabel(\PHPExcel_Worksheet $sheet, $from, $to, $value)
    {
        $sheet->mergeCells($from . ":" . $to);
        $sheet->setCellValue($from, $value);
        self::setFont($sheet->getCell($from), 11, true);
        self::setCellAlignment($sheet->getCell($from), $alignment = \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        self::setVerticalCellAlignment($sheet->getCell($from), $alignment = \PHPExcel_Style_Alignment::VERTICAL_CENTER);
        self::setBackgroundColor($sheet->getCell($from), self::labelColorBackground);
        self::setBorder($sheet->getStyle($from . ":" . $to));
    }

    /**
     * @param \PHPExcel_Worksheet $sheet
     * @param $from
     * @param $to
     * @param $value
     * @throws \PHPExcel_Exception
     */
    public static function setTableHeader(\PHPExcel_Worksheet $sheet, $from, $to, $value)
    {
        $sheet->mergeCells($from . ":" . $to);
        $sheet->setCellValue($from, $value);
        self::setFont($sheet->getCell($from), 11, true);
        self::setCellAlignment($sheet->getCell($from), $alignment = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        self::setVerticalCellAlignment($sheet->getCell($from), $alignment = \PHPExcel_Style_Alignment::VERTICAL_CENTER);
        self::setBackgroundColor($sheet->getCell($from), self::labelColorBackground);
        self::setBorder($sheet->getStyle($from . ":" . $to));
    }

    /**
     * @param \PHPExcel_Worksheet $sheet
     * @param $from
     * @param $to
     * @param $value
     * @throws \PHPExcel_Exception
     */
    public static function setTableColumnHeader(\PHPExcel_Worksheet $sheet, $from, $to, $value, $highlight = false)
    {
        $sheet->mergeCells($from . ":" . $to);
        $sheet->setCellValue($from, $value);
        self::setVerticalCellAlignment($sheet->getCell($from), $alignment = \PHPExcel_Style_Alignment::VERTICAL_CENTER);
        self::setBorder($sheet->getStyle($from . ":" . $to));
        if ($highlight) {
            self::setCellAlignment($sheet->getCell($from), $alignment = \PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            self::setBackgroundColor($sheet->getCell($from), self::highlightColorBackground);
            self::setFont($sheet->getCell($from), 12, true);
        } else {
            self::setCellAlignment($sheet->getCell($from), $alignment = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            self::setFont($sheet->getCell($from), 12, false);
        }
    }

    /**
     * @param \PHPExcel_Worksheet $sheet
     * @param $from
     * @param $to
     * @param $value
     * @throws \PHPExcel_Exception
     */
    public static function setNumericCellTableBodyValue(
        \PHPExcel_Worksheet $sheet,
        $from,
        $to,
        $value,
        $highlight = false
    )
    {
        $sheet->mergeCells($from . ":" . $to);
        $sheet->setCellValue($from, $value);
        self::setCellAlignment($sheet->getCell($from), $alignment = \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        self::setVerticalCellAlignment($sheet->getCell($from), $alignment = \PHPExcel_Style_Alignment::VERTICAL_CENTER);
        self::setBorder($sheet->getStyle($from . ":" . $to));
        if ($highlight) {
            self::setFont($sheet->getCell($from), 11, true);
            self::setBackgroundColor($sheet->getCell($from), self::highlightColorBackground);
        } else {
            self::setFont($sheet->getCell($from), 11, false);
        }
    }

    /**
     * @param \PHPExcel_Worksheet $sheet
     * @param $from
     * @param $to
     * @param $value
     * @throws \PHPExcel_Exception
     */
    public static function setValue(\PHPExcel_Worksheet $sheet, $from, $to, $value, $highlight = false)
    {
        $sheet->mergeCells($from . ":" . $to);
        $sheet->setCellValue($from, $value);
        self::setFont($sheet->getCell($from), 11, false);
        self::setCellAlignment($sheet->getCell($from), $alignment = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        self::setVerticalCellAlignment($sheet->getCell($from), $alignment = \PHPExcel_Style_Alignment::VERTICAL_CENTER);
        self::setBorder($sheet->getStyle($from . ":" . $to));
        if ($highlight) {
            self::setBackgroundColor($sheet->getCell($from), self::highlightColorBackground);
        }
    }

    /**
     * @param \PHPExcel_Worksheet $sheet
     * @param $from
     * @param $to
     * @param $value
     * @throws \PHPExcel_Exception
     */
    public static function setOnlyValue(\PHPExcel_Worksheet $sheet, $from, $to, $value, $highlight = false)
    {
        $sheet->mergeCells($from . ":" . $to);
        $sheet->setCellValue($from, $value);
    }

    public static function setTextColor($obj, $color = 'FFFFFF')
    {
        if ($obj instanceof \PHPExcel_Style) {
            $style = $obj;
        } elseif ($obj instanceof \PHPExcel_Cell) {
            $style = $obj->getStyle();
        }

        $style
            ->applyFromArray(
                array(
                    'font' => array(
                        'color' => array('rgb' => $color),
                    ),
                )
            );
    }

    public static function getNameFromNumber($num)
    {
        $numeric = $num % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval($num / 26);
        if ($num2 > 0) {
            return ExcelUtilities::getNameFromNumber($num2 - 1) . $letter;
        } else {
            return $letter;
        }
    }
}
