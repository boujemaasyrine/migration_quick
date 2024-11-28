<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 05/04/2016
 * Time: 16:21
 */

namespace AppBundle\Report\Service;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\FinancialRevenue;
use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\MargeFoodCost;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Transfer;
use AppBundle\Merchandise\Service\ProductService;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\Report\Entity\MargeFoodCostLine;
use AppBundle\Report\Entity\MargeFoodCostRapport;
use AppBundle\ToolBox\Service\CommandLauncher;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class ReportFoodCostService
{
    private $em;
    private $sqlQueriesDir;
    private $translator;
    private $phpExcel;
    private $restaurantService;

    /**
     * @var ProductService
     */
    private $productService;

    public function __construct(
        EntityManager $em,
        $sqlQueriesDir,
        Translator $translator,
        ProductService $productService,
        Factory $factory,
        RestaurantService $restaurantService
    ) {
        $this->em = $em;
        $this->sqlQueriesDir = $sqlQueriesDir;
        $this->translator = $translator;
        $this->productService = $productService;
        $this->phpExcel = $factory;
        $this->restaurantService = $restaurantService;
    }

    public function getMarginFoodCostResult($filter)
    {

        $filter1 = [
            'startDate' => date_create_from_format('d/m/Y', $filter['beginDate']),
            'endDate' => date_create_from_format('d/m/Y', $filter['endDate']),
            'selection' => 'all_items',
            'code' => null,
            'name' => null,
            'category' => [],
        ];


        $revenuePrice = $this->getRevenuePriceSold($filter);


        $soldLoss = $this->em->getRepository('Merchandise:LossLine')->getFiltredLossLineSold($filter, true, true);
        $detailsTicket = $this->em->getRepository('Financial:FinancialRevenue')->getFinancialRevenueBetweenDates(
            $filter1['startDate'],
            $filter1['endDate'],
            true
        );

        $result = [
            'caNetHt' => $detailsTicket['0']['caNetHT'],
            'caNetTtc' => $detailsTicket['0']['caNetTTC'],
            'caBrutTtc' => $detailsTicket['0']['caBrutTTC'],
            'caVoucherMeal' => $detailsTicket['0']['br'],
            'caDiscount' => $detailsTicket['0']['discount'],
            'inventoryLossVal' => $this->em->getRepository('Merchandise:LossLine')->getFiltredLossLine($filter, true, true),
            'soldLossVal' => $soldLoss['lossvalorization'],
            'revenuePrice' => $revenuePrice['totalrevenueprice'],
            'in' => $this->getInValorization($filter)['totalin'],
            'out' => $this->getOutValorization($filter)['totalout'],
            //'portionControl' => $portion_control['data']['0']['portion']
        ];

        $dateInitialStock = Utilities::getDateFromDate($filter1['startDate'], -1);

        $activeProductsAtStartDate = $this->em->getRepository(
            'Merchandise:ProductPurchased'
        )->getActivatedProductsInDay($dateInitialStock, true);
        $result['initial'] = 0;
        foreach ($activeProductsAtStartDate as $id) {
            $results = $this->productService->getStockForProductsAtDate($dateInitialStock, [$id]);
            foreach ($results as $line) {
                $initialQty = ($line['initial_stock'] < 0) ? 0 : $line['initial_stock'];

                $valorization = $line['initial_inventory_qty'] ? $initialQty * ($line['initial_buying_cost'] / $line['initial_inventory_qty']) : 0;
                $result['initial'] += $valorization;
            }
        }

        $activeProductsAtEndDate = $this->em->getRepository('Merchandise:ProductPurchased')->getActivatedProductsInDay(
            $filter1['endDate'],
            true
        );
        $result['final'] = 0;
        foreach ($activeProductsAtEndDate as $id) {
            $resultsEnd = $this->productService->getStockForProductsAtDate($filter1['endDate'], [$id]);
            foreach ($resultsEnd as $line) {
                $finalQty = ($line['initial_stock'] < 0) ? 0 : $line['initial_stock'];
                $valorization = $line['initial_inventory_qty'] ? $finalQty * ($line['initial_buying_cost'] / $line['initial_inventory_qty']) : 0;
                $result['final'] += $valorization;
            }
        }

        $result['voucherMeal'] = ($result['caBrutTtc']) ?
            ($result['revenuePrice'] / $result['caBrutTtc'] * $result['caVoucherMeal']) : 0;
        $result['discount'] = ($result['caBrutTtc']) ?
            (($result['revenuePrice'] / $result['caBrutTtc'] * $result['caDiscount'])) : 0;
        $result['real_fc'] = $result['initial'] + $result['in'] - $result['out'] - $result['final'];
        $result['theorical_fc'] = $result['revenuePrice'] + $result['inventoryLossVal'] + $result['soldLossVal'];
        $result['total_loss'] = $result['real_fc'] - $result['revenuePrice'];
        $result['unknown_loss'] = $result['total_loss'] - $result['inventoryLossVal'] - $result['soldLossVal'];
        $result['fcRealNet'] = $result['real_fc'] - $result['voucherMeal'] - $result['discount'];
        $result['realMargin'] = $result['caNetHt'] - $result['fcRealNet'];
        $result['portionControl'] = $result['revenuePrice'] + $result['inventoryLossVal'] + $result['soldLossVal'] - $result['real_fc'];

        return $result;
    }


    public function serializeMarginFoodCostReportResult($result)
    {
        $serializedResult = [];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.margin.mix_ca_ttc'),
            number_format($result['revenuePrice'], 2, '.', ''),
            $this->getPercentage($result['revenuePrice'], $result['caBrutTtc']),
            '',
            $this->translator->trans('report.ca.ca_net_ht'),
            number_format($result['caNetHt'], 2, '.', ''),
            '100',
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.margin.ideal_ht'),
            number_format($result['revenuePrice'], 2, '.', ''),
            $this->getPercentage($result['revenuePrice'], $result['caNetHt']),
            '',
            $this->translator->trans('report.food_cost.synthetic.initial_stock'),
            number_format($result['initial'], 2, '.', ''),
            $this->getPercentage($result['initial'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.synthetic.known_loss'),
            number_format(($result['inventoryLossVal'] + $result['soldLossVal']), 2, '.', ''),
            $this->getPercentage(($result['inventoryLossVal'] + $result['soldLossVal']), $result['caNetHt']),
        ];
        $serializedResult[] = [
            $this->translator->trans('article'),
            number_format($result['inventoryLossVal'], 2, '.', ''),
            $this->getPercentage($result['inventoryLossVal'], $result['caNetHt']),
            '',
            $this->translator->trans('keyword.in'),
            number_format($result['in'], 2, '.', ''),
            $this->getPercentage($result['in'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            $this->translator->trans('finalProduct'),
            number_format($result['soldLossVal'], 2, '.', ''),
            $this->getPercentage($result['soldLossVal'], $result['caNetHt']),
            '',
            $this->translator->trans('keyword.out'),
            number_format($result['out'], 2, '.', ''),
            $this->getPercentage($result['out'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.margin.total_loss'),
            number_format($result['total_loss'], 2, '.', ''),
            $this->getPercentage($result['total_loss'], $result['caNetHt']),
            '',
            $this->translator->trans('report.food_cost.synthetic.final_stock'),
            number_format($result['final'], 2, '.', ''),
            $this->getPercentage($result['final'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.margin.theorical_foodcost'),
            number_format($result['theorical_fc'], 2, '.', ''),
            $this->getPercentage($result['theorical_fc'], $result['caNetHt']),
            '',
            $this->translator->trans('report.food_cost.margin.real_foodcost'),
            number_format($result['real_fc'], 2, '.', ''),
            $this->getPercentage($result['real_fc'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.synthetic.unknown_loss'),
            number_format($result['unknown_loss'], 2, '.', ''),
            $this->getPercentage($result['unknown_loss'], $result['caNetHt']),
            '',
            $this->translator->trans('report.food_cost.margin.voucher_pub'),
            number_format($result['discount'], 2, '.', ''),
            $this->getPercentage($result['discount'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            '',
            '',
            '',
            '',
            $this->translator->trans('report.food_cost.margin.voucher_meal_foodcost'),
            number_format($result['voucherMeal'], 2, '.', ''),
            $this->getPercentage($result['voucherMeal'], $result['caNetHt']),
        ];

        $serializedResult[] = [
            $this->translator->trans('keyword.portion_control'),
            number_format($result['portionControl'], 2, '.', ''),
            $this->getPercentage($result['portionControl'], $result['caNetHt']),
            '',
            $this->translator->trans('report.food_cost.margin.net_real_foodcost'),
            number_format($result['fcRealNet'], 2, '.', ''),
            $this->getPercentage($result['fcRealNet'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            '',
            '',
            '',
            '',
            $this->translator->trans('report.food_cost.margin.net_real_margin'),
            number_format($result['realMargin'], 2, '.', ''),
            $this->getPercentage($result['realMargin'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            '',
            '',
            '',
            '',
        ];
        $serializedResult[] = [
            '',
            '',
            '',
            '',
        ];
        $serializedResult[] = [
            '',
            '',
            '',
            '',
        ];
        $serializedResult[] = [
            '',
            $this->translator->trans('keyword.theorical'),
            '',
            '',
            '',
            $this->translator->trans('report.food_cost.margin.period'),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.margin.margin_pf'),
            '',
            number_format(($result['caNetHt'] - $result['revenuePrice']), 2, '.', ''),
            '',
            $this->translator->trans('report.food_cost.synthetic.initial_stock'),
            '',
            number_format($result['initial'], 2, '.', ''),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.margin.voucher_meal'),
            '',
            number_format($result['voucherMeal'], 2, '.', ''),
            '',
            $this->translator->trans('keyword.in'),
            '',
            number_format($result['in'], 2, '.', ''),
        ];
        $serializedResult[] = [
            $this->translator->trans('keyword.loss'),
            $this->getPercentage(($result['inventoryLossVal'] + $result['soldLossVal']), $result['caNetHt']).' %',
            number_format(($result['inventoryLossVal'] + $result['soldLossVal']), 2, '.', ''),
            '',
            $this->translator->trans('keyword.out'),
            '',
            number_format($result['out'], 2, '.', ''),
        ];
        $serializedResult[] = [
            $this->translator->trans('label.inventory_item'),
            $this->getPercentage($result['inventoryLossVal'], $result['caNetHt']).' %',
            number_format($result['inventoryLossVal'], 2, '.', ''),
            '',
            $this->translator->trans('report.food_cost.synthetic.final_stock'),
            '',
            number_format($result['final'], 2, '.', ''),
        ];
        $serializedResult[] = [
            $this->translator->trans('label.sold_item'),
            $this->getPercentage($result['soldLossVal'], $result['caNetHt']).' %',
            number_format($result['soldLossVal'], 2, '.', ''),
            '',
            $this->translator->trans('keyword.consumption'),
            '',
            number_format($result['real_fc'], 2, '.', ''),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.ca.ca_net_ht'),
            '',
            number_format($result['caNetHt'], 2, '.', ''),
            '',
            $this->translator->trans('report.ca.ca_net_ht'),
            '',
            number_format($result['caNetHt'], 2, '.', ''),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.margin.theorical_margin'),
            '',
            number_format(($result['caNetHt'] - $result['theorical_fc']), 2, '.', ''),
            '',
            $this->translator->trans('report.food_cost.synthetic.real_margin'),
            '',
            number_format(($result['caNetHt'] - $result['real_fc']), 2, '.', ''),
        ];
        $serializedResult[] = [
            '',
            '',
            $this->getPercentage(($result['caNetHt'] - $result['theorical_fc']), $result['caNetHt']).' %',
            '',
            '',
            '',
            $this->getPercentage(($result['caNetHt'] - $result['real_fc']), $result['caNetHt']).' %',
        ];


        return $serializedResult;
    }

    public function getPercentage($value, $total)
    {
        return
            ($total != 0) ? number_format(($value / $total * 100), 2, '.', '') : 0;
    }

    public function getRevenuePriceSold($filter)
    {
        $sqlQueryFile = $this->sqlQueriesDir."/revenue_price.sql";

        if (!file_exists($sqlQueryFile)) {
            throw new \Exception("FILE REVENUE PRICE DOESN'T EXSIT");
        }

        $sql = file_get_contents($sqlQueryFile);
        $D1 = $filter['beginDate'];
        $D2 = $filter['endDate'];
        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $restaurantId=$filter["currentRestaurantId"];
        $stm->bindValue("origin_restaurant_id", $restaurantId);

        $stm->execute();
        $data = $stm->fetch();

        return $data;
    }

    public function getInValorization($filter)
    {
        $sqlQueryFile = $this->sqlQueriesDir."/in_valorization.sql";

        if (!file_exists($sqlQueryFile)) {
            throw new \Exception("FILE IN VALORIZATION DOESN'T EXSIT");
        }

        $sql = file_get_contents($sqlQueryFile);
        $D1 = $filter['beginDate']." 00:00:00";
        $D2 = $filter['endDate']." 23:59:59";
        $restaurantId=$filter["currentRestaurantId"];
        $stm = $this->em->getConnection()->prepare($sql);
        $transferIn = Transfer::TRANSFER_IN;
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        //$stm->bindParam('transferIn', $transferIn);
        $stm->bindValue("origin_restaurant_id", $restaurantId);
        $stm->execute();
        $data = $stm->fetch();

        return $data;
    }

    public function getOutValorization($filter)
    {
        $sqlQueryFile = $this->sqlQueriesDir."/out_valorization.sql";

        if (!file_exists($sqlQueryFile)) {
            throw new \Exception("FILE OUT VALORIZATION DOESN'T EXSIT");
        }

        $sql = file_get_contents($sqlQueryFile);
        $D1 = $filter['beginDate']." 00:00:00";
        $D2 = $filter['endDate']." 23:59:59";
        $restaurantId=$filter['currentRestaurantId'];
        $active = ProductPurchased::ACTIVE;
        $toInactive = ProductPurchased::TO_INACTIVE;
        $stm = $this->em->getConnection()->prepare($sql);
        $transferOut = Transfer::TRANSFER_OUT;
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('transfertOut', $transferOut);
        $stm->bindValue("origin_restaurant_id", $restaurantId);
        $stm->execute();
        $data = $stm->fetch();

        return $data;
    }

    public function generateExcelFile($filter, $result, $logoPath)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $colorOne = "ECECEC";
        $colorTwo = "ca9e67";
        $colorThree = "c8102e";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('report.food_cost.margin.title'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('report.food_cost.margin.title');
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
        ExcelUtilities::setBackgroundColor($sheet->getCell("C10"), $colorOne);
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

        //CONTENT
        //Start first left part
        //Header
        $sheet->mergeCells('A13:E13');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A13"), $colorTwo);
        $sheet->setCellValue('A13', $this->translator->trans('keyword.theorical'));
        ExcelUtilities::setCellAlignment($sheet->getCell("A13"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A13"), $alignmentV);
        $sheet->mergeCells('F13:G13');
        ExcelUtilities::setBackgroundColor($sheet->getCell("F13"), $colorTwo);
        $sheet->setCellValue('F13', $this->translator->trans('label.value'));
        ExcelUtilities::setCellAlignment($sheet->getCell("F13"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("F13"), $alignmentV);
        $sheet->mergeCells('H13:I13');
        ExcelUtilities::setBackgroundColor($sheet->getCell("H13"), $colorTwo);
        $sheet->setCellValue('H13', '%');
        ExcelUtilities::setCellAlignment($sheet->getCell("H13"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("H13"), $alignmentV);
        //First content
        $sheet->mergeCells('A14:C14');
        $sheet->setCellValue('A14', $this->translator->trans('report.food_cost.margin.mix_ca_ttc'));
        $sheet->mergeCells('F14:G14');
        $sheet->setCellValue('F14', round($result['revenuePrice'], 2));
        $sheet->mergeCells('H14:I14');
        if ($result['caBrutTtc'] != 0) {
            $sheet->setCellValue('H14', round(($result['revenuePrice'] / $result['caBrutTtc'] * 100), 2));
        }
        $sheet->mergeCells('A15:C15');
        $sheet->setCellValue('A15', $this->translator->trans('report.food_cost.margin.ideal_ht'));
        $sheet->mergeCells('F15:G15');
        $sheet->setCellValue('F15', round($result['revenuePrice'], 2));
        $sheet->mergeCells('H15:I15');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('H13', round(($result['revenuePrice'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('A16:I16');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A16"), $colorThree);
        //SECOND CONTENT
        $sheet->mergeCells('A17:B17');
        $sheet->setCellValue('A17', $this->translator->trans('report.food_cost.synthetic.known_loss'));
        $sheet->mergeCells('F17:G17');
        $sheet->setCellValue('F17', round($result['inventoryLossVal'] + $result['soldLossVal'], 2));
        $sheet->mergeCells('H17:I17');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('H17', 0);
        } else {
            $sheet->setCellValue(
                'H17',
                round((($result['inventoryLossVal'] + $result['soldLossVal']) / $result['caNetHt'] * 100), 2)
            );
        }
        $sheet->mergeCells('C18:D18');
        $sheet->setCellValue('C18', $this->translator->trans('article'));
        $sheet->mergeCells('F18:G18');
        $sheet->setCellValue('F18', round($result['inventoryLossVal'], 2));
        $sheet->mergeCells('H18:I18');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('H18', round(($result['inventoryLossVal'] / $result['caNetHt'] * 100), 2));
        }
        $sheet->mergeCells('C19:D19');
        $sheet->setCellValue('C19', $this->translator->trans('finalProduct'));
        $sheet->mergeCells('F19:G19');
        $sheet->setCellValue('F19', round($result['soldLossVal'], 2));
        $sheet->mergeCells('H19:I19');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('H19', round(($result['soldLossVal'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('A20:I20');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A18"), $colorThree);
        //Third content
        $sheet->mergeCells('A21:B21');
        $sheet->setCellValue('A21', $this->translator->trans('report.food_cost.margin.total_loss'));
        $sheet->mergeCells('F21:G21');
        $sheet->setCellValue('F21', abs(round($result['total_loss'], 2)));
        $sheet->mergeCells('H21:I21');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('H21', 0);
        } else {
            $sheet->setCellValue('H21', abs(round(($result['total_loss'] / $result['caNetHt'] * 100), 2)));
        }
        //SEPARATION
        $sheet->mergeCells('A22:I22');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A22"), $colorThree);
        //Fourth content
        $sheet->mergeCells('A23:B23');
        $sheet->setCellValue('A23', $this->translator->trans('report.food_cost.margin.theorical_foodcost'));
        $sheet->mergeCells('F23:G23');
        $sheet->setCellValue('F23', round($result['theorical_fc'], 2));
        $sheet->mergeCells('H23:I23');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('H23', 0);
        } else {
            $sheet->setCellValue('H23', round(($result['theorical_fc'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('A24:I24');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A24"), $colorThree);
        //FIFTH content
        $sheet->mergeCells('A25:B25');
        $sheet->setCellValue('A25', $this->translator->trans('report.food_cost.synthetic.unknown_loss'));
        $sheet->mergeCells('F25:G25');
        $sheet->setCellValue('F25', abs(round($result['unknown_loss'], 2)));
        $sheet->mergeCells('H25:I25');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('H25', 0);
        } else {
            $sheet->setCellValue('H25', abs(round(($result['unknown_loss'] / $result['caNetHt'] * 100), 2)));
        }

        //SEPARATION
        $sheet->mergeCells('A27:I27');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A27"), $colorThree);
        //SIXTH content
        $sheet->mergeCells('A28:B28');
        $sheet->setCellValue('A28', $this->translator->trans('keyword.portion_control'));
        $sheet->mergeCells('F28:G28');
        $sheet->setCellValue('F28', round($result['portionControl'], 2));
        $sheet->mergeCells('H28:I28');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('H28', 0);
        } else {
            $sheet->setCellValue('H28', round(($result['portionControl'] / $result['caNetHt'] * 100), 2));
        }
        //end first left part

        //Start FIRST right part
        //Header
        $sheet->mergeCells('K13:O13');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K13"), $colorTwo);
        $sheet->setCellValue('K13', $this->translator->trans('keyword.real'));
        ExcelUtilities::setCellAlignment($sheet->getCell("K13"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("K13"), $alignmentV);
        $sheet->mergeCells('P13:Q13');
        ExcelUtilities::setBackgroundColor($sheet->getCell("P13"), $colorTwo);
        $sheet->setCellValue('P13', $this->translator->trans('label.value'));
        ExcelUtilities::setCellAlignment($sheet->getCell("P13"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("P13"), $alignmentV);
        $sheet->mergeCells('R13:S13');
        ExcelUtilities::setBackgroundColor($sheet->getCell("r13"), $colorTwo);
        $sheet->setCellValue('R13', '%');
        ExcelUtilities::setCellAlignment($sheet->getCell("R13"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("R13"), $alignmentV);
        //First content
        $sheet->mergeCells('K14:M14');
        $sheet->setCellValue('k14', $this->translator->trans('report.ca.ca_net_ht'));
        $sheet->mergeCells('P14:Q14');
        $sheet->setCellValue('P14', round($result['caNetHt'], 2));
        $sheet->mergeCells('R14:S14');
        $sheet->setCellValue('R14', '100');
        $sheet->mergeCells('K15:M15');
        $sheet->setCellValue('K15', $this->translator->trans('report.food_cost.synthetic.initial_stock'));
        $sheet->mergeCells('P15:Q15');
        $sheet->setCellValue('P15', round($result['initial'], 2));
        $sheet->mergeCells('R15:S15');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('R15', round(($result['initial'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('K16:S16');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K16"), $colorThree);
        //SECOND CONTENT
        $sheet->mergeCells('K18:M18');
        $sheet->setCellValue('k18', $this->translator->trans('keyword.in'));
        $sheet->mergeCells('P18:Q18');
        $sheet->setCellValue('P18', round($result['in'], 2));
        $sheet->mergeCells('R18:S18');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('R18', round(($result['in'] / $result['caNetHt'] * 100), 2));
        }
        $sheet->mergeCells('K19:M19');
        $sheet->setCellValue('K19', $this->translator->trans('keyword.out'));
        $sheet->mergeCells('P19:Q19');
        $sheet->setCellValue('P19', round($result['out'], 2));
        $sheet->mergeCells('R19:S19');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('R19', round(($result['out'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('K20:S20');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K20"), $colorThree);
        //Third content
        $sheet->mergeCells('K21:M21');
        $sheet->setCellValue('K21', $this->translator->trans('report.food_cost.synthetic.final_stock'));
        $sheet->mergeCells('P21:Q21');
        $sheet->setCellValue('P21', round($result['final'], 2));
        $sheet->mergeCells('R21:S21');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('R21', 0);
        } else {
            $sheet->setCellValue('R21', round(($result['final'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('K22:S22');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K22"), $colorThree);
        //Fourth content
        $sheet->mergeCells('K23:M23');
        $sheet->setCellValue('K23', $this->translator->trans('report.food_cost.margin.real_foodcost'));
        $sheet->mergeCells('P23:Q23');
        $sheet->setCellValue('P23', round($result['real_fc'], 2));
        $sheet->mergeCells('R23:S23');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('R23', 0);
        } else {
            $sheet->setCellValue('R23', round(($result['real_fc'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('K24:S24');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K24"), $colorThree);
        //FIFTH content
        $sheet->mergeCells('K25:M25');
        $sheet->setCellValue('K25', $this->translator->trans('report.food_cost.margin.voucher_pub'));
        $sheet->mergeCells('P25:Q25');
        $sheet->setCellValue('P25', round($result['discount'], 2));
        $sheet->mergeCells('R25:S25');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('R25', 0);
        } else {
            $sheet->setCellValue('R25', round(($result['discount'] / $result['caNetHt'] * 100), 2));
        }
        $sheet->mergeCells('K26:M26');
        $sheet->setCellValue('K26', $this->translator->trans('report.food_cost.margin.voucher_meal_foodcost'));
        $sheet->mergeCells('P26:Q26');
        $sheet->setCellValue('P26', round($result['voucherMeal'], 2));
        $sheet->mergeCells('R26:S26');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('R26', 0);
        } else {
            $sheet->setCellValue('R26', round(($result['voucherMeal'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('K27:S27');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K27"), $colorThree);
        //SIXTH content
        $sheet->mergeCells('K28:M28');
        $sheet->setCellValue('K28', $this->translator->trans('report.food_cost.margin.net_real_foodcost'));
        $sheet->mergeCells('P28:Q28');
        $sheet->setCellValue('P28', round($result['fcRealNet'], 2));
        $sheet->mergeCells('R28:S28');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('R28', 0);
        } else {
            $sheet->setCellValue('R28', round(($result['fcRealNet'] / $result['caNetHt'] * 100), 2));
        }
        $sheet->mergeCells('K29:M29');
        $sheet->setCellValue('K29', $this->translator->trans('report.food_cost.margin.net_real_margin'));
        $sheet->mergeCells('P29:Q29');
        $sheet->setCellValue('P29', round($result['realMargin'], 2));
        $sheet->mergeCells('R29:S29');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('R29', 0);
        } else {
            $sheet->setCellValue('R29', round(($result['realMargin'] / $result['caNetHt'] * 100), 2));
        }
        //end first left part

        //Start SECOND left part
        //Header
        $sheet->mergeCells('A32:I32');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A32"), $colorTwo);
        $sheet->setCellValue('A32', $this->translator->trans('keyword.theorical'));
        ExcelUtilities::setCellAlignment($sheet->getCell("A32"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A32"), $alignmentV);
        //First content
        $sheet->mergeCells('A33:C33');
        $sheet->setCellValue('A33', $this->translator->trans('report.food_cost.margin.margin_pf'));
        $sheet->mergeCells('H33:I33');
        $sheet->setCellValue('H33', round($result['caNetHt'] - $result['revenuePrice'], 2));
        //SEPARATION
        $sheet->mergeCells('A34:I34');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A34"), $colorThree);
        //SECOND CONTENT
        $sheet->mergeCells('A35:C35');
        $sheet->setCellValue('A35', $this->translator->trans('report.food_cost.margin.voucher_meal'));
        $sheet->mergeCells('H35:I35');
        $sheet->setCellValue('H35', round($result['caVoucherMeal'], 2));
        //SEPARATION
        $sheet->mergeCells('A36:I36');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A36"), $colorThree);
        //third CONTENT
        $sheet->mergeCells('A37:B37');
        $sheet->setCellValue('A37', $this->translator->trans('keyword.loss'));
        $sheet->mergeCells('F37:G37');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue(
                'F37',
                round((($result['inventoryLossVal'] + $result['soldLossVal']) / $result['caNetHt'] * 100), 2).' %HT'
            );
        }
        $sheet->mergeCells('H37:I37');
        $sheet->setCellValue('H37', round(($result['inventoryLossVal'] + $result['soldLossVal']), 2));
        $sheet->mergeCells('C38:D38');
        $sheet->setCellValue('C38', $this->translator->trans('label.inventory_item'));
        $sheet->mergeCells('F38:G38');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('F38', round(($result['inventoryLossVal'] / $result['caNetHt'] * 100), 2).' %HT');
        }
        $sheet->mergeCells('H38:I38');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('H38', round($result['inventoryLossVal'], 2));
        }
        $sheet->mergeCells('C39:D39');
        $sheet->setCellValue('C39', $this->translator->trans('label.sold_item'));
        $sheet->mergeCells('F39:G39');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('F39', round(($result['soldLossVal'] / $result['caNetHt'] * 100), 2).' %HT');
        }
        $sheet->mergeCells('H39:I39');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('H39', round($result['soldLossVal'], 2));
        }
        $sheet->mergeCells('A40:B40');
        $sheet->setCellValue('A40', $this->translator->trans('report.ca.ca_net_ht'));
        $sheet->mergeCells('H40:I40');
        $sheet->setCellValue('H40', round($result['caNetHt'], 2));
        $sheet->mergeCells('A41:B41');
        $sheet->setCellValue('A41', $this->translator->trans('report.food_cost.margin.theorical_margin'));
        $sheet->mergeCells('H41:I41');
        $sheet->setCellValue('H41', round(($result['caNetHt'] - $result['theorical_fc']), 2));
        $sheet->mergeCells('H42:I42');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('H41', 0);
        } else {
            $sheet->setCellValue(
                'H42',
                round((($result['caNetHt'] - $result['theorical_fc']) / $result['caNetHt'] * 100), 2)
            );
        }
        //end SECOND left part


        //Start SECOND right part
        //Header
        $sheet->mergeCells('K32:S32');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K32"), $colorTwo);
        $sheet->setCellValue('K32', $this->translator->trans('report.food_cost.margin.period'));
        ExcelUtilities::setCellAlignment($sheet->getCell("K32"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("K32"), $alignmentV);
        //First content
        $sheet->mergeCells('K33:L33');
        $sheet->setCellValue('K33', $this->translator->trans('report.food_cost.synthetic.initial_stock'));
        $sheet->mergeCells('R33:S33');
        $sheet->setCellValue('R33', round($result['initial'], 2));
        //SEPARATION
        $sheet->mergeCells('K34:S34');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K34"), $colorThree);
        //SECOND CONTENT
        $sheet->mergeCells('K35:L35');
        $sheet->setCellValue('K35', $this->translator->trans('keyword.in'));
        $sheet->mergeCells('R35:S35');
        $sheet->setCellValue('R35', round($result['in'], 2));
        //SEPARATION
        $sheet->mergeCells('K36:S36');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K36"), $colorThree);
        //third CONTENT
        $sheet->mergeCells('K37:L37');
        $sheet->setCellValue('K37', $this->translator->trans('keyword.out'));
        $sheet->mergeCells('R37:S37');
        $sheet->setCellValue('R37', round($result['out'], 2));
        $sheet->mergeCells('K38:L38');
        $sheet->setCellValue('K38', $this->translator->trans('report.food_cost.synthetic.final_stock'));
        $sheet->mergeCells('R38:S38');
        $sheet->setCellValue('R38', round($result['final'], 2));
        $sheet->mergeCells('K39:L39');
        $sheet->setCellValue('K39', $this->translator->trans('keyword.consumption'));
        $sheet->mergeCells('R39:S39');
        $sheet->setCellValue('R39', round($result['real_fc'], 2));
        $sheet->mergeCells('K40:L40');
        $sheet->setCellValue('K40', $this->translator->trans('report.ca.ca_net_ht'));
        $sheet->mergeCells('R40:S40');
        $sheet->setCellValue('R40', round($result['caNetHt'], 2));
        $sheet->mergeCells('K41:L41');
        $sheet->setCellValue('K41', $this->translator->trans('report.food_cost.synthetic.real_margin'));
        $sheet->mergeCells('R41:S41');
        $sheet->setCellValue('R41', round(($result['caNetHt'] - $result['real_fc']), 2));
        $sheet->mergeCells('R42:S42');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('R42', 0);
        } else {
            $sheet->setCellValue(
                'R42',
                round((($result['caNetHt'] - $result['real_fc']) / $result['caNetHt'] * 100), 2)
            );
        }
        //end second right part

        $filename = "Rapport_marge_foodcost_".date('dmY_His').".xls";
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


    public function getMargeFoodCost(
        $currentRestaurantId,
        \DateTime $startDate,
        \DateTime $endDate,
        ImportProgression $progression = null,
        $force = 0
    ) {
        //$tmpDate = $startDate->format('Y-m-d');
        //new belsem
        $firstDate = $startDate->format('Y-m-d');
        $lastDate=  $endDate->format('Y-m-d');
        echo'start date is '.$startDate->format('Y-m-d') . 'end date is '.$endDate->format('Y-m-d') ." \n";

        $countDays = intval($startDate->diff($endDate)->format('%a')) + 1;
        $dateStepPerc = 1 / (($countDays) ? $countDays : 1);
        echo 'date step % '.$dateStepPerc;

         $currentRestaurant = $this->em->getRepository(Restaurant::class)->find($currentRestaurantId);

        $today = new \DateTime();
        $param = $this->em->getRepository(Parameter::class)->findOneBy(
            array(
                'type' => 'marge_food_cost',
                'originRestaurant' => $currentRestaurant,
            )
        );
        try {
//            while (strtotime($tmpDate) <= strtotime($endDate->format("Y-m-d"))) {
//                $tmp = date_create_from_format('Y-m-d', $tmpDate);
//                echo "processing date :".$tmpDate." \n";

                //check if exists
                $existingMargeFoodCost = $this->em->getRepository(MargeFoodCostLine::class)->findBy(
                    array(
                        "date" => $startDate,
                        "endDate" =>$endDate,
                        "originRestaurant" => $currentRestaurant,
                    )
                );

                if (($force != 0 && $existingMargeFoodCost) || $firstDate === $today->format('Y-m-d')) {
                    foreach ($existingMargeFoodCost as $line) {
                        $this->em->remove($line);
                        $this->em->flush();
                        $existingMargeFoodCost = null;
                    }
                }

                if (!$existingMargeFoodCost) {
                    $filter['beginDate'] = $firstDate;
                    $filter['endDate'] = $lastDate;
                    $filter['lastDate'] = $lastDate;
                    $filter['currentRestaurantId'] = $currentRestaurantId;

                    $soldLoss = $this->em->getRepository(LossLine::class)->getFiltredLossLineSold(
                        array(
                            'beginDate' => $firstDate,
                            'endDate' => $lastDate,
                            'currentRestaurantId' => $currentRestaurantId,
                        ),
                        true,
                        true
                    );

                    $detailsTicket = $this->em->getRepository(FinancialRevenue::class)->getFinancialRevenueBetweenDates(
                        $firstDate,
                        $lastDate,
                        $currentRestaurant,
                        true
                    );
                    $revenuePrice = $this->getRevenuePriceSold(
                        array(
                            'beginDate' => $firstDate,
                            'endDate' => $lastDate,
                            'currentRestaurantId' => $currentRestaurantId,
                        )
                    );
                    //  $initialStock = $this->productService->getInitialStockValorizationAtDate(
                    //      $tmp,
                    //      $currentRestaurantId
                    // );

                    // new belsem
                    $initialStock = $this->productService->getInitialStockValorizationAtDate(
                        array(
                            'beginDate' =>$firstDate,
                            'lastDate' => $lastDate
                            //'lastDate' => $tmpDate
                        ),
                        $currentRestaurantId
                    );

                    //  $finalStock = $this->productService->getFinalStockValorizationAtDate($tmp, $currentRestaurantId);

                    $finalStock = $this->productService->getFinalStockValorizationAtDate(  array(
                        'beginDate' =>$firstDate,
                        //'beginDate' =>$tmpDate,
                        'lastDate' => $lastDate
                    ), $currentRestaurantId);





                    $invLoss = $this->em->getRepository(LossLine::class)->getFiltredLossLine(
                        array(
                            'beginDate' => $firstDate,
                            'endDate' => $lastDate,
                            'currentRestaurantId' => $currentRestaurantId,
                        ),
                        true,
                        true
                    );

//                                      $inValorization = $this->getInValorization(
//                        array(
//                            'beginDate' => $tmpDate,
//                            'endDate' => $tmpDate,
//                            'currentRestaurantId' => $currentRestaurantId,
//                        )
//                    )['totalin'];
                    $inValorization = $this->getInValorization(
                        array(
                            'beginDate' => $firstDate,
                            'endDate' => $lastDate,
                            'currentRestaurantId' => $currentRestaurantId,
                        )
                    )['totalin'];

//                    $outValorization = $this->getOutValorization(
//                        array(
//                            'beginDate' => $tmpDate,
//                            'endDate' => $tmpDate,
//                            'currentRestaurantId' => $currentRestaurantId,
//                        )
//                    )['totalout'];
                    $outValorization = $this->getOutValorization(
                        array(
                            'beginDate' => $firstDate,
                            'endDate' => $lastDate,
                            'currentRestaurantId' => $currentRestaurantId,
                        )
                    )['totalout'];
                    $data['caBrutTTC'] = ($detailsTicket['0']['caBrutTTC'] == null) ? 0 : $detailsTicket['0']['caBrutTTC'];
                    $data['caNetHT'] = ($detailsTicket['0']['caNetHT'] == null) ? 0 : $detailsTicket['0']['caNetHT'];
                    $data['caNetTTC'] = ($detailsTicket['0']['caNetTTC'] == null) ? 0 : $detailsTicket['0']['caNetTTC'];
                    $data['br'] = ($detailsTicket['0']['br'] == null) ? 0 : $detailsTicket['0']['br'];
                    $data['caDiscount'] = ($detailsTicket['0']['discount'] == null) ? 0 : $detailsTicket['0']['discount'];
                    $data['invLoss'] = ($invLoss == null) ? 0 : $invLoss;
                    $data['soldLoss'] = ($soldLoss['lossvalorization'] == null) ? 0 : $soldLoss['lossvalorization'];
                    $data['revenuePrice'] = ($revenuePrice['totalrevenueprice'] == null) ? 0 : $revenuePrice['totalrevenueprice'];
                    $data['inValorization'] = ($inValorization == null) ? 0 : $inValorization;
                    $data['outValorization'] = ($outValorization == null) ? 0 : $outValorization;
                    $data['initialStock'] = ($initialStock == null) ? 0 : $initialStock;
                    $data['finalStock'] = ($finalStock == null) ? 0 : $finalStock;
                    $margeFoodCost = new MargeFoodCostLine();
                    $margeFoodCost->setDate($startDate);
                    $margeFoodCost->setEndDate($endDate);
                    $margeFoodCost->setData($data);
                    $margeFoodCost->setOriginRestaurant($currentRestaurant);
                    $this->em->persist($margeFoodCost);
                    $this->em->flush();
                }
                if ($progression) {
                    $progression->incrementPercentProgression( 100);
                    $this->em->flush();
                }
                $now = new \DateTime();
                $param->setUpdatedAt($now);
//                $tmpDate = date("Y-m-d", strtotime("+1 day", strtotime($tmpDate)));
//            }
        } catch (\Exception $e) {
            $param->setValue(0);
            $this->em->persist($param);
            $this->em->flush($param);
        }
    }

    public function formatResultMarginFoodCost(MargeFoodCostRapport $rapportTmp)
    {
        $lines = $this->em->getRepository("Report:MargeFoodCostLine")->createQueryBuilder("f")
            ->Where("f.date = :startDate")
            ->andWhere("f.endDate = :endDate")
            ->andWhere("f.originRestaurant = :originRestaurant")
            ->setParameter("startDate", $rapportTmp->getStartDate())
            ->setParameter("endDate", $rapportTmp->getEndDate())
            ->setParameter("originRestaurant", $rapportTmp->getOriginRestaurant())
            ->orderBy("f.date", "ASC")
            ->getQuery()
            ->getResult();
        $results = [];


            if ($lines[0]->getData() != null) {
                $results[] =  json_decode($lines[0]->getData(), true);

            }


        $finalResult['caNetHt'] = 0;
        $finalResult['caNetTTC'] = 0;
        $finalResult['caBrutTtc'] = 0;
        $finalResult['caVoucherMeal'] = 0;
        $finalResult['caDiscount'] = 0;
        $finalResult['inventoryLossVal'] = 0;
        $finalResult['soldLossVal'] = 0;
        $finalResult['revenuePrice'] = 0;
        $finalResult['in'] = 0;
        $finalResult['out'] = 0;

        $finalResult['caNetHt'] = $results[0]['caNetHT'];
        $finalResult['caNetTTC'] = $results[0]['caNetTTC'];
        $finalResult['caBrutTtc'] = $results[0]['caBrutTTC'];
        $finalResult['caVoucherMeal'] = $results[0]['br'];
        $finalResult['caDiscount'] = $results[0]['caDiscount'];
        $finalResult['inventoryLossVal'] = $results[0]['invLoss'];
        $finalResult['soldLossVal'] = $results[0]['soldLoss'];
        $finalResult['revenuePrice'] = $results[0]['revenuePrice'];
        $finalResult['in'] = $results[0]['inValorization'];
        $finalResult['out'] = $results[0]['outValorization'];


        $finalResult['initial'] = $results[0]['initialStock'];
        $finalResult['final'] = $results[0]['finalStock'];
        $finalResult['voucherMeal'] = ($finalResult['caBrutTtc']) ?
            ($finalResult['revenuePrice'] / $finalResult['caBrutTtc'] * $finalResult['caVoucherMeal']) : 0;
        $finalResult['discount'] = ($finalResult['caBrutTtc']) ?
            (($finalResult['revenuePrice'] / $finalResult['caBrutTtc'] * $finalResult['caDiscount'])) : 0;
        $finalResult['real_fc'] = $finalResult['initial'] + $finalResult['in'] - $finalResult['out'] - $finalResult['final'];
        $finalResult['theorical_fc'] = $finalResult['revenuePrice'] + $finalResult['inventoryLossVal'] + $finalResult['soldLossVal'];
        $finalResult['total_loss'] = $finalResult['real_fc'] - $finalResult['revenuePrice'];
        $finalResult['unknown_loss'] = $finalResult['total_loss'] - $finalResult['inventoryLossVal'] - $finalResult['soldLossVal'];
        $finalResult['fcRealNet'] = $finalResult['real_fc'] - $finalResult['voucherMeal'] - $finalResult['discount'];
        $finalResult['realMargin'] = $finalResult['caNetHt'] - $finalResult['fcRealNet'];
        $finalResult['portionControl'] = $finalResult['revenuePrice'] + $finalResult['inventoryLossVal'] + $finalResult['soldLossVal'] - $finalResult['real_fc'];
        $data['result'] = $finalResult;

        return $data;
    }

    public function checkLocked($currentRestaurantId)
    {
        $currentRestaurant = $this->em->getRepository(Restaurant::class)->find($currentRestaurantId);
        if (!$currentRestaurant) {
            throw new \Exception("Restaurant not found");
        }
        $param = $this->em->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => 'marge_food_cost',
                'originRestaurant' => $currentRestaurant,
            )
        );
        $now = new \DateTime('now');
        if (!$param || $param == null || $param->getValue() == 0) {
            if (!$param) {
                $param = new Parameter();
                $param->setType('marge_food_cost');
                $param->setCreatedAt($now);
                $param->setUpdatedAt($now);
                $param->setOriginRestaurant($currentRestaurant);
                $this->em->persist($param);
                $this->em->flush($param);
            }
        } else {
            if ($param->getValue() == 1) {
                $diffInSeconds = $now->getTimestamp() - $param->getUpdatedAt()->getTimestamp();

                if ($diffInSeconds > 300) {
                    $param->setValue(0);
                    $this->em->flush($param);
                }
            }
        }

        return $param;
    }
}
