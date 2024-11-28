<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/12/2015
 * Time: 10:54
 */

namespace AppBundle\Supervision\Controller;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\Supervision\Entity\RecipeSupervision;
use AppBundle\Supervision\Form\Items\DeactivateProductForRestaurantType;
use AppBundle\Supervision\Form\Items\ProductSoldSearchType;
use AppBundle\Supervision\Form\Items\ProductSoldType;
use AppBundle\Supervision\Service\ProductSoldService;
use AppBundle\Merchandise\Entity\SubSoldingCanal;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use AppBundle\Security\RightAnnotation;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * Class ProductSoldController
 *
 * @package               AppBundle\Controller
 * @Route("product_sold")
 */
class ProductSoldController extends Controller
{

    /**
     * @RightAnnotation("product_sold_list")
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/list", name="supervision_product_sold_list", options={"expose"=true})
     * @Method({"GET"})
     */
    public function productSoldListAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $response = null;
        if ($request->isXmlHttpRequest()) {
            if ($request->getMethod() === "GET") {
                $response = new JsonResponse();
                $draw = $request->get('draw', 0);
                $offset = $request->get('start', 0);
                $limit = $request->get('length', $this->getParameter('number_of_rows_per_page'));
                $criteria = $request->get('search', null);
                $order = $request->get('order', null);
                try {
                    $serviceResponse = $this->get('supervision.product.sold.service')->getProductsSold(
                        $criteria,
                        $order,
                        $offset,
                        $limit
                    );
                    $serviceResponse['draw'] = intval($draw);
                    $response->setData(
                        json_decode($this->get('serializer')->serialize($serviceResponse, 'json'))
                    );
                } catch (\Exception $e) {
                    $response->setData(
                        [
                            "errors" => [
                                $this->get('translator')->trans('Error.general.internal', array(), 'supervision'),
                                $e->getLine() . " : " . $e->getMessage(),
                            ],
                        ]
                    );
                }
            }
        } else {
            $subSoldingCanals =  $em->getRepository(SubSoldingCanal::class)->findAll();

            $productSold = new ProductSoldSupervision();

            $em = $this->getDoctrine()->getManager();
            $canals = $em->getRepository(SoldingCanal::class)
                ->findBy(array('type' => SoldingCanal::DESTINATION), array('default' => 'DESC'));
            foreach ($canals as $canal) {
                if (in_array($canal->getId(), array(SoldingCanal::CANAL_ALL_CANALS, SoldingCanal::ON_SITE_CANAL, SoldingCanal::E_ORDERING_IN_CANAL))) {
                    foreach ($subSoldingCanals as $subSoldingCanal) {
                        $recipe = new RecipeSupervision();
                        $recipe->setSoldingCanal($canal);
                        $recipe->setSubSoldingCanal($subSoldingCanal);
                        $productSold->addRecipe($recipe);
                    }
                } else {
                    $recipe = new RecipeSupervision();
                    $recipe->setSoldingCanal($canal);
                    $productSold->addRecipe($recipe);

                }
            }

            $productSoldForm = $this->createForm(ProductSoldType::class, $productSold);
            $formSearch = $this->createForm(ProductSoldSearchType::Class);

            $response = $this->render(
                "@Supervision/ProductSold/product_sold_list.html.twig",
                [
                    "productSoldForm" => $productSoldForm->createView(),
                    'formSearch' => $formSearch->createView(),
                    "canals"  =>  $canals,
                    "subSoldingCanals"  =>  $subSoldingCanals,
                ]
            );
        }

