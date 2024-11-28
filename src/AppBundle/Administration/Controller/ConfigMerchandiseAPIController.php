<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 10/03/2016
 * Time: 17:44
 */

namespace AppBundle\Administration\Controller;

use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class OrderAPIController
 *
 * @Route("/json/config")
 */
class ConfigMerchandiseAPIController extends Controller
{
    /**
     * @param Request $request
     * @param $download
     * @return JsonResponse
     *
     * @Route("/supplier_json_list/{download}",name="supplier_json_list", options={"expose"=true})
     */
    public function supplierListAction(Request $request, $download = 0)
    {
        $orders = array('code', 'supplier', 'designation', 'address', 'phone', 'mail');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);
        $dataTableHeaders['criteria']['restaurant'] = $this->get('restaurant.service')->getCurrentRestaurant();

        $download = intval($download);
        if (1 === $download) {
            $fileName = 'fournisseur'.date('dmY_His');
            $response = $this->get('toolbox.document.generator')
                ->generateXlsFile(
                    'config.merchandise.service',
                    'getSuppliers',
                    array(
                        'criteria' => $dataTableHeaders['criteria'],
                        'order' => $dataTableHeaders['orderBy'],
                        'onlyList' => true,

                    ),
                    $this->get('translator')->trans("restaurant.list.supplier_list"),
                    [
                        $this->get('translator')->trans('label.code'),
                        $this->get('translator')->trans('filter.supplier'),
                        $this->get('translator')->trans('designation'),
                        $this->get('translator')->trans('label.address'),
                        $this->get('translator')->trans('label.phone'),
                        $this->get('translator')->trans('label.mail'),
                    ],
                    null,
                    $fileName
                );

            //$response = Utilities::createFileResponse($filepath, 'fournisseur' . date('dmY_His') . ".csv");
            return $response;
        }

        $suppliers = $this->getDoctrine()->getRepository("Merchandise:Supplier")->getSupplierOrdered(
            $dataTableHeaders['criteria'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit']
        );
        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $suppliers['filtred'];
        $return['recordsTotal'] = $suppliers['total'];
        $return['data'] = $this->get('config.merchandise.service')->serializeSuppliers($suppliers['list']);

        return new JsonResponse($return);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @Route("/restaurant_list_export",name="restaurant_list_export", options={"expose"=true})
     */
    public function restaurantListExportAction(Request $request)
    {
        $orders = array('code', 'name', 'email', 'manager', 'adress', 'phone', 'type');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);

        $fileName = 'restaurant'.date('dmY_His');

        $response = $this->get('toolbox.document.generator')
            ->generateXlsFile(
                'config.merchandise.service',
                'getRestaurants',
                array(
                    'criteria' => $dataTableHeaders['criteria'],
                    'order' => $dataTableHeaders['orderBy'],
                ),
                $this->get('translator')->trans("restaurant.list.title"),
                [
                    $this->get('translator')->trans('label.code'),
                    $this->get('translator')->trans('label.name'),
                    $this->get('translator')->trans('label.mail'),
                    $this->get('translator')->trans('label.manager'),
                    $this->get('translator')->trans('label.address'),
                    $this->get('translator')->trans('label.phone'),
                    $this->get('translator')->trans('label.type'),
                    $this->get('translator')->trans('keyword_supplier_liste'),
                ],
                null,
                $fileName
            );

        //$response = Utilities::createFileResponse($filepath,'restaurant' . date('dmY_His') . ".csv");
        return $response;
    }

    /**
     * @param Request $request
     * @param int $download
     * @return JsonResponse
     *
     * @Route("/inventory_items_list/{download}",name="inventory_items_json_list", options={"expose"=true})
     */
    public function inventoryItemsJsonListAction(Request $request, $download = 0)
    {
        $orders = array('code', 'name', 'buyingCost', 'supplier', 'status');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);

        $dataTableHeaders['criteria']['inventory_item_search[keyword'] = $request->request->get('search')['value'];
        $dataTableHeaders['criteria']['restaurant'] = $this->get('restaurant.service')->getCurrentRestaurant();
        $dataTableHeaders['criteria']['locale'] = $request->getLocale();

        $download = intval($download);
        if (1 === $download) {
            $fileName = 'itemInventaire'.date('dmY_His');
            $response = $this->get('toolbox.document.generator')
                ->generateXlsFile(
                    'config.merchandise.service',
                    'getInventoryItems',
                    array(
                        'criteria' => $dataTableHeaders['criteria'],
                        'order' => $dataTableHeaders['orderBy'],
                        'onlyList' => true,
                    ),
                    $this->get('translator')->trans("item.inventory.title"),
                    [
                        $this->get('translator')->trans('label.code'),
                        $this->get('translator')->trans('label.name')." FR",
                        $this->get('translator')->trans('label.name')." NL",
                        $this->get('translator')->trans('item.label.buying_cost'),
                        $this->get('translator')->trans('item.label.category'),
                        $this->get('translator')->trans('filter.supplier'),
                        $this->get('translator')->trans('label.status'),
                        $this->get('translator')->trans('item.label.unit_expedition'),
                        $this->get('translator')->trans('item.label.unit_inventory'),
                        $this->get('translator')->trans('item.label.unit_usage'),
                        $this->get('translator')->trans('item.label.inventory_qty'),
                        $this->get('translator')->trans('item.label.usage_qty'),
                        $this->get('translator')->trans('secondary_item'),
                    ],
                    function ($line) {
                        return [
                            $line['code'],
                            $line['nameFr'],
                            $line['nameNl'],
                            $line['buyingCost'],
                            $line['category'],
                            $line['supplier'],
                            $line['status'],
                            $line['unitExpedition'],
                            $line['unitInventory'],
                            $line['unitUsage'],
                            $line['inventoryQty'],
                            $line['usageQty'],
                            $line['secondaryItem'],
                        ];
                    },
                    $fileName
                );

            //$response = Utilities::createFileResponse($filepath,'itemInventaire' . date('dmY_His') . ".csv");
            return $response;
        }

