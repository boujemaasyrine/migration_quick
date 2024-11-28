<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 09/03/2016
 * Time: 11:30
 */

namespace AppBundle\Supervision\Controller;

use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\RecipeLine;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\Supervision\Entity\RecipeLineSupervision;
use AppBundle\Supervision\Form\Items\DeactivateProductForRestaurantType;
use AppBundle\Supervision\Form\Items\SubstituteInventoryItemType;
use AppBundle\Supervision\Service\ItemsService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Supervision\Form\Items\InventoryItemType;
use AppBundle\Supervision\Form\Items\InventoryItemSearchType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class InventoryItemController extends Controller
{

    /**
     * @RightAnnotation("inventory_item_list")
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/inventory_item_list/{productPurchased}",name="supervision_inventory_item_list",options={"expose"=true})
     * @Method({"GET","POST"})
     */
    public function ProductPurchasedListAction(Request $request, ProductPurchasedSupervision $productPurchased = null)
    {
        if ($productPurchased == null) {
            $productPurchased = new ProductPurchasedSupervision();
        } else {
            if ($productPurchased->getNameTranslation('nl') == null) {
                $productPurchased->addNameTranslation('nl', '');
            }
        }

        $form = $this->createForm(InventoryItemType::Class, $productPurchased);
        $formSearch = $this->createForm(InventoryItemSearchType::Class);
        $type = ($productPurchased->getId() != null) ? 'edit' : 'plus';
        $formError = false;

        if ($request->getMethod() === "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->get('items.service')->saveInventoryItem($productPurchased);
                if (!is_null($request->get('synchronize', null))) {
                    $this->get('sync.create.entry.service')->createProductPurchasedEntry($productPurchased, true);
                    $productPurchased->setLastDateSynchro(new \DateTime('now'));
                    $this->getDoctrine()->getManager()->flush();
                }
                $message = $this->get('translator')->trans(
                    'inventory-item-' . $type . '-success',
                    [
                        '%name%' => $productPurchased->getName(),
                    ],
                    'supervision'
                );
                $this->get('session')->getFlashBag()->add('success', $message);

                return $this->redirectToRoute('supervision_inventory_item_list');
            } else {
                $formError = true;
            }
        }

        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();

            if ($request->getMethod() === "GET") {
                $response->setData(
                    [
                        "data" => [
                            $this->renderView(
                                '@Supervision/parts/form_add_edit_inventory_item.html.twig',
                                array(
                                    'form' => $form->createView(),
                                    'type' => 'edit',
                                )
                            ),
                            $productPurchased->getPrimaryItem(),
                        ],
                    ]
                );

                return $response;
            }
        }

        return $this->render(
            '@Supervision/inventory_item_list.html.twig',
            array(
                'form' => $form->createView(),
                'formSearch' => $formSearch->createView(),
                'type' => $type,
                'formError' => $formError,
            )
        );
    }


    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/activate_inventory_item/{productPurchased}/{activate}",name="activate_inventory_item", options={"expose"=true})
     */
    public function activateProductPurshasedAction(Request $request, ProductPurchased $productPurchased, $activate)
    {

        $session = $this->get('session');
        $form = $this->createFormBuilder(
            null,
            array(
                'action' => $this->generateUrl(
                    'activate_inventory_item',
                    array(
                        'productPurchased' => $productPurchased->getId(),
                        'activate' => $activate,
                    )
                ),
            )
        )->getForm();

        $activateBool = ($activate == 'true') ? true : false;
        $text_button = ($activateBool) ? $this->get('translator')->trans('item.inventory.activate') :
            $this->get('translator')->trans('item.inventory.deactivate');
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $deleted = $this->get('items.service')->deleteInventoryItem($productPurchased, $activateBool);
                if ($deleted) {
                    $session->getFlashBag()->set('success', 'item.inventory.status_change_success');
                } else {
                    $session->getFlashBag()->set('error', 'item.inventory.status_change_fails');
                }
            }

            return $this->redirectToRoute("supervision_inventory_item_list");
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
     * @param Request $request
     * @param ProductPurchased $inventoryItem
     * @return JsonResponse
     * @Route("/json/inventory_item_detail/{inventoryItem}",name="supervision_inventory_item_detail",options={"expose"=true})
     */
    public function inventoryItemDetailJsonAction(Request $request, ProductPurchasedSupervision $inventoryItem)
    {
        return new JsonResponse(
            array(

                'data' => $this->renderView(
                    "@Supervision/modals/details_list_inventory_item.html.twig",
                    array(
                        'item' => $inventoryItem,
                    )
                ),
                'footerBtn' => $this->renderView(
                    "@Supervision/modals/footer_details_inventory_item.html.twig",
                    array(
                        'item' => $inventoryItem,
                    )
                ),
            )
        );
    }

    /**
     * @param Request $request
     * @param ProductPurchasedSupervision $inventoryItem
     * @return JsonResponse
     * @Route("/json/inventory_item_substitute/{inventoryItem}",name="inventory_item_substitute",options={"expose"=true})
     */
    public function inventoryItemSubstituteAction(Request $request, ProductPurchasedSupervision $inventoryItem)
    {
        $em = $this->getDoctrine()->getManager();
        $recipeLines = $em->getRepository(RecipeLineSupervision::class)->findBy(
            array('productPurchased' => $inventoryItem)
        );
        $productSolds = $em->getRepository(ProductSoldSupervision::class)->findBy(
            array('productPurchased' => $inventoryItem)
        );

        $form = $this->createForm(new SubstituteInventoryItemType($em), array('mainProduct' => $inventoryItem));
        $form->handleRequest($request);
        if ($form->isValid()) {
            $newProduct = $form->get('productPurchased')->getData();
            $syncCMDService = $this->get('sync.create.entry.service');

            foreach ($recipeLines as $recipeLine) {
                $recipeLine->setProductPurchased($newProduct);

                if ($recipeLine->getRecipe()->getProductSold()) {
                    $productSold = $recipeLine->getRecipe()->getProductSold();

                    $productSold->setDateSynchro($form->get('dateSynchro')->getData());
                    $syncCMDService->createProductSoldEntry($productSold);
                }
            }

            foreach ($productSolds as $productSold) {
                $productSold->setProductPurchased($newProduct);

                $productSold->setDateSynchro($form->get('dateSynchro')->getData());
                $syncCMDService->createProductSoldEntry($productSold);
            }

            $em->flush();

            return new JsonResponse(
                array(
                    'data' => $this->renderView("@Supervision/modals/substitute_inventory_item.html.twig", array()),
                )
            );
        }

        return new JsonResponse(
            array(
                'data' => $this->renderView(
                    "@Supervision/modals/substitute_inventory_item.html.twig",
                    array(
                        'item' => $inventoryItem,
                        'recipeLines' => $recipeLines,
                        'productSolds' => $productSolds,
                        'form' => $form->createView(),
                    )
                ),
                'footerBtn' => $this->renderView(
                    "@Supervision/modals/footer_substitute_inventory_item.html.twig",
                    array(
                        'item' => $inventoryItem,
                        'recipeLines' => $recipeLines,
                        'productSolds' => $productSolds,
                    )
                ),
            )
        );
    }

    /**
     * @param ProductPurchased $productPurchased
     * @param Request $request
     * @return JsonResponse
     * @Route("/json/force_synchronize_purchased_product/{productPurchased}",name="force_synchronize_purchased_product",options={"expose"=true})
     */
    public function forceSynchronizePurchasedProduct(
        Request $request,
        ProductPurchasedSupervision $productPurchased
    )
    {
        $response = null;
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $data = [];
            try {
                $this->get('sync.create.entry.service')->createProductPurchasedEntry($productPurchased, true);
                //Set date synchro � nulle lros du forcage
                $productPurchased->setDateSynchro(null);
                $this->getDoctrine()->getManager()->flush();
                $restaurants = [];
                foreach ($productPurchased->getRestaurants() as $restaurant) {
                    $restaurants[] = $restaurant->getName();
                }
                $restaurants = implode(',', $restaurants);
                $this->addFlash(
                    'success',
                    $this->get('translator')->trans(
                        'synchro_order_launched',
                        [
                            '%product%' => $productPurchased->getName(),
                            '%restaurants%' => $restaurants,
                        ],
                        "supervision"
                    )
                );
                $data = [
                    "data" => ["sucess" => "success"],
                ];
            } catch (\Exception $e) {
                $data = [
                    "errors" => [
                        $this->get('translator')->trans('Error.general.internal'),
                        $e->getLine() . " : " . $e->getTraceAsString(),
                    ],
                ];
            }
            $response->setData($data);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param ProductPurchasedSupervision $inventoryItem
     * @return JsonResponse
     * @Route("/json/inventory_item_deactivate/{inventoryItem}",name="supervision_inventory_item_deactivate",options={"expose"=true})
     */
    public function inventoryItemDeactivateJsonAction(Request $request, ProductPurchasedSupervision $inventoryItem)
    {
        /**
         * @var ItemsService $iteamService
         */
        $itemService = $this->get('items.service');
        $restaurants = $itemService->findRestaurantsWhichTheInventoryItemIsActive($inventoryItem);



        if (count($restaurants) > 0) {
            $form = $this->createForm(new DeactivateProductForRestaurantType($restaurants));
            $form->handleRequest($request);
            if ($form->isValid()) {
                $data = $form->getData();
                list($successInRestaurant, $failedInRestaurant) = $itemService->disableInventoryItems($inventoryItem, $data['restaurant']);
                $restaurants = $itemService->findRestaurantsWhichTheInventoryItemIsActive($inventoryItem);
                if(count($restaurants)>0){
                    $form = $this->createForm(new DeactivateProductForRestaurantType($restaurants));
                }else{
                    return new JsonResponse(
                        array(
                            'data' => $this->renderView(
                                "@Supervision/modals/deactivate_inventory_item.html.twig",
                                array(
                                    'item' => $inventoryItem,
                                    'failedInRestaurant' => empty($failedInRestaurant) ? [] : $failedInRestaurant,
                                    'successInRestaurant' => empty($successInRestaurant) ? [] : $successInRestaurant,
                                    'isActivatedInOneOfTheRestaurant' => false
                                )
                            ),
                            'footerBtn' => $this->renderView(
                                "@Supervision/modals/footer_deactivate_inventory_item.html.twig",
                                array(
                                    'item' => $inventoryItem,
                                    'isActivatedInOneOfTheRestaurant' => false
                                )
                            ),
                        )
                    );
                }
            }
            return new JsonResponse(
                array(
                    'data' => $this->renderView(
                        "@Supervision/modals/deactivate_inventory_item.html.twig",
                        array(
                            'item' => $inventoryItem,
                            'form' => $form->createView(),
                            'failedInRestaurant' => empty($failedInRestaurant) ? [] : $failedInRestaurant,
                            'successInRestaurant' => empty($successInRestaurant) ? [] : $successInRestaurant,
                            'isActivatedInOneOfTheRestaurant' => true
                        )
                    ),
                    'footerBtn' => $this->renderView(
                        "@Supervision/modals/footer_deactivate_inventory_item.html.twig",
                        array(
                            'item' => $inventoryItem,
                            'isActivatedInOneOfTheRestaurant' => true
                        )
                    ),
                )
            );
        } else {
            return new JsonResponse(
                array(
                    'data' => $this->renderView(
                        "@Supervision/modals/deactivate_inventory_item.html.twig",
                        array(
                            'item' => $inventoryItem,
                            'isActivatedInOneOfTheRestaurant' => false
                        )
                    ),
                    'footerBtn' => $this->renderView(
                        "@Supervision/modals/footer_deactivate_inventory_item.html.twig",
                        array(
                            'item' => $inventoryItem,
                            'isActivatedInOneOfTheRestaurant' => false
                        )
                    ),
                )
            );
        }

    }


}
