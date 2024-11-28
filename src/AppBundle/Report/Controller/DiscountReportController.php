<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 10/10/2016
 * Time: 09:30
 */

namespace AppBundle\Report\Controller;

use AppBundle\Report\Form\DiscountFormType;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;


/**
 * Class DiscountReportController
 * @package AppBundle\Report\Controller
 * @Route("report/discount")
 */
class DiscountReportController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/discount",name="discount")
     */
    public function indexAction(Request $request)
    {
        $logger = $this->get('monolog.logger.generate_report');
        $data['currentRestaurant'] = $this->get("restaurant.service")->getCurrentRestaurant();
        $data['startDate'] = new \DateTime('Monday this week');
        $data['endDate'] = new \DateTime('Sunday this week');
        $form = $this->createForm(DiscountFormType::class, $data, array(
            'restaurant' => $data['currentRestaurant'],
        ));
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                /**
                 * @var ReportCacheService $reportCacheService
                 */
                $reportCacheService = $this->get('report.cache.service');
                $restaurantId = $data['currentRestaurant']->getId();
                $filterCacheReport = $this->getReportCachedFilter($data);
                $i=rand();
                if (is_null($request->get('download', null)) && is_null(
                        $request->get('xls', null)
                    )) {
                    $logger->addInfo('Generate report discount from ' . $data['startDate']->format('Y-m-d') . ' to ' . $data['endDate']->format('Y-m-d').' '.$i);
                    $t1 = time();
                    $result = $this->get('report.discount.service')->getDiscountList($data);
                    $reportCacheService->cacheReport('discount', $restaurantId, $result, $filterCacheReport);
                    $t2 = time();
                    $logger->addInfo('Generate report discount finish | generate time = ' . ($t2 - $t1) . 'seconds '.$i);
                    return $this->render('@Report/Discount/index_discount_report.html.twig',
                        array('form' => $form->createView(), 'data' => $result, 'generated' => true)
                    );
                } else {
                    $logger->addInfo('Generate report discount from ' . $data['startDate']->format('Y-m-d') . ' to ' . $data['endDate']->format('Y-m-d').' '.$i);
                    $t1 = time();
                    $result = $reportCacheService->getReportCache('discount', $restaurantId, $filterCacheReport);
                    if ($result === null) {
                        $result = $this->get('report.discount.service')->getDiscountList($data);
                        $reportCacheService->cacheReport('discount', $restaurantId, $result, $filterCacheReport);
                    }
                    $t2 = time();
                    $logger->addInfo('Generate report discount finish | generate time = ' . ($t2 - $t1) . 'seconds '.$i);
                    if (!is_null($request->get('download', null)) && is_null(
                            $request->get('xls', null)
                        )) {
                        //Téléchargement PDF
                        $filename = "discount_report_" . date('Y_m_d_H_i_s') . ".pdf";
                        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig($filename,
                            '@Report/Discount/export/export_report_discount.html.twig',
                            [
                                "form", $form->createView(),
                                "data" => $result,
                                "generated" => true,
                                'download' => true]
                            , [
                                'orientation' => 'Portrait',
                                'page-size' => "A4",
                                'footer-center' => 'Page [page] of [toPage]',
                                'footer-font-size' => 8
                            ]);

                        return Utilities::createFileResponse($filepath, $filename);
                    } else {
                        if (!is_null($request->get('xls', null)) && is_null(
                                $request->get('download', null)
                            )) {
                            $logoPath = $this->get('kernel')->getRootDir() . '/../web/src/images/logo.png';
                            $response = $this->get('report.discount.service')->generateExcelFile($result, $data, $data['currentRestaurant'], $logoPath);

                            return $response;
                        }
                    }
                }


            }
        }
        return $this->render('@Report/Discount/index_discount_report.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @param $data
     * @return array
     */
    private function getReportCachedFilter($data)
    {
        $filter = ['startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
            'startHour' => $data['startHour'],
            'endHour' => $data['endHour'],
            'InvoiceNumber' => $data['InvoiceNumber'],
            'discountPerCentMin' => $data['discountPerCentMin'],
            'discountPerCentMax' => $data['discountPerCentMax']
        ];
        $cashier = $data['cashier'];
        if (is_object($cashier)) {
            $filter['cashier'] = $cashier->getId();
        }
        return $filter;
    }
}

