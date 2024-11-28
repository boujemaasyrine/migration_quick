<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/03/2016
 * Time: 12:16
 */

namespace AppBundle\Report\Service;

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Repository\RestaurantRepository;
use AppBundle\Report\Entity\ThreeWeekReportLine;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use PHPStan\Type\ObjectType;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class ReportStockService
{

    private $em;
    private $translator;
    private $sqlQueriesDir;
    private $phpExcel;

    public function __construct(
        EntityManager $em,
        Translator $translator,
        $sqlQueriesDir,
        Factory $factory
    )
    {
        $this->em = $em;
        $this->translator = $translator;
        $this->sqlQueriesDir = $sqlQueriesDir;
        $this->phpExcel = $factory;
    }

    //belsem 2019

    public function getAllRestaurants($filter)
    {

        if (sizeof($filter['restaurants']) == 0) {
            return $this->em->getRepository(Restaurant ::class)->getAllActiveRestaurants();
        } else {
            return $filter['restaurants'];
        }
    }
    //done by belsem 2019
    public function generateSupervisionRealStockItems($filter, $locale = 'fr')
    {
        $result = $this->em->getRepository(ProductPurchased::class)->getSupervisionRealStockItems($filter, $locale);
        // agregation on data
        $data = new ArrayCollection($result['data']);

//        $categories = $data->map(
//            function ($e) {
//                return $e['category_name'];
//
//            }
//        );
        //var_dump($data);die;
        $products = $filter['products'];
        $nameProducts = array();
        foreach ($products as $item) {
            $nameProducts[] = $item->getName();
        }

        $lineByCategory = array("data" => array());

        foreach ($nameProducts as $category) {
            $lineByCategory['data'][$category] = array("data" => array());
        }
        foreach ($data as $key => $item) {
            $categoryName = $item['description'];

            if (array_key_exists($categoryName, $lineByCategory["data"])) {
                $lineByCategory["data"][$categoryName]["data"][] = $item;

            }
        }
        $result['data'] = $lineByCategory;
        return $result;
    }

    public function getDataForWeek($startOfWeek, $endOfWeek, $restaurantId, $postfix)
    {
        $result = $this->em->getRepository(ProductPurchased::class)->getDataForWeek($startOfWeek, $endOfWeek, $restaurantId, $locale = 'fr', $postfix);
        return $result;
    }

    public function calculate($startOfWeek, $endOfWeek, $restaurant, $weekNumber, $postfix)
    {
        $restaurantId = $restaurant->getId();
//        dump($restaurantId);
        $data = $this->getDataForWeek($startOfWeek, $endOfWeek, $restaurantId, $postfix);
        // Enregistrement des données dans la table // Code pour enregistrer chaque ligne dans la table
        $oldThreeWeek = $this->em->getRepository(ThreeWeekReportLine::class)->findBy(array('weekNumber' => $weekNumber, 'originRestaurant' => $restaurant));
        if ($oldThreeWeek) {
            foreach ($oldThreeWeek as $item) {
                $this->em->remove($item);
            }
            $this->em->flush();
        }

        $threeWeek = new ThreeWeekReportLine();
        $threeWeek->setDate(\DateTime::createFromFormat('Y-m-d', $startOfWeek));
        $threeWeek->setEndDate(\DateTime::createFromFormat('Y-m-d', $endOfWeek));
        $threeWeek->setData($data); // Enregistrer le tableau complet dans la propriété "data"
        $threeWeek->setWeekNumber($weekNumber);
        $threeWeek->setOriginRestaurant($restaurant);
        $this->em->persist($threeWeek);
        $this->em->flush();
    }

    public function generateDataPrviousWeeks($week, $restaurant)
    {

        $weeks = array(1, 2, 3);
        $resultForWeek = array();
        // récupérer les données pour chaque semaine
        foreach ($weeks as $key => $week) {

            $oldThreeWeek = $this->em->getRepository(ThreeWeekReportLine::class)->findBy(array('weekNumber' => $week,'originRestaurant'=>$restaurant));
            $resultForWeek[] = $oldThreeWeek;
        }
        $merged = array();
        $i = 0; // initialiser le compteur à 0
        // Bouclez à travers les données pour chaque semaine
        foreach ($resultForWeek as $weekData) {
            // Obtenir les données de la semaine actuelle
            $data = json_decode($weekData[0]->getData(), true);
            $productData = $data['data'];

            // Bouclez à travers les données pour chaque produit
            foreach ($productData as $product) {
                $productId = $product['product_id'];

                // Vérifiez si le produit existe déjà dans `$merged`
                if (!isset($merged[$productId])) {
                    // Si le produit n'existe pas, ajoutez une nouvelle entrée pour le produit
                    $merged[$productId] = $product;

                } else {
                    // Si le produit existe, fusionnez les données du produit avec les données existantes
                    $merged[$productId] = array_merge($merged[$productId], $product);

                }
            }
        }

        $merged = array_values($merged);

        $groupedByCategory = array(
            'aggregations' => array(
                'valorisation_minus_1' => 0,
                'valorisation_minus_2' => 0,
                'valorisation_minus_3' => 0,
                'gain_inf_seuil' => 0,
                'perte_sup_seuil' => 0,
                'without_error' => 0,
                'total_pie_data' => 0,
            ),
            'data' => array()
        );
        foreach ($merged as $product) {
            $category = $product['category_name'];
            // Ajouter la catégorie au tableau de données si elle n'existe pas déjà
            if (!isset($groupedByCategory['data'][$category])) {
                // Si la catégorie n'existe pas, ajoutez une nouvelle entrée pour la catégorie
                $groupedByCategory['data'][$category] = [
                    "aggregations" => [
                        "valorisation_minus_1" => 0,
                        "valorisation_minus_2" => 0,
                        "valorisation_minus_3" => 0,
                    ],
                    "data" => [],
                ];
            }

            $ventes_minus_1 = array_key_exists('ventes_minus_1', $product) ? floatval($product['ventes_minus_1']) : 0;
            $item_vtes_minus_1 = array_key_exists('item_vtes_minus_1', $product) ? floatval($product['item_vtes_minus_1']) : 0;
            $item_inv_minus_1 = array_key_exists('item_inv_minus_1', $product) ? floatval($product['item_inv_minus_1']) : 0;
            $initial_minus_1 = array_key_exists('initial_minus_1', $product) ? floatval($product['initial_minus_1']) : 0;
            $entree_minus_1 = array_key_exists('entree_minus_1', $product) ? floatval($product['entree_minus_1']) : 0;
            $sortie_minus_1 = array_key_exists('sortie_minus_1', $product) ? floatval($product['sortie_minus_1']) : 0;
            $final_minus_1 = array_key_exists('final_minus_1', $product) ? floatval($product['final_minus_1']) : 0;
            $product['theo_minus_1'] = $ventes_minus_1 + $item_vtes_minus_1 + $item_inv_minus_1;
            $product['reel_minus_1'] = $initial_minus_1 + $entree_minus_1 - $sortie_minus_1 - $final_minus_1;
            $product['ecart_minus_1'] = $product['theo_minus_1'] - $product['reel_minus_1'];
            $buying_cost_minus_1=  array_key_exists('buying_cost_minus_1', $product) ? floatval($product['buying_cost_minus_1']) : 0;
            $inventory_qty_minus_1=  array_key_exists('inventory_qty_minus_1', $product) ? intval($product['inventory_qty_minus_1']) : 0;
            $product['valorisation_minus_1'] = ($inventory_qty_minus_1 != 0) ? ($product['ecart_minus_1'] * ($buying_cost_minus_1 / $inventory_qty_minus_1)) : 0;


            $ventes_minus_2 = array_key_exists('ventes_minus_2', $product) ? floatval($product['ventes_minus_2']) : 0;
            $item_vtes_minus_2 = array_key_exists('item_vtes_minus_2', $product) ? floatval($product['item_vtes_minus_2']) : 0;
            $item_inv_minus_2 = array_key_exists('item_inv_minus_2', $product) ? floatval($product['item_inv_minus_2']) : 0;
            $initial_minus_2 = array_key_exists('initial_minus_2', $product) ? floatval($product['initial_minus_2']) : 0;
            $entree_minus_2 = array_key_exists('entree_minus_2', $product) ? floatval($product['entree_minus_2']) : 0;
            $sortie_minus_2 = array_key_exists('sortie_minus_2', $product) ? floatval($product['sortie_minus_2']) : 0;
            $final_minus_2 = array_key_exists('final_minus_2', $product) ? floatval($product['final_minus_2']) : 0;
            $product['theo_minus_2'] = $ventes_minus_2 + $item_vtes_minus_2 + $item_inv_minus_2;
            $product['reel_minus_2'] = $initial_minus_2 + $entree_minus_2 - $sortie_minus_2 - $final_minus_2;
            $product['ecart_minus_2'] = $product['theo_minus_2'] - $product['reel_minus_2'];
            $buying_cost_minus_2=  array_key_exists('buying_cost_minus_2', $product) ? floatval($product['buying_cost_minus_2']) : 0;
            $inventory_qty_minus_2=  array_key_exists('inventory_qty_minus_2', $product) ? intval($product['inventory_qty_minus_2']) : 0;
            $product['valorisation_minus_2'] = ($inventory_qty_minus_2 != 0) ? ($product['ecart_minus_2'] * ($buying_cost_minus_2 / $inventory_qty_minus_2)) : 0;


            $ventes_minus_3 = array_key_exists('ventes_minus_3', $product) ? floatval($product['ventes_minus_3']) : 0;
            $item_vtes_minus_3 = array_key_exists('item_vtes_minus_3', $product) ? floatval($product['item_vtes_minus_3']) : 0;
            $item_inv_minus_3 = array_key_exists('item_inv_minus_3', $product) ? floatval($product['item_inv_minus_3']) : 0;
            $initial_minus_3 = array_key_exists('initial_minus_3', $product) ? floatval($product['initial_minus_3']) : 0;
            $entree_minus_3 = array_key_exists('entree_minus_3', $product) ? floatval($product['entree_minus_3']) : 0;
            $sortie_minus_3 = array_key_exists('sortie_minus_3', $product) ? floatval($product['sortie_minus_3']) : 0;
            $final_minus_3 = array_key_exists('final_minus_3', $product) ? floatval($product['final_minus_3']) : 0;
            $product['theo_minus_3'] = $ventes_minus_3 + $item_vtes_minus_3 + $item_inv_minus_3;
            $product['reel_minus_3'] = $initial_minus_3 + $entree_minus_3 - $sortie_minus_3 - $final_minus_3;
            $product['ecart_minus_3'] = $product['theo_minus_3'] - $product['reel_minus_3'];
            $buying_cost_minus_3=  array_key_exists('buying_cost_minus_3', $product) ? floatval($product['buying_cost_minus_3']) : 0;
            $inventory_qty_minus_3=  array_key_exists('inventory_qty_minus_3', $product) ? intval($product['inventory_qty_minus_3']) : 0;
            $product['valorisation_minus_3'] = ($inventory_qty_minus_3 != 0) ? ($product['ecart_minus_3'] * ($buying_cost_minus_3 / $inventory_qty_minus_3)) : 0;


            // Ajoutez le produit à la catégorie correspondante
            if (array_key_exists($category, $groupedByCategory["data"])) {
                $groupedByCategory['data'][$category]['data'][] = $product;
                $groupedByCategory["data"][$category]["aggregations"]['valorisation_minus_1'] += floatval($product['valorisation_minus_1']);
                $groupedByCategory["data"][$category]["aggregations"]['valorisation_minus_2'] += floatval($product['valorisation_minus_2']);
                $groupedByCategory["data"][$category]["aggregations"]['valorisation_minus_3'] += floatval($product['valorisation_minus_3']);
                $groupedByCategory["aggregations"]["valorisation_minus_1"] += floatval($product['valorisation_minus_1']);
                $groupedByCategory["aggregations"]["valorisation_minus_2"] += floatval($product['valorisation_minus_2']);
                $groupedByCategory["aggregations"]["valorisation_minus_3"] += floatval($product['valorisation_minus_3']);
                $groupedByCategory["aggregations"]["gain_inf_seuil"] += 1;
                $groupedByCategory["aggregations"]["perte_sup_seuil"] += 1;
                $groupedByCategory["aggregations"]["without_error"] += 1;
                $groupedByCategory["aggregations"]["total_pie_data"] += 1;
            }
        }

        $resultThree['data'] = $groupedByCategory;
        return $resultThree;

    }

    public function generateEcartsPortionControlReport($filter, $locale = 'fr', $flag)
    {

        $result = $this->em->getRepository(ProductPurchased::class)
            ->calculatePortionControlData($filter, $locale, $flag);
        // agregation on data
        $data = new ArrayCollection($result['data']);

        $categories = $data->map(
            function ($e) {
                return $e['category_name'];
            }
        );

        $lineByCategory = [
            "aggregations" => [
                "positive_ecart" => 0,
                "negative_ecart" => 0,
                "final_value" => 0,
                "initial_value" => 0,
                "valorisation" => 0,
//                "valorisation_minus_1" => 0,
//                "valorisation_minus_2" => 0,
//                "valorisation_minus_3" => 0,
                "gain_inf_seuil" => 0,
                "perte_sup_seuil" => 0,
                "without_error" => 0,
                "total_pie_data" => 0,
            ],
            "data" => [

            ],
        ];

        foreach ($categories as $category) {
            $lineByCategory['data'][$category] = [
                "aggregations" => [
                    "final_value" => 0,
                    "valorisation" => 0,
//                    "valorisation_minus_1" => 0,
//                    "valorisation_minus_2" => 0,
//                    "valorisation_minus_3" => 0,
                ],
                "data" => [],
            ];
        }

        $initial_valo = 0;
        foreach ($data as $key => $item) {
            // calculate Theo, Reel, ecart and valorisation
            $ventes = floatval($item['ventes']);
            $item_vtes = floatval($item['item_vtes']);
            $item_inv = floatval($item['item_inv']);

            $initial = floatval($item['initial']);
            $entree = floatval($item['entree']);
            $sortie = floatval($item['sortie']);
            $final = floatval($item['final']);
            /*$inValorization=floatval($item['valeur_entree']);
            $outValorization=floatval($item['valeur_sortie']);*/

            $inValorization = floatval($item['in_valorization']);
            $outValorization = floatval($item['out_valorization']);
            $item['theo'] = $ventes + $item_vtes + $item_inv;
            $item['reel'] = $initial + $entree - $sortie - $final;
            $item['ecart'] = $item['theo'] - $item['reel'];
            //            $item['valorisation'] = $item['ventes_valorization'];
            //$item['valorisation'] = $item['ventes_valorization'] + $item['sold_loss_valorization'] + $item['inv_loss_valorisation'] - $item['initial_valorization'] - $item['in_valorization'] + $item['out_valorization'] + $item['final_valorization'];
            $item['valorisation'] = $item['ventes_valorization']
                + $item['sold_loss_valorization']
                + $item['inv_loss_valorisation'] - $item['initial_valorization']
                - $inValorization + $outValorization
                + $item['final_valorization'];

            // valos for an item of the thee previous Weeks

//            if ($result['isCalendarWeek'] && $flag == '1') {
//
//                $ventes_minus_1 = floatval($item['ventes_minus_1']);
//                $item_vtes_minus_1 = floatval($item['item_vtes_minus_1']);
//                $item_inv_minus_1 = floatval($item['item_inv_minus_1']);
//                $initial_minus_1 = floatval($item['initial_minus_1']);
//                $entree_minus_1 = floatval($item['entree_minus_1']);
//                $sortie_minus_1 = floatval($item['sortie_minus_1']);
//                $final_minus_1 = floatval($item['final_minus_1']);
//                $item['theo_minus_1'] = $ventes_minus_1 + $item_vtes_minus_1
//                    + $item_inv_minus_1;
//                $item['reel_minus_1'] = $initial_minus_1 + $entree_minus_1
//                    - $sortie_minus_1 - $final_minus_1;
//                $item['ecart_minus_1'] = $item['theo_minus_1']
//                    - $item['reel_minus_1'];
//                $item['valorisation_minus_1'] = $item['ecart_minus_1']
//                    * (floatval($item['buying_cost_minus_1']) / intval(
//                            $item['inventory_qty_minus_1']
//                        ));
//
//                $ventes_minus_2 = floatval($item['ventes_minus_2']);
//                $item_vtes_minus_2 = floatval($item['item_vtes_minus_2']);
//                $item_inv_minus_2 = floatval($item['item_inv_minus_2']);
//                $initial_minus_2 = floatval($item['initial_minus_2']);
//                $entree_minus_2 = floatval($item['entree_minus_2']);
//                $sortie_minus_2 = floatval($item['sortie_minus_2']);
//                $final_minus_2 = floatval($item['final_minus_2']);
//                $item['theo_minus_2'] = $ventes_minus_2 + $item_vtes_minus_2
//                    + $item_inv_minus_2;
//                $item['reel_minus_2'] = $initial_minus_2 + $entree_minus_2
//                    - $sortie_minus_2 - $final_minus_2;
//                $item['ecart_minus_2'] = $item['theo_minus_2']
//                    - $item['reel_minus_2'];
//                $item['valorisation_minus_2'] = $item['ecart_minus_2']
//                    * (floatval($item['buying_cost_minus_2']) / intval(
//                            $item['inventory_qty_minus_2']
//                        ));
//
//                $ventes_minus_3 = floatval($item['ventes_minus_3']);
//                $item_vtes_minus_3 = floatval($item['item_vtes_minus_3']);
//                $item_inv_minus_3 = floatval($item['item_inv_minus_3']);
//                $initial_minus_3 = floatval($item['initial_minus_3']);
//                $entree_minus_3 = floatval($item['entree_minus_3']);
//                $sortie_minus_3 = floatval($item['sortie_minus_3']);
//                $final_minus_3 = floatval($item['final_minus_3']);
//                $item['theo_minus_3'] = $ventes_minus_3 + $item_vtes_minus_3
//                    + $item_inv_minus_3;
//                $item['reel_minus_3'] = $initial_minus_3 + $entree_minus_3
//                    - $sortie_minus_3 - $final_minus_3;
//                $item['ecart_minus_3'] = $item['theo_minus_3']
//                    - $item['reel_minus_3'];
//                $item['valorisation_minus_3'] = $item['ecart_minus_3']
//                    * (floatval($item['buying_cost_minus_3']) / intval(
//                            $item['inventory_qty_minus_3']
//                        ));
//
//            }


            $treshhold = floatval($filter['threshold']);
            if ($filter['selection'] === "error") {
                if (abs($item['valorisation']) < $treshhold) {
                    unset($data[$key]);
                    continue;
                }
            }

            $categoryName = $item['category_name'];
            if (array_key_exists($categoryName, $lineByCategory["data"])) {
                $lineByCategory["data"][$categoryName]["data"][] = $item;
                $lineByCategory["data"][$categoryName]["aggregations"]['final_value'] += floatval(
                    $item['final_valorization']
                );
                $lineByCategory["data"][$categoryName]["aggregations"]['valorisation'] += floatval(
                    $item['valorisation']
                );
                //Valo by category for the three previous week
//                if ($result['isCalendarWeek'] && $flag== '1') {
//                    $lineByCategory["data"][$categoryName]["aggregations"]['valorisation_minus_1'] += floatval(
//                        $item['valorisation_minus_1']
//                    );
//                    $lineByCategory["data"][$categoryName]["aggregations"]['valorisation_minus_2'] += floatval(
//                        $item['valorisation_minus_2']
//                    );
//                    $lineByCategory["data"][$categoryName]["aggregations"]['valorisation_minus_3'] += floatval(
//                        $item['valorisation_minus_3']
//                    );
//                }

                // total agregation
                if (floatval($item['ecart']) >= 0) {
                    $lineByCategory["aggregations"]["positive_ecart"] += floatval(
                        $item['valorisation']
                    );
                } else {

                    $lineByCategory["aggregations"]["negative_ecart"] += floatval(
                        $item['valorisation']
                    );
                }
                $lineByCategory["aggregations"]["final_value"] += floatval(
                    $item['final_valorization']
                );
                $lineByCategory["aggregations"]["initial_value"] += floatval(
                    $item['initial_valorization']
                );
                $lineByCategory["aggregations"]["valorisation"] += floatval(
                    $item['valorisation']
                );


                // total valo for the three previous week
//                if ($result['isCalendarWeek'] && $flag== '1') {
//                    $lineByCategory["aggregations"]["valorisation_minus_1"] += floatval(
//                        $item['valorisation_minus_1']
//                    );
//                    $lineByCategory["aggregations"]["valorisation_minus_2"] += floatval(
//                        $item['valorisation_minus_2']
//                    );
//                    $lineByCategory["aggregations"]["valorisation_minus_3"] += floatval(
//                        $item['valorisation_minus_3']
//                    );
//                }

                $lineByCategory["aggregations"]["gain_inf_seuil"] += 1;
                $lineByCategory["aggregations"]["perte_sup_seuil"] += 1;
                $lineByCategory["aggregations"]["without_error"] += 1;

                $lineByCategory["aggregations"]["total_pie_data"] += 1;
            }
        }
        $currentRestaurant = $this->em->getRepository(Restaurant::class)->find(
            $filter["currentRestaurantId"]
        );

        $result['missing_plus'] = $this->em->getRepository(Ticket::class)
            ->findPlusThatAreNotExistingInProductSoldTable(
                $filter,
                $currentRestaurant
            );

        $result['data'] = $lineByCategory;


        return $result;
    }

    public function generateStockControlExcelFile(
        $result,
        $logoPath
    )
    {
        $startDate =new \DateTime();
       // $endDate = $result["endDate"];
        $topHeaderColor = "CA9E67";
        $secondHeaderColor = "EDE2C9";
        $categoryNameColor = "FDC300";
        $colorOne = "ECECEC";
        $goodEcartColor = "90EE90";
        $failEcartColor = "FFB6C1";

        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(
            substr(
                $this->translator->trans('portion_control.report_labels.stocks'),
                0,
                30
            )
        );

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('portion_control.report_labels.stocks');
        $sheet->setCellValue('B5', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("B5"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment(
            $sheet->getCell("B5"),
            $alignmentV
        );
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
//        $sheet->mergeCells("B2:F2");
//        $content = $currentRestaurant->getCode() . ' '
//            . $currentRestaurant->getName();
//        $sheet->setCellValue('B2', $content);

        //FILTER ZONE
        // START DATE
        ExcelUtilities::setFont($sheet->getCell('B10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B10"), $colorOne);
//        $sheet->setCellValue(
//            'B10',
//            $this->translator->trans('keyw') . ":"
//        );
        $sheet->mergeCells("C10:D10");
        ExcelUtilities::setFont($sheet->getCell('C10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C10"), $colorOne);
        $sheet->setCellValue('C10', $startDate->format('d-m-Y'));
        // END DATE
//        ExcelUtilities::setFont($sheet->getCell('E10'), 11, true);
//        ExcelUtilities::setBackgroundColor($sheet->getCell("E10"), $colorOne);
//        $sheet->setCellValue('E10', $this->translator->trans('keyword.to') . ":");
//        $sheet->mergeCells("F10:G10");
//        ExcelUtilities::setFont($sheet->getCell('F10'), 11, true);
//        ExcelUtilities::setBackgroundColor($sheet->getCell("F10"), $colorOne);
//        $sheet->setCellValue('F10', $endDate->format('d-m-Y'));


        //CONTENT
        $startCell = 1;
        $startLine = 14;
        $sheet->getRowDimension($startLine)->setRowHeight(17);
        // top headers
        //Items
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 4) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.report_labels.items')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 4) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $topHeaderColor
        );
        $startCell += 5;
        //stocks
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 10) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.report_labels.stocks')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 4) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $topHeaderColor
        );
        // second headers
        $startLine++;
        $startCell = 1;
        //liste restautant
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 4) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('restaurant.list.name')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //description
//        $sheet->mergeCells(
//            $this->getNameFromNumber($startCell) . $startLine . ":"
//            . $this->getNameFromNumber($startCell + 3) . $startLine
//        );
//        $sheet->setCellValue(
//            $this->getNameFromNumber($startCell) . $startLine,
//            $this->translator->trans(
//                'portion_control.report_labels.description'
//            )
//        );
//        ExcelUtilities::setFont(
//            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
//            7,
//            true
//        );
//        ExcelUtilities::setCellAlignment(
//            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
//            $alignmentH
//        );
//        ExcelUtilities::setBorder(
//            $sheet->getStyle(
//                $this->getNameFromNumber($startCell) . $startLine . ":"
//                . $this->getNameFromNumber($startCell + 3) . $startLine
//            )
//        );
//        ExcelUtilities::setBackgroundColor(
//            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
//            $secondHeaderColor
//        );
        $startCell += 4;
        //final
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 4) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('restaurant.list.fin')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 5;
           //format
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 5) . $startLine
        );
