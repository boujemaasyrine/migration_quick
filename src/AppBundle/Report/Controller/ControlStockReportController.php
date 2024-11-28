<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 15/04/2016
 * Time: 14:47
 */

namespace AppBundle\Report\Controller;

use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Report\Entity\ControlStockTmp;
use AppBundle\Security\RightAnnotation;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/",name="control_stock_report",options={"expose"=true})
     * @RightAnnotation("control_stock_report")
     */
    public function indexAction(Request $request)
    {

        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        //Test if there's coef calculated
        $base = $this->getDoctrine()->getRepository("Merchandise:CoefBase")->findOneBy(
            array("originRestaurant" => $currentRestaurant),
            array(
                'id' => 'DESC',
            )
        );

        if ($base == null) {
            $this->get('session')->getFlashBag()->add('warning', "Il n'y a pas de Coefficients calculés");

            return $this->redirectToRoute("coef_calculate_base");
        }

        $formData = [];

        $form = $this
            ->createFormBuilder($formData)
            ->add(
                'sheetModel',
                EntityType::class,
                array(
                    'class' => 'Merchandise:SheetModel',
                    'choice_name' => 'label',
                    'query_builder' => function (EntityRepository $er) use ($currentRestaurant) {
                        return $er->createQueryBuilder('s')
                            ->where('s.type = :inv')
                            ->andWhere('s.originRestaurant = :currentRestaurant')
                            ->setParameter('currentRestaurant', $currentRestaurant)
                            ->setParameter('inv', SheetModel::INVENTORY_MODEL);
                    },
                    'constraints' => new NotNull(),
                )
            )
            ->add(
                'startDate',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => new NotNull(),
                )
            )
            ->add(
                'endDate',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => new NotNull(),
                )
            )->getForm();

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $startDate = $form->get('startDate')->getData();
                $endDate = $form->get('endDate')->getData();
                $sheet = $form->get('sheetModel')->getData();

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
                    ->setOriginRestaurant($currentRestaurant);

                $this->getDoctrine()->getManager()->flush();
                $listProduct=array();
                foreach ($sheet->getLines() as $l) {
                    $product = $l->getProduct();
                    $coeffLine = $this->getDoctrine()->getRepository("Merchandise:Coefficient")->findOneBy(
                        array('product' => $product, 'base' => $base)
                    );
                    if (!$coeffLine) {
                        $listProduct[] = $product;
                    }
                }
                if(!empty($listProduct)){
                    $message = $this->get('translator')->trans('coeff_product_warning');
                    $html='<div class="alert alert-warning">
                              <span class="glyphicon glyphicon-warning-sign"></span>'.$message.'<br/>';
                    foreach($listProduct as $product){
                       $html.= $product->getName(). '<br/>';
                    }
                      $html.= '</div>';

                    return new JsonResponse(array('error' =>true, 'htmlMessage' =>$html));

                }

                $this->get('toolbox.command.launcher')->execute(
                    'report:control:stock '.$currentRestaurant->getId().' '.$controlStockTmp->getId(
                    ).' '.$progress->getId()
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
                    "@Report/ControlStockReport/index.html.twig",
                    array(
                        'form' => $form->createView(),
                        'progressID' => $progress->getId(),
                    )
                );
            }
        }

        return $this->render(
            "@Report/ControlStockReport/index.html.twig",
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @param ControlStockTmp $tmp
     * @Route("/get_result/{tmp}",name="get_result", options={"expose"=true})
     * @return JsonResponse
     */
    public function getResultAction(ControlStockTmp $tmp)
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
     * @Route("/export/{tmp}",name="export_control_stock_pdf",options={"expose"=true})
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
     * @Route("/export_excel/{tmp}",name="export_excel",options={"expose"=true})
     */
    public function exportExcelAction(ControlStockTmp $tmp)
    {

        $response = $this->get('report.control.stock.service')->createExcelFile($tmp);

        return $response;
    }


    private function getHtml(ControlStockTmp $tmp, $print = null)
    {
        $days = [
            $this->get('translator')->trans('days.sunday'),
            $this->get('translator')->trans('days.monday'),
            $this->get('translator')->trans('days.tuesday'),
            $this->get('translator')->trans('days.wednesday'),
            $this->get('translator')->trans('days.thursday'),
            $this->get('translator')->trans('days.friday'),
            $this->get('translator')->trans('days.saturday'),
        ];

        $months = [
            $this->get('translator')->trans('months.jan'),
            $this->get('translator')->trans('months.feb'),
            $this->get('translator')->trans('months.mar'),
            $this->get('translator')->trans('months.apr'),
            $this->get('translator')->trans('months.mai'),
            $this->get('translator')->trans('months.jun'),
            $this->get('translator')->trans('months.jul'),
            $this->get('translator')->trans('months.aug'),
            $this->get('translator')->trans('months.sep'),
            $this->get('translator')->trans('months.oct'),
            $this->get('translator')->trans('months.nov'),
            $this->get('translator')->trans('months.dec'),
        ];

        if ($print) {
            $html = $this->renderView(
                "@Report/ControlStockReport/print.html.twig",
                array(
                    'data' => $tmp,
                    'days' => $days,
                    'months' => $months,
                )
            );
        } else {
            $html = $this->renderView(
                "@Report/ControlStockReport/report_result.html.twig",
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
