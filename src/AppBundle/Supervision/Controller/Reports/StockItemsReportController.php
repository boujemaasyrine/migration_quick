<?php


namespace AppBundle\Supervision\Controller\Reports;


use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Report\Entity\GenericCachedReport;
use AppBundle\Report\Form\StockControlFilterType;
use AppBundle\Report\Service\ReportCacheService;
use AppBundle\Report\Validator\DatesReportConstraint;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class StockItemsReportController
 * @package AppBundle\Supervision\Controller\Reports
 * @Route("report")
 */
class StockItemsReportController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/final_stock",name="report_final_stock",options={"expose"=true})
     */
    public function generateSupervisionStockReport(Request $request)
    {

      // throwing an access denied exception
        
      //  throw new AccessDeniedException();


        $logger = $this->get('monolog.logger.generate_report');
        $currectDay =  new \DateTime();
        if (!$request->isXmlHttpRequest()) {
            $em = $this->getDoctrine()->getManager();
            $stockControlForm = $this->createForm(
                StockControlFilterType::class, null);
            if ($request->getMethod() === "GET") {
                return $this->render(
                    "@Supervision/Reports/Merchandise/StockItemsReport/index_stock_control_report.html.twig",
                    [ "form" => $stockControlForm->createView(),

                    ]
                );

            } elseif ($request->getMethod() === "POST") {

                $locale = $request->getLocale();
                $stockControlForm->handleRequest($request);
                $filter = $stockControlForm->getData();


                $filter["restaurants"] = $this->get('report.stock.service')->getAllRestaurants($filter);

                $total = count( $filter["restaurants"]);

                $em->flush();
//                $constraint = new DatesReportConstraint();
//
//                $errors = $this->get('validator')->validate(
//                    $filter,
//                    $constraint
//                );

                if ($stockControlForm->isValid() ) {
                    $em->flush();
                    /**
                     * @var ReportCacheService $reportCacheService
                     */
                    $reportCacheService = $this->get('report.cache.service');
                    $filterCacheReport = $filter;
                    $filterCacheReport = $this->getStockReportCachedFilter($filterCacheReport);
                    $i = rand();

                    if (is_null($request->get('download', null))
                        && is_null(
                            $request->get('export', null)
                        )
                        && is_null($request->get('xls', null))
                    ) {

                        $logger->addInfo('Generate stock report  of  ' . $currectDay->format('Y-m-d')  . ' ' . $i);
                        $t1 = time();



                        $this->getDoctrine()->getManager()->flush();
                        $result = $this->get('report.stock.service')
                            ->generateSupervisionRealStockItems($filter, $locale);

                       $res =is_object($filter['restaurants']) ? $filter["restaurants"]->toArray():$filter["restaurants"];
                        $restaurants = array_map("current", $res);
                        $progression = new ImportProgression();
                        $progression->setNature('supervision_foodcost_report')
                            ->setStatus('pending')->setStartDateTime(new \DateTime());
                        $progression->setProceedElements(0)->setTotalElements($total);
                        $this->getDoctrine()->getManager()->persist($progression);
                        $this->getDoctrine()->getManager()->flush();
                        $filename = 'reportFoodCost'.date('Y_m_d_H_i_s').'.xls';
                        $request->getSession()->set(
                            'foodCost_report_filename',
                            $filename
                        );
                        $cmd = 'saas:foodcost:sql '.$currectDay->format(
                                'Y-m-d'
                            )
                            .' '.$currectDay->format(
                                'Y-m-d'
                            ).' '
                            .$progression->getId().' '.$filename.' '.implode(
                                " ",
                                $restaurants
                            );

                    $this->get('toolbox.command.launcher')->execute(
                            $cmd
                        );


                        return $this->render(
                            '@Supervision/Reports/Merchandise/StockItemsReport/index_stock_control_report.html.twig',
                            [
                                "form" => $stockControlForm->createView(),
                                "reportResult" => $result,
                                "generated" => true,
                               'progressID' => $progression->getId()
                            ]
                        );
                    } else {
                        $logger->addInfo('Generate report stock control from ' . $currectDay->format('Y-m-d') . ' to ' . $currectDay->format('Y-m-d') . ' ' . $i);
                        $t1 = time();
//                        $result = $reportCacheService->getReportCache('stockControl',
//                            $restaurantId, $filterCacheReport);
//
//                        if ($result === null) {
                            $result = $this->get('report.stock.service')
                                ->generateSupervisionRealStockItems($filter, $locale);

                          //  $reportCacheService->cacheReport('stockControl', $restaurantId, $result,
                           //     $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
                      //  }
                        $t2 = time();
                        $logger->addInfo('\'Generate report stock control finish | generate time = ' . ($t2 - $t1) . 'seconds' . $i);
                        if (!is_null($request->get('download', null))
                            && is_null($request->get('export', null))
                            && is_null($request->get('xls', null))
                        ) {
                            $filename = "stock_control_report_" . date(
                                    'Y_m_d_H_i_s'
                                ) . ".pdf";

                            $filepath = $this->get(
                                'toolbox.pdf.generator.service'
                            )->generatePdfFromTwig(
                                $filename,
                                '@Supervision/Reports/Merchandise/StockItemsReport/export/stock_report.html.twig',
                                [
                                    "reportResult" => $result,
                                    "download" => true,

                                ]
                            );

                            return Utilities::createFileResponse(
                                $filepath,
                                $filename
                            );
                        } else {
                            if (!is_null($request->get('xls', null))
                                && is_null(
                                    $request->get('export', null)
                                )
                                && is_null($request->get('download', null))
                            ) {
                                $logoPath = $this->get('kernel')->getRootDir()
                                    . '/../web/src/images/logo.png';
                                $response = $this->get('report.stock.service')
                                    ->generateStockControlExcelFile(
                                        $result,
                                        $logoPath
                                    );

                                return $response;
                            }
                        }
                    }
                } else {
//                    if ($errors->count() > 0) {
//                        $this->get('session')->getFlashBag()->add(
//                            'error',
//                            $errors->get(0)->getMessage()
//                        );
//                    }

                    return $this->render(
                        '@Supervision/Reports/Merchandise/StockItemsReport/index_stock_control_report.html.twig',
                        [
                            "form" => $stockControlForm->createView(),
                        ]
                    );
                }
            }
        } else {
            throw new MethodNotAllowedHttpException(
                [''],
                'Only http request are allowed'
            );
        }


    }

    /**
     * @param $data
     * @return array
     */
    private function getStockReportCachedFilter($data)
    {
        $filter = [
            'restaurant' => $data['restaurants'],
            'products' => $data['products']
        ];
        return $filter;
    }

}