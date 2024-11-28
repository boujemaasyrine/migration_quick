<?php

namespace AppBundle\Report\Controller;

use AppBundle\Report\Form\CorrectionsFormType;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class BrReportController
 * @package AppBundle\Report\Controller
 * @Route("report/corrections")
 */
class CorrectionsReportController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/corrections",name="corrections_report")
     */
    public function indexAction(Request $request)
    {
        $logger=$this->get('monolog.logger.generate_report');
        $data['currentRestaurant'] = $this->get("restaurant.service")->getCurrentRestaurant();
        $data['startDate'] = new \DateTime('Monday this week');
        $data['endDate'] = new \DateTime('Sunday this week');
        $form = $this->createForm(CorrectionsFormType::class, $data);
        if($request->getMethod() == "POST"){
            $form->handleRequest($request);

            if($form->isValid()) {
                $i=rand();
                $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
                $data = $form->getData();
                $logger->addInfo('Generate report correction from '.$currentRestaurant->getCode().' from '.$data['startDate']->format('Y-m-d').' to '.$data['endDate']->format('Y-m-d').' '.$i);
                $t1 = time();
                $result = $this->get('report.corrections.service')->getList($data);
                $CA=$this->get('report.corrections.service')->getCaReel($data);
                $t2 = time();
                $logger->addInfo('Generate report correction finish | generate time = '. ($t2 - $t1) .'seconds by '.$currentRestaurant->getCode().' '.$i);

                if (is_null($request->get('download', null)) && is_null(
                        $request->get('xls', null)
                    )) {
                    return $this->render('@Report/Corrections/index_corrections_report.html.twig',
                        array('form' => $form->createView(),'data'=>$result, 'generated'=>true,'CA' => $CA)
                    );
                } else {
                    if (!is_null($request->get('download', null))  && is_null(
                            $request->get('xls', null)
                        )) {
                        //Téléchargement PDF
                        $filename = "corrections_report_" . date('Y_m_d_H_i_s') . ".pdf";
                        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig($filename,
                            '@Report/Corrections/export/export_corrections.html.twig',
                            [
                                "form", $form->createView(),
                                "data"=>$result,
                                "generated" => true,
                                'download'=>true,
                                'CA' => $CA]
                            , [
                                'orientation' => 'Portrait',
                                'page-size'=>"A4"
                            ]);

                        return Utilities::createFileResponse($filepath, $filename);
                    } else {
                        if (!is_null($request->get('xls', null)) &&  is_null(
                                $request->get('download', null)
                            )) {
                            $logoPath = $this->get('kernel')->getRootDir().'/../web/src/images/logo.png';
                            $response = $this->get('report.corrections.service')->generateExcelFile($result, $data,  $data['currentRestaurant'], $logoPath);

                            return $response;
                        }
                    }
                }
            }
        }
        return $this->render('@Report/Corrections/index_corrections_report.html.twig',
            array('form' => $form->createView())
        );
    }
}
