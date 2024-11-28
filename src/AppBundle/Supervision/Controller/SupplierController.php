<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 07/03/2016
 * Time: 12:38
 */

namespace AppBundle\Supervision\Controller;

use AppBundle\Supervision\Form\Supplier\SupplierSearchType;
use AppBundle\Supervision\Form\Supplier\SupplierType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use AppBundle\Validator\UniqueCodeSupplierConstraint;
use AppBundle\Validator\UniqueCodeSupplierConstraintValidator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Merchandise\Entity\Supplier;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class SupplierController extends Controller
{

    /**
     * @RightAnnotation("suppliers_list")
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/suppliers_list/{supplier}",name="supervision_suppliers_list",options={"expose"=true})
     * @Method({"GET","POST"})
     */
    public function suppliersListAction(Request $request, Supplier $supplier = null)
    {
        if ($supplier == null) {
            $supplier = new Supplier();
        }

        $form = $this->createForm(SupplierType::Class, $supplier);

        $searchForm = $this->createForm(SupplierSearchType::Class);

        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $type = ($supplier->getId() != null) ? 'edit' : 'plus';
            if ($request->getMethod() === "POST") {
                try {
                    $form->handleRequest($request);

                    if ($form->isValid()) {
                        $this->get('supplier.service')->saveSupplier($supplier);
                        $Newform = $this->createForm(SupplierType::Class, $newSupplier = new Supplier());
                        $response->setData(
                            [
                                "data" => [
                                    $this->renderView(
                                        '@Supervision/modals/details_list_supplier.html.twig',
                                        array(
                                            'supplier' => $supplier,
                                        )
                                    ),
                                    [
                                        "id" => $supplier->getId(),
                                        "code" => $supplier->getCode(),
                                        "name" => $supplier->getName(),
                                        "designation" => $supplier->getDesignation(),
                                        "address" => $supplier->getAddress(),
                                        "phone" => $supplier->getPhone(),
                                        "btn" => $this->renderView(
                                            '@Supervision/parts/btn_action_template.html.twig',
                                            array(
                                                'id' => $supplier->getId(),
                                            )
                                        ),
                                    ],
                                    $this->renderView(
                                        '@Supervision/parts/form_add_edit.html.twig',
                                        array(
                                            'form' => $Newform->createView(),
                                            'type' => 'plus',
                                        )
                                    ),
                                ],
                            ]
                        );
                    } else {
                        $response->setData(
                            [
                                "formError" => [
                                    $this->renderView(
                                        '@Supervision/parts/form_add_edit.html.twig',
                                        array(
                                            'form' => $form->createView(),
                                            'type' => $type,
                                        )
                                    ),
                                ],
                            ]
                        );
                    }
                } catch (\Exception $e) {
                    $response->setData(
                        [
                            "errors" => [
                                $this->get('translator')->trans('Error.general.internal', [], "supervision"),
                                $e->getMessage(),
                            ],
                        ]
                    );
                }

                return $response;
            } else {
                $response->setData(
                    [
                        "data" => [
                            $this->renderView(
                                '@Supervision/parts/form_add_edit.html.twig',
                                array(
                                    'form' => $form->createView(),
                                    'type' => $type,
                                )
                            ),
                        ],
                    ]
                );

                return $response;
            }
        }

        return $this->render(
            "@Supervision/suppliers_list.html.twig",
            array(
                'form' => $form->createView(),
                'searchForm' => $searchForm->createView(),
                'type' => 'plus',
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/delete_supplier/{supplier}",name="delete_supplier", options={"expose"=true})
     */
    public function deleteSupplierAction(Request $request, Supplier $supplier)
    {
        $session = $this->get('session');
        $form = $this->createFormBuilder(
            null,
            array('action' => $this->generateUrl('delete_supplier', array('supplier' => $supplier->getId())))
        )->getForm();
        $text_button = $this->get('translator')->trans('provider.list.delete', [], "supervision");
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $deleted = $this->get('supplier.service')->deleteSupplier($supplier);
                if ($deleted) {
                    $session->getFlashBag()->set('success', 'provider.list.delete_success');
                } else {
                    $session->getFlashBag()->set('error', 'provider.list.delete_fails');
                }
            }

            return $this->redirectToRoute("suppliers_list");
        }

        return new JsonResponse(
            array(
                'data' => true,
                'html' => $this->renderView(
                    '@Supervision/parts/delete.html.twig',
                    array(
                        'form' => $form->createView(),
                        'text_button' => $text_button,
                    )
                ),
            )
        );
    }

    /**
     * @param Supplier $supplier
     * @return JsonResponse
     * @Route("/json/supplier_detail/{supplier}",name="supplier_detail",options={"expose"=true})
     */
    public function supplierItemDetailJsonAction(Supplier $supplier)
    {

        return new JsonResponse(
            array(
                'data' => $this->renderView(
                    "@Supervision/modals/details_list_supplier.html.twig",
                    array(
                        'supplier' => $supplier,
                    )
                ),
            )
        );
    }
}
