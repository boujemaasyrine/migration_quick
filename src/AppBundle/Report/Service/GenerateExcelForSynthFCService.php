<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/07/2016
 * Time: 14:53
 */

namespace AppBundle\Report\Service;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Report\Entity\SyntheticFoodCostRapport;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class GenerateExcelForSynthFCService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Factory
     */
    private $phpExcel;

    private $tmpDir;

    private $translator;

    private $synthFcService;

    public function __construct(
        EntityManager $entityManager,
        ReportFoodCostInterface $synthFcService,
        Factory $phpExcel,
        Translator $translator,
        $tmpDir
    ) {
        $this->em = $entityManager;
        $this->phpExcel = $phpExcel;
        $this->tmpDir = $tmpDir;
        $this->translator = $translator;
        $this->synthFcService = $synthFcService;
    }

    /**
     * @param SyntheticFoodCostRapport $rapport
     * @return Response
     * @throws \PHPExcel_Exception
     */
    public function exportExcel(SyntheticFoodCostRapport $rapport, Restaurant $currentRestaurant, $logoPath)
    {
        $data = $this->synthFcService->formatResultFoodCostSynthetic($rapport);

        $offsetRow = 17;//min 15
        $offsetCol = 0;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle($this->translator->trans('report.food_cost.synthetic.title'));

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

        //Entete
        $sheet->setCellValueByColumnAndRow(
            $offsetCol+ 28,
            2,
            $this->translator->trans('control_stock.document_generated')." ".date('d/m/Y H:i:s')
        );

        $sheet->mergeCellsByColumnAndRow($offsetCol+ 28, 2, $offsetCol + 31, 2);
        ExcelUtilities::setCellAlignment(
            $sheet->getStyleByColumnAndRow(28, 2),
            \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT
        );

        $sheet->mergeCellsByColumnAndRow(1, 5, $offsetCol + 31, 8);
        $sheet->setCellValueByColumnAndRow(1, 5, $this->translator->trans('report.food_cost.synthetic.title'));
        ExcelUtilities::setCellAlignment(
            $sheet->getStyleByColumnAndRow(1, 5),
            \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        );
        ExcelUtilities::setVerticalCellAlignment(
            $sheet->getStyleByColumnAndRow(1, 5),
            \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        );
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 5), 22, true);

        $sheet->mergeCellsByColumnAndRow(1, 9, $offsetCol + 31, 12);
        $sheet->setCellValueByColumnAndRow(
            1,
            9,
            "Période Du ".$rapport->getStartDate()->format('d/m/y')." au ".$rapport->getEndDate()->format('d/m/y')
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getStyleByColumnAndRow(1, 9),
            \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        );
        ExcelUtilities::setVerticalCellAlignment(
            $sheet->getStyleByColumnAndRow(1, 9),
            \PHPExcel_Style_Alignment::HORIZONTAL_CENTER
        );
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 9), 14, true);
        //fin entete


        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 6,
            $offsetRow - 2,
            $this->translator->trans('keyword.theorical')
        );
        $sheet->mergeCellsByColumnAndRow($offsetCol + 6, $offsetRow - 2, $offsetCol + 18, $offsetRow - 2);

        $sheet->setCellValueByColumnAndRow($offsetCol + 19, $offsetRow - 2, $this->translator->trans('keyword.real'));
        $sheet->mergeCellsByColumnAndRow($offsetCol + 19, $offsetRow - 2, $offsetCol + 25, $offsetRow - 2);

        $sheet->setCellValueByColumnAndRow($offsetCol + 26, $offsetRow - 2, $this->translator->trans('keyword.margin'));
        $sheet->mergeCellsByColumnAndRow($offsetCol + 26, $offsetRow - 2, $offsetCol + 31, $offsetRow - 2);

        ExcelUtilities::setBackgroundColor(
            $sheet->getStyleByColumnAndRow($offsetCol + 6, $offsetRow - 2, $offsetCol + 31, $offsetRow - 2),
            "ca9e67"
        );
        ExcelUtilities::setFont(
            $sheet->getStyleByColumnAndRow($offsetCol + 6, $offsetRow - 2, $offsetCol + 31, $offsetRow - 2),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getStyleByColumnAndRow($offsetCol + 6, $offsetRow - 2, $offsetCol + 31, $offsetRow - 2)
        );

        $secondHeader = [
            'week' => 'S',
            'date' => $this->translator->trans('keyword.date'),
            'ca_brut_ttc' => $this->translator->trans('report.ca.ca_brut_ttc'),
            'ca_net_ht' => $this->translator->trans('report.ca.ca_net_ht'),
            'ventes_pr' => $this->translator->trans('report.food_cost.synthetic.sold_pr_ht'),
            'fc_mix' => $this->translator->trans('report.food_cost.synthetic.fc_mix'),
            'fc_ideal' => $this->translator->trans('report.food_cost.synthetic.ideal_fc'),
            'pertes_i_inv' => $this->translator->trans('report.food_cost.synthetic.loss_inventory'),
            'pertes_inv_pourcentage' => '%',
            'pertes_i_vtes' => $this->translator->trans('report.food_cost.synthetic.loss_sold'),
            'pertes_vtes_pourcentage' => '%',
            'pertes_connues' => $this->translator->trans('report.food_cost.synthetic.known_loss'),
            'pertes_connues_pourcentage' => '%',
            'pertes_inconnues' => $this->translator->trans('report.food_cost.synthetic.unknown_loss'),
            'pertes_inconnues_pourcentage' => '%',
            'pertes_totales' => $this->translator->trans('report.food_cost.synthetic.total_loss'),
            'pertes_totales_pourcentage' => '%',
            'fc_theo' => $this->translator->trans('report.food_cost.synthetic.theorical_fc'),
            'marge_theo' => $this->translator->trans('report.food_cost.synthetic.theorical_margin'),
            'initialStock' => $this->translator->trans('report.food_cost.synthetic.initial_stock'),
            'entree' => $this->translator->trans('report.food_cost.synthetic.in'),
            'sortie' => $this->translator->trans('report.food_cost.synthetic.out'),
            'finalStock' => $this->translator->trans('report.food_cost.synthetic.final_stock'),
            'conso_real' => $this->translator->trans('report.food_cost.synthetic.real_consomation'),
            'fc_real' => $this->translator->trans('report.food_cost.synthetic.real_fc'),
            'marge_real' => $this->translator->trans('report.food_cost.synthetic.real_margin'),
            'br' => 'BR',
            'br_pourcentage' => '%',
            'pr_pub' => 'Pub',
            'discount_pourcentage' => '%',
            'fc_real_net' => $this->translator->trans('report.food_cost.synthetic.real_fc_net'),
            'marge_brute' => $this->translator->trans('report.food_cost.synthetic.brut_margin'),
        ];

        $this->setLine($sheet, $offsetCol, $offsetRow, -1, $secondHeader, 'header');

        $weekCumul = 1;
        $lastWeek = null;
        $weeks = $data['weeks'];
        $i = 0;
        foreach ($data['result'] as $line) {
            //week lines
            if (is_null($lastWeek)) {
                $lastWeek = $line['week'];
            }

            if (!is_null($lastWeek) && $line['week'] != $lastWeek) {
                $lastWeekTotal = $data['perWeek'][$lastWeek];
                $lastWeek = $line['week'];
                $this->setLine($sheet, $offsetCol, $offsetRow, $i, $lastWeekTotal, 'week');
                $i++;
            }

            //day lines
            $this->setLine($sheet, $offsetCol, $offsetRow, $i, $line, 'day');
            $i++;
        }

        //show last week
        if ($lastWeek) {
            $this->setLine($sheet, $offsetCol, $offsetRow, $i, $data['perWeek'][$lastWeek], 'week');
            $i++;
        }

        //show total line
        if (count($data['total']) > 0) {
            $this->setLine($sheet, $offsetCol, $offsetRow, $i, $data['total'], 'total');
        }

        return $this->getResponse($rapport, $phpExcelObject);
    }

    private function setLine(\PHPExcel_Worksheet $sheet, $offsetCol, $offsetRow, $i, $line, $type = null)
    {

        $formatCallback = function ($x) {
            return number_format($x, 2, '.', '');
        };

        switch ($type) {
            case 'day':
                $dateS = preg_replace('/\.\d+/i', '', $line['date']);

                $date = \DateTime::createFromFormat('Y-m-d H:i:s', $dateS['date']);
                $sheet->setCellValueByColumnAndRow($offsetCol + 0, $offsetRow + $i, $line['week']);
                $sheet->setCellValueByColumnAndRow($offsetCol + 1, $offsetRow + $i, $date->format('d/m'));
                break;
            case 'week':
                $sheet->setCellValueByColumnAndRow($offsetCol + 0, $offsetRow + $i, '');
                $sheet->setCellValueByColumnAndRow($offsetCol + 1, $offsetRow + $i, 'Total');
                break;
            case 'total':
                $sheet->setCellValueByColumnAndRow($offsetCol + 0, $offsetRow + $i, '');
                $sheet->setCellValueByColumnAndRow($offsetCol + 1, $offsetRow + $i, 'Total');
                break;
            case 'header':
                $sheet->setCellValueByColumnAndRow($offsetCol + 0, $offsetRow + $i, $line['week']);
                $sheet->setCellValueByColumnAndRow($offsetCol + 1, $offsetRow + $i, $line['date']);
                $formatCallback = function ($x) {
                    return $x;
                };
                break;
        }

        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 2,
            $offsetRow + $i,
            $formatCallback($line['ca_brut_ttc'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 3,
            $offsetRow + $i,
            $formatCallback($line['ca_net_ht'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 4,
            $offsetRow + $i,
            $formatCallback($line['ventes_pr'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 5,
            $offsetRow + $i,
            $formatCallback($line['fc_mix'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 6,
            $offsetRow + $i,
            $formatCallback($line['fc_ideal'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 7,
            $offsetRow + $i,
            $formatCallback($line['pertes_i_inv'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 8,
            $offsetRow + $i,
            $formatCallback($line['pertes_inv_pourcentage'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 9,
            $offsetRow + $i,
            $formatCallback($line['pertes_i_vtes'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 10,
            $offsetRow + $i,
            $formatCallback($line['pertes_vtes_pourcentage'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 11,
            $offsetRow + $i,
            $formatCallback($line['pertes_connues'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 12,
            $offsetRow + $i,
            $formatCallback($line['pertes_connues_pourcentage'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 13,
            $offsetRow + $i,
            $formatCallback($line['pertes_inconnues'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 14,
            $offsetRow + $i,
            $formatCallback($line['pertes_inconnues_pourcentage'], 2, ',', '')
        );
        if ($type == 'header') {
            $sheet->setCellValueByColumnAndRow(
                $offsetCol + 15,
                $offsetRow + $i,
                $formatCallback($line['pertes_totales'], 2, ',', '')
            );
            $sheet->setCellValueByColumnAndRow(
                $offsetCol + 16,
                $offsetRow + $i,
                $formatCallback($line['pertes_totales_pourcentage'], 2, ',', '')
            );
        } else {
            $sheet->setCellValueByColumnAndRow(
                $offsetCol + 15,
                $offsetRow + $i,
                $formatCallback(abs($line['pertes_totales']), 2, ',', '')
            );
            $sheet->setCellValueByColumnAndRow(
                $offsetCol + 16,
                $offsetRow + $i,
                $formatCallback(abs($line['pertes_totales_pourcentage']), 2, ',', '')
            );
        }
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 17,
            $offsetRow + $i,
            $formatCallback($line['fc_theo'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 18,
            $offsetRow + $i,
            $formatCallback($line['marge_theo'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 19,
            $offsetRow + $i,
            $formatCallback($line['initialStock'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 20,
            $offsetRow + $i,
            $formatCallback($line['entree'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 21,
            $offsetRow + $i,
            $formatCallback($line['sortie'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 22,
            $offsetRow + $i,
            $formatCallback($line['finalStock'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 23,
            $offsetRow + $i,
            $formatCallback($line['conso_real'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 24,
            $offsetRow + $i,
            $formatCallback($line['fc_real'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 25,
            $offsetRow + $i,
            $formatCallback($line['marge_real'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow($offsetCol + 26, $offsetRow + $i, $formatCallback($line['br'], 2, ',', ''));
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 27,
            $offsetRow + $i,
            $formatCallback($line['br_pourcentage'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 28,
            $offsetRow + $i,
            $formatCallback($line['pr_pub'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 29,
            $offsetRow + $i,
            $formatCallback($line['discount_pourcentage'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 30,
            $offsetRow + $i,
            $formatCallback($line['fc_real_net'], 2, ',', '')
        );
        $sheet->setCellValueByColumnAndRow(
            $offsetCol + 31,
            $offsetRow + $i,
            $formatCallback($line['marge_brute'], 2, ',', '')
        );

        switch ($type) {
            case 'day':
                ExcelUtilities::setBackgroundColor(
                    $sheet->getStyleByColumnAndRow($offsetCol + 0, $offsetRow + $i, $offsetCol + 1, $offsetRow + $i),
                    "c8102e"
                );
                ExcelUtilities::setTextColor(
                    $sheet->getStyleByColumnAndRow($offsetCol + 0, $offsetRow + $i, $offsetCol + 1, $offsetRow + $i),
                    "FFFFFF"
                );
                break;
            case 'week':
                ExcelUtilities::setBackgroundColor(
                    $sheet->getStyleByColumnAndRow($offsetCol + 1, $offsetRow + $i, $offsetCol + 31, $offsetRow + $i),
                    "8FCCFF"
                );
                ExcelUtilities::setBackgroundColor(
                    $sheet->getStyleByColumnAndRow($offsetCol + 0, $offsetRow + $i, $offsetCol + 0, $offsetRow + $i),
                    "c8102e"
                );
                break;
            case 'total':
                ExcelUtilities::setBackgroundColor(
                    $sheet->getStyleByColumnAndRow($offsetCol + 0, $offsetRow + $i, $offsetCol + 1, $offsetRow + $i),
                    "c8102e"
                );
                ExcelUtilities::setBackgroundColor(
                    $sheet->getStyleByColumnAndRow($offsetCol + 2, $offsetRow + $i, $offsetCol + 31, $offsetRow + $i),
                    "ca9e67"
                );
                ExcelUtilities::setTextColor(
                    $sheet->getStyleByColumnAndRow($offsetCol + 0, $offsetRow + $i, $offsetCol + 1, $offsetRow + $i),
                    "FFFFFF"
                );
                break;
            case 'header':
                ExcelUtilities::setBackgroundColor(
                    $sheet->getStyleByColumnAndRow($offsetCol + 0, $offsetRow + $i, $offsetCol + 31, $offsetRow + $i),
                    "ede2c9"
                );
                ExcelUtilities::setFont(
                    $sheet->getStyleByColumnAndRow($offsetCol + 0, $offsetRow + $i, $offsetCol + 31, $offsetRow + $i),
                    11,
                    true
                );
                break;
        }
    }

    private function getResponse(SyntheticFoodCostRapport $rapport, $phpExcelObject)
    {
        //Creation de la response
        $filename = "fc_synthetic_".$rapport->getStartDate()->format('Y_m_d')."_".$rapport->getEndDate()->format(
            'Y_m_d'
        ).".xls";
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
