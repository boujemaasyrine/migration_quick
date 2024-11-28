<?php	
/**	
 * Created by PhpStorm.	
 * User: mchrif	
 * Date: 13/03/2016	
 * Time: 10:22	
 */	
namespace AppBundle\Report\Controller;	
use AppBundle\Administration\Entity\Parameter;	
use AppBundle\Merchandise\Entity\ProductCategories;	
use AppBundle\Report\Entity\GenericCachedReport;	
use AppBundle\Report\Form\PortionControlFilterType;	
use AppBundle\Report\Service\ReportCacheService;	
use AppBundle\Report\Validator\DatesReportConstraint;	
use AppBundle\Security\RightAnnotation;	
use AppBundle\ToolBox\Utils\Utilities;	
use AppBundle\ToolBox\Utils\DateUtilities;	
use Entity\Category;	
use Symfony\Bundle\FrameworkBundle\Controller\Controller;	
use Symfony\Component\HttpFoundation\Request;	
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;	
use Symfony\Component\HttpFoundation\Response;	
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;	
/**	
 * Class MerchandiseReportController	
 *	
 * @package                     AppBundle\Report\Controller	
 * @Route("report/merchandise")	
 */	
class MerchandiseReportController extends Controller	
{	
    /**	
     * @RightAnnotation("report_portion_control")	
     * @param Request $request	
     *	
     * @return \Symfony\Component\HttpFoundation\Response	
     * @Route("/portion_control",name="report_portion_control")	
     */	
    public function generatePortionControlReportAction(Request $request)	
    {	
    	
        $logger = $this->get('monolog.logger.generate_report');	
        if (!$request->isXmlHttpRequest()) {	
            $currentRestaurant = $this->get("restaurant.service")	
                ->getCurrentRestaurant();	
            $em = $this->getDoctrine()->getManager();	
            $oThreshold = $em->getRepository(Parameter::class)	
                ->findOneBy(	
                    [	
                        'originRestaurant' => $currentRestaurant,	
                        'type' => Parameter::PORTION_CONTROL_THRESHOLD,	
                    ]	
                );	
            if (!is_object($oThreshold)) {	
                $oThreshold=new Parameter();	
                $oThreshold->setOriginRestaurant($currentRestaurant);	
                $oThreshold->setValue('25');	
                $oThreshold->setType(Parameter::PORTION_CONTROL_THRESHOLD);	
                $em->persist($oThreshold);	
                $em->flush();	
            }	
            $threshold = $oThreshold->getValue();	
            $selectedCategories = $em->getRepository(Parameter::class)	
                ->findOneBy(	
                    [	
                        'originRestaurant' => $currentRestaurant,	
                        'type' => Parameter::PORTION_CONTROL_SELECTED_CATEGORIES,	
                    ]	
                );	
            if (!$selectedCategories) {	
                $foodCostCategories = $em->getRepository(	
                    ProductCategories::class	
                )	
                    ->createQueryBuilder('pc')	
                    ->join('pc.categoryGroup', 'cg')	
                    ->where('cg.isFoodCost = true')	
                    ->getQuery()	
                    ->getResult();	
                $ids = [];	
                foreach ($foodCostCategories as $category) {	
                    $ids[] = $category->getId();	
                }	
                $selectedCategories = new Parameter();	
                $selectedCategories->setType(	
                    Parameter::PORTION_CONTROL_SELECTED_CATEGORIES	
                )	
                    ->setValue($ids)	
                    ->setOriginRestaurant($currentRestaurant);	
                $em->persist($selectedCategories);	
                $em->flush();	
            } else {	
                $foodCostCategories = [];	
                foreach ($selectedCategories->getValue() as $category) {	
                    $foodCostCategory = $em->getRepository(	
                        ProductCategories::class	
                    )->find($category);	
                    if ($foodCostCategory) {	
                        $foodCostCategories[] = $foodCostCategory;	
                    }	
                }	
            }	
            $portionControlForm = $this->createForm(	
                PortionControlFilterType::class,	
                [	
                    "startDate" => null,	
                    "endDate" => null,	
                    "selection" => null,	
                    "threshold" => $threshold,	
                    'code' => null,	
                    'category' => null,	
                    'name' => null,	
                ],	
                array(	
                    "currentRestaurant" => $currentRestaurant,	
                )	
            );	
            $portionControlForm->get('category')->setData($foodCostCategories);	
            if ($request->getMethod() === "GET") {	
                return $this->render(	
                    "@Report/MerchandiseManagement/PortionControl/index_portion_control.html.twig",	
                    [	
                        "portionControlForm" => $portionControlForm->createView(),	
                    ]	
                );	
            } elseif ($request->getMethod() === "POST") {	
                $locale = $request->getLocale();	
                $portionControlForm->handleRequest($request);	
                $filter = $portionControlForm->getData();	
                $oThreshold->setValue($filter['threshold']);	
                $em->flush();	
                $filter["currentRestaurantId"] = $currentRestaurant->getId();	
                $i = rand();	
                $constraint = new DatesReportConstraint();	
                $errors = $this->get('validator')->validate(	
                    $filter,	
                    $constraint	
                );	
                if ($portionControlForm->isValid() && $errors->count() == 0) {	
                	
                $lock = $this->get('product_purchased_mvmt.service')->checkLockedPortion($currentRestaurant->getId());		
                    if ($lock->getValue() == 0) {		
                        $lock->setValue(1);	
                    $ids = [];	
                    foreach ($filter["category"] as $category) {	
                        $ids[] = $category->getId();	
                    }	
                    $selectedCategories->setValue($ids);	
                    $em->flush();	
                    /**	
                     * @var ReportCacheService $reportCacheService	
                     */	
                    $reportCacheService = $this->get('report.cache.service');	
                    $restaurantId = $currentRestaurant->getId();	
                    $filterCacheReport = $filter;	
                    $filterCacheReport['ids'] = $ids;	
                    $filterCacheReport = $this->getReportCachedFilter($filterCacheReport);	
                    if (is_null($request->get('download', null))	
                        && is_null(	
                            $request->get('export', null)	
                        )	
                        && is_null($request->get('xls', null))	
                    ) {	
                        $logger->addInfo('Generate report portion control from ' . $filter['startDate']->format('Y-m-d') . ' to ' . $filter['endDate']->format('Y-m-d').' by '.$currentRestaurant->getCode() .' '.$i);	
                        $t1 = time();	
                  
                        $result = $this->get('report.stock.service')	
                            ->generateEcartsPortionControlReport($filter, $locale, 0);	
                         $lock->setValue(0);   	
                        $reportCacheService->cacheReport('portionControl', $restaurantId, $result,	
                            $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);	
                        $t2 = time();	
                        $logger->addInfo('Generate report portion control finish | generate time = ' . ($t2 - $t1) . 'seconds by '.$currentRestaurant->getCode().' ' . $i);	
                        return $this->render(	
                            '@Report/MerchandiseManagement/PortionControl/index_portion_control.html.twig',	
                            [	
                                "portionControlForm" => $portionControlForm->createView(),	
                                "reportResult" => $result,	
                                "generated" => true,	
                            ]	
                        );	
                    } else {	
                        $logger->addInfo('Generate report portion control from ' . $filter['startDate']->format('Y-m-d') . ' to ' . $filter['endDate']->format('Y-m-d').' by '.$currentRestaurant->getCode() . ' ' . $i);	
                        $t1 = time();	
                        $result = $reportCacheService->getReportCache('portionControl',	
                            $restaurantId, $filterCacheReport);	
                        if ($result === null) {	
                            $result = $this->get('report.stock.service')	
                                ->generateEcartsPortionControlReport($filter, $locale, 0);	
                            $reportCacheService->cacheReport('portionControl', $restaurantId, $result,	
                                $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);	
                        }	
                        $t2 = time();	
                        $logger->addInfo('Generate report portion control finish | generate time = ' . ($t2 - $t1) . 'seconds by '.$currentRestaurant->getCode() . ' '.$i);	
                        $lock->setValue(0);
                        $em->flush();
                        if (!is_null($request->get('download', null))	
                            && is_null($request->get('export', null))	
                            && is_null($request->get('xls', null))	
                        ) {	
                            $filename = "portion_control_report_" . date(	
                                    'Y_m_d_H_i_s'	
                                ) . ".pdf";	
                            $filepath = $this->get(	
                                'toolbox.pdf.generator.service'	
                            )->generatePdfFromTwig(	
                                $filename,	
                                '@Report/MerchandiseManagement/PortionControl/exports/portion_control_report.html.twig',	
                                [	
                                    "portionControlForm",	
                                    $portionControlForm->createView(),	
                                    "reportResult" => $result,	
                                    "download" => true,	
                                ],	
                                [	
                                    'orientation' => 'Portrait',	
                                    'footer-center' => '[page]',	
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
                                    ->generateProtionControlExcelFile(	
                                        $result,	
                                        $currentRestaurant,	
                                        $logoPath	
                                    );	
                                return $response;	
                            }	
                        }	
                    }	
                }	
                else		
                    {		
                        return $this->render(		
                            "@Report/MerchandiseManagement/PortionControl/index_portion_control.html.twig",		
                            [		
                                "portionControlForm" => $portionControlForm->createView(),		
                                "locked" =>true		
                            ]		
                        );		
                    }	
                 }	
                 else {	
                    if ($errors->count() > 0) {	
                        $this->get('session')->getFlashBag()->add(	
                            'error',	
                            $errors->get(0)->getMessage()	
                        );	
                    }	
                    return $this->render(	
                        '@Report/MerchandiseManagement/PortionControl/index_portion_control.html.twig',	
                        [	
                            "portionControlForm" => $portionControlForm->createView(),	
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
    private function getReportCachedFilter($data)	
    {	
        $filter = ['startDate' => $data['startDate'],	
            'endDate' => $data['endDate'],	
            'selection' => $data['selection'],	
            'threshold' => $data['threshold'],	
            'code' => $data['code'],	
            'ids' => $data['ids'],	
            'name' => $data['name']	
        ];	
        return $filter;	
    }	
    /**	
     * @RightAnnotation ("report_inventory_loss")	
     * @param Request $request	
     *	
     * @return \Symfony\Component\HttpFoundation\Response	
     * @Route("/inventory_loss",name="report_inventory_loss")	
     */	
    public function generateLossInventoryReportAction(Request $request)	
    {	
        $logger = $this->get('monolog.logger.generate_report');	
        $currentRestaurant = $this->get("restaurant.service")	
            ->getCurrentRestaurant();	
        $categories = $this->getDoctrine()->getRepository(	
            ProductCategories::class	
        )	
            ->createQueryBuilder("c")	
            ->join("c.products", "pr")	
            ->where("pr.originRestaurant = :restaurant")	
            ->setParameter("restaurant", $currentRestaurant)	
            ->getQuery()	
            ->getResult();	
        if (!$request->isXmlHttpRequest()) {	
            if ($request->getMethod() === "GET") {	
                return $this->render(	
                    "@Report/MerchandiseManagement/Loss/index_inventory_loss.html.twig",	
                    array(	
                        'categories' => $categories,	
                    )	
                );	
            } elseif ($request->getMethod() === "POST") {	
                $errors = array();	
                $filter = $request->request->all();	
                $beginDate = $request->request->get('beginDate');	
                $endDate = $request->request->get('endDate');	
                $i = rand();	
                if ($beginDate != null && $endDate != null) {	
                    $begin = date_format(	
                        date_create_from_format('d/m/Y', $beginDate),	
                        'Y-m-d'	
                    );	
                    $end = date_format(	
                        date_create_from_format('d/m/Y', $endDate),	
                        'Y-m-d'	
                    );	
                    $nbrDayWeek = DateUtilities::getNbrDays($begin, $end);	
                    if ($begin <= $end) {	
                        $filter["currentRestaurantId"]	
                            = $currentRestaurant->getId();	
                        $logger->addInfo('Generate report inventory loss from ' . $begin . ' to ' . $end.' '.$i);	
                        $t1 = time();	
                        $resultAll = $this->get('report.stock.service')	
                            ->generateInventoryLossReport($filter);	
                        $t2 = time();	
                        $logger->addInfo('Generate report inventory loss finish | generate time = ' . ($t2 - $t1) . 'seconds '.$i);	
                        $result = $resultAll['0'];	
                        $avg = $resultAll['1'];	
                        $total = $resultAll['2'];	
                        $financialRevenue = $resultAll['3'];	
                        $proportion = $resultAll['4'];	
                        if (is_null($request->get('download', null))	
                            && is_null(	
                                $request->get('export', null)	
                            )	
                            && is_null($request->get('xls', null))	
                        ) {	
                            return $this->render(	
                                '@Report/MerchandiseManagement/Loss/index_inventory_loss.html.twig',	
                                [	
                                    "result" => $result,	
                                    "generated" => true,	
                                    'beginDate' => $beginDate,	
                                    'endDate' => $endDate,	
                                    'avg' => $avg,	
                                    'total' => $total,	
                                    'nbrDayWeek' => $nbrDayWeek,	
                                    'financialRevenue' => $financialRevenue,	
                                    'proportion' => $proportion,	
                                    'filter' => $filter,	
                                    'categories' => $categories,	
                                ]	
                            );	
                        } else {	
                            if (!is_null($request->get('download', null))	
                                && is_null(	
                                    $request->get('export', null)	
                                )	
                                && is_null($request->get('xls', null))	
                            ) {	
                                $filename = "perte_item_inventaire_" . date(	
                                        'Y_m_d_H_i_s'	
                                    ) . ".pdf";	
                                $filepath = $this->get(	
                                    'toolbox.pdf.generator.service'	
                                )->generatePdfFromTwig(	
                                    $filename,	
                                    '@Report/MerchandiseManagement/Loss/report_inventory_loss.html.twig',	
                                    [	
                                        'result' => $result,	
                                        'total' => $total,	
                                        'avg' => $avg,	
                                        'beginDate' => $begin,	
                                        'endDate' => $end,	
                                        'nbrDayWeek' => $nbrDayWeek,	
                                        'financialRevenue' => $financialRevenue,	
                                        'proportion' => $proportion,	
                                        "download" => true,	
                                        'filter' => $filter,	
                                        'categories' => $categories,	
                                    ],	
                                    [	
                                        'orientation' => 'Landscape',	
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
                                    $logoPath = $this->get('kernel')	
                                            ->getRootDir()	
                                        . '/../web/src/images/logo.png';	
                                    $response = $this->get(	
                                        'report.stock.service'	
                                    )->generateInventoryLossExcelFile(	
                                        $result,	
                                        $avg,	
                                        $total,	
                                        $nbrDayWeek,	
                                        $financialRevenue,	
                                        $proportion,	
                                        $filter,	
                                        $currentRestaurant,	
                                        $logoPath	
                                    );	
                                    return $response;	
                                } else {	
                                    $result = $this->get('report.stock.service')	
                                        ->serializeLossReportResult(	
                                            $resultAll,	
                                            $nbrDayWeek	
                                        );	
                                    $filepath = $this->get(	
                                        'toolbox.document.generator'	
                                    )->getFilePathFromSerializedResult(	
                                        [],	
                                        $result	
                                    );	
                                    $response = Utilities::createFileResponse(	
                                        $filepath,	
                                        'perte_item_inventaire' . date('dmY_His')	
                                        . ".csv"	
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
                    "@Report/MerchandiseManagement/Loss/index_inventory_loss.html.twig",	
                    array(	
                        'categories' => $categories,	
                        'filter' => $filter,	
                        'errors' => $errors,	
                    )	
                );	
            }	
        } else {	
            throw new MethodNotAllowedHttpException(	
                [''],	
                'Only http request are allowed'	
            );	
        }	
    }	
    /**	
     * @RightAnnotation ("report_sold_loss")	
     * @param Request $request	
     *	
     * @return \Symfony\Component\HttpFoundation\Response	
     * @Route("/sold_loss",name="report_sold_loss")	
     */	
    public function generateLossSoldReportAction(Request $request)	
    {	
        $logger = $this->get('monolog.logger.generate_report');	
        if (!$request->isXmlHttpRequest()) {	
            if ($request->getMethod() === "GET") {	
                return $this->render(	
                    "@Report/MerchandiseManagement/Loss/Sold/index_sold_loss.html.twig"	
                );	
            } elseif ($request->getMethod() === "POST") {	
                $errors = array();	
                $filter = $request->request->all();	
                $beginDate = $request->request->get('beginDate');	
                $endDate = $request->request->get('endDate');	
                $i = rand();	
                if ($beginDate != null && $endDate != null) {	
                    $begin = date_format(	
                        date_create_from_format('d/m/Y', $beginDate),	
                        'Y-m-d'	
                    );	
                    $end = date_format(	
                        date_create_from_format('d/m/Y', $endDate),	
                        'Y-m-d'	
                    );	
                    $nbrDayWeek = DateUtilities::getNbrDays($begin, $end);	
                    if ($begin <= $end) {	
                        $currentRestaurant = $this->get("restaurant.service")	
                            ->getCurrentRestaurant();	
                        $filter["currentRestaurantId"]	
                            = $currentRestaurant->getId();	
                        $logger->addInfo('Generate report sold loss from ' . $begin . ' to ' . $end.' '.$i);	
                        $t1 = time();	
                        $resultAll = $this->get('report.stock.service')	
                            ->generateSoldLossReport($filter);	
                        $t2 = time();	
                        $logger->addInfo('Generate report sold loss finish | generate time = ' . ($t2 - $t1) . 'seconds '.$i);	
                        $result = $resultAll['0'];	
                        $avg = $resultAll['1'];	
                        $total = $resultAll['2'];	
                        $financialRevenue = $resultAll['3'];	
                        $proportion = $resultAll['4'];	
                        if (is_null($request->get('download', null))	
                            && is_null(	
                                $request->get('export', null)	
                            )	
                            && is_null($request->get('xls', null))	
                        ) {	
                            return $this->render(	
                                '@Report/MerchandiseManagement/Loss/Sold/index_sold_loss.html.twig',	
                                [	
                                    "result" => $result,	
                                    "generated" => true,	
                                    'beginDate' => $beginDate,	
                                    'endDate' => $endDate,	
                                    'avg' => $avg,	
                                    'total' => $total,	
                                    'nbrDayWeek' => $nbrDayWeek,	
                                    'financialRevenue' => $financialRevenue,	
                                    'proportion' => $proportion,	
                                    'filter' => $filter,	
                                ]	
                            );	
                        } else {	
                            if (!is_null($request->get('download', null))	
                                && is_null(	
                                    $request->get('export', null)	
                                )	
                                && is_null($request->get('xls', null))	
                            ) {	
                                $filename = "perte_item_vente_" . date(	
                                        'Y_m_d_H_i_s'	
                                    ) . ".pdf";	
                                $filepath = $this->get(	
                                    'toolbox.pdf.generator.service'	
                                )->generatePdfFromTwig(	
                                    $filename,	
                                    '@Report/MerchandiseManagement/Loss/Sold/report_sold_loss.html.twig',	
                                    [	
                                        'result' => $result,	
                                        'total' => $total,	
                                        'avg' => $avg,	
                                        'beginDate' => $begin,	
                                        'endDate' => $end,	
                                        'nbrDayWeek' => $nbrDayWeek,	
                                        'financialRevenue' => $financialRevenue,	
                                        'proportion' => $proportion,	
                                        "download" => true,	
                                        'filter' => $filter,	
                                    ],	
                                    [	
                                        'orientation' => 'Landscape',	
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
                                    $logoPath = $this->get('kernel')	
                                            ->getRootDir()	
                                        . '/../web/src/images/logo.png';	
                                    $response = $this->get(	
                                        'report.stock.service'	
                                    )->generateSoldLossExcelFile(	
                                        $result,	
                                        $avg,	
                                        $total,	
                                        $nbrDayWeek,	
                                        $financialRevenue,	
                                        $proportion,	
                                        $filter,	
                                        $currentRestaurant,	
                                        $logoPath	
                                    );	
                                    return $response;	
                                } else {	
                                    $result = $this->get('report.stock.service')	
                                        ->serializeLossReportResult(	
                                            $resultAll,	
                                            $nbrDayWeek	
                                        );	
                                    $filepath = $this->get(	
                                        'toolbox.document.generator'	
                                    )->getFilePathFromSerializedResult(	
                                        [],	
                                        $result	
                                    );	
                                    $response = Utilities::createFileResponse(	
                                        $filepath,	
                                        'perte_item_vente' . date('dmY_His')	
                                        . ".csv"	
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
                    "@Report/MerchandiseManagement/Loss/Sold/index_sold_loss.html.twig",	
                    array(	
                        'filter' => $filter,	
                        'errors' => $errors,	
                    )	
                );	
            }	
        } else {	
            throw new MethodNotAllowedHttpException(	
                [''],	
                'Only http request are allowed'	
            );	
        }	
    }	
    /**	
     * @RightAnnotation("report_portion_control")	
     * @param Request $request	
     *	
     * @return Response	
     * @Route("/portion_control_three_weeks",name="report_portion_control_three_weeks")	
     */
    public function generateThreeWeeksPortionControlAction(Request $request)
    {
        $logger = $this->get('monolog.logger.generate_report');
        if (!$request->isXmlHttpRequest()) {
            $currentRestaurant = $this->get("restaurant.service")
                ->getCurrentRestaurant();
            $em = $this->getDoctrine()->getManager();

            $selectedCategories = $em->getRepository(Parameter::class)
                ->findOneBy(
                    [
                        'originRestaurant' => $currentRestaurant,
                        'type' => Parameter::PORTION_CONTROL_SELECTED_CATEGORIES,
                    ]
                );

            if (!$selectedCategories) {
                $foodCostCategories = $em->getRepository(
                    ProductCategories::class
                )
                    ->createQueryBuilder('pc')
                    ->join('pc.categoryGroup', 'cg')
                    ->where('cg.isFoodCost = true')
                    ->getQuery()
                    ->getResult();
                $ids = [];
                foreach ($foodCostCategories as $category) {
                    $ids[] = $category->getId();
                }

                $selectedCategories = new Parameter();
                $selectedCategories->setType(
                    Parameter::PORTION_CONTROL_SELECTED_CATEGORIES
                )
                    ->setValue($ids)
                    ->setOriginRestaurant($currentRestaurant);
                $em->persist($selectedCategories);
                $em->flush();
            } else {
                $foodCostCategories = [];
                foreach ($selectedCategories->getValue() as $category) {
                    $foodCostCategory = $em->getRepository(
                        ProductCategories::class
                    )->find($category);
                    if ($foodCostCategory) {
                        $foodCostCategories[] = $foodCostCategory;
                    }
                }
            }

            $portionControlForm = $this->createForm(
                PortionControlFilterType::class,
                [
                    "startDate" => null,
                    "endDate" => null,
                    "selection" => null,
                    "threshold" => 25,
                    'code' => null,
                    'category' => null,
                    'name' => null,
                ],
                array(
                    "currentRestaurant" => $currentRestaurant,
                )
            );
            $portionControlForm->get('category')->setData($foodCostCategories);

            if ($request->getMethod() === "GET") {

                return $this->render(
                    "@Report/MerchandiseManagement/PortionControl/index_portion_control_three_weeks.html.twig",
                    [
                        "portionControlForm" => $portionControlForm->createView(),
                    ]
                );
            } elseif ($request->getMethod() === "POST") {
                $locale = $request->getLocale();
                $portionControlForm->handleRequest($request);
                $filter = $portionControlForm->getData();
                $filter["currentRestaurantId"] = $currentRestaurant->getId();
                $i = rand();
                $constraint = new DatesReportConstraint();
                $errors = $this->get('validator')->validate(
                    $filter,
                    $constraint
                );

                if ($portionControlForm->isValid() && $errors->count() == 0) {
                    $ids = [];
                    foreach ($filter["category"] as $category) {
                        $ids[] = $category->getId();
                    }

                    $selectedCategories->setValue($ids);
                    $em->flush();
                    /**
                     * @var ReportCacheService $reportCacheService
                     */
                    $reportCacheService = $this->get('report.cache.service');
                    $restaurantId = $currentRestaurant->getId();
                    $filterCacheReport = $filter;
                    $filterCacheReport['ids'] = $ids;
                    $filterCacheReport = $this->getReportCachedFilter($filterCacheReport);
                    /**
                     * @var \DateTime $startDate
                     */
                    $startDate = $filter["startDate"];
                    $weekMinus1 = clone $startDate;
                    $weekMinus1->sub(new \DateInterval('P7D'));
                    $weekMinus1 = $weekMinus1->format('d/m/Y');
                    $weekMinus2 = clone $startDate;
                    $weekMinus2->sub(new \DateInterval('P14D'));
                    $weekMinus2 = $weekMinus2->format('d/m/Y');
                    $weekMinus3 = clone $startDate;
                    $weekMinus3 = $weekMinus3->sub(new \DateInterval('P21D'));
                    $weekMinus3 = $weekMinus3->format('d/m/Y');
                    $startDate = $startDate->format('d/m/Y');

                    if (is_null($request->get('download', null))
                        && is_null(
                            $request->get('export', null)
                        )
                        && is_null($request->get('xls', null))
                    ) {
                        $logger->addInfo('Generate report portion control 3 weeks from ' . $filter['startDate']->format('Y-m-d') . ' to ' . $filter['endDate']->format('Y-m-d') . ' ' . $i);
                        $t1 = time();

                        $weeks = array(1,2 ,3) ;
                        foreach ($weeks  as $key => $week){
                            $resultThree = $this->get('report.stock.service')->generateDataPrviousWeeks($week,$currentRestaurant);
                        }


                        $result = $this->get('report.stock.service')->generateEcartsPortionControlReport($filter, $locale, 1);
                        $mergedData = array_replace_recursive($result, $resultThree);

                        $reportCacheService->cacheReport('portion_control_three_weeks', $restaurantId, $mergedData,
                            $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
                        $t2 = time();
                        $logger->addInfo('Generate report portion control 3 weeks finish | generate time = ' . ($t2 - $t1) . 'seconds' . $i);
                        return $this->render(
                            '@Report/MerchandiseManagement/PortionControl/index_portion_control_three_weeks.html.twig',
                            [
                                "portionControlForm" => $portionControlForm->createView(),
                                "reportResult" => $mergedData,
                                "generated" => true,
                                "startDate" => $startDate,
                                "weekMinus1" => $weekMinus1,
                                "weekMinus2" => $weekMinus2,
                                "weekMinus3" => $weekMinus3,
                            ]
                        );
                    } else {
                        $logger->addInfo('Generate report portion control 3 weeks from ' . $filter['startDate']->format('Y-m-d') . ' to ' . $filter['endDate']->format('Y-m-d') . ' ' . $i);
                        $t1 = time();
                        $result = $reportCacheService->getReportCache('portion_control_three_weeks',
                            $restaurantId, $filterCacheReport);
                        if ($result === null) {
                            $result = $this->get('report.stock.service')
                                ->generateEcartsPortionControlReport($filter, $locale, 1);
                            $reportCacheService->cacheReport('portion_control_three_weeks', $restaurantId, $result,
                                $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
                        }
                        $t2 = time();
                        $logger->addInfo('Generate report portion control 3 weeks finish | generate time = ' . ($t2 - $t1) . 'seconds' . $i);

                        if (!is_null($request->get('download', null))
                            && is_null($request->get('export', null))
                            && is_null($request->get('xls', null))
                        ) {
                            $weeks = array(1,2 ,3) ;
                            foreach ($weeks  as $key => $week){
                                $resultThree = $this->get('report.stock.service')->generateDataPrviousWeeks($week,$currentRestaurant);
                            }

                            $mergedData = array_replace_recursive($result, $resultThree);
                            $filename = "portion_control_report_" . date(
                                    'Y_m_d_H_i_s'
                                ) . ".pdf";
                            $filepath = $this->get(
                                'toolbox.pdf.generator.service'
                            )->generatePdfFromTwig(
                                $filename,
                                '@Report/MerchandiseManagement/PortionControl/exports/portion_control_three_weeks_report.html.twig',
                                [
                                    "portionControlForm" => $portionControlForm->createView(),
                                    "reportResult" => $mergedData,
                                    "download" => true,
                                    "startDate" => $startDate,
                                    "weekMinus1" => $weekMinus1,
                                    "weekMinus2" => $weekMinus2,
                                    "weekMinus3" => $weekMinus3,
                                ],
                                [
                                    'orientation' => 'Landscape',
                                    'footer-center' => '[page]',
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
                                $weeks = array(1,2 ,3) ;
                                foreach ($weeks  as $key => $week){
                                    $resultThree = $this->get('report.stock.service')->generateDataPrviousWeeks($week,$currentRestaurant);
                                }
                                $mergedData = array_replace_recursive($result, $resultThree);
                                $response = $this->get('report.stock.service')
                                    ->generateThreeWeeksPortionControlExcelFile(
                                        $mergedData,
                                        $startDate,
                                        $weekMinus1,
                                        $weekMinus2,
                                        $weekMinus3,
                                        $currentRestaurant,
                                        $logoPath
                                    );

                                return $response;
                            }
                        }
                    }
                } else {
                    if ($errors->count() > 0) {
                        $this->get('session')->getFlashBag()->add(
                            'error',
                            $errors->get(0)->getMessage()
                        );
                    }

                    return $this->render(
                        '@Report/MerchandiseManagement/PortionControl/index_portion_control_three_weeks.html.twig',
                        [
                            "portionControlForm" => $portionControlForm->createView(),
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
}