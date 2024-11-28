<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 29/05/2016
 * Time: 18:22
 */

namespace AppBundle\Supervision\Controller\Reports;

use AppBundle\General\Entity\ImportProgression;
use AppBundle\Report\Entity\MargeFoodCostRapport;
use AppBundle\Report\Entity\RapportTmp;
use AppBundle\Supervision\Form\Reports\MarginFoodCostType;
use AppBundle\Supervision\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use AppBundle\Security\RightAnnotation;

/**
 * Class HourByHourController
 *
 * @package         AppBundle\Controller\Reports
 * @Route("report")
 */
class MarginFoodCostController extends Controller
{

    /**
     * @RightAnnotation("margin_food_cost_report")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/margin",name="supervision_margin_food_cost_report")
     */
    public function generateMarginFoodCostReportAction(Request $request)
    {
        $data['beginDate'] = new \DateTime('Monday this week');
        $data['endDate'] = new \DateTime('Sunday this week');
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $form = $this->createForm(MarginFoodCostType::class, $data, ['user' => $user]);

        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $result = $this->get('supervision.report.margin.foodcost.service')->getMarginFoodCostResult(
                    $form->getData()
                );
                if (is_null($request->get('download', null)) && is_null($request->get('export', null))) {
                    return $this->render(
                        '@Supervision/Reports/Merchandise/MarginFoodCost/index.html.twig',
                        [
                            "generated" => true,
                            'form' => $form->createView(),
                            'result' => $result,
                        ]
                    );
                } else {
                    if (is_null($request->get('export', null))) {
                        $filename = "marge_food_cost_".date('Y_m_d_H_i_s').".pdf";
                        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                            $filename,
                            '@Supervision/Reports/Merchandise/MarginFoodCost/report.html.twig',
                            [
                                "download" => true,
                                'result' => $result,
                                'data' => $form->getData(),
                            ],
                            [
                                'orientation' => 'Landscape',
                            ]
                        );

                        return Utilities::createFileResponse($filepath, $filename);
                    } else {
                        $result = $this->get(
                            'supervision.report.margin.foodcost.service'
                        )->serializeMarginFoodCostReportResult($result);

                        $filepath = $this->get('toolbox.document.generator')->getFilePathFromSerializedResult(
                            [
                                $this->get('translator')->trans('keywords.theorical', [], 'supervision'),
                                $this->get('translator')->trans('keyword.total', [], 'supervision'),
                                '%',
                                '',
                                $this->get('translator')->trans('keywords.real', [], 'supervision'),
                                $this->get('translator')->trans('keyword.total', [], 'supervision'),
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

        return $this->render(
            "@Supervision/Reports/Merchandise/MarginFoodCost/index.html.twig",
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/launch_calcul_margin/{force}",name="supervision_margin_foodcost_launch_calcul", options={"expose"=true})
     * @Method("post")
     */
    public function launchCalculAction(Request $request, $force = 0)
    {
        $data['beginDate'] = new \DateTime('Monday this week');
        $data['endDate'] = new \DateTime('Sunday this week');
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $form = $this->createForm(MarginFoodCostType::class, $data, ['user' => $user]);
        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $errors = [];
                $lock = $this->get('supervision.report.margin.foodcost.service')->checkLocked();
                if ($lock->getValue() == 1) {
                    $errors['locked'] = true;
                }

                if (sizeof($errors) > 0) {
                    return new JsonResponse(
                        array(
                            'errors' => true,
                            'html' => $this->renderView(
                                "@Supervision/Reports/Merchandise/MarginFoodCost/form_margin_food_cost.html.twig",
                                array(
                                    'errors' => $errors,
                                    'form' => $form->createView(),
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
                $rapport->setStartDate($form->getData()['beginDate'])
                    ->setEndDate($form->getData()['endDate']);
                $rapport->setOriginRestaurant($form->getData()['restaurant']);
                $this->getDoctrine()->getManager()->persist($rapport);
                $this->getDoctrine()->getManager()->flush();

                $startDate = $form->getData()['beginDate']->format('Y-m-d');
                $endDate = $form->getData()['endDate']->format('Y-m-d');
                $restaurant = $form->getData()['restaurant']->getId();

                $cmd = "supervision:report:marge:foodcost $restaurant $startDate  $endDate ".$progress->getId(
                )." ".$force." > file.log";

                $this->get('toolbox.command.launcher')->execute($cmd);

                return new JsonResponse(
                    array(
                        'progressID' => $progress->getId(),
                        'tmpID' => $rapport->getId(),
                    )
                );
            } else {
                return new JsonResponse(
                    array(
                        'errors' => true,
                        'html' => $this->renderView(
                            "@Supervision/Reports/Merchandise/MarginFoodCost/form_margin_food_cost.html.twig",
                            array(
                                'form' => $form->createView(),
                            )
                        ),
                    )
                );
            }
        }
    }

    /**
     * @param RapportTmp $tmp
     * @return JsonResponse
     * @Route("/get_margin_result/{tmp}",name="supervision_margin_foodcost_get_result", options={"expose"=true})
     */
    public function getResultAction(RapportTmp $tmp)
    {

        $data = $this->get('supervision.report.margin.foodcost.service')->formatResultMarginFoodCost($tmp);

        return new JsonResponse(
            array(
                'html' => $this->renderView(
                    "@Supervision/Reports/Merchandise/MarginFoodCost/body.html.twig",
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
     * @Route("/print/{tmp}/{type}",name="supervision_print_marge_food_cost",options={"expose"=true})
     */
    public function printAction(MargeFoodCostRapport $tmp, $type)
    {
        $data = $this->get('supervision.report.margin.foodcost.service')->formatResultMarginFoodCost($tmp);

        if ($type === 'pdf') {
            $filename = "marge_food_cost_".date('Y_m_d_H_i_s').".pdf";
            $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                $filename,
                '@Supervision/Reports/Merchandise/MarginFoodCost/report.html.twig',
                [
                    'result' => $data['result'],
                    'data' => array(
                        'beginDate' => $tmp->getStartDate(),
                        'endDate' => $tmp->getEndDate(),
                        'restaurant' => $tmp->getOriginRestaurant(),
                    ),
                    "download" => true,
                ],
                [
                    'orientation' => 'Landscape',
                    'page-size' => 'A4',
                ]
            );

            return Utilities::createFileResponse($filepath, $filename);
        } else {
            if ($type === 'xls') {
                $filter['beginDate'] = $tmp->getStartDate();
                $filter['endDate'] = $tmp->getEndDate();
                $filter['restaurant'] = $tmp->getOriginRestaurant();

                $response = $this->get('supervision.report.margin.foodcost.service')->generateExcelFile(
                    $filter,
                    $data['result']
                );

                return $response;
            } else {
                $result = $this->get('supervision.report.margin.foodcost.service')->serializeMarginFoodCostReportResult(
                    $data['result']
                );

                $filepath = $this->get('toolbox.document.generator')->getFilePathFromSerializedResult(
                    [
                        $this->get('translator')->trans('keywords.theorical', [], 'supervision'),
                        $this->get('translator')->trans('keywords.total', [], 'supervision'),
                        '%',
                        '',
                        $this->get('translator')->trans('keywords.real', [], 'supervision'),
                        $this->get('translator')->trans('keywords.total', [], 'supervision'),
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
