<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 16/05/2016
 * Time: 10:04
 */

namespace AppBundle\Administration\Controller;

use AppBundle\Administration\Entity\Parameter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FiscaleDateController
 *
 * @Route("/parameters")
 */
class FiscaleDateController extends Controller
{

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/fiscale_date",name="fiscale_date")
     */
    public function fiscaleDateAction(Request $request)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $parameter = $this->getDoctrine()->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => 'date_fiscale',
                'originRestaurant' => $restaurant,
            )
        );

        if (null === $parameter) {
            $parameter = new Parameter();
            $parameter->setValue(date('d/m/Y'))
                ->setType('date_fiscale')
                ->setOriginRestaurant($restaurant);
            $this->getDoctrine()->getManager()->persist($parameter);
        }

        $data = array('date' => $parameter->getValue());

        $form = $this->createFormBuilder($data)
            ->add('date', TextType::class)
            ->getForm();


        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            $parameter->setValue($form->getData()['date']);
            $this->getDoctrine()->getManager()->flush();
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('fiscal_date.save_success')." ".$form->getData()['date']
            );
        }

        $this->get('session')->getFlashBag()->add('info', $this->get('translator')->trans('fiscal_date.warining'));

        return $this->render(
            "@Administration/fiscale_date/fiscale_date.html.twig",
            array(
                'form' => $form->createView(),
            )
        );
    }
}
