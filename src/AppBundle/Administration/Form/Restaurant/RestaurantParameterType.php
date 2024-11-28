<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 09/05/2016
 * Time: 13:52
 */

namespace AppBundle\Administration\Form\Restaurant;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Class RestaurantParameterType
 * @package AppBundle\Administration\Form\Restaurant
 */
class RestaurantParameterType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Regex(
                            array(
                                'pattern' => '/^[^\W][a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\@[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\.[a-zA-Z]{2,4}$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ),
                )
            );
    }
}
