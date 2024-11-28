<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 19/04/2016
 * Time: 11:26
 */

namespace AppBundle\Staff\Controller;

use AppBundle\Security\Entity\Role;
use AppBundle\Staff\Entity\Employee;
use AppBundle\Staff\Form\Management\ChangeEmailType;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Staff\Form\Management\AttributeRoleType;
use AppBundle\Staff\Form\Management\DefaultPasswordType;
use AppBundle\Staff\Form\Management\StaffSearchType;
use AppBundle\Staff\Form\Management\RoleType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class StaffManagementController
 *
 * @package                    AppBundle\Staff\Controller
 * @Route("/staff_management")
 */
class StaffManagementController extends Controller
{
    /**
     * @RightAnnotation ("staff_list")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/staffList",name="staff_list",options={"expose"=true})
     */
    public function staffListAction(Request $request)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $this->get('staff.service')->importUsers($currentRestaurant);

        $form = $this->createForm(StaffSearchType::class);

        return $this->render(
            "@Staff/Management/list.html.twig",
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @param Request $request
     * @param $download
     * @return JsonResponse
     * @Route("/staffJsonList/{download}",name="staff_json_list", options={"expose"=true})
     */
    public function staffJsonListAction(Request $request, $download = 0)
    {
        $orders = array('social', 'firstName', 'email');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);
        $dataTableHeaders['criteria']['staff_search[keyword'] = $request->request->get('search')['value'];
        $dataTableHeaders['criteria']['restaurant'] = $this->get('restaurant.service')->getCurrentRestaurant();
        $download = intval($download);
        if ($download === 1) {
            $filepath = $this->get('toolbox.document.generator')
                ->generateCsvFile(
                    'staff.service',
                    'getStaff',
                    array(
                        'criteria' => $dataTableHeaders['criteria'],
                        'order' => $dataTableHeaders['orderBy'],
                        'onlyList' => true,

                    ),
                    [
                        $this->get('translator')->trans('user.social_security'),
                        $this->get('translator')->trans('user.first_name'),
                        $this->get('translator')->trans('user.username'),
                        $this->get('translator')->trans('label.mail'),
                    ],
                    function ($line) {
                        return [
                            $line['socialSecurity'],
                            $line['firstName'],
                            $line['username'],
                            $line['email'],
                        ];
                    }
                );

            $response = Utilities::createFileResponse($filepath, 'Staff'.date('dmY_His').".csv");

            return $response;
        } else {
            if ($download === 2) {
                $logoPath = $this->get('kernel')->getRootDir().'/../web/src/images/logo.png';
                $response = $this->get('staff.service')->generateExcelFile(
                    $dataTableHeaders['criteria'],
                    $dataTableHeaders['orderBy'],
                    $logoPath
                );

                return $response;
            }
        }

        $staff = $this->getDoctrine()->getRepository("Staff:Employee")->getStaffFiltredOrdered(
            $dataTableHeaders['criteria'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit']
        );
        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $staff['filtred'];
        $return['recordsTotal'] = $staff['total'];
        $return['data'] = $this->get('staff.service')->serializeStaff($staff['list']);

        return new JsonResponse($return);
    }

    /**
     * @param Employee $staff
     * @return JsonResponse
     * @Route("/json/detailsStaff/{staff}",name="staff_detail",options={"expose"=true})
     */
    public function detailsStaffJsonAction(Employee $staff)
    {
        return new JsonResponse(
            array(
                'data' => $this->renderView(
                    "@Staff/Management/modals/details.html.twig",
                    array(
                        'staff' => $staff,
                    )
                ),
                'footer' => $this->renderView(
                    "@Staff/Management/parts/footer_modal.html.twig",
                    array(
                        'staff' => $staff,
                    )
                ),
            )
        );
    }

    /**
     * @param Employee $staff
     * @param Request $request
     * @return JsonResponse
     * @Route("/json/attributeRole/{staff}",name="attribute_role",options={"expose"=true})
     */
    public function attributeRoleJsonAction(Request $request, Employee $staff)
    {

        $this->get('app.security.checker')->checkOrThrowAccedDenied('attribute_role');

        $form = $this->createForm(AttributeRoleType::class, null, array('staff' => $staff));

        if ($request->isXmlHttpRequest()) {
            if ($request->getMethod() === "POST") {
                $response = new JsonResponse();
                try {
                    $form->handleRequest($request);
                    if ($form->isValid()) {
                        $role = $form->getData()['role'];
                        $this->get('staff.service')->setRole($staff, $role);
                        $response->setData(
                            [
                                "staff" => $staff->getId(),
                            ]
                        );

                        return $response;
                    } else {
                        $response->setData(
                            [
                                "formError" => [
                                    $this->renderView(
                                        '@Staff/Management/parts/attribute_role_form.html.twig',
                                        array(
                                            'form' => $form->createView(),
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
            }
        }

        return new JsonResponse(
            array(
                'formBody' => $this->renderView(
                    "@Staff/Management/parts/attribute_role_form.html.twig",
                    array(
                        'form' => $form->createView(),
                    )
                ),
                'footer' => $this->renderView(
                    "@Staff/Management/parts/footer_form_role.html.twig",
                    array(
                        'staff' => $staff,
                        'type' => 'role',
                    )
                ),
            )
        );
    }

    /**
     * @param Employee $staff
     * @param Request $request
     * @param Bool $firstConnexion
     * @return JsonResponse
     * @Route("/json/defaultPassword/{staff}/{firstConnexion}",name="default_password",options={"expose"=true})
     */
    public function defaultPasswordJsonAction(
        Request $request,
        Employee $staff = null,
        $firstConnexion = false
    ) {

        if (!is_null($this->get('security.token_storage'))) {
            $securityToken = $this->get('security.token_storage');
            $currentUser = (!is_null($securityToken->getToken())) ? $securityToken->getToken()->getUser() : null;
        }

        //Test if the staff is the current employee
        if ($staff != null && $currentUser != $staff) {
            $this->get('app.security.checker')->checkOrThrowAccedDenied('default_password');
        }

        if ($staff == null) {
            $staff = $this->getUser();
        }
        $form = $this->createForm(DefaultPasswordType::class);

        if ($request->isXmlHttpRequest()) {
            if ($request->getMethod() === "POST") {
                $response = new JsonResponse();
                try {
                    $form->handleRequest($request);
                    if ($form->isValid()) {
                        $password = $form->getData()['password'];
                        $this->get('staff.service')->setDefaultPassword($staff, $password, $firstConnexion);
                        $response->setData(
                            [
                                "staff" => $staff->getId(),
                                "isFromSupervision"=>$this->get('users.service')->isFromSupervision($staff)
                            ]
                        );

                        return $response;
                    } else {
                        $response->setData(
                            [
                                "formError" => [
                                    $this->renderView(
                                        '@Staff/Management/parts/default_password_form.html.twig',
                                        array(
                                            'form' => $form->createView(),
                                            "staff" => $staff,
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
            }
        }

        return new JsonResponse(
            array(
                'formBody' => $this->renderView(
                    "@Staff/Management/parts/default_password_form.html.twig",
                    array(
                        'form' => $form->createView(),
                        'staff' => $staff,
                    )
                ),
                'footer' => $this->renderView(
                    "@Staff/Management/parts/footer_form_role.html.twig",
                    array(
                        'staff' => $staff,
                        'type' => 'password',
                    )
                ),
                'header' => $this->get('translator')->trans('staff.list.password.force_edit'),
            )
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/synchronizeUsers",name="synchronize_users",options={"expose"=true})
     */
    public function synchronizeUsersWithWyndAction(Request $request)
    {
        $this->get('app.security.checker')->checkOrThrowAccedDenied('synchronize_users');
        $response = new JsonResponse();
        try {
            $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
            $countUsers = $this->get('staff.service')->importUsers($currentRestaurant);
            $response->setData(
                [
                    "countNewUsers" => $countUsers['addedUsers'],
                    "countDeletedUsers" => $countUsers['deletedUsers'],
                ]
            );
        } catch (\Exception $e) {
            $response->setData(
                [
                    "errors" => [
                        $this->get('translator')->trans('Error.general.internal'),
                        $e->getLine()." : ".$e->getTraceAsString(),
                    ],
                ]
            );
        }

        return $response;
    }

    /**
     * @return JsonResponse
     * @Route("/modifyPassword",name="force_password_modification",options={"expose"=true})
     */
    public function forcePasswordModificationAction()
    {
        return $this->render("@Staff/force_password_modification.html.twig");
    }

    /**
     * @param Employee $staff
     * @param Role $role
     * @return JsonResponse
     * @Route("/deleteStaffRole/{staff}/{role}",name="delete_staff_role",options={"expose"=true})
     */
    public function deleteStaffRoleAction(Employee $staff, Role $role)
    {
        $response = new JsonResponse();
        try {
            $deleted = $this->get('staff.service')->deleteStaffRole($staff, $role);
            $response->setData(
                [
                    "deleted" => $deleted,
                ]
            );
        } catch (\Exception $e) {
            $response->setData(
                [
                    "errors" => [
                        $this->get('translator')->trans('Error.general.internal'),
                        $e->getLine()." : ".$e->getTraceAsString(),
                    ],
                ]
            );
        }

        return $response;
    }

    /**
     * @RightAnnotation ("add_role")
     * @param Request $request
     * @param Role $role
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/addRole/{role}",name="add_role",options={"expose"=true})
     */
    public function addRoleAction(Request $request, Role $role = null)
    {

        $action = 'edit';
        if ($role == null) {
            $role = new Role();
            $action = 'add';
        }

        $form = $this->createForm(RoleType::class, $role);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->get('staff.service')->saveRole($role);
                $message = $this->get('translator')->trans('staff.role.'.$action.'_success');
                $this->get('session')->getFlashBag()->add('success', $message);

                return $this->redirectToRoute('index_config_role');
            }
        }

        $roles = $this->getDoctrine()->getRepository("Security:Role")->findAllButNotEmployee();

        return $this->render(
            "@Staff/Management/add_role.html.twig",
            [
                'form' => $form->createView(),
                'roles' => $roles,
            ]
        );
    }

    /**
     * @param Request $request
     * @param Role $role
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/deleteRole/{role}",name="delete_role",options={"expose"=true})
     */
    public function deleteRoleAction(Request $request, Role $role)
    {
        $response = new JsonResponse();
        try {
            $deleted = $this->get('staff.service')->deleteRole($role);
            if ($deleted) {
                $message = $this->get('translator')->trans('staff.role.delete_success');
                $this->get('session')->getFlashBag()->add('success', $message);
            }
            $response->setData(
                [
                    "deleted" => $deleted,
                ]
            );
        } catch (\Exception $e) {
            $response->setData(
                [
                    "errors" => [
                        $this->get('translator')->trans('Error.general.internal'),
                        $e->getLine()." : ".$e->getTraceAsString(),
                    ],
                ]
            );
        }

        return $response;
    }

    /**
     * @param Employee $staff
     * @param Request $request
     * @return JsonResponse
     * @Route("/json/change_email/{staff}",name="change_email",options={"expose"=true})
     */
    public function ChangeEmailJsonAction(Request $request, Employee $staff)
    {
        $currentUser = null;
        if (!is_null($this->get('security.token_storage'))) {
            $securityToken = $this->get('security.token_storage');
            $currentUser = (!is_null($securityToken->getToken())) ? $securityToken->getToken()->getUser() : null;
        }

        //Test if the staff is the current employee
        if ($staff != null && $currentUser != $staff) {
            try
            {
                $this->get('app.security.checker')->checkOrThrowAccedDenied('change_email');
            }
            catch (AccessDeniedException $e)
            {
                $response = new JsonResponse();
                $response->setData([
                    "errors" => [$this->get('translator')->trans('Error.general.internal'), $e->getMessage()],
                ]);
                return $response;
            }
        }

        $form = $this->createForm(ChangeEmailType::class, $staff);

        if ($request->isXmlHttpRequest()) {
            if ($request->getMethod() === "POST") {
                $response = new JsonResponse();
                try {
                    $form->handleRequest($request);
                    if ($form->isValid()) {
                        $this->getDoctrine()->getManager()->flush();
                        $response->setData([
                            "staff" => $staff->getId()
                        ]);
                        return $response;
                    } else {
                        $response->setData([
                            "formError" => [
                                $this->renderView('@Staff/Management/parts/change_email_form.html.twig', array(
                                    'form' => $form->createView(),
                                    "staff" => $staff
                                )),
                            ]
                        ]);
                    }
                } catch (\Exception $e) {
                    $response->setData([
                        "errors" => [$this->get('translator')->trans('Error.general.internal'), $e->getMessage()],
                    ]);
                }
                return $response;
            }
        }

        return new JsonResponse(array(
            'formBody' => $this->renderView("@Staff/Management/parts/change_email_form.html.twig", array(
                'form' => $form->createView(),
                'staff' => $staff
            )),
            'footer' => $this->renderView("@Staff/Management/parts/footer_form_role.html.twig", array(
                'staff' => $staff,
                'type' => 'email'
            )),
            'header' => $this->get('translator')->trans('staff.list.password.force_edit')
        ));
    }
}
