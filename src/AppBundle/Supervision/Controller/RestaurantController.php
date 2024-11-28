<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 07/03/2016
 * Time: 15:45
 */

namespace AppBundle\Supervision\Controller;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\AdminClosingTmp;
use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Merchandise\Entity\Restaurant;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Supervision\Form\Restaurant\RestaurantType;
use AppBundle\Supervision\Form\Restaurant\ParametersRestaurantType;
use AppBundle\Security\RightAnnotation;
use AppBundle\Supervision\Form\Restaurant\RestaurantEditType;

class
RestaurantController extends Controller
{

    /**
     * @RightAnnotation("restaurants_list")
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/list_restaurant/{restaurant}",name="restaurants_list", options={"expose"=true})
     */
    public function listRestaurantAction(Request $request, Restaurant $restaurant = null)
    {
        $translator = $this->get("translator");
        if ($restaurant == null) {
            $restaurant = new Restaurant();
        }
        $validation = ($request->get('validation') and $request->get('validation') == 'false');
        if ($validation) {
            $form = $this->createForm(RestaurantType::Class, $restaurant, array(
                'validation_groups' => false,
            ));
        } else {
            $form = $this->createForm(RestaurantType::Class, $restaurant);
        }
        $administrativeClosing = $ordersUrl = $usersUrl = $wyndUser = $withdrawalUrl  = $secretKey = $optikitchenPath = $wyndActive = null;
        if ($restaurant != null and $restaurant->getActive()) {
            $ordersUrl = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::ORDERS_URL_TYPE, 'originRestaurant' => $restaurant)
            );
            $usersUrl = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::USERS_URL_TYPE, 'originRestaurant' => $restaurant)
            );

            $withdrawalUrl = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::WITHDRAWAL_URL_TYPE, 'originRestaurant' => $restaurant)
            );

            $wyndActive = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::WYND_ACTIVE, 'originRestaurant' => $restaurant)
            );
            $wyndUser = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::WYND_USER, 'originRestaurant' => $restaurant)
            );
            $secretKey = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::SECRET_KEY, 'originRestaurant' => $restaurant)
            );
            $optikitchenPath = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
                array('type' => Parameter::OPTIKITCHEN_PATH, 'originRestaurant' => $restaurant)
            );
            $administrativeClosing = $this->getDoctrine()->getRepository(AdministrativeClosing::class)->findOneBy(
                array('originRestaurant' => $restaurant), array('date' => 'ASC')
            );

            if ($ordersUrl != null and $form->has('ordersUrl')) {
                $form->get('ordersUrl')->setData($ordersUrl->getValue());
            }
            if ($usersUrl != null and $form->has('usersUrl')) {
                $form->get('usersUrl')->setData($usersUrl->getValue());
            }

            if ($withdrawalUrl != null and $form->has('withdrawalUrl')) {
                $form->get('withdrawalUrl')->setData($withdrawalUrl->getValue());
            }

            if ($wyndActive != null and $form->has('wyndActive')) {
                $form->get('wyndActive')->setData(boolval($wyndActive->getValue()));
            }
            if ($wyndUser != null and $form->has('wyndUser')) {
                $form->get('wyndUser')->setData($wyndUser->getValue());
            }
            if ($optikitchenPath != null and $form->has('optikitchenPath')) {
                $form->get('optikitchenPath')->setData($optikitchenPath->getValue());
            }
            if ($secretKey != null and $form->has('secretKey')) {
                $form->get('secretKey')->setData($secretKey->getValue());
            }
            if ($administrativeClosing != null and $form->has('openingDate')) {
                /**
                 * @var \DateTime $openingDate
                 */
                $openingDate = $administrativeClosing->getDate();
                $openingDate->add(new \DateInterval('P1D'));
                $form->get('openingDate')->setData($administrativeClosing->getDate());
            }
        }

        $type = ($restaurant->getId() != null) ? 'edit' : 'plus'; // flag to check if new restaurant or restaurant modification
        $formError = false;
        if ($request->getMethod() === "POST") {

            $form->handleRequest($request);
            if ($restaurant->getActive() and !$validation and $administrativeClosing == null and $form->has('openingDate')) {
                $openingDate = $form->get('openingDate')->getData();
                $today = new \DateTime();
                $today->setTime(0, 0, 0);
                if ($openingDate < $today) {
                    $formError = true;
                    $form->get('openingDate')->addError(new FormError($this->get("translator")->trans('restaurant.form_error.opening_date', [], 'supervision')));
                } else {
                    $openingDate->sub(new \DateInterval('P1D'));
                    $adminClosing = new AdministrativeClosing();
                    $adminClosing->setOriginRestaurant($restaurant);
                    $adminClosing->setDate($openingDate);
                    $this->getDoctrine()->getManager()->persist($adminClosing);
                    $adminClosingTmp = new AdminClosingTmp();
                    $adminClosingTmp->setOriginRestaurant($restaurant);
                    $adminClosingTmp->setDate($openingDate);
                    $this->getDoctrine()->getManager()->persist($adminClosingTmp);
                }
            }
            if ($formError == false and $form->isValid()) {
                if ($restaurant->getActive()) {
                    if ($ordersUrl == null) {
                        $ordersUrl = $form->get('ordersUrl')->getNormData();
                        $param = new Parameter();
                        $param->setValue($ordersUrl);
                        $param->setLabel($translator->trans('label.orders_url', [], 'supervision'));
                        $param->setType(Parameter::ORDERS_URL_TYPE);
                        $restaurant->addParameter($param);
                    } else {
                        $ordersUrl->setValue($form->get('ordersUrl')->getNormData());
                    }
                    if ($usersUrl == null) {
                        $usersUrl = $form->get('usersUrl')->getNormData();
                        $param = new Parameter();
                        $param->setValue($usersUrl);
                        $param->setLabel($translator->trans('label.users_url', [], 'supervision'));
                        $param->setType(Parameter::USERS_URL_TYPE);
                        $restaurant->addParameter($param);
                    } else {
                        $usersUrl->setValue($form->get('usersUrl')->getNormData());
                    }

                    if ($withdrawalUrl == null) {
                        $withdrawalUrl = $form->get('withdrawalUrl')->getNormData();
                        $param = new Parameter();
                        $param->setValue($withdrawalUrl);
                        $param->setLabel($translator->trans('label.withdrawal_url', [], 'supervision'));
                        $param->setType(Parameter::WITHDRAWAL_URL_TYPE);
                        $restaurant->addParameter($param);
                    } else {
                        $withdrawalUrl->setValue($form->get('withdrawalUrl')->getNormData());
                    }

                    if ($wyndUser == null) {
                        $wyndUser = $form->get('wyndUser')->getNormData();
                        $param = new Parameter();
                        $param->setValue($wyndUser);
                        $param->setLabel($translator->trans('label.wynd_user', [], 'supervision'));
                        $param->setType(Parameter::WYND_USER);
                        $restaurant->addParameter($param);
                    } else {
                        $wyndUser->setValue($form->get('wyndUser')->getNormData());
                    }

                    if ($secretKey == null) {
                        $secretKey = $form->get('secretKey')->getNormData();
                        $param = new Parameter();
                        $param->setValue($secretKey);
                        $param->setLabel($translator->trans('label.secret_key', [], 'supervision'));
                        $param->setType(Parameter::SECRET_KEY);
                        $restaurant->addParameter($param);
                    } else {
                        $secretKey->setValue($form->get('secretKey')->getNormData());
                    }

                    if ($optikitchenPath == null) {
                        $optikitchenPath = $form->get('optikitchenPath')->getNormData();
                        $param = new Parameter();
                        $param->setValue($optikitchenPath);
                        $param->setLabel($translator->trans('label.optikitchen_path', [], 'supervision'));
                        $param->setType(Parameter::OPTIKITCHEN_PATH);
                        $restaurant->addParameter($param);
                    } else {
                        $optikitchenPath->setValue($form->get('optikitchenPath')->getNormData());
                    }

                    if ($wyndActive == null) {
                        $wyndActive = $form->get('wyndActive')->getNormData();
                        $param = new Parameter();
                        $param->setValue((int)$wyndActive);
                        $param->setLabel($translator->trans('label.wynd_active', [], 'supervision'));
                        $param->setType(Parameter::WYND_ACTIVE);
                        $restaurant->addParameter($param);
                    } else {
                        $wyndActive->setValue((int)$form->get('wyndActive')->getNormData());
                    }
                }

                $this->get('supervision.restaurant.service')->saveRestaurant($restaurant, $type);
                $message = $this->get('translator')->trans(
                    'restaurant-' . $type . '-success',
                    [
                        '%name%' => $restaurant->getName(),
                    ],
                    "supervision"
                );
                $this->get('session')->getFlashBag()->add('success', $message);

                return $this->redirectToRoute('restaurants_list');
            } else {
                $formError = true;
            }
        }
        if ($request->isXmlHttpRequest()) {
            $response = new JsonResponse();
            if ($request->getMethod() === 'GET') {
                $response->setData(
                    [
                        "data" => [
                            $this->renderView(
                                '@Supervision/parts/form_add_edit_restaurant.html.twig',
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

        if ($type == 'edit' and $validation) {
            $form = $this->createForm(RestaurantEditType::Class, $restaurant, array(
                'validation_groups' => false,
            ));
            if ($ordersUrl != null and $form->has('ordersUrl')) {
                $form->get('ordersUrl')->setData($ordersUrl->getValue());
            }
            if ($usersUrl != null and $form->has('usersUrl')) {
                $form->get('usersUrl')->setData($usersUrl->getValue());
            }
            if ($withdrawalUrl != null and $form->has('withdrawalUrl')) {
                $form->get('withdrawalUrl')->setData($withdrawalUrl->getValue());
            }
            if ($wyndActive != null and $form->has('wyndActive')) {
                $form->get('wyndActive')->setData(boolval($wyndActive->getValue()));
            }
            if ($wyndUser != null and $form->has('wyndUser')) {
                $form->get('wyndUser')->setData($wyndUser->getValue());
            }
            if ($optikitchenPath != null and $form->has('optikitchenPath')) {
                $form->get('optikitchenPath')->setData($optikitchenPath->getValue());
            }
            if ($secretKey != null and $form->has('secretKey')) {
                $form->get('secretKey')->setData($secretKey->getValue());
            }
            if ($administrativeClosing != null and $form->has('openingDate')) {
                /**
                 * @var \DateTime $openingDate
                 */
                $openingDate = $administrativeClosing->getDate();
                $openingDate->add(new \DateInterval('P1D'));
                $form->get('openingDate')->setData($administrativeClosing->getDate());
            }
        }

        $restaurants = $this->getDoctrine()->getRepository(Restaurant::class)->findAll();

        return $this->render(
            "@Supervision/restaurant_list.html.twig",
            array(
                'form' => $form->createView(),
                'restaurants' => $restaurants,
                'type' => $type,
                'formError' => $formError,
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/delete_restaurant/{restaurant}",name="delete_restaurant", options={"expose"=true})
     */
    public function deleteSupplierAction(Request $request, Restaurant $restaurant)
    {
        $session = $this->get('session');
        $form = $this->createFormBuilder(
            null,
            array('action' => $this->generateUrl('delete_restaurant', array('restaurant' => $restaurant->getId())))
        )->getForm();
        $text_button = $this->get('translator')->trans('restaurant.list.delete', array(), "supervision");
        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $deleted = $this->get('supervision.restaurant.service')->deleteRestaurant($restaurant);
                if ($deleted) {
                    $session->getFlashBag()->set('success', 'restaurant.list.delete_success');
                } else {
                    $session->getFlashBag()->set('error', 'restaurant.list.delete_fails');
                }
            }

            return $this->redirectToRoute("restaurants_list");
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
     * @param Restaurant $restaurant
     * @return JsonResponse
     * @Route("/json/restaurant_detail/{restaurant}",name="restaurant_detail",options={"expose"=true})
     */
    public function restaurantDetailJsonAction(Restaurant $restaurant)
    {
        $ordersUrl = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
            array('type' => Parameter::ORDERS_URL_TYPE, 'originRestaurant' => $restaurant)
        );
        $usersUrl = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
            array('type' => Parameter::USERS_URL_TYPE, 'originRestaurant' => $restaurant)
        );
        $withdrawalUrl = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
            array('type' => Parameter::WITHDRAWAL_URL_TYPE, 'originRestaurant' => $restaurant)
        );
        $wyndUser = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
            array('type' => Parameter::WYND_USER, 'originRestaurant' => $restaurant)
        );
        $secretKey = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
            array('type' => Parameter::SECRET_KEY, 'originRestaurant' => $restaurant)
        );
        $wyndActive = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
            array('type' => Parameter::WYND_ACTIVE, 'originRestaurant' => $restaurant)
        );
        $administrativeClosing = $this->getDoctrine()->getRepository(AdministrativeClosing::class)->findOneBy(
            array('originRestaurant' => $restaurant), array('date' => 'ASC')
        );
        $optikitchenPath = $this->getDoctrine()->getRepository(Parameter::class)->findOneBy(
            array('type' => Parameter::OPTIKITCHEN_PATH, 'originRestaurant' => $restaurant)
        );

        $ordersUrl = $ordersUrl == null ? "" : $ordersUrl->getValue();
        $usersUrl = $usersUrl == null ? "" : $usersUrl->getValue();
        $withdrawalUrl = $withdrawalUrl == null ? "" : $withdrawalUrl->getValue();
        $secretKey = $secretKey == null ? "" : $secretKey->getValue();
        $wyndActive = $wyndActive == null ? "" : $wyndActive->getValue();
        $wyndUser = $wyndUser == null ? "" : $wyndUser->getValue();
        $openingDate = $administrativeClosing == null ? "" : $administrativeClosing->getDate()->add(new \DateInterval('P1D'));
        $optikitchenPath = $optikitchenPath == null ? "" : $optikitchenPath->getValue();
        return new JsonResponse(
            array(
                'data' => $this->renderView(
                    "@Supervision/modals/details_list_restaurant.html.twig",
                    array(
                        'restaurant' => $restaurant,
                        'ordersUrl' => $ordersUrl,
                        'usersUrl' => $usersUrl,
                        'withdrawalUrl' => $withdrawalUrl,
                        'secretKey' => $secretKey,
                        'wyndActive' => $wyndActive,
                        'wyndUser' => $wyndUser,
                        'openingDate' => $openingDate,
                        'optikitchenPath' => $optikitchenPath
                    )
                ),
                'footerBtn' => $this->renderView(
                    "@Supervision/modals/footer_details_restaurant.html.twig",
                    array(
                        'restaurant' => $restaurant,
                    )
                ),
            )
        );
    }

    /**
     * @param Restaurant $restaurant
     * @param Request $request
     * @return JsonResponse
     * @Route("/json/restaurant_parameters/{restaurant}",name="restaurant_parameters",options={"expose"=true})
     */
    public function restaurantParametersAction(Request $request, Restaurant $restaurant)
    {
        $version = $this->getParameter('version');
        $data = $this->get('parameter.service')->loadParameters($restaurant);
        $form = $this->createForm(ParametersRestaurantType::class, $data, array('version' => $version));

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $paramsUpdate = $this->get('parameter.service')->saveParameters($restaurant, $form->getData());
                if ($paramsUpdate) {
                    return new JsonResponse(
                        array(
                            'restaurantName' => $restaurant->getName(),
                        )
                    );
                }
            } else {
                return new JsonResponse(
                    array(
                        'formView' => $this->renderView(
                            "@Supervision/Restaurant/modals/form_parameters.html.twig",
                            array(
                                'form' => $form->createView(),
                            )
                        ),
                        'btn' => $this->renderView(
                            "@Supervision/Restaurant/modals/btn_validate_form.html.twig",
                            array(
                                'restaurant' => $restaurant,
                            )
                        ),
                        'restaurantName' => $restaurant->getName(),
                    )
                );
            }
        }

        return new JsonResponse(
            array(
                'formView' => $this->renderView(
                    "@Supervision/Restaurant/modals/form_parameters.html.twig",
                    array(
                        'form' => $form->createView(),
                    )
                ),
                'btn' => $this->renderView(
                    "@Supervision/Restaurant/modals/btn_validate_form.html.twig",
                    array(
                        'restaurant' => $restaurant,
                    )
                ),
                'restaurantName' => $restaurant->getName(),
            )
        );
    }
}
