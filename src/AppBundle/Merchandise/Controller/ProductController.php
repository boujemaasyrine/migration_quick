<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/12/2015
 * Time: 10:54
 */

namespace AppBundle\Merchandise\Controller;

use AppBundle\Merchandise\Entity\InventoryLine;
use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Form\InventorySheet\InventorySheetType;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Class ProductController
 *
 * @package          AppBundle\Merchandise\Controller
 * @Route("product")
 */
class ProductController extends Controller
{
    /**
     * @Route("/",name="find_products", options={"expose"=true})
     */
    public function findProductsAction(Request $request)
    {
        $manager = $this->getDoctrine()->getManager();
        if ($request->getMethod() == 'GET') {
            $searchArray = [];
            $filters = [];
            $term = $request->get('term', null);
            $code = $request->get('code', null);
            $categoryId = $request->get('categoryId', null);
            $selectedType = $request->get('selectedType', null);
            if (!is_null($term) && $term != "null") {
                $searchArray['term'] = $term;
            }
            if (!is_null($code) && $code != "null") {
                $searchArray['code'] = $code;
            }
            if (!is_null($categoryId) && $categoryId != "null") {
                $filters['categoryId'] = $categoryId;
            }
            $products = [];
            if (is_null($selectedType) || $selectedType === "null") {
                $products = $manager
                    ->getRepository('Merchandise:Product')
                    ->findProduct($searchArray, $filters);
            } elseif ($selectedType === SheetModel::ARTICLE) {
                $products = $manager
                    ->getRepository('Merchandise:ProductPurchased')
                    ->findProduct($searchArray, $filters);
            } elseif ($selectedType === SheetModel::FINALPRODUCT) {
                $products = $manager
                    ->getRepository('Merchandise:ProductSold')
                    ->findProduct($searchArray, $filters);
            } elseif ($selectedType == SheetModel::UNIT_NEED) {
                $products = $manager
                    ->getRepository('Merchandise:UnitNeedProducts')
                    ->findUnitNeed($searchArray, $filters);
            }

            return new JsonResponse(
                [
                    'data' => [json_decode($this->get('serializer')->serialize($products, 'json'))],
                ]
            );
        }
    }

    /**
     * @Route("/find_active_products",name="find_active_products", options={"expose"=true})
     */
    public function findActiveProductsAction(Request $request)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $manager = $this->getDoctrine()->getManager();
        $locale = $request->getLocale();
        if ($request->getMethod() == 'GET') {
            $searchArray = [];
            $filters = [];
            $filters['locale']=$locale;
            $term = $request->get('term', null);
            $code = $request->get('code', null);
            $categoryId = $request->get('categoryId', null);
            $selectedType = $request->get('selectedType', null);
            $filterSecondary = $request->get('filterSecondary', null);
            if (!is_null($term) && $term != "null") {
                $searchArray['term'] = $term;
            }
            if (!is_null($code) && $code != "null") {
                $searchArray['code'] = $code;
            }
            if (!is_null($categoryId) && $categoryId != "null") {
                $filters['categoryId'] = $categoryId;
            }
            if (!is_null($filterSecondary) && $filterSecondary != "null") {
                $filters['filterSecondary'] = true;
            }
            $products = [];
            if (is_null($selectedType) || $selectedType === "null") {
                $products = $manager
                    ->getRepository('Merchandise:Product')
                    ->findProduct($restaurant, $searchArray, $filters, true);
            } elseif ($selectedType === SheetModel::ARTICLE) {
                $products = $manager
                    ->getRepository('Merchandise:ProductPurchased')
                    ->findProduct($restaurant, $searchArray, $filters, true);
            } elseif ($selectedType === SheetModel::FINALPRODUCT) {
                $products = $manager
                    ->getRepository('Merchandise:ProductSold')
                    ->findProduct($restaurant, $searchArray, $filters, true);
            }

            return new JsonResponse(
                [
                    'data' => [json_decode($this->get('serializer')->serialize($products, 'json'))],
                ]
            );
        }
    }

    /**
     * @return JsonResponse
     * @Route("/last_qty/{product}",name="last_product_qty",options={"expose"=true})
     */
    public function getLastProductQty(ProductPurchased $product)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $lastADay = $this->getDoctrine()->getRepository("Financial:AdministrativeClosing")->findOneBy(
            ["originRestaurant"=>$restaurant],
            ["date" => "desc"]
        );
        if ($lastADay == null) {
            $lastADay = Utilities::getDateFromDate(new \DateTime(), -1);
        } else {
            $lastADay = $lastADay->getDate();
        }
        $stock = $this->get('product.service')->getStockForProductInDate($product, $lastADay);

        $data = array(
            'qty' => (int) $stock['stock'],
            'type' => ($stock['isRealStock'] == true) ? 'real' : 'theory',
            'inv_unit_label' => $product->getLabelUnitInventory(),
        );

        return new JsonResponse(
            array(
                'data' => $data,
            )
        );
    }

    /**
     * @param $p
     * @param $date
     * @param bool|false $code
     * @return Response
     * @throws \Exception
     * This method is for TEST
     * @Route("/stock_product/{p}/{date}/{code}")
     */
    public function getStockForProductFORDEV($p, $date, $code = null)
    {

        /**
         * This method is for TEST
         */

        if ($code) {
            $product = $this->getDoctrine()->getRepository("Merchandise:ProductPurchased")->find($p);
        } else {
            $product = $this->getDoctrine()->getRepository("Merchandise:ProductPurchased")->findOneBy(
                array(
                    'externalId' => $p,
                )
            );
        }

        //dump($this->get('product.service')->serializeProduct($product));
        //dump($this->get('product.service')->getStockForProductInDate($product,date_create_from_format('Y-m-d',$date)));
        die;

        return new Response('');
    }

    /**
     * @param ProductPurchased $productPurchased
     * @param $date
     * @return Response
     * @Route("/test_historic_product/{productPurchased}/{date}")
     */
    public function testGetHistoricProductAction(ProductPurchased $productPurchased, $date)
    {

        //dump($this->get('product.service')->getHistoricProduct($productPurchased,date_create_from_format('Y-m-d',$date)));

        return new Response('');
    }

    /**
     * @param ProductPurchased $productPurchased
     * @param $date1
     * @param $date2
     * @return Response
     * @Route("/test_consomation_product/{productPurchased}/{date1}/{date2}")
     */
    public function testConsomationProductAction(ProductPurchased $productPurchased, $date1, $date2)
    {

        $data = $this->get('product.service')
            ->getConsomationFormProduct(
                $productPurchased,
                date_create_from_format('Y-m-d', $date1),
                date_create_from_format('Y-m-d', $date2)
            );

        //dump($data);
        die;
    }
}
