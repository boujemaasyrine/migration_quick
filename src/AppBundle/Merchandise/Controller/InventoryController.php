<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/12/2015
 * Time: 10:54
 */

namespace AppBundle\Merchandise\Controller;

use AppBundle\General\Exception\OperationCannotBeDoneException;
use AppBundle\Merchandise\Entity\InventoryLine;
use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Form\InventorySearchType;
use AppBundle\Merchandise\Form\InventorySheet\InventorySheetType;
use AppBundle\Security\RightAnnotation;
use AppBundle\ToolBox\Utils\Utilities;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class InventoryController
 *
 * @package            AppBundle\Merchandise\Controller
 * @Route("inventory")
 */
class InventoryController extends Controller
{

    /**
     * @RightAnnotation("inventory_sheet")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/inventorySheet",name="inventory_sheet", options={"expose"=true})
     * @Method({"GET"})
     */
    public function inventorySheetAction(Request $request)
    {
        $response = null;
        if (!$request->isXmlHttpRequest()) {
            $response = $this->render(
                "@Merchandise/Inventory/Sheets/sheets.html.twig",
                [
                    "type" => SheetModel::INVENTORY_MODEL,
                ]
            );
        }

        return $response;
    }

    /**
     * @RightAnnotation("inventory_entry")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/entry/{inventorySheet}",name="inventory_entry", options={"expose"=true})
     */
    public function inventoryEntryAction(Request $request, InventorySheet $inventorySheet = null)
    {
        $session = $this->get('session');
        //getting Current Restaurant from service
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $response = null;
        if (is_null($inventorySheet)) {
            $inventorySheet = new InventorySheet();
            $inventorySheet->setFiscalDate(new \DateTime('yesterday'));
        }

        $inventorySheetForm = $this->createForm(
            new InventorySheetType(),
            $inventorySheet,
            array('restaurant' => $restaurant)
        );

        if ($request->isXmlHttpRequest()) {
            try {
                $response = new JsonResponse();
                // return inventory sheet block
                if ($request->getMethod() === "GET") {
                    $data = [
                        "data" => [
                            $this->renderView(
                                '@Merchandise/Inventory/parts/form_inventory_sheet.html.twig',
                                ["inventorySheetForm" => $inventorySheetForm->createView()]
                            ),
                        ],
                    ];
                } else {
                    // save inventory sheet
                    $inventorySheetForm->handleRequest($request);
                    if ($inventorySheetForm->isValid()) {
                        $validated = $request->get('validated') == 'true' ? true : false;
                        $this->get('inventory.service')->saveInventorySheet($restaurant, $inventorySheet);
                        $this->get('inventory.service')->UpdateMFCforInventory($restaurant, $inventorySheet);
                    } else {
                        $data["errors"] = [];
                    }

                    $data = [
                        "data" => [
                            $this->renderView(
                                '@Merchandise/Inventory/parts/form_inventory_sheet.html.twig',
                                ["inventorySheetForm" => $inventorySheetForm->createView()]
                            ),
                        ],
                    ];
                }
            } catch (OperationCannotBeDoneException $e) {
                $data = [
                    "data" => [
                        $this->renderView(
                            '@Merchandise/Inventory/parts/form_inventory_sheet.html.twig',
                            ["inventorySheetForm" => $inventorySheetForm->createView()]
                        ),
                    ],
                    "errors" => [
                        $e->getMessage(),
                    ],
                ];
            } catch (\Exception $e) {
                $data = [
                    "errors" => [
                        $this->get('translator')->trans('Error.general.internal'),
                        $e->getLine()." : ".$e->getMessage(),
                    ],
                ];
            }
            $response->setData($data);
        } else {
            try {
                if ($request->getMethod() === "GET") {
                    $response =
                        $this->render(
                            '@Merchandise/Inventory/inventory_entry.html.twig',
                            ["inventorySheetForm" => $inventorySheetForm->createView()]
                        );
                } else {
                    // save inventory sheet
                    $inventorySheetForm->handleRequest($request);
                    if ($inventorySheetForm->isValid()) {
                        $this->get('inventory.service')->saveInventorySheet($restaurant, $inventorySheet);
                        $this->get('inventory.service')->UpdateMFCforInventory($restaurant, $inventorySheet);
                        $session->getFlashBag()->add('success', 'inventory.entered_with_success');

                        return $this->get('workflow.service')->nextStep($this->redirectToRoute("inventory_list"));
                    } else {
                        $session->getFlashBag()->add('error', 'inventory.entered_with_error');

                        return $this->redirect($this->generateUrl('inventory_entry'));
                        //                        $response =
                        //                            $this->render('@Merchandise/Inventory/inventory_entry.html.twig',
                        //                                ["inventorySheetForm" => $inventorySheetForm->createView()]);
                    }
                }
            } catch (OperationCannotBeDoneException $e) {
                $response =
                    $this->render(
                        '@Merchandise/Inventory/inventory_entry.html.twig',
                        ["inventorySheetForm" => $inventorySheetForm->createView()]
                    );
                $session->getFlashBag()->add('error', $this->get('translator')->trans('Error.general.internal'));
            }
        }

        return $response;
    }

