<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 09/03/2016
 * Time: 14:20
 */

namespace AppBundle\Administration\Service;

use AppBundle\Merchandise\Entity\CaPrev;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\ToolBox\Service\DocumentGeneratorService;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

/**
 * Class CaPrevService
 */
class CaPrevService
{

    private $em;
    private $restaurantService;
    private $phpExcel;
    private $translator;
    private $documentGenerator;

    /**
     * CaPrevService constructor.
     * @param EntityManager $em
     * @param RestaurantService $restaurantService
     */
    public function __construct(EntityManager $em, RestaurantService $restaurantService,Factory $factory,Translator $translator,DocumentGeneratorService $documentGenerator)
    {
        $this->em = $em;
        $this->restaurantService = $restaurantService;
        $this->translator = $translator;
        $this->phpExcel = $factory;
        $this->documentGenerator = $documentGenerator;
    }

    /**
     * @param \DateTime $date
     *
     * @return float
     */
    public function createIfNotExsit(\DateTime $date,$restaurant=null)
    {
        //fixed

        if(!$restaurant){
            $restaurant = $this->restaurantService->getCurrentRestaurant();
        }


        $ca = $this->em->getRepository("Merchandise:CaPrev")->findOneBy(
            array(
                'date' => $date,
                'originRestaurant' => $restaurant,
            )
        );

        if (null === $ca || !$ca->getFixed()) {
            if (null === $ca) {
                $ca = new CaPrev();
                $ca->setFixed(false);
            }

            $ca->setDate($date);

            $date1 = new \DateTime();
            $date1->setTimestamp($date->getTimestamp() - (86400 * 7));

            $date2 = new \DateTime();
            $date2->setTimestamp($date->getTimestamp() - (86400 * 7 * 2));

            $date3 = new \DateTime();
            $date3->setTimestamp($date->getTimestamp() - (86400 * 7 * 3));

            $date4 = new \DateTime();
            $date4->setTimestamp($date->getTimestamp() - (86400 * 7 * 4));

            $date5 = new \DateTime();
            $date5->setTimestamp($date->getTimestamp() - (86400 * (364 + 7)));

            $date6 = new \DateTime();
            $date6->setTimestamp($date->getTimestamp() - (86400 * (364 + 14)));

            $date7 = new \DateTime();
            $date7->setTimestamp($date->getTimestamp() - (86400 * (364 + 21)));

            $date8 = new \DateTime();
            $date8->setTimestamp($date->getTimestamp() - (86400 * (364 + 28)));

            $comparableDay = new \DateTime();
            $comparableDay->setTimestamp($ca->getDate()->getTimestamp() - (86400 * 364));

            $ca
                ->setDate1($date1)
                ->setDate2($date2)
                ->setDate3($date3)
                ->setDate4($date4)
                ->setDate5($date5)
                ->setDate6($date6)
                ->setDate7($date7)
                ->setDate8($date8)
                ->setSynchronized(false)
                ->setComparableDay($comparableDay);
            $ca->setOriginRestaurant($restaurant);

            $this->calculateCa($ca);


            $this->em->persist($ca);
            $this->em->flush();

            return $ca->getCa();
        }

        return $ca->getCa();
    }

    /**
     * @param CaPrev $caPrev
     *
     * @return array
     */
    public function calculateCa(CaPrev $caPrev)
    {
        //fixed

        /**
         *
         ** * 4 latest weeks **
         */
        $c1 = $this->getCaForCaPrev($caPrev, 1);
        $c2 = $this->getCaForCaPrev($caPrev, 2);
        $c3 = $this->getCaForCaPrev($caPrev, 3);
        $c4 = $this->getCaForCaPrev($caPrev, 4);

        $m1 = ($c1 + $c2 + $c3 + $c4) / 4;

        /**
         *
         ** * 4 latest weeks for last year **
         */
        $c5 = $this->getCaForCaPrev($caPrev, 5);
        $c6 = $this->getCaForCaPrev($caPrev, 6);
        $c7 = $this->getCaForCaPrev($caPrev, 7);
        $c8 = $this->getCaForCaPrev($caPrev, 8);

        $m2 = ($c5 + $c6 + $c7 + $c8) / 4;

        if (0 != $m2) {
            $variance = ($m1 - $m2) / $m2;
        } else {
            $variance = 0;
        }

        $comparableDayCaObj = $this->em->getRepository("Financial:FinancialRevenue")
            ->findOneBy(
                array(
                    'date' => $caPrev->getComparableDay(),
                    'originRestaurant' => $this->restaurantService->getCurrentRestaurant(),
                )
            );

        if ($comparableDayCaObj) {
            $comparableDayCa = $comparableDayCaObj->getAmount();
        } else {
            $comparableDayCa = 0;
        }

        $result = (1 + $variance) * $comparableDayCa;
        $caPrev->setCa(number_format($result, 3, '.', ''));

        $caPrev->setVariance($variance);
        $caPrev->setSynchronized(false);

        return [
            $c1,
            $c2,
            $c3,
            $c4,
            $c5,
            $c6,
            $c7,
            $c8,
            'm1' => $m1,
            'm2' => $m2,
            'comparableDayCa' => $comparableDayCa,
        ];
    }

