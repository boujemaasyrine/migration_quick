<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 19/02/2016
 * Time: 09:22
 */

namespace AppBundle\Merchandise\Form;

use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\OrderLine;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Entity\SupplierPlanning;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class OrderType
 *
 * @package AppBundle\Merchandise\Form
 */
class OrderType extends AbstractType
{

    private $em;
    private $translator;

    public function __construct(EntityManager $entityManager, Translator $translator)
    {
        $this->em = $entityManager;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->em;
        $oldOrder = $options['oldOrder'];
        $restaurant = $options['restaurant'];
        $suppliers = $restaurant->getSuppliers();


        $builder
            ->add(
                'dateOrder',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => array(
                        new Callback(
                            array(
                                'groups' => 'validated_order',
                                'callback' => function (
                                    $value,
                                    ExecutionContextInterface $context
                                ) use (
                                    $em,
                                    $oldOrder,
                                    $restaurant
                                ) {

                                    if ($value === null || (is_scalar($value) && trim($value) === '')) {
                                        $context->buildViolation("null_value")->addViolation();

                                        return;
                                    }

                                    $order = $context->getRoot()->getData();
                                    if (!$order instanceof Order) {
                                        throw new \Exception("Expected an Order Object , got ".get_class($order));
                                    }
                                    if (Utilities::compareDates(
                                        $order->getDateOrder(),
                                        $order->getDateDelivery()
                                    ) === 1
                                    ) {
                                        $context->buildViolation("date_order_sup_delivery")->addViolation();
                                    } else {
                                        //Test the j-31 constraint
                                        $today = new \DateTime('NOW');
                                        $diff = $today->diff($value);
                                        if ($diff->days >= 31) {
                                            $context->buildViolation("j_31_constraint_message")->addViolation();

                                            return;
                                        }


                                        if ($order != null && $order->getSupplier() != null) {
                                            $orders = $em->getRepository(
                                                "Merchandise:Order"
                                            )->getPendingsOrderBySupplier($order->getSupplier(), $restaurant);
                                            /**
                                             * @var Order[] $orders
                                             */
                                            foreach ($orders as $o) {
                                                if ($o->getDateOrder()->format('Ymd') == $order->getDateOrder()->format(
                                                    'Ymd'
                                                )
                                                ) {
                                                    if (isset($oldOrder) && $oldOrder != null && $oldOrder instanceof Order && $o->getId(
                                                    ) == $oldOrder->getId()
                                                    ) {
                                                    } else {
                                                        //                                                        $context->buildViolation(
                                                        //                                                            "order_exist_for_supplier"
                                                        //                                                        )->addViolation();
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                },
                            )
                        ),
                    ),
                )
            )
            ->add(
                'dateDelivery',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => array(
                        new NotNull(
                            array(
                                'groups' => 'validated_order',
                                'message' => 'null_value',
                            )
                        ),
                    ),
                )
            )
            ->add(
                'supplier',
                EntityType::class,
                array(
                    'required' => false,
                    'class' => Supplier::class,
                    'choice_label' => 'name',
                    'placeholder' => $this->translator->trans("select_supplier"),
                    'query_builder' => function (EntityRepository $repository) use ($suppliers) {
                        return $repository->createQueryBuilder('s')
                            ->orderBy('s.name', 'ASC')
                            ->where('s.active = :true')
                            ->setParameter('true', true)
                            ->andWhere('s IN (:suppliers)')
                            ->setParameter('suppliers', $suppliers);
                    },
                    'constraints' => array(
                        new NotNull(
                            array(
                                'groups' => 'validated_order',
                                'message' => 'null_value',
                            )
                        ),
                    ),
                )
            )
            ->add(
                'lines',
                CollectionType::class,
                array(
                    'by_reference' => false,
                    'entry_type' => OrderLineType::class,
                    'prototype_name' => '_line_number_',
                    'allow_add' => true,
                    'allow_delete' => true,
                    'constraints' => array(
                        new Count(
                            array(
                                'min' => 1,
                                'groups' => array('validated_order'),
                                'minMessage' => 'order.order_line_min',
                            )
                        ),
                    ),
                    'error_bubbling' => false,
                    'entry_options' => array(
                        'constraints' => array(
                            new Callback(
                                array(
                                    'groups' => array("validated_order"),
                                    'callback' => function (
                                        $value,
                                        ExecutionContextInterface $context
                                    ) use (
                                        $em,
                                        $oldOrder,
                                        $restaurant
                                    ) {

                                        $order = $context->getRoot()->getData();

                                        if (!$order instanceof Order) {
                                            throw new \Exception("Expected an Order Object , got ".get_class($order));
                                        }

                                        if ($order->getDateOrder() == null
                                            || $order->getDateOrder() == null
                                            || $order->getSupplier() == null
                                        ) {
                                            return;
                                        }

                                        if (!$value instanceof OrderLine) {
                                            throw new \Exception(
                                                "Expected an OrderLine Object , got ".get_class($value)
                                            );
                                        }

                                        if ($value->getProduct()->getStatus() == ProductPurchased::INACTIVE) {
                                            $context->buildViolation('product_not_active')->addViolation();

                                            return;
                                        }

                                        if ($value->getProduct()->getSuppliers()->first() != $order->getSupplier()) {
                                            $context
                                                ->buildViolation("product_not_in_supplier_products")
                                                ->addViolation();

                                            return;
                                        }

                                        foreach ($order->getLines() as $l) {
                                            if ($l === $value) {
                                                break;
                                            }

                                            if ($l->getProduct()->getId() == $value->getProduct()->getId()) {
                                                $context
                                                    ->buildViolation("product_already_entred")
                                                    ->addViolation();

                                                return;
                                            }
                                        }

                                        //Test On category
                                        $categories = [];
                                        foreach ($order->getSupplier()->getPlannings() as $p) {
                                            if ($p->getOriginRestaurant() === $restaurant and $p->getOrderDay() == intval($order->getDateOrder()->format('w'))) {
                                                $categories = $p->getCategories()->map(
                                                    function (ProductCategories $c) {
                                                        return $c->getName();
                                                    }
                                                );
                                                break;
                                            }
                                        }
                                        if (count($categories) > 0 && !in_array(
                                            $value->getProduct()->getProductCategory()->getName(),
                                            $categories->toArray()
                                        )
                                        ) {
                                            $context
                                                ->buildViolation("product_not_category")
                                                ->addViolation();
                                        }

                                        if ($order != null && $order->getSupplier() != null) {
                                            $orders = $em->getRepository("Merchandise:Order")
                                                ->getPendingsOrderBySupplier($order->getSupplier(), $restaurant);
                                            /**
                                             * @var Order[] $orders
                                             */
                                            foreach ($orders as $o) {
                                                if ($o->getDateOrder()->format('Ymd') == $order->getDateOrder()->format('Ymd') && $o->getDateDelivery()->format('Ymd') == $order->getDateDelivery()->format('Ymd')) {
                                                    if (isset($oldOrder) && $oldOrder != null && $oldOrder instanceof Order && $o->getId(
                                                    ) == $oldOrder->getId()
                                                    ) {
                                                    } else {
                                                        foreach ($o->getLines() as $line) {
                                                            if ($line->getProduct()->getId() == $value->getProduct(
                                                            )->getId()
                                                            ) {
                                                                $context->buildViolation("order_exist_for_supplier")
                                                                    ->addViolation();
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    },
                                )
                            ),
                        ),
                        'error_bubbling' => false,
                    ),
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Order::class,
                'oldOrder' => Order::class,
                'restaurant' => null,
            )
        );
    }
}
