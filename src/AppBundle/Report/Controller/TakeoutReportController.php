<?php
/**
 * Created by PhpStorm.
 * User: hmnaouar
 * Date: 30/05/2018
 * Time: 10:10
 */

namespace AppBundle\Report\Controller;

use AppBundle\Report\Entity\GenericCachedReport;
use AppBundle\Report\Form\TakeOutFormType;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TakeoutReportController
 * @package AppBundle\Report\Controller
 * @Route("/takeout")
 */
class TakeoutReportController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/", name="takeout_report")
     */
    public function indexAction(Request $request)
    {
        $logger=$this->get('monolog.logger.generate_report');
        $data['startDate'] = new \DateTime('Monday this week');
        $data['endDate'] = new \DateTime('Sunday this week');
        $form = $this->createForm(TakeOutFormType::class, $data);
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $i=rand();
                $data = $form->getData();
                $logger->addInfo('Generate report take out by '.$currentRestaurant->getCode().' from '.$data['startDate']->format('Y-m-d').' to '.$data['endDate']->format('Y-m-d').' '.$i);
                $t1 = time();
                $result=$this->getTakeoutReportResult($request,$data,$currentRestaurant->getId());
                $t2 = time();
                $logger->addInfo('Generate report take out finish | generate time = '. ($t2 - $t1) .'seconds by '.$currentRestaurant->getCode().' '.$i);
                if (is_null($request->get('download', null)) && is_null($request->get('export', null)) && is_null($request->get('xls', null))) {
                    return $this->render('@Report/Takeout/index_takeout_report.html.twig', array('form' => $form->createView(), 'data' => $result, 'generated' => true));
                } else if (!is_null($request->get('download', null)) && is_null($request->get('export', null)) && is_null($request->get('xls', null))) {
                    //Téléchargement PDF
                    $filename = "takeout_report_" . date('Y_m_d_H_i_s') . ".pdf";
                    $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig($filename, '@Report/Takeout/export/export_takeout.html.twig',
                        [
                            "form", $form->createView(),
                            "data" => $result,
                            "generated" => true,
                            "exported" => true,
                            "download" => true
                        ],
                        [
                            'orientation' => 'Portrait',
                        ]);

                    return Utilities::createFileResponse($filepath, $filename);
                } else if (!is_null($request->get('xls', null)) && is_null($request->get('export', null)) && is_null($request->get('download', null))) {
                    $logoPath = $this->get('kernel')->getRootDir() . '/../web/src/images/logo.png';
                    $response = $this->get('report.takeout.service')->generateTakeoutReportExcelFile($result, $data['startDate'], $data['endDate'], $logoPath);

                    return $response;
                }
            }
        }

        return $this->render('@Report/Takeout/index_takeout_report.html.twig', array('form' => $form->createView()));
    }

    /**
     * @param $request
     * @param $data
     * @param $rid
     * @return mixed
     */
    private function getTakeoutReportResult($request, $data, $rid)
    {

        /**
         * @var ReportCacheService $reportCacheService
         */
        $reportCacheService = $this->get('report.cache.service');
        $filterCacheReport['startDate'] = $data['startDate'];
        $filterCacheReport['endDate'] = $data['endDate'];
        $cashier=$data['cashier'];
        if(is_object($cashier)){
            $filterCacheReport['cashier'] = $cashier->getId();
        }
        if (!$this->isRequestForExportTakeoutReport($request)) {
            $result = $this->get('report.takeout.service')->getList($data);
            $reportCacheService->cacheReport('takeout', $rid, $result, $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
        } else {
            $result = $reportCacheService->getReportCache('takeout', $rid, $filterCacheReport);
            if ($result === null) {
                $result = $this->get('report.takeout.service')->getList($data);
                $reportCacheService->cacheReport('takeout', $rid, $result, $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
            }
        }
        return $result;
    }

    private function isRequestForExportTakeoutReport($request)
    {
        return !(is_null($request->get('download', null)) && is_null($request->get('export', null)) && is_null($request->get('xls', null)));
    }

}