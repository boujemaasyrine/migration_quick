<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 17:01
 */

namespace AppBundle\Report\Controller;

use AppBundle\Report\Entity\GenericCachedReport;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Report\Form\DailyResultsType;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DailyResultsController
 *
 * @package         AppBundle\Report\Controller
 * @Route("report")
 */
class DailyResultsController extends Controller
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/dailyResults",name="daily_results_report")
     */
    public function generateDailyResultsReportAction(Request $request)
    {
        $logger = $this->get('monolog.logger.generate_report');
        $form = $this->createForm(DailyResultsType::class);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $filter = $form->getData();
                $i = rand();
                $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
                $filter["currentRestaurantId"] = $currentRestaurant->getId();
                $logger->addInfo('Generate report daily result by ' . $currentRestaurant->getCode() .' from '.$filter['startDate']->format('Y-m-d').' to '.$filter['endDate']->format('Y-m-d').' '. $i);
                $t1 = time();
                $result =$this->getDailyResultsReport($request, $filter);
                $t2 = time();
                $logger->addInfo('Generate report daily result finish | generate time = ' . ($t2 - $t1) . 'seconds by ' . $currentRestaurant->getCode() . ' ' . $i);
                if (is_null($request->get('download', null)) && is_null($request->get('export', null)) && is_null(
                        $request->get('xls', null)
                    )) {
                    return $this->render(
                        '@Report/DailyResults/index.html.twig',
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
                        $filename = "resultat_journalier_" . date('Y_m_d_H_i_s') . ".pdf";
                        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                            $filename,
                            '@Report/DailyResults/report.html.twig',
                            [
                                "download" => true,
                                'filter' => $form->getData(),
                                'result' => $result,
                                'download' => true
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
                            $logoPath = $this->get('kernel')->getRootDir() . '/../web/src/images/logo.png';
                            $response = $this->get('report.daily.results.service')->generateExcelFile($result, $filter, $currentRestaurant, $logoPath);

                            return $response;
                        } else {
                            $result = $this->get('report.daily.results.service')->serializeDailyResultsReportResult(
                                $result
                            );

                            $filepath = $this->get('toolbox.document.generator')->getFilePathFromSerializedResult(
                                [
                                    $this->get('translator')->trans('keyword.date'),
                                    $this->get('translator')->trans('keyword.date_comp'),
                                    $this->get('translator')->trans('budget_label'),
                                    $this->get('translator')->trans('report.ca.ca_brut_ttc'),
                                    $this->get('translator')->trans('report.daily_result.vente_annexe'),
                                    $this->get('translator')->trans('report.discount'),
                                    $this->get('translator')->trans('report.br'),
                                    $this->get('translator')->trans('report.ca.ca_net_ht'),
                                    '% (-1)',
                                    $this->get('translator')->trans('report.sales.hour_by_hour.tickets'),
                                    '% (-1)',
                                    $this->get('translator')->trans('report.daily_result.avg_net_ticket'),
                                    '% (-1)',
                                    $this->get('translator')->trans(
                                        'cashbox_counts_anomalies.report_labels.diff_caisse'
                                    ),
                                    $this->get('translator')->trans('report.daily_result.chest_error'),
                                    $this->get('translator')->trans('report.daily_result.sold_loss'),
                                    $this->get('translator')->trans('report.daily_result.inventory_loss'),
                                    $this->get('translator')->trans('report.daily_result.takeout_percentage'),
                                    $this->get('translator')->trans('keyword.comment'),
                                ],
                                $result
                            );
                            $response = Utilities::createFileResponse(
                                $filepath,
                                'Resultats Journaliers' . date('dmY_His') . ".csv"
                            );

                            return $response;
                        }
                    }
                }
            }
        }

        return $this->render(
            "@Report/DailyResults/index.html.twig",
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @param $request
     * @param $criteria
     * @return mixed
     */
    private function getDailyResultsReport($request, $criteria)
    {
        $rid = $criteria["currentRestaurantId"];
        /**
         * @var ReportCacheService $reportCacheService
         */
        $reportCacheService = $this->get('report.cache.service');
        $filterCacheReport = $criteria;
        if (!$this->isRequestForExportDailyResultsReport($request)) {
            $result = $this->get('report.daily.results.service')->getAllDailyResults($criteria);
            $reportCacheService->cacheReport('daily_results_report', $rid, $result, $filterCacheReport);
        } else {
            $result = $reportCacheService->getReportCache('daily_results_report', $rid, $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
            if ($result === null) {
                $result = $this->get('report.daily.results.service')->getAllDailyResults($criteria);
                $reportCacheService->cacheReport('daily_results_report', $rid, $result, $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
            }
        }
        return $result;
    }

    private function isRequestForExportDailyResultsReport($request)
    {
        return !(is_null($request->get('download', null)) && is_null($request->get('export', null)) && is_null(
                $request->get('xls', null)
            ));
    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/getComparableDate/{date}",name="get_comparable_date", options={"expose"=true})
     */
    public function getComparableDateAction(Request $request, $date)
    {
        $response = new JsonResponse();
        try {
            $date = \DateTime::createFromFormat('d-m-Y', $date);
            $lastDate = $this->get('report.daily.results.service')->getDateOfLastYear($date);
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
                        $e->getLine() . " : " . $e->getTraceAsString(),
                    ],
                ]
            );
        }

        return $response;
    }
}
