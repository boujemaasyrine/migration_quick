<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 27/05/2016
 * Time: 12:11
 */

namespace AppBundle\Supervision\Controller\Reports;

use AppBundle\Supervision\Form\Reports\DailyResultsType;
use AppBundle\Supervision\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use AppBundle\Security\RightAnnotation;

/**
 * Class DailyResultsController
 *
 * @package         AppBundle\Controller\Reports
 * @Route("report")
 */
class DailyResultsController extends Controller
{
    /**
     * @RightAnnotation("daily_results_report")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dailyResults",name="supervision_daily_results_report")
     */
    public function generateDailyResultsReportAction(Request $request)
    {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $form = $this->createForm(DailyResultsType::class, null, ['user' => $user]);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $filter = $form->getData();
                $result = $this->get('supervision.report.daily.results.service')->getAllDailyResults($filter);
                if (is_null($request->get('download', null)) && is_null($request->get('export', null)) && is_null(
                    $request->get('xls', null)
                )) {
                    return $this->render(
                        '@Supervision/Reports/Revenue/DailyResults/index.html.twig',
                        [
                            "form" => $form->createView(),
                            "generated" => true,
                            "result" => $result,
                        ]
                    );
                } else {
                    if (!is_null($request->get('download', null)) && is_null($request->get('export', null)) && is_null(
                        $request->get('xls', null)
                    )) {
                        $filename = "resultat_journalier_".date('Y_m_d_H_i_s').".pdf";
                        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                            $filename,
                            '@Supervision/Reports/Revenue/DailyResults/report.html.twig',
                            [
                                "download" => true,
                                'filter' => $form->getData(),
                                'result' => $result,
                            ],
                            [
                                'orientation' => 'Landscape',
                            ]
                        );

                        return Utilities::createFileResponse($filepath, $filename);
                    } else {
                        if (!is_null($request->get('xls', null)) && is_null($request->get('export', null)) && is_null(
                            $request->get('download', null)
                        )) {
                            $response = $this->get('supervision.report.daily.results.service')->generateExcelFile(
                                $result,
                                $filter
                            );

                            return $response;
                        } else {
                            $result = $this->get(
                                'supervision.report.daily.results.service'
                            )->serializeDailyResultsReportResult($result);

                            $filepath = $this->get('toolbox.document.generator')->getFilePathFromSerializedResult(
                                [
                                    $this->get('translator')->trans('keywords.date', [], 'supervision'),
                                    $this->get('translator')->trans('keywords.date_comp', [], 'supervision'),
                                    $this->get('translator')->trans('budget_label', [], 'supervision'),
                                    $this->get('translator')->trans('report.ca.ca_brut_ttc', [], 'supervision'),
                                    $this->get('translator')->trans('report.discount', [], 'supervision'),
                                    $this->get('translator')->trans('report.br', [], 'supervision'),
                                    $this->get('translator')->trans('report.ca.ca_net_ht', [], 'supervision'),
                                    '% (-1)',
                                    $this->get('translator')->trans(
                                        'report.sales.hour_by_hour.tickets',
                                        [],
                                        'supervision'
                                    ),
                                    '% (-1)',
                                    $this->get('translator')->trans(
                                        'report.daily_result.avg_net_ticket',
                                        [],
                                        'supervision'
                                    ),
                                    '% (-1)',
                                    $this->get('translator')->trans(
                                        'report.daily_result.diff_caisse',
                                        [],
                                        'supervision'
                                    ),
                                    $this->get('translator')->trans(
                                        'report.daily_result.chest_error',
                                        [],
                                        'supervision'
                                    ),
                                    $this->get('translator')->trans('report.daily_result.sold_loss', [], 'supervision'),
                                    $this->get('translator')->trans(
                                        'report.daily_result.inventory_loss',
                                        [],
                                        'supervision'
                                    ),
                                ],
                                $result
                            );
                            $response = Utilities::createFileResponse(
                                $filepath,
                                'Resultats Journaliers'.date('dmY_His').".csv"
                            );

                            return $response;
                        }
                    }
                }
            }
        }

        return $this->render(
            "@Supervision/Reports/Revenue/DailyResults/index.html.twig",
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/getComparableDate/{date}",name="supervision_get_comparable_date", options={"expose"=true})
     */
    public function getComparableDateAction(Request $request, $date)
    {
        $response = new JsonResponse();
        try {
            $date = \DateTime::createFromFormat('d-m-Y', $date);
            $lastDate = $this->get('supervision.report.daily.results.service')->getDateOfLastYear($date);
            $response->setData(
                [
                    'date' => $lastDate->format('d/m/Y'),
                ]
            );
        } catch (\Exception $e) {
            $response->setData(
                [
                    "errors" => [
                        $this->get('translator')->trans('Error.general.internal'),
                        $e->getLine()." : ".$e->getTraceAsString(),
                    ],
                ]
            );
        }

        return $response;
    }
}
