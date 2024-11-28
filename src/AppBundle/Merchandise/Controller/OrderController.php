<?php

namespace AppBundle\Merchandise\Controller;

use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\OrderLine;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Form\OrderType;
use AppBundle\Security\RightAnnotation;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NoResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Intl\Exception\NotImplementedException;

/**
 * Class CommandController
 *
 * @package           AppBundle\Controller
 * @Route("/command")
 */
class OrderController extends Controller
{


    public function indexAction($name)
    {
        return $this->render('', array('name' => $name));
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/add/{order}",name="add_command")
     * @RightAnnotation("add_command")
     */
    public function addAction(Request $request, Order $order = null)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $suppliers = $this->getDoctrine()->getRepository("Merchandise:Supplier")->getRestaurantSuppliers(
            $currentRestaurant
        );

        $formOrder = new Order();
        $edit = true;
        if ($order === null) { // creation of new order
            $order = new Order();
            $order->setOriginRestaurant($currentRestaurant);
            $formOrder->setNumOrder($this->get('order.service')->getLastOrderNum());
            $edit = false;
        } else { // updating a existing order
            $formOrder
                ->setNumOrder($order->getNumOrder())
                ->setSupplier($order->getSupplier())
                ->setDateOrder($order->getDateOrder())
                ->setDateDelivery($order->getDateDelivery());
        }

        $form = $this->createForm(
            OrderType::class,
            $formOrder,
            array(
                'validation_groups' => "validated_order",
                'oldOrder' => $order,
                'restaurant' => $currentRestaurant,
            )
        );

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formOrder->setEmployee($this->getUser());
                $formOrder->setOriginRestaurant($currentRestaurant);
                $this->get('order.service')->saveOrderAsDraft($formOrder, $order);
                if ($edit) {
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        "Commande #".$order->getNumOrder()." modifiée avec succès"
                    );
                } else {
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        "Commande #".$order->getNumOrder()." créée avec succès"
                    );
                }

                return $this->get('workflow.service')->nextStep($this->redirectToRoute("list_pendings_commands"));
            }
        } else {
            $form->get('lines')->setData($order->getLines());
        }

        return $this->render(
            '@Merchandise/Order/add_edit.html.twig',
            array(
                'orderForm' => $form->createView(),
                'suppliers' => $suppliers,
            )
        );
    }


    /**
     * @Route("/check_product",name="check_product_orderable",options={"expose"=true})
     */
    public function checkProductOrderable(Request $request)
    {
        $translator = $this->get('translator');
        $productId = $request->query->get('productId');
        $orderDateString = $request->query->get('orderDate');
        // Utilisez DateTime::createFromFormat pour analyser la date au format d/m/Y
        $orderDate = \DateTime::createFromFormat('d/m/Y', $orderDateString);
        $product = $this->getDoctrine()->getRepository(ProductPurchased::class)->find($productId);
        if (!$orderDate) {
            throw new \Exception("Invalid date format: " . $orderDateString);
        }
        if (!$product) {
            return new JsonResponse(['orderable' => false, 'message' => 'Produit non trouvé.'], 404);
        }

        $isOrderable = $this->get('product.service')->isProductOrderable($product, $orderDate);

        if ($isOrderable) {
            return new JsonResponse(['orderable' => true]);
        } else {
            return new JsonResponse(['orderable' => false, 'message' => $translator->trans('product.not_orderable')]);
        }
    }


    /**
     * @param Order $order
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     * @Route("/send_order/{order}",name="send_order")
     */
    public function sendOrder(Order $order)
    {

        $this->get('order.service')->createOrder($order);

        $this->get('session')->getFlashBag()->add('success', "command.send_planned");

        return $this->render(
            "@Merchandise/Order/recap_order.html.twig",
            array(
                'order' => $order,
            )
        );
    }

    /**
     * @param Request $request
     *
     * @return string
     * @Route("/pending_list",name="list_pendings_commands", options={"expose"=true})
     * @RightAnnotation("list_pendings_commands")
     */
    public function pendingListAction(Request $request)
    {
        $order = new Order();

        return $this->render(
            "@Merchandise/Order/pending_list.html.twig",
            array(
                'order' => $order,
            )
        );
    }

    /**
     * @param Order $order
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("/edit/{order}",name="edit_order", options={"expose"=true})
     */
    public function editAction(Request $request, Order $order)
    {

        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $suppliers = $this->getDoctrine()->getRepository("Merchandise:Supplier")->findBy(
            array(
                'active' => true,
            )
        );

        $canBeEdit = $this->get('order.service')->canBeEditable($order);

        if ($canBeEdit !== true) {
            $session = $this->get('session');
            $session->getFlashBag()->add('error', $canBeEdit);

            return $this->redirectToRoute("list_pendings_commands");
        }

        $newOrder = $this->get('order.service')->cloneOrderWithoutLines($order);
        $newOrder->setEmployee($this->getUser());
        $form = $this->createForm(
            OrderType::class,
            $newOrder,
            array(
                'validation_groups' => "validated_order",
                'oldOrder' => $order,
                'restaurant' => $currentRestaurant,
            )
        );

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $oldOrderCopie = clone $order;
                $order->setOriginRestaurant($currentRestaurant);
                $newOrder->setOriginRestaurant($currentRestaurant);
                $edit = $this->get('order.service')->editOrder($order, $newOrder);

                $session = $this->get("session");
                if ($edit === true) {
                    $session->getFlashBag()->add('success', 'order_edit_success');

                    return $this->redirectToRoute("list_pendings_commands");
                } else {
                    $session->getFlashBag()->add('error', 'order_edit_fail');

                    return $this->render(
                        "@Merchandise/Order/recap_order.html.twig",
                        array(
                            'order' => $newOrder,
                            'oldOrder' => $oldOrderCopie,
                        )
                    );
                }
            }
        } else {
            $form->get('lines')->setData($order->getLines());
        }

        return $this->render(
            '@Merchandise/Order/add_edit.html.twig',
            array(
                'orderForm' => $form->createView(),
                'edit_page' => true,
                'suppliers' => $suppliers,
            )
        );
    }

    /**
     * @param Request $request
     * @param Order $order
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @Route("/detail/{order}",name="details_order",options={"expose"=true})
     */
    public function detailsAction(Request $request, Order $order)
    {

        if ($request->isXmlHttpRequest()) {
            $data = $this->renderView("@Merchandise/Order/modals/details_pending_order.html.twig", ['order' => $order]);

            return new JsonResponse(array('data' => $data, 'status' => $order->getStatus()));
        }

        return $this->render("@Merchandise/Order/modals/details_pending_order.html.twig", ['order' => $order]);
    }

    /**
     * @param Request $request
     * @param Order $order
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("/cancel/{order}",name="cancel_order",options={"expose"=true})
     */
    public function cancelOrderAction(Request $request, Order $order)
    {
        $session = $this->get('session');

        //Cancel Tests
        $canBeCancelled = $this->get('order.service')->canBeCancelled($order);

        if ($canBeCancelled !== true) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(
                    array(
                        'data' => false,
                        'errors' => array(
                            $canBeCancelled,
                        ),
                    )
                );
            } else {
                $session->getFlashBag()->set('error', 'order_cancelled_fail');
                $session->getFlashBag()->set('error', $canBeCancelled);

                return $this->redirectToRoute("list_pendings_commands");
            }
        }

        $form = $this->createFormBuilder()->getForm();

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            $canceled = $this->get('order.service')->cancelOrder($order);
            if ($canceled) {
                $session->getFlashBag()->set('success', 'order_cancelled_success');
            } else {
                $session->getFlashBag()->set('error', 'order_cancelled_fail');
            }

            return $this->redirectToRoute("list_pendings_commands");
        }

        return new JsonResponse(
            array(
                'data' => true,
                'orderNum' => $order->getNumOrder(),
                'html' => $this->renderView(
                    '@Merchandise/Order/cancel.html.twig',
                    array(
                        'form' => $form->createView(),
                        'order' => $order,
                    )
                ),
            )
        );
    }

    /**
     * @param Order $order
     * @return JsonResponse
     * @Route("/mark_as_sended/{order}",name="mark_as_sended",options={"expose"=true})
     */
    public function markAsSended(Order $order)
    {
        if (in_array($order->getStatus(), [Order::DRAFT, Order::REJECTED])) {
            $order->setStatus(Order::SENDED);
            $this->getDoctrine()->getManager()->flush();
            $this->get('session')->getFlashBag()->add('success', 'marked_as_sended_success');
        } else {
            $this->get('session')->getFlashBag()->add('error', 'marked_as_sended_failed');
            $this->get('session')->getFlashBag()->add('error', 'only_rejected_prepared_can_be_forced');
        }

        return $this->redirectToRoute("list_pendings_commands");
    }

    /**
     * @param Request $request
     * @param Order $order
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("/force_modification/{order}",name="force_modification")
     */
    public function forceModificationOrder(Request $request, Order $order)
    {

        $canBeForced = $this->get('order.service')->canBeForced($order);
        if ($canBeForced !== true) {
            $session = $this->get('session');
            $session->getFlashBag()->add('error', $canBeForced);

            return $this->redirectToRoute("list_pendings_commands");
        }

        /* END TESTS */

        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $newOrder = $this->get('order.service')->cloneOrderWithoutLines($order);
        $newOrder->setEmployee($this->getUser());
        $form = $this->createForm(
            OrderType::class,
            $newOrder,
            array(
                'validation_groups' => "validated_order",
                'oldOrder' => $order,
                'restaurant' => $currentRestaurant,
            )
        );

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $oldOrderCopie = clone $order;
                $order->setOriginRestaurant($currentRestaurant);
                $newOrder->setOriginRestaurant($currentRestaurant);
                $this->get('order.service')->editOrder($order, $newOrder, Order::MODIFIED);

                $sended = $this->get('order.service')->notifySupplierByModification($newOrder);
                $session = $this->get("session");
                if ($sended == false) {
                    $session->getFlashBag()->add('error', "Echèc lors de l'envoi du mail");
                }
                $session->getFlashBag()->add('success', 'order_edit_success');

                return $this->redirectToRoute("list_pendings_commands");
            }
        } else {
            $form->get('lines')->setData($order->getLines());
        }

        $suppliers = $this->getDoctrine()->getRepository("Merchandise:Supplier")->findBy(
            array(
                'active' => true,
            )
        );

        return $this->render(
            '@Merchandise/Order/add_edit.html.twig',
            array(
                'orderForm' => $form->createView(),
                'edit_page' => true,
                'suppliers' => $suppliers,
            )
        );
    }


    /**
     * @param Order $order
     * @return Response
     * @Route("/download/{order}/{download}",name="download_order", options={"expose" = true})
     */
    public function downloadAction(Order $order, $download)
    {
        if($download == 1)
        {
            $logoPath = $this->get('kernel')->getRootDir().'/../web/src/images/logo.png';

            return $this->get('order.service')->generateBonOrderExcelFile($order, $logoPath);
        }
        else if($download == 2)
        {
            $filename = "order_".date('Y_m_d_H_i_s').".pdf";
            $filepath = $this->get("order.service")->generateBonOrder($order);
            $response = Utilities::createFileResponse($filepath, $filename);

            return $response;
        }
        else
        {
            throw new BadRequestHttpException();
        }
    }
}
