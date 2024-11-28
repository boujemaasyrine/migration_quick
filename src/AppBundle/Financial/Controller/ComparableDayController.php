<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 14/06/2016
 * Time: 12:32
 */

namespace AppBundle\Financial\Controller;

use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Security\RightAnnotation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ComparableDayController
 *
 * @Route("/comparable_days")
 */
class ComparableDayController extends Controller
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/list",name="comparable_days_list",options={"expose"=true})
     *
     * @RightAnnotation("comparable_days_list")
     */
    public function indexAction(Request $request)
    {

        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        if ($request->isXmlHttpRequest()) {
            $minDate = $request->query->get('start');
            $minDate = \DateTime::createFromFormat('Y-m-d', $minDate);
            $maxDate = $request->query->get('end');
            $maxDate = \DateTime::createFromFormat('Y-m-d', $maxDate);

            //Get admin closing dates
            $adminClosingDays = $this->getDoctrine()->getRepository("Financial:AdministrativeClosing")
                ->getAdminClosingBetweenDates($minDate, $maxDate, $restaurant);

            $days = [];
            //fill the gaps
            $nMin = $minDate->getTimestamp();
            $nMax = $maxDate->getTimestamp();
            for ($i = $nMin; $i <= $nMax; $i += 86400) {
                $date = new \DateTime();
                $date->setTimestamp($i);
                $data = ['id' => null, 'date' => $date->format('Y-m-d')];
                foreach ($adminClosingDays as $a) {
                    if ($a->getDate()->format('Ymd') === $date->format('Ymd')) {
                        $data = array(
                            'date' => $a->getDate()->format('Y-m-d'),
                            'comparable' => $a->getComparable(),
                            'comment' => $a->getComment(),
                            'id' => $a->getId(),
                        );
                        break;
                    }
                }
                $days[] = $data;
            }

            return new JsonResponse($days);
        }//end ajax request

        return $this->render("@Financial/ComparableDay/list_comparable_days.html.twig");
    }

    /**
     * @param Request $request
     * @param AdministrativeClosing $day
     *
     * @return JsonResponse
     *
     * @Route("/comparable_day_details/{day}",name="comparable_day_details", options={"expose"=true})
     */
    public function comparableDayDetailAction(Request $request, AdministrativeClosing $day)
    {

        if ($request->getMethod() === 'POST') {
            $this->get('app.security.checker')->checkOrThrowAccedDenied('comparable_days_modify');
            $form = $this->getForm($day);
            $form->handleRequest($request);
            $this->getDoctrine()->getManager()->flush();
            $flashMsg = $this->get('translator')->trans(
                'comparable_day_modified_with_success',
                ['%1%' => $day->getDate()->format('d/m/Y')]
            );
            $this->addFlash('success', $flashMsg);

            return $this->redirectToRoute('comparable_days_list');
        }

            $html = $this->renderView(
                "@Financial/ComparableDay/comparable_day_details.html.twig",
                array(
                    'form' => $this->getForm($day)->createView(),
                )
            );

            return new JsonResponse(
                array(
                    'html' => $html,
                )
            );

    }

    /**
     * @param AdministrativeClosing $day
     *
     * @return \Symfony\Component\Form\Form
     */
    private function getForm(AdministrativeClosing $day)
    {
        $form = $this->createFormBuilder($day)
            ->add('comment', TextareaType::class)
            ->add(
                'comparable',
                ChoiceType::class,
                array(
                    'choices' => array(
                        false => $this->get('translator')->trans('keyword.no'),
                        true => $this->get('translator')->trans('keyword.yes'),
                    ),
                    'expanded' => true,
                )
            )
            ->getForm();

        return $form;
    }
}
