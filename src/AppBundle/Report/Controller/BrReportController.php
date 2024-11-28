<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 14/10/2016
 * Time: 10:04
 */

namespace AppBundle\Report\Controller;

use AppBundle\Report\Form\BrFormType;
use AppBundle\Report\Service\ReportBrService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\ToolBox\Utils\Utilities;


/**
 * Class BrReportController
 * @package AppBundle\Report\Controller
 * @Route("report/br")
 */
class BrReportController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/beneficiary",name="br_beneficiary_report")
     */
    public function beneficiaryReportAction(Request $request)
    {
        $logger=$this->get('monolog.logger.generate_report');
        $currentRestaurant = $this->get("restaurant.service")
            ->getCurrentRestaurant();
        $i=rand();
        $data['startDate'] = new \DateTime('Monday this week');
        $data['endDate'] = new \DateTime('Sunday this week');
        $form = $this->createForm(BrFormType::class, $data);
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $data = $form->getData();
                $logger->addInfo('Generate report br beneficiary by restaurant '.$currentRestaurant->getCode().' from '.$data['startDate']->format('Y-m-d').' to '.$data['endDate']->format('Y-m-d').' '.$i);
                $t1 = time();
                $result = $this->get('report.br.service')->getBrBeneficiaryList($data);
                $t2 = time();
                $logger->addInfo('Generate report  br beneficiary finish | generate time = '. ($t2 - $t1) .'seconds by'.$currentRestaurant->getCode().' '.$i);
                $isEPDF = !empty($request->get('pdf', null));
                $isEXLS = !empty($request->get('xls', null));
                if (!$isEPDF && !$isEXLS) {
                    return $this->render('@Report/BR/beneficiary/index_br_report.html.twig',
                        array('form' => $form->createView(), 'data' => $result, 'generated' => true)
                    );
                } elseif ($isEPDF) {
                    //Téléchargement PDF
                    $filename = "br_report_" . date('Y_m_d_H_i_s') . ".pdf";
                    $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig($filename,
                        '@Report/BR/beneficiary/export/export_report_br.html.twig',
                        [
                            "form", $form->createView(),
                            "data" => $result,
                            "generated" => true,
                            "download" => true
                        ]
                        , [
                            'orientation' => 'Landscape',
                            'page-size' => "A4"
                        ]);

                    return Utilities::createFileResponse($filepath, $filename);
                } elseif ($isEXLS) {
                    $logoPath = $this->get('kernel')->getRootDir() . '/../web/src/images/logo.png';
                    $response = $this->get('report.br.service')->getBrReportExcelFile($data, $result, $logoPath, ReportBrService::BENEFICIARY);
                    return $response;
                }
            }
        }
        return $this->render('@Report/BR/beneficiary/index_br_report.html.twig',
            array('form' => $form->createView())
        );

    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/responsible",name="br_responsible_report")
     */
    public function responsibleReportAction(Request $request)
    {
        $currentRestaurant = $this->get("restaurant.service")
            ->getCurrentRestaurant();
        $i=rand();
        $logger=$this->get('monolog.logger.generate_report');
        $data['startDate'] = new \DateTime('Monday this week');
        $data['endDate'] = new \DateTime('Sunday this week');
        $form = $this->createForm(BrFormType::class, $data);
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $logger->addInfo('Generate report br responsible by '.$currentRestaurant->getCode().' from '.$data['startDate']->format('Y-m-d').' to '.$data['endDate']->format('Y-m-d').' '.$i);
                $t1 = time();
                $result = $this->get('report.br.service')->getBrResponsibleList($data);
                $t2 = time();
                $logger->addInfo('Generate report  br responsible finish | generate time = '. ($t2 - $t1) .'seconds by'.$currentRestaurant->getCode().' '.$i);
                $isEPDF = !empty($request->get('pdf', null));
                $isEXLS = !empty($request->get('xls', null));
                if (!$isEPDF && !$isEXLS) {
                    return $this->render('@Report/BR/responsible/index_br_report.html.twig',
                        array('form' => $form->createView(), 'data' => $result, 'generated' => true)
                    );
                } elseif ($isEPDF) {
                    //Téléchargement PDF
                    $filename = "br_report_" . date('Y_m_d_H_i_s') . ".pdf";
                    $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig($filename,
                        '@Report/BR/responsible/export/export_report_br.html.twig',
                        [
                            "form", $form->createView(),
                            "data" => $result,
                            "generated" => true,
                            "download" => true
                        ]
                        , [
                            'orientation' => 'Landscape',
                            'page-size' => "A4"
                        ]);

                    return Utilities::createFileResponse($filepath, $filename);
                } elseif ($isEXLS) {
                    $logoPath = $this->get('kernel')->getRootDir() . '/../web/src/images/logo.png';
                    $response = $this->get('report.br.service')->getBrReportExcelFile($data, $result, $logoPath, ReportBrService::RESPONSIBLE);
                    return $response;
                }
            }
        }
        return $this->render('@Report/BR/responsible/index_br_report.html.twig',
            array('form' => $form->createView())
        );

    }
}
