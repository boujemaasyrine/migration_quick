<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 30/05/2016
 * Time: 16:05
 */

namespace AppBundle\Supervision\Controller;

use AppBundle\Security\Entity\Role;
use AppBundle\Security\Entity\User;
use AppBundle\Security\RightAnnotation;
use AppBundle\Staff\Entity\Employee;
use AppBundle\Supervision\Form\UsersManagement\AddUserType;
use AppBundle\Supervision\Form\UsersManagement\RoleType;
use AppBundle\Supervision\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UsersManagementController
 *
 * @package                    AppBundle\Controller
 * @Route("/users_management")
 */
class UsersManagementController extends Controller
{

    /**
     * @RightAnnotation("users_list")
     * @param User $user
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/list_users/{user}",name="users_list", options={"expose"=true})
     */
    /*
     * working
     */
    public function listUsersAction(Request $request, Employee $user = null)
    {

        if (!$user) {
            $user = new Employee();
            $options['role'] = null;
        } else {
            $options['role'] =  $this->get('users.service')->getRoleManagementControl($user);
        }

        $formAdd = $this->createForm(AddUserType::class, $user, $options);
        $users = $this->getDoctrine()->getRepository(Employee::class)->getShownUsers();


        //        $users = $this->getDoctrine()->getRepository('AppBundle:Security\User')->findBy([
        //            'active' => true, 'deleted' => false ]);

        if ($request->getMethod() === 'POST') {
            $formAdd->handleRequest($request);
            if ($formAdd->isValid()) {
                $role = $formAdd->get("role")->getData();
                $this->get('users.service')->saveUser($user, $role);
                $message = $this->get('translator')->trans('users.list.add_success', array(), "supervision");
                $this->get('session')->getFlashBag()->add('success', $message);

                return $this->redirectToRoute('users_list');
            }
        }

        return $this->render(
            "@Supervision/UsersManagement/list_add_user.html.twig",
            array(
                'formAdd' => $formAdd->createView(),
                'users' => $users,
            )
        );
    }

    /**
     * @param User $user
     * @return JsonResponse
     * @Route("/json/user_details/{user}",name="user_details",options={"expose"=true})
     */
    public function UserDetailJsonAction(Employee $user)
    {

        return new JsonResponse(
            array(

                'data' => [
                    'bodyModal' => $this->renderView(
                        "@Supervision/UsersManagement/modals/details_users.html.twig",
                        array(
                            'user' => $user,
                        )
                    ),
                    'footerModal' => $this->renderView(
                        "@Supervision/UsersManagement/modals/footer_details_user.html.twig",
                        array('user' => $user)
                    ),
                ],
            )
        );
    }

    /**
     * @param User $user
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/deleteUser/{user}",name="delete_user",options={"expose"=true})
     */
    public function deleteUserAction(Employee $user)
    {
        $response = new JsonResponse();
        try {
            $deleted = $this->get('users.service')->deleteUser($user);
            if ($deleted) {
                $message = $this->get('translator')->trans('users.list.delete_success', [], "supervision");
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
                        $this->get('translator')->trans('Error.general.internal', [], "supervision"),
                        $e->getLine()." : ".$e->getTraceAsString(),
                    ],
                ]
            );
        }

        return $response;
    }

    /**
     * Export the list of supervsion users to excel format
     * The list is filtered by a criteria send in the request object
     *
     * @param                                                 Request $request
     * @return                                                JsonResponse
     * @Route("/users_list_export/",name="users_list_export", options={"expose"=true})
     */
    public function usersListExportAction(Request $request)
    {

        $orders = array('lastName', 'firstName', 'login', 'email', 'function');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);

        $dataTableHeaders['criteria']['usersSearch[keyword'] = $request->request->get('search')['value'];

        $fileName = $this->get('translator')->trans('staff.list', [], 'navbar_supervision').date('dmY_His');

        $response = $this->get('toolbox.document.generator')
            ->generateSupervisionXlsFile(
                'users.service',
                'getUsers',
                array(
                    'criteria' => $dataTableHeaders['criteria'],
                    'order' => $dataTableHeaders['orderBy'],
                ),
                $this->get('translator')->trans('staff.list', [], 'navbar_supervision')
                ,
                [
                    $this->get('translator')->trans('users.last_name', [], "supervision"),
                    $this->get('translator')->trans('users.first_name', [], "supervision"),
                    $this->get('translator')->trans('users.username', [], "supervision"),
                    $this->get('translator')->trans('mail_adresse', [], "supervision"),
                    $this->get('translator')->trans('labels.role', [], "supervision"),
                ],
                function ($line) {
                    return [
                        $line['lastName'],
                        $line['firstName'],
                        $line['login'],
                        $line['email'],
                        $line['function'],
                    ];
                },
                $fileName
            );

        return $response;
    }

    /**
     * @RightAnnotation("add_role")
     * @param Request $request
     * @param Role    $role
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
        if (in_array($role->getLabel(), Role::$SUPER_ADMINS_ROLES)) {
            return $this->redirectToRoute('add_role');
        }
        $roles = $this->getDoctrine()->getRepository(Role::class)->findAllButNotAdmin();

        $form = $this->createForm(RoleType::class, $role);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->get('users.service')->saveRole($role);
                $message = $this->get('translator')->trans('roles.'.$action.'_success', [], "supervision");
                $this->get('session')->getFlashBag()->add('success', $message);

                return $this->redirectToRoute('add_role');
            }
        }


        return $this->render(
            "@Supervision/UsersManagement/add_role.html.twig",
            [
                'form' => $form->createView(),
                'roles' => $roles,
                'type' => $action,
            ]
        );
    }

    /**
     * @param Request $request
     * @param Role    $role
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/deleteRole/{role}",name="delete_role",options={"expose"=true})
     */
    public function deleteRoleAction(Request $request, Role $role)
    {
        $response = new JsonResponse();
        try {
            $deleted = $this->get('users.service')->deleteRole($role);
            if ($deleted) {
                $message = $this->get('translator')->trans('roles.delete_success', [], "supervision");
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
                        $this->get('translator')->trans('Error.general.internal', [], "supervision"),
                        $e->getLine()." : ".$e->getTraceAsString(),
                    ],
                ]
            );
        }

        return $response;
    }
}
