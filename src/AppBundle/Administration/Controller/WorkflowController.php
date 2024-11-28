<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/04/2016
 * Time: 15:34
 */

namespace AppBundle\Administration\Controller;

use AppBundle\Administration\Entity\Action;
use AppBundle\Administration\Entity\Procedure;
use AppBundle\Administration\Entity\ProcedureInstance;
use AppBundle\Administration\Entity\ProcedureStep;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class WorkflowController
 *
 * @Route("/workflow")
 */
class WorkflowController extends Controller
{
    /**
     * @param Procedure $procedure
     *
     * @return Response
     *
     * @Route("/start/{procedure}",name="start_workflow")
     */
    public function startWorkflowAction(Procedure $procedure)
    {

        $adminClosingAction = $this->getDoctrine()->getRepository(Action::class)
            ->findOneBy(array('name' => 'administrative_closing'));


        $closureDate = $this->get('administrative.closing.service')
            ->getCurrentClosingDate();

        $fiscalDate = $this->get('administrative.closing.service')
            ->getLastWorkingEndDate()->setTime(0, 0, 0);

        $predicate = function ($key, ProcedureStep $step) use (
            $adminClosingAction
        ) {

            return $step->getAction()->getId() == $adminClosingAction->getId();
        };


        if ($closureDate >= $fiscalDate
            && $procedure->getSteps()->exists(
                $predicate
            )
        ) {

            $this->addFlash(
                'warning',
                $this->get('translator')->trans(
                    'procedure.cannot_execute_opening'
                )
            );

            return $this->redirectToRoute('index');
        }


        return $this->get('workflow.service')->startWorkflow($procedure);
    }

    /**
     * @param  $targetRouteName = null
     * @param  $outRouteOff     = null
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/next/{targetRouteName}/{outRouteOff}",name="next_in_workflow",options={"expose"=true})
     */
    public function nextAction($targetRouteName = null, $outRouteOff = null)
    {
        if ($targetRouteName) {
            $targetRedirect = new RedirectResponse(
                $this->generateUrl($targetRouteName)
            );
        } else {
            $targetRedirect = null;
        }

        return $this->get('workflow.service')->nextStep(
            $targetRedirect,
            $outRouteOff
        );
    }

    /**
     * @return JsonResponse
     *
     * @Route("/in_workflow",name="in_workflow",options={"expose"=true})
     */
    public function inWorkflow()
    {

        return new JsonResponse($this->get('workflow.service')->inWorkflow());
    }

    /**
     * @return JsonResponse
     *
     * @Route("/in_administrative_closing",name="in_administrative_closing",options={"expose"=true})
     */
    public function inAdministrativeClosing()
    {

        return new JsonResponse(
            $this->get('workflow.service')->inAdministrativeClosing()
        );
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/abandon",name="abandon_workflow")
     */
    public function abandonAction(Request $request)
    {

        $this->get('workflow.service')->endWorkflow();

        $url = $this->generateUrl('index');
        if ($request->query->has('url')) {
            $url = $request->query->get('url');
        }

        return $this->redirect($url);
    }

    /**
     * @param ProcedureInstance $procedure = null
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/go_to_current_step/{procedure}",name="go_to_current_step")
     */
    public function goToCurrentStep(ProcedureInstance $procedure = null)
    {
        if (is_null($procedure)) {
            return $this->get('workflow.service')->goToCurrentStep();
        }

        return $this->get('workflow.service')->goToCurrentStep2($procedure);
    }
}
