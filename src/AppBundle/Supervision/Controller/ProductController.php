<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/12/2015
 * Time: 10:54
 */

namespace AppBundle\Supervision\Controller;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\Supervision\Entity\ProductSupervision;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Class ProductController
 *
 * @package          AppBundle\Controller
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
            if ($selectedType === ProductSupervision::ARTICLE) {
                $products = $manager
                    ->getRepository(ProductPurchasedSupervision::class)
                    ->findProductSupervision($searchArray, $filters);
            } elseif ($selectedType === ProductSupervision::FINALPRODUCT) {
                $products = $manager
                    ->getRepository(ProductSoldSupervision::class)
                    ->findProductSupervision($searchArray, $filters);
            }


            return new JsonResponse(
                [
                    'data' => [json_decode($this->get('serializer')->serialize($products, 'json'))],
                ]
            );
        }
    }
}
