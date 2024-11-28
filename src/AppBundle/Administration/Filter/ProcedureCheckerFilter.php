<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 09/02/2016
 * Time: 10:49
 */

namespace AppBundle\Administration\Filter;

use AppBundle\Administration\Service\WorkflowService;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Doctrine\ORM\EntityManager;

/**
 * Class ProcedureCheckerFilter
 */
class ProcedureCheckerFilter
{

    private $tokenStorage;
    private $user;
    private $router;
    private $em;
    private $translator;
    private $workflow;

    /**
     * ProcedureCheckerFilter constructor.
     * @param TokenStorage $tokenStorage
     * @param Router $router
     * @param EntityManager $em
     * @param Translator $translator
     * @param WorkflowService $workflow
     */
    public function __construct(TokenStorage $tokenStorage, Router $router, EntityManager $em, Translator $translator, WorkflowService $workflow)
    {
        if (null !== $tokenStorage && null !== $tokenStorage->getToken() && null !== $tokenStorage->getToken()->getUser()) {
            $this->user = $tokenStorage->getToken()->getUser();
        } else {
            $this->user = null;
        }

        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->em = $em;
        $this->translator = $translator;
        $this->workflow = $workflow;
    }


    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $user = null;

        if ($this->tokenStorage->getToken()) {
            $user = $this->tokenStorage->getToken()->getUser();
        }

        if ($request->getSession()->has('current_workflow')) {
            $currentWorkflow = $request->getSession()->get('current_workflow');
            if ($currentWorkflow) {
                $procedureInstance = $this->em->getRepository("Administration:ProcedureInstance")->find(
                    $currentWorkflow['instanceID']
                );
                if ($procedureInstance && $user && $procedureInstance->getUser() != $user) {
                    $request->getSession()->remove('current_workflow');

                    $newResponse = new RedirectResponse($this->router->generate('index'));
                    $event->setResponse($newResponse);
                }
            }
        }
    }
}
