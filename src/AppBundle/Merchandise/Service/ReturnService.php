<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 04/03/2016
 * Time: 12:00
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Returns;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Knp\Snappy\Pdf;
use Liuggio\ExcelBundle\Factory;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class ReturnService
{

    private $em;
    private $productService;
    private $twig;
    private $pdfGenerator;
    private $tmpDir;
    private $productPurchasedMvmtService;
    private $phpExcel;
    private $translator;

    public function __construct(
        EntityManager $em,
        ProductService $productService,
        TwigEngine $twigEngine,
        Pdf $loggableGenerator,
        $tmpDir,
        ProductPurchasedMvmtService $productPurchasedMvmtService,
        Factory $factory,
        Translator $translator
    ) {
        $this->em = $em;
        $this->productService = $productService;
        $this->twig = $twigEngine;
        $this->pdfGenerator = $loggableGenerator;
        $this->tmpDir = $tmpDir;
        $this->productPurchasedMvmtService = $productPurchasedMvmtService;
        $this->phpExcel = $factory;
        $this->translator = $translator;
    }

    public function createReturn(Returns $return,$restaurant)
    {

        try {
            $this->em->beginTransaction();

            $val = 0;
            foreach ($return->getLines() as $l) {
                $val += $l->getValorization();
                if ($l->getProduct()->getPrimaryItem() != null) {
                    $this->productService->updateStock($l->getProduct()->getPrimaryItem(), (-1) * $l->total());
                }
                $this->productService->updateStock($l->getProduct(), (-1) * $l->getTotal());
            }

            $return->setValorization($val);

            $this->em->persist($return);
            $this->productPurchasedMvmtService->createMvmtEntryForReturn($return,$restaurant, false);
            $this->em->flush();

            $this->em->commit();

            return true;
        } catch (\Exception $e) {
            $this->em->commit();

            return false;
        }
    }

    public function createReturnForDownload(Returns $return)
    {
        $val = 0;
        foreach ($return->getLines() as $l) {
            $val += $l->getValorization();
        }
        $return->setValorization($val);
    }

    public function getList($currentRestaurant, $criteria, $order, $limit, $offset, $onlyList)
    {
        $list = $this->em->getRepository(Returns::class)
            ->getList($currentRestaurant, $criteria, $order, $offset, $limit, $onlyList);

        return $this->serializeReturnsList($list);
    }

    public function generateBon(Returns $return)
    {
        $html = $this->twig->render(
            "@Merchandise/Returns/return_print.html.twig",
            array('return' => $return)
        );

        $file_path = $this->tmpDir."/return_".hash('md5', date('Y/m/d H:i:s')).".pdf";

        $this->pdfGenerator->generateFromHtml($html, $file_path);

        return $file_path;
    }

    /**
     * @param Returns[] $list
     * @return array
     */
    public function serializeReturnsList($list)
    {

        $data = [];

        foreach ($list as $l) {
            $data[] = array(
                'id' => $l->getId(),
                'supplier' => $l->getSupplier()->getName(),
                'date' => $l->getDate()->format('d/m/Y'),
                'responsible' => $l->getEmployee()->getFirstName()." ".$l->getEmployee()->getLastName(),
                'valorization' => $l->getValorization(),
            );
        }

        return $data;
    }

    public function generateExcelFile(Restaurant $currentRestaurant, $criteria, $orderBy, $logoPath)
    {

        $data = $this->getList($currentRestaurant, $criteria, $orderBy, null, null, true);
        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle($this->translator->trans('return_list'));

        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('return_list');
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


        //table
        $sheet->mergeCells("C10:D10");
        ExcelUtilities::setFont($sheet->getCell('C10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C10"), "ECECEC");
        $sheet->setCellValue('C10', $this->translator->trans('filter.supplier'));
        ExcelUtilities::setBorder($sheet->getCell('C10'));
        ExcelUtilities::setBorder($sheet->getCell('D10'));


        $sheet->mergeCells("E10:F10");
        ExcelUtilities::setFont($sheet->getCell('E10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E10"), "ECECEC");
        $sheet->setCellValue('E10', $this->translator->trans('return_date'));
        ExcelUtilities::setBorder($sheet->getCell('E10'));
        ExcelUtilities::setBorder($sheet->getCell('F10'));

        $sheet->mergeCells("G10:H10");
        ExcelUtilities::setFont($sheet->getCell('G10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G10"), "ECECEC");
        $sheet->setCellValue('G10', $this->translator->trans('return_responsible'));
        ExcelUtilities::setBorder($sheet->getCell('G10'));
        ExcelUtilities::setBorder($sheet->getCell('H10'));


        $sheet->mergeCells("I10:J10");
        ExcelUtilities::setFont($sheet->getCell('I10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I10"), "ECECEC");
        $sheet->setCellValue('I10', $this->translator->trans('return_valorization'));
        ExcelUtilities::setBorder($sheet->getCell('I10'));
        ExcelUtilities::setBorder($sheet->getCell('J10'));

        $startLine = 11;
        foreach ($data as $key => $line) {
            $sheet->mergeCells("C".$startLine.":D".$startLine);
            $sheet->setCellValue('C'.$startLine, $line['supplier']);
            ExcelUtilities::setBorder($sheet->getCell('C'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('D'.$startLine));
            $sheet->getStyle('C'.$startLine)->getAlignment()->setWrapText(true);

            $sheet->mergeCells("E".$startLine.":F".$startLine);
            $sheet->setCellValue('E'.$startLine, $line['date']);
            ExcelUtilities::setBorder($sheet->getCell('E'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('F'.$startLine));

            $sheet->mergeCells("G".$startLine.":H".$startLine);
            $sheet->setCellValue('G'.$startLine, $line['responsible']);
            ExcelUtilities::setBorder($sheet->getCell('G'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('H'.$startLine));

            $sheet->mergeCells("I".$startLine.":J".$startLine);
            $sheet->setCellValue('I'.$startLine, $line['valorization']);
            ExcelUtilities::setBorder($sheet->getCell('I'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('J'.$startLine));


            $startLine++;
        }
        $filename = "liste_des_retours".date('dmY_His').".xls";
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