    /**
     * @param CaPrev $caPrev
     * @param $n
     *
     * @return \AppBundle\Financial\Entity\FinancialRevenue|int|null|object
     */
    public function getCaForCaPrev(CaPrev $caPrev, $n)
    {
        //fixed
        $finacialRepo = $this->em->getRepository("Financial:FinancialRevenue");
        $method = "getDate".$n;
        $c = $finacialRepo->findOneBy(
            array('date' => $caPrev->$method(), 'originRestaurant' => $caPrev->getOriginRestaurant())
        );
        if (null !== $c) {
            $c = $c->getBrutTTC();
        } else {
            $c = 0;
        }

        return $c;
    }

    /**
     * @param \DateTime $d1
     * @param \DateTime $d2
     *
     * @return float|int
     */
    public function getCumulCaPrevBetweenDate(\DateTime $d1, \DateTime $d2)
    {
        $caPrev = 0;
        $range = $d2->diff($d1)->days;
        for ($i = 0; $i <= $range; $i++) {
            $auxDateTimeStamp = mktime(
                0,
                0,
                0,
                intval($d1->format('m')),
                intval($d1->format('d')) + $i,
                intval($d1->format('Y'))
            );
            $auxDate = new \DateTime();
            $auxDate->setTimestamp($auxDateTimeStamp);
            $ca = $this->createIfNotExsit($auxDate);
            $caPrev = $ca + $caPrev;
            //            echo "CA PREV ".$auxDate->format('d/m/Y')." CA =>  $ca CaPrev => $caPrev \n";
            //            sleep(2);
        }

        return $caPrev;
    }

