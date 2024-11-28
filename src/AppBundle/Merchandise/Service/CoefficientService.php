<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 02/05/2016
 * Time: 10:21
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\CoefBase;
use AppBundle\Merchandise\Entity\Coefficient;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class CoefficientService
{

    /**
     * @var EntityManager
     */
    private $em;
    private $phpExcel;
    private $translator;

    /**
     * @var
     */
    private $productService;

    private $restaurantService;

    public function __construct(
        EntityManager $entityManager,
        ProductService $productService,
        RestaurantService $restaurantService,
        Factory $factory,
        Translator $translator

    ) {
        $this->em = $entityManager;
        $this->productService = $productService;
        $this->restaurantService = $restaurantService;
        $this->phpExcel = $factory;
        $this->translator = $translator;
    }

    public function calculateCoeffForPP(CoefBase $base, ImportProgression $progression = null, $loss = null)
    {

        try {
            //Lock the base
            $base->setLocked(true);
            $this->em->flush();

            //clearing old Coeffcient for base
            $this->clearBase($base);

            //Getting activated products for the end date in the current restaurant and eligible for orderHelp
            $products = $this->em->getRepository("Merchandise:ProductPurchased")->getActivatedProductsInDay(
                $base->getEndDate(),
                false,
                $this->restaurantService->getCurrentRestaurant());


            if ($progression) {
                $progression->setTotalElements(count($products))
                    ->setProceedElements(0);
                $this->em->flush();
            }

            //foreach products
            foreach ($products as $product) {
                $coefData = $this->productService
                    ->getCoefForPP(
                        $product,
                        $base->getStartDate(),
                        $base->getEndDate(),
                        $base->getCa(),
                        $loss
                    );

                $coef = $coefData['coef'];
                $consoReal = $coefData['conso_real'];
                $consoTheo = $coefData['conso_theo'];
                $fixed = $coefData['fixed'];
                $type = $coefData['type'];
                $finalStockExist = $coefData['finalStockExist'];
                $stockReal = $coefData['realStock'];
                $stockTheo = $coefData['theoStock'];

                $coefficient = new Coefficient();
                $coefficient->setBase($base)
                    ->setRealStock($stockReal)
                    ->setTheoStock($stockTheo)
                    ->setStockFinalExist($finalStockExist)
                    ->setProduct($product)
                    ->setCoef($coef)
                    ->setHebReal($consoReal)
                    ->setHebTheo($consoTheo)
                    ->setFixed($fixed)
                    ->setType($type);

                $this->em->persist($coefficient);

                if ($progression) {
                    $progression->incrementProgression();
                }

                $this->em->flush();
            }
        } catch (\Exception $e) {
        }

        if ($progression) {
            $progression->setProgress(100)
                ->setStatus('finish');
        }

        //UnLock the base
        $base->setLocked(false);
        $this->em->flush();
    }

    private function clearBase(CoefBase $base)
    {
        foreach ($base->getCoefs() as $c) {
            $this->em->remove($c);
        }
        $this->em->flush();
    }


    public function generateExcelFile(CoefBase $base, Restaurant $currentRestaurant, $logoPath)
    {
        $colorOne = "ECECEC";
        $colorTwo = "E5CFAB";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('coef_report'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('coef_report');
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
        $content = $currentRestaurant->getCode() . ' ' . $currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);

         //data base calcul

        //Calculate base
        $sheet->mergeCells("B10:E10");
        ExcelUtilities::setFont($sheet->getCell('B10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B10"), $colorTwo);
        $sheet->setCellValue('B10', $this->translator->trans('base_calcul') );
        ExcelUtilities::setCellAlignment($sheet->getCell("B10"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B10"), $alignmentV);

        $sheet->mergeCells("B11:E11");
        ExcelUtilities::setFont($sheet->getCell('B11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B11"), $colorOne);
        $sheet->setCellValue('B11', $base->getStartDate()->format('d-m-Y'). '=>'.$base->getEndDate()->format('d-m-Y'));

        //Ca base
        $sheet->mergeCells("F10:I10");
        ExcelUtilities::setFont($sheet->getCell('F10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F10"), $colorTwo);
        $sheet->setCellValue('F10', $this->translator->trans('coef_ca').' (€)' );
        ExcelUtilities::setCellAlignment($sheet->getCell("F10"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("F10"), $alignmentV);

        $sheet->mergeCells("F11:I11");
        ExcelUtilities::setFont($sheet->getCell('F11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F11"), $colorOne);
        ExcelUtilities::setFormat($sheet->getCell('F11'),\PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValue('F11', number_format($base->getCa(),'0','.',''));

        //last modification
        $sheet->mergeCells("J10:N10");
        ExcelUtilities::setFont($sheet->getCell('J10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J10"), $colorTwo);
        $sheet->setCellValue('J10', $this->translator->trans('last_update_date_label') );
        ExcelUtilities::setCellAlignment($sheet->getCell("J10"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("J10"), $alignmentV);

        $sheet->mergeCells("J11:N11");
        ExcelUtilities::setFont($sheet->getCell('J11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J11"), $colorOne);
        $sheet->setCellValue('J11', $base->getUpdatedAt()->format('d-m-Y'));
        //content
        $i=13;

        //Code
        $sheet->mergeCells('A'.$i.':B'.$i);
        ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
        $sheet->setCellValue('A'.$i, $this->translator->trans('product.code'));

        //item inventaire
        $sheet->mergeCells('C'.$i.':G'.$i);
        ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C".$i), $colorOne);
        $sheet->setCellValue('C'.$i, $this->translator->trans('article'));

        //U.exp_U.inv
        $sheet->mergeCells('H'.$i.':K'.$i);
        ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorOne);
        $sheet->setCellValue('H'.$i, $this->translator->trans('u_exp_u_inv'));

        //U.usage
        $sheet->mergeCells('L'.$i.':O'.$i);
        ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L".$i), $colorOne);
        $sheet->setCellValue('L'.$i, $this->translator->trans('u_inv_u_use'));

        //R_T
        $sheet->mergeCells('P'.$i.':Q'.$i);
        ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P".$i), $colorOne);
        $sheet->setCellValue('P'.$i, $this->translator->trans('r_t'));

        //Qte consomme
        $sheet->mergeCells('R'.$i.':T'.$i);
        ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R".$i), $colorOne);
        $sheet->setCellValue('R'.$i, $this->translator->trans('consumed_qty_u_exp'));

        //Coeff
        $sheet->mergeCells('U'.$i.':V'.$i);
        ExcelUtilities::setFont($sheet->getCell('U'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("U".$i), $colorOne);
        $sheet->setCellValue('U'.$i, $this->translator->trans('coef_euro_u_exp'));

        //Fixe
        $sheet->mergeCells('W'.$i.':X'.$i);
        ExcelUtilities::setFont($sheet->getCell('W'.$i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("W".$i), $colorOne);
        $sheet->setCellValue('W'.$i, $this->translator->trans('fixed'));
        //Border
        $cell = 'A';
        while ($cell != 'Y') {
            ExcelUtilities::setBorder($sheet->getCell($cell.$i));
            $cell++;
        }
        $i=14;
        foreach ($base->getCoefs() as $c){
            //Code
            $sheet->mergeCells('A'.$i.':B'.$i);
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            $sheet->setCellValue('A'.$i, $c->getProduct()->getExternalId());

            //item inventaire
            $sheet->mergeCells('C'.$i.':G'.$i);
            ExcelUtilities::setFont($sheet->getCell('C'.$i), 10, true);
            $sheet->setCellValue('C'.$i, $c->getProduct()->getName());

            //U.exp_U.inv
            $sheet->mergeCells('H'.$i.':K'.$i);
            ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
            $sheet->setCellValue('H'.$i,'1 '.$this->translator->trans($c->getProduct()->getLabelUnitExped()).'='.$c->getProduct()->getInventoryQty().' '.$this->translator->trans($c->getProduct()->getLabelUnitInventory()));

            //U.usage
            $sheet->mergeCells('L'.$i.':O'.$i);
            ExcelUtilities::setFont($sheet->getCell('L'.$i), 10, true);
            $sheet->setCellValue('L'.$i, '1 '.$this->translator->trans($c->getProduct()->getLabelUnitInventory()).'='.$c->getProduct()->getUsageQty().' '.$this->translator->trans($c->getProduct()->getLabelUnitUsage()));

            //R_T
            $sheet->mergeCells('P'.$i.':Q'.$i);
            ExcelUtilities::setFont($sheet->getCell('P'.$i), 10, true);
            ExcelUtilities::setFormat($sheet->getCell('P'.$i),\PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValue('P'.$i,$this->translator->trans($c->getType().'_shortcut'));
            if($c->getType()== 'real' && $c->getStockFinalExist()){
                $qt= number_format($c->getHebReal(),'2','.','');
            }else{
                $qt=number_format($c->getHebTheo(),'2','.','');
            }
            //Qte consomme
            $sheet->mergeCells('R'.$i.':T'.$i);
            ExcelUtilities::setFont($sheet->getCell('R'.$i), 10, true);
            $sheet->setCellValue('R'.$i, $qt);

            //Coeff
            $sheet->mergeCells('U'.$i.':V'.$i);
            ExcelUtilities::setFont($sheet->getCell('U'.$i), 10, true);
            ExcelUtilities::setFormat($sheet->getCell('U'.$i),\PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValue('U'.$i, number_format($c->getCoef(),'2','.',''));

            //Fixe
            $sheet->mergeCells('W'.$i.':X'.$i);
            ExcelUtilities::setFont($sheet->getCell('W'.$i), 10, true);
            if($c->getFixed()){
                $fixed= 'Fixé';
            }else{
                $fixed= 'Non Fixé';
            }
            $sheet->setCellValue('W'.$i, $fixed);
            //Border
            $cell = 'A';
            while ($cell != 'Y') {
                ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                $cell++;
            }
            $i++;
        }



        $filename = "Rapport_Coefficient_".date('dmY_His').".xls";
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
