<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 08/03/2016
 * Time: 14:59
 */

namespace AppBundle\Supervision\Controller;

use AppBundle\Merchandise\Entity\CategoryGroup;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Supervision\Form\CategoryGroupType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class GroupController extends Controller
{

    /**
     * @RightAnnotation("groups_list")
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/groups_list/{group}",name="groups_list",options={"expose"=true})
     * @Method({"GET","POST"})
     */
    public function groupsListAction(Request $request, CategoryGroup $group = null)
    {
        if ($group == null) {
            $group = new CategoryGroup();
        } elseif ($group->getNameTranslation('nl') == null) {
            $group->addNameTranslation('nl', '');
        }

        $type = ($group->getId() != null) ? 'edit' : 'plus';

        $form = $this->createForm(CategoryGroupType::Class, $group);
        $groups = $this->getDoctrine()->getRepository(CategoryGroup::class)->findByActive(true);
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            if ($request->getMethod() === "POST") {
                try {
                    $form->handleRequest($request);
                    if ($form->isValid()) {
                        $this->get('group.service')->saveGroup($group);
                        $newGroup = new CategoryGroup();
                        $form = $this->createForm(CategoryGroupType::Class, $newGroup);
                        $response->setData(
                            [
                                "data" => [
                                    $this->renderView(
                                        '@Supervision/modals/details_list_group.html.twig',
                                        array(
                                            'group' => $group,
                                        )
                                    ),
                                    [
                                        "id" => $group->getId(),
                                        "nameFR" => $group->getNameTranslation('fr'),
                                        "nameNL" => $group->getNameTranslation('nl'),
                                        "name" => $group->getName(),
                                        "foodCost" => $group->isFoodCost(),
                                        "btn" => $this->renderView(
                                            '@Supervision/parts/btn_action_template.html.twig',
                                            array(
                                                'id' => $group->getId(),
                                            )
                                        ),
                                    ],
                                    $this->renderView(
                                        '@Supervision/parts/form_add_edit_group.html.twig',
                                        array(
                                            'form' => $form->createView(),
                                            'type' => 'plus',
                                        )
                                    ),
                                ],
                            ]
                        );
                    } else {
                        $form->addError(new FormError($this->get('translator')->trans('form.error')));
                        $response->setData(
                            [
                                "formError" => [
                                    $this->renderView(
                                        '@Supervision/parts/form_add_edit_group.html.twig',
                                        array(
                                            'form' => $form->createView(),
                                            'type' => $type,
                                        )
                                    ),
                                ],
                            ]
                        );
                    }
                } catch (\Exception $e) {
                    $response->setData(
                        [
                            "errors" => [$this->get('translator')->trans('Error.general.internal'), $e->getMessage()],
                        ]
                    );
                }

                return $response;
            } else {
                $response->setData(
                    [
                        "data" => [
                            $this->renderView(
                                '@Supervision/parts/form_add_edit_group.html.twig',
                                array(
                                    'form' => $form->createView(),
                                    'type' => $type,
                                )
                            ),
                        ],
                    ]
                );

                return $response;
            }
        }

        return $this->render(
            "@Supervision/groups_list.html.twig",
            array(
                'form' => $form->createView(),
                'groups' => $groups,
                'type' => $type,
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/delete_group/{group}",name="delete_group", options={"expose"=true})
     */
    public function deleteGroupAction(Request $request, CategoryGroup $group)
    {
        $session = $this->get('session');
        $form = $this->createFormBuilder(
            null,
            array('action' => $this->generateUrl('delete_group', array('group' => $group->getId())))
        )->getForm();
        $text_button = $this->get('translator')->trans('group.list.delete', array(), 'supervision');
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $deleted = $this->get('group.service')->deleteGroup($group);
                if ($deleted) {
                    $session->getFlashBag()->set('success', 'group.list.delete_success');
                } else {
                    $session->getFlashBag()->set('error', 'group.list.delete_fails');
                }
            }

            return $this->redirectToRoute("groups_list");
        }

        return new JsonResponse(
            array(
                'data' => true,
                'html' => $this->renderView(
                    '@Supervision/parts/delete.html.twig',
                    array(
                        'form' => $form->createView(),
                        'text_button' => $text_button,
                    )
                ),
            )
        );
    }

    /**
     * @param CategoryGroup $group
     * @return JsonResponse
     * @Route("/json/group_detail/{group}",name="group_detail",options={"expose"=true})
     */
    public function groupDetailJsonAction(CategoryGroup $group)
    {

        return new JsonResponse(
            array(
                'data' => $this->renderView(
                    "@Supervision/modals/details_list_group.html.twig",
                    array(
                        'group' => $group,
                    )
                ),
            )
        );
    }
}