//        $sheet->setCellValue(
//            $this->getNameFromNumber($startCell) . $startLine,
//            $this->translator->trans('portion_control.export_labels.quantite')
//        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('restaurant.list.format')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        //body

        foreach ($result["data"]["data"] as $categoryName => $row) {

            $startCell = 1;
            $startLine++;

            $sheet->getRowDimension($startLine)->setRowHeight(17);
            $sheet->mergeCells(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 15) . $startLine
            );


            $sheet->setCellValue(
                $this->getNameFromNumber($startCell) . $startLine,
                $categoryName
            );
            ExcelUtilities::setFont(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                7,
                true
            );
            ExcelUtilities::setBorder(
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 9) . $startLine
                )
            );
            ExcelUtilities::setBackgroundColor(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                $categoryNameColor
            );
            $initial = $startLine;
            if ($result["data"]["data"][$categoryName]['data']) {
                $startLine++;
            }

            //data
            foreach ($row["data"] as $item) {


                //restaurant code
                $sheet->mergeCells(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 4) . $startLine
                );
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    $item["restaurant"] . '(' . $item['code_restaurant'] . ')'
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getStyle(
                        $this->getNameFromNumber($startCell) . $startLine . ":"
                        . $this->getNameFromNumber($startCell + 4) . $startLine
                    )
                );
                $startCell++;
                //description
//                $sheet->mergeCells(
//                    $this->getNameFromNumber($startCell) . $startLine . ":"
//                    . $this->getNameFromNumber($startCell + 3) . $startLine
//                );
//                $sheet->setCellValue(
//                    $this->getNameFromNumber($startCell) . $startLine,
//                    $item["description"]
//                );
//                ExcelUtilities::setFont(
//                    $sheet->getCell(
//                        $this->getNameFromNumber($startCell) . $startLine
//                    ),
//                    7,
//                    false
//                );
//                ExcelUtilities::setBorder(
//                    $sheet->getStyle(
//                        $this->getNameFromNumber($startCell) . $startLine . ":"
//                        . $this->getNameFromNumber($startCell + 3) . $startLine
//                    )
//                );
                $startCell += 4;
                //final
                $sheet->mergeCells(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 4) . $startLine
                );
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["final"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getStyle(
                        $this->getNameFromNumber($startCell) . $startLine . ":"
                        . $this->getNameFromNumber($startCell + 4) . $startLine
                    )
                );

