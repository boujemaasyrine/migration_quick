<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 03/12/2015
 * Time: 10:28
 */

namespace AppBundle\Supervision\Handler;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class AuthenticationHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface
{
    private $router;
    private $session;
    private $translator;

    /**
     * Constructor
     *
     * @author Joe Sexton <joe@webtipblog.com>
     * @param  RouterInterface $router
     * @param  Session $session
     */
    public function __construct(RouterInterface $router, Session $session, Translator $translator)
    {
        $this->router = $router;
        $this->session = $session;
        $this->translator = $translator;
    }

    /**
     * onAuthenticationSuccess
     *
     * @author Joe Sexton <joe@webtipblog.com>
     * @param  Request $request
     * @param  TokenInterface $token
     * @return Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {

        $request->getSession()->set('_locale', $token->getUser()->getDefaultLocale());


        // if AJAX login
        if ($request->isXmlHttpRequest()) {
            $array = ['success' => true]; // data to return via JSON
            $response = new Response(json_encode($array));
            $response->headers->set('Content-Type', 'application/json');

            return $response;

            // if form login
        } else {
            if ($this->session->get('_security.main.target_path')) {
                $url = $this->session->get('_security.main.target_path');
            } else {
                $url = $this->router->generate('home');
            } // end if

            return new RedirectResponse($url);
        }
    }

    /**
     * onAuthenticationFailure
     *
     * @author Joe Sexton <joe@webtipblog.com>
     * @param  Request $request
     * @param  AuthenticationException $exception
     * @return Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        // if AJAX login
        if ($request->isXmlHttpRequest()) {
            $array = [
                'success' => false,
                'message' => $this->translator->trans($exception->getMessageKey(), [], 'security'),
            ]; // data to return via JSON
            $response = new JsonResponse($array);

            return $response;

            // if form login
        } else {
            // set authentication exception to session
            $request->getSession()->set(SecurityContextInterface::AUTHENTICATION_ERROR, $exception);

            return new RedirectResponse($this->router->generate('login'));
        }
    }
}