        $itemsInventory = $this->getDoctrine()->getRepository("Merchandise:ProductPurchased")->getInventoryItemsOrdered(
            $dataTableHeaders['criteria'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit']
        );
        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $itemsInventory['filtred'];
        $return['recordsTotal'] = $itemsInventory['total'];
        $return['data'] = $this->get('config.merchandise.service')->serializeInventoryItems($itemsInventory['list']);

        return new JsonResponse($return);
    }

    /**
     * @param Request $request
     * @param $download
     * @return JsonResponse
     *
     * @Route("/product_sold_list_export/{download}",name="product_sold_list_export", options={"expose"=true})
     */
    public function soldItemExportAction(Request $request, $download = 0)
    {
        $translator = $this->get('translator');
        $orders = array('codePlu', 'name', 'type', 'active');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);

        $dataTableHeaders['criteria']['inventory_item_search[keyword'] = $request->request->get('search')['value'];
        $dataTableHeaders['criteria']['search'] = $request->request->get('search')['value'];
        $dataTableHeaders['criteria']['restaurant'] = $this->get('restaurant.service')->getCurrentRestaurant();
        $dataTableHeaders['criteria']['locale'] = $request->getLocale();
        $download = intval($download);
        if (1 === $download) {
            $fileName = 'itemVente'.date('dmY_His');

            $result = [
                [
                    //                    'idSheet' => 'soldItems',
                    'title' => $translator->trans('label.sold_item'),
                    'header' => [
                        'ID',
                        $translator->trans('label.code'),
                        $translator->trans('label.name'),
                        $translator->trans('label.type'),
                        $translator->trans('label.active'),
                        $translator->trans('id_inventory_item'),
                    ],
                    'rendering' => function ($line) {
                        return [
                            $line['id'],
                            $line['codePlu'],
                            $line['name'],
                            $this->get('translator')->trans($line['type']),
                            $line['active'] ? $this->get('translator')->trans('keyword.yes') :
                                $this->get('translator')->trans('keyword.no'),
                            $line['inventoryItem'],
                        ];
                    },

                ],
                [
                    //                    'idSheet' => 'recipeLines',
                    'title' => $translator->trans('product_sold.labels.recettes'),
                    'header' => [
                        $translator->trans('id_recipe'),
                        $translator->trans('id_sold_item'),
                        $translator->trans('product_sold.detail.label.solding_canal'),
                        $translator->trans('code_inventory_item'),
                        $translator->trans('name_inventory_item'),
                        $translator->trans('keyword.quantite'),
                        $translator->trans('item.label.unit_usage'),
                        $translator->trans('price_of_line'),
                        $translator->trans('price_of_recipe'),
                        $translator->trans('id_recipe_line'),
                    ],
                    'rendering' => function ($line) {
                        return [
                            $line['recipeId'],
                            $line['soldItemId'],
                            $line['canal'],
                            $line['codeInventoryItem'],
                            $line['codeInventoryName'],
                            $line['qty'],
                            $this->get('translator')->trans($line['usageUnit']),
                            $line['linePrice'],
                            $line['recipePrice'],
                            $line['id'],
                        ];
                    },
                ],
            ];

            $response = $this->get('toolbox.document.generator')
                ->generateXlsFileMultipleSheet(
                    'product.sold.service',
                    ['getProductsSoldOrdered', 'getRecipeLinesOrdered'],
                    [
                        array(
                            'criteria' => $dataTableHeaders['criteria'],
                            'order' => $dataTableHeaders['orderBy'],
                            'onlyList' => true,
                            'asObject' => false,
                        ),
                        array(
                            'criteria' => $dataTableHeaders['criteria'],
                            'order' => $dataTableHeaders['orderBy'],
                            'onlyList' => true,
                        ),
                    ],
                    $translator->trans('product_sold.list.tile'),
                    $result,
                    $fileName
                );

            //$response = Utilities::createFileResponse($filepath, 'itemVente' . date('dmY_His') . ".csv");
            return $response;
        }

        $productSold = $this->getDoctrine()->getRepository("Merchandise:ProductSold")->getProductsSoldOrdered(
            $dataTableHeaders['criteria'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit']
        );

        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $productSold['filtred'];
        $return['recordsTotal'] = $productSold['total'];
        $return['data'] = $this->get('product.sold.service')->serializeProduct($productSold['list']);

        return new JsonResponse($return);
    }
}