    /**
     * @RightAnnotation("inventory_entry")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/entry_load",name="load_inventory_entry", options={"expose"=true})
     */
    public function loadInventorySheetAction(Request $request)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $response = null;
        if ($request->isXmlHttpRequest()) {
            try {
                $response = new JsonResponse();
                $inventorySheet = new InventorySheet();
                $data = [];
                if ($request->getMethod() === "POST") {
                    // return inventory sheet block
                    $inventorySheetForm = $this->createForm(
                        InventorySheetType::class,
                        $inventorySheet,
                        array('restaurant' => $restaurant)
                    );
                    $inventorySheetForm->handleRequest($request);

                    $inventorySheet = $this->get('inventory.service')->loadInventorySheet($inventorySheet);
                    $lines = $inventorySheet->getLines();
                    $inventorySheet->setLines(null);
                    $inventorySheetForm = $this->createForm(
                        InventorySheetType::class,
                        $inventorySheet,
                        array('restaurant' => $restaurant)
                    );

                    $encoder = new JsonEncoder();
                    $normalizer = new ObjectNormalizer();
                    $normalizer->setCircularReferenceHandler(
                        function ($object) {
                            return $object->getId();
                        }
                    );
                    $serializer = new Serializer(array($normalizer), array($encoder));

                    $data = [
                        "data" => [
                            'lines' => json_decode($serializer->serialize($lines, 'json')),
                            $this->renderView(
                                '@Merchandise/Inventory/parts/form_inventory_sheet.html.twig',
                                ["inventorySheetForm" => $inventorySheetForm->createView()]
                            ),
                        ],
                    ];
                }
            } catch (\Exception $e) {
                $data = [
                    "errors" => [
                        $this->get('translator')->trans('Error.general.internal'),
                        $e->getLine()." : ".$e->getMessage(),
                    ],
                ];
            }
            $response->setData($data);
        }

        return $response;
    }

    /**
     * @RightAnnotation("inventory_entry")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/sheetDetails",name="inventory_sheet_details", options={"expose"=true})
     */
    public function detailsInventorySheetAction(Request $request)
    {
        $response = null;
        if ($request->isXmlHttpRequest()) {
            $data = [];
            try {
                if ($request->getMethod() === "GET") {
                    $response = new JsonResponse();
                    $inventorySheetId = $request->get('inventorySheet', null);
                    $inventorySheet = $this->getDoctrine()->getManager()->getRepository('Merchandise:InventorySheet')
                        ->createQueryBuilder('inventorySheet')
                        ->select(['inventorySheet', 'lines'])
                        ->leftJoin('inventorySheet.lines', 'lines')
                        ->where('inventorySheet.id = :inventorySheetId')->setParameter(
                            'inventorySheetId',
                            $inventorySheetId
                        )
                        ->getQuery()->getResult();
                    $inventorySheet = $inventorySheet[0];
                    $inventorySheetForm = $this->createForm(
                        InventorySheetType::class,
                        $inventorySheet
                    );
                    $data = [
                        "data" => [
                            $this->renderView(
                                '@Merchandise/Inventory/parts/consult_inventory_sheet.html.twig',
                                ["inventorySheetForm" => $inventorySheetForm->createView()]
                            ),
                        ],
                    ];
                }
            } catch (\Exception $e) {
                $data = [
                    "errors" => [
                        $this->get('translator')->trans('Error.general.internal'),
                        $e->getLine()." : ".$e->getMessage(),
                    ],
                ];
            }
            $response->setData($data);

            return $response;
        }
    }

    /**
     * @RightAnnotation("inventory_list")
     * @Route("/list",name="inventory_list", options={"expose"=true})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */

    public function inventoryListAction(Request $request)
    {
        $restaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $form = $this->createForm(InventorySearchType::class, [
            "startDate" => new \DateTime(),
            "endDate" => new \DateTime(),
        ]);
        $response = new Response();

        if ($request->isXmlHttpRequest()) {
            if ($request->getMethod() === "GET") {
                $response = new JsonResponse();
                $draw = $request->get('draw', 0);
                $length = $request->get('length', $this->getParameter('number_of_rows_per_page'));
                $start = $request->get('start', 0);
                $search = $request->get('search', null);
                $criteria=$request->get('criteria',null);
                if (!is_null($search)) {
                    $search = strval($search['value']);
                }
                $order = $request->get('order', null);
                try {

                    $serviceResponse = $this->get('inventory.service')->getCreatedTodayInventories(
                        $restaurant,
                        $draw,
                        $search,
                        $criteria,
                        $order,
                        $start,
                        $length
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
        } else {
            if ($request->getMethod() === "GET") {
                $response = $this->render(
                    "@Merchandise/Inventory/inventory_management.html.twig",
                    ['form' => $form->createView()]
                );
            } else {
                $response = $this->render(
                    "@Merchandise/Inventory/inventory_management.html.twig",
                    ['form' => $form->createView()]
                );
            }
        }

        return $response;
    }

    /**
     * @Route("/consult",name="inventory_consult")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function consultStockAction(
        Request $request
    ) {

        return $this->render('@Merchandise/Inventory/consultation.html.twig');
    }

    /**
     * @Route("/gapCalculation",name="inventory_gap_calculation", options={"expose"=true})
     */
    public function calculateGapAction(
        Request $request
    ) {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(
                [
                    'errors' => [],
                    'data' => [$this->renderView('@Merchandise/Inventory/parts/gap.html.twig')],
                ]
            );
        }
    }

    /**
     * @RightAnnotation("inventory_list")
     * @Route("/export/{inventorySheet}", name="export_inventory", options={"expose"=true})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportInventoryAction(
        Request $request,
        InventorySheet $inventorySheet
    ) {
        $inventorySheetForm = $this->createForm(
            InventorySheetType::class,
            $inventorySheet
        );
        $filename = "inventory_sheet_".date('Y_m_d_H_i_s').".pdf";
        $filepath = $this->get('toolbox.pdf.generator.service')->generatePdfFromTwig(
            $filename,
            '@Merchandise/Inventory/exports/inventory_sheet_lines.html.twig',
            array(
                "inventorySheetForm" => $inventorySheetForm->createView(),
            )
        );

        return Utilities::createFileResponse($filepath, $filename);
    }
}
