<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 29/05/2016
 * Time: 19:04
 */

namespace AppBundle\Supervision\Service\Reports;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\FinancialRevenue;
use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Transfer;
use AppBundle\Report\Entity\MargeFoodCostLine;
use AppBundle\Report\Entity\MargeFoodCostRapport;
use AppBundle\Supervision\Service\ProductService;
use AppBundle\Supervision\Utils\Utilities;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class MarginFoodCostService
{

    private $em;
    private $translator;
    /**
     * @var ProductService
     */
    private $productService;
    private $sqlQueriesDir;
    private $phpExcel;

    public function __construct(
        EntityManager $em,
        Translator $translator,
        ProductService $productService,
        $sqlQueryDir,
        Factory $factory
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->productService = $productService;
        $this->sqlQueriesDir = $sqlQueryDir;
        $this->phpExcel = $factory;
    }

    public function getMarginFoodCostResult($data)
    {

        $filter1 = [
            'startDate' => $data['beginDate'],
            'endDate' => $data['endDate'],
            'restaurant' => $data['restaurant']->getId(),
            'selection' => 'all_items',
            'code' => null,
            'name' => null,
            'category' => [],
        ];

        $filter = [
            'beginDate' => $data['beginDate']->format('Y-m-d'),
            'endDate' => $data['endDate']->format('Y-m-d'),
            'restaurants' => [$data['restaurant']],
        ];


        //        $portion_control = $this->em->getRepository('AppBundle:ProductPurchased')->getFiltredProductPurchased($filter1, true);
        //        $detailsTicket = $this->em->getRepository('AppBundle:Financial\Ticket')->getCaTicket($filter);
        $revenuePrice = $this->getRevenuePriceSold($data);
        $soldLoss = $this->em->getRepository(LossLine::class)->getSupervisionFiltredLossLineSold($filter, true);
        $inventoryLoss = $this->em->getRepository(LossLine::class)->getSupervisionFiltredLossLine($filter, true);

        $detailsTicket = $this->em->getRepository(FinancialRevenue::class)->getSupervisionFinancialRevenueBetweenDates(
            $filter1['startDate'],
            $filter1['endDate'],
            $filter['restaurants'],
            true
        );

        $result = [
            'caNetHt' => $detailsTicket['0']['caNetHT'],
            'caNetTtc' => $detailsTicket['0']['caNetTTC'],
            'caBrutTtc' => $detailsTicket['0']['caBrutTTC'],
            'caVoucherMeal' => $detailsTicket['0']['br'],
            'caDiscount' => $detailsTicket['0']['discount'],
            'inventoryLossVal' => $inventoryLoss,
            'soldLossVal' => $soldLoss['lossvalorization'],
            'revenuePrice' => $revenuePrice['totalrevenueprice'],
            'in' => $this->getInValorization($data)['totalin'],
            'out' => $this->getOutValorization($data)['totalout'],
            //'portionControl' => $portion_control['data']['0']['portion']
        ];

        $dateInitialStock = Utilities::getDateFromDate($filter1['startDate'], -1);

        $activeProductsAtStartDate = $this->em->getRepository(
            ProductPurchased::class
        )->getSupervisionActivatedProductsInDay($dateInitialStock, $data['restaurant'], true);
        $result['initial'] = 0;
        $results = $this->productService->getStockForProductsAtDate(
            $dateInitialStock,
            $activeProductsAtStartDate,
            $filter1['restaurant']
        );
        foreach ($results as $line) {
            $initialQty = $line['initial_stock'];
            $valorization = $line['initial_inventory_qty'] ? $initialQty * ($line['initial_buying_cost'] / $line['initial_inventory_qty']) : 0;
            $result['initial'] += $valorization;
        }

        $activeProductsAtEndDate = $this->em->getRepository(
            ProductPurchased::class
        )->getSupervisionActivatedProductsInDay($filter1['endDate'], $data['restaurant'], true);
        $result['final'] = 0;
        $resultsEnd = $this->productService->getStockForProductsAtDate(
            $filter1['endDate'],
            $activeProductsAtEndDate,
            $filter1['restaurant']
        );
        foreach ($resultsEnd as $line) {
            $finalQty = $line['initial_stock'];
            $valorization = $line['initial_inventory_qty'] ? $finalQty * ($line['initial_buying_cost'] / $line['initial_inventory_qty']) : 0;
            $result['final'] += $valorization;
        }

        $result['voucherMeal'] = ($result['caBrutTtc']) ?
            ($result['revenuePrice'] / $result['caBrutTtc'] * $result['caVoucherMeal'] / 100) : 0;
        $result['discount'] = ($result['caBrutTtc']) ?
            (($result['revenuePrice'] / $result['caBrutTtc'] * $result['caDiscount']) / 100) : 0;
        $result['real_fc'] = $result['initial'] + $result['in'] - $result['out'] - $result['final'];
        $result['theorical_fc'] = $result['revenuePrice'] + $result['inventoryLossVal'] + $result['soldLossVal'];
        $result['total_loss'] = $result['real_fc'] - $result['revenuePrice'];
        $result['unknown_loss'] = $result['total_loss'] - $result['inventoryLossVal'] - $result['soldLossVal'];
        $result['fcRealNet'] = $result['real_fc'] - $result['voucherMeal'] - $result['discount'];
        $result['realMargin'] = $result['caNetHt'] - $result['fcRealNet'];
        $result['portionControl'] = $result['revenuePrice'] + $result['inventoryLossVal'] + $result['soldLossVal'] - $result['real_fc'];

        return $result;
    }

    public function getRevenuePriceSold($data)
    {
        $sqlQueryFile = $this->sqlQueriesDir."/revenue_price_supervision.sql";

        if (!file_exists($sqlQueryFile)) {
            throw new \Exception("FILE REVENUE PRICE DOESN'T EXSIT");
        }

        $sql = file_get_contents($sqlQueryFile);
        $D1 = $data['beginDate']->format('Y-m-d');
        $D2 = $data['endDate']->format('Y-m-d');
        $restaurant = $data['restaurant']->getId();

        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('restaurant', $restaurant);

        $stm->execute();
        $data = $stm->fetch();

        return $data;
    }

    public function serializeMarginFoodCostReportResult($result)
    {
        $serializedResult = [];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.margin.mix_ca_ttc', [], 'supervision'),
            number_format($result['revenuePrice'], 2, '.', ''),
            $this->getPercentage($result['revenuePrice'], $result['caBrutTtc']),
            '',
            $this->translator->trans('report.ca.ca_net_ht', [], 'supervision'),
            number_format($result['caNetHt'], 2, '.', ''),
            '100',
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.margin.ideal_ht', [], 'supervision'),
            number_format($result['revenuePrice'], 2, '.', ''),
            $this->getPercentage($result['revenuePrice'], $result['caNetHt']),
            '',
            $this->translator->trans('report.food_cost.synthetic.initial_stock', [], 'supervision'),
            number_format($result['initial'], 2, '.', ''),
            $this->getPercentage($result['initial'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.synthetic.known_loss', [], 'supervision'),
            number_format(($result['inventoryLossVal'] + $result['soldLossVal']), 2, '.', ''),
            $this->getPercentage(($result['inventoryLossVal'] + $result['soldLossVal']), $result['caNetHt']),
        ];
        $serializedResult[] = [
            $this->translator->trans('article', [], 'supervision'),
            number_format($result['inventoryLossVal'], 2, '.', ''),
            $this->getPercentage($result['inventoryLossVal'], $result['caNetHt']),
            '',
            $this->translator->trans('keywords.in', [], 'supervision'),
            number_format($result['in'], 2, '.', ''),
            $this->getPercentage($result['in'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            $this->translator->trans('finalProduct', [], 'supervision'),
            number_format($result['soldLossVal'], 2, '.', ''),
            $this->getPercentage($result['soldLossVal'], $result['caNetHt']),
            '',
            $this->translator->trans('keywords.out', [], 'supervision'),
            number_format($result['out'], 2, '.', ''),
            $this->getPercentage($result['out'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.margin.total_loss', [], 'supervision'),
            number_format($result['total_loss'], 2, '.', ''),
            $this->getPercentage($result['total_loss'], $result['caNetHt']),
            '',
            $this->translator->trans('report.food_cost.synthetic.final_stock', [], 'supervision'),
            number_format($result['final'], 2, '.', ''),
            $this->getPercentage($result['final'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.margin.theorical_foodcost', [], 'supervision'),
            number_format($result['theorical_fc'], 2, '.', ''),
            $this->getPercentage($result['theorical_fc'], $result['caNetHt']),
            '',
            $this->translator->trans('report.food_cost.margin.real_foodcost', [], 'supervision'),
            number_format($result['real_fc'], 2, '.', ''),
            $this->getPercentage($result['real_fc'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.synthetic.unknown_loss', [], 'supervision'),
            number_format($result['unknown_loss'], 2, '.', ''),
            $this->getPercentage($result['unknown_loss'], $result['caNetHt']),
            '',
            $this->translator->trans('report.food_cost.margin.voucher_pub', [], 'supervision'),
            number_format($result['discount'], 2, '.', ''),
            $this->getPercentage($result['discount'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            '',
            '',
            '',
            '',
            $this->translator->trans('report.food_cost.margin.voucher_meal_foodcost', [], 'supervision'),
            number_format($result['voucherMeal'], 2, '.', ''),
            $this->getPercentage($result['voucherMeal'], $result['caNetHt']),
        ];

        $serializedResult[] = [
            $this->translator->trans('keywords.portion_control', [], 'supervision'),
            number_format($result['portionControl'], 2, '.', ''),
            $this->getPercentage($result['portionControl'], $result['caNetHt']),
            '',
            $this->translator->trans('report.food_cost.margin.net_real_foodcost', [], 'supervision'),
            number_format($result['fcRealNet'], 2, '.', ''),
            $this->getPercentage($result['fcRealNet'], $result['caNetHt']),
        ];
        $serializedResult[] = [
            '',
            '',
            '',
            '',
            $this->translator->trans('report.food_cost.margin.net_real_margin', [], 'supervision'),
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
            $this->translator->trans('keywords.theorical', [], 'supervision'),
            '',
            '',
            '',
            $this->translator->trans('report.food_cost.margin.period', [], 'supervision'),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.margin.margin_pf', [], 'supervision'),
            '',
            number_format(($result['caNetHt'] - $result['revenuePrice']), 2, '.', ''),
            '',
            $this->translator->trans('report.food_cost.synthetic.initial_stock', [], 'supervision'),
            '',
            number_format($result['initial'], 2, '.', ''),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.margin.voucher_meal', [], 'supervision'),
            '',
            number_format($result['voucherMeal'], 2, '.', ''),
            '',
            $this->translator->trans('keywords.in', [], 'supervision'),
            '',
            number_format($result['in'], 2, '.', ''),
        ];
        $serializedResult[] = [
            $this->translator->trans('keywords.loss', [], 'supervision'),
            $this->getPercentage(($result['inventoryLossVal'] + $result['soldLossVal']), $result['caNetHt']).' %',
            number_format(($result['inventoryLossVal'] + $result['soldLossVal']), 2, '.', ''),
            '',
            $this->translator->trans('keywords.out', [], 'supervision'),
            '',
            number_format($result['out'], 2, '.', ''),
        ];
        $serializedResult[] = [
            $this->translator->trans('labels.inventory_item', [], 'supervision'),
            $this->getPercentage($result['inventoryLossVal'], $result['caNetHt']).' %',
            number_format($result['inventoryLossVal'], 2, '.', ''),
            '',
            $this->translator->trans('report.food_cost.synthetic.final_stock', [], 'supervision'),
            '',
            number_format($result['final'], 2, '.', ''),
        ];
        $serializedResult[] = [
            $this->translator->trans('labels.sold_item', [], 'supervision'),
            $this->getPercentage($result['soldLossVal'], $result['caNetHt']).' %',
            number_format($result['soldLossVal'], 2, '.', ''),
            '',
            $this->translator->trans('keywords.consumption', [], 'supervision'),
            '',
            number_format($result['real_fc'], 2, '.', ''),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.ca.ca_net_ht', [], 'supervision'),
            '',
            number_format($result['caNetHt'], 2, '.', ''),
            '',
            $this->translator->trans('report.ca.ca_net_ht', [], 'supervision'),
            '',
            number_format($result['caNetHt'], 2, '.', ''),
        ];
        $serializedResult[] = [
            $this->translator->trans('report.food_cost.margin.theorical_margin', [], 'supervision'),
            '',
            number_format(($result['caNetHt'] - $result['theorical_fc']), 2, '.', ''),
            '',
            $this->translator->trans('report.food_cost.synthetic.real_margin', [], 'supervision'),
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

    public function getInValorization($data)
    {
        $sqlQueryFile = $this->sqlQueriesDir."/in_valorization_supervision.sql";

        if (!file_exists($sqlQueryFile)) {
            throw new \Exception("FILE IN VALORIZATION DOESN'T EXSIT");
        }

        $sql = file_get_contents($sqlQueryFile);
        $D1 = $data['beginDate']->format('Y-m-d');
        $D2 = $data['endDate']->format('Y-m-d');
        $restaurant = $data['restaurant']->getId();

        $stm = $this->em->getConnection()->prepare($sql);
        $transferIn = Transfer::TRANSFER_IN;
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('restaurant', $restaurant);
        $stm->bindParam('transferIn', $transferIn);

        $stm->execute();
        $data = $stm->fetch();

        return $data;
    }

    public function getOutValorization($data)
    {
        $sqlQueryFile = $this->sqlQueriesDir."/out_valorization_supervision.sql";

        if (!file_exists($sqlQueryFile)) {
            throw new \Exception("FILE OUT VALORIZATION DOESN'T EXSIT");
        }

        $sql = file_get_contents($sqlQueryFile);
        $D1 = $data['beginDate']->format('Y-m-d');
        $D2 = $data['endDate']->format('Y-m-d');
        $restaurant = $data['restaurant']->getId();

        $stm = $this->em->getConnection()->prepare($sql);
        $transferOut = Transfer::TRANSFER_OUT;
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('restaurant', $restaurant);
        $stm->bindParam('transfertOut', $transferOut);

        $stm->execute();
        $data = $stm->fetch();

        return $data;
    }

    public function generateExcelFile($filter, $result)
    {
        $colorOne = "ECECEC";
        $colorTwo = "ca9e67";
        $colorThree = "c8102e";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('report.food_cost.margin.title', [], 'supervision'), 0, 30));

        $sheet->mergeCells("B3:K6");
        $content = $this->translator->trans('report.food_cost.margin.title', [], 'supervision');
        $sheet->setCellValue('B3', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("B3"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B3"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 3), 22, true);
        //FILTER ZONE
        // START DATE
        $sheet->mergeCells("A8:B8");
        ExcelUtilities::setFont($sheet->getCell('A8'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A8"), $colorOne);
        $sheet->setCellValue('A8', $this->translator->trans('keywords.from', [], 'supervision').":");
        $sheet->mergeCells("C8:D8");
        ExcelUtilities::setFont($sheet->getCell('C8'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C8"), $colorOne);
        $sheet->setCellValue('C8', $filter['beginDate']->format('Y-m-d'));
        // END DATE
        $sheet->mergeCells("A9:B9");
        ExcelUtilities::setFont($sheet->getCell('A9'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A9"), $colorOne);
        $sheet->setCellValue('A9', $this->translator->trans('keywords.to', [], 'supervision').":");
        $sheet->mergeCells("C9:D9");
        ExcelUtilities::setFont($sheet->getCell('C9'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C9"), $colorOne);
        $sheet->setCellValue('C9', $filter['endDate']->format('Y-m-d'));
        // RESTAURANT
        $sheet->mergeCells("E8:F8");
        ExcelUtilities::setFont($sheet->getCell('E8'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E8"), $colorOne);
        $sheet->setCellValue('E8', $this->translator->trans('keywords.restaurant', [], 'supervision').":");
        $sheet->mergeCells("G8:H8");
        ExcelUtilities::setFont($sheet->getCell('G8'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G8"), $colorOne);
        $sheet->setCellValue('G8', $filter['restaurant']->getName());

        //CONTENT
        //Start first left part
        //Header
        $sheet->mergeCells('A11:E11');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A11"), $colorTwo);
        $sheet->setCellValue('A11', $this->translator->trans('keywords.theorical', [], 'supervision'));
        ExcelUtilities::setCellAlignment($sheet->getCell("A11"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A11"), $alignmentV);
        $sheet->mergeCells('F11:G11');
        ExcelUtilities::setBackgroundColor($sheet->getCell("F11"), $colorTwo);
        $sheet->setCellValue('F11', $this->translator->trans('labels.value', [], 'supervision'));
        ExcelUtilities::setCellAlignment($sheet->getCell("F11"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("F11"), $alignmentV);
        $sheet->mergeCells('H11:I11');
        ExcelUtilities::setBackgroundColor($sheet->getCell("H11"), $colorTwo);
        $sheet->setCellValue('H11', '%');
        ExcelUtilities::setCellAlignment($sheet->getCell("H11"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("H11"), $alignmentV);
        //First content
        $sheet->mergeCells('A12:C12');
        $sheet->setCellValue('A12', $this->translator->trans('report.food_cost.margin.mix_ca_ttc', [], 'supervision'));
        $sheet->mergeCells('F12:G12');
        $sheet->setCellValue('F12', round($result['revenuePrice'], 2));
        $sheet->mergeCells('H12:I12');
        if ($result['caBrutTtc'] != 0) {
            $sheet->setCellValue('H12', round(($result['revenuePrice'] / $result['caBrutTtc'] * 100), 2));
        }
        $sheet->mergeCells('A13:C13');
        $sheet->setCellValue('A13', $this->translator->trans('report.food_cost.margin.ideal_ht', [], 'supervision'));
        $sheet->mergeCells('F13:G13');
        $sheet->setCellValue('F13', round($result['revenuePrice'], 2));
        $sheet->mergeCells('H13:I13');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('H13', round(($result['revenuePrice'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('A14:I14');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A14"), $colorThree);
        //SECOND CONTENT
        $sheet->mergeCells('A15:B15');
        $sheet->setCellValue(
            'A15',
            $this->translator->trans('report.food_cost.synthetic.known_loss', [], 'supervision')
        );
        $sheet->mergeCells('F15:G15');
        $sheet->setCellValue('F15', round($result['inventoryLossVal'] + $result['soldLossVal'], 2));
        $sheet->mergeCells('H15:I15');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('H15', 0);
        } else {
            $sheet->setCellValue(
                'H15',
                round((($result['inventoryLossVal'] + $result['soldLossVal']) / $result['caNetHt'] * 100), 2)
            );
        }
        $sheet->mergeCells('C16:D16');
        $sheet->setCellValue('C16', $this->translator->trans('article', [], 'supervision'));
        $sheet->mergeCells('F16:G16');
        $sheet->setCellValue('F16', round($result['inventoryLossVal'], 2));
        $sheet->mergeCells('H16:I16');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('H16', round(($result['inventoryLossVal'] / $result['caNetHt'] * 100), 2));
        }
        $sheet->mergeCells('C17:D17');
        $sheet->setCellValue('C17', $this->translator->trans('finalProduct', [], 'supervision'));
        $sheet->mergeCells('F17:G17');
        $sheet->setCellValue('F17', round($result['soldLossVal'], 2));
        $sheet->mergeCells('H17:I17');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('H17', round(($result['soldLossVal'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('A18:I18');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A18"), $colorThree);
        //Third content
        $sheet->mergeCells('A19:B19');
        $sheet->setCellValue('A19', $this->translator->trans('report.food_cost.margin.total_loss', [], 'supervision'));
        $sheet->mergeCells('F19:G19');
        $sheet->setCellValue('F19', round($result['total_loss'], 2));
        $sheet->mergeCells('H19:I19');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('H19', 0);
        } else {
            $sheet->setCellValue('H19', round(($result['total_loss'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('A20:I20');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A20"), $colorThree);
        //Fourth content
        $sheet->mergeCells('A21:B21');
        $sheet->setCellValue(
            'A21',
            $this->translator->trans('report.food_cost.margin.theorical_foodcost', [], 'supervision')
        );
        $sheet->mergeCells('F21:G21');
        $sheet->setCellValue('F21', round($result['theorical_fc'], 2));
        $sheet->mergeCells('H21:I21');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('H21', 0);
        } else {
            $sheet->setCellValue('H21', round(($result['theorical_fc'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('A22:I22');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A22"), $colorThree);
        //FIFTH content
        $sheet->mergeCells('A23:B23');
        $sheet->setCellValue(
            'A23',
            $this->translator->trans('report.food_cost.synthetic.unknown_loss', [], 'supervision')
        );
        $sheet->mergeCells('F23:G23');
        $sheet->setCellValue('F23', round($result['unknown_loss'], 2));
        $sheet->mergeCells('H23:I23');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('H23', 0);
        } else {
            $sheet->setCellValue('H23', round(($result['unknown_loss'] / $result['caNetHt'] * 100), 2));
        }

        //SEPARATION
        $sheet->mergeCells('A25:I25');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A25"), $colorThree);
        //SIXTH content
        $sheet->mergeCells('A26:B26');
        $sheet->setCellValue('A26', $this->translator->trans('keywords.portion_control', [], 'supervision'));
        $sheet->mergeCells('F26:G26');
        $sheet->setCellValue('F26', round($result['portionControl'], 2));
        $sheet->mergeCells('H26:I26');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('H26', 0);
        } else {
            $sheet->setCellValue('H26', round(($result['portionControl'] / $result['caNetHt'] * 100), 2));
        }
        //end first left part

        //Start FIRST right part
        //Header
        $sheet->mergeCells('K11:O11');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K11"), $colorTwo);
        $sheet->setCellValue('K11', $this->translator->trans('keywords.real', [], 'supervision'));
        ExcelUtilities::setCellAlignment($sheet->getCell("K11"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("K11"), $alignmentV);
        $sheet->mergeCells('P11:Q11');
        ExcelUtilities::setBackgroundColor($sheet->getCell("P11"), $colorTwo);
        $sheet->setCellValue('P11', $this->translator->trans('labels.value', [], 'supervision'));
        ExcelUtilities::setCellAlignment($sheet->getCell("P11"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("P11"), $alignmentV);
        $sheet->mergeCells('R11:S11');
        ExcelUtilities::setBackgroundColor($sheet->getCell("r11"), $colorTwo);
        $sheet->setCellValue('R11', '%');
        ExcelUtilities::setCellAlignment($sheet->getCell("R11"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("R11"), $alignmentV);
        //First content
        $sheet->mergeCells('K12:M12');
        $sheet->setCellValue('k12', $this->translator->trans('report.ca.ca_net_ht', [], 'supervision'));
        $sheet->mergeCells('P12:Q12');
        $sheet->setCellValue('P12', round($result['caNetHt'], 2));
        $sheet->mergeCells('R12:S12');
        $sheet->setCellValue('R12', '100');
        $sheet->mergeCells('K13:M13');
        $sheet->setCellValue(
            'K13',
            $this->translator->trans('report.food_cost.synthetic.initial_stock', [], 'supervision')
        );
        $sheet->mergeCells('P13:Q13');
        $sheet->setCellValue('P13', round($result['initial'], 2));
        $sheet->mergeCells('R13:S13');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('R13', round(($result['initial'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('K14:S14');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K14"), $colorThree);
        //SECOND CONTENT
        $sheet->mergeCells('K16:M16');
        $sheet->setCellValue('k16', $this->translator->trans('keywords.in', [], 'supervision'));
        $sheet->mergeCells('P16:Q16');
        $sheet->setCellValue('P16', round($result['in'], 2));
        $sheet->mergeCells('R16:S16');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('R16', round(($result['in'] / $result['caNetHt'] * 100), 2));
        }
        $sheet->mergeCells('K17:M17');
        $sheet->setCellValue('K17', $this->translator->trans('keywords.out', [], 'supervision'));
        $sheet->mergeCells('P17:Q17');
        $sheet->setCellValue('P17', round($result['out'], 2));
        $sheet->mergeCells('R17:S17');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('R17', round(($result['out'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('K18:S18');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K18"), $colorThree);
        //Third content
        $sheet->mergeCells('K19:M19');
        $sheet->setCellValue(
            'K19',
            $this->translator->trans('report.food_cost.synthetic.final_stock', [], 'supervision')
        );
        $sheet->mergeCells('P19:Q19');
        $sheet->setCellValue('P19', round($result['final'], 2));
        $sheet->mergeCells('R19:S19');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('R19', 0);
        } else {
            $sheet->setCellValue('R19', round(($result['final'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('K20:S20');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K20"), $colorThree);
        //Fourth content
        $sheet->mergeCells('K21:M21');
        $sheet->setCellValue(
            'K21',
            $this->translator->trans('report.food_cost.margin.real_foodcost', [], 'supervision')
        );
        $sheet->mergeCells('P21:Q21');
        $sheet->setCellValue('P21', round($result['real_fc'], 2));
        $sheet->mergeCells('R21:S21');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('R21', 0);
        } else {
            $sheet->setCellValue('R21', round(($result['real_fc'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('K22:S22');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K22"), $colorThree);
        //FIFTH content
        $sheet->mergeCells('K23:M23');
        $sheet->setCellValue('K23', $this->translator->trans('report.food_cost.margin.voucher_pub', [], 'supervision'));
        $sheet->mergeCells('P23:Q23');
        $sheet->setCellValue('P23', round($result['discount'], 2));
        $sheet->mergeCells('R23:S23');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('R23', 0);
        } else {
            $sheet->setCellValue('R23', round(($result['discount'] / $result['caNetHt'] * 100), 2));
        }
        $sheet->mergeCells('K24:M24');
        $sheet->setCellValue(
            'K24',
            $this->translator->trans('report.food_cost.margin.voucher_meal_foodcost', [], 'supervision')
        );
        $sheet->mergeCells('P24:Q24');
        $sheet->setCellValue('P24', round($result['voucherMeal'], 2));
        $sheet->mergeCells('R24:S24');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('R24', 0);
        } else {
            $sheet->setCellValue('R24', round(($result['voucherMeal'] / $result['caNetHt'] * 100), 2));
        }
        //SEPARATION
        $sheet->mergeCells('K25:S25');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K25"), $colorThree);
        //SIXTH content
        $sheet->mergeCells('K26:M26');
        $sheet->setCellValue(
            'K26',
            $this->translator->trans('report.food_cost.margin.net_real_foodcost', [], 'supervision')
        );
        $sheet->mergeCells('P26:Q26');
        $sheet->setCellValue('P26', round($result['fcRealNet'], 2));
        $sheet->mergeCells('R26:S26');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('R26', 0);
        } else {
            $sheet->setCellValue('R26', round(($result['fcRealNet'] / $result['caNetHt'] * 100), 2));
        }
        $sheet->mergeCells('K27:M27');
        $sheet->setCellValue(
            'K27',
            $this->translator->trans('report.food_cost.margin.net_real_margin', [], 'supervision')
        );
        $sheet->mergeCells('P27:Q27');
        $sheet->setCellValue('P27', round($result['realMargin'], 2));
        $sheet->mergeCells('R27:S27');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('R27', 0);
        } else {
            $sheet->setCellValue('R27', round(($result['realMargin'] / $result['caNetHt'] * 100), 2));
        }
        //end first left part

        //Start SECOND left part
        //Header
        $sheet->mergeCells('A30:I30');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A30"), $colorTwo);
        $sheet->setCellValue('A30', $this->translator->trans('keywords.theorical', [], 'supervision'));
        ExcelUtilities::setCellAlignment($sheet->getCell("A30"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("A30"), $alignmentV);
        //First content
        $sheet->mergeCells('A31:C31');
        $sheet->setCellValue('A31', $this->translator->trans('report.food_cost.margin.margin_pf', [], 'supervision'));
        $sheet->mergeCells('H31:I31');
        $sheet->setCellValue('H31', round($result['caNetHt'] - $result['revenuePrice'], 2));
        //SEPARATION
        $sheet->mergeCells('A32:I32');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A32"), $colorThree);
        //SECOND CONTENT
        $sheet->mergeCells('A33:C33');
        $sheet->setCellValue(
            'A33',
            $this->translator->trans('report.food_cost.margin.voucher_meal', [], 'supervision')
        );
        $sheet->mergeCells('H33:I33');
        $sheet->setCellValue('H33', round($result['caVoucherMeal'], 2));
        //SEPARATION
        $sheet->mergeCells('A34:I34');
        ExcelUtilities::setBackgroundColor($sheet->getCell("A34"), $colorThree);
        //third CONTENT
        $sheet->mergeCells('A35:B35');
        $sheet->setCellValue('A35', $this->translator->trans('keywords.loss', [], 'supervision'));
        $sheet->mergeCells('F35:G35');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue(
                'F35',
                round((($result['inventoryLossVal'] + $result['soldLossVal']) / $result['caNetHt'] * 100), 2).' %HT'
            );
        }
        $sheet->mergeCells('H35:I35');
        $sheet->setCellValue('H35', round(($result['inventoryLossVal'] + $result['soldLossVal']), 2));
        $sheet->mergeCells('C36:D36');
        $sheet->setCellValue('C36', $this->translator->trans('labels.inventory_item', [], 'supervision'));
        $sheet->mergeCells('F36:G36');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('F36', round(($result['inventoryLossVal'] / $result['caNetHt'] * 100), 2).' %HT');
        }
        $sheet->mergeCells('H36:I36');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('H36', round($result['inventoryLossVal'], 2));
        }
        $sheet->mergeCells('C37:D37');
        $sheet->setCellValue('C37', $this->translator->trans('labels.sold_item', [], 'supervision'));
        $sheet->mergeCells('F37:G37');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('F37', round(($result['soldLossVal'] / $result['caNetHt'] * 100), 2).' %HT');
        }
        $sheet->mergeCells('H37:I37');
        if ($result['caNetHt'] != 0) {
            $sheet->setCellValue('H37', round($result['soldLossVal'], 2));
        }
        $sheet->mergeCells('A38:B38');
        $sheet->setCellValue('A38', $this->translator->trans('report.ca.ca_net_ht', [], 'supervision'));
        $sheet->mergeCells('H38:I38');
        $sheet->setCellValue('H38', round($result['caNetHt'], 2));
        $sheet->mergeCells('A39:B39');
        $sheet->setCellValue(
            'A39',
            $this->translator->trans('report.food_cost.margin.theorical_margin', [], 'supervision')
        );
        $sheet->mergeCells('H39:I39');
        $sheet->setCellValue('H39', round(($result['caNetHt'] - $result['theorical_fc']), 2));
        $sheet->mergeCells('H40:I40');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('H39', 0);
        } else {
            $sheet->setCellValue(
                'H40',
                round((($result['caNetHt'] - $result['theorical_fc']) / $result['caNetHt'] * 100), 2)
            );
        }
        //end SECOND left part


        //Start SECOND right part
        //Header
        $sheet->mergeCells('K30:S30');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K30"), $colorTwo);
        $sheet->setCellValue('K30', $this->translator->trans('report.food_cost.margin.period', [], 'supervision'));
        ExcelUtilities::setCellAlignment($sheet->getCell("K30"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("K30"), $alignmentV);
        //First content
        $sheet->mergeCells('K31:L31');
        $sheet->setCellValue(
            'K31',
            $this->translator->trans('report.food_cost.synthetic.initial_stock', [], 'supervision')
        );
        $sheet->mergeCells('R31:S31');
        $sheet->setCellValue('R31', round($result['initial'], 2));
        //SEPARATION
        $sheet->mergeCells('K32:S32');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K32"), $colorThree);
        //SECOND CONTENT
        $sheet->mergeCells('K33:L33');
        $sheet->setCellValue('K33', $this->translator->trans('keywords.in', [], 'supervision'));
        $sheet->mergeCells('R33:S33');
        $sheet->setCellValue('R33', round($result['in'], 2));
        //SEPARATION
        $sheet->mergeCells('K34:S34');
        ExcelUtilities::setBackgroundColor($sheet->getCell("K34"), $colorThree);
        //third CONTENT
        $sheet->mergeCells('K35:L35');
        $sheet->setCellValue('K35', $this->translator->trans('keywords.out', [], 'supervision'));
        $sheet->mergeCells('R35:S35');
        $sheet->setCellValue('R35', round($result['out'], 2));
        $sheet->mergeCells('K36:L36');
        $sheet->setCellValue(
            'K36',
            $this->translator->trans('report.food_cost.synthetic.final_stock', [], 'supervision')
        );
        $sheet->mergeCells('R36:S36');
        $sheet->setCellValue('R36', round($result['final'], 2));
        $sheet->mergeCells('K37:L37');
        $sheet->setCellValue('K37', $this->translator->trans('keywords.consumption', [], 'supervision'));
        $sheet->mergeCells('R37:S37');
        $sheet->setCellValue('R37', round($result['real_fc'], 2));
        $sheet->mergeCells('K38:L38');
        $sheet->setCellValue('K38', $this->translator->trans('report.ca.ca_net_ht', [], 'supervision'));
        $sheet->mergeCells('R38:S38');
        $sheet->setCellValue('R38', round($result['caNetHt'], 2));
        $sheet->mergeCells('K39:L39');
        $sheet->setCellValue(
            'K39',
            $this->translator->trans('report.food_cost.synthetic.real_margin', [], 'supervision')
        );
        $sheet->mergeCells('R39:S39');
        $sheet->setCellValue('R39', round(($result['caNetHt'] - $result['real_fc']), 2));
        $sheet->mergeCells('R40:S40');
        if ($result['caNetHt'] == 0) {
            $sheet->setCellValue('R40', 0);
        } else {
            $sheet->setCellValue(
                'R40',
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
        Restaurant $restaurant,
        \DateTime $startDate,
        \DateTime $endDate,
        ImportProgression $progression = null,
        $force = 0
    ) {
        $tmpDate = $startDate->format('Y-m-d');
        $countDays = intval($startDate->diff($endDate)->format('%a')) + 1;
        $dateStepPerc = 1 / (($countDays) ? $countDays : 1);

        $today = new \DateTime();
        $param = $this->em->getRepository(Parameter::class)->findOneBy(
            array(
                'type' => 'marge_food_cost',
            )
        );
        try {
            while (strtotime($tmpDate) <= strtotime($endDate->format("Y-m-d"))) {
                $tmp = date_create_from_format('Y-m-d', $tmpDate);
                echo "processing date :".$tmpDate." \n";
                //check if exists
                $existingMargeFoodCost = $this->em->getRepository(MargeFoodCostLine::class)->findBy(
                    array("date" => $tmp, "originRestaurant" => $restaurant)
                );

                if (($force != 0 && $existingMargeFoodCost) || $tmpDate === $today->format('Y-m-d')) {
                    foreach ($existingMargeFoodCost as $line) {
                        $this->em->remove($line);
                        $this->em->flush();
                        $existingMargeFoodCost = null;
                    }
                }
                if (!$existingMargeFoodCost) {
                    $filter['beginDate'] = $tmp;
                    $filter['endDate'] = $tmp;
                    $soldLoss = $this->em->getRepository(LossLine::class)->getSupervisionFiltredLossLineSold(
                        array('beginDate' => $tmpDate, 'endDate' => $tmpDate, "restaurants" => array($restaurant)),
                        true
                    );

                    $detailsTicket = $this->em->getRepository(
                        FinancialRevenue::class
                    )->getSupervisionFinancialRevenueBetweenDates($tmpDate, $tmpDate, [$restaurant], true);

                    $revenuePrice = $this->getRevenuePriceSold(
                        array('beginDate' => $tmp, 'endDate' => $tmp, 'restaurant' => $restaurant)
                    );
                    $initialStock = $this->productService->getInitialStockValorizationAtDate($tmp, $restaurant);

                    $finalStock = $this->productService->getFinalStockValorizationAtDate($tmp, $restaurant);

                    $invLoss = $this->em->getRepository(LossLine::class)->getSupervisionFiltredLossLine(
                        array('beginDate' => $tmpDate, 'endDate' => $tmpDate, "restaurants" => array($restaurant)),
                        true
                    );
                    $inValorization = $this->getInValorization(
                        array('beginDate' => $tmp, 'endDate' => $tmp, 'restaurant' => $restaurant)
                    )['totalin'];
                    $outValorization = $this->getOutValorization(
                        array('beginDate' => $tmp, 'endDate' => $tmp, 'restaurant' => $restaurant)
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
                    $margeFoodCost->setDate($tmp);
                    $margeFoodCost->setData($data);
                    $margeFoodCost->setOriginRestaurant($restaurant);
                    $this->em->persist($margeFoodCost);
                    $this->em->flush();
                }

                if ($progression) {
                    $progression->incrementPercentProgression($dateStepPerc * 100);
                    $this->em->flush();
                }

                $now = new \DateTime();
                $param->setUpdatedAt($now);
                $this->em->persist($param);
                $this->em->flush($param);
                $tmpDate = date("Y-m-d", strtotime("+1 day", strtotime($tmpDate)));
            }
        } catch (\Exception $e) {
            $param->setValue(0);
            $this->em->persist($param);
            $this->em->flush($param);
            var_dump($e->getMessage());
        }
    }

    public function formatResultMarginFoodCost(MargeFoodCostRapport $rapportTmp)
    {
        $lines = $this->em->getRepository(MargeFoodCostLine::class)->createQueryBuilder("f")
            ->where("f.date <= :endDate ")
            ->andWhere("f.date >= :startDate")
            ->andWhere('f.originRestaurant=:restaurant')
            ->setParameter("startDate", $rapportTmp->getStartDate())
            ->setParameter("endDate", $rapportTmp->getEndDate())
            ->setParameter('restaurant', $rapportTmp->getOriginRestaurant())
            ->orderBy("f.date", "ASC")
            ->getQuery()
            ->getResult();
        $results = [];
        $count = 0;
        foreach ($lines as $l) {
            if ($l->getData() != null) {
                $results[] = json_decode($l->getData(), true);
                $count++;
            }
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

        foreach ($results as $element) {
            $finalResult['caNetHt'] += $element['caNetHT'];
            $finalResult['caNetTTC'] += $element['caNetTTC'];
            $finalResult['caBrutTtc'] += $element['caBrutTTC'];
            $finalResult['caVoucherMeal'] += $element['br'];
            $finalResult['caDiscount'] += $element['caDiscount'];
            $finalResult['inventoryLossVal'] += $element['invLoss'];
            $finalResult['soldLossVal'] += $element['soldLoss'];
            $finalResult['revenuePrice'] += $element['revenuePrice'];
            $finalResult['in'] += $element['inValorization'];
            $finalResult['out'] += $element['outValorization'];
        }

        $finalResult['initial'] = $results[0]['initialStock'];
        $finalResult['final'] = $results[$count - 1]['finalStock'];
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

    public function checkLocked()
    {
        $param = $this->em->getRepository(Parameter::class)->findOneBy(
            array(
                'type' => 'marge_food_cost',
                "originRestaurant" => null,
            )
        );
        $now = new \DateTime('now');
        if (!$param || $param == null || $param->getValue() == 0) {
            if (!$param) {
                $param = new Parameter();
                $param->setType('marge_food_cost');
                $param->setCreatedAt($now);
                $param->setUpdatedAt($now);
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
