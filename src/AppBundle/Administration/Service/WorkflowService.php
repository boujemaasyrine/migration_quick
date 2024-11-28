<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 27/04/2016
 * Time: 10:22
 */

namespace AppBundle\Administration\Service;

use AppBundle\Administration\Entity\Procedure;
use AppBundle\Administration\Entity\ProcedureInstance;
use AppBundle\Administration\Entity\ProcedureStep;
use AppBundle\Financial\Service\AdministrativeClosingService;
use AppBundle\Merchandise\Service\RestaurantService;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class WorkflowService
 *
 * @package AppBundle\Administration\Service
 *
 * When an instance in session it must be in the current_workflow
 *   - procedure : object
 *   - current_step : int
 *   - instanceID : int
 */
class WorkflowService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var TokenStorage
     */
    private $token;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Translator
     */
    private $translator;

    private $administrativeClosingService;

    private $restaurantService;

    /**
     * WorkflowService constructor.
     *
     * @param EntityManager                $em
     * @param Session                      $session
     * @param TokenStorage                 $token
     * @param Router                       $router
     * @param RequestStack                 $requestStack
     * @param Translator                   $translator
     * @param AdministrativeClosingService $administrativeClosing
     * @param RestaurantService            $restaurantService
     */
    public function __construct(
        EntityManager $em,
        Session $session,
        TokenStorage $token,
        Router $router,
        RequestStack $requestStack,
        Translator $translator,
        AdministrativeClosingService $administrativeClosing,
        RestaurantService $restaurantService
    ) {
        $this->em = $em;
        $this->session = $session;
        $this->token = $token;
        $this->router = $router;
        $this->request = $requestStack->getCurrentRequest();
        $this->translator = $translator;
        $this->administrativeClosingService = $administrativeClosing;
        $this->restaurantService = $restaurantService;
    }

    /**
     * @param Procedure $procedure
     *
     * @return null|JsonResponse|RedirectResponse
     */
    public function startWorkflow(Procedure $procedure)
    {
        //Test IF there is a pending procedure,
        // IF YES charge instance into session and go to the current step
        if ($this->inWorkflow()) {
            if ($procedure != $this->getCurrentProcedure()) {
                $this->session->getFlashBag()->add('info', "procedure_pending");
            }

            return $this->goToCurrentStep();
        }
        $instanceInBase = $this->pendingInstanceInBase();
        if ($instanceInBase) {
            $this->chargeInstanceIntoSession($instanceInBase);
            if ($procedure != $this->getCurrentProcedure()) {
                $this->session->getFlashBag()->add('info', "procedure_pending");
            }

            return $this->goToCurrentStep();
        }

        //Test on roles of the procedure
        $requiredRoles = $procedure->getEligibleRoles();
        $employeeRoles = $this->token->getToken()->getUser()->getEmployeeRoles(
        );
        $hasRight = false;
        foreach ($employeeRoles as $r) {
            if (in_array($r, $requiredRoles->toArray())) {
                $hasRight = true;
            }
        }

        if (!$hasRight) {
            $this->session->getFlashBag()->add(
                'warning',
                $this->translator->trans(
                    'procedure_not_authroized',
                    ['%1%' => $procedure->getName()]
                )
            );

            return new RedirectResponse($this->router->generate('index'));
        }

        //Test IF the procedure is can be done once a day or no,
        // IF YES verify if he's done it that day,
        //   IF YES redirect to the workflow page with error msg
        if ($procedure->getOnlyOnceAtDay()) {
            $instances = $this->em->getRepository(ProcedureInstance::class)
                ->getInstanceByProcedureByDateByUser(
                    $procedure,
                    new \DateTime('today'),
                    $this->token->getToken()->getUser()
                );

            if (count($instances) > 0) {
                $this->session->getFlashBag()->add(
                    'warning',
                    $this->translator->trans(
                        'procedure_executed_once_a_day',
                        ['%1%' => $procedure->getName()]
                    )
                );

                return new RedirectResponse($this->router->generate('index'));
            }
        }

        //Test IF the procedure is can be done a the same time,
        // IF YES verify if there's a user into the procedure,
        //   IF YES redirect to the worfklow index with error msg
        if (!$procedure->getAtSameTime()) {
            $instances = $this->em->getRepository(ProcedureInstance::class)
                ->getAllPendingInstance($procedure);

            if (count($instances) > 0) {
                $this->session->getFlashBag()->add(
                    'warning',
                    $this->translator->trans(
                        'procedure_cannot_be_executed_at_the_same_time',
                        ['%1%' => $procedure->getName()]
                    )
                );

                return new RedirectResponse($this->router->generate('index'));
            }
        }


        //TEST if the procedure must be done once for all users,
        // IF YES verify if there an instance for that procedure done for that day,
        //   IF YES redirect to the workflow
        if ($procedure->getOnlyOnceForAll()) {
            $instances = $this->em->getRepository(ProcedureInstance::class)
                ->getAllInstanceForADate($procedure, new \DateTime('today'));

            if (count($instances) > 0) {
                $this->session->getFlashBag()->add(
                    'warning',
                    $this->translator->trans(
                        'procedure_executed_once_a_day_for_all_user',
                        ['%1%' => $procedure->getName()]
                    )
                );

                return new RedirectResponse($this->router->generate('index'));
            }
        }
        $procedureInstance = new ProcedureInstance();
        $procedureInstance
            ->setCurrentStep(1)
            ->setProcedure($procedure)
            ->setStatus(ProcedureInstance::PENDING)
            ->setUser($this->token->getToken()->getUser());
        $this->em->persist($procedureInstance);
        $this->em->flush();

        $this->session
            ->set(
                'current_workflow',
                array(
                    'procedure'    => $procedure,
                    'current_step' => 1,
                    'instanceID'   => $procedureInstance->getId(),
                )
            );

        return $this->generateResponseFromProcedureStep(
            $procedure->getStepByOrder(1)
        );
    }

    /**
     * @param Procedure $procedure
     *
     * @return bool
     */
    public function verifyRole(Procedure $procedure)
    {
        //Test on roles of the procedure
        $requiredRoles = $procedure->getEligibleRoles();
        $employeeRoles = $this->token->getToken()->getUser()->getEmployeeRoles(
        );
        $hasRight = false;
        foreach ($employeeRoles as $r) {
            if ($requiredRoles->contains($r)) {
                $hasRight = true;
            }
        }

        return $hasRight;
    }

    /**
     * @param Response|null $originalResponse
     * @param null          $outRouteOf
     *
     * @return JsonResponse|RedirectResponse|Response
     */
    public function nextStep(
        Response $originalResponse = null,
        $outRouteOf = null
    ) {
        if (!$this->inWorkflow()) {
            if (!$originalResponse) {
                return new RedirectResponse($this->router->generate('index'));
            } else {
                return $originalResponse;
            }
        } else {
            //Dans le cas ou la procedure a été modifier ou supprimée
            $procedure = $this->getCurrentProcedure();
            if (!$procedure) {
                $this->endWorkflow();
                if (!$originalResponse) {
                    return new RedirectResponse(
                        $this->router->generate('index')
                    );
                } else {
                    return $originalResponse;
                }
            }

            $instance = $this->getCurrentProcedureInstance();

            if ($instance->getUser() != $this->token->getToken()->getUser()) {
                if ($this->session->has('current_workflow')) {


                    $this->session->remove('current_workflow');
                }
                $this->session->getFlashBag()->add(
                    'warning',
                    $this->translator->trans(
                        'procedure_transfered',
                        array('%1%' => $procedure->getName())
                    )
                );

                return new RedirectResponse($this->router->generate('index'));
            }

            $currentAction = $procedure->getStepByOrder(
                $instance->getCurrentStep()
            )->getAction();

            if (!$outRouteOf) {
                $routeMatching = $this->router->matchRequest($this->request);
                $outRouteOf = $routeMatching['_route'];
            }

            if ($outRouteOf != $currentAction->getRoute()
                && $originalResponse
            ) {
                return $originalResponse;
            }

            if ($this->inAdministrativeClosing()) {
                $x = $this->goToCurrentStepInAdminClosing();
                if ($x) {
                    return $x;
                }
            }

            if ($instance->isFinalStep()) {
                $this->endWorkflow($instance);
                $this->session->getFlashBag()->add(
                    'success',
                    'procedure.finish.success'
                );
                if (!$originalResponse) {
                    return new RedirectResponse(
                        $this->router->generate('index')
                    );
                } else {
                    return $originalResponse;
                }
            } else {
                $instance->next();
                $nextStep = $procedure->getStepByOrder(
                    $instance->getCurrentStep()
                );
                $this->incrementStepInSession();
                $this->em->flush();
                if ($this->request->isXmlHttpRequest()) {
                    return $this->generateResponseFromProcedureStep(
                        $nextStep,
                        true
                    );
                } else {
                    return $this->generateResponseFromProcedureStep($nextStep);
                }
            }
        }//End IN WORKFLOW TEST
    }

    /**
     *
     */
    public function incrementStepInSession()
    {
        $currentWorkflow = $this->session->get('current_workflow');
        $currentWorkflow['current_step']++;
        $this->session->set('current_workflow', $currentWorkflow);
    }

    /**
     * @param ProcedureInstance|null $procedureInstance
     */
    public function endWorkflow(ProcedureInstance $procedureInstance = null)
    {

        if (!$procedureInstance) {
            $procedureInstance = $this->getCurrentProcedureInstance();
        }
        if ($procedureInstance) {
            $procedureInstance->setStatus(ProcedureInstance::FINISH);
            $this->em->flush();
        }

        if ($this->session->has('current_workflow')) {

            $this->session->remove('current_workflow');
        }
    }

    /**
     * @throws \Exception
     *
     * @return ProcedureInstance
     */
    public function getCurrentProcedureInstance()
    {
        $currentWorkflow = $this->session->get('current_workflow');
        if ($currentWorkflow) {
            $procedureInstance = $this->em->getRepository(
                ProcedureInstance::class
            )->find(
                $currentWorkflow['instanceID']
            );

            return $procedureInstance;
        }

        return null;
    }

    /**
     * @return Procedure
     */
    public function getCurrentProcedure()
    {
        $currentWorkflow = $this->session->get('current_workflow');
        if ($currentWorkflow['procedure'] == null) {
            $this->endWorkflow();

            return null;
        }
        $procedure = $this->em->getRepository("Administration:Procedure")->find(
            $currentWorkflow['procedure']->getId()
        );
        if (!$procedure) {
            $this->endWorkflow();

            return null;
        }

        return $procedure;
    }

    /**
     * @return bool
     */
    public function inWorkflow()
    {
        if ($this->session->has('current_workflow')) {
            $procedure = $this->getCurrentProcedure();
            if (!$procedure) {
                $this->endWorkflow();

                return false;
            }

            $instance = $this->getCurrentProcedureInstance();

            if (null === $instance) {
                $this->endWorkflow();

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @param ProcedureStep $step
     * @param bool          $isAjax
     *
     * @return JsonResponse|RedirectResponse
     */
    public function generateResponseFromProcedureStep(
        ProcedureStep $step,
        $isAjax = false
    ) {

        $url = $this->router->generate(
            $step->getAction()->getRoute(),
            $step->getAction()->getParams()
        );

        if (!$isAjax) {
            $redirectResponse = new RedirectResponse($url);

            return $redirectResponse;
        }
        $jsonResponse = new JsonResponse(
            array(
                'in_workflow' => true,
                'redirect_to' => $url,
            )
        );

        return $jsonResponse;

    }

    /**
     * @return array
     */
    public function getProcedures()
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $result = [];
        $procedures = $this->em->getRepository(Procedure::class)->findBy(
            array(
                'originRestaurant' => $currentRestaurant,
            )
        );

        foreach ($procedures as $p) {
            if (strtolower($p->getName()) == strtolower('ouverture')) {
                $result[] = $p;
            }
        }

        foreach ($procedures as $p) {
            if (strtolower($p->getName()) == strtolower('fermeture')) {
                $result[] = $p;
            }
        }

        foreach ($procedures as $p) {
            if (strtolower($p->getName()) != strtolower('fermeture')
                && strtolower($p->getName()) != strtolower('ouverture')
            ) {
                $result[] = $p;
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function inAdministrativeClosing()
    {
        try {
            if (!$this->inWorkflow()) {
                return false;
            }
            $procedure = $this->getCurrentProcedure();
            if (!$procedure) {
                return false;
            }
            $instance = $this->getCurrentProcedureInstance();
            if (null === $instance) {
                $this->endWorkflow();

                return false;
            }
            $currentAction = $procedure->getStepByOrder(
                $instance->getCurrentStep()
            )->getAction();
            if ($currentAction->getName() == 'administrative_closing') {
                return true;
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    /**
     * @param ProcedureInstance $instance
     */
    public function chargeInstanceIntoSession(ProcedureInstance $instance)
    {

        $data = [
            'procedure'    => $instance->getProcedure(),
            'current_step' => $instance->getCurrentStep(),
            'instanceID'   => $instance->getId(),
        ];

        $this->session->set('current_workflow', $data);
    }

    /**
     * @return null|JsonResponse|RedirectResponse|Response
     */
    public function goToCurrentStep()
    {
        $procedure = $this->getCurrentProcedure();
        if (!$procedure) {
            $this->endWorkflow();

            return new RedirectResponse($this->router->generate('index'));
        }

        $instance = $this->getCurrentProcedureInstance();

        if (null === $instance) {
            $this->endWorkflow();

            return $this->nextStep();
        } elseif ($instance->getUser() != $this->token->getToken()->getUser()) {
            if ($this->session->has('current_workflow')) {

                $this->session->remove('current_workflow');
            }

            return new RedirectResponse($this->router->generate('index'));
        }

        $currentStep = $procedure->getStepByOrder($instance->getCurrentStep());

        if ($this->inAdministrativeClosing()) {
            $x = $this->goToCurrentStepInAdminClosing();
            if ($x) {
                return $x;
            }
        }

        if ($this->request) {
            if (!$this->request->isXmlHttpRequest()) {
                return $this->generateResponseFromProcedureStep($currentStep);
            } else {
                return $this->generateResponseFromProcedureStep(
                    $currentStep,
                    true
                );
            }
        } else {
            return $this->generateResponseFromProcedureStep($currentStep);
        }
    }

    /**
     * @param ProcedureInstance $instance
     *
     * @return null|JsonResponse|RedirectResponse|Response
     */
    public function goToCurrentStep2(ProcedureInstance $instance)
    {

        $instance->setUser($this->token->getToken()->getUser());
        $this->em->flush();
        $this->chargeInstanceIntoSession($instance);

        $procedure = $instance->getProcedure();
        if (!$procedure) {
            $this->endWorkflow();

            return new RedirectResponse($this->router->generate('index'));
        }

        if (null === $instance) {
            $this->endWorkflow();

            return $this->nextStep();
        }

        $currentStep = $procedure->getStepByOrder($instance->getCurrentStep());

        if ($this->inAdministrativeClosing()) {
            $x = $this->goToCurrentStepInAdminClosing();
            if ($x) {
                return $x;
            }
        }

        if ($this->request) {
            if (!$this->request->isXmlHttpRequest()) {
                return $this->generateResponseFromProcedureStep($currentStep);
            } else {
                return $this->generateResponseFromProcedureStep(
                    $currentStep,
                    true
                );
            }
        } else {
            return $this->generateResponseFromProcedureStep($currentStep);
        }
    }

    /**
     * @return ProcedureInstance
     */
    public function pendingInstanceInBase()
    {
        return $this->em->getRepository(ProcedureInstance::class)
            ->findOneBy(
                array(
                    'user'   => $this->token->getToken()->getUser(),
                    'status' => ProcedureInstance::PENDING,
                )
            );
    }

    /**
     * @return bool|null
     *  null if not in workflow
     *  true if in workflow and in the current step
     *  false else
     */
    public function inCurrentStep()
    {
        if ($this->inWorkflow()) {
            $procedure = $this->getCurrentProcedure();
            if (!$procedure) {
                $this->endWorkflow();

                return null;
            }

            $instance = $this->getCurrentProcedureInstance();

            if ($instance == null) {
                $this->endWorkflow();

                return null;
            } elseif ($instance->getUser()->getId() !== $this->token->getToken()
                    ->getUser()->getId()
            ) {

                if ($this->session->has('current_workflow')) {

                    $this->session->remove('current_workflow');
                }

                return null;
            }

            $currentStep = $procedure->getStepByOrder(
                $instance->getCurrentStep()
            );
            $routeNameForCurrentAction = $currentStep->getAction()->getRoute();
            $currentRouteForRequest = $this->router->getMatcher()->match(
                $this->router->getContext()->getPathInfo()
            )['_route'];

            $in = ($routeNameForCurrentAction == $currentRouteForRequest);


            if (!$in) {
                //Test in cloture
                if ($this->inAdministrativeClosing()) {
                    $subStep = $this->getSubStep();
                    switch ($subStep) {
                        case null:
                            return false;
                        /*       case 1 :
                                   return $currentRouteForRequest == "verify_last_date";*/

                        case 1:
                            return $currentRouteForRequest == "kiosk_counting";
                        case 2 :
                            return $currentRouteForRequest
                                == "validation_income_show";
                        case 3 :
                            return $currentRouteForRequest == "deposit_V2";
                        case 4 :
                            return $currentRouteForRequest == "check_withdrawal_envelope";
                        case 5 :
                            return $currentRouteForRequest == "chest_count";
                        case 6 :
                            return $currentRouteForRequest == "comparable_day";
                        default:
                            return false;
                    }

                    //old version
                    //                    if (in_array($currentRouteForRequest,
                    //                        ["verify_last_date", "deposit_electronic", "validation_income_show", "chest_count", "validation_income_validate", "comparable_day"])) {
                    //                        $in = true;
                    //                    }
                }
            }

            return $in;
        }
    }

    /**
     * @return ProcedureInstance[]
     */
    public function pendingProceduresByUsers()
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $procedures = $this->em->getRepository(Procedure::class)->findBy(
            array(
                'originRestaurant' => $currentRestaurant,
            )
        );
        $instances = $this->em->getRepository(ProcedureInstance::class)
            ->createQueryBuilder('p')
            ->where("p.status = :pending")
            ->andWhere("p.user != :me")
            ->andWhere("p.procedure IN (:procedures)")
            ->setParameter("pending", ProcedureInstance::PENDING)
            ->setParameter("me", $this->token->getToken()->getUser())
            ->setParameter("procedures", $procedures)
            ->getQuery()
            ->getResult();

        return $instances;
    }

    /**
     * @param $subStep
     */
    public function setSubStep($subStep)
    {
        $currentStep = $this->getCurrentProcedureInstance();
        if ($currentStep) {
            $currentStep->setSubStep($subStep);
            $this->em->flush();
        }
    }

    /**
     * @return int|null
     */
    public function getSubStep()
    {
        $subStep = null;
        $currentStep = $this->getCurrentProcedureInstance();
        if ($currentStep) {
            $subStep = $currentStep->getSubStep();
        }

        return $subStep;
    }

    /**
     * @return null|JsonResponse|RedirectResponse|Response
     */
    public function goToCurrentStepInAdminClosing()
    {
        $procedure = $this->getCurrentProcedure();
        if (!$procedure) {
            $this->endWorkflow();

            return new RedirectResponse($this->router->generate('index'));
        }
        $instance = $this->getCurrentProcedureInstance();

        if (null === $instance) {
            $this->endWorkflow();

            return $this->nextStep();
        }

        //Test If is in Closing Admin
        //If YES Redirect to the sub step
        if ($this->inAdministrativeClosing()) {
            $subStep = $instance->getSubStep();

            if ($subStep !== null && $subStep > 0) {
                $url = null;
                switch ($subStep) {
                    case 1:
                        $url = $this->router->generate('kiosk_counting');
                        break;
                    case 2 :
                        $url = $this->router->generate(
                            'validation_income_show'
                        );
                        break;
                    case 3:
                        $url = $this->router->generate('deposit_V2');
                        break;
                    case 4:
                        $url = $this->router->generate('check_withdrawal_envelope');
                        break;
                    case 5 :
                        $url = $this->router->generate('chest_count');
                        $this->administrativeClosingService->setInChestCount();
                        break;
                    case 6 :
                        $url = $this->router->generate('comparable_day');
                        break;
                }

                if ($url) {
                    $response = new RedirectResponse($url);

                    return $response;
                }
            }
        }

        return null;
    }
}
