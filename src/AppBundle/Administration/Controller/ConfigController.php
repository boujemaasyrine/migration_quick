<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 30/11/2015
 * Time: 09:54
 */

namespace AppBundle\Administration\Controller;

use AppBundle\Administration\Form\RightsConfig\RightsForRolesType;
use AppBundle\Merchandise\Entity\Restaurant;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class ConfigController
 *
 * @Route("/administration")
 */
class ConfigController extends Controller
{


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/indexConfigRole",name="index_config_role",options={"expose"=true})
     */
    public function indexRightsAction(Request $request)
    {
        $code = $this->getParameter('quick_code');
        $restaurant = $this->get('doctrine.orm.entity_manager')->getRepository('Merchandise:Restaurant')->findOneBy(
            [
                'code' => $code,
            ]
        );
        if (!$restaurant or $restaurant->getType() === Restaurant::COMPANY) {
            return $this->redirectToRoute('index');
        }
        $form = $this->createForm(RightsForRolesType::class);
       
          
        $rights = $this->getDoctrine()->getRepository('Administration:Action')->findAll();

        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->get('right.role.service')->setRightsForRoles($form->getData()['roles']);

                $message = $this->get('translator')->trans('right_role.success');
                $this->get('session')->getFlashBag()->add('success', $message);

                return $this->redirectToRoute('staff_list');
            }
        }

        return $this->render(
            "@Administration/RolesRights/index_rights_config.html.twig",
            array(
                'form' => $form->createView(),
                'rights' => $rights,
            )
        );
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/getExistingRights",name="get_existing_rights", options={"expose"=true})
     * @Method({"GET"})
     */
    public function getExistingRightsAction(Request $request)
    {
        $response = null;
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $rights = array();
            try {
                $rights = $this->get('staff.service')->getRightsForAllRoles();
                $data = [
                    "rights" => $rights,
                ];
            } catch (\Exception $e) {
                $data = [
                    "errors" => [
                        $this->get('translator')->trans('Error.general.internal'),
                        $e->getLine()." : ".$e->getTraceAsString(),
                    ],
                ];
            }
            $response->setData($data);
        }

        return $response;
    }
}
