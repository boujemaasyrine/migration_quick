<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 21/03/2016
 * Time: 16:38
 */

namespace AppBundle\Report\Controller;

use AppBundle\Security\RightAnnotation;
use AppBundle\ToolBox\Utils\Utilities;
use AppBundle\ToolBox\Utils\DateUtilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use \DateTime;
use \DateInterval;

/**
 * Class BuyingReportController
 *
 * @package                AppBundle\Report\Controller
 * @Route("report/buying")
 */
class BuyingReportController extends Controller
{
    /**
     * @RightAnnotation ("report_in_out")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/in_out",name="report_in_out")
     */
    public function generateInOutReportAction(Request $request)
    {
        $logger=$this->get('monolog.logger.generate_report');
        if (!$request->isXmlHttpRequest()) {
            if ($request->getMethod() === "GET") {
                $currentBeginDate = date('01/m/Y');
                $currentEndDate = new DateTime(date('m/01/Y'));
                $interval = new DateInterval('P' . (date("t") - 1) . 'D');
                $end = $currentEndDate->add($interval);
                $currentEndDate = $end->format('d/m/Y');
                $filter['beginDate'] = $currentBeginDate;
                $filter['endDate'] = $currentEndDate;

                return $this->render(
                    "@Report/BuyingManagement/InOut/index_in_out_report.html.twig",
                    [
                        'filter' => $filter,
                    ]
                );
            } elseif ($request->getMethod() === "POST") {
                $errors = array();
                $filter = $request->request->all();
                $beginDate = $request->request->get('beginDate');
                $endDate = $request->request->get('endDate');
                if ($beginDate != null && $endDate != null) {
                    $begin = date_format(date_create_from_format('d/m/Y', $beginDate), 'Y-m-d');
                    $end = date_format(date_create_from_format('d/m/Y', $endDate), 'Y-m-d');
                    if ($begin <= $end) {
                        $i=rand();
                        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
                        $filter['currentRestaurantId'] = $currentRestaurant->getId();
                        $logger->addInfo('Generate reportIN_OUT by '.$currentRestaurant->getCode().' from '.$begin.' to '.$end.' '.$i);
                        $t1 = time();
                        $result = $this->get('report.buying.service')->generateInOutReport($filter);
                        $t2 = time();
                        $logger->addInfo('Generate report IN_OUT finish | generate time = '. ($t2 - $t1) .'seconds by '.$currentRestaurant->getCode().' '.$i);

                        if (is_null($request->get('download', null)) && is_null(
                                $request->get('export', null)
                            ) && is_null($request->get('xls', null))) {
                            return $this->render(
                                '@Report/BuyingManagement/InOut/index_in_out_report.html.twig',
                                [
                                    "result" => $result,
                                    "generated" => true,
                                    'beginDate' => $beginDate,
                                    'endDate' => $endDate,
                                    'filter' => $filter,
                                ]
                            );
                        } else {
                            if (!is_null($request->get('download', null)) && is_null(
                                    $request->get('export', null)
                                ) && is_null($request->get('xls', null))) {
                                $filename = "entrees_sorties_" . date('Y_m_d_H_i_s') . ".pdf";
                                $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                                    $filename,
                                    '@Report/BuyingManagement/InOut/report_in_out.html.twig',
                                    [
                                        'result' => $result,
                                        'beginDate' => $begin,
                                        'endDate' => $end,
                                        'filter' => $filter,
                                        "download" => true
                                    ]
                                );

                                return Utilities::createFileResponse($filepath, $filename);
                            } else {
                                if (!is_null($request->get('xls', null)) && is_null(
                                        $request->get('export', null)
                                    ) && is_null($request->get('download', null))) {
                                    $logoPath = $this->get('kernel')->getRootDir() . '/../web/src/images/logo.png';
                                    $response = $this->get('report.buying.service')->generateExcelFile(
                                        $result,
                                        $filter,
                                        $currentRestaurant,
                                        $logoPath
                                    );

                                    return $response;
                                } else {
                                    $result = $this->get('report.buying.service')->serializeInOutReportResult($result);

                                    $filepath = $this->get(
                                        'toolbox.document.generator'
                                    )->getFilePathFromSerializedResult(
                                        [
                                            'Nom',
                                            $this->get('translator')->trans('keyword.invoice'),
                                            $this->get('translator')->trans('keyword.date'),
                                            $this->get('translator')->trans('keyword.total'),
                                        ],
                                        $result
                                    );
                                    $response = Utilities::createFileResponse(
                                        $filepath,
                                        'entrees_sorties_' . date('dmY_His') . ".csv"
                                    );

                                    return $response;
                                }
                            }
                        }
                    } else {
                        $errors['compareDate'] = true;
                    }
                }
                if ($beginDate == null) {
                    $errors['firstDate'] = true;
                }
                if ($endDate == null) {
                    $errors['secondDate'] = true;
                }

                return $this->render(
                    "@Report/BuyingManagement/InOut/index_in_out_report.html.twig",
                    [
                        'filter' => $filter,
                        'errors' => $errors,
                    ]
                );
            }
        } else {
            throw new MethodNotAllowedHttpException('Only http request are allowed');
        }
    }
}
