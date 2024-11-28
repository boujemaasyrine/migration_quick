<?php

namespace AppBundle\Report\Controller;

use AppBundle\Report\Form\CashboxCountsAnomaliesFilterType;
use AppBundle\Report\Form\CashboxCountsCashierFilterType;
use AppBundle\Report\Form\CashboxCountsOwnerFilterType;
use AppBundle\Report\Validator\DatesReportConstraint;
use AppBundle\Report\Validator\FilterIntervalConstraint;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use AppBundle\Security\RightAnnotation;

/**
 * Class CashboxCountsOwnerReportController
 *
 * @package                        AppBundle\Report\Controller
 * @Route("report/cashbox_counts")
 */
class CashboxCountsReportController extends Controller
{
    /**
     * @param $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/per_owner",name="report_cashbox_counts_owner")
     * @RightAnnotation("report_cashbox_counts_owner")
     */
    public function generateCashboxCountsPerOwnerAction(Request $request)
    {
        $logger=$this->get('monolog.logger.generate_report');
        if (!$request->isXmlHttpRequest()) {
            $countPerOwnerForm = $this->createForm(
                CashboxCountsOwnerFilterType::class,
                [
                    "startDate" => null,
                    "endDate" => null,
                    "owner" => null,
                ]
            );

            if ($request->getMethod() === "GET") {
                return $this->render(
                    "@Report/CashboxCounts/CountsPerOwner/index_counts_per_owner.twig",
                    [
                        "countPerOwnerForm" => $countPerOwnerForm->createView(),
                    ]
                );
            } elseif ($request->getMethod() === "POST") {
                $countPerOwnerForm->handleRequest($request);
                $filter = $countPerOwnerForm->getData();

                $constraint = new DatesReportConstraint();
                $errors = $this->get('validator')->validate($filter, $constraint);

                if ($countPerOwnerForm->isValid() && $errors->count() == 0) {
                    $result['startDate'] = $filter['startDate'];
                    $result['endDate'] = $filter['endDate'];
                    $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
                    $filter["currentRestaurantId"] = $currentRestaurant->getId();
                    $i=rand();
                    $logger->addInfo('Generate report cashbox counts per owner by '.$currentRestaurant->getCode().' from '.$filter['startDate']->format('Y-m-d').' to '.$filter['endDate']->format('Y-m-d').' '.$i);
                    $t1 = time();
                    $result['data'] = $this->get('report.cashbox.service')->getCashboxCountsOwner($filter);
                    $t2 = time();
                    $logger->addInfo('Generate report cashbox counts per owner finish | generate time = '. ($t2 - $t1) .'seconds by '.$currentRestaurant->getCode().' '.$i);

                    if (is_null($request->get('download', null)) && is_null($request->get('export', null))) {
                        return $this->render(
                            '@Report/CashboxCounts/CountsPerOwner/index_counts_per_owner.twig',
                            [
                                "countPerOwnerForm" => $countPerOwnerForm->createView(),
                                "reportResult" => $result,
                                "generated" => true,
                            ]
                        );
                    } else {
                        if (!is_null($request->get('export', null))) {
                            $response = $this->get('report.cashbox.service')->generateCSVReportOwner($filter);

                            return $response;
                        } else {
                            $filename = "cashbox_counts_owner_".date('Y_m_d_H_i_s').".pdf";
                            $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                                $filename,
                                '@Report/CashboxCounts/CountsPerOwner/exports/portion_control_report.html.twig',
                                [
                                    "countPerOwnerForm",
                                    $countPerOwnerForm->createView(),
                                    "reportResult" => $result,
                                    "download" => true,
                                ],
                                [
                                    'orientation' => 'Landscape',
                                ]
                            );

                            return Utilities::createFileResponse($filepath, $filename);
                        }
                    }
                } else {
                    if ($errors->count() > 0) {
                        $this->get('session')->getFlashBag()->add('error', $errors->get(0)->getMessage());
                    }

                    return $this->render(
                        '@Report/CashboxCounts/CountsPerOwner/index_counts_per_owner.twig',
                        [
                            "countPerOwnerForm" => $countPerOwnerForm->createView(),
                        ]
                    );
                }
            }
        } else {
            throw new MethodNotAllowedHttpException('Only http request are allowed');
        }
    }

    /**
     * @param $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/per_cashier",name="report_cashbox_counts_cashier")
     * @RightAnnotation("report_cashbox_counts_cashier")
     */
    public function generateCashboxCountsPerCashierAction(Request $request)
    {
        $logger=$this->get('monolog.logger.generate_report');
        if (!$request->isXmlHttpRequest()) {
            $countPerCashierForm = $this->createForm(
                CashboxCountsCashierFilterType::class,
                [
                    "startDate" => null,
                    "endDate" => null,
                ]
            );

            if ($request->getMethod() === "GET") {
                return $this->render(
                    "@Report/CashboxCounts/CountsPerCashier/index_counts_per_cashier.twig",
                    [
                        "countPerCashierForm" => $countPerCashierForm->createView(),
                    ]
                );
            } elseif ($request->getMethod() === "POST") {
                $countPerCashierForm->handleRequest($request);
                $filter = $countPerCashierForm->getData();

                $constraint = new DatesReportConstraint();
                $errors = $this->get('validator')->validate($filter, $constraint);

                if ($countPerCashierForm->isValid() && $errors->count() == 0) {
                    $result['startDate'] = $filter['startDate'];
                    $result['endDate'] = $filter['endDate'];
                    $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
                    $i=rand();
                    $filter["currentRestaurantId"] = $currentRestaurant->getId();
                    $logger->addInfo('Generate report cashbox counts per cashier by '.$currentRestaurant->getCode().' from '.$filter['startDate']->format('Y-m-d').' to '.$filter['endDate']->format('Y-m-d').' '.$i);
                    $t1 = time();
                    $result['data'] = $this->get('report.cashbox.service')->getCashboxCountsCashier($filter);
                    $t2 = time();
                    $logger->addInfo('Generate report cashbox counts per cashier finish | generate time = '. ($t2 - $t1) .'seconds by '.$currentRestaurant->getCode().' '.$i);
                    if (is_null($request->get('download', null)) && is_null($request->get('export', null))) {
                        return $this->render(
                            '@Report/CashboxCounts/CountsPerCashier/index_counts_per_cashier.twig',
                            [
                                "countPerCashierForm" => $countPerCashierForm->createView(),
                                "reportResult" => $result,
                                "generated" => true,
                            ]
                        );
                    } else {
                        if (!is_null($request->get('export', null))) {
                            $response = $this->get('report.cashbox.service')->generateCSVReportCashier($filter);

                            return $response;
                        } else {
                            $filename = "cashbox_counts_cashier_".date('Y_m_d_H_i_s').".pdf";
                            $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                                $filename,
                                '@Report/CashboxCounts/CountsPerCashier/exports/cashbox_count_report.html.twig',
                                [
                                    "countPerCashierForm",
                                    $countPerCashierForm->createView(),
                                    "reportResult" => $result,
                                    "download" => true,
                                ],
                                [
                                    'orientation' => 'Landscape',
                                ]
                            );

                            return Utilities::createFileResponse($filepath, $filename);
                        }
                    }
                } else {
                    if ($errors->count() > 0) {
                        $this->get('session')->getFlashBag()->add('error', $errors->get(0)->getMessage());
                    }

                    return $this->render(
                        '@Report/CashboxCounts/CountsPerCashier/index_counts_per_cashier.twig',
                        [
                            "countPerCashierForm" => $countPerCashierForm->createView(),
                        ]
                    );
                }
            }
        } else {
            throw new MethodNotAllowedHttpException('Only http request are allowed');
        }
    }

    /**
     * @param $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/anomalies",name="report_cashbox_counts_anomalies")
     * @RightAnnotation("report_cashbox_counts_anomalies")
     */
    public function generateCashboxCountsAnomaliesAction(Request $request)
    {
        $logger=$this->get('monolog.logger.generate_report');
        if (!$request->isXmlHttpRequest()) {
            $countAnomaliesForm = $this->createForm(
                CashboxCountsAnomaliesFilterType::class,
                [
                    "startDate" => null,
                    "endDate" => null,
                ]
            );

            if ($request->getMethod() === "GET") {
                return $this->render(
                    "@Report/CashboxCounts/CountsAnomalies/index_counts_anomalies.twig",
                    [
                        "countAnomaliesForm" => $countAnomaliesForm->createView(),
                    ]
                );
            } elseif ($request->getMethod() === "POST") {
                $countAnomaliesForm->handleRequest($request);
                $filter = $countAnomaliesForm->getData();

                $constraint = new DatesReportConstraint();
                $errors = $this->get('validator')->validate($filter, $constraint);

                if ($errors->count() > 0) {
                    foreach ($errors as $error) {
                        $countAnomaliesForm->get($error->getPropertyPath())
                            ->addError(new FormError($error->getMessage()));
                    }
                }

                if ($countAnomaliesForm->isValid() && $errors->count() == 0) {
                    $result['startDate'] = $filter['startDate'];
                    $result['endDate'] = $filter['endDate'];
                    $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
                    $i=rand();
                    $filter["currentRestaurantId"] = $currentRestaurant->getId();
                    $logger->addInfo('Generate report cashbox counts anomalies by '.$currentRestaurant->getCode().' from '.$filter['startDate']->format('Y-m-d').' to '.$filter['endDate']->format('Y-m-d').' '.$i);
                    $t1 = time();
                    $result['data'] = $this->get('report.cashbox.service')->getCashboxCountsAnomalies($filter);
                    $t2 = time();
                    $logger->addInfo('Generate report cashbox counts anomalies finish | generate time = '. ($t2 - $t1) .'seconds by '.$currentRestaurant->getCode().' '.$i);

                    $filter['max'] = $this->get('report.cashbox.service')->getCashboxCountsAnomaliesMax($filter);

                    if (is_null($request->get('download', null)) && is_null($request->get('export', null))) {
                        return $this->render(
                            '@Report/CashboxCounts/CountsAnomalies/index_counts_anomalies.twig',
                            [
                                "countAnomaliesForm" => $countAnomaliesForm->createView(),
                                "reportResult" => $result,
                                "filters" => $filter,
                                "generated" => true,
                            ]
                        );
                    } else {
                        if (!is_null($request->get('export', null))) {
                            $response = $this->get('report.cashbox.service')->generateCSVReportAnomalies($filter);

                            return $response;
                        } else {
                            $filename = "cashbox_counts_anomalies_".date('Y_m_d_H_i_s').".pdf";
                            $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                                $filename,
                                '@Report/CashboxCounts/CountsAnomalies/exports/cashbox_count_anomalies_report.html.twig',
                                [
                                    "countAnomaliesForm",
                                    $countAnomaliesForm->createView(),
                                    "reportResult" => $result,
                                    "filters" => $filter,
                                    "download" => true,
                                ],
                                [
                                    'orientation' => 'Landscape',
                                ]
                            );

                            return Utilities::createFileResponse($filepath, $filename);
                        }
                    }
                } else {
                    return $this->render(
                        '@Report/CashboxCounts/CountsAnomalies/index_counts_anomalies.twig',
                        [
                            "countAnomaliesForm" => $countAnomaliesForm->createView(),
                        ]
                    );
                }
            }
        } else {
            throw new MethodNotAllowedHttpException('Only http request are allowed');
        }
    }

    /**
     * @param $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/anomalies/update_filter",name="report_cashbox_counts_anomalies_update", options={"expose"=true})
     */
    public function updateFiltersAnomaliesAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $countAnomaliesForm = $this->createForm(CashboxCountsAnomaliesFilterType::class);

            if ($request->getMethod() === "POST") {
                $countAnomaliesForm->handleRequest($request);
                $filter = $countAnomaliesForm->getData();

                $constraint = new DatesReportConstraint();
                $errors = $this->get('validator')->validate($filter, $constraint);

                if ($errors->count() > 0) {
                    foreach ($errors as $error) {
                        $countAnomaliesForm->get($error->getPropertyPath())
                            ->addError(new FormError($error->getMessage()));
                    }
                }

                if ($countAnomaliesForm->isValid() && $errors->count() == 0) {
                    $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
                    $filter["currentRestaurantId"] = $currentRestaurant->getId();
                    $result = $this->get('report.cashbox.service')->getCashboxCountsAnomaliesFilters($filter);

                    return new JsonResponse($result);
                }
            }

            return new JsonResponse();
        } else {
            throw new MethodNotAllowedHttpException('Only XmlHttp request are allowed');
        }
    }
}
