<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 06/05/2016
 * Time: 11:24
 */

namespace AppBundle\Administration\Form\Cashbox\Parts;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Class CheckQuickValueType
 * @package AppBundle\Administration\Form\Cashbox\Parts
 */
class CheckQuickValueType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id',HiddenType::class)
                ->add('type',HiddenType::class)
                ->add('values',CollectionType::class,[
                    'entry_type' => CheckQuickUnitValueType::class,
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
                ]);


    }
}
