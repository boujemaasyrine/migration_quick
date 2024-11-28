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
use AppBundle\Merchandise\Form\InventorySheet\InventorySheetType;
use AppBundle\Security\Exception\NotAllowedException;
use AppBundle\Security\RightAnnotation;
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
 * Class InventoryAPIController
 *
 * @package                  AppBundle\Merchandise\Controller
 * @Route("/json/inventory")
 */
class InventoryAPIController extends Controller
{

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/inventorySheet",name="api_inventory_sheet", options={"expose"=true})
     * @Method({"GET"})
     */
    public function inventorySheetAction(Request $request)
    {
        $response = null;
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $draw = $request->get('draw', 0);
            $length = $request->get('length', $this->getParameter('number_of_rows_per_page'));
            $start = $request->get('start', 0);
            $search = $request->get('search', null);
            if (!is_null($search)) {
                $search = $search['value'];
            }
            $order = $request->get('order', null);
            try {
                $serviceResponse = $this->get('inventory.service')->getInventorySheets(
                    $search,
                    $order,
                    $length,
                    $start
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
     * @RightAnnotation("api_save_inventory_sheet")
     * @param Request $request
     * @param InventorySheet|null $inventorySheet
     * @return JsonResponse
     * @throws \Exception
     * @Route("/saveInventorySheet/{inventorySheet}", name="api_save_inventory_sheet", options={"expose"=true})
     * @Method({"GET","POST"})
     */
    public function saveInventorySheetAction(Request $request, InventorySheet $inventorySheet = null)
    {
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            try {
                $data = [];
                if (is_null($inventorySheet)) {
                    $inventorySheet = new InventorySheet();
                }
                $inventorySheetForm = $this->createForm(
                    new InventorySheetType(),
                    $inventorySheet
                );
                if ($request->getMethod() === "POST") {
                    $inventorySheetForm->handleRequest($request);
                    if ($inventorySheetForm->isValid()) {
                        $this->get('inventory.service')->saveInventorySheet($inventorySheet);
                        $inventorySheet = new InventorySheet();
                        $inventorySheetForm = $this->createForm(
                            new InventorySheetType(),
                            $inventorySheet
                        );
                    } else {
                        $data["errors"] = [

                        ];
                    }
                } elseif ($request->getMethod() === "GET") {
                }

                $data["data"] = [
                    $this->renderView(
                        '@Merchandise/Inventory/Sheets/parts/form_inventory_sheet.html.twig',
                        [
                            'inventorySheetForm' => $inventorySheetForm->createView(),
                        ]
                    ),
                    $inventorySheet->getId(),
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
            throw new AccessDeniedHttpException("This method accept only ajax calls.");
        }

        return $response;
    }
}
