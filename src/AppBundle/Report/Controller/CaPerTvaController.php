<?php
/**
 * Created by PhpStorm.
 * User: bchebbi
 * Date: 30/07/2018
 * Time: 10:30
 */

namespace AppBundle\Report\Controller;


use AppBundle\Report\Entity\GenericCachedReport;
use AppBundle\Report\Form\CaPerTvaFormType;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class CaPerTvaController
 * @package AppBundle\Report\Controller
 * @Route("report/ca_per_tva")
 */
class CaPerTvaController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/",name="ca_per_tva")
     */
    public function caPerTvaReportAction(Request $request)
    {
        $logger=$this->get('monolog.logger.generate_report');
        $data['startDate'] = new \DateTime('Monday this week');
        $data['endDate'] = new \DateTime('Sunday this week');
        $form = $this->createForm(CaPerTvaFormType::class, $data);
        $translator=$this->get('translator');
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $i=rand();
                $data = $form->getData();
                $filter['startDate']=$data['startDate']->format('Y-m-d');
                 $filter['endDate']=$data['endDate']->format('Y-m-d');
                $logger->addInfo('Generate report CA per tva by '.$currentRestaurant->getCode().' from '.$filter['startDate'].' to '.$filter['endDate'].' '.$i);
                $t1 = time();
                $result=$this->getcaPerTvaReportResult($request,$data,$currentRestaurant->getId());

                $t2 = time();
                $logger->addInfo('Generate report CA per tva finish | generate time = '. ($t2 - $t1) .'seconds by '.$currentRestaurant->getCode().' '.$i);
                if (is_null($request->get('download', null)) && is_null(
                        $request->get('xls', null)
                    )) {
                    return $this->render('@Report/CaPerTva/index_report.html.twig',
                        array('form' => $form->createView(), 'data' => $result, 'generated' => true)
                    );
                } else {
                    if (!is_null($request->get('download', null)) && is_null(
                            $request->get('xls', null)
                        )) {
                        //Téléchargement PDF
                        $filename = $translator->trans('ca_per_tva.report_title') . date('Y_m_d_H_i_s') . ".pdf";
                        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig($filename,
                            '@Report/CaPerTva/export_report.html.twig',
                            array(
                                "form", $form->createView(),
                                "data" => $result,
                                'filter' => $filter,
                                "generated" => true)
                            , array(
                                'orientation' => 'Portrait',
                                'page-size' => "A4"
                            ));

                        return Utilities::createFileResponse($filepath, $filename);
                    }else{
                        $logoPath = $this->get('kernel')->getRootDir() . '/../web/src/images/logo.png';
                        $response = $this->get('report.ca.per.tva.service')->generateExcelFile($result, $data, $currentRestaurant, $logoPath);

                        return $response;
                    }
               return $this->render('@Report/CaPerTva/index_report.html.twig',
                        array('form' => $form->createView(), 'data' => $result, 'generated' => true)
                    );
                }
            }

        }
        return $this->render('@Report/CaPerTva/index_report.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @param $request
     * @param $data
     * @return mixed
     */
    private function getcaPerTvaReportResult($request, $data,$rid)
    {

        /**
         * @var ReportCacheService $reportCacheService
         */
        $reportCacheService = $this->get('report.cache.service');
        $filterCacheReport['startDate']=$data['startDate']->format('Y-m-d');
        $filterCacheReport['endDate']=$data['endDate']->format('Y-m-d');
        if (!$this->isRequestForExportcaPerTvaReport($request)) {
            $result = $this->get('report.ca.per.tva.service')->getGlobalCa($data);
            $reportCacheService->cacheReport('ca_per_tva', $rid, $result, $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
        } else {
            $result = $reportCacheService->getReportCache('ca_per_tva', $rid, $filterCacheReport);
            if ($result === null) {
                $result = $this->get('report.ca.per.tva.service')->getGlobalCa($data);
                $reportCacheService->cacheReport('ca_per_tva', $rid, $result, $filterCacheReport, GenericCachedReport::REPORT_EXPIRED_TIME);
            }
        }
        return $result;
    }

    private function isRequestForExportcaPerTvaReport($request)
    {
        return !(is_null($request->get('export', null)));
    }

}