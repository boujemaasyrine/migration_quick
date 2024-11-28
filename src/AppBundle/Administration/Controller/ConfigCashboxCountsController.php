<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 11/02/2016
 * Time: 09:43
 */

namespace AppBundle\Administration\Controller;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Administration\Entity\Translation\ParameterTranslation;
use AppBundle\Administration\Form\Cashbox\CashboxParameterType;
use AppBundle\Administration\Form\Cashbox\LabelsType;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\SupplierPlanning;
use AppBundle\Merchandise\Entity\ProductCategories;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use AppBundle\Administration\Form\Search\InventoryItemSearchType;
use AppBundle\Administration\Form\Search\SupplierSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Administration\Form\Supplier\SupplierPlanningType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ConfigController
 *
 * @Route("/administration")
 */
class ConfigCashboxCountsController extends Controller
{

    /**
     * @param Request $request
     *
     * @RightAnnotation("cahsbox_parameter")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/cahsbox_parameter",name="cahsbox_parameter",options={"expose"=true})
     *
     * @Method({"GET","POST"})
     */
    public function cashBoxParameterAction(Request $request)
    {
        $data = $this->get('paremeter.service')->loadCashboxParameters();

        $form = $this->createForm(CashboxParameterType::Class, $data);

        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->get('paremeter.service')->updateCashboxParameter($form->getData());
            } else {
                $message = $this->get('translator')->trans('cashbox.error_form');
                $this->get('session')->getFlashBag()->add('error', $message);
            }
        }

        return $this->render(
            "@Administration/Cashbox/cashbox.html.twig",
            array(
                'paymentMethodStatus' => $this->get('payment_method.status.service'),
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @RightAnnotation("labels_config")
     *
     * @param Request $request
     * @param $type
     * @param  $label
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/labelsConfig/{type}/{label}",name="labels_config",options={"expose"=true})
     * @Method({"GET","POST"})
     */
    public function expenceAndRecipeLabelsAction(Request $request, $type, $label = null)
    {
        $label = $this->getDoctrine()->getRepository('Administration:Parameter')->findParameterById(
            $label,
            $this->getParameter('fallback_locale')
        );

        $action = 'edit';
        if ( null == $label ) {
            $label = new Parameter();
            $action = 'add';
        }
        if ($label->getTranslations()->count() == 0) {
            $label->addLabelTranslation('fr', '');
            $label->addLabelTranslation('nl', '');
        }
        if (Parameter::EXPENSE === $type) {
            $parameter = Parameter::EXPENSE_LABELS_TYPE;
        } else {
            if (Parameter::RECIPE === $type) {
                $parameter = Parameter::RECIPE_LABELS_TYPE;
            } else {
                throw new NotFoundHttpException();
            }
        }

        $form = $this->createForm(LabelsType::Class, $label);
        $labels = $this->getDoctrine()->getRepository('Administration:Parameter')->findParameterByType(
            $parameter,
            $this->getParameter('fallback_locale')
        );
        if ("POST" === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->get('paremeter.service')->addLabelParameter(
                    $label,
                    $parameter,
                    count($labels),
                    $this->getParameter('fallback_locale')
                );
                $message = $this->get('translator')->trans('cashbox.labels.'.$action.'_success');
                $this->get('session')->getFlashBag()->add('success', $message);

                return $this->redirectToRoute('labels_config', array('type' => $type));
            }
            $form->addError(new FormError($this->get('translator')->trans('form.error')));
        }

        return $this->render(
            "@Administration/Cashbox/labels.html.twig",
            array(
                'form' => $form->createView(),
                'labels' => $labels,
                'type' => $type,
            )
        );
    }

    /**
     * @param Parameter $label
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/deleteLabel/{label}",name="delete_label",options={"expose"=true})
     */
    public function deleteLabelAction(Parameter $label)
    {
        $response = new JsonResponse();
        try {
            $deleted = $this->get('paremeter.service')->deleteLabel($label);
            if ($deleted) {
                $message = $this->get('translator')->trans('cashbox.labels.delete_success');
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
                        $this->get('translator')->trans('Error.general.internal'),
                        $e->getLine()." : ".$e->getTraceAsString(),
                    ],
                ]
            );
        }

        return $response;
    }
}
