<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 25/04/2016
 * Time: 11:07
 */

namespace AppBundle\Supervision\Controller\Reports;

use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Report\Entity\RapportTmp;
use AppBundle\Report\Entity\SyntheticFoodCostRapport;
use AppBundle\Supervision\Utils\Utilities;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
            )
            ->add(
                'restaurant',
                EntityType::class,
                array(
                    'class' => Restaurant::class,
                    'choices' => $restaurants,
                    'choice_label' => function (Restaurant $r) {
                        return $r->getName()." (".$r->getCode().")";
                    },
                    'placeholder' => 'Veuillez choisir un restaurant',
                )
            );

        return $form->getForm();
    }

    /**
     * @RightAnnotation("index_food_cost_synthetic")
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/",name="supervision_index_food_cost_synthetic")
     */
    public function indexAction()
    {

        $form = $this->getForm();

        return $this->render(
            "@Supervision/Reports/SyntheticFoodCost/index.html.twig",
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/launch_calcul/{force}",name="supervision_synthetic_foodcost_launch_calcul", options={"expose"=true})
     * @Method("post")
     */
    public function launchCalculAction(Request $request, $force = 0)
    {

        $form = $this->getForm();

        //Recupération dates
        $form->handleRequest($request);

        $lock = $this->get('supervision.report.foodcost.synthetic.service')->checkLocked();

        if (!$form->isValid() || ($lock->getValue() == 1)) {
            return new JsonResponse(
                array(
                    'errors' => true,
                    'html' => $this->renderView(
                        "@Supervision/Reports/SyntheticFoodCost/form_filter.html.twig",
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
        $restaurant = $form->getData()['restaurant'];

        //Launch asynch command
        $rapport = new SyntheticFoodCostRapport();
        $rapport
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setOriginRestaurant($restaurant);
        $this->getDoctrine()->getManager()->persist($rapport);

        $progress = new ImportProgression();
        $this->getDoctrine()->getManager()->persist($progress);
        $progress->setProgress(0)
            ->setNature('foodcost_synthetic');

        $this->getDoctrine()->getManager()->flush();


        $startDate = $startDate->format('Y-m-d');
        $endDate = $endDate->format('Y-m-d');
        $rstaurantID = $form->getData()['restaurant']->getId();

        $cmd = "supervision:report:synthetic:foodcost $rstaurantID $startDate  $endDate ".$progress->getId(
        )." ".$force." ";

        $execute = $this->get('toolbox.command.launcher')->execute($cmd);

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
     * @Route("/get_result/{tmp}",name="supervision_synthetic_foodcost_get_result", options={"expose"=true})
     */
    public function getResultAction(RapportTmp $tmp)
    {

        $data = $this->get('supervision.report.foodcost.synthetic.service')->formatResultFoodCostSynthetic($tmp);

        return new JsonResponse(
            array(
                'html' => $this->renderView(
                    "@Supervision/Reports/SyntheticFoodCost/body_report_synthetic_foodcost.html.twig",
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
     * @Route("/print/{tmp}",name="supervision_print_food_cost_synthetic",options={"expose"=true})
     */
    public function printAction(RapportTmp $tmp)
    {

        $data = $this->get('supervision.report.foodcost.synthetic.service')->formatResultFoodCostSynthetic($tmp);

        $filename = "synthetique_food_cost_".date('Y_m_d_H_i_s').".pdf";
        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
            $filename,
            '@Supervision/Reports/SyntheticFoodCost/print.html.twig',
            [
                'data' => $data,
                'tmp' => $tmp,
            ],
            [
                'orientation' => 'Landscape',
            ]
        );

        return Utilities::createFileResponse($filepath, $filename);
    }
}
