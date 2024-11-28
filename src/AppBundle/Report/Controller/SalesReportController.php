<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 23/03/2016
 * Time: 16:38
 */

namespace AppBundle\Report\Controller;

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Report\Entity\GenericCachedReport;
use AppBundle\Report\Form\CaBySliceHourReportType;
use AppBundle\Report\Form\HourByHourEmployeeReportType;
use AppBundle\Report\Form\HourByHourReportType;
use AppBundle\Report\Service\ReportCacheService;
use AppBundle\Security\RightAnnotation;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class BuyingReportController
 * @package AppBundle\Report\Controller
 * @Route("report/sales")
 */
class SalesReportController extends Controller
{
    /**
     * @RightAnnotation ("hour_by_hour")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/hour_by_hour",name="hour_by_hour")
     */
    public function generateHourByHourReportAction(Request $request)
    {
        $logger = $this->get('monolog.logger.generate_report');
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $restaurantService = $this->get("restaurant.service");
        $title = $this->get('translator')->trans('report.sales.hour_by_hour.title');
        $form = $this->createForm(HourByHourReportType::class);
        if (!$request->isXmlHttpRequest()) {
            if ($request->getMethod() === "GET") {
                $currentDate = date('d/m/Y');

                return $this->render(
                    "@Report/SalesManagement/HourByHour/index_hour_by_hour_report.html.twig",
                    [
                        'date' => $currentDate,
                        'form' => $form->createView(),
                         'importRecentTickets' => true
                    ]
                );
            } elseif ($request->getMethod() === "POST") {
                $form->handleRequest($request);
                if ($form->isValid()) {
                    $logoPath = $this->get('kernel')->getRootDir() . '/../web/src/images/logo.png';
                    //handle date fields
                    $fromDateTime = $form->get('from')->getData();
                    $from = $fromDateTime->format('d-m-Y');
                    $toDateTime = $form->get('to')->getData();
                    $to = $toDateTime->format('d-m-Y');
                    //handle ca type field
                    $caType = $form->get('caType')->getData();
                    //handle scheduleType field
                    $scheduleType = $form->get('scheduleType')->getData();
                    if ($restaurantService->isHistoricDate($currentRestaurant, new \DateTime($from))) {
                        $scheduleType = 0;
                    }
                    $criteria = [];
                    $criteria['from'] = $from;
                    $criteria['to'] = $to;
                    $i = rand();
                    $logger->addInfo('Generate report hour by hour started by ' . $currentRestaurant->getCode() .' from '.$from.' to '.$to.' ' . $i);
                    $t1 = time();
                    $allResult = $this->getHourByHourReportResult($request, $caType, $criteria, $scheduleType);
                    $t2 = time();
                    $logger->addInfo('Generate report hour by hour finish | generate time = ' . ($t2 - $t1) . 'seconds by ' . $currentRestaurant->getCode() . ' ' . $i);
                    $result = $allResult['result'];
                    $openingHour = $allResult['openingHour'];
                    $closingHour = $allResult['closingHour'];
                    $currentTime = new \DateTime();
                    $currentHour = (int)$currentTime->format('H');
                    if (!$this->isRequestForExport($request)) {
                        return $this->render(
                            '@Report/SalesManagement/HourByHour/index_hour_by_hour_report.html.twig',
                            [
                                "result" => $result,
                                "generated" => true,
                                'from' => $from,
                                'to' => $to,
                                'opening_hour' => $openingHour,
                                'closing_hour' => $closingHour,
                                'form' => $form->createView(),
                                'scheduleType' => $scheduleType,
                                'caType' => $caType,
                                'currentHour' => $currentHour
                            ]
                        );

                    } else {
                        if (!is_null($request->get('download', null)) && is_null(
                                $request->get('export', null)
                            ) && is_null($request->get('xls', null))) {
                            $pageSizes = [0 => 'A4', 1 => 'A2', 2 => 'A1'];
                            $filename = $title . date('Y_m_d_H_i_s') . ".pdf";
                            $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                                $filename,
                                '@Report/SalesManagement/HourByHour/report_hour_by_hour.html.twig',
                                [
                                    'result' => $result,
                                    'from' => $from,
                                    'to' => $to,
                                    "download" => true,
                                    'opening_hour' => $openingHour,
                                    'closing_hour' => $closingHour,
                                    'scheduleType' => $scheduleType,
                                    'caType' => $caType,
                                ],
                                [
                                    'orientation' => 'Landscape',
                                    'page-size' => $pageSizes[$scheduleType],
                                    'zoom' => ($scheduleType == 2) ? 0.8 : 1,
                                ]
                            );

                            return Utilities::createFileResponse($filepath, $filename);
                        } else {
                            if (!is_null($request->get('xls', null)) && is_null(
                                    $request->get('export', null)
                                ) && is_null($request->get('download', null))) {
                                $response = $this->get('report.sales.service')->generateExcelFile(
                                    $result,
                                    $fromDateTime,
                                    $toDateTime,
                                    $openingHour,
                                    $closingHour,
                                    $logoPath,
                                    $scheduleType,
                                    $caType
                                );

                                return $response;
                            } else {
                                if ($scheduleType == 0) {
                                    $header = $this->get('report.sales.service')->getCsvHeader(
                                        $openingHour,
                                        $closingHour
                                    );
                                    $result = $this->get('report.sales.service')->serializeHourByHourReportResult(
                                        $result,
                                        $openingHour,
                                        $closingHour,
                                        $caType
                                    );
                                    $filepath = $this->get(
                                        'toolbox.document.generator'
                                    )->getFilePathFromSerializedResult($header, $result);
                                    $response = Utilities::createFileResponse(
                                        $filepath,
                                        $title . 'CSV' . date('dmY_His') . ".csv"
                                    );
                                } else {
                                    $header = $this->get('report.sales.service')->getHalfORQuarterHourCsvHeader(
                                        $openingHour,
                                        $closingHour,
                                        $scheduleType
                                    );
                                    $result = $this->get('report.sales.service')->serializeHalfOrQuarterHourCSVResult(
                                        $result,
                                        $openingHour,
                                        $closingHour,
                                        $scheduleType,
                                        $caType
                                    );
                                    $filepath = $this->get(
                                        'toolbox.document.generator'
                                    )->getFilePathFromSerializedResult($header, $result);
                                    $response = Utilities::createFileResponse(
                                        $filepath,
                                        $title . 'CSV' . date('dmY_His') . ".csv"
                                    );
                                }

                                return $response;

                            }
                        }
                    }

                }

                return $this->render(
                    "@Report/SalesManagement/HourByHour/index_hour_by_hour_report.html.twig",
                    [
                        'error' => true,
                        'form' => $form->createView(),
                    ]
                );
            }
        } else {
            throw new MethodNotAllowedHttpException('Only http request are allowed');
        }
    }


    /**
     * @param $request
     * @param $caType
     * @param $criteria
     * @param $scheduleType
     * @return mixed
     */
    private function getHourByHourReportResult($request, $caType, $criteria, $scheduleType)
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        /**
         * @var ReportCacheService $reportCacheService
         */
        $reportCacheService = $this->get('report.cache.service');
        $restaurantId = $currentRestaurant->getId();
        $filterCacheReport = array_merge($criteria, ['scheduleType' => $scheduleType, 'caType' => $caType]);
        if ($caType === 0) {
            if (!$this->isRequestForExport($request)) {
                $allResult = $this->get('report.sales.service')->generateHourByHourReport(
                    $criteria,
                    $scheduleType
                );
                $reportCacheService->cacheReport('hour_by_hour', $restaurantId, $allResult,
                    $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
            } else {
                $allResult = $reportCacheService->getReportCache('hour_by_hour',
                    $restaurantId, $filterCacheReport);
                if ($allResult === null) {
                    $allResult = $this->get('report.sales.service')->generateHourByHourReport(
                        $criteria,
                        $scheduleType
                    );
                    $reportCacheService->cacheReport('hour_by_hour', $restaurantId, $allResult,
                        $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
                }
            }

        } else {
            if (!$this->isRequestForExport($request)) {
                $allResult = $this->get('report.sales.service')->generateCaHTvaHourByHourReport(
                    $criteria,
                    $scheduleType
                );
                $reportCacheService->cacheReport('hour_by_hour', $restaurantId, $allResult,
                    $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
            } else {
                $allResult = $reportCacheService->getReportCache('hour_by_hour',
                    $restaurantId, $filterCacheReport);
                if ($allResult === null) {
                    $allResult = $this->get('report.sales.service')->generateCaHTvaHourByHourReport(
                        $criteria,
                        $scheduleType
                    );
                    $reportCacheService->cacheReport('hour_by_hour', $restaurantId, $allResult,
                        $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
                }
            }
        }
        return $allResult;
    }

    /**
     * Vérifier que c'est une demande d'export ou non
     * @param $request
     * @return bool
     */
    private function isRequestForExport($request)
    {
        return !(is_null($request->get('download', null)) && is_null($request->get('export', null)) && is_null(
                $request->get('xls', null)
            ));
    }


    /**
     * @param Request $request
     * @Route("/by_slice_hour",name="ca_by_slice_hour")
     */
    public function generateCaBySliceHourReportAction(Request $request)
    {
        $logger = $this->get('monolog.logger.generate_report');
        $form = $this->createForm(CaBySliceHourReportType::class);
        $restaurantService = $this->get("restaurant.service");
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $logoPath = $this->get('kernel')->getRootDir() . '/../web/src/images/logo.png';
                $filter = array();
                //handle date fields
                $filter['date1'] = $form->get('date1')->getData();
                $filter['date2'] = $form->get('date2')->getData();
                $filter['date3'] = $form->get('date3')->getData();
                $filter['date4'] = $form->get('date4')->getData();
                $filter['scheduleType'] = $form->get('scheduleType')->getData();
                //handle ca type fields
                $caType = $form->get('caType')->getData();
                if ($restaurantService->isHistoricDate($currentRestaurant, $filter['date1']) ||
                    $restaurantService->isHistoricDate($currentRestaurant, $filter['date2']) ||
                    $restaurantService->isHistoricDate($currentRestaurant, $filter['date3']) ||
                    $restaurantService->isHistoricDate($currentRestaurant, $filter['date4'])) {
                    $filter['scheduleType'] = 0;
                }
                $i = rand();
                $logger->addInfo('Generate report sales by slice hour started by ' . $currentRestaurant->getCode() . ' ' . $i);
                $t1 = time();
                $allResult = $this->getSalesBySliceHourReportResult($request, $caType, $filter, $currentRestaurant->getId());
                $t2 = time();
                $logger->addInfo('Generate report sales by slice hour finish | generate time = ' . ($t2 - $t1) . 'seconds by ' . $currentRestaurant->getCode() . ' ' . $i);
                $result = $allResult['result'];
                $openingHour = $allResult['openingHour'];
                $closingHour = $allResult['closingHour'];
                if (is_null($request->get('download', null)) && is_null($request->get('xls', null))) {

                    return $this->render(
                        '@Report/SalesManagement/CaBySliceHour/index_ca_by_slicehour_report.html.twig',
                        array(
                            "result" => $result,
                            "generated" => true,
                            'form' => $form->createView(),
                            'filter' => $filter,
                            'scheduleType' => $filter['scheduleType'],
                            'caType' => $caType,
                            'openingHour' => $openingHour,
                            'closingHour' => $closingHour
                        )
                    );

                } else {
                    if (!is_null($request->get('download', null)) && is_null($request->get('xls', null))) {
                        $pageSizes = array(0 => 'A4', 1 => 'A2', 2 => 'A1');
                        $filename = "Ca_par_tranche_horaire" . date('Y_m_d_H_i_s') . ".pdf";
                        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                            $filename,
                            '@Report/SalesManagement/CaBySliceHour/report_ca_by_slicehour.html.twig',
                            array(
                                'result' => $result,
                                'scheduleType' => $filter['scheduleType'],
                                'filter' => $filter,
                                "download" => true,
                                'caType' => $caType,
                                'openingHour' => $openingHour,
                                'closingHour' => $closingHour
                            ),
                            array(
                                'orientation' => 'Portrait',
                                'page-size' => $pageSizes[$filter['scheduleType']],
                                'zoom' => ($filter['scheduleType'] == 2) ? 0.8 : 1,
                            )
                        );

                        return Utilities::createFileResponse($filepath, $filename);
                    } else {
                        if (!is_null($request->get('xls', null)) && is_null($request->get('download', null))) {
                            $response = $this->get('report.sales.service')->generateCaExcelFile(
                                $result,
                                $filter,
                                $logoPath,
                                $caType,
                                $openingHour,
                                $closingHour
                            );

                            return $response;
                        }
                    }
                }
            }
        }

        return $this->render(
            "@Report/SalesManagement/CaBySliceHour/index_ca_by_slicehour_report.html.twig",
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @param $request
     * @param $caType
     * @param $filter
     * @param $rid
     * @return mixed
     */
    private function getSalesBySliceHourReportResult($request, $caType, $filter, $rid)
    {
        /**
         * @var ReportCacheService $reportCacheService
         */
        $reportCacheService = $this->get('report.cache.service');
        $filterCacheReport = array_merge($filter, ['caType' => $caType]);
        if ($caType === 0) {
            if (!$this->isRequestForExportSalesBySliceHourReport($request)) {
                $result = $this->get('report.sales.service')->generateCaBrutBySliceHourReport($filter);
                $reportCacheService->cacheReport('ca_by_slice_hour', $rid, $result, $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
            } else {
                $result = $reportCacheService->getReportCache('ca_by_slice_hour', $rid, $filterCacheReport);
                if ($result === null) {
                    $result = $this->get('report.sales.service')->generateCaBrutBySliceHourReport($filter);
                    $reportCacheService->cacheReport('ca_by_slice_hour', $rid, $result, $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
                }
            }
        } else {
            if (!$this->isRequestForExportSalesBySliceHourReport($request)) {
                $result = $this->get('report.sales.service')->generateCaHTvaBySliceHourReport($filter);
                $reportCacheService->cacheReport('ca_by_slice_hour', $rid, $result, $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
            } else {
                $result = $reportCacheService->getReportCache('ca_by_slice_hour', $rid, $filterCacheReport);
                if ($result === null) {
                    $result = $this->get('report.sales.service')->generateCaHTvaBySliceHourReport($filter);
                    $reportCacheService->cacheReport('ca_by_slice_hour', $rid, $result, $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
                }
            }

        }

        return $result;
    }

    /**
     * @param $request
     * @return bool
     */
    private function isRequestForExportSalesBySliceHourReport($request)
    {
        return !(is_null($request->get('download', null)) && is_null($request->get('xls', null)));
    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/ca_byHour",name="ca_byHour",options={"expose"=true})
     */
     public function getCaJsonAction(Request $request)
    {

        if (!$request->request->has('date')) {
            return new JsonResponse(
                array(
                    'ca' => null,
                )
            );
        }
        $caType = $request->request->get('catype');
        $dateS = $request->request->get('date');
        $date = str_replace('/', '-', $dateS);
        $restaurantService=$this->get("restaurant.service");
        $currentRestaurant = $restaurantService->getCurrentRestaurant();
        $criteria['to'] = $criteria['from'] = $date;
        if ($caType == '0') {
            if($restaurantService->isHistoricDate($currentRestaurant,new \DateTime($date))){
                $caByHour = $this->getDoctrine()->getRepository(Ticket::class)->getHistoricCaBrutTicketsPerHour(
                    $criteria,
                    $currentRestaurant
                );
            }else{
                $caByHour = $this->getDoctrine()->getRepository(Ticket::class)->getCaBrutTicketsPerHour(
                    $criteria,
                    $currentRestaurant
                );
            }

        } else {
            if($restaurantService->isHistoricDate($currentRestaurant,new \DateTime($date))){
                $caByHour = $this->getDoctrine()->getRepository(Ticket::class)->getHistoricCaHTvaPerSliceHour(
                    $criteria,
                    $currentRestaurant
                );
            }else{
                $caByHour = $this->getDoctrine()->getRepository(Ticket::class)->getCaHTvaPerSliceHour(
                    $criteria,
                    $currentRestaurant
                );
            }
        }
        $ca = 0;
        foreach ($caByHour as $raw) {
            $ca += $raw['ca'];
        }


        return new JsonResponse(array('ca' => number_format($ca, '2', ',', '')));
    }


    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/hour_by_hour_employee",name="hour_by_hour_employee")
     */
    public function generateHourByHourEmployeeReportAction(Request $request)
    {
        $logger = $this->get('monolog.logger.generate_report');
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $title = $this->get('translator')->trans('hour_bu_hour_employee.title');
        $form = $this->createForm(HourByHourEmployeeReportType::class);
        if (!$request->isXmlHttpRequest()) {
            if ($request->getMethod() === "GET") {
                $currentDate = date('d/m/Y');

                return $this->render(
                    "@Report/SalesManagement/HourByHourEmployee/index_hour_by_hour_report_employee.html.twig",
                    [
                        'date' => $currentDate,
                        'form' => $form->createView(),
                    ]
                );
            } elseif ($request->getMethod() === "POST") {
                $form->handleRequest($request);
                if ($form->isValid()) {
                    $logoPath = $this->get('kernel')->getRootDir() . '/../web/src/images/logo.png';
                    //handle date fields
                    $fromDateTime = $form->get('from')->getData();
                    $from = $fromDateTime->format('d-m-Y');
                    $toDateTime = $form->get('to')->getData();
                    $to = $toDateTime->format('d-m-Y');
                    $i = rand();
                    $criteria = [];
                    $criteria['from'] = $from;
                    $criteria['to'] = $to;
                    $scheduleType = $form->get('scheduleType')->getData();
                    $logger->addInfo('Generate report hour by hour per employee by ' . $currentRestaurant->getCode() .' from '.$from.' to '.$to.' ' . $i);
                    $t1 = time();
                    $result = $this->getHourByHourEmployeeReportResult($request, $criteria, $currentRestaurant->getId(),$scheduleType);
                    $t2 = time();
                    $logger->addInfo('Generate report hour by hour per employee finish | generate time = ' . ($t2 - $t1) . 'seconds by ' . $currentRestaurant->getCode() . ' ' . $i);
                    if (is_null($request->get('download', null)) && is_null($request->get('export', null)) && is_null(
                            $request->get('xls', null)
                        )) {
                        $currentTime = new \DateTime();
                        $currentHour = (int)$currentTime->format('H');
                        return $this->render(
                            '@Report/SalesManagement/HourByHourEmployee/index_hour_by_hour_report_employee.html.twig',
                            [
                                "result" => $result,
                                "generated" => true,
                                'from' => $from,
                                'to' => $to,
                                'scheduleType' => $scheduleType,
                                'form' => $form->createView(),
                                'currentHour' => $currentHour
                            ]
                        );

                    } else {
                        if (!is_null($request->get('download', null)) && is_null(
                                $request->get('export', null)
                            ) && is_null($request->get('xls', null))) {
                            $pageSizes = [0 => 'A4', 1 => 'A2', 2 => 'A1'];
                            $filename = $title . date('Y_m_d_H_i_s') . ".pdf";
                            $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                                $filename,
                                '@Report/SalesManagement/HourByHourEmployee/report_hour_by_hour_employee.html.twig',
                                [
                                    'result' => $result,
                                    'from' => $from,
                                    'to' => $to,
                                    'scheduleType' => $scheduleType,
                                    "download" => true
                                ],
                                [
                                    'orientation' => 'Landscape',
                                    'page-size' => $pageSizes[2],
                                    'zoom' => ($scheduleType == 1) ? 0.8 : 1,
                                ]
                            );

                            return Utilities::createFileResponse($filepath, $filename);
                        } else {
                            if (!is_null($request->get('xls', null)) && is_null(
                                    $request->get('export', null)
                                ) && is_null($request->get('download', null))) {
                                $response = $this->get('report.sales.service')->generateEmployeeExcelFile(
                                    $result,
                                    $from,
                                    $to,
                                    $scheduleType,
                                    $logoPath
                                );

                                return $response;
                            }
                        }
                    }

                }

                return $this->render(
                    "@Report/SalesManagement/HourByHourEmployee/index_hour_by_hour_report_employee.html.twig",
                    [
                        'error' => true,
                        'form' => $form->createView(),
                    ]
                );
            }
        } else {
            throw new MethodNotAllowedHttpException('Only http request are allowed');
        }
    }

    /**
     * @param $request
     * @param $criteria
     * @param $rid
     * @return mixed
     */
    private function getHourByHourEmployeeReportResult($request, $criteria, $rid, $scheduleType)
    {
        /**
         * @var ReportCacheService $reportCacheService
         */
        $reportCacheService = $this->get('report.cache.service');
        $filterCacheReport = $criteria;
        if (!$this->isRequestForExportHbHER($request)) {
            $result = $this->get('report.sales.service')->generateEmployeeHourByHourReport($criteria,$scheduleType);
            $reportCacheService->cacheReport('hour_by_hour_employee', $rid, $result, $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
        } else {
            $result = $reportCacheService->getReportCache('hour_by_hour_employee', $rid, $filterCacheReport);
            if ($result === null) {
                $result = $this->get('report.sales.service')->generateEmployeeHourByHourReport($criteria,$scheduleType);
                $reportCacheService->cacheReport('hour_by_hour_employee', $rid, $result, $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
            }
        }
        return $result;
    }

    private function isRequestForExportHbHER($request)
    {
        return !(is_null($request->get('download', null)) && is_null($request->get('export', null)) && is_null(
                $request->get('xls', null)
            ));
    }


}