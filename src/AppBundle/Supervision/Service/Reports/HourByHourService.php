<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 29/05/2016
 * Time: 16:19
 */

namespace AppBundle\Supervision\Service\Reports;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Merchandise\Entity\CaPrev;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class HourByHourService
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

    public function generateHourByHourReport($data)
    {
        $result = array();
        $divisionsRaw = $this->em->getRepository(Ticket::class)->getSupervisionCaTicketsPerHourAndOrigin($data);

        $hoursOpeningClosing = $this->getOpeningAndClosingHour($divisionsRaw, $data);
        $openingHour = $hoursOpeningClosing['openingHour'];
        $closingHour = $hoursOpeningClosing['closingHour'];

        $firstHour = $openingHour;
        $lastHour = $closingHour;

        $minHour = ($lastHour >= $firstHour) ? $lastHour : 23;
        $maxHour = ($lastHour <= $firstHour) ? $lastHour : 0;

        $today = new \DateTime();

        if ($data['date'] > $today) {
            for ($i = 0; $i < 25; $i++) {
                $result['ticket'][$i]['nbrTicket'] = '*';
                $result['caBrut'][$i] = '*';
            }
        } else {
            for ($i = 0; $i < 25; $i++) {
                if ($data['date']->format('d/m/Y') == date('d/m/Y') and (date('G') < $i
                        or (date('G') > $i and $i < $firstHour))
                ) {
                    $result['ticket'][$i]['nbrTicket'] = '*';
                    $result['caBrut'][$i] = '*';
                } else {
                    $result['ticket'][$i]['nbrTicket'] = 0;
                    $result['caBrut'][$i] = 0;

                    foreach ($divisionsRaw as $raw) {
                        if ($raw['entryhour'] == $i) {
                            if (($i >= $firstHour and $i <= $minHour) or ($i <= $maxHour)) {
                                $result['ticket'][$i]['nbrTicket'] += $raw['countticket'];
                                $result['caBrut'][$i] += $raw['ca_brut_ttc'];
                                $result['origin'][$raw['origin']][$i]['ca_brut'] = $raw['ca_brut_ttc'];
                                $result['origin'][$raw['origin']][$i]['tickets'] = $raw['countticket'];
                            }
                        }
                    }
                    $result['caBrut'][$i] = number_format($result['caBrut'][$i], 2, '.', '');
                }
            }

            if (isset($result['origin'])) {
                foreach ($result['origin'] as &$origin) {
                    $origin[24]['ca_brut'] = 0;
                    $origin[24]['tickets'] = 0;
                    for ($i = 0; $i < 24; $i++) {
                        if ($data['date']->format('d/m/Y') == date('d/m/Y') and (date('G') < $i
                                or (date(
                                    'G'
                                ) > $i and $i < (($openingHour) ? $openingHour : Parameter::RESTAURANT_OPENING_HOUR_DEFAULT)))
                        ) {
                            $origin[$i]['ca_brut'] = '*';
                            $origin[$i]['tickets'] = '*';
                        } elseif (!isset($origin[$i])) {
                            $origin[$i]['ca_brut'] = 0;
                            $origin[$i]['tickets'] = 0;
                        } else {
                            $origin[24]['ca_brut'] += $origin[$i]['ca_brut'];
                            $origin[24]['tickets'] += $origin[$i]['tickets'];
                        }
                    }
                }
            }

            for ($i = 0; $i < 24; $i++) {
                $result['ticket'][24]['nbrTicket'] += $result['ticket'][$i]['nbrTicket'];
                $result['caBrut'][24] += $result['caBrut'][$i];
            }
        }


        $result['ca_prev'] = $this->getCaPrev($data);
        if (isset($result['origin'])) {
            uksort(
                $result['origin'],
                function ($key1, $key2) {
                    $defaultSort = [
                        'pos' => 0,
                        'drive' => 1,
                        'borne' => 2,
                        'delivery' => 3,
                    ];
                    if (!isset($defaultSort[strtolower($key1)])) {
                        return 1;
                    } else {
                        if (!isset($defaultSort[strtolower($key2)])) {
                            return -1;
                        } else {
                            return ($defaultSort[strtolower($key1)] > $defaultSort[strtolower($key2)]) ? 1 : -1;
                        }
                    }
                }
            );
        }

        $allResult = [
            'result' => $result,
            'openingHour' => $openingHour,
            'closingHour' => $closingHour,
        ];

        return $allResult;
    }

    public function getCaPrev($data)
    {

        $caFinalPerHour = array();

        $date = $data['date'];

        $previousDate = array();

        $previousDate['0'] = new \DateTime();
        $previousDate['0']->setTimestamp($date->getTimestamp() - (86400 * 7));

        $previousDate['1'] = new \DateTime();
        $previousDate['1']->setTimestamp($date->getTimestamp() - (86400 * 7 * 2));

        $previousDate['2'] = new \DateTime();
        $previousDate['2']->setTimestamp($date->getTimestamp() - (86400 * 7 * 3));

        $previousDate['3'] = new \DateTime();
        $previousDate['3']->setTimestamp($date->getTimestamp() - (86400 * 7 * 4));

        //$total = $this->em->getRepository('AppBundle:Financial\Ticket')->getTotalPerDayFourPreviousWeek($previousDate, $data['restaurant']);
        $caHour = $this->em->getRepository(Ticket::class)->getSupervisionTotalPerHourFourPreviousWeek(
            $previousDate,
            $data['restaurant']
        );

        $total = 0;

        foreach ($caHour as $ca) {
            $caPerHour[$ca['entryhour']] = $ca['total'];
            $total += $ca['total'];
        }


        for ($i = 0; $i < 24; $i++) {
            $caProportionPerHour[$i] = (isset($caPerHour[$i]) && $total != 0)
                ? ($caPerHour[$i] / $total)
                : 0;
        }


        $ca_prev_date = $this->em->getRepository(CaPrev::class)->findOneBy(
            array(
                'date' => $date,
                'originRestaurant' => $data['restaurant'],
            )
        );

        $ca_prev_date_ca = isset($ca_prev_date) ? $ca_prev_date->getCa() : 0;

        for ($i = 0; $i < 24; $i++) {
            $caFinalPerHour[$i] = $ca_prev_date_ca * $caProportionPerHour[$i];
        }
        $caFinalPerHour[24] = $ca_prev_date_ca;


        return $caFinalPerHour;
    }

    public function serializeHourByHourReportResult($result, $openingHour, $closingHour)
    {
        $serializedResult = [];

        if ($closingHour > $openingHour) {
            $limitHour = $closingHour;
        } else {
            $limitHour = 23;
        }

        $serializedResult['0']['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.ca_prev');
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            $serializedResult['0'][$i] = number_format($result['ca_prev'][$i], 2, '.', '');
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                $serializedResult['0'][$i] = number_format($result['ca_prev'][$i], 2, '.', '');
            }
        }

        $serializedResult['0'][] = number_format($result['ca_prev']['24'], 2, '.', '');

        $serializedResult['1']['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.ca_brut');
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            if (is_numeric($result['caBrut'][$i])) {
                $serializedResult['1'][$i] = number_format($result['caBrut'][$i], 2, '.', '');
            } else {
                $serializedResult['1'][$i] = '*';
            }
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                if (is_numeric($result['caBrut'][$i])) {
                    $serializedResult['1'][$i] = number_format($result['caBrut'][$i], 2, '.', '');
                } else {
                    $serializedResult['1'][$i] = '*';
                }
            }
        }
        $serializedResult['1'][] = number_format($result['caBrut']['24'], 2, '.', '');

        $serializedResult['2']['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.tickets');
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            if (is_numeric($result['ticket'][$i]['nbrTicket'])) {
                $serializedResult['2'][$i] = $result['ticket'][$i]['nbrTicket'];
            } else {
                $serializedResult['2'][$i] = '*';
            }
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                if (is_numeric($result['ticket'][$i]['nbrTicket'])) {
                    $serializedResult['2'][$i] = $result['ticket'][$i]['nbrTicket'];
                } else {
                    $serializedResult['2'][$i] = '*';
                }
            }
        }
        $serializedResult['2'][] = $result['ticket']['24']['nbrTicket'];

        $serializedResult['3']['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.avg_ticket');
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            if (is_numeric($result['ticket'][$i]['nbrTicket'])) {
                $serializedResult['3'][$i] = ($result['ticket'][$i]['nbrTicket'] != 0) ?
                    number_format($result['caBrut'][$i] / $result['ticket'][$i]['nbrTicket'], 2, '.', '') :
                    0.00;
            } else {
                $serializedResult['3'][$i] = '*';
            }
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                if (is_numeric($result['ticket'][$i]['nbrTicket'])) {
                    $serializedResult['3'][$i] = ($result['ticket'][$i]['nbrTicket'] != 0) ?
                        number_format($result['caBrut'][$i] / $result['ticket'][$i]['nbrTicket'], 2, '.', '') :
                        0.00;
                } else {
                    $serializedResult['3'][$i] = '*';
                }
            }
        }
        $serializedResult['3'][] = ($result['ticket']['24']['nbrTicket'] != 0) ?
            number_format($result['caBrut']['24'] / $result['ticket']['24']['nbrTicket'], 2, '.', '') :
            0.00;

        if (isset($result['origin'])) {
            $j = 4;
            foreach ($result['origin'] as $key => $origin) {
                $serializedResult[$j]['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.ca_brut').' ('.$this->translator->trans('canal.'.$key).')';
                $serializedResult[$j + 1]['titleColumn'] = '% CA ('.' '.$this->translator->trans('canal.'.$key).')';
                $serializedResult[$j + 2]['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.ticket').' '.$this->translator->trans('canal.'.$key);
                for ($i = $openingHour; $i <= $limitHour; $i++) {
                    if (is_numeric($origin[$i]['ca_brut'])) {
                        $serializedResult[$j][$i] = number_format($origin[$i]['ca_brut'], 2, '.', '');

                        $serializedResult[$j + 1][$i] = ($result['caBrut'][$i] != 0) ?
                            number_format($origin[$i]['ca_brut'] / $result['caBrut'][$i] * 100, 2, '.', '') :
                            0.00;
                        $serializedResult[$j + 2][$i] = number_format($origin[$i]['tickets'], 2, '.', '');
                    } else {
                        $serializedResult[$j][$i] = '*';
                        $serializedResult[$j + 1][$i] = '*';
                        $serializedResult[$j + 2][$i] = '*';
                    }
                }
                if ($closingHour < $openingHour) {
                    for ($i = 0; $i <= $closingHour; $i++) {
                        if (is_numeric($origin[$i]['ca_brut'])) {
                            $serializedResult[$j][$i] = number_format($origin[$i]['ca_brut'], 2, '.', '');
                            $serializedResult[$j + 1][$i] = ($result['caBrut'][$i] != 0) ?
                                number_format($origin[$i]['ca_brut'] / $result['caBrut'][$i] * 100, 2, '.', '') :
                                0.00;
                            $serializedResult[$j + 2][$i] = number_format($origin[$i]['tickets'], 2, '.', '');
                        } else {
                            $serializedResult[$j][$i] = '*';
                            $serializedResult[$j + 1][$i] = '*';
                            $serializedResult[$j + 2][$i] = '*';
                        }
                    }
                }
                $serializedResult[$j][] = number_format($origin['24']['ca_brut'], 2, '.', '');
                $serializedResult[$j + 1][] = ($result['caBrut']['24'] != 0) ?
                    number_format($origin['24']['ca_brut'] / $result['caBrut']['24'] * 100, 2, '.', '') :
                    0.00;
                $serializedResult[$j + 2][] = number_format($origin['24']['tickets'], 2, '.', '');

                $j = $j + 3;
            }
        }

        return $serializedResult;
    }

    public function getCsvHeader($openingHour, $closingHour)
    {
        $header = [''];
        $limitHour = ($closingHour < $openingHour) ? 23 : $closingHour;
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            $header[] = $i.":00";
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                $header[] = $i.":00";
            }
        }
        $header[] = $this->translator->trans('keyword.total');

        return $header;
    }

    public function getOpeningAndClosingHour($divisionsRaw, $data)
    {
        $openingHourParameter = $this->em->getRepository(Parameter::class)->findParameterByTypeAndRestaurant(
            Parameter::RESTAURANT_OPENING_HOUR,
            $data['restaurant']
        );
        $closingHourParameter = $this->em->getRepository(Parameter::class)->findParameterByTypeAndRestaurant(
            Parameter::RESTAURANT_CLOSING_HOUR,
            $data['restaurant']
        );

        if ($openingHourParameter) {
            $openingHourRestaurant = $openingHourParameter->getValue();
        } else {
            $openingHourRestaurant = Parameter::RESTAURANT_OPENING_HOUR_DEFAULT;
        }
        if ($closingHourParameter) {
            $closingHourRestaurant = $closingHourParameter->getValue();
        } else {
            $closingHourRestaurant = Parameter::RESTAURANT_CLOSING_HOUR_DEFAULT;
        }
        $minTicketHour = $openingHourRestaurant;
        $maxTicketHour = $closingHourRestaurant;

        foreach ($divisionsRaw as $raw) {
            if ($raw['entryhour'] >= Parameter::RESTAURANT_OPENING_HOUR_DEFAULT) {
                $minTicketHour = min([$minTicketHour, $raw['entryhour']]);
                if ($maxTicketHour >= Parameter::RESTAURANT_OPENING_HOUR_DEFAULT) {
                    $maxTicketHour = max([$maxTicketHour, $raw['entryhour']]);
                }
            } else {
                if ($maxTicketHour >= Parameter::RESTAURANT_OPENING_HOUR_DEFAULT) {
                    $maxTicketHour = $raw['entryhour'];
                } else {
                    $maxTicketHour = max([$raw['entryhour'], $maxTicketHour]);
                }
            }
        }
        $openingHour = min([$openingHourRestaurant, $minTicketHour]);

        if (($closingHourRestaurant < Parameter::RESTAURANT_OPENING_HOUR_DEFAULT and $maxTicketHour < Parameter::RESTAURANT_OPENING_HOUR_DEFAULT)
            or ($closingHourRestaurant >= Parameter::RESTAURANT_OPENING_HOUR_DEFAULT and $maxTicketHour >= Parameter::RESTAURANT_OPENING_HOUR_DEFAULT)
        ) {
            $closingHour = max([$closingHourRestaurant, $maxTicketHour]);
        } else {
            $closingHour = min([$closingHourRestaurant, $maxTicketHour]);
        }
        $result = [
            'openingHour' => $openingHour,
            'closingHour' => $closingHour,
        ];

        return $result;
    }

    public function generateExcelFile($result, $restaurant, $startDate, $openingHour, $closingHour)
    {
        $colorOne = "ECECEC";
        $colorTwo = "E5CFAB";
        $colorThree = "C5923F";
        $colorFour = "449EFD";
        $colorFive = "FFFCC0";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('report.sales.hour_by_hour.title'), 0, 30));

        $sheet->mergeCells("B3:K6");
        $content = $this->translator->trans('report.sales.hour_by_hour.title');
        $sheet->setCellValue('B3', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("B3"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B3"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 3), 22, true);

        //FILTER ZONE
        // START DATE
        $sheet->mergeCells("A8:B8");
        ExcelUtilities::setFont($sheet->getCell('A8'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A8"), $colorOne);
        $sheet->setCellValue('A8', $this->translator->trans('keywords.date', [], 'supervision').":");
        $sheet->mergeCells("C8:D8");
        ExcelUtilities::setFont($sheet->getCell('C8'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C8"), $colorOne);
        $sheet->setCellValue('C8', $startDate->format('Y-m-d'));

        $sheet->mergeCells("A9:B9");
        ExcelUtilities::setFont($sheet->getCell('A9'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A9"), $colorOne);
        $sheet->setCellValue('A9', $this->translator->trans('keywords.restaurant', [], 'supervision').":");
        $sheet->mergeCells("C9:D9");
        ExcelUtilities::setFont($sheet->getCell('C9'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C9"), $colorOne);
        $sheet->setCellValue('C9', $restaurant->getName());

        //CONTENT

        //Hours
        if ($closingHour > $openingHour) {
            $limitHour = $closingHour;
        } else {
            $limitHour = 23;
        }
        $startCell = 'C';
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            $sheet->setCellValue($startCell.'15', $i.':00');
            ExcelUtilities::setBorder($sheet->getCell($startCell.'15'));
            ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.'15'), $colorFive);
            $startCell++;
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                $sheet->setCellValue($startCell.'15', $i.':00');
                ExcelUtilities::setBorder($sheet->getCell($startCell.'15'));
                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.'15'), $colorFive);
                $startCell++;
            }
        }
        $sheet->setCellValue($startCell.'15', $this->translator->trans('keyword.total'));
        ExcelUtilities::setBorder($sheet->getCell($startCell.'15'));
        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.'15'), $colorFive);

        //CONTENT
        $lineIndex = 16;

        $lineIndex++;

        //CA PREV
        $sheet->mergeCells('A'.$lineIndex.':B'.$lineIndex);
        $sheet->setCellValue('A'.$lineIndex, $this->translator->trans(('report.sales.hour_by_hour.ca_prev')));
        ExcelUtilities::setBorder($sheet->getCell('A'.$lineIndex));
        ExcelUtilities::setBorder($sheet->getCell('B'.$lineIndex));
        ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineIndex), $colorThree);

        $startCell = 'C';
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            $sheet->setCellValue($startCell.$lineIndex, round($result['ca_prev'][$i], 2));
            ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
            ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorThree);
            $startCell++;
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                $sheet->setCellValue($startCell.$lineIndex, round($result['ca_prev'][$i], 2));
                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorThree);
                $startCell++;
            }
        }
        $sheet->setCellValue($startCell.$lineIndex, round($result['ca_prev'][24], 2));
        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorThree);
        $lineIndex++;
        //END CA PREV


        //CA BRUT
        $sheet->mergeCells('A'.$lineIndex.':B'.$lineIndex);
        $sheet->setCellValue('A'.$lineIndex, $this->translator->trans(('report.sales.hour_by_hour.ca_brut')));
        ExcelUtilities::setBorder($sheet->getCell('A'.$lineIndex));
        ExcelUtilities::setBorder($sheet->getCell('B'.$lineIndex));
        ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineIndex), $colorTwo);

        $startCell = 'C';
        $colorSwitcher = 0;
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            $sheet->setCellValue($startCell.$lineIndex, round($result['caBrut'][$i], 2));
            ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
            if (is_int($colorSwitcher / 2)) {
                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
            }
            $colorSwitcher++;
            $startCell++;
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                $sheet->setCellValue($startCell.$lineIndex, round($result['caBrut'][$i], 2));
                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                if (is_int($colorSwitcher / 2)) {
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                }
                $colorSwitcher++;
                $startCell++;
            }
        }
        $sheet->setCellValue($startCell.$lineIndex, round($result['caBrut'][24], 2));
        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
        $lineIndex += 2;
        //END CA BRUT

        //Tickets
        $sheet->mergeCells('A'.$lineIndex.':B'.$lineIndex);
        $sheet->setCellValue('A'.$lineIndex, $this->translator->trans(('report.sales.hour_by_hour.tickets')));
        ExcelUtilities::setBorder($sheet->getCell('A'.$lineIndex));
        ExcelUtilities::setBorder($sheet->getCell('B'.$lineIndex));
        ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineIndex), $colorTwo);

        $startCell = 'C';
        $colorSwitcher = 0;
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            $sheet->setCellValue($startCell.$lineIndex, round($result['ticket'][$i]['nbrTicket'], 2));
            ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
            if (is_int($colorSwitcher / 2)) {
                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
            }
            $colorSwitcher++;
            $startCell++;
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                $sheet->setCellValue($startCell.$lineIndex, round($result['ticket'][$i]['nbrTicket'], 2));
                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                if (is_int($colorSwitcher / 2)) {
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                }
                $colorSwitcher++;
                $startCell++;
            }
        }
        $sheet->setCellValue($startCell.$lineIndex, round($result['ticket'][24]['nbrTicket'], 2));
        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
        $lineIndex++;
        //END Tickets

        //Tickets AVG
        $sheet->mergeCells('A'.$lineIndex.':B'.$lineIndex);
        $sheet->setCellValue('A'.$lineIndex, $this->translator->trans(('report.sales.hour_by_hour.avg_ticket')));
        ExcelUtilities::setBorder($sheet->getCell('A'.$lineIndex));
        ExcelUtilities::setBorder($sheet->getCell('B'.$lineIndex));
        ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineIndex), $colorTwo);

        $startCell = 'C';
        $colorSwitcher = 0;
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            if (!is_numeric($result['ticket'][$i]['nbrTicket'])) {
                $sheet->setCellValue($startCell.$lineIndex, '*');
            } else {
                if ($result['ticket'][$i]['nbrTicket'] != 0) {
                    $sheet->setCellValue(
                        $startCell.$lineIndex,
                        round($result['caBrut'][$i] / $result['ticket'][$i]['nbrTicket'], 2)
                    );
                } else {
                    $sheet->setCellValue($startCell.$lineIndex, '0.00');
                }
            }
            ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
            if (is_int($colorSwitcher / 2)) {
                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
            }
            $colorSwitcher++;
            $startCell++;
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                if (!is_numeric($result['ticket'][$i]['nbrTicket'])) {
                    $sheet->setCellValue($startCell.$lineIndex, '*');
                } else {
                    if ($result['ticket'][$i]['nbrTicket'] != 0) {
                        $sheet->setCellValue(
                            $startCell.$lineIndex,
                            round($result['caBrut'][$i] / $result['ticket'][$i]['nbrTicket'], 2)
                        );
                    } else {
                        $sheet->setCellValue($startCell.$lineIndex, '0,00');
                    }
                }
                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                if (is_int($colorSwitcher / 2)) {
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                }
                $colorSwitcher++;
                $startCell++;
            }
        }
        if (!is_numeric($result['ticket'][24]['nbrTicket'])) {
            $sheet->setCellValue($startCell.$lineIndex, '*');
        } else {
            if ($result['ticket'][24]['nbrTicket'] != 0) {
                $sheet->setCellValue(
                    $startCell.$lineIndex,
                    round($result['caBrut'][24] / $result['ticket'][24]['nbrTicket'], 2)
                );
            } else {
                $sheet->setCellValue($startCell.$lineIndex, '0,00');
            }
        }
        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
        $lineIndex += 2;
        //END Tickets AVG

        if (isset($result['origin'])) {
            foreach ($result['origin'] as $key => $origin) {
                //CA BRUT CANAL
                $sheet->mergeCells('A'.$lineIndex.':B'.$lineIndex);
                $sheet->setCellValue(
                    'A'.$lineIndex,
                    $this->translator->trans(('report.sales.hour_by_hour.ca_brut')).' ('.$this->translator->trans(
                        'canal.'.$key
                    ).')'
                );
                ExcelUtilities::setBorder($sheet->getCell('A'.$lineIndex));
                ExcelUtilities::setBorder($sheet->getCell('B'.$lineIndex));
                ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineIndex), $colorTwo);

                $startCell = 'C';
                $colorSwitcher = 0;
                for ($i = $openingHour; $i <= $limitHour; $i++) {
                    if (!is_numeric($origin[$i]['ca_brut'])) {
                        $sheet->setCellValue($startCell.$lineIndex, '*');
                    } else {
                        $sheet->setCellValue($startCell.$lineIndex, round($origin[$i]['ca_brut'], 2));
                    }
                    ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                    if (is_int($colorSwitcher / 2)) {
                        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                    }
                    $colorSwitcher++;
                    $startCell++;
                }
                if ($closingHour < $openingHour) {
                    for ($i = 0; $i <= $closingHour; $i++) {
                        if (!is_numeric($origin[$i]['ca_brut'])) {
                            $sheet->setCellValue($startCell.$lineIndex, '*');
                        } else {
                            $sheet->setCellValue($startCell.$lineIndex, round($origin[$i]['ca_brut'], 2));
                        }
                        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                        if (is_int($colorSwitcher / 2)) {
                            ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                        }
                        $colorSwitcher++;
                        $startCell++;
                    }
                }
                if (!is_numeric($origin[24]['ca_brut'])) {
                    $sheet->setCellValue($startCell.$lineIndex, '*');
                } else {
                    $sheet->setCellValue($startCell.$lineIndex, round($origin[24]['ca_brut'], 2));
                }
                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                $lineIndex++;
                //END CA BRUT CANAL

                // % CA  CANAL
                $sheet->mergeCells('A'.$lineIndex.':B'.$lineIndex);
                $sheet->setCellValue('A'.$lineIndex, '%CA ('.$this->translator->trans('canal.'.$key).')');
                ExcelUtilities::setBorder($sheet->getCell('A'.$lineIndex));
                ExcelUtilities::setBorder($sheet->getCell('B'.$lineIndex));
                ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineIndex), $colorTwo);

                $startCell = 'C';
                $colorSwitcher = 0;
                for ($i = $openingHour; $i <= $limitHour; $i++) {
                    if (!is_numeric($origin[$i]['ca_brut'])) {
                        $sheet->setCellValue($startCell.$lineIndex, '*');
                    } else {
                        if ($result['caBrut'][$i] != 0) {
                            $sheet->setCellValue(
                                $startCell.$lineIndex,
                                round(($origin[$i]['ca_brut'] / $result['caBrut'][$i] * 100), 2)
                            );
                        } else {
                            $sheet->setCellValue($startCell.$lineIndex, '0,00');
                        }
                    }
                    ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                    if (is_int($colorSwitcher / 2)) {
                        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                    }
                    $colorSwitcher++;
                    $startCell++;
                }
                if ($closingHour < $openingHour) {
                    for ($i = 0; $i <= $closingHour; $i++) {
                        if (!is_numeric($origin[$i]['ca_brut'])) {
                            $sheet->setCellValue($startCell.$lineIndex, '*');
                        } else {
                            if ($result['caBrut'][$i] != 0) {
                                $sheet->setCellValue(
                                    $startCell.$lineIndex,
                                    round(($origin[$i]['ca_brut'] / $result['caBrut'][$i] * 100), 2)
                                );
                            } else {
                                $sheet->setCellValue($startCell.$lineIndex, '0,00');
                            }
                        }
                        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                        if (is_int($colorSwitcher / 2)) {
                            ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                        }
                        $colorSwitcher++;
                        $startCell++;
                    }
                }
                if (!is_numeric($origin[24]['ca_brut'])) {
                    $sheet->setCellValue($startCell.$lineIndex, '*');
                } else {
                    if ($result['caBrut'][24] != 0) {
                        $sheet->setCellValue(
                            $startCell.$lineIndex,
                            round(($origin[24]['ca_brut'] / $result['caBrut'][24] * 100), 2)
                        );
                    } else {
                        $sheet->setCellValue($startCell.$lineIndex, '0,00');
                    }
                }
                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                $lineIndex++;
                //END % CA  CANAL

                //TICKET CANAL
                $sheet->mergeCells('A'.$lineIndex.':B'.$lineIndex);
                $sheet->setCellValue(
                    'A'.$lineIndex,
                    $this->translator->trans(('report.sales.hour_by_hour.ticket')).' ('.$this->translator->trans(
                        'canal.'.$key
                    ).')'
                );
                ExcelUtilities::setBorder($sheet->getCell('A'.$lineIndex));
                ExcelUtilities::setBorder($sheet->getCell('B'.$lineIndex));
                ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineIndex), $colorTwo);

                $startCell = 'C';
                $colorSwitcher = 0;
                for ($i = $openingHour; $i <= $limitHour; $i++) {
                    $sheet->setCellValue($startCell.$lineIndex, round($origin[$i]['tickets'], 2));

                    ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                    if (is_int($colorSwitcher / 2)) {
                        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                    }
                    $colorSwitcher++;
                    $startCell++;
                }
                if ($closingHour < $openingHour) {
                    for ($i = 0; $i <= $closingHour; $i++) {
                        $sheet->setCellValue($startCell.$lineIndex, round($origin[$i]['tickets'], 2));
                        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                        if (is_int($colorSwitcher / 2)) {
                            ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                        }
                        $colorSwitcher++;
                        $startCell++;
                    }
                }
                $sheet->setCellValue($startCell.$lineIndex, round($origin[24]['tickets'], 2));

                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                $lineIndex += 2;
                //END TICKET CANAL
            }
        }

        $filename = "Rapport_heure_par_heure_".date('dmY_His').".xls";
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
