<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 28/12/2015
 * Time: 16:05
 */

namespace AppBundle\Security\Controller;

use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SecurityController extends Controller
{

    /**
     * @Route("/login",name="login")
     */
    public function loginAction(Request $request)
    {
        $language=$request->get('locale');
        if(null != $language){
            $this->get('session')->set('_locale', $language);
            $referer = $request->headers->get('referer');
            if (empty($referer)) {
                throw $this->createNotFoundException('Page not found.');
            }

            return $this->redirect($referer);
        }

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    array('redirection' => $this->generateUrl('index'))
                );
            }

            return $this->redirectToRoute('index');
        }


        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(
                array('redirection' => $this->generateUrl('login'))
            );
        }

        $authenticationUtils = $this->get('security.authentication_utils');

        $errors = $authenticationUtils->getLastAuthenticationError();

        return $this->render(
            "@Security/login.html.twig",
            [
                'error' => $errors,
                'last_username' => $authenticationUtils->getLastUsername(),
            ]
        );
    }

    /**
     * @Route("/login_check",name="login_check")
     */
    public function loginCheckAction()
    {
        //The Security system will handle the security process
    }

    /**
     * @Route("/logout",name="logout", options={"expose"=true})
     */
    public function logoutAction()
    {
        //The Security system will handle the security process
    }

    /**
     * @param $right
     * @return JsonResponse
     * @Route("/has_right/{right}",name="has_right",options={"expose"=true})
     */
    public function hasRightAction($right)
    {

        $ok = $this->get('app.security.checker')->check($right);

        return new JsonResponse($ok);
    }

    /**
     * @Route("/reset_pw",name="reset_password")
     */
    public function resetPasswordAction(Request $request)
    {
        if ($request->getMethod() == 'POST' && $request->request->has('mail')) {
            $mail = $request->request->get('mail');

            $users=$this->getDoctrine()->getManager()->getRepository(Employee::class)->createQueryBuilder('e')
                ->where('e.email = :email')
                ->setParameter('email', $mail)
                ->getQuery()->getResult();
            if(empty($users)){
                $this->addFlash('error', 'user_not_found');
            }elseif (count($users)==1){
                $newPw = $this->get('app.reset.pw.service')->resetPassword($users[0]);
                $sended = $this->get('app.reset.pw.service')->sendUserNewPassword($users[0], $newPw);
                if ($sended) {
                    $this->addFlash('success', 'pw_reset_success');
                } else {
                    $this->addFlash('success', 'pw_reset_send_mail_fail');
                }
            }else{
                if($request->request->has('user_id')){
                    $user=$this->getDoctrine()->getManager()->getRepository(Employee::class)->find($request->request->get('user_id'));
                    if(!empty($user)){
                        $newPw = $this->get('app.reset.pw.service')->resetPassword($user);
                        $sended = $this->get('app.reset.pw.service')->sendUserNewPassword($user, $newPw);
                        if ($sended) {
                            $this->addFlash('success', 'pw_reset_success');
                        } else {
                            $this->addFlash('success', 'pw_reset_send_mail_fail');
                        }
                    }
                }else{
                    $this->addFlash('warning', 'select_user');
                    return $this->render('@Security/forgot_password.html.twig',array('multi_account'=>true,'users'=>$users,'mail'=>$mail));
                }
            }

        }

        return $this->render('@Security/forgot_password.html.twig');
    }
}
