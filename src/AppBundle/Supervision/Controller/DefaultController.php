<?php

namespace AppBundle\Supervision\Controller;

use AppBundle\Administration\Entity\Action;
use AppBundle\Security\Entity\Role;
use AppBundle\Staff\Entity\Employee;
use AppBundle\Supervision\Form\ChangeLanguageType;
use AppBundle\Supervision\Form\UsersManagement\ChangePasswordType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use AppBundle\ToolBox\Utils\ExcelUtilities;

class DefaultController extends Controller
{

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/",name="home")
     */
    public function homeAction()
    {

        return $this->redirectToRoute('restaurant_list_super');
    }

    /**
     * @param Request $request
     * @param string  $locale
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/locale/{locale}", name="supervision_locale_switch",options={"expose"=true})
     */
    public function localeAction(Request $request, $locale)
    {
        $request->getSession()->set('_locale', $locale);

        $referer = $request->headers->get('referer');
        if (empty($referer)) {
            return $this->redirect($this->generateUrl('home'));
        }

        return $this->redirect($referer);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/profile", name="supervision_user_profile")
     */
    public function profileAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('Staff:Employee')->find($this->getUser()->getId());

        $form_password = $this->createForm(ChangePasswordType::class);

        $form_language = $this->createForm(
            ChangeLanguageType::class,
            [
                'language' => $user->getDefaultLocale(),
            ]
        );

        $form_language->handleRequest($request);
        $form_password->handleRequest($request);

        if ($form_language->isValid()) {
            $user->setDefaultLocale($form_language->get('language')->getData());
            $em->flush();

            $this->addFlash('success', $this->get('translator')->trans('language.change_confirm'));
        }
        if ($form_password->isValid()) {
            $newPwEncoded = $this->get('security.password_encoder')->encodePassword(
                $user,
                $form_password->get('password')->getData()
            );
            $user->setPassword($newPwEncoded);

            //$this->get('sync.create.entry.service')->createUserEntry($user);

            $em->persist($user);
            $em->flush();
            $this->addFlash('success', $this->get('translator')->trans('profile.change_password.confirm'));
        }

        return $this->render(
            '@Supervision/profile.html.twig',
            [
                'form_language' => $form_language->createView(),
                'form_password' => $form_password->createView(),
                'user' => $user,
            ]
        );
    }

    /**
     * @Route("/export_roles")
     */
    public function exportRoles()
    {

        $rolesRestaurant = $this->getDoctrine()->getRepository('AppBundle:Security\Role')->findBy(
            array(
                'type' => Role::RESTAURANT_ROLE_TYPE,
            )
        );

        $phpExcel = $this->get('phpexcel');
        $phpExcelObject = $phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle('BO');
        $sheet->getColumnDimension('A')->setWidth(100);
        $i = 1;
        foreach ($this->_sortRoles($rolesRestaurant) as $r) {
            $sheet->setCellValue("A$i", $r->getTextLabel()." (".count($r->getActions()).")");
            ExcelUtilities::setFont($sheet->getStyle("A$i"), 14, true);
            ExcelUtilities::setBackgroundColor($sheet->getStyle("A$i"), "CDDAB4");
            $i++;
            if (count($r->getActions()) > 0) {
                foreach ($this->_sortActions($r->getActions()) as $a) {
                    $sheet->setCellValue("A$i", $this->get('translator')->trans($a->getName(), [], 'actions'));
                    $i++;
                }
            } else {
                $sheet->setCellValue("A$i", 'AUCUN ROLE');
                $i++;
            }
        }

        $rolesCentral = $this->getDoctrine()->getRepository('AppBundle:Security\Role')->findBy(
            array(
                'type' => Role::CENTRAL_ROLE_TYPE,
            )
        );
        $phpExcelObject->createSheet();
        $phpExcelObject->setActiveSheetIndex(1);
        $sheet2 = $phpExcelObject->getActiveSheet();
        $sheet2->setTitle('CENTRAL');
        $sheet2->getColumnDimension('A')->setWidth(100);
        $i = 1;
        foreach ($this->_sortRoles($rolesCentral) as $r) {
            $sheet2->setCellValue("A$i", $r->getTextLabel()." (".count($r->getActions()).")");
            ExcelUtilities::setFont($sheet2->getStyle("A$i"), 14, true);
            ExcelUtilities::setBackgroundColor($sheet2->getStyle("A$i"), "CDDAB4");
            $i++;
            if (count($r->getActions()) > 0) {
                foreach ($this->_sortActions($r->getActions()) as $a) {
                    $sheet2->setCellValue("A$i", $this->get('translator')->trans($a->getName(), [], 'actions'));
                    $i++;
                }
            } else {
                $sheet2->setCellValue("A$i", 'AUCUN ROLE');
                $i++;
            }
        }

        $phpExcelObject->setActiveSheetIndex(0);

        //Creation de la response
        $filename = "rights.xls";
        // create the writer
        $writer = $phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    private function _sortActions($actions)
    {
        $actions = $actions->toArray();
        usort(
            $actions,
            function (Action $a1, Action $a2) {
                if ($this->get('translator')->trans($a1->getName(), [], 'actions') < $this->get('translator')->trans(
                    $a2->getName(),
                    [],
                    'actions'
                )
                ) {
                    return -1;
                }

                return 1;
            }
        );

        return $actions;
    }

    /**
     * @param Role[] $roles
     * @return Role[]
     */
    public function _sortRoles($roles)
    {
        usort(
            $roles,
            function (Role $r1, Role $r2) {
                if ($r1->getTextLabel() < $r2->getTextLabel()) {
                    return -1;
                }

                return 1;
            }
        );

        return $roles;
    }
}
