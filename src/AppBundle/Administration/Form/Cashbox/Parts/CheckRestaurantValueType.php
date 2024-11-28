<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 09/05/2016
 * Time: 11:32
 */

namespace AppBundle\Administration\Form\Cashbox\Parts;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;

class CheckRestaurantValueType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add('code', HiddenType::class)
            ->add('electronic', HiddenType::class)
            ->add('type', HiddenType::class)
            ->add(
                'affiliate_code',
                TextType::class,
                array(
                    'label' => 'label.affiliate_code',
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ),
                )
            )
            ->add(
                'values',
                CollectionType::class,
                [
                    'entry_type' => CheckRestaurantUnitValueType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'mapped' => true,
                    'by_reference' => false,
                    'prototype_name' => "__index__",
                    'constraints' => array(
                        new Valid(),
                    ),
                    'entry_options' => [
                        'error_bubbling' => false,
                    ],
                    'error_bubbling' => false,
                ]
            );
    }
}
