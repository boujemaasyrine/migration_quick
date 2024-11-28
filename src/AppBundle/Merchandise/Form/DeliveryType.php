<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 26/02/2016
 * Time: 09:23
 */

namespace AppBundle\Merchandise\Form;

use AppBundle\Merchandise\Entity\Delivery;
use AppBundle\Merchandise\Entity\DeliveryLine;
use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\Supplier;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DeliveryType extends AbstractType
{

    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentRestaurant = $options['restaurant'];
        $builder
            ->add(
                'order',
                EntityType::class,
                array(
                    'choice_label' => function (Order $order) {
                        return " Commandée le ".$order->getDateOrder()->format('d/m/Y').' | Montant : '.number_format($order->getTotal(), 2, ',', '')." (€) ";
                    },
                    'class' => Order::class,
                    'placeholder' => $this->translator->trans("select_command"),
                    'query_builder' => function (EntityRepository $repo) use ($currentRestaurant) {
                        return $repo->createQueryBuilder('o')
                            ->where('o.status = :sended')
                            ->orWhere('o.status = :modified')
                            ->andWhere('o.originRestaurant = :currentRestaurant')
                            ->setParameter('sended', Order::SENDED)
                            ->setParameter('modified', Order::MODIFIED)
                            ->setParameter('currentRestaurant', $currentRestaurant)
                            ->orderBy('o.dateOrder', 'asc');
                    },
                    'group_by' => function (Order $val) {
                        return $this->translator->trans($val->getStatus(), [], 'order_status');
                    },
                    'constraints' => array(
                        new NotNull(),
                    ),
                )
            )
            ->add(
                'date',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => array(
                        new NotNull(),
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if ($value == null) {
                                        return;
                                    }

                                    $delivery = $context->getRoot()->getData();

                                    if ($delivery == null) {
                                        return;
                                    }

                                    if (!$delivery instanceof Delivery) {
                                        return;
                                    }

                                    $order = $delivery->getOrder();

                                    if ($order == null) {
                                        return;
                                    }

                                    if (!$value instanceof \DateTime) {
                                        return;
                                    }

                                    if ($value->format('Ymd') < $order->getDateOrder()->format('Ymd')) {
                                        $context->buildViolation('date_delivery_inf_date_commande')->addViolation();
                                    }
                                },
                            )
                        ),
                    ),
                )
            )
            ->add(
                'deliveryBordereau',
                TextType::class,
                array(
                    'constraints' => array(
                        new NotNull(),
                        new Length(array('max' => 50)),
                    ),
                )
            )
            ->add(
                'valorization',
                HiddenType::class,
                array(
                    'constraints' => array(
                        new NotNull(),
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+([,\.]{1}[0-9]+)?$/',
                            )
                        ),
                    ),
                )
            )
            ->add(
                'lines',
                CollectionType::class,
                array(
                    'entry_type' => DeliveryLineType::class,
                    'allow_add' => true,
                    'entry_options' => array(
                        'currentRestaurant' => $currentRestaurant,
                    ),
                    'constraints' => array(
                        new Count(
                            array(
                                'min' => 1,
                            )
                        ),
                    ),
                    'error_bubbling' => false,
                    'by_reference' => false,
                )
            )
            ->add(
                'prefix-num',
                HiddenType::class,
                array(
                    'mapped' => false,
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Delivery::class,
                'restaurant' => null,
            )
        );
    }
}
