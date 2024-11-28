<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 10/05/2016
 * Time: 11:10
 */

namespace AppBundle\Report\Controller;

use AppBundle\Report\Entity\GenericCachedReport;
use AppBundle\Report\Form\CashbookReportType;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CashBookReportController
 *
 * @package                  AppBundle\Report\Controller
 * @Route("report/cashBook")
 */
class CashBookReportController extends Controller
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/", name="cash_book_report")
     */
    public function generateCashBookReportAction(Request $request)
    {
        $logger=$this->get('monolog.logger.generate_report');
        //        $this->get('ticket.service')->importTickets(new \DateTime('now'));
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $lastClosingDate = $this->getDoctrine()->getRepository('Financial:AdministrativeClosing')->getLastClosingDate(
            $currentRestaurant
        );
        $data['startDate'] = $lastClosingDate;
        $data['endDate'] = clone $data['startDate'];

        $form = $this->createForm(CashbookReportType::class, $data, array('lastClosingDate' => $lastClosingDate));

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $data = $form->getData();
                /**
                 * @var ReportCacheService $reportCacheService
                 */
                $reportCacheService = $this->get('report.cache.service');
                $restaurantId = $currentRestaurant->getId();
                if (is_null($request->get('download', null)) && is_null($request->get('export', null))) {
                    $i = rand();
                    $logger->addInfo('Generate report cashbook by ' . $currentRestaurant->getCode() . ' from ' . $data['startDate']->format('Y-m-d') . ' to ' . $data['endDate']->format('Y-m-d') . ' ' . $i);
                    $t1 = time();
                    $result = $this->get('cash.book.report')->getAllCashBookResultBetweenTwoDates(
                        $form->getData(),
                        $currentRestaurant
                    );
                    $reportCacheService->cacheReport('cashBook', $restaurantId, $result,
                        $data, GenericCachedReport::REPORT_EXPIRED_TIME);
                    $t2 = time();
                    $logger->addInfo('Generate report cashbook finish | generate time = ' . ($t2 - $t1) . 'seconds by ' . $currentRestaurant->getCode() . ' ' . $i);
                    return $this->render(
                        '@Report/Revenue/CashBook/index.html.twig',
                        [
                            "form" => $form->createView(),
                            "generated" => true,
                            "result" => $result,
                        ]
                    );
                } else {
                    $result = $reportCacheService->getReportCache('cashBook',
                        $restaurantId, $data);
                    if ($result === null) {
                        $result = $this->get('cash.book.report')->getAllCashBookResultBetweenTwoDates(
                            $data,
                            $currentRestaurant
                        );
                        $reportCacheService->cacheReport('cashBook', $restaurantId, $result,
                            $data, GenericCachedReport::REPORT_EXPIRED_TIME);
                    }

                    if (is_null($request->get('export', null))) {
                        $filename = "Livre_caisse".date('Y_m_d_H_i_s').".pdf";
                        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                            $filename,
                            '@Report/Revenue/CashBook/report.html.twig',
                            [
                                'result' => $result,
                                "download" => true,
                                "filter" => $form->getData(),
                            ],
                            [
                                'orientation' => 'Portrait',
                            ]
                        );

                        return Utilities::createFileResponse($filepath, $filename);
                    } else {
                        $logoPath = $this->get('kernel')->getRootDir().'/../web/src/images/logo.png';
                        $response = $this->get('cash.book.report')->createExcelFileSecondVersion(
                            $result,
                            $form->getData(),
                            $currentRestaurant,
                            $logoPath
                        );

                        return $response;
                    }
                }
            }
        }

        return $this->render(
            "@Report/Revenue/CashBook/index.html.twig",
            [
                'form' => $form->createView(),
            ]
        );
    }
}
