<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 31/05/2016
 * Time: 11:19
 */

namespace AppBundle\Supervision\Controller;

use AppBundle\Administration\Entity\Action;
use AppBundle\Supervision\Form\UsersManagement\RightsForRolesType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConfigRightsController
 *
 * @package                 AppBundle\Controller
 * @Route("/config_rights")
 */
class ConfigRightsController extends Controller
{

    /**
     * @RightAnnotation("config_right_restaurant")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/restaurantRoles",name="config_right_restaurant",options={"expose"=true})
     */
    public function indexRestaurantRightsAction(Request $request)
    {
        $type = 'restaurant';
        $form = $this->createForm(RightsForRolesType::class, null, ['type' => $type]);
        $rights = $this->getDoctrine()->getRepository(Action::class)->findBy(
            array("type" => Action::RESTAURANT_ACTION_TYPE)
        );
        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->get('rights.roles.service')->setRightsForRoles(
                    $form->getData()['roles'],
                    Action::RESTAURANT_ACTION_TYPE
                );

                $message = $this->get('translator')->trans('rights_roles.success', [], "supervision");
                $this->get('session')->getFlashBag()->add('success', $message);

                return $this->redirectToRoute('config_right_restaurant');
            }
        }

        return $this->render(
            "@Supervision/UsersManagement/index_rights_config.html.twig",
            array(
                'type' => $type,
                'form' => $form->createView(),
                'rights' => $rights,
            )
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/getExistingRights",name="get_existing_rights", options={"expose"=true})
     */
    public function getExistingRightsAction(Request $request)
    {
        $response = null;
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            try {
                $rights = $this->get('rights.roles.service')->getRightsForAllRoles();
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

    /**
     * @RightAnnotation("config_right_central")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/centralRoles",name="config_right_central",options={"expose"=true})
     */
    public function indexCentralRightsAction(Request $request)
    {

        $type = 'central';
        $form = $this->createForm(RightsForRolesType::class, null, ['type' => $type]);
        $rights = $this->getDoctrine()->getRepository(Action::class)->findBy(
            array("type" => Action::CENTRAL_ACTION_TYPE)
        );

        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->get('rights.roles.service')->setRightsForRoles(
                    $form->getData()['roles'],
                    Action::CENTRAL_ACTION_TYPE
                );

                $message = $this->get('translator')->trans('rights_roles.success', [], "supervision");
                $this->get('session')->getFlashBag()->add('success', $message);

                return $this->redirectToRoute('config_right_central');
            }
        }

        return $this->render(
            "@Supervision/UsersManagement/index_rights_config.html.twig",
            array(
                'type' => $type,
                'form' => $form->createView(),
                'rights' => $rights,
            )
        );
    }
}
