<?php
/**
 * Created by PhpStorm.
 * User: zbessassi
 * Date: 15/02/2019
 * Time: 17:33
 */

namespace AppBundle\Supervision\Service\Reports;

use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DuplicateItemReportService
{

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var Factory $phpExcel
     */
    private $phpExcel;


    public function __construct(EntityManager $em, Factory $phpExcel)
    {
        $this->phpExcel = $phpExcel;
        $this->em = $em;
    }

    /**
     * Générer une fichier Excel contient tous les items intentaire dupliqués
     * @param $version
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \PHPExcel_Exception
     */
    public function generateDuplicateInventoryItemExcelFile($version)
    {

        $colorOne = "e0e0eb";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle('Items inventaire ', 0, 30);


        $sheet->mergeCells("A1:C1");
        $content = 'Nom de restaurant';
        $sheet->setCellValue('A1', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("A1"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A1"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 1), 28, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A1"), $colorOne);
        ExcelUtilities::setBorder($sheet->getStyle("A1:C1"), '000000');

        $sheet->mergeCells("D1:F1");
        $content = "Nom de l'item d'inventaire";
        $sheet->setCellValue('D1', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("D1"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("D1"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 1), 28, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D1"), $colorOne);
        ExcelUtilities::setBorder($sheet->getStyle("D1:F1"), '000000');

        $sheet->mergeCells("G1:I1");
        $content = 'Statut au niveau restaurant';
        $sheet->setCellValue('G1', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("G1"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("G1"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 1), 28, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G1"), $colorOne);
        ExcelUtilities::setBorder($sheet->getStyle("G1:I1"), '000000');

        $sheet->mergeCells("J1:L1");
        $content = 'Statut au niveau supervision';
        $sheet->setCellValue('J1', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("J1"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("J1"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 1), 28, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J1"), $colorOne);
        ExcelUtilities::setBorder($sheet->getStyle("J1:L1"), '000000');


        $sheet->mergeCells("M1:O1");
        $content = 'éligible par le restaurant';
        $sheet->setCellValue('M1', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("M1"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("M1"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 1), 28, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M1"), $colorOne);
        ExcelUtilities::setBorder($sheet->getStyle("M1:O1"), '000000');


        $sheet->mergeCells("P1:Q1");
        $content = 'Commentaire';
        $sheet->setCellValue('P1', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("P1"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("P1"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 1), 28, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P1"), $colorOne);
        ExcelUtilities::setBorder($sheet->getStyle("P1:Q1"), '000000');

        $result = $this->getResult();
        $startLine = 2;
        $count = 0;
        foreach ($result as $r) {

            $sheet->mergeCells("A" . $startLine . ":C" . $startLine);
            $content = $r['restaurantname'] . "(" . $r['restaurantcode'] . ")"."(id= ".$r['restaurant_id'].")" ;
            $sheet->setCellValue('A' . $startLine, $content);
            ExcelUtilities::setCellAlignment($sheet->getCell("A" . $startLine), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A" . $startLine), $alignmentV);
            ExcelUtilities::setBorder($sheet->getStyle(("A" . $startLine . ":C" . $startLine)), '000000');
            ExcelUtilities::setBackgroundColor($sheet->getCell("A" . $startLine), 'ffffff');


            $sheet->mergeCells("D" . $startLine . ":F" . $startLine);
            $content = $r['productname'] . "(" . $r['productexternalid'] . ")"."(id=".$r['productid'].")";
            $sheet->setCellValue('D' . $startLine, $content);
            ExcelUtilities::setCellAlignment($sheet->getCell("D" . $startLine), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell("D" . $startLine), $alignmentV);
            ExcelUtilities::setBorder($sheet->getStyle(("D" . $startLine . ":F" . $startLine)), '000000');
            ExcelUtilities::setBackgroundColor($sheet->getCell("D" . $startLine), 'ffffff');

            $sheet->mergeCells("G" . $startLine . ":I" . $startLine);
            $content = $r['productstatus'];
            $sheet->setCellValue('G' . $startLine, $content);
            ExcelUtilities::setCellAlignment($sheet->getCell("G" . $startLine), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell("G" . $startLine), $alignmentV);
            ExcelUtilities::setBorder($sheet->getStyle(("G" . $startLine . ":I" . $startLine)), '000000');
            ExcelUtilities::setBackgroundColor($sheet->getCell("G" . $startLine), 'ffffff');


            $sheet->mergeCells("J" . $startLine . ":L" . $startLine);
            $content = $r['productsupstatus'] ? 'actif' : 'inactif';
            $sheet->setCellValue('J' . $startLine, $content);
            ExcelUtilities::setCellAlignment($sheet->getCell("J" . $startLine), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell("J" . $startLine), $alignmentV);
            ExcelUtilities::setBorder($sheet->getStyle(("J" . $startLine . ":L" . $startLine)), '000000');
            ExcelUtilities::setBackgroundColor($sheet->getCell("J" . $startLine), 'ffffff');

            $sheet->mergeCells("M" . $startLine . ":O" . $startLine);
            $content = $r['eligible_par_restaurant'] ? 'Oui' : 'Non';
            $sheet->setCellValue('M' . $startLine, $content);
            ExcelUtilities::setCellAlignment($sheet->getCell("M" . $startLine), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell("M" . $startLine), $alignmentV);
            ExcelUtilities::setBorder($sheet->getStyle(("M" . $startLine . ":O" . $startLine)), '000000');
            ExcelUtilities::setBackgroundColor($sheet->getCell("M" . $startLine), 'ffffff');

            $sheet->mergeCells("P" . $startLine . ":Q" . $startLine);
            $content = '';
            $sheet->setCellValue('P' . $startLine, $content);
            ExcelUtilities::setCellAlignment($sheet->getCell("P" . $startLine), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell("P" . $startLine), $alignmentV);
            ExcelUtilities::setBorder($sheet->getStyle(("P" . $startLine . ":Q" . $startLine)), '000000');
            ExcelUtilities::setBackgroundColor($sheet->getCell("P" . $startLine), 'ffffff');

            $startLine += 1;
            if (array_key_exists($count + 1, $result)) {
                if ($result[$count + 1]['productexternalid'] != $r['productexternalid']) {
                    $sheet->mergeCells("A" . $startLine . ":Q" . $startLine);
                    $content = '';
                    $sheet->setCellValue('A' . $startLine, $content);
                    ExcelUtilities::setCellAlignment($sheet->getCell("A" . $startLine), $alignmentH);
                    ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A" . $startLine), $alignmentV);
                    ExcelUtilities::setBorder($sheet->getStyle(("A" . $startLine . ":Q" . $startLine)), '000000');
                    ExcelUtilities::setBackgroundColor($sheet->getCell("A" . $startLine), $colorOne);
                    $startLine += 1;
                } elseif ($result[$count + 1]['productexternalid'] == $r['productexternalid']
                    && $result[$count + 1]['restaurantcode'] != $r['restaurantcode']) {
                    $sheet->mergeCells("A" . $startLine . ":Q" . $startLine);
                    $content = '';
                    $sheet->setCellValue('A' . $startLine, $content);
                    ExcelUtilities::setCellAlignment($sheet->getCell("A" . $startLine), $alignmentH);
                    ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A" . $startLine), $alignmentV);
                    ExcelUtilities::setBorder($sheet->getStyle(("A" . $startLine . ":Q" . $startLine)), '000000');
                    ExcelUtilities::setBackgroundColor($sheet->getCell("A" . $startLine), $colorOne);
                    $startLine += 1;
                }
            }
            $count++;
        }

        $filename = $version . "_Items_inventaire_dupliques.xls";

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

    private function getResult()
    {
        $conn = $this->em->getConnection();


        $sql = "select r.id as restaurant_id, r.code as restaurantCode, r.name as restaurantName, p.id as productId,p.name
              as productName, pp.external_id as productExternalId,pp.status as productStatus, psup.active as productSupStatus,  
              CASE
                WHEN r.id in (select psr.restaurant_id from product_supervision_restaurant psr where psr.product_supervision_id=psup.id ) THEN true
                ELSE false
               END as eligible_par_restaurant
                 from product p join product_purchased pp on p.id=pp.id join product_supervision psup on psup.id=p.supervision_product_id join restaurant r on r.id=p.origin_restaurant_id 
                where  pp.external_id in(select pp.external_id from product p2 inner join product_purchased pp on pp.id=p2.id  where
                 p2.origin_restaurant_id=p.origin_restaurant_id group by p2.origin_restaurant_id,pp.external_id having count(pp.external_id)>1 
                order by p2.origin_restaurant_id) order by p.origin_restaurant_id, pp.external_id 
                       ";
        $stm = $conn->prepare($sql);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }


    /**
     * Générer une fichier Excel contient tous les items de vente dupliqués
     * @param $version
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \PHPExcel_Exception
     */
    public function generateDuplicateProductSoldExcelFile($version)
    {

        $colorOne = "e0e0eb";
        $colorThree = "f7f7f7";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle('Items de vente ', 0, 30);


        $sheet->mergeCells("A1:C1");
        $content = 'Nom de restaurant';
        $sheet->setCellValue('A1', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("A1"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A1"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 1), 28, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A1"), $colorOne);
        ExcelUtilities::setBorder($sheet->getStyle("A1:C1"), '000000');

        $sheet->mergeCells("D1:F1");
        $content = "Nom de l'item de vente";
        $sheet->setCellValue('D1', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("D1"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("D1"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 1), 28, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D1"), $colorOne);
        ExcelUtilities::setBorder($sheet->getStyle("D1:F1"), '000000');

        $sheet->mergeCells("G1:K1");
        $content = "Recette de l'item de vente";
        $sheet->setCellValue('G1', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("G1"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("G1"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 1), 28, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G1"), $colorOne);
        ExcelUtilities::setBorder($sheet->getStyle("G1:K1"), '000000');

        $sheet->mergeCells("L1:N1");
        $content = 'Statut au niveau restaurant';
        $sheet->setCellValue('L1', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("L1"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("L1"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 1), 28, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L1"), $colorOne);
        ExcelUtilities::setBorder($sheet->getStyle("L1:N1"), '000000');


        $sheet->mergeCells("O1:Q1");
        $content = 'Statut au niveau supervision';
        $sheet->setCellValue('O1', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("O1"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("O1"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 1), 28, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O1"), $colorOne);
        ExcelUtilities::setBorder($sheet->getStyle("O1:Q1"), '000000');


        $sheet->mergeCells("R1:T1");
        $content = 'éligible par le restaurant';
        $sheet->setCellValue('R1', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("R1"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("R1"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 1), 28, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R1"), $colorOne);
        ExcelUtilities::setBorder($sheet->getStyle("R1:T1"), '000000');

        $sheet->mergeCells("U1:V1");
        $content = 'Commentaire';
        $sheet->setCellValue('U1', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("U1"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("U1"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 1), 28, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("U1"), $colorOne);
        ExcelUtilities::setBorder($sheet->getStyle("U1:V1"), '000000');

        $result = $this->getProductSoldResult();
        $startLine = 2;
        $count = 0;
        foreach ($result as $r) {

            $sheet->mergeCells("A" . $startLine . ":C" . $startLine);
            $content = $r['restaurantname'] . "(" . $r['restaurantcode'] . ")".'( id= '.$r['restaurant_id'].' )';
            $sheet->setCellValue('A' . $startLine, $content);
            ExcelUtilities::setCellAlignment($sheet->getCell("A" . $startLine), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A" . $startLine), $alignmentV);
            ExcelUtilities::setBorder($sheet->getStyle(("A" . $startLine . ":C" . $startLine)), '000000');
            ExcelUtilities::setBackgroundColor($sheet->getCell("A" . $startLine), 'ffffff');


            $sheet->mergeCells("D" . $startLine . ":F" . $startLine);
            $content = $r['productname'] . "(" . $r['productcodeplu'] . ")".'( id= '.$r['productid'].' )';
            $sheet->setCellValue('D' . $startLine, $content);
            ExcelUtilities::setCellAlignment($sheet->getCell("D" . $startLine), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell("D" . $startLine), $alignmentV);
            ExcelUtilities::setBorder($sheet->getStyle(("D" . $startLine . ":F" . $startLine)), '000000');
            ExcelUtilities::setBackgroundColor($sheet->getCell("D" . $startLine), 'ffffff');
            $recipeLine = explode(',', $r['productrecipe']);
            if ($recipeLine) {
                $substartLine = 0;
                foreach ($recipeLine as $recipe) {
                    $line = $startLine + $substartLine;
                    if ($substartLine > 0) {
                        $sheet->mergeCells("A" . $line . ":F" . $line);
                        $content = '';
                        $sheet->setCellValue('A' . $line, $content);
                        ExcelUtilities::setCellAlignment($sheet->getCell("A" . $line), $alignmentH);
                        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A" . $line), $alignmentV);
                        ExcelUtilities::setBackgroundColor($sheet->getCell("A" . $line), 'ffffff');
                    }

                    $sheet->mergeCells("G" . $line . ":K" . $line);
                    $content = $recipe;
                    $sheet->setCellValue('G' . $line, $content);
                    ExcelUtilities::setCellAlignment($sheet->getCell("G" . $line), $alignmentH);
                    ExcelUtilities::setVerticalCellAlignment($sheet->getCell("G" . $line), $alignmentV);
                    ExcelUtilities::setBackgroundColor($sheet->getCell("G" . $line), 'ffffff');

                    if ($substartLine > 0) {
                        $sheet->mergeCells("L" . $line . ":V" . $line);
                        $content = '';
                        $sheet->setCellValue('L' . $line, $content);
                        ExcelUtilities::setCellAlignment($sheet->getCell("L" . $line), $alignmentH);
                        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("L" . $line), $alignmentV);
                        ExcelUtilities::setBackgroundColor($sheet->getCell("L" . $line), 'ffffff');
                    }


                    $substartLine++;
                }
            }


            $sheet->mergeCells("L" . $startLine . ":N" . $startLine);
            $content = $r['productstatus'] ? 'actif' : 'inactif';
            $sheet->setCellValue('L' . $startLine, $content);
            ExcelUtilities::setCellAlignment($sheet->getCell("L" . $startLine), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell("L" . $startLine), $alignmentV);
            ExcelUtilities::setBorder($sheet->getStyle(("L" . $startLine . ":N" . $startLine)), '000000');
            ExcelUtilities::setBackgroundColor($sheet->getCell("L" . $startLine), 'ffffff');


            $sheet->mergeCells("O" . $startLine . ":Q" . $startLine);
            $content = $r['productsupstatus'] ? 'actif' : 'inactif';
            $sheet->setCellValue('O' . $startLine, $content);
            ExcelUtilities::setCellAlignment($sheet->getCell("O" . $startLine), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell("O" . $startLine), $alignmentV);
            ExcelUtilities::setBorder($sheet->getStyle(("O" . $startLine . ":Q" . $startLine)), '000000');
            ExcelUtilities::setBackgroundColor($sheet->getCell("O" . $startLine), 'ffffff');

            $sheet->mergeCells("R" . $startLine . ":T" . $startLine);
            $content = $r['eligible_par_restaurant'] ? 'Oui' : 'Non';
            $sheet->setCellValue('R' . $startLine, $content);
            ExcelUtilities::setCellAlignment($sheet->getCell("R" . $startLine), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell("R" . $startLine), $alignmentV);
            ExcelUtilities::setBorder($sheet->getStyle(("R" . $startLine . ":T" . $startLine)), '000000');
            ExcelUtilities::setBackgroundColor($sheet->getCell("R" . $startLine), 'ffffff');

            $sheet->mergeCells("U" . $startLine . ":V" . $startLine);
            $content = '';
            $sheet->setCellValue('U' . $startLine, $content);
            ExcelUtilities::setCellAlignment($sheet->getCell("U" . $startLine), $alignmentH);
            ExcelUtilities::setVerticalCellAlignment($sheet->getCell("U" . $startLine), $alignmentV);
            ExcelUtilities::setBorder($sheet->getStyle(("U" . $startLine . ":V" . $startLine)), '000000');
            ExcelUtilities::setBackgroundColor($sheet->getCell("U" . $startLine), 'ffffff');


            $startLine += $substartLine;
            if (array_key_exists($count + 1, $result)) {
                if ($result[$count + 1]['productcodeplu'] != $r['productcodeplu']) {
                    $sheet->mergeCells("A" . $startLine . ":V" . $startLine);
                    $content = '';
                    $sheet->setCellValue('A' . $startLine, $content);
                    ExcelUtilities::setCellAlignment($sheet->getCell("A" . $startLine), $alignmentH);
                    ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A" . $startLine), $alignmentV);
                    ExcelUtilities::setBorder($sheet->getStyle(("A" . $startLine . ":V" . $startLine)), '000000');
                    ExcelUtilities::setBackgroundColor($sheet->getCell("A" . $startLine), $colorOne);
                    $startLine += 1;
                } else {
                    $sheet->mergeCells("A" . $startLine . ":V" . $startLine);
                    $content = '';
                    $sheet->setCellValue('A' . $startLine, $content);
                    ExcelUtilities::setCellAlignment($sheet->getCell("A" . $startLine), $alignmentH);
                    ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A" . $startLine), $alignmentV);
                    ExcelUtilities::setBorder($sheet->getStyle(("A" . $startLine . ":V" . $startLine)), '000000');
                    ExcelUtilities::setBackgroundColor($sheet->getCell("A" . $startLine), 'ffffff');
                    $startLine += 1;
                }
            }
            $count++;
        }

        $filename = $version . "_Items_vente_duplique.xls";

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

    private function getProductSoldResult()
    {
        $conn = $this->em->getConnection();
        $sql = "select r.id as restaurant_id, r.code as restaurantCode, r.name as restaurantName, p.id as productId,p.name as productName, ps.code_plu as productCodePlu,p.active as productStatus, psup.active as productSupStatus,  
                     CASE
                    WHEN r.id in (select psr.restaurant_id from product_supervision_restaurant psr where psr.product_supervision_id=psup.id )
                     THEN true
                    ELSE false
                     END as eligible_par_restaurant,
                    CASE
                       WHEN ps.type ='transformed_product' THEN (select string_agg(  CONCAT(prod.name,'( ',product_purchased_recipe.external_id,' )') ,', ') as recipe_productsold from recipe rec join solding_canal solcan on rec.solding_canal_id=solcan.id  join recipe_line recl on rec.id=recl.recipe_id join product_purchased product_purchased_recipe on product_purchased_recipe.id=recl.product_purchased_id join product  prod on prod.id =recl.product_purchased_id where rec.product_sold_id=ps.id and solcan.label='allcanals')
                       WHEN ps.type ='non_transformed_product' THEN (select  CONCAT(product_recipe.name,'( ',product_purchased_recipe.external_id,' )') as recipe_productsold from product_sold product_sold_recipe join product_purchased product_purchased_recipe on product_sold_recipe.product_purchased_id=product_purchased_recipe.id join product product_recipe on product_recipe.id=product_purchased_recipe.id where product_sold_recipe.id=ps.id)
                     ELSE ''
                     END as productrecipe
                 from product p join product_sold ps on
                p.id=ps.id join product_supervision psup on psup.id=p.supervision_product_id join restaurant r on r.id=p.origin_restaurant_id where  ps.code_plu in(select ps.code_plu from product p2 inner join product_sold ps on ps.id=p2.id  where p2.origin_restaurant_id=p.origin_restaurant_id group by p2.origin_restaurant_id,ps.code_plu having count(ps.code_plu)>1  order by p2.origin_restaurant_id) order by p.origin_restaurant_id, ps.code_plu 
                           ";
        $stm = $conn->prepare($sql);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }


}