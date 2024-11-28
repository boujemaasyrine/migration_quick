<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 15/03/2016
 * Time: 14:53
 */

namespace AppBundle\Supervision\Form\Items;

use AppBundle\Merchandise\Entity\Supplier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class InventoryItemSearchType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'nameSearch',
                TextType::class,
                array(
                    'label' => 'label.name',
                    'attr' => array('class' => 'form-control'),
                )
            )
            ->add(
                'codeSearch',
                TextType::class,
                array(
                    'label' => 'label.code',
                    'attr' => array('class' => 'form-control'),
                )
            )
            ->add(
                'supplierSearch',
                EntityType::class,
                [
                    'label' => 'keyword.supplier',
                    'class' => Supplier::class,
                    'choice_label' => 'name',
                    'empty_value' => 'item.inventory.choose_supplier',
                ]
            )
            ->add(
                'statusSearch',
                ChoiceType::class,
                array(
                    'label' => 'keyword.status',
                    'empty_value' => 'item.inventory.choose_status',
                    'choices' => array(
                        'active' => 'status.active',
                        'inactive' => 'status.inactive',
                        'toInactive' => 'status.toInactive',
                    ),
                )
            )
            ->add(
                'lastDateSynchro',
                'text',
                array(
                    'label' => 'last_synchronisation_date',
                    'required' => false,
                    'attr' => array('class' => 'form-control datepicker'),
                )
            )
            ->add(
                'dateSynchro',
                'text',
                array(
                    'label' => 'synchronisation_date',
                    'required' => false,
                    'attr' => array('class' => 'form-control datepicker'),
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'translation_domain' => 'supervision',
            ]
        );
    }
}
