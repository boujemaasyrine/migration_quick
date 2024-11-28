<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 01/03/2016
 * Time: 13:30
 */

namespace AppBundle\Merchandise\Form;

use AppBundle\Merchandise\Entity\ReturnLine;
use AppBundle\Merchandise\Form\DataTransformer\DeliveryLineToNumberTransformer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ReturnLineType extends AbstractType
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('product', ProductType::class)
            ->add(
                'qty',
                TextType::class,
                array(
                    'required' => false,
                    'constraints' => array(
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if (preg_match('/^[0-9]+$/', $value) == 0) {
                                        $context->buildViolation('int_postive_field')->addViolation();

                                        return;
                                    }
                                },
                            )
                        ),
                    ),
                )
            )
            ->add(
                'qtyExp',
                TextType::class,
                array(
                    'required' => false,
                    'constraints' => array(
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if (preg_match('/^[0-9]+$/', $value) == 0) {
                                        $context->buildViolation('int_postive_field')->addViolation();

                                        return;
                                    }
                                },
                            )
                        ),
                    ),
                )
            )
            ->add(
                'qtyUse',
                TextType::class,
                array(
                    'required' => false,
                    'constraints' => array(
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if (preg_match('/^[0-9]+$/', $value) == 0) {
                                        $context->buildViolation('int_postive_field')->addViolation();

                                        return;
                                    }
                                },
                            )
                        ),
                    ),
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => ReturnLine::class,
            )
        );
    }
}
