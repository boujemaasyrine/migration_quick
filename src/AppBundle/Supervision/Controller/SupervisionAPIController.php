<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 11/03/2016
 * Time: 12:07
 */

namespace AppBundle\Supervision\Controller;

use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\Supervision\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class OrderAPIController
 *
 * @package               AppBundle\Controller
 * @Route("/json/config")
 */
class SupervisionAPIController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/supplier_list_export/{download}",name="supplier_list_export", options={"expose"=true})
     */
    public function SupplierListAction(Request $request, $download = false)
    {
        $orders = array('code', 'supplier', 'designation', 'address', 'phone', 'mail');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);

        $download = intval($download);
        if ($download === 1) {
            $fileName = $this->get('translator')->trans('keyword.suppliers', [], "supervision").date('dmY_His');
            $response = $this->get('supervision.document.generator')
                ->generateXlsFile(
                    'supplier.service',
                    'getSuppliers',
                    array(
                        'criteria' => $dataTableHeaders['criteria'],
                        'order' => $dataTableHeaders['orderBy'],
                        'onlyList' => true,
                    ),
                    [
                        $this->get('translator')->trans('label.code', [], "supervision"),
                        $this->get('translator')->trans('filter.supplier', [], "supervision"),
                        $this->get('translator')->trans('provider.list.designation', [], "supervision"),
                        $this->get('translator')->trans('label.address', [], "supervision"),
                        $this->get('translator')->trans('label.phone', [], "supervision"),
                        $this->get('translator')->trans('label.mail', [], "supervision"),
                    ],
                    function ($line) {
                        return [
                            $line['code'],
                            $line['name'],
                            $line['designation'],
                            $line['address'],
                            $line['phone'],
                            $line['mail'],
                        ];
                    },
                    $fileName
                );

            //$response = Utilities::createFileResponse($filepath, 'fournisseur' . date('dmY_His') . ".csv");
            return $response;
        }

        $suppliers = $this->getDoctrine()->getRepository(Supplier::class)->getSupplierOrderedForSupervision(
            $dataTableHeaders['criteria'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit']
        );
        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $suppliers['filtred'];
        $return['recordsTotal'] = $suppliers['total'];
        $return['data'] = $this->get('supplier.service')->serializeSuppliers($suppliers['list']);

        return new JsonResponse($return);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/restaurant_list_export",name="supervision_restaurant_list_export", options={"expose"=true})
     */
    public function RestaurantListExportAction(Request $request)
    {
        $orders = array('code', 'name', 'email', 'manager', 'adress', 'phone', 'type');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);

        $dataTableHeaders['criteria']['restaurant[keyword'] = $request->request->get('search')['value'];

        $fileName = 'liste_restaurants_'.date('dmY_His');

        $response = $this->get('supervision.document.generator')
            ->generateXlsFile(
                'supervision.restaurant.service',
                'getRestaurants',
                array(
                    'criteria' => $dataTableHeaders['criteria'],
                    'order' => $dataTableHeaders['orderBy'],
                ),
                [
                    $this->get('translator')->trans('label.code'),
                    $this->get('translator')->trans('label.name'),
                    $this->get('translator')->trans('label.mail'),
                    $this->get('translator')->trans('label.manager'),
                    $this->get('translator')->trans('label.address'),
                    $this->get('translator')->trans('label.phone'),
                    $this->get('translator')->trans('label.type'),
                    $this->get('translator')->trans('keyword.suppliers', [], 'supervision'),
                ],
                function ($line) {
                    return [
                        $line['code'],
                        $line['name'],
                        $line['email'],
                        $line['manager'],
                        $line['adress'],
                        $line['phone'],
                        $line['type'],
                        $line['restaurantSuppliers'],
                    ];
                },
                $fileName
            );

        //$response = Utilities::createFileResponse($filepath, 'liste_restaurants_' . date('dmY_His') . ".csv");
        return $response;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/categories_list_export",name="categories_list_export", options={"expose"=true})
     */
    public function CategoriesListExportAction(Request $request)
    {

        $orders = array('ref', 'name', 'group', 'tvaBel', 'tvaLux');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);

        $fileName = 'categorie'.date('dmY_His');

        $response = $this->get('supervision.document.generator')
            ->generateXlsFile(
                'category.service',
                'getCategories',
                array(
                    'criteria' => $dataTableHeaders['criteria'],
                    'order' => $dataTableHeaders['orderBy'],
                ),
                [
                    $this->get('translator')->trans('keyword.label'),
                    $this->get('translator')->trans('label.group'),
                    $this->get('translator')->trans('category.list.tvaBel', [], 'supervision'),
                    $this->get('translator')->trans('category.list.tvaLux', [], 'supervision'),
                ],
                null,
                $fileName
            );

        //$response = Utilities::createFileResponse($filepath, 'categorie' . date('dmY_His') . ".csv");
        return $response;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/groups_list_export",name="groups_list_export", options={"expose"=true})
     */
    public function GroupsListExportAction(Request $request)
    {

        $orders = array('ref', 'name');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);

        $fileName = 'groups'.date('dmY_His');

        $response = $this->get('supervision.document.generator')
            ->generateXlsFile(
                'group.service',
                'getGroups',
                array(
                    'criteria' => $dataTableHeaders['criteria'],
                    'order' => $dataTableHeaders['orderBy'],
                ),
                [
                    $this->get('translator')->trans('label.reference'),
                    $this->get('translator')->trans('keyword.label').' FR',
                    $this->get('translator')->trans('keyword.label').' NL',
                ],
                function ($line) {
                    return [
                        $line['Ref'],
                        $line['nameFr'],
                        $line['nameNl'],

                    ];
                },
                $fileName
            );

        //$response = Utilities::createFileResponse($filepath, 'groups' . date('dmY_His') . ".csv");
        return $response;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/inventory_item_list_export/{download}",name="inventory_item_list_export", options={"expose"=true})
     */
    public function InventoryItemExportAction(Request $request, $download = 0)
    {

        $translator = $this->get('translator');
        $orders = array('code', 'name', 'buyingCost', 'status', 'dateSynchro', 'lastDateSynchro');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);

        $dataTableHeaders['criteria']['inventory_item_search[keyword'] = $request->request->get('search')['value'];
        $dataTableHeaders['criteria']['locale'] = $request->getLocale();

        $download = intval($download);
        if ($download === 1) {
            $fileName = 'itemInventaire'.date('dmY_His');
            $response = $this->get('supervision.document.generator')
                ->generateXlsFile(
                    'items.service',
                    'getInventoryItems',
                    array(
                        'criteria' => $dataTableHeaders['criteria'],
                        'order' => $dataTableHeaders['orderBy'],
                        'onlyList' => true,
                    ),
                    [
                        $translator->trans('keyword.code', [], "supervision"),
                        $translator->trans('label.name', [], "supervision")." FR",
                        $translator->trans('label.name', [], "supervision")." NL",
                        $translator->trans('item.label.buying_cost', [], "supervision"),
                        $translator->trans('item.label.category', [], "supervision"),
                        $translator->trans('item.label.status', [], "supervision"),
                        $translator->trans('item.inventory.deactivateDate', [], "supervision"),
                        $translator->trans('item.label.unit_expedition', [], "supervision"),
                        $translator->trans('item.label.unit_inventory', [], "supervision"),
                        $translator->trans('item.label.unit_usage', [], "supervision"),
                        $translator->trans('item.label.inventory_qty', [], "supervision"),
                        $translator->trans('item.label.usage_qty', [], "supervision"),
                        $translator->trans('item.inventory.secondary_product', [], "supervision"),
                        $translator->trans('synchronisation_date', [], "supervision"),
                        $translator->trans('last_synchronisation_date', [], "supervision"),
                        $translator->trans('users.list.eligible_restaurant', [], "supervision"),
                    ],
                    function ($line) {
                        return [
                            $line['code'],
                            $line['nameFr'],
                            $line['nameNl'],
                            $line['buyingCost'],
                            $line['category'],
                            $line['status'],
                            $line['deactivationDate'],
                            $line['unitExpedition'],
                            $line['unitInventory'],
                            $line['unitUsage'],
                            $line['inventoryQty'],
                            $line['usageQty'],
                            $line['secondaryItem'],
                            $line['dateSynchro'],
                            $line['lastDateSynchro'],
                            $line['restaurantsAsString'],
                        ];
                    },
                    $fileName
                );

            //$response = Utilities::createFileResponse($filepath, 'itemInventaire' . date('dmY_His') . ".csv");
            return $response;
        }

        $itemsInventory = $this->getDoctrine()->getRepository(
            ProductPurchasedSupervision::class
        )->getSupervisonInventoryItemsOrdered(
            $dataTableHeaders['criteria'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit']
        );

        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $itemsInventory['filtred'];
        $return['recordsTotal'] = $itemsInventory['total'];
        $return['data'] = $this->get('items.service')->serializeInventoryItems($itemsInventory['list']);
        $response = new JsonResponse($return);

        return $response;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/product_sold_list_export/{download}",name="supervision_product_sold_list_export", options={"expose"=true})
     */
    public function SoldItemExportAction(Request $request, $download = 0)
    {
        $translator = $this->get('translator');
        $orders = array('codePlu', 'name', 'type', 'active', 'dateSynchro', 'lastDateSynchro','venteAnnexe');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);

        $dataTableHeaders['criteria']['inventory_item_search[keyword'] = $request->request->get('search')['value'];
        $dataTableHeaders['criteria']['search'] = $request->request->get('search')['value'];
        $dataTableHeaders['criteria']['locale'] = $request->getLocale();

        $download = intval($download);
        if ($download === 1) {
            $fileName = 'itemVente'.date('dmY_His');
             $result = [
                [
                    'title' => $translator->trans('labels.sold_item', [], "supervision"),
                    'header' => [
                        'ID',
                        $translator->trans('label.code', [], "supervision"),
                        $translator->trans('label.name', [], "supervision"),
                        $translator->trans('label.type', [], "supervision"),
                        $translator->trans('synchronisation_date', [], "supervision"),
                        $translator->trans('last_synchronisation_date', [], "supervision"),
                        $translator->trans('users.list.eligible_restaurant', [], "supervision"),
                        $translator->trans('label.active', [], "supervision"),
                        $translator->trans('id_inventory_item', [], "supervision"),
                        $translator->trans('code_inventory_item', [], "supervision"),
                        $translator->trans('name_inventory_item', [], "supervision"),
                        $translator->trans('product_sold.vente_annexe', [], "supervision")
                    ],
                    'rendering' => function ($line) {
                        return [
                            $line['id'],
                            $line['codePlu'],
                            $line['name'],
                            $this->get('translator')->trans($line['type'], [], "supervision"),
                            $line['dateSynchro'],
                            $line['lastDateSynchro'],
                            $line['restaurantsAsString'],
                            $line['active'] ? $this->get('translator')->trans('keyword.yes', [], "supervision") :
                                $this->get('translator')->trans('keyword.no', [], "supervision"),
                            $line['inventoryItem'],
                            $line['inventoryItemCode'],
                            $line['inventoryItemName'],
                            $line['venteAnnexe'] ? $this->get('translator')->trans('keyword.yes', [], "supervision") :
                                $this->get('translator')->trans('keyword.no', [], "supervision"),
                        ];
                    },

                ],
                [
                    'title' => $translator->trans('product_sold.labels.recettes'),
                    'header' => [
                        $translator->trans('id_recipe', [], "supervision"),
                        $translator->trans('id_sold_item', [], "supervision"),
                        $translator->trans('label.code_plu', [], "supervision"),
                        $translator->trans('product_sold.labels.name', [], "supervision"),
                        $translator->trans('product_sold.labels.solding_canal', [], "supervision"),
                        $translator->trans('product_sold.labels.sub_solding_canal', [], "supervision"),
                        $translator->trans('code_inventory_item', [], "supervision"),
                        $translator->trans('name_inventory_item', [], "supervision"),
                        $translator->trans('keywords.quantite', [], "supervision"),
                        $translator->trans('item.label.unit_usage', [], "supervision"),
                        $translator->trans('price_of_line', [], "supervision"),
                        $translator->trans('price_of_recipe', [], "supervision"),
                        $translator->trans('id_recipe_line', [], "supervision"),
                    ],
                    'rendering' => function ($line) {
                        return [
                            $line['recipeId'],
                            $line['soldItemId'],
                            $line['soldItemPlu'],
                            $line['soldItemName'],
                            $line['canal'],
                            $line['sous_canal'],
                            $line['codeInventoryName'],
                            $line['qty'],
                            $this->get('translator')->trans($line['usageUnit'], [], "supervision"),
                            $line['linePrice'],
                            $line['recipePrice'],
                            $line['id'],
                        ];
                    },
                ],
            ];
            $response = $this->get('supervision.document.generator')
                ->generateXlsFileMultipleSheet(
                    'supervision.product.sold.service',
                    ['getProductsSoldOrdered', 'getRecipeLinesOrdered'],
                    [
                        array(
                            'criteria' => $dataTableHeaders['criteria'],
                            'order' => $dataTableHeaders['orderBy'],
                            'onlyList' => true,
                        ),
                        array(
                            'criteria' => $dataTableHeaders['criteria'],
                            'order' => $dataTableHeaders['orderBy'],
                            'onlyList' => true,
                        ),
                    ],
                    $result,
                    $fileName
                );

            //$response = Utilities::createFileResponse($filepath, 'itemVente' . date('dmY_His') . ".csv");
            return $response;
        }
        $productSold = $this->getDoctrine()->getRepository(ProductSoldSupervision::class)->getProductsSoldOrdered(
            $dataTableHeaders['criteria'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit']
        );
        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $productSold['filtred'];
        $return['recordsTotal'] = $productSold['total'];
        $return['data'] = $this->get('supervision.product.sold.service')->serializeProduct(
            $productSold['list'],
            'json'
        );
        return new JsonResponse($return);
    }
    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/roles_list_export",name="roles_list_export", options={"expose"=true})
     */
    public function RolesListExportAction(Request $request)
    {
        $orders = array('label', 'type',);
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);

        $dataTableHeaders['criteria']['role[keyword'] = $request->request->get('search')['value'];

        $fileName = 'liste_roles_'.date('dmY_His');

        $response = $this->get('supervision.document.generator')
            ->generateXlsFile(
                'users.service',
                'getRoles',
                array(
                    'criteria' => $dataTableHeaders['criteria'],
                    'order' => $dataTableHeaders['orderBy'],
                ),
                [
                    $this->get('translator')->trans('keyword.label'),
                    $this->get('translator')->trans('label.type'),
                ],
                function ($line) {
                    return [
                        $line['label'],
                        $line['type'],

                    ];
                },
                $fileName
            );

        //$response = Utilities::createFileResponse($filepath, 'liste_roles_' . date('dmY_His') . ".csv");
        return $response;
    }
}
