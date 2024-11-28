<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 11/02/2016
 * Time: 09:43
 */

namespace AppBundle\Administration\Controller;

use AppBundle\Administration\Form\ProductSold\ProductSoldSearchType;
use AppBundle\General\Entity\Notification;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\SupplierPlanning;
use AppBundle\Merchandise\Entity\ProductCategories;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Security\RightAnnotation;
use AppBundle\Administration\Form\Search\InventoryItemSearchType;
use AppBundle\Administration\Form\Search\SupplierSearchType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Administration\Form\Supplier\SupplierPlanningType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class ConfigController
 *
 * @package                  AppBundle\Controller\Administrator
 * @Route("/administration")
 */
class ConfigMerchandiseController extends Controller
{

    /**
     * @RightAnnotation ("suppliers_list")
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/suppliers_list",name="suppliers_list",options={"expose"=true})
     * @Method({"GET","POST"})
     */
    public function suppliersListAction()
    {
        $form = $this->createForm(SupplierSearchType::Class);

        return $this->render(
            "@Administration/suppliers_list.html.twig",
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @RightAnnotation ("planning")
     * @param Supplier $supplier
     * @param Request  $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/planning/{supplier}",name="planning",options={"expose"=true})
     */
    public function planningAction(Request $request, Supplier $supplier = null)
    {

        $restaurant = $this->get("restaurant.service")->getCurrentRestaurant();

        if ($supplier == null) {
            $categories = $this->getDoctrine()->getRepository(
                ProductCategories::class
            )->findAll();
            $supplier = new Supplier;
        } else {
            $categories = $this->getDoctrine()->getRepository(
                ProductCategories::class
            )->findCategoriesBySupplierAndRestaurant($supplier, $restaurant);
        }
        $options['categories'] = $categories;
        $options['restaurant'] = $restaurant;

        $form = $this->createForm(
            SupplierPlanningType::Class,
            $supplier,
            $options
        );

        $form->handleRequest($request);
        if ($form->isValid()) {
            $this->get("config.merchandise.service")->setSupplierPlannings(
                $supplier,
                $restaurant
            );
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $usersToNotify = $this->getDoctrine()->getRepository(
                'Staff:Employee'
            )->getAllExceptTheAuthenticated(
                $user->getId(), $restaurant
            );
            $this->get("notification.service")->addNotificationByUsers(
                Notification::SCHEDULE_DELIVERY_CHANGED_NOTIFICATION,
                $restaurant,
                [
                    'supplier' => $supplier->getName(),
                    'user'     => $user->getFirstName().' '.$user->getLastName(
                        ),
                ],
                Notification::PLANNING_SUPPLIERS_PATH,
                $usersToNotify
            );

            return $this->get('workflow.service')->nextStep(
                $this->redirectToRoute('planning_suppliers')
            );
        }

        if ($request->getMethod() == 'GET') {
            if ($request->isXmlHttpRequest()) {
                $response = new JsonResponse();
                $form->handleRequest($request);
                $response->setData(
                    [
                        "data" => [
                            count($supplier->getPlannings()),
                            $this->renderView(
                                '@Administration/parts/planning_initial_content.html.twig',
                                array(
                                    'form' => $form->createView(),
                                )
                            ),
                            'prototype' => $this->renderView(
                                '@Administration/parts/planning_line_prototype.html.twig',
                                array(
                                    'number'     => '_id_',
                                    'rank'       => '_rank_',
                                    'categories' => $categories,
                                )
                            ),
                        ],
                    ]
                );

                return ($response);
            }
        }

        return $this->render(
            "@Administration/planning.html.twig",
            array(
                'form'       => $form->createView(),
                'categories' => $categories,
            )
        );
    }


    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/deleteLinePlanning/{line}",name="delete_line_planning", options={"expose"=true})
     */
    public function deleteLinePlanningAction(Request $request, SupplierPlanning $line)
    {
        $restaurant = $this->get("restaurant.service")->getCurrentRestaurant();

        $form = $this->createFormBuilder()->getForm();
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            $supplier = $line->getSupplier();
            $deleted = $this->get('config.merchandise.service')
                ->deleteLinePlanning($line);
            if ($deleted) {
                $response = new JsonResponse();
                $options['categories'] = $this->getDoctrine()->getRepository(ProductCategories::class)->findCategoriesBySupplierAndRestaurant($supplier, $restaurant);
                $options['restaurant'] = $restaurant;
                $formSupplier = $this->createForm(
                    SupplierPlanningType::Class,
                    $supplier,
                    $options
                );
                $response->setData(
                    [
                        'data'    => true,
                        "resForm" => [
                            count($supplier->getPlannings()),
                            $this->renderView(
                                '@Administration/parts/planning_initial_content.html.twig',
                                array(
                                    'form' => $formSupplier->createView(),
                                )
                            ),
                        ],
                    ]
                );

                return $response;
            } else {
                return new JsonResponse(
                    array(
                        'data' => false,
                    )
                );
            }
        }

        return new JsonResponse(
            array(
                'data' => true,
                'html' => $this->renderView(
                    '@Administration/parts/delete_line_planning.html.twig',
                    array(
                        'form' => $form->createView(),
                        'line' => $line,
                    )
                ),
            )
        );
    }


    /**
     * @RightAnnotation ("restaurant_list")
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/restaurant_list",name="restaurant_list")
     */
    public function restaurantListAction()
    {
        $restaurants = $this->getDoctrine()->getRepository(
            "Merchandise:Restaurant"
        )->findAll();

        return $this->render(
            "@Administration/restaurant_list.html.twig",
            array(
                'restaurants' => $restaurants,
            )
        );
    }

    /**
     * @RightAnnotation ("inventory_item_list")
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/inventory_item_list",name="inventory_item_list")
     */
    public function inventoryItemAction()
    {
        $currentRestaurant = $this->get('restaurant.service')->getCurrentRestaurant();
        $form = $this->createForm(InventoryItemSearchType::Class, null, array(
            "restaurant" => $currentRestaurant
        ));

        return $this->render("@Administration/inventory_items_list.html.twig", array(
            'form' => $form->createView()
        ));
    }

    /**
     * @param Request $request
     * @param ProductPurchased $inventoryItem
     *
     * @return JsonResponse
     * @Route("/json/inventory_item_detail/{inventoryItem}",name="inventory_item_detail",options={"expose"=true})
     */
    public function inventoryItemDetailJsonAction(
        Request $request,
        ProductPurchased $inventoryItem
    ) {

        return new JsonResponse(
            array(

                'data' => $this->renderView(
                    "@Administration/modals/details_list_inventory_item.html.twig",
                    array(
                        'item' => $inventoryItem,
                    )
                ),
            )
        );
    }

    /**
     * @RightAnnotation("product_sold_list")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/product_sold_list", name="product_sold_list", options={"expose"=true})
     * @Method({"GET"})
     */
    public function productSoldListAction(Request $request)
    {
        $response = null;
        if ($request->isXmlHttpRequest()) {
            if ($request->getMethod() === "GET") {
                $response = new JsonResponse();
                $draw = $request->get('draw', 0);
                $offset = $request->get('start', 0);
                $limit = $request->get(
                    'length',
                    $this->getParameter('number_of_rows_per_page')
                );
                $criteria = $request->get('search', null);
                $order = $request->get('order', null);
                try {
                    $serviceResponse = $this->get('product.sold.service')
                        ->getProductsSold(
                            $criteria,
                            $order,
                            $offset,
                            $limit
                        );
                    $serviceResponse['draw'] = intval($draw);
                    $response->setData(
                        json_decode(
                            $this->get('serializer')->serialize(
                                $serviceResponse,
                                'json'
                            )
                        )
                    );
                } catch (\Exception $e) {
                    $response->setData(
                        [
                            "errors" => [
                                $this->get('translator')->trans(
                                    'Error.general.internal'
                                ),
                                $e->getLine()." : ".$e->getMessage(),
                            ],
                        ]
                    );
                }
            }
        } else {
            $formSearch = $this->createForm(ProductSoldSearchType::Class);
            $response = $this->render(
                "@Administration/MerchandiseManagement/product_sold_list.html.twig",
                [
                    'formSearch' => $formSearch->createView(),
                ]
            );
        }

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/product_sold_detail/{productSold}", name="product_sold_detail", options={"expose"=true})
     * @Method({"GET"})
     */
    public function productSoldDetailsAction(
        Request $request,
        ProductSold $productSold
    ) {
        $response = null;
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();


            $response->setData(
                [
                    "data" => [
                        $this->renderView(
                            '@Administration/MerchandiseManagement/parts/details_product_sold.html.twig',
                            [
                                'productSold' => $productSold,
                            ]
                        ),
                    ],
                ]
            );
        }

        return $response;
    }
}
