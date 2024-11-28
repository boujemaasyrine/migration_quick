<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 20/10/2016
 * Time: 16:28
 */

namespace AppBundle\Report\Controller;

use AppBundle\Report\Form\ItemsPerSoldingCanalsFormType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\ToolBox\Utils\Utilities;

/**
 * Class ItemsPerSoldingCanalReportController
 * @package AppBundle\Report\Controller
 * @Route("report/items_solding_canals")
 */
class ItemsPerSoldingCanalReportController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/index",name="items_solding_canals")
     */
    public function indexAction(Request $request)
    {
        $logger=$this->get('monolog.logger.generate_report');

        $data['startDate'] = new \DateTime('Monday this week');
        $data['endDate'] = new \DateTime('Sunday this week');
        $data['currentRestaurant'] = $currentRestaurant= $this->get("restaurant.service")->getCurrentRestaurant();
        $form = $this->createForm(ItemsPerSoldingCanalsFormType::class, $data);
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
         ini_set("memory_limit", "2100M");

            if ($form->isValid()) {
                $i=rand();
                $data = $form->getData();
                if (is_null($request->get('download', null)) && is_null($request->get('export', null)) && is_null($request->get('xls', null)) ){
                    $logger->addInfo('Generate report item solding canal by '.$currentRestaurant->getCode().' from '.$data['startDate']->format('Y-m-d').' to '.$data['endDate']->format('Y-m-d').' '.$i);
                    $t1 = time();
                    $result = $this->get('report.item.canals.service')->getList($data);
                    $t2 = time();
                    $logger->addInfo('Generate report item solding canal finish | generate time = '. ($t2 - $t1) .'seconds by '.$currentRestaurant->getCode().' '.$i);
                    $this->get('session')->set('solding_canal_report', $result);
                    return $this->render(
                        '@Report/ItemsPerSoldingCanals/index_item_per_solding_canals_report.html.twig',
                        array('form' => $form->createView(), 'data' => $result, 'generated' => true)
                    );
                } elseif (!is_null($request->get('download', null)) && is_null($request->get('export', null)) && is_null(
                        $request->get('xls', null)
                    ))  {
                    //Téléchargement PDF
                    $result = $this->get('session')->get("solding_canal_report", false);
                    if (!$result) {
                        $result = $this->get('report.item.canals.service')->getList($data);
                    }
                    $filename = "Repartition_des_ventes_par_canal_de_vente_".date('Y_m_d_H_i_s').".pdf";
                    $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                        $filename,
                        '@Report/ItemsPerSoldingCanals/export/export_report_items_per_solding_canals.html.twig',
                        [
                            "form",
                            $form->createView(),
                            "data" => $result,
                            "generated" => true,
                        ]
                        ,
                        [
                            'orientation' => 'Landscape',
                            'page-size' => "A4",
                        ]
                    );

                    return Utilities::createFileResponse($filepath, $filename);
                }elseif (!is_null($request->get('xls', null)) && is_null($request->get('export', null)) && is_null(
                        $request->get('download', null)
                    )) {
                    $result = $this->get('session')->get("solding_canal_report", false);
                    if (!$result) {
                        $result = $this->get('report.item.canals.service')->getList($data);
                    }
                    $logoPath = $this->get('kernel')->getRootDir().'/../web/src/images/logo.png';
                    $response = $this->get('report.item.canals.service')->generateExcelFile($result, $data,$logoPath);
                    return $response;
                }
            }
        }
        $this->get('session')->remove('solding_canal_report');
        return $this->render(
            '@Report/ItemsPerSoldingCanals/index_item_per_solding_canals_report.html.twig',
            array('form' => $form->createView())
        );

    }
}
