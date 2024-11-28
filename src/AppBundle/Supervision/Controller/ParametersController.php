<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 01/06/2016
 * Time: 20:22
 */

namespace AppBundle\Supervision\Controller;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Supervision\Form\Parameters\LabelsType;
use AppBundle\Supervision\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class ParametersController extends Controller
{

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/labelsConfig/{type}/{label}",name="labels_config",options={"expose"=true})
     */
    public function ExpenseAndRecipeLabelsAction(Request $request, $type, $label = null)
    {
        $label = $this->getDoctrine()->getRepository(Parameter::class)->findParameterById(
            $label,
            $this->getParameter('fallback_locale')
        );

        $action = 'modify';
        if ($label == null) {
            $label = new Parameter();
            $action = 'add';
        }
        if ($label->getTranslations()->count() == 0) {
            $label->addLabelTranslation('fr', '');
            $label->addLabelTranslation('nl', '');
        }
        if ($type == Parameter::EXPENSE) {
            $this->get('app.security.checker')->checkOrThrowAccedDenied('labels_config_expense');
            $parameter = Parameter::EXPENSE_LABELS_TYPE;
        } else {
            if ($type == Parameter::RECIPE) {
                $this->get('app.security.checker')->checkOrThrowAccedDenied('labels_config_recipe');
                $parameter = Parameter::RECIPE_LABELS_TYPE;
            } else {
                throw new NotFoundHttpException();
            }
        }
        $formError = false;

        $form = $this->createForm(LabelsType::Class, $label);
        $labels = $this->get('parameter.service')->getActiveLabelsByType($parameter);
        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->get('parameter.service')->addLabelParameter(
                    $label,
                    $parameter,
                    count($labels),
                    $this->getParameter('fallback_locale')
                );
                $message = $this->get('translator')->trans(
                    'parameters.financial_management.'.$action.'_success',
                    [],
                    "supervision"
                );
                $this->get('session')->getFlashBag()->add('success', $message);

                return $this->redirectToRoute('labels_config', array('type' => $type));
            } else {
                $formError = true;
            }
            $form->addError(new FormError($this->get('translator')->trans('form.error', [], "supervision")));
        }

        return $this->render(
            "@Supervision/Administration/labels.html.twig",
            array(
                'form' => $form->createView(),
                'labels' => $labels,
                'type' => $type,
                'action' => $action,
                'formError' => $formError,
            )
        );
    }

    /**
     * @param Parameter $label
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/deleteLabel/{label}",name="delete_label",options={"expose"=true})
     */
    public function deleteLabelAction(Parameter $label)
    {
        $response = new JsonResponse();
        try {
            $deleted = $this->get('parameter.service')->deleteLabel($label);
            if ($deleted) {
                $message = $this->get('translator')->trans(
                    'parameters.financial_management.delete_success',
                    [],
                    "supervision"
                );
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
     * @param Request $request
     * @param $type
     * @return JsonResponse
     * @Route("/labels_list_export/{type}",name="labels_list_export", options={"expose"=true})
     */
    public function labelsListExportAction(Request $request, $type)
    {
        if ($type == Parameter::EXPENSE) {
            $parameter = Parameter::EXPENSE_LABELS_TYPE;
        } else {
            if ($type == Parameter::RECIPE) {
                $parameter = Parameter::RECIPE_LABELS_TYPE;
            }
        }
        $orders = array('label');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);

        $dataTableHeaders['criteria']['labelsSearch[keyword'] = $request->request->get('search')['value'];

        $fileName = $this->get('translator')->trans($type.'_label', [], "supervision").date('dmY_His');

        $response = $this->get('toolbox.document.generator')
            ->generateXlsFile(
                'parameter.service',
                'getLabels',
                array(
                    'criteria' => $dataTableHeaders['criteria'],
                    'order' => $dataTableHeaders['orderBy'],
                    'type' => $parameter,
                ),
                [
                    $this->get('translator')->trans('label.reference'),
                    $this->get('translator')->trans('keyword.label').' FR',
                    $this->get('translator')->trans('keyword.label').' NL',
                ],
                function ($line) {
                    return [
                        $line['Ref'],
                        $line['nameFr'],
                        $line['nameNl'],
                    ];
                },
                $fileName
            );

        return $response;
    }
}