//--------  format
                $startCell += 5;
                //final
                $sheet->mergeCells(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 5) . $startLine
                );
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    $item["format"]
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getStyle(
                        $this->getNameFromNumber($startCell) . $startLine . ":"
                        . $this->getNameFromNumber($startCell + 4) . $startLine
                    )
                );
     //------------

                $startCell = 1;
                if (count($result["data"]["data"][$categoryName]['data']) != ($startLine - $initial)) {
                    $startLine++;
                }
                $sheet->getRowDimension($startLine)->setRowHeight(17);
            }
        }
        $filename = "Rapport_stock_items_" . date('dmY_His') . ".xls";
        // create the writer
        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        $response->headers->set(
            'Content-Type',
            'text/vnd.ms-excel; charset=utf-8'
        );
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }


    public function generateProtionControlExcelFile(
        $result,
        Restaurant $currentRestaurant,
        $logoPath
    )
    {
        //$result["isCalendarWeek"] The flag whether it would be generated for the previous three Weeks

        $startDate = $result["startDate"];
        $endDate = $result["endDate"];
        $topHeaderColor = "CA9E67";
        $secondHeaderColor = "EDE2C9";
        $categoryNameColor = "FDC300";
        $colorOne = "ECECEC";
        $goodEcartColor = "90EE90";
        $failEcartColor = "FFB6C1";

        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(
            substr(
                $this->translator->trans('portion_control.tille_file'),
                0,
                30
            )
        );

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('portion_control.tille_file');
        $sheet->setCellValue('B5', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("B5"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment(
            $sheet->getCell("B5"),
            $alignmentV
        );
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
        $content = $currentRestaurant->getCode() . ' '
            . $currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);

        //FILTER ZONE
        // START DATE
        ExcelUtilities::setFont($sheet->getCell('B10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B10"), $colorOne);
        $sheet->setCellValue(
            'B10',
            $this->translator->trans('keyword.from') . ":"
        );
        $sheet->mergeCells("C10:D10");
        ExcelUtilities::setFont($sheet->getCell('C10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C10"), $colorOne);
        $sheet->setCellValue('C10', $startDate->format('d-m-Y'));
        // END DATE
        ExcelUtilities::setFont($sheet->getCell('E10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E10"), $colorOne);
        $sheet->setCellValue('E10', $this->translator->trans('keyword.to') . ":");
        $sheet->mergeCells("F10:G10");
        ExcelUtilities::setFont($sheet->getCell('F10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F10"), $colorOne);
        $sheet->setCellValue('F10', $endDate->format('d-m-Y'));


        //CONTENT
        $startCell = 1;
        $startLine = 14;
        $sheet->getRowDimension($startLine)->setRowHeight(17);
        // top headers
        //Items
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 7) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.report_labels.items')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 7) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $topHeaderColor
        );
        $startCell += 8;
        //stocks
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 4) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.report_labels.stocks')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 4) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $topHeaderColor
        );
        $startCell += 5;
        //sales
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.report_labels.ventes')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $topHeaderColor
        );
        $startCell += 1;
        //loss
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.report_labels.pertes')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $topHeaderColor
        );
        $startCell += 2;
        //consommations
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.report_labels.consommations'
            )
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $topHeaderColor
        );
        $startCell += 2;
        //ecart
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.report_labels.ecart')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $topHeaderColor
        );

        // second headers
        $startLine++;
        $startCell = 1;
        //code
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.report_labels.code')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //description
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 3) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.report_labels.description'
            )
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 3) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 4;
        //format
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 2) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.report_labels.format')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 2) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 3;
        //initial
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.export_labels.initial')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //entree
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.export_labels.entree')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //sortie
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.export_labels.sortie')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //final
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.export_labels.final')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //valeur finale
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.export_labels.valeur_final'
            )
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //ventes
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.export_labels.ventes')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //items vtes
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.export_labels.item_vtes')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //items inv
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.export_labels.item_inv')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //theo
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.report_labels.theo')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //reel
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.report_labels.reel')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //ecart
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.export_labels.ecart')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //valo
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.export_labels.valorisation'
            )
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );


        //body

        foreach ($result["data"]["data"] as $categoryName => $row) {
            $startCell = 1;
            $startLine++;
            $sheet->getRowDimension($startLine)->setRowHeight(17);
            //category name row
        try {
            if ($result["isCalendarWeek"]) {
                $sheet->mergeCells(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 27) . $startLine
                );
            } else {
                $sheet->mergeCells(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 19) . $startLine
                );

            }
        } catch (\Exception $e) {}
            $sheet->setCellValue(
                $this->getNameFromNumber($startCell) . $startLine,
                $categoryName
            );
            ExcelUtilities::setFont(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                7,
                true
            );
            ExcelUtilities::setBorder(
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 19) . $startLine
                )
            );
            ExcelUtilities::setBackgroundColor(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                $categoryNameColor
            );
            $startLine++;
            //data
            foreach ($row["data"] as $item) {
                //code
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    $item["code"]
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //description
                $sheet->mergeCells(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 3) . $startLine
                );
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    $item["description"]
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setBorder(
                    $sheet->getStyle(
                        $this->getNameFromNumber($startCell) . $startLine . ":"
                        . $this->getNameFromNumber($startCell + 3) . $startLine
                    )
                );
                $startCell += 4;
                //format
                $sheet->mergeCells(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 2) . $startLine
                );
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    $this->translator->trans($item["format"])
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setBorder(
                    $sheet->getStyle(
                        $this->getNameFromNumber($startCell) . $startLine . ":"
                        . $this->getNameFromNumber($startCell + 2) . $startLine
                    )
                );
                $startCell += 3;
                //initial
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["initial"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //entree
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["entree"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //sortie
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["sortie"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //final
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["final"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //valeur_final
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["valeur_final"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //ventes
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["ventes"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //item_vtes
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["item_vtes"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //item_inv
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["item_inv"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //theo
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["theo"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //reel
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["reel"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //ecart
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["ecart"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );

                $startCell++;
                //valorisation
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["valorisation"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );


                $startCell = 1;
                $startLine++;
                $sheet->getRowDimension($startLine)->setRowHeight(17);
            }
            //total line
            //total cell;
            $sheet->setCellValue(
                $this->getNameFromNumber($startCell) . $startLine,
                $this->translator->trans('portion_control.label.total')
            );
            ExcelUtilities::setFont(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                7,
                false
            );
            ExcelUtilities::setCellAlignment(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                $alignmentH
            );
            ExcelUtilities::setBorder(
                $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
            );
            $startCell += 1;
            //empty cells
            $sheet->mergeCells(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 3) . $startLine
            );
            ExcelUtilities::setBorder(
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 3) . $startLine
                )
            );
            $startCell += 4;
            $sheet->mergeCells(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 2) . $startLine
            );
            ExcelUtilities::setBorder(
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 2) . $startLine
                )
            );
            $startCell += 3;
            ExcelUtilities::setBorder(
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 3) . $startLine
                )
            );
            $startCell += 4;
            //total final value
            $sheet->setCellValue(
                $this->getNameFromNumber($startCell) . $startLine,
                number_format($row["aggregations"]["final_value"], 2, '.', '')
            );
            $sheet->getStyle($this->getNameFromNumber($startCell) . $startLine)
                ->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
            ExcelUtilities::setFont(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                7,
                false
            );
            ExcelUtilities::setCellAlignment(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                $alignmentH
            );
            ExcelUtilities::setBorder(
                $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
            );
            $startCell += 1;
            //empty cells
            ExcelUtilities::setBorder(
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 5) . $startLine
                )
            );
            $startCell += 6;
            //total valorisation
            $sheet->setCellValue(
                $this->getNameFromNumber($startCell) . $startLine,
                number_format($row["aggregations"]["valorisation"], 2, '.', '')
            );
            $sheet->getStyle($this->getNameFromNumber($startCell) . $startLine)
                ->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
            ExcelUtilities::setFont(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                7,
                false
            );
            ExcelUtilities::setCellAlignment(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                $alignmentH
            );
            ExcelUtilities::setBorder(
                $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
            );


        }

        $startLine++;
        $sheet->getRowDimension($startLine)->setRowHeight(17);
        $startCell = 1;
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 7) . ($startLine + 2)
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 7) . ($startLine + 2)
            )
        );
        $startCell += 8;
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 3) . $startLine
            )
        );
        $startCell += 4;
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            number_format(
                $result["data"]["aggregations"]["final_value"],
                2,
                '.',
                ''
            )
        );
        $sheet->getStyle($this->getNameFromNumber($startCell) . $startLine)
            ->getNumberFormat()->setFormatCode(
                \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
            );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            false
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        $startCell += 1;
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 5) . $startLine
            )
        );
        $startCell += 6;
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            number_format(
                $result["data"]["aggregations"]["valorisation"],
                2,
                '.',
                ''
            )
        );
        $sheet->getStyle($this->getNameFromNumber($startCell) . $startLine)
            ->getNumberFormat()->setFormatCode(
                \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
            );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            false
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );


        $startCell = 9;
        $startLine++;
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.report_labels.ecart_positif'
            ) . " (€)"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );
        $startCell += 2;
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            number_format(
                $result["data"]["aggregations"]["positive_ecart"],
                2,
                '.',
                ''
            )
        );
        $sheet->getStyle($this->getNameFromNumber($startCell) . $startLine)
            ->getNumberFormat()->setFormatCode(
                \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
            );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            false
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        $startCell += 2;
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 7) . $startLine
            )
        );
        $startCell = 9;
        $startLine++;
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.report_labels.ecartnegatif'
            ) . " (€)"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );
        $startCell += 2;
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            number_format(
                $result["data"]["aggregations"]["negative_ecart"],
                2,
                '.',
                ''
            )
        );
        $sheet->getStyle($this->getNameFromNumber($startCell) . $startLine)
            ->getNumberFormat()->setFormatCode(
                \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
            );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            false
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );

        $startCell += 2;
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 7) . $startLine
            )
        );

        $filename = "Rapport_portion_controle_" . date('dmY_His') . ".xls";
        // create the writer
        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        $response->headers->set(
            'Content-Type',
            'text/vnd.ms-excel; charset=utf-8'
        );
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    public function generateInventoryLossReport($filter)
    {

        $divisionsRaw = $this->em->getRepository('Merchandise:LossLine')
            ->getFiltredLossLine($filter, false, false);

        $rawProduct = array();
        $i = 0;
        foreach ($divisionsRaw as $raw) {
            foreach ($divisionsRaw as $secondRawKey => $secondRaw) {
                if ($raw['productid'] == $secondRaw['productid']) {
                    $rawProduct[$i]['productId'] = $raw['productid'];
                    $rawProduct[$i]['unitInventory'] = $raw['unitinventory'];
                    $rawProduct[$i]['productName'] = $raw['productname'];
                    $rawProduct[$i][$secondRaw['entryday']]['total']
                        = $secondRaw['total'];
                    $rawProduct[$i][$secondRaw['entryday']]['lossVal']
                        = $secondRaw['lossprice'];
                    unset($divisionsRaw[$secondRawKey]);
                }
            }

            $i++;
        }

        foreach ($rawProduct as &$raw) {
            $raw['totalLoss'] = 0;
            $raw['totalLossVal'] = 0;
            for ($i = 0; $i <= 6; $i++) {
                if (!isset($raw[$i])) {
                    $raw[$i]['total'] = 0;
                    $raw[$i]['lossVal'] = 0;
                }
                $raw['totalLoss'] += $raw[$i]['total'];
                $raw['totalLossVal'] += $raw[$i]['lossVal'];
            }
        }

        $avgColumn = array();
        $totalColumn = array();
        for ($i = 0; $i < 7; $i++) {
            $avgColumn[$i] = 0;
            $totalColumn[$i] = 0;
            $avgColumn['7'] = 0;
            $totalColumn['7'] = 0;
            foreach ($rawProduct as &$raw) {
                $totalColumn[$i] += $raw[$i]['lossVal'];
                $totalColumn['7'] += $raw['totalLossVal'];
            }
            if (count($rawProduct) > 0) {
                $avgColumn[$i] = $totalColumn[$i] / (count($rawProduct));
                $avgColumn['7'] = $totalColumn['7'] / (count($rawProduct));
            }
        }
        $result['0'] = $rawProduct;
        $result['1'] = $avgColumn;
        $result['2'] = $totalColumn;

        $result = $this->getRevenue($result, $filter);

        return $result;
    }

    public function generateSoldLossReport($filter)
    {

        $divisionsRaw = $this->em->getRepository('Merchandise:LossLine')
            ->getFiltredLossLineSold($filter, false, false);
        $rawProduct = array();
        $i = 0;
        foreach ($divisionsRaw as $raw) {
            foreach ($divisionsRaw as $secondRawKey => $secondRaw) {
                if ($raw['productid'] == $secondRaw['productid']) {
                    $rawProduct[$i]['productId'] = $raw['productid'];
                    $rawProduct[$i]['productName'] = $raw['productname'];
                    $rawProduct[$i][$secondRaw['entryday']]['total']
                        = $secondRaw['total'];
                    $rawProduct[$i][$secondRaw['entryday']]['lossVal']
                        = ($secondRaw['lossprice'] !== null)
                        ? $secondRaw['lossprice'] : 0;
                    unset($divisionsRaw[$secondRawKey]);
                }
            }

            $i++;
        }

        foreach ($rawProduct as &$raw) {
            $raw['totalLoss'] = 0;
            $raw['totalLossVal'] = 0;
            for ($i = 0; $i <= 6; $i++) {
                if (!isset($raw[$i])) {
                    $raw[$i]['total'] = 0;
                    $raw[$i]['lossVal'] = 0;
                }
                $raw['totalLoss'] += $raw[$i]['total'];
                $raw['totalLossVal'] += $raw[$i]['lossVal'];
            }
        }


        $avgColumn = array();
        $totalColumn = array();

        for ($i = 0; $i < 7; $i++) {
            $avgColumn[$i] = 0;
            $totalColumn[$i] = 0;
            $avgColumn['7'] = 0;
            $totalColumn['7'] = 0;
            foreach ($rawProduct as &$raw) {
                $totalColumn[$i] += $raw[$i]['lossVal'];
                $totalColumn['7'] += $raw['totalLossVal'];
            }
            if (count($rawProduct) > 0) {
                $avgColumn[$i] = $totalColumn[$i] / (count($rawProduct));
                $avgColumn['7'] = $totalColumn['7'] / (count($rawProduct));
            }
        }

        $result['0'] = $rawProduct;
        $result['1'] = $avgColumn;
        $result['2'] = $totalColumn;

        $result = $this->getRevenue($result, $filter);

        return $result;
    }

    public function getRevenue(&$result, $filter)
    {
        $sqlQueryFile = $this->sqlQueriesDir . "/ca_net_ht.sql";

        if (!file_exists($sqlQueryFile)) {
            throw new \Exception("FILE CA NET HT DOESN'T EXSIT");
        }
        $sql = file_get_contents($sqlQueryFile);

        $D1 = $filter['beginDate'];
        $D2 = $filter['endDate'];
        //        $discountLabelType = TicketPayment::DISCOUNT_TYPE;
        //        $brLabelType = TicketPayment::MEAL_TICKET;
        //        $canceled = -1;
        //        $abandonment = 5;

        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('origin_restaurant_id', $filter['currentRestaurantId']);
        //        $stm->bindParam('discount_label_type',$discountLabelType);
        //        $stm->bindParam('bon_repas_label_type',$brLabelType);
        //        $stm->bindParam('canceled', $canceled);
        //        $stm->bindParam('abandonment', $abandonment);

        $stm->execute();
        $amountRevenue = $stm->fetchAll();

        $result['3'] = array_fill(0, 7, '0');
        $result['3']['total'] = 0;
        $result['4'] = array_fill(0, 7, '0');

        foreach ($amountRevenue as $amount) {
            $result['3'][$amount['entryday']] = $amount['totalht'];
            if ($result['3'][$amount['entryday']] != 0) {
                $result['4'][$amount['entryday']]
                    = $result['2'][$amount['entryday']]
                    / $result['3'][$amount['entryday']] * 100;
            } else {
                $result['4'][$amount['entryday']] = 0;
            }
            $result['3']['total'] += $result['3'][$amount['entryday']];
        }
        if ($result['3']['total' != 0]) {
            $result['4']['total'] = $result['2']['7'] / $result['3']['total']
                * 100;
        } else {
            $result['4']['total'] = 0;
        }

        return $result;
    }

    public function serializeLossReportResult($result, $nbrDayWeek)
    {
        $serializedResult = [];
        $days = [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
        ];
        $serializedResult['0']['0'] = '';
        $serializedResult['1']['0'] = '';

        if (isset($result['0']['0']['unitInventory'])) {
            $serializedResult['0']['1'] = '';
            $serializedResult['1']['1'] = '';
            $serializedResult['2']['0'] = $this->translator->trans(
                'label.inventory_item'
            );
            $serializedResult['2']['1'] = $this->translator->trans(
                'item.label.unit_inventory'
            );
        } else {
            $serializedResult['2']['1'] = $this->translator->trans(
                'label.sold_item'
            );
        }

        for ($i = 0; $i <= 7; $i++) {
            $serializedResult['2'][] = $this->translator->trans('label.qty');
            $serializedResult['2'][] = $this->translator->trans('label.value');
        }

        foreach ($days as $day) {
            $serializedResult['0'][] = $this->translator->trans(
                strtolower('days.' . $day)
            );
            $serializedResult['0'][] = $nbrDayWeek[$day];
        }
        $serializedResult['0'][] = $this->translator->trans('keyword.total');
        $serializedResult['0'][] = $nbrDayWeek['total'];
        for ($i = 1; $i <= 6; $i++) {
            $serializedResult['1'][] = 'CA NET HT';
            $serializedResult['1'][] = $result['3'][$i];
        }
        $serializedResult['1'][] = 'CA NET HT';
        $serializedResult['1'][] = $result['3']['0'];
        $serializedResult['1'][] = 'CA NET HT';
        $serializedResult['1'][] = $result['3']['total'];

        foreach ($result['0'] as $line) {
            $tmp = [];
            $tmp[] = $line['productName'];
            if (isset($line['unitInventory'])) {
                $tmp[] = $this->translator->trans($line['unitInventory']);
            }
            for ($i = 1; $i <= 6; $i++) {
                $tmp[] = $line[$i]['total'];
                $tmp[] = number_format($line[$i]['lossVal'], 2, '.', '');
            }
            $tmp[] = $line['0']['total'];
            $tmp[] = $line['0']['lossVal'];
            $tmp[] = $line['totalLoss'];
            $tmp[] = number_format($line['totalLossVal'], 2, '.', '');

            $serializedResult[] = $tmp;
        }
        $actualLength = count($serializedResult);
        if (isset($result['0']['0']['unitInventory'])) {
            $serializedResult[$actualLength][] = '';
            $serializedResult[$actualLength + 1][] = '';
        }
        $serializedResult[$actualLength][] = $this->translator->trans(
            'label.total_period'
        );
        $serializedResult[$actualLength + 1][] = $this->translator->trans(
            'label.average'
        );
        for ($i = 1; $i <= 6; $i++) {
            $serializedResult[$actualLength][] = number_format(
                $result['4'][$i],
                2,
                '.',
                ''
            );
            $serializedResult[$actualLength][] = number_format(
                $result['2'][$i],
                2,
                '.',
                ''
            );
            $serializedResult[$actualLength + 1][] = '';
            $serializedResult[$actualLength + 1][] = number_format(
                $result['1'][$i],
                2,
                '.',
                ''
            );
        }
        $serializedResult[$actualLength][] = number_format(
            $result['4']['0'],
            2,
            '.',
            ''
        );
        $serializedResult[$actualLength][] = number_format(
            $result['2']['0'],
            2,
            '.',
            ''
        );
        $serializedResult[$actualLength + 1][] = '';
        $serializedResult[$actualLength + 1][] = number_format(
            $result['1']['0'],
            2,
            '.',
            ''
        );
        $serializedResult[$actualLength][] = number_format(
            $result['4']['total'],
            2,
            '.',
            ''
        );
        $serializedResult[$actualLength][] = number_format(
            $result['2']['7'],
            2,
            '.',
            ''
        );
        $serializedResult[$actualLength + 1][] = '';
        $serializedResult[$actualLength + 1][] = number_format(
            $result['1']['7'],
            2,
            '.',
            ''
        );

        return $serializedResult;
    }


    //NEW
    public function generateInventoryLossExcelFile(
        $result,
        $avg,
        $total,
        $nbrDayWeek,
        $financialRevenue,
        $proportion,
        $filter,
        Restaurant $currentRestaurant,
        $logoPath
    )
    {
        $colorOne = "ECECEC";
        $colorTwo = "EDE2C9";
        $colorThree = "F4DF42";
        $colorFour = "FCEF01";
        $colorFive = "FFFCC0";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(
            substr(
                $this->translator->trans('report.loss.inventory_item_title'),
                0,
                30
            )
        );


        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('report.loss.inventory_item_title');

        $sheet->setCellValue('B5', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("B5"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment(
            $sheet->getCell("B5"),
            $alignmentV
        );
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
        $content = $currentRestaurant->getCode() . ' '
            . $currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);


        //FILTER ZONE
        // START DATE
        $sheet->mergeCells("A10:B10");
        ExcelUtilities::setFont($sheet->getCell('A10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A10"), $colorOne);
        $sheet->setCellValue(
            'A10',
            $this->translator->trans('keyword.from') . ":"
        );
        $sheet->mergeCells("C10:D10");
        ExcelUtilities::setFont($sheet->getCell('C10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C10"), $colorOne);
        $sheet->setCellValue('C10', $filter['beginDate']);


        // END DATE
        $sheet->mergeCells("A11:B11");
        ExcelUtilities::setFont($sheet->getCell('A11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A11"), $colorOne);
        $sheet->setCellValue('A11', $this->translator->trans('keyword.to') . ":");
        $sheet->mergeCells("C11:D11");
        ExcelUtilities::setFont($sheet->getCell('C11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C11"), $colorOne);
        $sheet->setCellValue('C11', $filter['endDate']);


        // PRODUCT NAME
        $sheet->mergeCells("F10:G10");
        ExcelUtilities::setFont($sheet->getCell('F10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F10"), $colorOne);
        $sheet->setCellValue('F10', $this->translator->trans('label.name') . ":");
        $sheet->mergeCells("H10:I10");
        ExcelUtilities::setFont($sheet->getCell('H10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H10"), $colorOne);
        if ($filter['productName'] != "") {
            $sheet->setCellValue('H10', $filter['productName']);
        } else {
            $sheet->setCellValue('H10', "--");
        }

        // PRODUCT CODE
        $sheet->mergeCells("F11:G11");
        ExcelUtilities::setFont($sheet->getCell('F11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F11"), $colorOne);
        $sheet->setCellValue('F11', $this->translator->trans('label.code') . ":");
        $sheet->mergeCells("H11:I11");
        ExcelUtilities::setFont($sheet->getCell('H11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H11"), $colorOne);
        if ($filter['productCode'] != "") {
            $sheet->setCellValue('H11', $filter['productCode']);
        } else {
            $sheet->setCellValue('H11', "--");
        }

        // PRODUCT CATEGORIES
        $sheet->mergeCells("A13:B13");
        ExcelUtilities::setFont($sheet->getCell('A13'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A13"), $colorOne);
        $sheet->setCellValue(
            'A13',
            $this->translator->trans('keyword.categories') . ":"
        );
        if (isset($filter['categories'])) {
            $categoriesList = "";
            foreach ($filter['categories'] as $category) {
                $categoriesList .= $this->em->getRepository(
                        'Merchandise:ProductCategories'
                    )->findOneBy(
                        array("id" => $category)
                    )->getName() . ", ";
            }
            $sheet->mergeCells("C13:R13");
            ExcelUtilities::setFont($sheet->getCell('H11'), 11, true);
            ExcelUtilities::setBackgroundColor(
                $sheet->getCell("C13"),
                $colorOne
            );
            $sheet->setCellValue('C13', $categoriesList);
        } else {
            $sheet->mergeCells("C13:D13");
            ExcelUtilities::setFont($sheet->getCell('C13'), 11, true);
            ExcelUtilities::setBackgroundColor(
                $sheet->getCell("C13"),
                $colorOne
            );
            $sheet->setCellValue('C13', $this->translator->trans('label.all'));
        }

        //TABLE
        //  TABLE HEADER
        //    BLANC PART
        $sheet->mergeCells("A17:D18");


        //    MONDY
        $sheet->mergeCells("E17:F17");
        ExcelUtilities::setFont($sheet->getCell('E17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E17"), $colorThree);
        $sheet->setCellValue('E17', $this->translator->trans('days.monday'));
        ExcelUtilities::setBorder($sheet->getCell('E17'));
        ExcelUtilities::setBorder($sheet->getCell('F17'));
        //    MONDAY NUMBER
        ExcelUtilities::setFont($sheet->getCell('G17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G17"), $colorThree);
        $sheet->setCellValue('G17', $nbrDayWeek['Monday']);
        ExcelUtilities::setBorder($sheet->getCell('G17'));

        //    TUESDAY
        $sheet->mergeCells("H17:I17");
        ExcelUtilities::setFont($sheet->getCell('H17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H17"), $colorThree);
        $sheet->setCellValue('H17', $this->translator->trans('days.tuesday'));
        ExcelUtilities::setBorder($sheet->getCell('H17'));
        ExcelUtilities::setBorder($sheet->getCell('I17'));
        //    TUESDAY NUMBER
        ExcelUtilities::setFont($sheet->getCell('J17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J17"), $colorThree);
        $sheet->setCellValue('J17', $nbrDayWeek['Tuesday']);
        ExcelUtilities::setBorder($sheet->getCell('J17'));

        //    WEDNESDAY
        $sheet->mergeCells("K17:L17");
        ExcelUtilities::setFont($sheet->getCell('K17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K17"), $colorThree);
        $sheet->setCellValue('K17', $this->translator->trans('days.wednesday'));
        ExcelUtilities::setBorder($sheet->getCell('K17'));
        ExcelUtilities::setBorder($sheet->getCell('L17'));
        //    WEDNESDAY NUMBER
        ExcelUtilities::setFont($sheet->getCell('M17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M17"), $colorThree);
        $sheet->setCellValue('M17', $nbrDayWeek['Wednesday']);
        ExcelUtilities::setBorder($sheet->getCell('M17'));

        //    THURSDAY
        $sheet->mergeCells("N17:O17");
        ExcelUtilities::setFont($sheet->getCell('N17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N17"), $colorThree);
        $sheet->setCellValue('N17', $this->translator->trans('days.thursday'));
        ExcelUtilities::setBorder($sheet->getCell('N17'));
        ExcelUtilities::setBorder($sheet->getCell('O17'));
        //    THURSDAY NUMBER
        ExcelUtilities::setFont($sheet->getCell('P17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P17"), $colorThree);
        $sheet->setCellValue('P17', $nbrDayWeek['Thursday']);
        ExcelUtilities::setBorder($sheet->getCell('P17'));

        //    FRIDAY
        $sheet->mergeCells("Q17:R17");
        ExcelUtilities::setFont($sheet->getCell('Q17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q17"), $colorThree);
        $sheet->setCellValue('Q17', $this->translator->trans('days.friday'));
        ExcelUtilities::setBorder($sheet->getCell('Q17'));
        ExcelUtilities::setBorder($sheet->getCell('R17'));
        //    FRIDAY NUMBER
        ExcelUtilities::setFont($sheet->getCell('S17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("S17"), $colorThree);
        $sheet->setCellValue('S17', $nbrDayWeek['Friday']);
        ExcelUtilities::setBorder($sheet->getCell('S17'));

        //    SATURDAY
        $sheet->mergeCells("T17:U17");
        ExcelUtilities::setFont($sheet->getCell('T17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("T17"), $colorThree);
        $sheet->setCellValue('T17', $this->translator->trans('days.saturday'));
        ExcelUtilities::setBorder($sheet->getCell('T17'));
        ExcelUtilities::setBorder($sheet->getCell('U17'));
        //    SATURDAY NUMBER
        ExcelUtilities::setFont($sheet->getCell('V17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("V17"), $colorThree);
        $sheet->setCellValue('V17', $nbrDayWeek['Saturday']);
        ExcelUtilities::setBorder($sheet->getCell('V17'));

        //    SUNDAY
        $sheet->mergeCells("W17:X17");
        ExcelUtilities::setFont($sheet->getCell('W17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("W17"), $colorThree);
        $sheet->setCellValue('W17', $this->translator->trans('days.sunday'));
        ExcelUtilities::setBorder($sheet->getCell('W17'));
        ExcelUtilities::setBorder($sheet->getCell('X17'));
        //    SUNDAY NUMBER
        ExcelUtilities::setFont($sheet->getCell('Y17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Y17"), $colorThree);
        $sheet->setCellValue('Y17', $nbrDayWeek['Sunday']);
        ExcelUtilities::setBorder($sheet->getCell('Y17'));

        //    TOTAL
        $sheet->mergeCells("Z17:AA17");
        ExcelUtilities::setFont($sheet->getCell('Z17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Z17"), $colorThree);
        $sheet->setCellValue('Z17', $this->translator->trans('keyword.total'));
        ExcelUtilities::setBorder($sheet->getCell('Z17'));
        ExcelUtilities::setBorder($sheet->getCell('AA17'));
        //    TOTAL NUMBER
        ExcelUtilities::setFont($sheet->getCell('AB17'), 11, true);
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell("AB17"),
            $colorThree
        );
        $sheet->setCellValue('AB17', $nbrDayWeek['total']);
        ExcelUtilities::setBorder($sheet->getCell('AB17'));


        //    MONDAY CA NET
        $sheet->mergeCells("E18:F18");
        ExcelUtilities::setFont($sheet->getCell('E18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E18"), $colorTwo);
        $sheet->setCellValue('E18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('E18'));
        ExcelUtilities::setBorder($sheet->getCell('F18'));
        //    MONDAY FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('G18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G18"), $colorTwo);
        $sheet->setCellValue('G18', round($financialRevenue[1], 2));
        ExcelUtilities::setBorder($sheet->getCell('G18'));

        //    TUESDAY CA NET
        $sheet->mergeCells("H18:I18");
        ExcelUtilities::setFont($sheet->getCell('H18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H18"), $colorTwo);
        $sheet->setCellValue('H18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('H18'));
        ExcelUtilities::setBorder($sheet->getCell('I18'));
        //    TUESDAY FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('J18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J18"), $colorTwo);
        $sheet->setCellValue('J18', round($financialRevenue[2], 2));
        ExcelUtilities::setBorder($sheet->getCell('J18'));

        //    WEDNESDAY CA NET
        $sheet->mergeCells("K18:L18");
        ExcelUtilities::setFont($sheet->getCell('K18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K18"), $colorTwo);
        $sheet->setCellValue('K18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('K18'));
        ExcelUtilities::setBorder($sheet->getCell('L18'));
        //    WEDNESDAY FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('M18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M18"), $colorTwo);
        $sheet->setCellValue('M18', round($financialRevenue[3], 2));
        ExcelUtilities::setBorder($sheet->getCell('M18'));

        //    THURSDAY CA NET
        $sheet->mergeCells("N18:O18");
        ExcelUtilities::setFont($sheet->getCell('N18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N18"), $colorTwo);
        $sheet->setCellValue('N18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('N18'));
        ExcelUtilities::setBorder($sheet->getCell('O18'));
        //    THURSDAY FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('P18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P18"), $colorTwo);
        $sheet->setCellValue('P18', round($financialRevenue[4], 2));
        ExcelUtilities::setBorder($sheet->getCell('P18'));

        //    FRIDAY CA NET
        $sheet->mergeCells("Q18:R18");
        ExcelUtilities::setFont($sheet->getCell('Q18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q18"), $colorTwo);
        $sheet->setCellValue('Q18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('Q18'));
        ExcelUtilities::setBorder($sheet->getCell('R18'));
        //    FRIDAY FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('S18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("S18"), $colorTwo);
        $sheet->setCellValue('S18', round($financialRevenue[5], 2));
        ExcelUtilities::setBorder($sheet->getCell('S18'));

        //    SATURDAY CA NET
        $sheet->mergeCells("T18:U18");
        ExcelUtilities::setFont($sheet->getCell('T18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("T18"), $colorTwo);
        $sheet->setCellValue('T18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('T18'));
        ExcelUtilities::setBorder($sheet->getCell('U18'));
        //    SATURDAY FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('V18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("V18"), $colorTwo);
        $sheet->setCellValue('V18', round($financialRevenue[6], 2));
        ExcelUtilities::setBorder($sheet->getCell('V18'));

        //    SUNDAY CA NET
        $sheet->mergeCells("W18:X18");
        ExcelUtilities::setFont($sheet->getCell('W18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("W18"), $colorTwo);
        $sheet->setCellValue('W18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('W18'));
        ExcelUtilities::setBorder($sheet->getCell('X18'));
        //    SUNDAY FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('Y18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Y18"), $colorTwo);
        $sheet->setCellValue('Y18', round($financialRevenue[0], 2));
        ExcelUtilities::setBorder($sheet->getCell('Y18'));

        //    TOTAL CA NET
        $sheet->mergeCells("Z18:AA18");
        ExcelUtilities::setFont($sheet->getCell('Z18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Z18"), $colorTwo);
        $sheet->setCellValue('Z18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('Z18'));
        ExcelUtilities::setBorder($sheet->getCell('AA18'));
        //    TOTAL FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('AB18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("AB18"), $colorTwo);
        $sheet->setCellValue('AB18', round($financialRevenue['total'], 2));
        ExcelUtilities::setBorder($sheet->getCell('AB18'));

        //    INVENTORY ITEMS
        $sheet->mergeCells("A19:B19");
        ExcelUtilities::setFont($sheet->getCell('A19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A19"), $colorOne);
        $sheet->setCellValue(
            'A19',
            $this->translator->trans('label.inventory_item')
        );
        ExcelUtilities::setBorder($sheet->getCell('A19'));
        ExcelUtilities::setBorder($sheet->getCell('B19'));
        //    UNIT INVENTORY
        $sheet->mergeCells("C19:D19");
        ExcelUtilities::setFont($sheet->getCell('C19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C19"), $colorOne);
        $sheet->setCellValue(
            'C19',
            $this->translator->trans('item.label.unit_inventory')
        );
        ExcelUtilities::setBorder($sheet->getCell('C19'));
        ExcelUtilities::setBorder($sheet->getCell('D19'));
        //    MONDAY QUANTITY
        $sheet->mergeCells("E19:F19");
        ExcelUtilities::setFont($sheet->getCell('E19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E19"), $colorThree);
        $sheet->setCellValue('E19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('E19'));
        ExcelUtilities::setBorder($sheet->getCell('F19'));
        //    MONDAY VALUE
        ExcelUtilities::setFont($sheet->getCell('G19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G19"), $colorThree);
        $sheet->setCellValue(
            'G19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('G19'));

        //    TUESDAY QUANTITY
        $sheet->mergeCells("H19:I19");
        ExcelUtilities::setFont($sheet->getCell('H19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H19"), $colorThree);
        $sheet->setCellValue('H19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('H19'));
        ExcelUtilities::setBorder($sheet->getCell('I19'));
        //    TUESDAY VALUE
        ExcelUtilities::setFont($sheet->getCell('J19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J19"), $colorThree);
        $sheet->setCellValue(
            'J19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('J19'));

        //    WEDNESDAY QUANTITY
        $sheet->mergeCells("K19:L19");
        ExcelUtilities::setFont($sheet->getCell('K19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K19"), $colorThree);
        $sheet->setCellValue('K19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('K19'));
        ExcelUtilities::setBorder($sheet->getCell('L19'));
        //    WEDNESDAY VALUE
        ExcelUtilities::setFont($sheet->getCell('M19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M19"), $colorThree);
        $sheet->setCellValue(
            'M19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('M19'));

        //    THURSDAY QUANTITY
        $sheet->mergeCells("N19:O19");
        ExcelUtilities::setFont($sheet->getCell('N19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N19"), $colorThree);
        $sheet->setCellValue('N19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('N19'));
        ExcelUtilities::setBorder($sheet->getCell('O19'));
        //    THURSDAY VALUE
        ExcelUtilities::setFont($sheet->getCell('P19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P19"), $colorThree);
        $sheet->setCellValue(
            'P19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('P19'));

        //    FRIDAY QUANTITY
        $sheet->mergeCells("Q19:R19");
        ExcelUtilities::setFont($sheet->getCell('Q19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q19"), $colorThree);
        $sheet->setCellValue('Q19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('Q19'));
        ExcelUtilities::setBorder($sheet->getCell('R19'));
        //    FRIDAY VALUE
        ExcelUtilities::setFont($sheet->getCell('S19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("S19"), $colorThree);
        $sheet->setCellValue(
            'S19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('S19'));

        //    SATURDAY QUANTITY
        $sheet->mergeCells("T19:U19");
        ExcelUtilities::setFont($sheet->getCell('T19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("T19"), $colorThree);
        $sheet->setCellValue('T19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('T19'));
        ExcelUtilities::setBorder($sheet->getCell('U19'));
        //    SATURDAY VALUE
        ExcelUtilities::setFont($sheet->getCell('V19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("V19"), $colorThree);
        $sheet->setCellValue(
            'V19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('V19'));

        //    SUNDAY QUANTITY
        $sheet->mergeCells("W19:X19");
        ExcelUtilities::setFont($sheet->getCell('W19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("W19"), $colorThree);
        $sheet->setCellValue('W19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('W19'));
        ExcelUtilities::setBorder($sheet->getCell('X19'));
        //    SUNDAY VALUE
        ExcelUtilities::setFont($sheet->getCell('Y19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Y19"), $colorThree);
        $sheet->setCellValue(
            'Y19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('Y19'));

        //    TOTAL QUANTITY
        $sheet->mergeCells("Z19:AA19");
        ExcelUtilities::setFont($sheet->getCell('Z19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Z19"), $colorThree);
        $sheet->setCellValue('Z19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('Z19'));
        ExcelUtilities::setBorder($sheet->getCell('AA19'));
        //    TOTAL VALUE
        ExcelUtilities::setFont($sheet->getCell('AB19'), 11, true);
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell("AB19"),
            $colorThree
        );
        $sheet->setCellValue(
            'AB19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('AB19'));

        // TABLE CONTENT
        $i = 20;
        foreach ($result as $line) {
            //  PRODUCT NAME
            $sheet->mergeCells("A" . $i . ":B" . $i);
            ExcelUtilities::setFont($sheet->getCell('A' . $i), 11, true);
            $sheet->setCellValue('A' . $i, $line['productName']);
            ExcelUtilities::setBorder($sheet->getCell('A' . $i));
            ExcelUtilities::setBorder($sheet->getCell('B' . $i));

            //  PRODUCT INVENTORY UNIT
            $sheet->mergeCells("C" . $i . ":D" . $i);
            ExcelUtilities::setFont($sheet->getCell('C' . $i), 11, true);
            $sheet->setCellValue(
                'C' . $i,
                $this->translator->trans($line['unitInventory'])
            );
            ExcelUtilities::setBorder($sheet->getCell('C' . $i));
            ExcelUtilities::setBorder($sheet->getCell('D' . $i));
            //  MONDAY PRODUCT LOSS
            $sheet->mergeCells("E" . $i . ":F" . $i);
            ExcelUtilities::setFont($sheet->getCell('E' . $i), 11, true);
            $sheet->setCellValue('E' . $i, round($line[1]['total'], 2));
            ExcelUtilities::setBorder($sheet->getCell('E' . $i));
            ExcelUtilities::setBorder($sheet->getCell('F' . $i));
            //  MONDAY PRODUCT LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('G' . $i), 11, true);
            $sheet->setCellValue('G' . $i, round($line[1]['lossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('G' . $i));

            //  TUESDAY PRODUCT LOSS
            $sheet->mergeCells("H" . $i . ":I" . $i);
            ExcelUtilities::setFont($sheet->getCell('H' . $i), 11, true);
            $sheet->setCellValue('H' . $i, round($line[2]['total'], 2));
            ExcelUtilities::setBorder($sheet->getCell('H' . $i));
            ExcelUtilities::setBorder($sheet->getCell('I' . $i));
            //  TUESDAY PRODUCT LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('J' . $i), 11, true);
            $sheet->setCellValue('J' . $i, round($line[2]['lossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('J' . $i));

            //  WEDNESDAY PRODUCT LOSS
            $sheet->mergeCells("K" . $i . ":L" . $i);
            ExcelUtilities::setFont($sheet->getCell('K' . $i), 11, true);
            $sheet->setCellValue('K' . $i, round($line[3]['total'], 2));
            ExcelUtilities::setBorder($sheet->getCell('K' . $i));
            ExcelUtilities::setBorder($sheet->getCell('L' . $i));
            //  WEDNESDAY PRODUCT LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('M' . $i), 11, true);
            $sheet->setCellValue('M' . $i, round($line[3]['lossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('M' . $i));

            //  THURSDAY PRODUCT LOSS
            $sheet->mergeCells("N" . $i . ":O" . $i);
            ExcelUtilities::setFont($sheet->getCell('N' . $i), 11, true);
            $sheet->setCellValue('N' . $i, round($line[4]['total'], 2));
            ExcelUtilities::setBorder($sheet->getCell('N' . $i));
            ExcelUtilities::setBorder($sheet->getCell('O' . $i));
            //  THURSDAY PRODUCT LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('P' . $i), 11, true);
            $sheet->setCellValue('P' . $i, round($line[4]['lossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('P' . $i));

            //  FRIDAY PRODUCT LOSS
            $sheet->mergeCells("Q" . $i . ":R" . $i);
            ExcelUtilities::setFont($sheet->getCell('Q' . $i), 11, true);
            $sheet->setCellValue('Q' . $i, round($line[5]['total'], 2));
            ExcelUtilities::setBorder($sheet->getCell('Q' . $i));
            ExcelUtilities::setBorder($sheet->getCell('R' . $i));
            //  FRIDAY PRODUCT LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('S' . $i), 11, true);
            $sheet->setCellValue('S' . $i, round($line[5]['lossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('S' . $i));

            //  SATURDAY PRODUCT LOSS
            $sheet->mergeCells("T" . $i . ":U" . $i);
            ExcelUtilities::setFont($sheet->getCell('T' . $i), 11, true);
            $sheet->setCellValue('T' . $i, round($line[6]['total'], 2));
            ExcelUtilities::setBorder($sheet->getCell('T' . $i));
            ExcelUtilities::setBorder($sheet->getCell('U' . $i));
            //  SATURDAY PRODUCT LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('V' . $i), 11, true);
            $sheet->setCellValue('V' . $i, round($line[6]['lossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('V' . $i));

            //  SUNDAY PRODUCT  LOSS
            $sheet->mergeCells("W" . $i . ":X" . $i);
            ExcelUtilities::setFont($sheet->getCell('W' . $i), 11, true);
            $sheet->setCellValue('W' . $i, round($line[0]['total'], 2));
            ExcelUtilities::setBorder($sheet->getCell('W' . $i));
            ExcelUtilities::setBorder($sheet->getCell('X' . $i));
            //  SUNDAY PRODUCT  LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('Y' . $i), 11, true);
            $sheet->setCellValue('Y' . $i, round($line[0]['lossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('Y' . $i));

            //  TOTAL LOSS
            $sheet->mergeCells("Z" . $i . ":AA" . $i);
            ExcelUtilities::setFont($sheet->getCell('Z' . $i), 11, true);
            $sheet->setCellValue('Z' . $i, round($line['totalLoss'], 2));
            ExcelUtilities::setBorder($sheet->getCell('Z' . $i));
            ExcelUtilities::setBorder($sheet->getCell('AA' . $i));
            //  TOTAL LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('AB' . $i), 11, true);
            $sheet->setCellValue('AB' . $i, round($line['totalLossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('AB' . $i));
            $i++;
        }

        //  TOTAL PERIOD
        //     BLANC PART
        $sheet->mergeCells("A" . $i . ":B" . $i);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A" . $i), $colorFour);
        ExcelUtilities::setBorder($sheet->getCell('A' . $i));
        ExcelUtilities::setBorder($sheet->getCell('B' . $i));
        //     LABEL
        $sheet->mergeCells("C" . $i . ":D" . $i);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C" . $i), $colorFour);
        $sheet->setCellValue(
            'C' . $i,
            $this->translator->trans('label.total_period')
        );
        ExcelUtilities::setBorder($sheet->getCell('C' . $i));
        ExcelUtilities::setBorder($sheet->getCell('D' . $i));

        //     MONDAY PROPORTION
        $sheet->mergeCells("E" . $i . ":F" . $i);
        ExcelUtilities::setFont($sheet->getCell('E' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E" . $i), $colorFour);
        $sheet->setCellValue('E' . $i, round($proportion[1], 2));
        ExcelUtilities::setBorder($sheet->getCell('E' . $i));
        ExcelUtilities::setBorder($sheet->getCell('F' . $i));
        //     MONDAY PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('G' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G" . $i), $colorFour);
        $sheet->setCellValue('G' . $i, round($total[1], 2));
        ExcelUtilities::setBorder($sheet->getCell('G' . $i));

        //     TUESDAY PROPORTION
        $sheet->mergeCells("H" . $i . ":I" . $i);
        ExcelUtilities::setFont($sheet->getCell('H' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H" . $i), $colorFour);
        $sheet->setCellValue('H' . $i, round($proportion[2], 2));
        ExcelUtilities::setBorder($sheet->getCell('H' . $i));
        ExcelUtilities::setBorder($sheet->getCell('I' . $i));
        //     TUESDAY PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('J' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J" . $i), $colorFour);
        $sheet->setCellValue('J' . $i, round($total[2], 2));
        ExcelUtilities::setBorder($sheet->getCell('J' . $i));

        //     WEDNESDAY PROPORTION
        $sheet->mergeCells("K" . $i . ":L" . $i);
        ExcelUtilities::setFont($sheet->getCell('K' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K" . $i), $colorFour);
        $sheet->setCellValue('K' . $i, round($proportion[3], 2));
        ExcelUtilities::setBorder($sheet->getCell('K' . $i));
        ExcelUtilities::setBorder($sheet->getCell('L' . $i));
        //     WEDNESDAY PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('M' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M" . $i), $colorFour);
        $sheet->setCellValue('M' . $i, round($total[3], 2));
        ExcelUtilities::setBorder($sheet->getCell('M' . $i));

        //     THURSDAY PROPORTION
        $sheet->mergeCells("N" . $i . ":O" . $i);
        ExcelUtilities::setFont($sheet->getCell('N' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N" . $i), $colorFour);
        $sheet->setCellValue('N' . $i, round($proportion[4], 2));
        ExcelUtilities::setBorder($sheet->getCell('N' . $i));
        ExcelUtilities::setBorder($sheet->getCell('O' . $i));
        //     THURSDAY PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('P' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P" . $i), $colorFour);
        $sheet->setCellValue('P' . $i, round($total[4], 2));
        ExcelUtilities::setBorder($sheet->getCell('P' . $i));

        //     FRIDAY PROPORTION
        $sheet->mergeCells("Q" . $i . ":R" . $i);
        ExcelUtilities::setFont($sheet->getCell('Q' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q" . $i), $colorFour);
        $sheet->setCellValue('Q' . $i, round($proportion[5], 2));
        ExcelUtilities::setBorder($sheet->getCell('Q' . $i));
        ExcelUtilities::setBorder($sheet->getCell('R' . $i));
        //     FRIDAY PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('S' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("S" . $i), $colorFour);
        $sheet->setCellValue('S' . $i, round($total[5], 2));
        ExcelUtilities::setBorder($sheet->getCell('S' . $i));

        //     SATURDAY PROPORTION
        $sheet->mergeCells("T" . $i . ":U" . $i);
        ExcelUtilities::setFont($sheet->getCell('T' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("T" . $i), $colorFour);
        $sheet->setCellValue('T' . $i, round($proportion[6], 2));
        ExcelUtilities::setBorder($sheet->getCell('T' . $i));
        ExcelUtilities::setBorder($sheet->getCell('U' . $i));
        //     SATURDAY PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('V' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("V" . $i), $colorFour);
        $sheet->setCellValue('V' . $i, round($total[6], 2));
        ExcelUtilities::setBorder($sheet->getCell('V' . $i));

        //     SUNDAY PROPORTION
        $sheet->mergeCells("W" . $i . ":X" . $i);
        ExcelUtilities::setFont($sheet->getCell('W' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("W" . $i), $colorFour);
        $sheet->setCellValue('W' . $i, round($proportion[0], 2));
        ExcelUtilities::setBorder($sheet->getCell('W' . $i));
        ExcelUtilities::setBorder($sheet->getCell('X' . $i));
        //     SUNDAY PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('Y' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Y" . $i), $colorFour);
        $sheet->setCellValue('Y' . $i, round($total[0], 2));
        ExcelUtilities::setBorder($sheet->getCell('Y' . $i));

        //     TOTAL PROPORTION
        $sheet->mergeCells("Z" . $i . ":AA" . $i);
        ExcelUtilities::setFont($sheet->getCell('Z' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Z" . $i), $colorFour);
        $sheet->setCellValue('Z' . $i, round($proportion['total'], 2));
        ExcelUtilities::setBorder($sheet->getCell('Z' . $i));
        ExcelUtilities::setBorder($sheet->getCell('AA' . $i));
        //     TOTAL PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('AB' . $i), 11, true);
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell("AB" . $i),
            $colorFour
        );
        $sheet->setCellValue('AB' . $i, round($total[7], 2));
        ExcelUtilities::setBorder($sheet->getCell('AB' . $i));
        $i++;

        //  AVERAGE
        //    BLANC PART
        $sheet->mergeCells("A" . $i . ":B" . $i);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('A' . $i));
        ExcelUtilities::setBorder($sheet->getCell('B' . $i));
        //    LABEL
        $sheet->mergeCells("C" . $i . ":D" . $i);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C" . $i), $colorFive);
        $sheet->setCellValue('C' . $i, $this->translator->trans('label.average'));
        ExcelUtilities::setBorder($sheet->getCell('C' . $i));
        ExcelUtilities::setBorder($sheet->getCell('D' . $i));

        //    MONDAY BLANC
        $sheet->mergeCells("E" . $i . ":F" . $i);
        ExcelUtilities::setFont($sheet->getCell('E' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('E' . $i));
        ExcelUtilities::setBorder($sheet->getCell('F' . $i));
        //    MONDAY AVERAGE
        ExcelUtilities::setFont($sheet->getCell('G' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G" . $i), $colorFive);
        $sheet->setCellValue('G' . $i, round($avg[1], 2));
        ExcelUtilities::setBorder($sheet->getCell('G' . $i));

        //    TUESDAY BLANC
        $sheet->mergeCells("H" . $i . ":I" . $i);
        ExcelUtilities::setFont($sheet->getCell('H' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('H' . $i));
        ExcelUtilities::setBorder($sheet->getCell('I' . $i));
        //    TUESDAY AVERAGE
        ExcelUtilities::setFont($sheet->getCell('J' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J" . $i), $colorFive);
        $sheet->setCellValue('J' . $i, round($avg[2], 2));
        ExcelUtilities::setBorder($sheet->getCell('J' . $i));

        //    WEDNESDAY BLANC
        $sheet->mergeCells("K" . $i . ":L" . $i);
        ExcelUtilities::setFont($sheet->getCell('K' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('K' . $i));
        ExcelUtilities::setBorder($sheet->getCell('L' . $i));
        //    WEDNESDAY AVERAGE
        ExcelUtilities::setFont($sheet->getCell('M' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("M" . $i), $colorFive);
        $sheet->setCellValue('M' . $i, round($avg[3], 2));
        ExcelUtilities::setBorder($sheet->getCell('M' . $i));

        //    THURSDAY BLANC
        $sheet->mergeCells("N" . $i . ":O" . $i);
        ExcelUtilities::setFont($sheet->getCell('N' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('N' . $i));
        ExcelUtilities::setBorder($sheet->getCell('O' . $i));
        //    THURSDAY AVERAGE
        ExcelUtilities::setFont($sheet->getCell('P' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("P" . $i), $colorFive);
        $sheet->setCellValue('P' . $i, round($avg[4], 2));
        ExcelUtilities::setBorder($sheet->getCell('P' . $i));

        //    FRIDAY BLANC
        $sheet->mergeCells("Q" . $i . ":R" . $i);
        ExcelUtilities::setFont($sheet->getCell('Q' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('Q' . $i));
        ExcelUtilities::setBorder($sheet->getCell('R' . $i));
        //    FRIDAY AVERAGE
        ExcelUtilities::setFont($sheet->getCell('S' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("S" . $i), $colorFive);
        $sheet->setCellValue('S' . $i, round($avg[5], 2));
        ExcelUtilities::setBorder($sheet->getCell('S' . $i));

        //    SATURDAY BLANC
        $sheet->mergeCells("T" . $i . ":U" . $i);
        ExcelUtilities::setFont($sheet->getCell('T' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("T" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('T' . $i));
        ExcelUtilities::setBorder($sheet->getCell('U' . $i));
        //    SATURDAY AVERAGE
        ExcelUtilities::setFont($sheet->getCell('V' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("V" . $i), $colorFive);
        $sheet->setCellValue('V' . $i, round($avg[6], 2));
        ExcelUtilities::setBorder($sheet->getCell('V' . $i));

        //    SUNDAY BLANC
        $sheet->mergeCells("W" . $i . ":X" . $i);
        ExcelUtilities::setFont($sheet->getCell('W' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("W" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('W' . $i));
        ExcelUtilities::setBorder($sheet->getCell('X' . $i));
        //    SUNDAY AVERAGE
        ExcelUtilities::setFont($sheet->getCell('Y' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Y" . $i), $colorFive);
        $sheet->setCellValue('Y' . $i, round($avg[0], 2));
        ExcelUtilities::setBorder($sheet->getCell('Y' . $i));

        //    TOTAL BLANC
        $sheet->mergeCells("Z" . $i . ":AA" . $i);
        ExcelUtilities::setFont($sheet->getCell('Z' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Z" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('Z' . $i));
        ExcelUtilities::setBorder($sheet->getCell('AA' . $i));
        //    TOTAL AVERAGE
        ExcelUtilities::setFont($sheet->getCell('AB' . $i), 11, true);
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell("AB" . $i),
            $colorFive
        );
        $sheet->setCellValue('AB' . $i, round($avg[7], 2));
        ExcelUtilities::setBorder($sheet->getCell('AB' . $i));
        // END TABLE CONTENT

        $filename = "Rapport_des_pertes_des_items_d'inventaire" . date('dmY_His')
            . ".xls";
        // create the writer
        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        $response->headers->set(
            'Content-Type',
            'text/vnd.ms-excel; charset=utf-8'
        );
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }
    //END NEW

    //    NEW
    public function generateSoldLossExcelFile(
        $result,
        $avg,
        $total,
        $nbrDayWeek,
        $financialRevenue,
        $proportion,
        $filter,
        Restaurant $currentRestaurant,
        $logoPath
    )
    {
        $colorOne = "ECECEC";
        $colorTwo = "EDE2C9";
        $colorThree = "F4DF42";
        $colorFour = "FCEF01";
        $colorFive = "FFFCC0";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(
            substr($this->translator->trans('report.sold.title'), 0, 30)
        );

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('report.sold.title');
        $sheet->setCellValue('B5', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("B5"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment(
            $sheet->getCell("B5"),
            $alignmentV
        );
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
        $content = $currentRestaurant->getCode() . ' '
            . $currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);


        //FILTER ZONE
        // START DATE
        $sheet->mergeCells("A10:B10");
        ExcelUtilities::setFont($sheet->getCell('A10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A10"), $colorOne);
        $sheet->setCellValue(
            'A10',
            $this->translator->trans('keyword.from') . ":"
        );
        $sheet->mergeCells("C10:D10");
        ExcelUtilities::setFont($sheet->getCell('C10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C10"), $colorOne);
        $sheet->setCellValue('C10', $filter['beginDate']);


        // END DATE
        $sheet->mergeCells("A11:B11");
        ExcelUtilities::setFont($sheet->getCell('A11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A11"), $colorOne);
        $sheet->setCellValue('A11', $this->translator->trans('keyword.to') . ":");
        $sheet->mergeCells("C11:D11");
        ExcelUtilities::setFont($sheet->getCell('C11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C11"), $colorOne);
        $sheet->setCellValue('C11', $filter['endDate']);


        // PRODUCT NAME
        $sheet->mergeCells("F10:G10");
        ExcelUtilities::setFont($sheet->getCell('F10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F10"), $colorOne);
        $sheet->setCellValue('F10', $this->translator->trans('label.name') . ":");
        $sheet->mergeCells("H10:I10");
        ExcelUtilities::setFont($sheet->getCell('H10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H10"), $colorOne);
        if ($filter['productName'] != "") {
            $sheet->setCellValue('H10', $filter['productName']);
        } else {
            $sheet->setCellValue('H10', "--");
        }

        // PRODUCT CODE
        $sheet->mergeCells("F11:G11");
        ExcelUtilities::setFont($sheet->getCell('F11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F11"), $colorOne);
        $sheet->setCellValue('F11', $this->translator->trans('label.code') . ":");
        $sheet->mergeCells("H11:I11");
        ExcelUtilities::setFont($sheet->getCell('H11'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H11"), $colorOne);
        if ($filter['productCode'] != "") {
            $sheet->setCellValue('H11', $filter['productCode']);
        } else {
            $sheet->setCellValue('H11', "--");
        }


        //TABLE
        //  TABLE HEADER
        //    BLANC PART
        $sheet->mergeCells("A17:B18");
        //    MONDY
        $sheet->mergeCells("C17:D17");
        ExcelUtilities::setFont($sheet->getCell('C17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C17"), $colorThree);
        $sheet->setCellValue('E17', $this->translator->trans('days.monday'));
        ExcelUtilities::setBorder($sheet->getCell('C17'));
        ExcelUtilities::setBorder($sheet->getCell('D17'));
        //    MONDAY NUMBER
        ExcelUtilities::setFont($sheet->getCell('E17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E17"), $colorThree);
        $sheet->setCellValue('G17', $nbrDayWeek['Monday']);
        ExcelUtilities::setBorder($sheet->getCell('E17'));

        //    TUESDAY
        $sheet->mergeCells("F17:G17");
        ExcelUtilities::setFont($sheet->getCell('F17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F17"), $colorThree);
        $sheet->setCellValue('H17', $this->translator->trans('days.tuesday'));
        ExcelUtilities::setBorder($sheet->getCell('F17'));
        ExcelUtilities::setBorder($sheet->getCell('G17'));
        //    TUESDAY NUMBER
        ExcelUtilities::setFont($sheet->getCell('H17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H17"), $colorThree);
        $sheet->setCellValue('H17', $nbrDayWeek['Tuesday']);
        ExcelUtilities::setBorder($sheet->getCell('H17'));

        //    WEDNESDAY
        $sheet->mergeCells("I17:J17");
        ExcelUtilities::setFont($sheet->getCell('I17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I17"), $colorThree);
        $sheet->setCellValue('I17', $this->translator->trans('days.wednesday'));
        ExcelUtilities::setBorder($sheet->getCell('I17'));
        ExcelUtilities::setBorder($sheet->getCell('J17'));
        //    WEDNESDAY NUMBER
        ExcelUtilities::setFont($sheet->getCell('K17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K17"), $colorThree);
        $sheet->setCellValue('K17', $nbrDayWeek['Wednesday']);
        ExcelUtilities::setBorder($sheet->getCell('K17'));

        //    THURSDAY
        $sheet->mergeCells("L17:M17");
        ExcelUtilities::setFont($sheet->getCell('L17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L17"), $colorThree);
        $sheet->setCellValue('L17', $this->translator->trans('days.thursday'));
        ExcelUtilities::setBorder($sheet->getCell('L17'));
        ExcelUtilities::setBorder($sheet->getCell('M17'));
        //    THURSDAY NUMBER
        ExcelUtilities::setFont($sheet->getCell('N17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N17"), $colorThree);
        $sheet->setCellValue('N17', $nbrDayWeek['Thursday']);
        ExcelUtilities::setBorder($sheet->getCell('N17'));

        //    FRIDAY
        $sheet->mergeCells("O17:P17");
        ExcelUtilities::setFont($sheet->getCell('O17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O17"), $colorThree);
        $sheet->setCellValue('O17', $this->translator->trans('days.friday'));
        ExcelUtilities::setBorder($sheet->getCell('O17'));
        ExcelUtilities::setBorder($sheet->getCell('P17'));
        //    FRIDAY NUMBER
        ExcelUtilities::setFont($sheet->getCell('Q17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q17"), $colorThree);
        $sheet->setCellValue('Q17', $nbrDayWeek['Friday']);
        ExcelUtilities::setBorder($sheet->getCell('Q17'));

        //    SATURDAY
        $sheet->mergeCells("R17:S17");
        ExcelUtilities::setFont($sheet->getCell('R17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R17"), $colorThree);
        $sheet->setCellValue('R17', $this->translator->trans('days.saturday'));
        ExcelUtilities::setBorder($sheet->getCell('R17'));
        ExcelUtilities::setBorder($sheet->getCell('S17'));
        //    SATURDAY NUMBER
        ExcelUtilities::setFont($sheet->getCell('T17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("T17"), $colorThree);
        $sheet->setCellValue('T17', $nbrDayWeek['Saturday']);
        ExcelUtilities::setBorder($sheet->getCell('T17'));

        //    SUNDAY
        $sheet->mergeCells("U17:V17");
        ExcelUtilities::setFont($sheet->getCell('U17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("U17"), $colorThree);
        $sheet->setCellValue('U17', $this->translator->trans('days.sunday'));
        ExcelUtilities::setBorder($sheet->getCell('U17'));
        ExcelUtilities::setBorder($sheet->getCell('V17'));
        //    SUNDAY NUMBER
        ExcelUtilities::setFont($sheet->getCell('W17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("W17"), $colorThree);
        $sheet->setCellValue('W17', $nbrDayWeek['Sunday']);
        ExcelUtilities::setBorder($sheet->getCell('W17'));

        //    TOTAL
        $sheet->mergeCells("X17:Y17");
        ExcelUtilities::setFont($sheet->getCell('X17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("X17"), $colorThree);
        $sheet->setCellValue('X17', $this->translator->trans('keyword.total'));
        ExcelUtilities::setBorder($sheet->getCell('X17'));
        ExcelUtilities::setBorder($sheet->getCell('Y17'));
        //    TOTAL NUMBER
        ExcelUtilities::setFont($sheet->getCell('Z17'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Z17"), $colorThree);
        $sheet->setCellValue('Z17', $nbrDayWeek['total']);
        ExcelUtilities::setBorder($sheet->getCell('Z17'));

        //    MONDAY CA NET
        $sheet->mergeCells("C18:D18");
        ExcelUtilities::setFont($sheet->getCell('C18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C18"), $colorTwo);
        $sheet->setCellValue('C18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('C18'));
        ExcelUtilities::setBorder($sheet->getCell('D18'));
        //    MONDAY FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('E18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E18"), $colorTwo);
        $sheet->setCellValue('E18', round($financialRevenue[1], 2));
        ExcelUtilities::setBorder($sheet->getCell('E18'));

        //    TUESDAY CA NET
        $sheet->mergeCells("F18:G18");
        ExcelUtilities::setFont($sheet->getCell('F18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F18"), $colorTwo);
        $sheet->setCellValue('F18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('F18'));
        ExcelUtilities::setBorder($sheet->getCell('G18'));
        //    TUESDAY FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('H18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H18"), $colorTwo);
        $sheet->setCellValue('H18', round($financialRevenue[2], 2));
        ExcelUtilities::setBorder($sheet->getCell('H18'));

        //    WEDNESDAY CA NET
        $sheet->mergeCells("I18:J18");
        ExcelUtilities::setFont($sheet->getCell('I18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I18"), $colorTwo);
        $sheet->setCellValue('I18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('I18'));
        ExcelUtilities::setBorder($sheet->getCell('J18'));
        //    WEDNESDAY FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('K18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K18"), $colorTwo);
        $sheet->setCellValue('K18', round($financialRevenue[3], 2));
        ExcelUtilities::setBorder($sheet->getCell('K18'));

        //    THURSDAY CA NET
        $sheet->mergeCells("L18:M18");
        ExcelUtilities::setFont($sheet->getCell('L18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L18"), $colorTwo);
        $sheet->setCellValue('L18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('L18'));
        ExcelUtilities::setBorder($sheet->getCell('M18'));
        //    THURSDAY FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('N18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N18"), $colorTwo);
        $sheet->setCellValue('N18', round($financialRevenue[4], 2));
        ExcelUtilities::setBorder($sheet->getCell('N18'));

        //    FRIDAY CA NET
        $sheet->mergeCells("O18:P18");
        ExcelUtilities::setFont($sheet->getCell('O18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O18"), $colorTwo);
        $sheet->setCellValue('O18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('O18'));
        ExcelUtilities::setBorder($sheet->getCell('P18'));
        //    FRIDAY FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('Q18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q18"), $colorTwo);
        $sheet->setCellValue('Q18', round($financialRevenue[5], 2));
        ExcelUtilities::setBorder($sheet->getCell('Q18'));

        //    SATURDAY CA NET
        $sheet->mergeCells("R18:S18");
        ExcelUtilities::setFont($sheet->getCell('R18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R18"), $colorTwo);
        $sheet->setCellValue('R18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('R18'));
        ExcelUtilities::setBorder($sheet->getCell('S18'));
        //    SATURDAY FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('T18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("T18"), $colorTwo);
        $sheet->setCellValue('T18', round($financialRevenue[6], 2));
        ExcelUtilities::setBorder($sheet->getCell('T18'));

        //    SUNDAY CA NET
        $sheet->mergeCells("U18:V18");
        ExcelUtilities::setFont($sheet->getCell('U18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("U18"), $colorTwo);
        $sheet->setCellValue('U18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('V18'));
        ExcelUtilities::setBorder($sheet->getCell('W18'));
        //    SUNDAY FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('W18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("W18"), $colorTwo);
        $sheet->setCellValue('W18', round($financialRevenue[0], 2));
        ExcelUtilities::setBorder($sheet->getCell('W18'));

        //    TOTAL CA NET
        $sheet->mergeCells("X18:Y18");
        ExcelUtilities::setFont($sheet->getCell('X18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("X18"), $colorTwo);
        $sheet->setCellValue('X18', 'CA Net HT');
        ExcelUtilities::setBorder($sheet->getCell('X18'));
        ExcelUtilities::setBorder($sheet->getCell('Y18'));
        //    TOTAL FINANCIAL REVENUE
        ExcelUtilities::setFont($sheet->getCell('Z18'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Z18"), $colorTwo);
        $sheet->setCellValue('Z18', round($financialRevenue['total'], 2));
        ExcelUtilities::setBorder($sheet->getCell('Z18'));

        //    INVENTORY ITEMS
        $sheet->mergeCells("A19:B19");
        ExcelUtilities::setFont($sheet->getCell('A19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A19"), $colorOne);
        $sheet->setCellValue(
            'A19',
            $this->translator->trans('label.inventory_item')
        );
        ExcelUtilities::setBorder($sheet->getCell('A19'));
        ExcelUtilities::setBorder($sheet->getCell('B19'));


        //    MONDAY QUANTITY
        $sheet->mergeCells("C19:D19");
        ExcelUtilities::setFont($sheet->getCell('C19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C19"), $colorThree);
        $sheet->setCellValue('C19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('C19'));
        ExcelUtilities::setBorder($sheet->getCell('D19'));
        //    MONDAY VALUE
        ExcelUtilities::setFont($sheet->getCell('E19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E19"), $colorThree);
        $sheet->setCellValue(
            'E19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('E19'));

        //    TUESDAY QUANTITY
        $sheet->mergeCells("F19:G19");
        ExcelUtilities::setFont($sheet->getCell('F19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F19"), $colorThree);
        $sheet->setCellValue('F19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('F19'));
        ExcelUtilities::setBorder($sheet->getCell('G19'));
        //    TUESDAY VALUE
        ExcelUtilities::setFont($sheet->getCell('H19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H19"), $colorThree);
        $sheet->setCellValue(
            'H19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('H19'));

        //    WEDNESDAY QUANTITY
        $sheet->mergeCells("I19:J19");
        ExcelUtilities::setFont($sheet->getCell('I19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I19"), $colorThree);
        $sheet->setCellValue('I19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('I19'));
        ExcelUtilities::setBorder($sheet->getCell('J19'));
        //    WEDNESDAY VALUE
        ExcelUtilities::setFont($sheet->getCell('K19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K19"), $colorThree);
        $sheet->setCellValue(
            'K19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('K19'));

        //    THURSDAY QUANTITY
        $sheet->mergeCells("L19:M19");
        ExcelUtilities::setFont($sheet->getCell('L19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L19"), $colorThree);
        $sheet->setCellValue('L19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('L19'));
        ExcelUtilities::setBorder($sheet->getCell('M19'));
        //    THURSDAY VALUE
        ExcelUtilities::setFont($sheet->getCell('N19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N19"), $colorThree);
        $sheet->setCellValue(
            'N19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('N19'));

        //    FRIDAY QUANTITY
        $sheet->mergeCells("O19:P19");
        ExcelUtilities::setFont($sheet->getCell('O19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O19"), $colorThree);
        $sheet->setCellValue('O19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('O19'));
        ExcelUtilities::setBorder($sheet->getCell('P19'));
        //    FRIDAY VALUE
        ExcelUtilities::setFont($sheet->getCell('Q19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q19"), $colorThree);
        $sheet->setCellValue(
            'Q19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('Q19'));

        //    SATURDAY QUANTITY
        $sheet->mergeCells("R19:S19");
        ExcelUtilities::setFont($sheet->getCell('R19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R19"), $colorThree);
        $sheet->setCellValue('R19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('R19'));
        ExcelUtilities::setBorder($sheet->getCell('S19'));
        //    SATURDAY VALUE
        ExcelUtilities::setFont($sheet->getCell('T19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("T19"), $colorThree);
        $sheet->setCellValue(
            'T19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('T19'));

        //    SUNDAY QUANTITY
        $sheet->mergeCells("U19:V19");
        ExcelUtilities::setFont($sheet->getCell('U19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("U19"), $colorThree);
        $sheet->setCellValue('U19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('U19'));
        ExcelUtilities::setBorder($sheet->getCell('V19'));
        //    SUNDAY VALUE
        ExcelUtilities::setFont($sheet->getCell('W19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("W19"), $colorThree);
        $sheet->setCellValue(
            'W19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('W19'));

        //    TOTAL QUANTITY
        $sheet->mergeCells("X19:Y19");
        ExcelUtilities::setFont($sheet->getCell('X19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("X19"), $colorThree);
        $sheet->setCellValue('X19', $this->translator->trans('label.qty'));
        ExcelUtilities::setBorder($sheet->getCell('X19'));
        ExcelUtilities::setBorder($sheet->getCell('Y19'));
        //    TOTAL VALUE
        ExcelUtilities::setFont($sheet->getCell('Z19'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Z19"), $colorThree);
        $sheet->setCellValue(
            'Z19',
            $this->translator->trans('label.value') . " (€)"
        );
        ExcelUtilities::setBorder($sheet->getCell('Z19'));

        // TABLE CONTENT
        $i = 20;
        foreach ($result as $line) {
            //  PRODUCT NAME
            $sheet->mergeCells("A" . $i . ":B" . $i);
            ExcelUtilities::setFont($sheet->getCell('A' . $i), 11, true);
            $sheet->setCellValue('A' . $i, $line['productName']);
            ExcelUtilities::setBorder($sheet->getCell('A' . $i));
            ExcelUtilities::setBorder($sheet->getCell('B' . $i));

            //  MONDAY PRODUCT LOSS
            $sheet->mergeCells("C" . $i . ":D" . $i);
            ExcelUtilities::setFont($sheet->getCell('C' . $i), 11, true);
            $sheet->setCellValue('C' . $i, round($line[1]['total'], 2));
            ExcelUtilities::setBorder($sheet->getCell('C' . $i));
            ExcelUtilities::setBorder($sheet->getCell('D' . $i));
            //  MONDAY PRODUCT LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('E' . $i), 11, true);
            $sheet->setCellValue('E' . $i, round($line[1]['lossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('E' . $i));

            //  TUESDAY PRODUCT LOSS
            $sheet->mergeCells("F" . $i . ":G" . $i);
            ExcelUtilities::setFont($sheet->getCell('F' . $i), 11, true);
            $sheet->setCellValue('F' . $i, round($line[2]['total'], 2));
            ExcelUtilities::setBorder($sheet->getCell('F' . $i));
            ExcelUtilities::setBorder($sheet->getCell('G' . $i));
            //  TUESDAY PRODUCT LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('H' . $i), 11, true);
            $sheet->setCellValue('H' . $i, round($line[2]['lossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('H' . $i));

            //  WEDNESDAY PRODUCT LOSS
            $sheet->mergeCells("I" . $i . ":J" . $i);
            ExcelUtilities::setFont($sheet->getCell('I' . $i), 11, true);
            $sheet->setCellValue('I' . $i, round($line[3]['total'], 2));
            ExcelUtilities::setBorder($sheet->getCell('I' . $i));
            ExcelUtilities::setBorder($sheet->getCell('J' . $i));
            //  WEDNESDAY PRODUCT LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('K' . $i), 11, true);
            $sheet->setCellValue('K' . $i, round($line[3]['lossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('K' . $i));

            //  THURSDAY PRODUCT LOSS
            $sheet->mergeCells("L" . $i . ":M" . $i);
            ExcelUtilities::setFont($sheet->getCell('L' . $i), 11, true);
            $sheet->setCellValue('L' . $i, round($line[4]['total'], 2));
            ExcelUtilities::setBorder($sheet->getCell('L' . $i));
            ExcelUtilities::setBorder($sheet->getCell('M' . $i));
            //  THURSDAY PRODUCT LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('N' . $i), 11, true);
            $sheet->setCellValue('N' . $i, round($line[4]['lossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('N' . $i));

            //  FRIDAY PRODUCT LOSS
            $sheet->mergeCells("O" . $i . ":P" . $i);
            ExcelUtilities::setFont($sheet->getCell('O' . $i), 11, true);
            $sheet->setCellValue('O' . $i, round($line[5]['total'], 2));
            ExcelUtilities::setBorder($sheet->getCell('O' . $i));
            ExcelUtilities::setBorder($sheet->getCell('P' . $i));
            //  FRIDAY PRODUCT LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('Q' . $i), 11, true);
            $sheet->setCellValue('Q' . $i, round($line[5]['lossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('Q' . $i));

            //  SATURDAY PRODUCT LOSS
            $sheet->mergeCells("R" . $i . ":S" . $i);
            ExcelUtilities::setFont($sheet->getCell('R' . $i), 11, true);
            $sheet->setCellValue('R' . $i, round($line[6]['total'], 2));
            ExcelUtilities::setBorder($sheet->getCell('R' . $i));
            ExcelUtilities::setBorder($sheet->getCell('S' . $i));
            //  SATURDAY PRODUCT LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('T' . $i), 11, true);
            $sheet->setCellValue('T' . $i, round($line[6]['lossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('T' . $i));

            //  SUNDAY PRODUCT  LOSS
            $sheet->mergeCells("U" . $i . ":V" . $i);
            ExcelUtilities::setFont($sheet->getCell('U' . $i), 11, true);
            $sheet->setCellValue('U' . $i, round($line[0]['total'], 2));
            ExcelUtilities::setBorder($sheet->getCell('U' . $i));
            ExcelUtilities::setBorder($sheet->getCell('V' . $i));
            //  SUNDAY PRODUCT  LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('W' . $i), 11, true);
            $sheet->setCellValue('W' . $i, round($line[0]['lossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('W' . $i));

            //  TOTAL LOSS
            $sheet->mergeCells("X" . $i . ":Y" . $i);
            ExcelUtilities::setFont($sheet->getCell('X' . $i), 11, true);
            $sheet->setCellValue('X' . $i, round($line['totalLoss'], 2));
            ExcelUtilities::setBorder($sheet->getCell('X' . $i));
            ExcelUtilities::setBorder($sheet->getCell('Y' . $i));
            //  TOTAL LOSS VALORIZATION
            ExcelUtilities::setFont($sheet->getCell('Z' . $i), 11, true);
            $sheet->setCellValue('Z' . $i, round($line['totalLossVal'], 2));
            ExcelUtilities::setBorder($sheet->getCell('Z' . $i));
            $i++;
        }

        //  TOTAL PERIOD
        //     LABEL
        $sheet->mergeCells("A" . $i . ":B" . $i);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A" . $i), $colorFour);
        $sheet->setCellValue(
            'A' . $i,
            $this->translator->trans('label.total_period')
        );
        ExcelUtilities::setBorder($sheet->getCell('A' . $i));
        ExcelUtilities::setBorder($sheet->getCell('B' . $i));

        //     MONDAY PROPORTION
        $sheet->mergeCells("C" . $i . ":D" . $i);
        ExcelUtilities::setFont($sheet->getCell('C' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C" . $i), $colorFour);
        $sheet->setCellValue('C' . $i, round($proportion[1], 2));
        ExcelUtilities::setBorder($sheet->getCell('C' . $i));
        ExcelUtilities::setBorder($sheet->getCell('D' . $i));
        //     MONDAY PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('E' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E" . $i), $colorFour);
        $sheet->setCellValue('E' . $i, round($total[1], 2));
        ExcelUtilities::setBorder($sheet->getCell('E' . $i));

        //     TUESDAY PROPORTION
        $sheet->mergeCells("F" . $i . ":G" . $i);
        ExcelUtilities::setFont($sheet->getCell('F' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F" . $i), $colorFour);
        $sheet->setCellValue('F' . $i, round($proportion[2], 2));
        ExcelUtilities::setBorder($sheet->getCell('F' . $i));
        ExcelUtilities::setBorder($sheet->getCell('G' . $i));
        //     TUESDAY PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('H' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H" . $i), $colorFour);
        $sheet->setCellValue('H' . $i, round($total[2], 2));
        ExcelUtilities::setBorder($sheet->getCell('H' . $i));

        //     WEDNESDAY PROPORTION
        $sheet->mergeCells("I" . $i . ":J" . $i);
        ExcelUtilities::setFont($sheet->getCell('I' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I" . $i), $colorFour);
        $sheet->setCellValue('I' . $i, round($proportion[3], 2));
        ExcelUtilities::setBorder($sheet->getCell('I' . $i));
        ExcelUtilities::setBorder($sheet->getCell('J' . $i));
        //     WEDNESDAY PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('K' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K" . $i), $colorFour);
        $sheet->setCellValue('K' . $i, round($total[3], 2));
        ExcelUtilities::setBorder($sheet->getCell('K' . $i));

        //     THURSDAY PROPORTION
        $sheet->mergeCells("L" . $i . ":M" . $i);
        ExcelUtilities::setFont($sheet->getCell('L' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L" . $i), $colorFour);
        $sheet->setCellValue('L' . $i, round($proportion[4], 2));
        ExcelUtilities::setBorder($sheet->getCell('L' . $i));
        ExcelUtilities::setBorder($sheet->getCell('M' . $i));
        //     THURSDAY PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('N' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N" . $i), $colorFour);
        $sheet->setCellValue('N' . $i, round($total[4], 2));
        ExcelUtilities::setBorder($sheet->getCell('N' . $i));

        //     FRIDAY PROPORTION
        $sheet->mergeCells("O" . $i . ":P" . $i);
        ExcelUtilities::setFont($sheet->getCell('O' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O" . $i), $colorFour);
        $sheet->setCellValue('O' . $i, round($proportion[5], 2));
        ExcelUtilities::setBorder($sheet->getCell('O' . $i));
        ExcelUtilities::setBorder($sheet->getCell('P' . $i));
        //     FRIDAY PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('Q' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q" . $i), $colorFour);
        $sheet->setCellValue('Q' . $i, round($total[5], 2));
        ExcelUtilities::setBorder($sheet->getCell('Q' . $i));

        //     SATURDAY PROPORTION
        $sheet->mergeCells("R" . $i . ":S" . $i);
        ExcelUtilities::setFont($sheet->getCell('R' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R" . $i), $colorFour);
        $sheet->setCellValue('R' . $i, round($proportion[6], 2));
        ExcelUtilities::setBorder($sheet->getCell('R' . $i));
        ExcelUtilities::setBorder($sheet->getCell('S' . $i));
        //     SATURDAY PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('T' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("T" . $i), $colorFour);
        $sheet->setCellValue('T' . $i, round($total[6], 2));
        ExcelUtilities::setBorder($sheet->getCell('T' . $i));

        //     SUNDAY PROPORTION
        $sheet->mergeCells("U" . $i . ":V" . $i);
        ExcelUtilities::setFont($sheet->getCell('U' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("U" . $i), $colorFour);
        $sheet->setCellValue('U' . $i, round($proportion[0], 2));
        ExcelUtilities::setBorder($sheet->getCell('U' . $i));
        ExcelUtilities::setBorder($sheet->getCell('V' . $i));
        //     SUNDAY PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('W' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("W" . $i), $colorFour);
        $sheet->setCellValue('W' . $i, round($total[0], 2));
        ExcelUtilities::setBorder($sheet->getCell('Y' . $i));

        //     TOTAL PROPORTION
        $sheet->mergeCells("X" . $i . ":Y" . $i);
        ExcelUtilities::setFont($sheet->getCell('X' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("X" . $i), $colorFour);
        $sheet->setCellValue('X' . $i, round($proportion['total'], 2));
        ExcelUtilities::setBorder($sheet->getCell('X' . $i));
        ExcelUtilities::setBorder($sheet->getCell('Y' . $i));
        //     TOTAL PROPORTION VALORIZATION
        ExcelUtilities::setFont($sheet->getCell('Z' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Z" . $i), $colorFour);
        $sheet->setCellValue('Z' . $i, round($total[7], 2));
        ExcelUtilities::setBorder($sheet->getCell('Z' . $i));
        $i++;

        //  AVERAGE

        //    LABEL
        $sheet->mergeCells("A" . $i . ":B" . $i);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A" . $i), $colorFive);
        $sheet->setCellValue('A' . $i, $this->translator->trans('label.average'));
        ExcelUtilities::setBorder($sheet->getCell('A' . $i));
        ExcelUtilities::setBorder($sheet->getCell('B' . $i));

        //    MONDAY BLANC
        $sheet->mergeCells("C" . $i . ":D" . $i);
        ExcelUtilities::setFont($sheet->getCell('C' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('C' . $i));
        ExcelUtilities::setBorder($sheet->getCell('D' . $i));
        //    MONDAY AVERAGE
        ExcelUtilities::setFont($sheet->getCell('E' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E" . $i), $colorFive);
        $sheet->setCellValue('E' . $i, round($avg[1], 2));
        ExcelUtilities::setBorder($sheet->getCell('E' . $i));

        //    TUESDAY BLANC
        $sheet->mergeCells("F" . $i . ":G" . $i);
        ExcelUtilities::setFont($sheet->getCell('F' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('F' . $i));
        ExcelUtilities::setBorder($sheet->getCell('G' . $i));
        //    TUESDAY AVERAGE
        ExcelUtilities::setFont($sheet->getCell('H' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H" . $i), $colorFive);
        $sheet->setCellValue('H' . $i, round($avg[2], 2));
        ExcelUtilities::setBorder($sheet->getCell('H' . $i));

        //    WEDNESDAY BLANC
        $sheet->mergeCells("I" . $i . ":J" . $i);
        ExcelUtilities::setFont($sheet->getCell('I' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('I' . $i));
        ExcelUtilities::setBorder($sheet->getCell('J' . $i));
        //    WEDNESDAY AVERAGE
        ExcelUtilities::setFont($sheet->getCell('K' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K" . $i), $colorFive);
        $sheet->setCellValue('K' . $i, round($avg[3], 2));
        ExcelUtilities::setBorder($sheet->getCell('K' . $i));

        //    THURSDAY BLANC
        $sheet->mergeCells("L" . $i . ":M" . $i);
        ExcelUtilities::setFont($sheet->getCell('L' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("L" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('L' . $i));
        ExcelUtilities::setBorder($sheet->getCell('M' . $i));
        //    THURSDAY AVERAGE
        ExcelUtilities::setFont($sheet->getCell('N' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("N" . $i), $colorFive);
        $sheet->setCellValue('N' . $i, round($avg[4], 2));
        ExcelUtilities::setBorder($sheet->getCell('N' . $i));

        //    FRIDAY BLANC
        $sheet->mergeCells("O" . $i . ":P" . $i);
        ExcelUtilities::setFont($sheet->getCell('O' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("O" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('O' . $i));
        ExcelUtilities::setBorder($sheet->getCell('P' . $i));
        //    FRIDAY AVERAGE
        ExcelUtilities::setFont($sheet->getCell('Q' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Q" . $i), $colorFive);
        $sheet->setCellValue('Q' . $i, round($avg[5], 2));
        ExcelUtilities::setBorder($sheet->getCell('Q' . $i));

        //    SATURDAY BLANC
        $sheet->mergeCells("R" . $i . ":S" . $i);
        ExcelUtilities::setFont($sheet->getCell('R' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("R" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('R' . $i));
        ExcelUtilities::setBorder($sheet->getCell('S' . $i));
        //    SATURDAY AVERAGE
        ExcelUtilities::setFont($sheet->getCell('T' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("T" . $i), $colorFive);
        $sheet->setCellValue('T' . $i, round($avg[6], 2));
        ExcelUtilities::setBorder($sheet->getCell('T' . $i));

        //    SUNDAY BLANC
        $sheet->mergeCells("U" . $i . ":V" . $i);
        ExcelUtilities::setFont($sheet->getCell('U' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("U" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('U' . $i));
        ExcelUtilities::setBorder($sheet->getCell('V' . $i));
        //    SUNDAY AVERAGE
        ExcelUtilities::setFont($sheet->getCell('W' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("W" . $i), $colorFive);
        $sheet->setCellValue('W' . $i, round($avg[0], 2));
        ExcelUtilities::setBorder($sheet->getCell('W' . $i));

        //    TOTAL BLANC
        $sheet->mergeCells("X" . $i . ":Y" . $i);
        ExcelUtilities::setFont($sheet->getCell('X' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("X" . $i), $colorFive);
        ExcelUtilities::setBorder($sheet->getCell('X' . $i));
        ExcelUtilities::setBorder($sheet->getCell('Y' . $i));
        //    TOTAL AVERAGE
        ExcelUtilities::setFont($sheet->getCell('Z' . $i), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("Z" . $i), $colorFive);
        $sheet->setCellValue('Z' . $i, round($avg[7], 2));
        ExcelUtilities::setBorder($sheet->getCell('Z' . $i));
        // END TABLE CONTENT


        $filename = "Rapport_des_pertes_des_items_de_vente" . date('dmY_His')
            . ".xls";
        // create the writer
        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        $response->headers->set(
            'Content-Type',
            'text/vnd.ms-excel; charset=utf-8'
        );
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    //END NEW


    public function generateThreeWeeksPortionControlExcelFile(
        $result,
        $startDate,
        $weekMinus1,
        $weekMinus2,
        $weekMinus3,
        Restaurant $currentRestaurant,
        $logoPath
    )
    {
        //$result["isCalendarWeek"] The flag whether it would be generated for the previous three Weeks

        $endDate = $result["endDate"];
        $topHeaderColor = "CA9E67";
        $secondHeaderColor = "EDE2C9";
        $categoryNameColor = "FDC300";
        $colorOne = "ECECEC";
        $goodEcartColor = "90EE90";
        $failEcartColor = "FFB6C1";

        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(
            substr(
                $this->translator->trans('portion_control.three_weeks'),
                0,
                30
            )
        );

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('portion_control.three_weeks');
        $sheet->setCellValue('B5', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("B5"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment(
            $sheet->getCell("B5"),
            $alignmentV
        );
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
        $content = $currentRestaurant->getCode() . ' '
            . $currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);

        //FILTER ZONE
        // START DATE
        ExcelUtilities::setFont($sheet->getCell('B10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B10"), $colorOne);
        $sheet->setCellValue(
            'B10',
            $this->translator->trans('keyword.from') . ":"
        );
        $sheet->mergeCells("C10:D10");
        ExcelUtilities::setFont($sheet->getCell('C10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C10"), $colorOne);
        $sheet->setCellValue('C10', $startDate);
        // END DATE
        ExcelUtilities::setFont($sheet->getCell('E10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E10"), $colorOne);
        $sheet->setCellValue('E10', $this->translator->trans('keyword.to') . ":");
        $sheet->mergeCells("F10:G10");
        ExcelUtilities::setFont($sheet->getCell('F10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F10"), $colorOne);
        $sheet->setCellValue('F10', $endDate->format('d-m-Y'));


        //CONTENT
        $startCell = 1;
        $startLine = 14;
        $sheet->getRowDimension($startLine)->setRowHeight(17);
        // top headers
        //Items
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 7) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.report_labels.items')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 7) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $topHeaderColor
        );
        $startCell += 8;
        //stocks
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 4) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('general.week') . $startDate
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 4) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $topHeaderColor
        );
        $startCell += 5;
        //sales
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('general.week') . $weekMinus1
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $topHeaderColor
        );
        $startCell += 2;
        //loss
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('general.week') . $weekMinus2
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $topHeaderColor
        );
        $startCell += 2;
        //consommations
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('general.week') . $weekMinus3
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $topHeaderColor
        );

        $startCell += 2;
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('keyword.total')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $topHeaderColor
        );


        // second headers
        $startLine++;
        $startCell = 1;
        //code
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.report_labels.code')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //description
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 3) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.report_labels.description'
            )
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 3) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 4;
        //format
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 2) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.report_labels.format')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 2) . $startLine
            )
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 3;
        //ventes
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.export_labels.ventes')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //Item vtes
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.export_labels.item_vtes')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //Item inv
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.export_labels.item_inv')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //ecart semaine N
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.export_labels.ecart')
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );
        $startCell += 1;
        //valorisation
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.export_labels.valorisation'
            )
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $secondHeaderColor
        );


        // subheaders if the generation is for the three previous weeks


        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.report_labels.ecart_minus',
                array("%number%" => 1)
            )
        );
        ExcelUtilities::setFont(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $secondHeaderColor
        );

        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.report_labels.valorisation_minus',
                array("%number%" => 1)
            ) . "(€)"
        );
        ExcelUtilities::setFont(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $secondHeaderColor
        );


        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.report_labels.ecart_minus',
                array("%number%" => 1)
            )
        );
        ExcelUtilities::setFont(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $secondHeaderColor
        );

        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.report_labels.valorisation_minus',
                array("%number%" => 1)
            ) . "(€)"
        );
        ExcelUtilities::setFont(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $secondHeaderColor
        );

        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.report_labels.ecart_minus',
                array("%number%" => 2)
            )
        );
        ExcelUtilities::setFont(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $secondHeaderColor
        );

        $startCell += 1;

        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.report_labels.valorisation_minus',
                array("%number%" => 3)
            ) . "(€)"
        );
        ExcelUtilities::setFont(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $secondHeaderColor
        );


        $startCell += 1;
        //ecart
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans('portion_control.export_labels.ecart')
        );
        ExcelUtilities::setFont(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $secondHeaderColor
        );
        $startCell += 1;
        //valo
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.export_labels.valorisation'
            )
        );
        ExcelUtilities::setFont(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            11,
            true
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        ExcelUtilities::setBackgroundColor(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $secondHeaderColor
        );


        //body

        foreach ($result["data"]["data"] as $categoryName => $row) {
            $startCell = 1;
            $startLine++;
            $sheet->getRowDimension($startLine)->setRowHeight(17);
            //category name row

            $sheet->mergeCells(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 20) . $startLine
            );

            $sheet->setCellValue(
                $this->getNameFromNumber($startCell) . $startLine,
                $categoryName
            );
            ExcelUtilities::setFont(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                7,
                true
            );
            ExcelUtilities::setBorder(
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 20) . $startLine
                )
            );
            ExcelUtilities::setBackgroundColor(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                $categoryNameColor
            );
            $startLine++;
            //data
            foreach ($row["data"] as $item) {
                //code
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    $item["code"]
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //description
                $sheet->mergeCells(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 3) . $startLine
                );
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    $item["description"]
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setBorder(
                    $sheet->getStyle(
                        $this->getNameFromNumber($startCell) . $startLine . ":"
                        . $this->getNameFromNumber($startCell + 3) . $startLine
                    )
                );
                $startCell += 4;
                //format
                $sheet->mergeCells(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 2) . $startLine
                );
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    $this->translator->trans($item["format"])
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setBorder(
                    $sheet->getStyle(
                        $this->getNameFromNumber($startCell) . $startLine . ":"
                        . $this->getNameFromNumber($startCell + 2) . $startLine
                    )
                );
                $startCell += 3;
                //initial
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["ventes"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //entree
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["item_vtes"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //sortie
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["item_inv"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //final
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["ecart"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    true
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //valeur_final
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["valorisation"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //ventes
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["ecart_minus_1"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //item_vtes
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["valorisation_minus_1"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //item_inv
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["ecart_minus_2"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //theo
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["valorisation_minus_2"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //reel
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["ecart_minus_3"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );
                $startCell++;
                //ecart
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($item["valorisation_minus_3"], 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );


                // total Ecart
                $startCell++;
                $ecartTotal = $item["ecart"] + $item['ecart_minus_1']
                    + $item['ecart_minus_2'] + $item['ecart_minus_3'];
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($ecartTotal, 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );

                // total Valorisation
                $startCell++;
                $valorisationTotal = $item["valorisation"]
                    + $item['valorisation_minus_1']
                    + $item['valorisation_minus_2']
                    + $item['valorisation_minus_3'];
                $sheet->setCellValue(
                    $this->getNameFromNumber($startCell) . $startLine,
                    number_format($valorisationTotal, 2, '.', '')
                );
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine
                )->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
                ExcelUtilities::setFont(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    7,
                    false
                );
                ExcelUtilities::setCellAlignment(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    ),
                    $alignmentH
                );
                ExcelUtilities::setBorder(
                    $sheet->getCell(
                        $this->getNameFromNumber($startCell) . $startLine
                    )
                );

                $startCell = 1;
                $startLine++;
                $sheet->getRowDimension($startLine)->setRowHeight(17);
            }
            //total line
            //total cell;
            $sheet->setCellValue(
                $this->getNameFromNumber($startCell) . $startLine,
                $this->translator->trans('portion_control.label.total')
            );
            ExcelUtilities::setFont(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                7,
                false
            );
            ExcelUtilities::setCellAlignment(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                $alignmentH
            );
            ExcelUtilities::setBorder(
                $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
            );
            $startCell += 1;
            //empty cells
            $sheet->mergeCells(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 3) . $startLine
            );
            ExcelUtilities::setBorder(
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 3) . $startLine
                )
            );
            $startCell += 4;
            $sheet->mergeCells(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 2) . $startLine
            );
            ExcelUtilities::setBorder(
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 2) . $startLine
                )
            );
            $startCell += 3;
            ExcelUtilities::setBorder(
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 3) . $startLine
                )
            );
            $startCell += 4;
            //total final value
            $sheet->setCellValue(
                $this->getNameFromNumber($startCell) . $startLine,
                number_format($row["aggregations"]["valorisation"], 2, '.', '')
            );
            $sheet->getStyle($this->getNameFromNumber($startCell) . $startLine)
                ->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
            ExcelUtilities::setFont(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                7,
                false
            );
            ExcelUtilities::setCellAlignment(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                $alignmentH
            );
            ExcelUtilities::setBorder(
                $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
            );
            $startCell += 1;
            //empty cells
            ExcelUtilities::setBorder(
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 5) . $startLine
                )
            );
            $startCell += 1;
            //total valorisation
            $sheet->setCellValue(
                $this->getNameFromNumber($startCell) . $startLine,
                number_format($row["aggregations"]["valorisation_minus_1"], 2, '.', '')
            );
            $sheet->getStyle($this->getNameFromNumber($startCell) . $startLine)
                ->getNumberFormat()->setFormatCode(
                    \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
                );
            ExcelUtilities::setFont(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                7,
                false
            );
            ExcelUtilities::setCellAlignment(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                $alignmentH
            );
            ExcelUtilities::setBorder(
                $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
            );


            $startCell += 2;
            $sheet->setCellValue(
                $this->getNameFromNumber($startCell) . $startLine,
                number_format(
                    $row["aggregations"]["valorisation_minus_2"],
                    2,
                    '.',
                    ''
                )
            );
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine
            )->getNumberFormat()->setFormatCode(
                \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
            );
            ExcelUtilities::setFont(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                7,
                false
            );
            ExcelUtilities::setCellAlignment(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                $alignmentH
            );
            ExcelUtilities::setBorder(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                )
            );

            $startCell += 2;
            $sheet->setCellValue(
                $this->getNameFromNumber($startCell) . $startLine,
                number_format(
                    $row["aggregations"]["valorisation_minus_3"],
                    2,
                    '.',
                    ''
                )
            );
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine
            )->getNumberFormat()->setFormatCode(
                \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
            );
            ExcelUtilities::setFont(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                7,
                false
            );
            ExcelUtilities::setCellAlignment(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                ),
                $alignmentH
            );
            ExcelUtilities::setBorder(
                $sheet->getCell(
                    $this->getNameFromNumber($startCell) . $startLine
                )
            );
            $startCell += 1;
            //empty cells
            ExcelUtilities::setBorder(
                $sheet->getStyle(
                    $this->getNameFromNumber($startCell) . $startLine . ":"
                    . $this->getNameFromNumber($startCell + 1) . $startLine
                )
            );


        }

        $startLine++;
        $sheet->getRowDimension($startLine)->setRowHeight(17);
        $startCell = 1;
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 7) . ($startLine + 2)
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 7) . ($startLine + 2)
            )
        );
        $startCell += 8;
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 3) . $startLine
            )
        );
        $startCell += 4;
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            number_format(
                $result["data"]["aggregations"]["valorisation"],
                2,
                '.',
                ''
            )
        );
        $sheet->getStyle($this->getNameFromNumber($startCell) . $startLine)
            ->getNumberFormat()->setFormatCode(
                \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
            );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            false
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        $startCell += 1;
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 5) . $startLine
            )
        );
        $startCell += 1;
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            number_format(
                $result["data"]["aggregations"]["valorisation_minus_1"],
                2,
                '.',
                ''
            )
        );
        $sheet->getStyle($this->getNameFromNumber($startCell) . $startLine)
            ->getNumberFormat()->setFormatCode(
                \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
            );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            false
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );


        $startCell += 2;
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            number_format(
                $result["data"]["aggregations"]["valorisation_minus_2"],
                2,
                '.',
                ''
            )
        );
        $sheet->getStyle($this->getNameFromNumber($startCell) . $startLine)
            ->getNumberFormat()->setFormatCode(
                \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
            );
        ExcelUtilities::setFont(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            7,
            false
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );

        $startCell += 2;
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            number_format(
                $result["data"]["aggregations"]["valorisation_minus_3"],
                2,
                '.',
                ''
            )
        );
        $sheet->getStyle($this->getNameFromNumber($startCell) . $startLine)
            ->getNumberFormat()->setFormatCode(
                \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
            );
        ExcelUtilities::setFont(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            7,
            false
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell(
                $this->getNameFromNumber($startCell) . $startLine
            ),
            $alignmentH
        );
        ExcelUtilities::setBorder(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine)
        );
        $startCell += 1;
        //empty cells
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );


        $startCell = 9;
        $startLine++;
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.report_labels.ecart_positif'
            ) . " (€)"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );
        $startCell += 2;
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            number_format(
                $result["data"]["aggregations"]["positive_ecart"],
                2,
                '.',
                ''
            )
        );
        $sheet->getStyle($this->getNameFromNumber($startCell) . $startLine)
            ->getNumberFormat()->setFormatCode(
                \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
            );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            false
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );
        $startCell += 2;
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 8) . $startLine
            )
        );
        $startCell = 9;
        $startLine++;
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            $this->translator->trans(
                'portion_control.report_labels.ecartnegatif'
            ) . " (€)"
        );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            true
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );
        $startCell += 2;
        $sheet->mergeCells(
            $this->getNameFromNumber($startCell) . $startLine . ":"
            . $this->getNameFromNumber($startCell + 1) . $startLine
        );
        $sheet->setCellValue(
            $this->getNameFromNumber($startCell) . $startLine,
            number_format(
                $result["data"]["aggregations"]["negative_ecart"],
                2,
                '.',
                ''
            )
        );
        $sheet->getStyle($this->getNameFromNumber($startCell) . $startLine)
            ->getNumberFormat()->setFormatCode(
                \PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00
            );
        ExcelUtilities::setFont(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            7,
            false
        );
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 1) . $startLine
            )
        );
        ExcelUtilities::setCellAlignment(
            $sheet->getCell($this->getNameFromNumber($startCell) . $startLine),
            $alignmentH
        );

        $startCell += 2;
        ExcelUtilities::setBorder(
            $sheet->getStyle(
                $this->getNameFromNumber($startCell) . $startLine . ":"
                . $this->getNameFromNumber($startCell + 8) . $startLine
            )
        );

        $filename = "Rapport_portion_controle_" . date('dmY_His') . ".xls";
        // create the writer
        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        $response->headers->set(
            'Content-Type',
            'text/vnd.ms-excel; charset=utf-8'
        );
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    function getNameFromNumber($num)
    {
        $numeric = $num % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval($num / 26);
        if ($num2 > 0) {
            return $this->getNameFromNumber($num2 - 1) . $letter;
        } else {
            return $letter;
        }
    }
}

