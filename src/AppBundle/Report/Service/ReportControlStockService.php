<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 14/04/2016
 * Time: 17:20
 */

namespace AppBundle\Report\Service;

use AppBundle\Administration\Service\CaPrevService;
use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Service\ProductService;
use AppBundle\Report\Entity\ControlStockTmp;
use AppBundle\Report\Entity\ControlStockTmpDay;
use AppBundle\Report\Entity\ControlStockTmpProduct;
use AppBundle\Report\Entity\ControlStockTmpProductDay;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ReportControlStockService
{
    /**
     * @var EntityManager
     */
    private $em;

    private $caPrevService;

    private $productService;

    /**
     * @var Factory
     */
    private $phpExcel;

    private $tmpDir;

    private $translator;

    private $days;

    private $months;

    public function __construct(
        EntityManager $entityManager,
        CaPrevService $caPrevService,
        ProductService $productService,
        Factory $phpExcel,
        Translator $translator,
        $tmpDir
    ) {
        $this->em = $entityManager;
        $this->caPrevService = $caPrevService;
        $this->productService = $productService;
        $this->phpExcel = $phpExcel;
        $this->tmpDir = $tmpDir;
        $this->translator = $translator;

        $this->days = [
            $this->translator->trans('days.sunday'),
            $this->translator->trans('days.monday'),
            $this->translator->trans('days.tuesday'),
            $this->translator->trans('days.wednesday'),
            $this->translator->trans('days.thursday'),
            $this->translator->trans('days.friday'),
            $this->translator->trans('days.saturday'),
        ];

        $this->months = [
            $this->translator->trans('months.jan'),
            $this->translator->trans('months.feb'),
            $this->translator->trans('months.mar'),
            $this->translator->trans('months.apr'),
            $this->translator->trans('months.mai'),
            $this->translator->trans('months.jun'),
            $this->translator->trans('months.jul'),
            $this->translator->trans('months.aug'),
            $this->translator->trans('months.sep'),
            $this->translator->trans('months.oct'),
            $this->translator->trans('months.nov'),
            $this->translator->trans('months.dec'),
        ];
    }

    public function createControlReport(
        ControlStockTmp $controlStockTmp,
        ImportProgression $progression,
        Restaurant $restaurant
    ) {

        //Disable logging for performance
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $base = $this->em->getRepository("Merchandise:CoefBase")->findOneBy(
            array(
                "originRestaurant" => $restaurant,
            ),
            array(
                'id' => 'DESC',
            )
        );

        //Reset tables
        $this->resetControlStockTmpTables($controlStockTmp);

        $sheetModel = $controlStockTmp->getSheet();

        $startDate = $controlStockTmp->getStartDate();

        $endDate = $controlStockTmp->getEndDate();

        $progression->setTotalElements(count($sheetModel->getLines()));
        $this->em->flush();


        //Calcul du CA réalisé sur J-7
        $controlStockTmp->setCa($base->getCa());

        $d1 = $base->getStartDate();
        $d2 = $base->getEndDate();

        $controlStockTmp->setD1($d1);
        $controlStockTmp->setD2($d2);

        //Creation des instances ControlStockTmpDay
        //Calcul du Budget prévisionnel elementaire et cumulé pour chaque date
        $period = $endDate->diff($startDate)->days + 1;

        $days = [];
        $budCumul = 0;
        for ($i = 0; $i < $period; $i++) {
            $d = Utilities::getDateFromDate($startDate, $i);
            $bud = $this->caPrevService->createIfNotExsit($d);
            $budCumul = $budCumul + $bud;
            $controlStockTmpDay = new ControlStockTmpDay();
            $controlStockTmpDay
                ->setCaPrev($bud)
                ->setCaPrevCum($budCumul)
                ->setDate($d)
                ->setControlStockTmp($controlStockTmp);
            $days[$i] = clone $controlStockTmpDay;
            $this->em->persist($days[$i]);
        }
        $this->em->flush();

        usort(
            $days,
            function (ControlStockTmpDay $d1, ControlStockTmpDay $d2) {
                if ($d1->getDate()->format('Y/m/d') < $d2->getDate()->format('Y/m/d')) {
                    return 1;
                }

                return -1;
            }
        );

        //calculate all stock qty
        $productIds = [];
        foreach ($sheetModel->getLines() as $l) {
                $productIds[] = $l->getProduct()->getId();
        }
        $yesterday = Utilities::getDateFromDate(new \DateTime('TODAY'), -1);
        $stockForAllProducts = $this->productService->getStockForProductsAtDate($yesterday, $productIds, true);
        //Creation des instances des ControlStockTmpProduct
        //Calcul des Coefficients
        //Consulter le Stock

        foreach ($sheetModel->getLines() as $l) {
            $product = $l->getProduct();
            $pTmp = new ControlStockTmpProduct();
            $pTmp->setProduct($product)
                ->setOrder($l->getOrderInSheet());
            $this->em->persist($pTmp);
            $pTmp->setTmp($controlStockTmp);
            $coefData = $this->productService->getCoefForPP($product, $d1, $d2, $base->getCa());
            //Calculer la qte en stock de la veille
            $stock = $stockForAllProducts[$product->getId()]['initial_stock'];
            $stock = $stock < 0 ? 0 : $stock;

            $pTmp->setConsoReal($coefData['conso_real'])
                ->setConsoTheo($coefData['conso_theo'])
                ->setStockType($coefData['type'])
                ->setStock($stock);

            //get The coef
            $coeffLine = $this->em->getRepository("Merchandise:Coefficient")->findOneBy(
                array('product' => $product, 'base' => $base)
            );
            if($coeffLine)
            {
                $pTmp->setCoef($coeffLine->getCoef());
            }
            else
            {
                $pTmp->setCoef(0);
            }

            //Foreach days Creation du  ControlStockTmpProductDate
            //Calcul du besoin
            //Calcul du Liv
            foreach ($days as $d) {
                $prdDayTmp = new ControlStockTmpProductDay();
                $prdDayTmp
                    ->setDay($d)
                    ->setProductTmp($pTmp);
                $need = 0;
                if ($pTmp->getCoef() != 0) {
                    $need = $d->getCaPrevCum() / $pTmp->getCoef();
                }


                //Recupérer la LP Avant J2
                //Récupérer les quantités des commande "envoyé" et "en cours d'envoi" et "modifie aprs envoie"
                $orderlines = $this->em->getRepository("Merchandise:OrderLine")
                    ->getOrderLineToBeDeliveredInDate($product, $d->getDate(), $restaurant);
                $lp = 0;
                foreach ($orderlines as $ol) {
                    $lp = $lp + ($ol->getQty() * $product->getInventoryQty());
                }
                $prdDayTmp
                    ->setLiv($lp)
                    ->setNeed($need);

                //echo "Product ".$product->getName()." ".$product->getId()." / Jour ".$d->getDate()->format('d/m/Y')." / Livraison => $lp / Need => $need \n";

                $this->em->persist($prdDayTmp);
            }

            $progression->incrementProgression();
            $this->em->flush();
        }
        //End foreach products

        //Increment progress
        $progression->setStatus('finish');

        return $controlStockTmp;
    }

    public function createExcelFile(ControlStockTmp $controlStockTmp)
    {

        $row = 17;
        $col = 9;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle($this->translator->trans('control_stock.title'));

        $sheet->mergeCells("B14:F14");
        $sheet->setCellValue('B14', $this->translator->trans('control_stock.ca_prev')." >> ");
        ExcelUtilities::setFont($sheet->getCell('B14'), 11, true);
        ExcelUtilities::setCellAlignment(
            $sheet->getCell("B14"),
            $alignment = \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT
        );
        ExcelUtilities::setBackgroundColor($sheet->getCell("B14"), "ECECEC");

        $sheet->setCellValue('B15', $this->translator->trans('control_stock.cumul_ca')." >> ");
        $sheet->mergeCells("B15:F15");
        ExcelUtilities::setFont($sheet->getCell('B15')->getStyle(), 11, true);
        ExcelUtilities::setCellAlignment(
            $sheet->getCell("B15"),
            $alignment = \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT
        );
        ExcelUtilities::setBackgroundColor($sheet->getCell("B15"), "ECECEC");

        $sheet->setCellValue("B16", $this->translator->trans('product.code'));
        $sheet->setCellValue("C16", $this->translator->trans('article'));
        $sheet->setCellValue("D16", $this->translator->trans('control_stock.inventory_unit'));
        $sheet->setCellValue("E16", $this->translator->trans('control_stock.coef'));
        $sheet->setCellValue("F16", $this->translator->trans('control_stock.en_stock'));

        $sheet->mergeCells("G12:G13");
        $sheet->mergeCells("H12:H13");
        $sheet->mergeCells("I12:I13");
        $sheet->setCellValue('G12', $this->translator->trans('control_stock.dlc_1'));
        $sheet->setCellValue('H12', $this->translator->trans('control_stock.dlc_2'));
        $sheet->setCellValue('I12', $this->translator->trans('control_stock.transfers_pending'));
        $sheet->getCell("I12")->getStyle()->getAlignment()->setWrapText(true);

        ExcelUtilities::setBorder($sheet->getStyle("B14:F14"));
        ExcelUtilities::setBorder($sheet->getStyle("B15:F15"));
        ExcelUtilities::setBorder($sheet->getStyle("G12:G13"));
        ExcelUtilities::setBorder($sheet->getStyle("H12:H13"));
        ExcelUtilities::setBorder($sheet->getStyle("I12:I13"));

        ExcelUtilities::setBorder($sheet->getStyle("B16"));
        ExcelUtilities::setBorder($sheet->getStyle("C16"));
        ExcelUtilities::setBorder($sheet->getStyle("D16"));
        ExcelUtilities::setBorder($sheet->getStyle("E16"));
        ExcelUtilities::setBorder($sheet->getStyle("F16"));

        ExcelUtilities::setBorder($sheet->getStyleByColumnAndRow(6, 14, 8, 16));
        $sheet->getColumnDimension("C")->setWidth("30");
        $sheet->getColumnDimension("D")->setWidth("30");

        //Base de calcul
        $basedeclacul = $this->translator->trans('control_stock.base_calcul').": ".$controlStockTmp->getD1()->format(
            "d/m/Y"
        )." - ".$controlStockTmp->getD2()->format("d/m/Y")."\n";
        $basedeclacul .= $this->translator->trans('control_stock.ca').": ".number_format(
            $controlStockTmp->getCa(),
            2,
            ',',
            ''
        )." (€)";
        $basedeclacul .= "  -  Modèle de la feuille : ".$controlStockTmp->getSheet()->getLabel();
        $sheet->setCellValueByColumnAndRow(1, 12, $basedeclacul);
        $sheet->getStyleByColumnAndRow(1, 12, 5, 13)->getAlignment()->setWrapText(true);
        $sheet->mergeCellsByColumnAndRow(1, 12, 5, 13);
        ExcelUtilities::setBorder($sheet->getStyleByColumnAndRow(1, 12, 5, 13));
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 12, 5, 13), null, true);


        $l = $col;
        foreach ($controlStockTmp->getDays() as $d) {
            $day = $this->days[intval($d->getDate()->format('w'))];
            $sheet->setCellValueByColumnAndRow($l, 12, ucfirst($day));
            $sheet->mergeCellsByColumnAndRow($l, 12, $l + 1, 12);
            ExcelUtilities::setCellAlignment($sheet->getCellByColumnAndRow($l, 12));
            $month = $this->months[intval($d->getDate()->format('m')) - 1];
            $sheet->setCellValueByColumnAndRow($l, 13, $d->getDate()->format("d")." ".ucfirst($month));
            $sheet->mergeCellsByColumnAndRow($l, 13, $l + 1, 13);
            ExcelUtilities::setCellAlignment($sheet->getCellByColumnAndRow($l, 13));

            $sheet->setCellValueByColumnAndRow($l, 14, number_format($d->getCaPrev(), 2, ',', '')." € ");
            $sheet->mergeCellsByColumnAndRow($l, 14, $l + 1, 14);
            ExcelUtilities::setCellAlignment($sheet->getCellByColumnAndRow($l, 14));

            $sheet->setCellValueByColumnAndRow($l, 15, number_format($d->getCaPrevCum(), 2, ',', '')." € ");
            $sheet->mergeCellsByColumnAndRow($l, 15, $l + 1, 15);
            ExcelUtilities::setBackgroundColor($sheet->getCellByColumnAndRow($l, 15), "CDDAB4");
            ExcelUtilities::setCellAlignment($sheet->getCellByColumnAndRow($l, 15));

            $sheet->setCellValueByColumnAndRow($l, 16, "Besoin");
            $sheet->setCellValueByColumnAndRow($l + 1, 16, "Liv");

            ExcelUtilities::setBorder($sheet->getStyleByColumnAndRow($l, 12, $l + 1, 12));
            ExcelUtilities::setBorder($sheet->getStyleByColumnAndRow($l, 13, $l + 1, 13));
            ExcelUtilities::setBorder($sheet->getStyleByColumnAndRow($l, 14, $l + 1, 14));
            ExcelUtilities::setBorder($sheet->getStyleByColumnAndRow($l, 15, $l + 1, 15));
            ExcelUtilities::setBorder($sheet->getCellByColumnAndRow($l, 16));
            ExcelUtilities::setBorder($sheet->getCellByColumnAndRow($l + 1, 16));

            $l += 2;
        }

        $sheet->getColumnDimensionByColumn($l)->setWidth("20");
        $sheet->getColumnDimensionByColumn($l + 1)->setWidth("20");

        $sheet->setCellValueByColumnAndRow($l, 12, $this->translator->trans('control_stock.total'));
        $sheet->mergeCellsByColumnAndRow($l, 12, $l + 1, 13);
        ExcelUtilities::setBackgroundColor($sheet->getCellByColumnAndRow($l, 12), "ECECEC");
        ExcelUtilities::setCellAlignment($sheet->getCellByColumnAndRow($l, 12));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCellByColumnAndRow($l, 12));
        ExcelUtilities::setBorder($sheet->getStyleByColumnAndRow($l, 12, $l + 1, 13));

        $sheet->setCellValueByColumnAndRow($l, 14, number_format($controlStockTmp->getTotalCaPrev(), 2, ',', '')." € ");
        $sheet->mergeCellsByColumnAndRow($l, 14, $l + 1, 15);
        ExcelUtilities::setBackgroundColor($sheet->getCellByColumnAndRow($l, 14), "CDDAB4");
        ExcelUtilities::setCellAlignment($sheet->getCellByColumnAndRow($l, 14));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCellByColumnAndRow($l, 14));
        ExcelUtilities::setFont($sheet->getCellByColumnAndRow($l, 14), null, true);
        ExcelUtilities::setBorder($sheet->getStyleByColumnAndRow($l, 14, $l + 1, 15));

        $sheet->setCellValueByColumnAndRow($l, 16, $this->translator->trans('control_stock.stock_liv'));
        ExcelUtilities::setCellAlignment($sheet->getCellByColumnAndRow($l, 16));
        ExcelUtilities::setBorder($sheet->getCellByColumnAndRow($l, 16));

        $sheet->setCellValueByColumnAndRow($l + 1, 16, $this->translator->trans('control_stock.total_need'));
        ExcelUtilities::setCellAlignment($sheet->getCellByColumnAndRow($l + 1, 16));
        ExcelUtilities::setBorder($sheet->getCellByColumnAndRow($l + 1, 16));

        $l += 2;
        $sheet->setCellValueByColumnAndRow($l, 12, $this->translator->trans('control_stock.diff'));
        $sheet->mergeCellsByColumnAndRow($l, 12, $l, 16);
        ExcelUtilities::setCellAlignment($sheet->getCellByColumnAndRow($l, 12));
        ExcelUtilities::setVerticalCellAlignment($sheet->getCellByColumnAndRow($l, 12));
        ExcelUtilities::setBorder($sheet->getStyleByColumnAndRow($l, 12, $l, 16));
        ExcelUtilities::setBackgroundColor($sheet->getStyleByColumnAndRow($l, 12, $l, 16), "b4c6fe");
        $sheet->getColumnDimensionByColumn($l)->setWidth("20");

        $j = $row;
        $lastCol = $l;


        ExcelUtilities::setBackgroundColor(
            $sheet->getStyleByColumnAndRow(1, $row - 1, $lastCol - 1, $row - 1),
            "f09800"
        );
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, $row - 1, $lastCol - 1, $row - 1), null, true);

        $lastCat = null;

        foreach ($controlStockTmp->getProducts() as $p) {
            //$p = new ControlStockTmpProduct();
            //            if ($lastCat != $p->getProduct()->getProductCategory()->getId()) {
            //                $sheet->setCellValue("B$j", $p->getProduct()->getProductCategory()->getName());
            //                $sheet->mergeCellsByColumnAndRow(1, $j, $lastCol, $j);
            //                $lastCat = $p->getProduct()->getProductCategory()->getId();
            //                ExcelUtilities::setBackgroundColor($sheet->getCellByColumnAndRow(1, $j), "c1ddc0");
            //                ExcelUtilities::setFont($sheet->getCellByColumnAndRow(1, $j), null, true);
            //                $j++;
            //            }

            $sheet->setCellValue("B$j", $p->getProduct()->getExternalId());
            $sheet->setCellValue("C$j", $p->getProduct()->getName());

            ExcelUtilities::setBackgroundColor($sheet->getCell("B$j"), "ffe58f");
            ExcelUtilities::setBackgroundColor($sheet->getCell("C$j"), "ffe58f");

            $sheet->setCellValue("D$j", $this->translator->trans($p->getProduct()->getLabelUnitInventory()));
            $sheet->setCellValue("E$j", number_format($p->getCoef(), 2, ',', ''));
            $sheet->setCellValue("F$j", number_format($p->getStock(), 2, ',', ''));

            ExcelUtilities::setBackgroundColor($sheet->getCell("F$j"), "CDDAB4");

            $l = $col;
            foreach ($p->getDays() as $d) {
                //$d = new ControlStockTmpProductDay();
                $sheet->setCellValueByColumnAndRow($l, $j, number_format($d->getNeed(), 2, ',', ''));
                $sheet->setCellValueByColumnAndRow($l + 1, $j, number_format($d->getLiv(), 2, ',', ''));
                ExcelUtilities::setBackgroundColor($sheet->getCellByColumnAndRow($l + 1, $j), "CDDAB4");
                $l += 2;
            }

            $sheet->setCellValueByColumnAndRow($l, $j, number_format($p->getStock() + $p->getTotalLiv(), 2, ',', ''));
            $sheet->setCellValueByColumnAndRow($l + 1, $j, number_format($p->getTotalNeed(), 2, ',', ''));
            $sheet->setCellValueByColumnAndRow(
                $l + 2,
                $j,

                number_format($p->getStock() + $p->getTotalLiv() - $p->getTotalNeed(), 2, ',', '')
            );
            ExcelUtilities::setBackgroundColor($sheet->getCellByColumnAndRow($l + 2, $j), "b4c6fe");
            $j++;
        }

        ExcelUtilities::setBorder($sheet->getStyleByColumnAndRow(1, $row, $l + 2, $j - 1));

        //Entete
        $sheet->setCellValueByColumnAndRow(
            1,
            2,
            $this->translator->trans('control_stock.document_generated')." ".date('d/m/Y H:i:s')
        );
        $sheet->mergeCellsByColumnAndRow(1, 2, $lastCol, 2);
        ExcelUtilities::setCellAlignment(
            $sheet->getStyleByColumnAndRow(1, 2),
            \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT
        );

        $sheet->mergeCellsByColumnAndRow(1, 3, $lastCol, 6);
        $sheet->setCellValueByColumnAndRow(1, 3, $this->translator->trans('control_stock.rapport_title'));
        ExcelUtilities::setCellAlignment(
            $sheet->getStyleByColumnAndRow(1, 3),
            \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        );
        ExcelUtilities::setVerticalCellAlignment(
            $sheet->getStyleByColumnAndRow(1, 3),
            \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        );
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 3), 22, true);

        $sheet->mergeCellsByColumnAndRow(1, 7, $lastCol, 10);
        $sheet->setCellValueByColumnAndRow(
            1,
            7,
            "Période Du ".$controlStockTmp->getStartDate()->format('d/m/y')." au ".$controlStockTmp->getEndDate(
            )->format('d/m/y')
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getStyleByColumnAndRow(1, 7),
            \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        );
        ExcelUtilities::setVerticalCellAlignment(
            $sheet->getStyleByColumnAndRow(1, 7),
            \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        );
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 7), 14, true);

        //Creation de la response
        $filename = "control_stock_".$controlStockTmp->getStartDate()->format('Y_m_d')."_".$controlStockTmp->getEndDate(
        )->format('Y_m_d').".xls";
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

    private function resetControlStockTmpTables(ControlStockTmp $tmp)
    {
        $sql = [];

        $sql[] = "DELETE FROM control_stock_tmp_product_day
                  WHERE day_id IN (
                  SELECT id from control_stock_tmp_day
                  WHERE control_stock_tmp_id = :productID);";
        $sql[] = "DELETE FROM control_stock_tmp_product where tmp_id = :productID";
        $sql[] = "DELETE from control_stock_tmp_day  WHERE control_stock_tmp_id = :productID";

        foreach ($sql as $s) {
            $stm = $this->em->getConnection()->prepare($s);
            $stm->bindParam('productID', $tmp->getId());
            $stm->execute();
        }
    }
}
