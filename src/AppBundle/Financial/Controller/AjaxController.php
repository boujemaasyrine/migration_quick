<?php
namespace AppBundle\Financial\Controller;
/**
 * Created by PhpStorm.
 * User: zbessassi
 * Date: 27/05/2019
 * Time: 12:01
 */

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AjaxController extends Controller{
    /**
     * @param Request $request
     * @return JsonResponse
     * @Route("/ajax-check-password",name="ajax_check_password",options={"expose"=true})
     */
    public function checkPasswordAction(Request $request){
        if ($request->isXmlHttpRequest()) {
            try {
                $password = $request->request->get('password');
                $data = ["status" => -1];
                $user = $this->getUser();
                $factory = $this->get('security.encoder_factory');
                $encoder = $factory->getEncoder($user);
                $salt = $user->getSalt();

                if ($encoder->isPasswordValid($user->getPassword(), trim($password), $salt)) {
                    $data = ["status" => 1];
                } else {
                    $data = ["status" => -1];
                }


            } catch (\Exception $e) {
                $data =
                    [
                        "status" => 0,
                    ];
            }
            return new JsonResponse($data);
        } else {
            throw new AccessDeniedHttpException("This method accept only ajax calls.");
        }
    }
}