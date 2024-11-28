<?php

namespace AppBundle\Report\Controller;

use AppBundle\Report\Entity\GenericCachedReport;
use AppBundle\Report\Form\StrikeRateFormType;
use AppBundle\Report\Form\StrikeRatePyramidFormType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\ToolBox\Utils\Utilities;

/**
 * Class BrReportController
 * @package AppBundle\Report\Controller
 * @Route("report/strike_rate")
 */
class StrikeRateController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/index",name="strike_rate")
     */
    public function indexAction(Request $request)
    {
        $logger = $this->get('monolog.logger.generate_report');
        $data['currentRestaurant'] = $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $data['startDate'] = new \DateTime('Monday this week');
        $data['endDate'] = new \DateTime('Sunday this week');
        $form = $this->createForm(StrikeRateFormType::class, $data);
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $i = rand();
                $data = $form->getData();
                $logger->addInfo('Generate report strike rate by ' . $currentRestaurant->getCode() .' from '.$data['startDate']->format('Y-m-d').' to '.$data['endDate']->format('Y-m-d').' '. $i);
                $t1 = time();
                $result = $this->getStrikeRateReportResult($request, $data, $currentRestaurant->getId());
                $t2 = time();
                $logger->addInfo('Generate report strike rate finish | generate time = ' . ($t2 - $t1) . 'seconds by ' . $currentRestaurant->getCode() . ' ' . $i);
                if (is_null($request->get('download', null)) && is_null(
                        $request->get('xls', null))) {
                    return $this->render('@Report/StrikeRate/MMM/index_strike_rate_report.html.twig',
                        array('form' => $form->createView(), 'data' => $result, 'generated' => true, 'filter' => $data)
                    );
                } else {
                    if (!is_null($request->get('download', null)) && is_null(
                            $request->get('xls', null)
                        )) {
                        //Téléchargement PDF
                        $filename = "strike_rate_report_" . date('Y_m_d_H_i_s') . ".pdf";
                        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig($filename,
                            '@Report/StrikeRate/MMM/export/export_strike_rate.html.twig',
                            [
                                "form", $form->createView(),
                                "data" => $result,
                                "generated" => true,
                                "download" => true
                            ]
                            , [
                                'orientation' => 'Portrait',
                                'page-size' => "A4",
                                'footer-center' => '[page]'
                            ]);

                        return Utilities::createFileResponse($filepath, $filename);
                    } else {
                        if (!is_null($request->get('xls', null)) && is_null(
                                $request->get('download', null)
                            )) {
                            $logoPath = $this->get('kernel')->getRootDir() . '/../web/src/images/logo.png';
//                            var_dump($result);die;
                            $response = $this->get('report.strike.rate.service')->generateExcelFile($result, $data, $logoPath);

                            return $response;
                        }
                    }
                }
            }
        }
        return $this->render('@Report/StrikeRate/MMM/index_strike_rate_report.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @param $request
     * @param $data
     * @param $rid
     * @return mixed
     */
    private function getStrikeRateReportResult($request, $data, $rid)
    {

        /**
         * @var ReportCacheService $reportCacheService
         */
        $reportCacheService = $this->get('report.cache.service');
        $filterCacheReport['startDate'] = $data['startDate'];
        $filterCacheReport['endDate'] = $data['endDate'];
        $filterCacheReport['itemName'] = $data['itemName'];
        if (!$this->isRequestForExportStrikeRateReport($request)) {
            $result = $this->get('report.strike.rate.service')->getList($data);
            $reportCacheService->cacheReport('strike_rate', $rid, $result, $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
        } else {
            $result = $reportCacheService->getReportCache('strike_rate', $rid, $filterCacheReport);
            if ($result === null) {
                $result = $this->get('report.strike.rate.service')->getList($data);
                $reportCacheService->cacheReport('strike_rate', $rid, $result, $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
            }
        }
        return $result;
    }

    private function isRequestForExportStrikeRateReport($request)
    {
        return !(is_null($request->get('download', null)) && is_null(
                $request->get('xls', null)));
    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/pyramid",name="strike_rate_pyramid")
     */
    public function pyramidAction(Request $request)
    {

        $data['startDate'] = new \DateTime('Monday this week');
        $data['endDate'] = new \DateTime('Sunday this week');
        $data['compareStartDate'] = $this->get('report.strike.rate.service')->getDateOfLastYear($data['startDate']);
        $data['compareEndDate'] = $this->get('report.strike.rate.service')->getDateOfLastYear($data['endDate']);
        $form = $this->createForm(StrikeRatePyramidFormType::class, $data);
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $result = $this->get('report.strike.rate.service')->getPyramidList($data);

                if (is_null($request->get('export', null))) {
                    return $this->render('@Report/StrikeRate/Pyramid/index_strike_rate_report.html.twig',
                        array('form' => $form->createView(), 'data' => $result, 'generated' => true)
                    );
                } else {
                    //Téléchargement PDF
                    $filename = "strike_rate_report_" . date('Y_m_d_H_i_s') . ".pdf";
                    $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig($filename,
                        '@Report/StrikeRate/Pyramid/export/export_strike_rate.html.twig',
                        [
                            "form", $form->createView(),
                            "data" => $result,
                            "generated" => true]
                        , [
                            'orientation' => 'Landscape',
                            'page-size' => "A4"
                        ]);

                    return Utilities::createFileResponse($filepath, $filename);
                }
            }
        }
        return $this->render('@Report/StrikeRate/Pyramid/index_strike_rate_report.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/generateComparableDate/{date}",name="generate_comparable_date", options={"expose"=true})
     */
    public function getComparableDateAction(Request $request, $date)
    {
        $response = new JsonResponse();
        try {
            $date = \DateTime::createFromFormat('d-m-Y', $date);
            $lastDate = $this->get('report.strike.rate.service')->getDateOfLastYear($date);
            $response->setData([
                'date' => $lastDate->format('d/m/Y'),
            ]);
        } catch (\Exception $e) {
            $response->setData([
                "errors" => [
                    $this->get('translator')->trans('Error.general.internal'),
                    $e->getLine() . " : " . $e->getTraceAsString(),
                ]
            ]);
        }

        return $response;

    }
}
