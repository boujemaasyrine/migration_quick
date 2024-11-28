<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 29/04/2016
 * Time: 09:26
 */

namespace AppBundle\Administration\Controller;

use AppBundle\Administration\Entity\Action;
use AppBundle\Administration\Entity\Procedure;
use AppBundle\Administration\Entity\ProcedureInstance;
use AppBundle\Administration\Entity\ProcedureStep;
use AppBundle\Administration\Form\Procedure\ProcedureType;
use AppBundle\Security\RightAnnotation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ProcedureController
 */
class ProcedureController extends Controller
{

    /**
     * @param Request $request
     * @param Procedure $procedure
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/index_workflows/{procedure}",name="index_workflows")
     * @RightAnnotation("index_workflows")
     */
    public function indexWorkflowsAction(Request $request, Procedure $procedure = null)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();

        //Modification d'une procédure
        if ($procedure) {
            //Test if the procedure is opened by a user
            $pendingInstance = $this->getDoctrine()->getRepository(ProcedureInstance::class)
                ->findBy(['status' => ProcedureInstance::PENDING, 'procedure' => $procedure]);

            if (count($pendingInstance) > 0) {
                $this->addFlash('error', 'you_cannot_modify_a_pending_procedure');

                return $this->redirectToRoute("index_workflows");
            }
        }

        if (!$procedure) {
            $procedure = new Procedure();
            $procedure->setAtSameTime(false);
            $procedure->setOriginRestaurant($restaurant);
            $new = true;
            $actions = [];
            $notDeletableActions = [];
        } else {
            $new = false;
            $actions = $this->getActionsFromProcedureStep($procedure->getSteps()->toArray());
            $notDeletableActions = $this->getNotDeletableAction($procedure->getSteps()->toArray());
        }

        $form = $this->createForm(
            ProcedureType::class,
            $procedure,
            array(
                'actions' => $actions,
                'not_deletable_actions' => $notDeletableActions,
            )
        );

        if ('POST' === $request->getMethod()) {
            $this->get('app.security.checker')->checkOrThrowAccedDenied('create_workflow');
            $form->handleRequest($request);
            if ($form->isValid()) {
                $steps = $this
                    ->createProcedureStepsFromAction($form->get('actions')->getData()->toArray(), $notDeletableActions);

                foreach ($procedure->getSteps() as $s) {
                    $this->getDoctrine()->getManager()->remove($s);
                }
                $this->getDoctrine()->getManager()->flush();

                $newProcedure = clone $procedure;
                $this->getDoctrine()->getManager()->remove($procedure);
                $this->getDoctrine()->getManager()->persist($newProcedure);

                foreach ($steps as $s) {
                    $this->getDoctrine()->getManager()->persist($s);
                    $newProcedure->addStep($s);
                }
                $this->getDoctrine()->getManager()->flush();

                if ($new) {
                    $this->get('session')->getFlashBag()->add('success', 'workflow_added_with_success');
                } else {
                    $this->get('session')->getFlashBag()->add('success', 'workflow_edit_with_success');
                }

                return $this->redirectToRoute('index_workflows');
            } else {//Form not valid
            }
        } else {
            $form->get('actions')->setData($actions);
        }

        return $this->render("@Administration/procedure/index_workflows.html.twig", array(
                'form' => $form->createView(),
                'new' => $new,
                'procedures' => $this->getDoctrine()->getRepository(Procedure::class)->findBy(
                    array('originRestaurant' => $restaurant)
                ),
              ));
    }

    /**
     * @param Procedure|null $procedure
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/delete/{procedure}",name="delete_procedure")
     * @RightAnnotation("delete_procedure")
     */
    public function deleteProcedure(Procedure $procedure = null)
    {
        if ($procedure) {
            $this->getDoctrine()->getManager()->remove($procedure);
            $this->getDoctrine()->getManager()->flush();
            $this->get('session')->getFlashBag()->add('success', 'workflow_deleted_with_success');
        }

        return $this->redirectToRoute('index_workflows');
    }


    /**
     *
     * * ** HELPERS ****
     */

    /**
     * @param Action[] $notDeletableActions
     * @param Action[] $actions
     *
     * @return ProcedureStep[]
     */
    private function createProcedureStepsFromAction($actions, $notDeletableActions = [])
    {

        foreach ($notDeletableActions as $a) {
            if (!in_array($a, $actions)) {
                $actions[] = $a;
            }
        }

        $steps = [];
        foreach ($actions as $key => $a) {
            $step = new ProcedureStep();
            $step->setOrder($key + 1)
                ->setAction($a);

            if (in_array($a, $notDeletableActions)) {
                $step->setDeletable(false);
            } else {
                $step->setDeletable(true);
            }

            $steps[] = clone $step;
        }

        return $steps;
    }

    /**
     * @param ProcedureStep[] $steps
     *
     * @return Action[]
     */
    private function getActionsFromProcedureStep($steps)
    {

        usort(
            $steps,
            function (ProcedureStep $s1, ProcedureStep $s2) {
                return $s1->getOrder() - $s2->getOrder();
            }
        );

        $actions = [];
        foreach ($steps as $s) {
            $actions[] = $s->getAction();
        }

        return $actions;
    }

    /**
     * @param ProcedureStep[] $steps
     *
     * @return Action[]
     */
    private function getNotDeletableAction($steps)
    {

        $actions = [];
        foreach ($steps as $s) {
            if (false === $s->getDeletable()) {
                $actions[] = $s->getAction();
            }
        }

        return $actions;
    }
}
