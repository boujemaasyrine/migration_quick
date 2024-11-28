<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 15/03/2016
 * Time: 14:53
 */

namespace AppBundle\Administration\Form\Cashbox;

use AppBundle\Administration\Form\Cashbox\Parts\AdditionalMailsContainerType;
use AppBundle\Administration\Form\Cashbox\Parts\CheckQuickContainerType;
use AppBundle\Administration\Form\Cashbox\Parts\CheckRestaurantContainerType;
use AppBundle\Administration\Form\Cashbox\Parts\ForeignCurrencyContainerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class CashboxParameterType
 *
 * @package AppBundle\Administration\Form\Cashbox
 */
class CashboxParameterType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'eft',
                ChoiceType::class,
                [
                    "choices" => [
                        false => "keyword.no",
                        true => "keyword.yes",
                    ],
                    'disabled' => true,
                ]
            )
            ->add(
                'nbrCashboxes',
                NumberType::class,
                [
                    "constraints" => [
                        new NotBlank(),
                    ],
                ]
            )
            ->add(
                'cashboxStartingDayFunds',
                TextType::class,
                [
                    'constraints' => [
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]{1,4}([\.,][0-9]+)?$/',
                                'message' => 'invalid_format',
                            )
                        ),
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ],
                ]
            )
            ->add(
                'openingHour',
                TextType::class,
                array(
                    'label' => 'parameter.opening_hour',
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if (!in_array($value, range(0, 23))) {
                                        $context->buildViolation('error.out_of_values')->addViolation();
                                    }
                                },
                            )
                        ),
                    ),
                )
            )
            ->add(
                'closingHour',
                TextType::class,
                array(
                    'label' => 'parameter.closing_hour',
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if (!in_array($value, range(0, 23))) {
                                        $context->buildViolation('error.out_of_values')->addViolation();
                                    }
                                },
                            )
                        ),
                    ),
                )
            )
            ->add(
                'mail',
                TextType::class,
                array(
                    'label' => 'label.mail',
                    'disabled' => true,
                )
            )
            ->add(
                'checkRestaurantContainer',
                CheckRestaurantContainerType::class,
                [
                ]
            )
            ->add(
                'checkQuickContainer',
                CheckQuickContainerType::class,
                [
                ]
            )
            ->add(
                'foreignCurrencyContainer',
                ForeignCurrencyContainerType::class,
                [
                ]
            )
            ->add(
                'additionalMailsContainer',
                AdditionalMailsContainerType::class,
                [
                    'label' => 'parameters.additional_emails',
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'restaurant' => null,
            )
        );
    }
}
