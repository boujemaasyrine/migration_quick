<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 09/03/2016
 * Time: 10:45
 */

namespace AppBundle\Administration\Controller;

use AppBundle\Merchandise\Entity\CaPrev;
use AppBundle\Security\RightAnnotation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CaPrevController
 *
 * @Route("/ca_prev")
 */
class ConfigCaPrevController extends Controller
{

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @param Request $request
     *
     * @Route("/list",name="show_ca_prv_list")
     *
     * @RightAnnotation("show_ca_prv_list")
     */
    public function showAction(Request $request)
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        if ($request->isXmlHttpRequest()) {
            if (!$request->request->has('ca')) {
                return new JsonResponse(array('data' => true));
            }

            $listPost = $request->request->get('ca');

            foreach ($listPost as $key => $value) {
                $date = \DateTime::createFromFormat("Y-m-d", $key);
                $ca = $this->getDoctrine()->getRepository("Merchandise:CaPrev")->findOneBy(
                    array(
                        'date' => $date,
                        'originRestaurant' => $currentRestaurant,
                    )
                );
                $ca->setCa($value);
                $ca->setSynchronized(false);
                $this->getDoctrine()->getEntityManager()->flush($ca);
            }

            return new JsonResponse(array('data' => true));
        }

        return $this->render("@Administration/ca/list.html.twig");
    }

    /**
     * @return JsonResponse
     *
     * @param Request $request
     *
     * @Route("/json/list",name="ca_json_list",options={"expose"=true})
     */
    public function getCaDatesAction(Request $request)
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $minDate = $request->query->get('start');
        $minDate = \DateTime::createFromFormat('Y-m-d', $minDate);
        $maxDate = $request->query->get('end');
        $maxDate = \DateTime::createFromFormat('Y-m-d', $maxDate);

        //Create CA for non existing dates
        $nMin = $minDate->getTimestamp();
        $nMax = $maxDate->getTimestamp();

        for ($i = $nMin; $i <= $nMax; $i+=86400) {
            $date = new \DateTime();
            $date->setTimestamp($i);
            $this->get('ca_prev.service')->createIfNotExsit($date);
        }

        $ca = $this->getDoctrine()->getRepository("Merchandise:CaPrev")->getBetween(
            $minDate,
            $maxDate,
            $currentRestaurant
        );

        $data = [];
        foreach ($ca as $c) {
            $data[] = array(
                'fixed' => $c->getFixed(),
                'id' => $c->getId(),
                'ca' => $c->getCa(),
                'date' => $c->getDate()->format('Y-m-d'),
            );
        }

        return new JsonResponse($data);
    }

    /**
     * @param CaPrev $caPrev
     *
     * @return JsonResponse
     *
     * @Route("/details/{caPrev}",name="details_ca_prev",options={"expose"=true})
     */
    public function getDetails(CaPrev $caPrev)
    {
        //$currentRestaurant=$this->get('restaurant.service')->getCurrentRestaurant();
        $c = [];
        for ($i = 1; $i < 9; $i++) {
            $c[$i] = $this->get('ca_prev.service')->getCaForCaPrev($caPrev, $i);
        }

        $comparableDayCaObj = $this->getDoctrine()->getRepository("Financial:FinancialRevenue")
            ->findOneBy(
                array(
                    'date' => $caPrev->getComparableDay(),
                    'originRestaurant' => $caPrev->getOriginRestaurant(),
                )
            );

        if ($comparableDayCaObj) {
            $comparableDayCa = $comparableDayCaObj->getAmount();
        } else {
            $comparableDayCa = 0;
        }

        return new JsonResponse(
            array(
                'data' => $this->renderView(
                    "@Administration/ca/details.html.twig",
                    array(
                        'ca' => $caPrev,
                        'variance'=>$caPrev->getVariance(),
                        'values' => $c,
                        'comparable_day_ca' => $comparableDayCa,
                    )
                ),
            )
        );
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route("/json/refresh/",name="ca_refresh_data",options={"expose"=true})
     */
    public function refreshDataAction(Request $request)
    {

        if (!$request->request->has('date') || !$request->request->has('comparable_date')) {
            return new JsonResponse(array('data' => null));
        }

        $listDates = $request->request->get('date');
        $ca = new CaPrev();
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $ca->setOriginRestaurant($currentRestaurant);
        foreach ($listDates as $key => $d) {
            $method = 'setDate'.$key;
            $ca->$method(date_create_from_format('d/m/Y', $d));
        }

        $comparableDate = \DateTime::createFromFormat('d/m/Y', $request->request->get('comparable_date'));
        $ca->setComparableDay($comparableDate);

        $cas = $this->get('ca_prev.service')->calculateCa($ca);

        return new JsonResponse(
            array(
                'data' => array(
                    'ca' => $ca->getCa(),
                    'variance' => $ca->getVariance() * 100,
                    'cas' => $cas,
                ),
            )
        );
    }

    /**
     * @param Request $request
     * @param CaPrev $ca
     *
     * @return JsonResponse
     *
     * @Route("/save_ca/{ca}",name="save_ca", options={"expose"=true})
     */
    public function saveCaAction(Request $request, CaPrev $ca = null)
    {

        $permission = $this->get('app.security.checker')->check("bud_prev_edit");
        if (!$permission) {
            return new JsonResponse(
                array(
                    'data' => null,
                    'errors' => 'ACCESS DENIED',
                )
            );
        }

        if (!$ca) {
            return new JsonResponse(
                array(
                    'data' => null,
                    'errors' => 'CA PREV NOT FOUND',
                )
            );
        }

        if (!$request->request->has('date') || !$request->request->has('comparable_date')) {
            return new JsonResponse(
                array(
                    'data' => null,
                    'errors' => 'EXPECT DATES IN POST',
                )
            );
        }

        if (!$request->request->has('ca_typed')) {
            return new JsonResponse(
                array(
                    'data' => null,
                    'errors' => 'CA TYPED NOT FOUND IN POST',
                )
            );
        }

        if (preg_match('/^[0-9]+([,\.]?[0-9]+)?$/', trim($request->request->get('ca_typed'))) == 0) {
            return new JsonResponse(
                array(
                    'data' => null,
                    'errors' => 'CA TYPED IS NOT A NUMERIC',
                )
            );
        }


        if (!$request->request->has('ca_to_be_sended')) {
            return new JsonResponse(
                array(
                    'data' => null,
                    'errors' => 'CA TO BE SEND NOT FOUND',
                )
            );
        }

        //Let's begin work :) :)
        $listDates = $request->request->get('date');
        foreach ($listDates as $key => $d) {
            $method = 'setDate'.$key;
            $ca->$method(date_create_from_format('d/m/Y', $d));
        }

        $comparableDate = \DateTime::createFromFormat('d/m/Y', $request->request->get('comparable_date'));
        $ca->setComparableDay($comparableDate);
        $ca->setOriginRestaurant($this->get('restaurant.service')->getCurrentRestaurant());

        $this->get('ca_prev.service')->calculateCa($ca);

        if ($request->request->get('ca_to_be_sended') == 'typed') {
            $caTyped=floatval($request->request->get('ca_typed'));
            $ca->setCa($caTyped);
            $comparableDayCaObj = $this->getDoctrine()->getRepository("Financial:FinancialRevenue")
                ->findOneBy(
                    array(
                        'date' => $ca->getComparableDay(),
                        'originRestaurant' => $ca->getOriginRestaurant(),
                    )
                );

            if ($comparableDayCaObj) {
                $comparableDayCa = $comparableDayCaObj->getAmount();
            } else {
                $comparableDayCa = 0;
            }
            $variance=($caTyped -$comparableDayCa ) / $comparableDayCa;
            $ca->setVariance($variance);
            $ca->setIsTyped(true);
        } else {
            $ca->setIsTyped(false);
        }

        $ca->setFixed(true);
        $ca->setSynchronized(false);

        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(
            array(
                'data' => true,
            )
        );
    }

    /**
     * @param Request $request
     * @param CaPrev $ca
     *
     * @return JsonResponse
     *
     * @Route("/save_single_ca/{ca}",name="save_single_ca", options={"expose"=true})
     */
    public function saveSingleCaAction(Request $request, CaPrev $ca = null)
    {

        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $permission = $this->get('app.security.checker')->check("bud_prev_edit");
        if (!$permission) {
            return new JsonResponse(
                array(
                    'data' => null,
                    'errors' => 'ACCESS DENIED',
                )
            );
        }

        if (!$ca) {
            return new JsonResponse(
                array(
                    'data' => null,
                    'errors' => 'CA PREV NOT FOUND',
                )
            );
        }

        if (!$request->request->has('ca')) {
            return new JsonResponse(
                array(
                    'data' => null,
                    'errors' => 'CA NOT FOUND IN POST',
                )
            );
        }

        if (preg_match('/^[0-9]+([,\.]?[0-9]+)?$/', trim($request->request->get('ca'))) == 0) {
            return new JsonResponse(
                array(
                    'data' => null,
                    'errors' => 'CA TYPED IS NOT A NUMERIC',
                )
            );
        }

        $ca->setCa($request->request->get('ca'));
        $ca->setFixed(true);
        $ca->setIsTyped(true);
        $ca->setSynchronized(false);
        $ca->setOriginRestaurant($currentRestaurant);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(
            array(
                'data' => true,
            )
        );
    }


    /**
     * @return JsonResponse
     *
     * @Route("/no_comparable_days",name="no_comparable_days",options={"expose"=true})
     */
    public function noComparableDaysAction()
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $days = $this->getDoctrine()->getRepository("Financial:AdministrativeClosing")->findBy(
            array(
                'comparable' => false,
                'originRestaurant'=> $currentRestaurant
            )
        );

        $result = [];
        foreach ($days as $d) {
            $result[] = [
                'date' => $d->getDate()->format('d/m/Y'),
                'comment' => $d->getComment(),
            ];
        };

        return new JsonResponse(
            array(
                'data' => $result,
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/export/{dates}{schedule}",name="export_ca_prv_list",options={"expose"=true})
     * @param $dates
     * @param $schedule
     */
    public function exportBudgetAction($dates,$schedule){
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $daysWeek = explode(',', $dates);
        $locale = $this->container->get('request_stack')->getCurrentRequest()->getLocale();
        $result=array();

        foreach ($daysWeek as $day){
            $date=new \DateTime($day);
            $dateString=$date->format('d-m-Y');
            $interval=array("from"=>$dateString,"to"=>$dateString);
            if($schedule>0){
                $caPrev = $this->get('report.sales.service')->getCaPrevPerHalfOrQuarterHour($interval, $currentRestaurant, $schedule);
            }else{
                $caPrev = $this->get('report.sales.service')->getCaPrevPerHour($interval, $currentRestaurant);
            }
            $result[$dateString]=$caPrev;
        }
        $response = $this->get('ca_prev.service')->createExcelFileForCaPrev($result,$schedule,$locale);
        return $response;


    }

}