        return $response;
    }

    /**
     * @RightAnnotation("product_sold_save")
     * @param Request $request
     * @param ProductSold $productSold
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/save/{productSold}", name="product_sold_save", options={"expose"=true})
     * @Method({"GET", "POST"})
     */
    public function saveProductSoldAction(Request $request, $productSold = null)
    {
        $em = $this->getDoctrine()->getManager();

        $session = $this->get('session');
        $response = null;

        if (is_null($productSold)) {
            $productSold = new ProductSoldSupervision();
        } else {
            $productSold = $em->getRepository(ProductSoldSupervision::class)
                ->find($productSold);
        }

        $subSoldingCanals =  $em->getRepository(SubSoldingCanal::class)->findAll();

        $canals = $em->getRepository(SoldingCanal::class)
            ->findBy(array('type' => SoldingCanal::DESTINATION), array('default' => 'DESC'));
        foreach ($canals as $canal) {
            if (in_array($canal->getId(), array(SoldingCanal::CANAL_ALL_CANALS, SoldingCanal::ON_SITE_CANAL, SoldingCanal::E_ORDERING_IN_CANAL))) {
                foreach ($subSoldingCanals as $subSoldingCanal) {
                    $oldRecipe = $productSold->getRecipes()->filter(
                        function ($entry) use ($canal, $subSoldingCanal) {
                            return $entry->getSoldingCanal()->getId() === $canal->getId()
                                && !is_null($entry->getSubSoldingCanal())
                                && $entry->getSubSoldingCanal()->getId() === $subSoldingCanal->getId();
                        }
                    );

                    if (!$oldRecipe->first()) {
                        $recipe = new RecipeSupervision();
                        $recipe->setSoldingCanal($canal);
                        $recipe->setSubSoldingCanal($subSoldingCanal);
                        $productSold->addRecipe($recipe);
                    }
                }
            } else {
                $oldRecipe = $productSold->getRecipes()->filter(
                    function ($entry) use ($canal) {
                        return $entry->getSoldingCanal()->getId() === $canal->getId();
                    }
                );

                if (!$oldRecipe->first()) {
                    $recipe = new RecipeSupervision();
                    $recipe->setSoldingCanal($canal);
                    $productSold->addRecipe($recipe);
                }
            }
        }


        $productSoldForm = $this->createForm(ProductSoldType::class, $productSold);

        if (!$request->isXmlHttpRequest()) {
            try {
                if ($request->getMethod() === "POST") {
                    $productSoldForm->handleRequest($request);
                    $group = $productSoldForm->getData()->getType() == ProductSoldSupervision::TRANSFORMED_PRODUCT ? ['transformed_product'] : ['non_transformed_product'];
                    $this->get('validator')->validate($productSold, null, $group);

                     if ($productSoldForm->isValid()) {
                        $this->get('supervision.product.sold.service')->saveProductSold($productSold);

                        if (!is_null($request->get('synchronize', null))) {
                            $this->get('sync.create.entry.service')->createProductSoldEntry($productSold, true);
                            $productSold->setLastDateSynchro(new \DateTime('now'));
                            $em->flush();
                        }
                        $session->getFlashBag()->add('success', 'product_sold.flashbag.product_created_with_success');

                        $productSold = new ProductSoldSupervision();
                        $productSoldForm = $this->createForm(ProductSoldType::class, $productSold);
                        return $this->redirectToRoute("supervision_product_sold_list");
                    }
                }
                $response =
                    $this->render(
                        '@Supervision/ProductSold/product_sold_form.html.twig',
                        [
                            "productSoldForm" => $productSoldForm->createView(),
                            "subSoldingCanals"  =>  $subSoldingCanals,
                            "canals"  =>  $canals,
                        ]
                    );
            } catch (\Exception $e) {
                $response =
                    $this->render(
                        '@Supervision/ProductSold/product_sold_form.html.twig',
                        [
                            "productSoldForm" => $productSoldForm->createView(),
                            "subSoldingCanals"  =>  $subSoldingCanals,
                            "canals"  =>  $canals,
                        ]
                    );
                $this->addFlash('error', 'Error.general.internal');
                $this->get('logger')->addError('ProductSoldController', $e->getTrace());
            }
        } else {
            throw new MethodNotAllowedException('Only http request are allowed !');
        }

        return $response;
    }

    /**
     * @param ProductSoldSupervision $productSold
     * @param Request $request
     * @return JsonResponse
     * @Route("/json/force_synchronize_sold_product/{productSold}",name="force_synchronize_sold_product",options={"expose"=true})
     */
    public function forceSynchronizePurchasedProduct(Request $request, ProductSoldSupervision $productSold)
    {
        $response = null;
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            $data = [];
            try {
                $this->get('sync.create.entry.service')->createProductSoldEntry($productSold, true);
                //Set date synchro � nulle lros du forcage
                $productSold->setDateSynchro(null);
                $this->getDoctrine()->getManager()->flush();
                $restaurants = [];
                foreach ($productSold->getRestaurants() as $restaurant) {
                    $restaurants[] = $restaurant->getName();
                }
                $restaurants = implode(',', $restaurants);
                $this->addFlash(
                    'success',
                    $this->get('translator')->trans(
                        'synchro_order_launched',
                        [
                            '%product%' => $productSold->getName(),
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
     *
     * @param Request $request
     * @param ProductSoldSupervision $productSold
     *
     * @return JsonResponse
     * @Route("/deactivate/{productSold}", name="deactivate_product_sold_in_restaurants", options={"expose"=true})
     *
     */
    public function deactivateProductSoldInRestaurantsAction(Request $request, ProductSoldSupervision $productSold = null)
    {

        /**
         * @var ItemsService $iteamService
         */
        $itemService = $this->get('items.service');
        $restaurants = $itemService->findRestaurantsWhichTheProductSoldIsActive($productSold);

        if (count($restaurants) > 0) {
            $form = $this->createForm(new DeactivateProductForRestaurantType($restaurants));
            $form->handleRequest($request);
            if ($form->isValid()) {
                $data = $form->getData();
                list($successInRestaurant, $failedInRestaurant) = $itemService->disableProductsSold($productSold, $data['restaurant']);
                $restaurants = $itemService->findRestaurantsWhichTheProductSoldIsActive($productSold);
                if (count($restaurants) > 0) {
                    $form = $this->createForm(new DeactivateProductForRestaurantType($restaurants));
                } else {
                    return new JsonResponse(
                        array(
                            'data' => $this->renderView(
                                "@Supervision/modals/deactivate_product_sold.html.twig",
                                array(
                                    'item' => $productSold,
                                    'failedInRestaurant' => empty($failedInRestaurant) ? [] : $failedInRestaurant,
                                    'successInRestaurant' => empty($successInRestaurant) ? [] : $successInRestaurant,
                                    'isActivatedInOneOfTheRestaurant' => false
                                )
                            ),
                            'footerBtn' => $this->renderView(
                                "@Supervision/modals/footer_deactivate_product_sold.html.twig",
                                array(
                                    'item' => $productSold,
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
                        "@Supervision/modals/deactivate_product_sold.html.twig",
                        array(
                            'item' => $productSold,
                            'form' => $form->createView(),
                            'failedInRestaurant' => empty($failedInRestaurant) ? [] : $failedInRestaurant,
                            'successInRestaurant' => empty($successInRestaurant) ? [] : $successInRestaurant,
                            'isActivatedInOneOfTheRestaurant' => true
                        )
                    ),
                    'footerBtn' => $this->renderView(
                        "@Supervision/modals/footer_deactivate_product_sold.html.twig",
                        array(
                            'item' => $productSold,
                            'isActivatedInOneOfTheRestaurant' => true
                        )
                    ),
                )
            );


        } else {
            return new JsonResponse(
                array(
                    'data' => $this->renderView(
                        "@Supervision/modals/deactivate_product_sold.html.twig",
                        array(
                            'item' => $productSold,
                            'isActivatedInOneOfTheRestaurant' => false
                        )
                    ),
                    'footerBtn' => $this->renderView(
                        "@Supervision/modals/footer_deactivate_product_sold.html.twig",
                        array(
                            'item' => $productSold,
                            'isActivatedInOneOfTheRestaurant' => false
                        )
                    ),
                )
            );
        }
    }

}
