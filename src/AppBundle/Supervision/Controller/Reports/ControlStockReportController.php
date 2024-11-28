<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 15/04/2016
 * Time: 14:47
 */

namespace AppBundle\Supervision\Controller\Reports;

use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Report\Entity\ControlStockTmp;
use AppBundle\Supervision\Form\Reports\ControlStockType;
use AppBundle\Supervision\Utils\Utilities;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Class ControlStockReportController
 *
 * @package                        AppBundle\Report\Controller
 * @Route("/control_stock_report")
 */
class ControlStockReportController extends Controller
{
    /**
     * @RightAnnotation("control_stock_report")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/",name="supervision_control_stock_report",options={"expose"=true})
     */
    public function indexAction(Request $request)
    {

        $currentUser = $this->getUser();
        $restaurants = $currentUser->getEligibleRestaurants()->toArray();
        usort(
            $restaurants,
            function (Restaurant $r1, Restaurant $r2) {
                if ($r1->getName() < $r2->getName()) {
                    return -1;
                }

                return 1;
            }
        );


        $form = $this->createForm(
            ControlStockType::class,
            [],
            array(
                "restaurants" => $restaurants,
            )
        );

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $startDate = $form->get('startDate')->getData();
                $endDate = $form->get('endDate')->getData();
                $sheet = $form->get('sheetModel')->getData();
                $restaurant = $form->get('restaurant')->getData();

                $progress = new ImportProgression();
                $progress
                    ->setStartDateTime(new \DateTime('NOW'))
                    ->setNature('calcul_control_stock')
                    ->setProgress(0);
                $this->getDoctrine()->getManager()->persist($progress);

                $controlStockTmp = new ControlStockTmp();
                $this->getDoctrine()->getManager()->persist($controlStockTmp);

                $controlStockTmp
                    ->setStartDate($startDate)
                    ->setEndDate($endDate)
                    ->setSheet($sheet)
                    ->setOriginRestaurant($restaurant);

                $this->getDoctrine()->getManager()->flush();

                $this->get('toolbox.command.launcher')->execute(
                    'supervision:report:control:stock '.$controlStockTmp->getId().' '.$progress->getId()." > file.log "
                );

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(
                        array(
                            'progressID' => $progress->getId(),
                            'tmpID' => $controlStockTmp->getId(),
                        )
                    );
                }

                return $this->render(
                    "@Supervision/Reports/ControlStockReport/index.html.twig",
                    array(
                        'form' => $form->createView(),
                        'progressID' => $progress->getId(),
                    )
                );
            }
        }

        return $this->render(
            "@Supervision/Reports/ControlStockReport/index.html.twig",
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @param ControlStockTmp $tmp
     * @Route("/get_result/{tmp}",name="supervision_get_result", options={"expose"=true})
     * @return JsonResponse
     */
    public function getResult(ControlStockTmp $tmp)
    {

        $html = $this->getHtml($tmp);

        return new JsonResponse(
            array(
                'html' => $html,
            )
        );
    }

    /**
     * @param ControlStockTmp $tmp
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     * @Route("/export/{tmp}",name="supervision_export_control_stock_pdf",options={"expose"=true})
     */
    public function exportAction(ControlStockTmp $tmp)
    {

        $html = $this->getHtml($tmp, true);

        $filename = "control_stock_".date('Y_m_d_H_i_s').".pdf";

        $file_path = $this->getParameter('tmp_directory')."/$filename".hash('md5', date('Y/m/d H:i:s')).".pdf";

        $this->get('knp_snappy.pdf')->generateFromHtml(
            $html,
            $file_path,
            [
                'orientation' => 'Landscape',
            ]
        );

        return Utilities::createFileResponse($file_path, $filename);
    }

    /**
     * @param ControlStockTmp $tmp
     * @return Response
     * @Route("/export_excel/{tmp}",name="supervision_export_excel",options={"expose"=true})
     */
    public function exportExcelAction(ControlStockTmp $tmp)
    {

        $response = $this->get('supervision.report.control.stock.service')->createExcelFile($tmp);

        return $response;
    }


    private function getHtml(ControlStockTmp $tmp, $print = null)
    {
        $days = [
            $this->get('translator')->trans('days.sunday', [], 'supervision'),
            $this->get('translator')->trans('days.monday', [], 'supervision'),
            $this->get('translator')->trans('days.tuesday', [], 'supervision'),
            $this->get('translator')->trans('days.wednesday', [], 'supervision'),
            $this->get('translator')->trans('days.thursday', [], 'supervision'),
            $this->get('translator')->trans('days.friday', [], 'supervision'),
            $this->get('translator')->trans('days.saturday', [], 'supervision'),
        ];

        $months = [
            $this->get('translator')->trans('months.jan', [], 'supervision'),
            $this->get('translator')->trans('months.feb', [], 'supervision'),
            $this->get('translator')->trans('months.mar', [], 'supervision'),
            $this->get('translator')->trans('months.apr', [], 'supervision'),
            $this->get('translator')->trans('months.mai', [], 'supervision'),
            $this->get('translator')->trans('months.jun', [], 'supervision'),
            $this->get('translator')->trans('months.jul', [], 'supervision'),
            $this->get('translator')->trans('months.aug', [], 'supervision'),
            $this->get('translator')->trans('months.sep', [], 'supervision'),
            $this->get('translator')->trans('months.oct', [], 'supervision'),
            $this->get('translator')->trans('months.nov', [], 'supervision'),
            $this->get('translator')->trans('months.dec', [], 'supervision'),
        ];

        if ($print) {
            $html = $this->renderView(
                "@Supervision/Reports/ControlStockReport/print.html.twig",
                array(
                    'data' => $tmp,
                    'days' => $days,
                    'months' => $months,
                )
            );
        } else {
            $html = $this->renderView(
                "@Supervision/Reports/ControlStockReport/report_result.html.twig",
                array(
                    'data' => $tmp,
                    'days' => $days,
                    'months' => $months,
                )
            );
        }


        return $html;
    }
}
