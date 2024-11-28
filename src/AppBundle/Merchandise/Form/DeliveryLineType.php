<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 26/02/2016
 * Time: 11:14
 */

namespace AppBundle\Merchandise\Form;

use AppBundle\Merchandise\Entity\Delivery;
use AppBundle\Merchandise\Entity\DeliveryLine;
use AppBundle\Merchandise\Entity\ProductPurchased;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DeliveryLineType extends AbstractType
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $restaurant = $options["currentRestaurant"];
        $builder
            ->add(
                'new',
                ChoiceType::class,
                array(
                    'mapped' => false,
                    'choices' => array(true => 'true', false => 'false'),
                    'preferred_choices' => array(true),
                )
            )
            ->add(
                'qty',
                TextType::class,
                array(
                    'constraints' => array(
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if (preg_match('/^[0-9]+([,\.]{1}[0-9]+)?$/', $value) == 0) {
                                        $context->addViolation("Erreur de saisie");
                                    } else {
                                        $intValue = intval($value);
                                        if ($intValue < 0) {
                                            $context->addViolation("positive_field");
                                        }
                                    }
                                },
                            )
                        ),
                    ),
                )
            )
            ->add(
                'valorization',
                HiddenType::class,
                array(
                    'constraints' => array(
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+([,\.]{1}[0-9]+)?$/',
                            )
                        ),
                    ),
                )
            )
            ->add(
                'product_id',
                NumberType::class,
                array(
                    'mapped' => false,
                    'constraints' => array(
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    $delivery = $context->getRoot()->getData();
                                    if (!$delivery instanceof Delivery) {
                                        throw new \Exception("Expected an Delivery Object , got ".get_class($delivery));
                                    }

                                    $tab = explode("children[lines].children[", $context->getPropertyPath());
                                    $index = intval($tab[1][0]);

                                    $i = 0;
                                    foreach ($delivery->getLines() as $key => $l) {
                                        if ($l->getProduct()->getExternalId() == $value
                                            && $key < $index
                                        ) {
                                            $context
                                                ->buildViolation("product_already_entred")
                                                ->addViolation();

                                            return;
                                        }
                                        $i++;
                                    }
                                },
                            )
                        ),
                    ),
                )
            );

        $em = $this->em;
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($em, $restaurant) {

                $data = $event->getData();

                $productID = $data['product_id'];
                $product = $em->getRepository("Merchandise:ProductPurchased")->findOneBy(
                    array(
                        'externalId' => $productID,
                        'originRestaurant' => $restaurant
                    )
                );

                $deliveryLine = new DeliveryLine();
                $deliveryLine->setQty($data['qty'])
                    ->setProduct($product)
                    ->setValorization(str_replace(",", ".", $data['valorization']));

                $event->getForm()->setData($deliveryLine);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => DeliveryLine::class,
                'currentRestaurant' => null
            )
        );
    }
}
