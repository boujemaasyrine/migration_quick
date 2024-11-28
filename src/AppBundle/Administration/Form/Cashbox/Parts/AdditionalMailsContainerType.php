<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 24/05/2016
 * Time: 10:52
 */

namespace AppBundle\Administration\Form\Cashbox\Parts;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Class AdditionalMailsContainerType
 * @package AppBundle\Administration\Form\Cashbox\Parts
 */
class AdditionalMailsContainerType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'mails',
                CollectionType::class,
                [
                    'entry_type' => MailValueType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'mapped' => true,
                    'by_reference' => false,
                    'prototype' => true,
                    'prototype_name' => '__index__',
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
