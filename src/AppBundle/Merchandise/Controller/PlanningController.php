<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 25/02/2016
 * Time: 09:51
 */

namespace AppBundle\Merchandise\Controller;

use AppBundle\Merchandise\Entity\Supplier;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PlanningController
 *
 * @package            AppBundle\Merchandise\Controller
 * @Route("/planning")
 */
class PlanningController extends Controller
{


    /**
     * @RightAnnotation("planning_suppliers")
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/planning_suppliers",name="planning_suppliers")
     */
    public function planningAction(Request $request)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $suppliers = $this->getDoctrine()
            ->getRepository(Supplier::class)
            ->createQueryBuilder("s")
            ->join("s.restaurants", "r")
            ->where("r = :currentRestaurant")
            ->setParameter("currentRestaurant", $currentRestaurant)
            ->getQuery()
            ->getResult();

        return $this->render(
            "@Merchandise/Order/planning.html.twig",
            array(
                'suppliers' => $suppliers,
            )
        );
    }


    /**
     * @param $date
     * @return JsonResponse
     * @Route("/get_ca/{date}",name="get_ca_for_date",options={"expose"=true})
     */
    public function getCaForDate($date)
    {
        $ca = $this->getDoctrine()->getRepository("Merchandise:CaPrev")->findOneBy(
            array(
                "date" => date_create_from_format("d-m-Y", $date),
            )
        );

        if ($ca === null) {
            return new JsonResponse(
                array(
                    "data" => null,
                )
            );
        }

        return new JsonResponse(
            array(
                "data" => $ca->getCa(),
            )
        );
    }
}
