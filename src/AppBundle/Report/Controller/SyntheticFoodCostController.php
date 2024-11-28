<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 25/04/2016
 * Time: 11:07
 */

namespace AppBundle\Report\Controller;

use AppBundle\General\Entity\ImportProgression;
use AppBundle\Report\Entity\RapportTmp;
use AppBundle\Report\Entity\SyntheticFoodCostRapport;
use AppBundle\Security\RightAnnotation;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class SyntheticFoodCostController
 *
 * @package                       AppBundle\Report\Controller
 * @Route("/synthetic_food_cost")
 */
class SyntheticFoodCostController extends Controller
{

    private function getForm()
    {
        $form = $this->createFormBuilder()
            ->add(
                'beginDate',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => array(
                        new NotNull(),
                    ),
                )
            )
            ->add(
                'endDate',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => array(
                        new NotNull(),
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if ($value === null) {
                                        return;
                                    }

                                    if (!$value instanceof \DateTime) {
                                        return;
                                    }

                                    $rootData = $context->getRoot()->getData();

                                    $startDate = $rootData['beginDate'];
                                    if ($startDate === null) {
                                        return;
                                    }

                                    if (!$startDate instanceof \DateTime) {
                                        return;
                                    }

                                    if (Utilities::compareDates($startDate, $value) > 0) {
                                        $context->buildViolation('Superieur à la date de début')->addViolation();
                                    }
                                },
                            )
                        ),
                    ),
                )
            );

        return $form->getForm();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/",name="index_food_cost_synthetic")
     * @RightAnnotation("index_food_cost_synthetic")
     */
    public function indexAction()
    {

        $form = $this->getForm();

        return $this->render(
            "@Report/SyntheticFoodCost/index.html.twig",
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/launch_calcul/{force}",name="synthetic_foodcost_launch_calcul", options={"expose"=true})
     * @Method("post")
     */
    public function launchCalculAction(Request $request, $force = 0)
    {
        $logger=$this->get('monolog.logger.generate_report');
        $i=rand();
        $form = $this->getForm();

        //Recupération dates
        $form->handleRequest($request);

        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $currentRestaurantId = $currentRestaurant->getId();
        $lock = $this->get('report.foodcost.synthetic.service')->checkLocked($currentRestaurantId);

        //Vérification dates
        if (!$form->isValid() || $lock->getValue() == 1) {
            return new JsonResponse(
                array(
                    'errors' => true,
                    'lock' => $lock,
                    'html' => $this->renderView(
                        "@Report/SyntheticFoodCost/form_filter.html.twig",
                        array(
                            'form' => $form->createView(),
                            'lock' => $lock->getValue(),
                        )
                    ),
                )
            );
        }

        $startDate = $form->getData()['beginDate'];
        $endDate = $form->getData()['endDate'];
        //Launch asynch command
        $rapport = new SyntheticFoodCostRapport();
        $rapport->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setOriginRestaurant($currentRestaurant);
        $this->getDoctrine()->getManager()->persist($rapport);

        $progress = new ImportProgression();
        $this->getDoctrine()->getManager()->persist($progress);
        $progress->setProgress(0)
            ->setNature('foodcost_synthetic');

        $this->getDoctrine()->getManager()->flush();


        $startDate = $startDate->format('Y-m-d');
        $endDate = $endDate->format('Y-m-d');
        $logger->addInfo('Generate report Synthetic foodCost by '.$currentRestaurant->getCode().' from '.$startDate.' to '.$endDate.' '.$i);
        $t1 = time();
        $cmd = "report:synthetic:foodcost $currentRestaurantId $startDate  $endDate ".$progress->getId(
        )." ".$force." ";

        $this->get('toolbox.command.launcher')->execute($cmd);
        $t2 = time();
        $logger->addInfo('Generate report Synthetic foodCost finish | generate time = '. ($t2 - $t1) .'seconds by'.$currentRestaurant->getCode().' '.$i);
        return new JsonResponse(
            array(
                'progressID' => $progress->getId(),
                'tmpID' => $rapport->getId(),
                // 'cmd' => $cmd
            )
        );
    }

    /**
     * @param RapportTmp $tmp
     * @return JsonResponse
     * @Route("/get_result/{tmp}",name="synthetic_foodcost_get_result", options={"expose"=true})
     */
    public function getResultAction(RapportTmp $tmp)
    {

        $data = $this->get('report.foodcost.synthetic.service')->formatResultFoodCostSynthetic($tmp);

        return new JsonResponse(
            array(
                'html' => $this->renderView(
                    "@Report/SyntheticFoodCost/body_report_synthetic_foodcost.html.twig",
                    array(
                        'data' => $data,
                    )
                ),
            )
        );
    }

    /**
     * @param RapportTmp $tmp
     * @return Response
     * @Route("/print/{tmp}",name="print_food_cost_synthetic",options={"expose"=true})
     */
    public function printAction(RapportTmp $tmp)
    {

        $data = $this->get('report.foodcost.synthetic.service')->formatResultFoodCostSynthetic($tmp);

        $filename = "synthetique_food_cost_".date('Y_m_d_H_i_s').".pdf";
        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
            $filename,
            '@Report/SyntheticFoodCost/print.html.twig',
            [
                'data' => $data,
            ],
            [
                'orientation' => 'Landscape',
            ]
        );

        return Utilities::createFileResponse($filepath, $filename);
    }

    /**
     * @param SyntheticFoodCostRapport $rapport
     * @return Response
     * @Route("/excel/{rapport}",name="synth_fc_export_excel",options={"expose"=true})
     */
    public function exportReportExcel(SyntheticFoodCostRapport $rapport)
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $logoPath = $this->get('kernel')->getRootDir().'/../web/src/images/logo.png';

        return $this->get('report.synthetic.fc.excel.service')->exportExcel($rapport, $currentRestaurant, $logoPath);
    }
}