    /**
     * @param $result
     * @return mixed
     */
    public function createExcelFileForCaPrev($result,$schedule=0,$locale='fr'){
        $workingHours=$this->restaurantService->getWorkingHours();
        if($locale=='fr'){
            setlocale(LC_TIME, "fr_FR", "French","fr_FR.utf8");
        }else{
            setlocale(LC_TIME, "nl_NL", "Dutch",'nl_NL.utf8');
        }

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $phpExcelObject->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle($this->translator->trans('budget_prev_export.title'));
        switch ($schedule) {
            case 1:
                $title=$this->translator->trans('budget_prev_export.title_excel_half_hour');
                break;
            case 2:
                $title=$this->translator->trans('budget_prev_export.title_excel');
                break;
            default:
                $title=$this->translator->trans('budget_prev_export.title_excel_hour');
        }

        $sheet->mergeCells("B2:H4");
        $sheet->setCellValue('B2', $title);
        ExcelUtilities::setCellAlignment($sheet->getCell("B2"), $alignment = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B2"), $alignment = \PHPExcel_Style_Alignment::VERTICAL_CENTER);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 2), 22, true);

        reset($result);
        $from = key($result);
        end($result);
        $to = key($result);

        $sheet->mergeCells("J2:M2");
        $sheet->setCellValue('J2', $this->translator->trans('budget_prev_export.periode')." : ".$this->translator->trans('budget_prev_export.from').'  '.$from.'  '.$this->translator->trans('budget_prev_export.to').'  '.$to);

        $sheet->mergeCells("A6:B6");


        reset($result);
        $cellNumber = 3;
        foreach($result as $lineResult){
            unset($lineResult[24]);
            foreach ($lineResult as $key => $line) {
                if (!in_array($key, $workingHours)) {
                    continue;
                }
                $cell = $this->documentGenerator->getNameFromNumber($cellNumber);
                if($schedule==0){
                    $sheet->setCellValue($cell.'6', $key.':00');
                }elseif ($schedule==1){
                    $sheet->setCellValue($cell.'6', $key.':00');
                    ExcelUtilities::setBackgroundColor($sheet->getCell($cell.'6', $key), "E5CFAB");
                    ExcelUtilities::setCellAlignment($sheet->getCell($cell.'6', $key),\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $cellNumber++;
                    $cell = $this->documentGenerator->getNameFromNumber($cellNumber);
                    $sheet->setCellValue($cell.'6', $key.':30');
                }elseif ($schedule==2){
                    $sheet->setCellValue($cell.'6', $key.':00');
                    ExcelUtilities::setBackgroundColor($sheet->getCell($cell.'6', $key), "E5CFAB");
                    ExcelUtilities::setCellAlignment($sheet->getCell($cell.'6', $key),\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $cellNumber++;
                    $cell = $this->documentGenerator->getNameFromNumber($cellNumber);
                    $sheet->setCellValue($cell.'6', $key.':15');
                    ExcelUtilities::setBackgroundColor($sheet->getCell($cell.'6', $key), "E5CFAB");
                    ExcelUtilities::setCellAlignment($sheet->getCell($cell.'6', $key),\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $cellNumber++;
                    $cell = $this->documentGenerator->getNameFromNumber($cellNumber);
                    $sheet->setCellValue($cell.'6', $key.':30');
                    ExcelUtilities::setBackgroundColor($sheet->getCell($cell.'6', $key), "E5CFAB");
                    ExcelUtilities::setCellAlignment($sheet->getCell($cell.'6', $key),\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $cellNumber++;
                    $cell = $this->documentGenerator->getNameFromNumber($cellNumber);
                    $sheet->setCellValue($cell.'6', $key.':45');
                }
                ExcelUtilities::setBackgroundColor($sheet->getCell($cell.'6', $key), "E5CFAB");
                ExcelUtilities::setCellAlignment($sheet->getCell($cell.'6', $key),\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $cellNumber++;
            }
            $cell = $this->documentGenerator->getNameFromNumber($cellNumber);
            $sheet->setCellValue($cell.'6', $this->translator->trans('budget_prev_export.total'));
            ExcelUtilities::setBackgroundColor($sheet->getCell($cell.'6', 24), "a8d4ff");
            ExcelUtilities::setCellAlignment($sheet->getCell($cell.'6', 24),\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            break;
        }
        $lineNumber = 7;
        foreach($result as $keyDate => $lineResult){
            $sheet->mergeCells('A'.$lineNumber.':B'.$lineNumber);
            $sheet->setCellValue('A'.$lineNumber, $keyDate);
            ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineNumber, $key), "E5CFAB");
            ExcelUtilities::setCellAlignment($sheet->getCell('A'.$lineNumber), $alignment = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $dayOfWeek = strftime("%A", strtotime($keyDate));
            $sheet->setCellValue('C'.$lineNumber, $dayOfWeek);
            ExcelUtilities::setBackgroundColor($sheet->getCell('C'.$lineNumber, $key), "f0e3ce");
            ExcelUtilities::setCellAlignment($sheet->getCell('C'.$lineNumber), $alignment = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $cellNumber = 3;
            $total=$lineResult[24];
            unset($lineResult[24]);
            foreach ($lineResult as $key => $line) {
                if (!in_array($key, $workingHours)) {
                    continue;
                }
                $cell = $this->documentGenerator->getNameFromNumber($cellNumber);
                if($schedule == 0 ){
                    $sheet->setCellValue($cell.$lineNumber, number_format($line, 2, '.', ''));
                    $sheet->getStyle($cell.$lineNumber)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                }elseif ($schedule == 1){
                    $sheet->setCellValue($cell.$lineNumber, number_format($line[0], 2, '.', ''));
                    $sheet->getStyle($cell.$lineNumber)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                    $cellNumber++;
                    $cell = $this->documentGenerator->getNameFromNumber($cellNumber);
                    $sheet->setCellValue($cell.$lineNumber, number_format($line[1], 2, '.', ''));
                    $sheet->getStyle($cell.$lineNumber)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                }elseif ($schedule == 2){
                    $sheet->setCellValue($cell.$lineNumber, number_format($line[0], 2, '.', ''));
                    $sheet->getStyle($cell.$lineNumber)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                    $cellNumber++;
                    $cell = $this->documentGenerator->getNameFromNumber($cellNumber);
                    $sheet->setCellValue($cell.$lineNumber, number_format($line[1], 2, '.', ''));
                    $sheet->getStyle($cell.$lineNumber)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                    $cellNumber++;
                    $cell = $this->documentGenerator->getNameFromNumber($cellNumber);
                    $sheet->setCellValue($cell.$lineNumber, number_format($line[2], 2, '.', ''));
                    $sheet->getStyle($cell.$lineNumber)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                    $cellNumber++;
                    $cell = $this->documentGenerator->getNameFromNumber($cellNumber);
                    $sheet->setCellValue($cell.$lineNumber, number_format($line[3], 2, '.', ''));
                    $sheet->getStyle($cell.$lineNumber)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
                }
                $cellNumber++;
            }
            $cell = $this->documentGenerator->getNameFromNumber($cellNumber);
            $sheet->setCellValue($cell.$lineNumber, number_format($total, 2, '.', ''));
            ExcelUtilities::setFont($sheet->getCell($cell.$lineNumber, 24), null,true);
            $sheet->getStyle($cell.$lineNumber)->getNumberFormat()->setFormatCode( \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
            $lineNumber++;
        }

        //Creation de la response
        $filename = "Budget_prev" . date('dmY_His').".xls";
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
