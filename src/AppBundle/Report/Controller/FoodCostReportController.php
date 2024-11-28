<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 01/04/2016
 * Time: 09:45
 */

namespace AppBundle\Report\Controller;

use AppBundle\General\Entity\ImportProgression;
use AppBundle\Report\Entity\MargeFoodCostRapport;
use AppBundle\Report\Entity\RapportTmp;
use AppBundle\Security\RightAnnotation;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class BuyingReportController
 *
 * @package                   AppBundle\Report\Controller
 * @Route("report/food_cost")
 */
class FoodCostReportController extends Controller
{

    /**
     * @RightAnnotation ("margin_food_cost_report")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/margin",name="margin_food_cost_report")
     */
    public function generateMarginFoodCostReportAction(Request $request)
    {
        $session = $this->get('session');
        if (!$request->isXmlHttpRequest()) {
            if ($request->getMethod() === "GET") {
                $beginDate = date("d/m/Y", strtotime("monday this week"));
                $endDate = date("d/m/Y", strtotime("sunday this week"));

                return $this->render(
                    "@Report/FoodCost/Margin/index_margin_report.html.twig",
                    [
                        'beginDate' => $beginDate,
                        'endDate' => $endDate,
                    ]
                );
            }
        } else {
            throw new MethodNotAllowedHttpException('Only http request are allowed');
        }
    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/launch_calcul_margin/{force}",name="margin_foodcost_launch_calcul", options={"expose"=true})
     * @Method("post")
     */
    public function launchCalculAction(Request $request, $force = 0)
    {
        $logger=$this->get('monolog.logger.generate_report');
        if ($request->getMethod() === "POST") {
            $errors = null;
            $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
            $currentRestaurantId = $currentRestaurant->getId();

            $startDate = date_create_from_format('d/m/Y', $request->request->get('beginDate'));
            $endDate = date_create_from_format('d/m/Y', $request->request->get('endDate'));
            $filter = $request->request->all();
            if ($startDate == null || $startDate == false) {
                $errors['firstDate'] = true;
            }
            if ($endDate == null || $endDate == false) {
                $errors['secondDate'] = true;
            }

            if ($startDate != false && $endDate != false) {
                if ($startDate->format('Y-m-d') > $endDate->format('Y-m-d')) {
                    $errors['compareDate'] = true;
                    $startDate = $startDate->format('d/m/Y');
                    $endDate = $endDate->format('d/m/Y');
                }
            }
            $lock = $this->get('report.foodcost.service')->checkLocked($currentRestaurantId);
            if ($lock->getValue() == 1) {
                $errors['locked'] = true;
                if ($startDate instanceof \DateTime) {
                    $startDate = $startDate->format('d/m/Y');
                }
                if ($endDate instanceof \DateTime) {
                    $endDate = $endDate->format('d/m/Y');
                }
            }
            if (sizeof($errors) > 0) {
                return new JsonResponse(
                    array(
                        'errors' => true,
                        'html' => $this->renderView(
                            "@Report/FoodCost/Margin/form_margin_food_cost.html.twig",
                            array(
                                'beginDate' => $startDate,
                                'endDate' => $endDate,
                                'filter' => $filter,
                                'errors' => $errors,
                            )
                        ),
                    )
                );
            }

            $progress = new ImportProgression();
            $this->getDoctrine()->getManager()->persist($progress);
            $progress->setProgress(0)
                ->setNature('foodcost_margin');

            $this->getDoctrine()->getManager()->flush();

            $rapport = new MargeFoodCostRapport();
            $rapport->setStartDate($startDate)
                ->setEndDate($endDate)
                ->setOriginRestaurant($currentRestaurant);
            $this->getDoctrine()->getManager()->persist($rapport);
            $this->getDoctrine()->getManager()->flush();

            $startDate = $startDate->format('Y-m-d');
            $endDate = $endDate->format('Y-m-d');
            $i=rand();
            $logger->addInfo('Generate report margin foodCost by '.$currentRestaurant->getCode().' from '.$startDate.' to '.$endDate.' '.$i);
            $t1 = time();
            $cmd = "report:marge:foodcost $currentRestaurantId $startDate  $endDate ".$progress->getId()." ".$force." ";
//            $t2 = time();
//            $logger->addInfo('Generate report margin foodCost finish | generate time = '. ($t2 - $t1) .'seconds by '.$currentRestaurant->getCode().' '.$i);
            $this->get('toolbox.command.launcher')->execute($cmd);
            $t2 = time();
            $logger->addInfo('Generate report margin foodCost finish | generate time = '. ($t2 - $t1) .'seconds by '.$currentRestaurant->getCode().' '.$i);
            return new JsonResponse(
                array(
                    'progressID' => $progress->getId(),
                    'tmpID' => $rapport->getId(),
                )
            );
        }
    }

    /**
     * @param RapportTmp $tmp
     * @return JsonResponse
     * @Route("/get_margin_result/{tmp}",name="margin_foodcost_get_result", options={"expose"=true})
     */
    public function getResultAction(RapportTmp $tmp)
    {

        $data = $this->get('report.foodcost.service')->formatResultMarginFoodCost($tmp);

        return new JsonResponse(
            array(
                'html' => $this->renderView(
                    "@Report/FoodCost/Margin/body_report_margin_foodcost.html.twig",
                    array(
                        'result' => $data['result'],
                    )
                ),
            )
        );
    }


    /**
     * @param MargeFoodCostRapport $tmp
     * @param $type
     * @return Response
     * @Route("/print/{tmp}/{type}",name="print_marge_food_cost",options={"expose"=true})
     */
    public function printAction(MargeFoodCostRapport $tmp, $type)
    {
        $data = $this->get('report.foodcost.service')->formatResultMarginFoodCost($tmp);

        if ($type === 'pdf') {
            $filename = "marge_food_cost_".date('Y_m_d_H_i_s').".pdf";
            $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                $filename,
                '@Report/FoodCost/Margin/report_margin_foodcost.html.twig',
                [
                    'result' => $data['result'],
                    'beginDate' => $tmp->getStartDate(),
                    'endDate' => $tmp->getEndDate(),
                ],
                [
                    'orientation' => 'Landscape',
                    'page-size' => 'A4',
                ]
            );

            return Utilities::createFileResponse($filepath, $filename);
        } else {
            if ($type === 'xls') {
                $filter['beginDate'] = $tmp->getStartDate()->format('Y-m-d');
                $filter['endDate'] = $tmp->getEndDate()->format('Y-m-d');
                $logoPath = $this->get('kernel')->getRootDir().'/../web/src/images/logo.png';
                $response = $this->get('report.foodcost.service')->generateExcelFile($filter, $data['result'], $logoPath);

                return $response;
            } else {
                $result = $this->get('report.foodcost.service')->serializeMarginFoodCostReportResult($data['result']);

                $filepath = $this->get('toolbox.document.generator')->getFilePathFromSerializedResult(
                    [
                        $this->get('translator')->trans('keyword.theorical'),
                        $this->get('translator')->trans('keyword.total'),
                        '%',
                        '',
                        $this->get('translator')->trans('keyword.real'),
                        $this->get('translator')->trans('keyword.total'),
                        '%',
                    ],
                    $result
                );
                $response = Utilities::createFileResponse($filepath, 'Marge_foodCost'.date('dmY_His').".csv");

                return $response;
            }
        }
    }
}
