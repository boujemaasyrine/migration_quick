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
use AppBundle\Merchandise\Form\InventorySheet\InventorySheetType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Class CategoryController
 *
 * @package           AppBundle\Merchandise\Controller
 * @Route("category")
 */
class CategoryController extends Controller
{

    /**
     * @Route("/",name="search_in_all_categories", options={"expose"=true})
     */
    public function getAllCategoriesAction(Request $request)
    {
        $term = $request->get('term', null);
        if ($request->getMethod() == 'GET') {
            $categories = $this->getDoctrine()->getManager()->getRepository(
                'Merchandise:ProductCategories'
            )->getArrayOfCategoryNamesWithIdByTerm($term);

            return new JsonResponse(
                [
                    'data' => [json_decode($this->get('serializer')->serialize($categories, 'json'))],
                ]
            );
        }
    }
}
