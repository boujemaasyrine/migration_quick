<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 20/05/2016
 * Time: 17:38
 */

namespace AppBundle\Financial\Controller;

use AppBundle\Administration\Entity\Parameter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class VerifyOpenedTableController
 *
 *
 * @Route("/financial")
 */
class VerifyOpenedTableController extends Controller
{

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/verify_opened_table",name="verify_opened_table")
     */
    public function verifyOpenedTableAction()
    {


        return $this->render("@Financial/VerifyOpenedTable/verify_opened_table.html.twig");
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/tables_opened",name="tables_opened")
     */
    public function openedTableAction(Request $request)
    {
        $parameter = $this->getDoctrine()->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => 'opened_table',
            )
        );

        if (null == $parameter) {
            $parameter = new Parameter();
            $parameter->setValue('0')
                ->setType('opened_table');
            $this->getDoctrine()->getManager()->persist($parameter);
        }

        $data = array('opened' => $parameter->getValue());

        $form = $this->createFormBuilder($data)
            ->add(
                'opened',
                ChoiceType::class,
                array(
                    'choices' => array(
                        '0' => 'Tables ouvertes',
                        '1' => 'Tables fermées',
                    ),
                )
            )
            ->getForm();


        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            $parameter->setValue($form->getData()['opened']);
            $this->getDoctrine()->getManager()->flush();
            $this->get('session')->getFlashBag()->add('success', "Enregistré avec succès");
        }

        $this->get('session')->getFlashBag()->add('info', "Fonctionnalité de Test");

        return $this->render(
            "@Financial/VerifyOpenedTable/table_opened_parameter.html.twig",
            array(
                'form' => $form->createView(),
            )
        );
    }
}
