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
 * Class UnitNeedController
 *
 * @package            AppBundle\Merchandise\Controller
 * @Route("unit_need")
 */
class UnitNeedController extends Controller
{
    /**
     * @Route("/",name="find_unit_need", options={"expose"=true})
     */
    public function findUnitNeedsByNameAction(Request $request)
    {
        if ($request->getMethod() == 'GET') {
            $searchArray = [];
            $filters = [];
            $term = $request->get('term', null);
            $code = $request->get('code', null);
            $categoryId = $request->get('categoryId', null);
            if (!is_null($term) && $term != "null") {
                $searchArray['term'] = $term;
            }
            if (!is_null($code) && $code != "null") {
                $searchArray['code'] = $code;
            }
            if (!is_null($categoryId) && $categoryId != "null") {
                $filters['categoryId'] = $categoryId;
            }
            $unitNeeds = $this->getDoctrine()
                ->getManager()
                ->getRepository('Merchandise:UnitNeedProducts')
                ->findUnitNeed($searchArray, $filters);

            return new JsonResponse(
                [
                    'data' => [json_decode($this->get('serializer')->serialize($unitNeeds, 'json'))],
                ]
            );
        }
    }
}
