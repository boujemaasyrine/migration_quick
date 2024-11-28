<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/12/2015
 * Time: 10:54
 */

namespace AppBundle\Merchandise\Controller;

use AppBundle\General\Exception\OperationCannotBeDoneException;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Form\InventorySheet\InventorySheetType;
use AppBundle\Merchandise\Form\ModelSheet\SheetModelType;
use AppBundle\Security\Exception\NotAllowedException;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * Class SheetModelAPIController
 *
 * @package                   AppBundle\Merchandise\Controller
 * @Route("/json/sheetModel")
 */
class SheetModelAPIController extends Controller
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/sheet",name="api_sheet", options={"expose"=true})
     * @Method({"GET"})
     */
    public function sheetModelAction(Request $request)
    {
        $response = null;
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $draw = $request->get('draw', 0);
            $length = $request->get('length', $this->getParameter('number_of_rows_per_page'));
            $start = $request->get('start', 0);
            $search = $request->get('search', null);
            $type = $request->get('type', null);
            if (!is_null($search)) {
                $search = $search['value'];
            }
            $order = $request->get('order', null);
            try {
                $serviceResponse = $this->get('sheet_model.service')->getSheets(
                    $search,
                    $order,
                    $length,
                    $start,
                    $type
                );
                $serviceResponse['draw'] = intval($draw);
                $response->setData(
                    json_decode($this->get('serializer')->serialize($serviceResponse, 'json'))
                );
            } catch (\Exception $e) {
                $response->setData(
                    [
                        "errors" => [
                            $this->get('translator')->trans('Error.general.internal'),
                            $e->getLine()." : ".$e->getMessage(),
                        ],
                    ]
                );
            }
        }
        return $response;
    }

    /**
     * @param Request $request
     * @return null|JsonResponse
     * @throws \Exception
     * @Route("/paginateOnGroup", name="api_paginate_on_group", options={"expose"=true})
     */
    public function paginateOnGroupAction(Request $request)
    {
        $response = null;
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $data = [];
            $currentOffset = $request->get('offset', 0);
            $limit = $request->get('limit', $this->getParameter('number_of_groups_per_page'));
            $type = $request->get('type');
            if (SheetModel::$cibledByType[$type] === SheetModel::ARTICLE) {
                $categories = $this->getDoctrine()->getRepository(
                    'Merchandise:Product'
                )->findAllProductsGroupedByCategory($currentOffset, $limit);
                $maxOffset = floor(
                    $this->getDoctrine()->getRepository('Merchandise:Product')->countFindAllProductsGroupedByCategory(
                    ) / intval($this->getParameter('number_of_groups_per_page'))
                );
                $data["data"] = [
                    $this->renderView(
                        '@Merchandise/Loss/Sheets/parts/category_list.html.twig',
                        [
                            'categories' => $categories,
                            'currentOffset' => $currentOffset,
                            'maxOffset' => $maxOffset,
                        ]
                    ),
                ];
            } elseif ($type && SheetModel::$cibledByType[$type] === SheetModel::FINALPRODUCT) {
                $categories = $this->getDoctrine()->getRepository(
                    'Merchandise:Division'
                )->findAllSoldProductsGroupedByDivision($currentOffset, $limit);
                $maxOffset = floor(
                    $this->getDoctrine()->getRepository(
                        'Merchandise:Division'
                    )->countFindAllSoldProductsGroupedByDivision() / intval(
                        $this->getParameter('number_of_groups_per_page')
                    )
                );
                $data["data"] = [
                    $this->renderView(
                        '@Merchandise/Loss/Sheets/parts/category_list.html.twig',
                        [
                            'categories' => $categories,
                            'currentOffset' => $currentOffset,
                            'maxOffset' => $maxOffset,
                        ]
                    ),
                ];
            }

            $response->setData($data);
        } else {
            throw new AccessDeniedHttpException("This method accept only ajax calls.");
        }

        return $response;
    }

    /**
     * @param Request         $request
     * @param SheetModel|null $sheetModel
     * @return JsonResponse
     * @throws \Exception
     * @Route("/saveLossSheet/{sheetModel}", name="api_save_loss_sheet_model", options={"expose"=true})
     * @Method({"GET","POST"})
     */
    public function saveSheetLossModelAction(Request $request, SheetModel $sheetModel = null)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        if ($request->isXmlHttpRequest()) {
            $type = $request->get('type');
            $response = new JsonResponse();
            try {
                $data = [];
                if (is_null($sheetModel)) {
                    $sheetModel = new SheetModel();
                }

                if ($sheetModel->getId()) {
                    if ($type == 'articles_loss_model') {
                        $this->get('app.security.checker')->checkOrThrowAccedDenied('add_loss_sheet_article');
                    } else {
                        $this->get('app.security.checker')->checkOrThrowAccedDenied('add_loss_sheet_pf');
                    }
                } else {
                    if ($type == 'articles_loss_model') {
                        $this->get('app.security.checker')->checkOrThrowAccedDenied('edit_loss_sheet_article');
                    } else {
                        $this->get('app.security.checker')->checkOrThrowAccedDenied('edit_loss_sheet_pf');
                    }
                }

                $sheetModelForm = $this->createForm(
                    SheetModelType::class,
                    $sheetModel
                );
                if ($request->getMethod() === "POST") {
                    $sheetModelForm->handleRequest($request);
                    if ($sheetModelForm->isValid()) {
                        $this->get('sheet_model.service')->saveSheetModel($sheetModel, $type);
                        $sheet = new SheetModel();
                        $sheetModelForm = $this->createForm(
                            SheetModelType::class,
                            $sheet
                        );
                    } else {
                        $data["errors"] = [

                        ];
                    }
                }

                if (SheetModel::$cibledByType[$type] === SheetModel::ARTICLE) {
                    $categories = $this->getDoctrine()->getRepository(
                        'Merchandise:Product'
                    )->findAllProductsGroupedByCategory($restaurant, null, null, true);
                    $data["data"] = [
                        $this->renderView(
                            '@Merchandise/Loss/Sheets/parts/form_sheet_model.html.twig',
                            [
                                'sheetModelForm' => $sheetModelForm->createView(),
                                'type' => $type,
                                'categories' => $categories,
                                'cibledProduct' => SheetModel::$cibledByType[$type],
                            ]
                        ),
                        $sheetModel->getId(),
                        $sheetModel->getLabel()
                    ];
                } elseif ($type && SheetModel::$cibledByType[$type] === SheetModel::FINALPRODUCT) {
                    $data["data"] = [
                        $this->renderView(
                            '@Merchandise/Loss/Sheets/parts/form_sheet_model.html.twig',
                            [
                                'sheetModelForm' => $sheetModelForm->createView(),
                                'type' => $type,
                                'cibledProduct' => SheetModel::$cibledByType[$type],
                            ]
                        ),
                        $sheetModel->getId(),
                        $sheetModel->getLabel()
                    ];
                }
                $response->setData($data);
            } catch (OperationCannotBeDoneException $e) {
                $response->setData(
                    [
                        "errors" => [
                            $e->getMessage(),
                        ],
                    ]
                );
            } catch (\Exception $e) {
                $response->setData(
                    [
                        "errors" => [
                            $this->get('translator')->trans('Error.general.internal'),
                            $e->getLine()." : ".$e->getMessage(),
                        ],
                    ]
                );
            }

            return $response;
        } else {
            if ($request->getMethod() === "POST") {
                $type = $request->get('type');
                if (is_null($sheetModel)) {
                    $sheetModel = new SheetModel();
                }
                if ($sheetModel->getId()) {
                    if ($type == 'articles_loss_model') {
                        $this->get('app.security.checker')->checkOrThrowAccedDenied('add_loss_sheet_article');
                    } else {
                        $this->get('app.security.checker')->checkOrThrowAccedDenied('add_loss_sheet_pf');
                    }
                } else {
                    if ($type == 'articles_loss_model') {
                        $this->get('app.security.checker')->checkOrThrowAccedDenied('edit_loss_sheet_article');
                    } else {
                        $this->get('app.security.checker')->checkOrThrowAccedDenied('edit_loss_sheet_pf');
                    }
                }
                $sheetModelForm = $this->createForm(
                    SheetModelType::class,
                    $sheetModel
                );
                $sheetModelForm->handleRequest($request);
                $download = $request->get('download', false);
                if ($sheetModelForm->isValid()) {
                    if ($download) {
                        $filename = "loss_sheet_".date('Y_m_d_H_i_s').".pdf";
                        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                            $filename,
                            '@Merchandise/SheetModel/exports/sheet_model.html.twig',
                            array(
                                "sheetModelForm" => $sheetModelForm->createView(),
                                "sheetModelType" => $type,
                            )
                        );

                        return Utilities::createFileResponse($filepath, $filename);
                    } else {
                        $this->get('sheet_model.service')->saveSheetModel($sheetModel, $type);
                        $this->get('session')->getFlashBag()->add(
                            'success',
                            $this->get('translator')->trans(
                                'sheet_model.notification.loss_edited_with_success',
                                [
                                    '%id%' => $sheetModel->getLabel(),
                                ]
                            )
                        );

                        return $this->redirectToRoute(
                            'loss_sheet',
                            [
                                'type' => $type,
                            ]
                        );
                    }
                }
            } else {
                throw new AccessDeniedHttpException("This method does not accept this method.");
            }

            if (SheetModel::$cibledByType[$type] === SheetModel::ARTICLE) {
                $categories = $this->getDoctrine()->getRepository(
                    'Merchandise:Product'
                )->findAllProductsGroupedByCategory();
                $response = $this->render(
                    "@Merchandise/Loss/Sheets/sheets_entry.twig",
                    [
                        'sheetModelForm' => $sheetModelForm->createView(),
                        'type' => $type,
                        'categories' => $categories,
                        'cibledProduct' => SheetModel::$cibledByType[$type],
                        'isNotAjax' => true,
                    ]
                );
            } elseif ($type && SheetModel::$cibledByType[$type] === SheetModel::FINALPRODUCT) {
                $response = $this->render(
                    "@Merchandise/Loss/Sheets/sheets_entry.twig",
                    [
                        'sheetModelForm' => $sheetModelForm->createView(),
                        'type' => $type,
                        'cibledProduct' => SheetModel::$cibledByType[$type],
                        'isNotAjax' => true,
                    ]
                );
            }
        }

        return $response;
    }

    /**
     * @param Request         $request
     * @param SheetModel|null $sheetModel
     * @return JsonResponse
     * @throws \Exception
     * @Route("/saveInventorySheet/{sheetModel}", name="api_save_inventory_sheet_model", options={"expose"=true})
     * @Method({"GET","POST"})
     */
    public function saveInventorySheetModelAction(Request $request, SheetModel $sheetModel = null)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        if (is_null($sheetModel)) {
            $sheetModel = new SheetModel();
        }
        $sheetModelForm = $this->createForm(
            SheetModelType::class,
            $sheetModel
        );
        $categories = $this->getDoctrine()->getRepository('Merchandise:Product')->findAllProductsGroupedByCategory(
            $restaurant,
            null,
            null,
            true
        );
        if ($sheetModel->getId()) {
            $this->get('app.security.checker')->checkOrThrowAccedDenied('edit_inventory_sheet_model');
        } else {
            $this->get('app.security.checker')->checkOrThrowAccedDenied('add_inventory_sheet_model');
        }
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            try {
                $data = [];
                if ($request->getMethod() === "POST") {
                    $sheetModelForm->handleRequest($request);
                    if ($sheetModelForm->isValid()) {
                        $this->get('sheet_model.service')->saveSheetModel($sheetModel, SheetModel::INVENTORY_MODEL);
                        $sheet = new SheetModel();
                        $sheetModelForm = $this->createForm(
                            SheetModelType::class,
                            $sheet
                        );
                    } else {
                        $data["errors"] = [

                        ];
                    }
                } elseif ($request->getMethod() === "GET") {
                }
                $data["data"] = [
                    $this->renderView(
                        '@Merchandise/Inventory/Sheets/parts/form_sheet_model.html.twig',
                        [
                            'sheetModelForm' => $sheetModelForm->createView(),
                            'sheetType' => SheetModel::INVENTORY_MODEL,
                            "categories" => $categories,
                        ]
                    ),
                    $sheetModel->getId(),
                ];

                $response->setData($data);
            } catch (OperationCannotBeDoneException $e) {
                $response->setData(
                    [
                        "errors" => [
                            $e->getMessage(),
                        ],
                    ]
                );
            } catch (\Exception $e) {
                $response->setData(
                    [
                        "errors" => [
                            $this->get('translator')->trans('Error.general.internal'),
                            $e->getLine()." : ".$e->getMessage(),
                        ],
                    ]
                );
            }
        } else {
            if ($request->getMethod() === "GET") {
                $response = $this->render(
                    "@Merchandise/Inventory/Sheets/sheets_entry.twig",
                    [
                        'sheetModelForm' => $sheetModelForm->createView(),
                        "categories" => $categories,
                        'sheetType' => SheetModel::INVENTORY_MODEL,
                    ]
                );
            } else {
                $sheetModelForm->handleRequest($request);
                $download = $request->get('download', false);
                if ($sheetModelForm->isValid()) {
                    if ($download) {
                        $filename = "inventory_sheet_".date('Y_m_d_H_i_s').".pdf";
                        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
                            $filename,
                            '@Merchandise/SheetModel/exports/sheet_model.html.twig',
                            array(
                                "sheetModelForm" => $sheetModelForm->createView(),
                                "sheetModelType" => SheetModel::INVENTORY_MODEL,
                            )
                        );

                        return Utilities::createFileResponse($filepath, $filename);
                    } else {
                        $this->get('sheet_model.service')->saveSheetModel($sheetModel, SheetModel::INVENTORY_MODEL);

                        $this->get('session')->getFlashBag()->add(
                            'success',
                            $this->get('translator')->trans(
                                'sheet_model.notification.edited_with_success',
                                [
                                    '%id%' => $sheetModel->getLabel(),
                                ]
                            )
                        );

                        return $this->get('workflow.service')->nextStep($this->redirectToRoute('inventory_sheet'));
                    }
                }
                $response = $this->render(
                    "@Merchandise/Inventory/Sheets/sheets_entry.twig",
                    [
                        'sheetModelForm' => $sheetModelForm->createView(),
                        "categories" => $categories,
                        'sheetType' => SheetModel::INVENTORY_MODEL,
                    ]
                );
            }
        }

        return $response;
    }

    /**
     * @param Request    $request
     * @param SheetModel $sheetModel
     * @Route("/delete_sheet_model/{sheetModel}", name="api_delete_sheet_model", options={"expose"=true})
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteSheetModelAction(Request $request, SheetModel $sheetModel)
    {
        if ($request->isXmlHttpRequest()) {
            try {
                $type = $sheetModel->getType();
                $this->get('sheet_model.service')->removeSheetModel($sheetModel);
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans(
                        'sheet_model.notification.sheet_model_deleted_with_success',
                        [
                            'label' => $sheetModel->getLabel(),
                        ]
                    )
                );
                $data = [];
                switch ($type) {
                    case SheetModel::INVENTORY_MODEL:
                        $this->get('app.security.checker')->checkOrThrowAccedDenied('delete_inventory_sheet_model');
                        $data = ["data" => ["redirect" => $this->get('router')->generate('inventory_sheet')]];
                        break;
                    case SheetModel::ARTICLES_LOSS_MODEL:
                        $this->get('app.security.checker')->checkOrThrowAccedDenied('delete_loss_sheet_article');
                        $data = [
                            "data" => [
                                "redirect" => $this->get('router')->generate(
                                    'loss_sheet',
                                    ['type' => SheetModel::ARTICLES_LOSS_MODEL]
                                ),
                            ],
                        ];
                        break;
                    case SheetModel::PRODUCT_SOLD_LOSS_MODEL:
                        $this->get('app.security.checker')->checkOrThrowAccedDenied('delete_loss_sheet_pf');
                        $data = [
                            "data" => [
                                "redirect" => $this->get('router')->generate(
                                    'loss_sheet',
                                    ['type' => SheetModel::PRODUCT_SOLD_LOSS_MODEL]
                                ),
                            ],
                        ];
                        break;
                }
            } catch (\Exception $e) {
                $this->get('logger')->addError('Deleting Sheet Model', $e->getTrace());
                $data =
                    [
                        "errors" => [
                            $this->get('translator')->trans('Error.general.internal'),
                            $e->getLine()." : ".$e->getMessage(),
                        ],
                    ];
            }

            return new JsonResponse($data);
        } else {
            throw new AccessDeniedHttpException("This method accept only ajax calls.");
        }
    }

    /**
     * @Route("/export/{sheetModel}", name="export_sheet_model", options={"expose"=true})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportSheetModelAction(
        Request $request,
        SheetModel $sheetModel
    ) {
        $sheetModelForm = $this->createForm(
            SheetModelType::class,
            $sheetModel
        );
        $filename = "sheet_model_".date('Y_m_d_H_i_s').".pdf";
        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
            $filename,
            '@Merchandise/SheetModel/exports/sheet_model.html.twig',
            array(
                "sheetModelForm" => $sheetModelForm->createView(),
                "sheetModelType" => $sheetModel->getType(),
            )
        );

        return Utilities::createFileResponse($filepath, $filename);
    }
}
