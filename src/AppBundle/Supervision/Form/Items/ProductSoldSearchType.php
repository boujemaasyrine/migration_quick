<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 15/03/2016
 * Time: 14:53
 */

namespace AppBundle\Supervision\Form\Items;

use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ProductSoldSearchType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'nameSearch',
                TextType::class,
                array(
                    'label' => 'label.name',
                    'required' => false,
                    'attr' => array('class' => 'form-control'),
                )
            )
            ->add(
                'codeSearch',
                TextType::class,
                array(
                    'label' => 'label.code',
                    'required' => false,
                    'attr' => array('class' => 'form-control'),
                )
            )
            ->add(
                'statusSearch',
                ChoiceType::class,
                array(
                    'label' => 'keyword.status',
                    'required' => false,
                    'attr' => array('class' => 'form-control'),
                    'empty_value' => 'item.inventory.choose_status',
                    'choices' => array(
                        true => 'status.active',
                        false => 'status.inactive',
                    ),
                )
            )
            ->add(
                'typeSearch',
                ChoiceType::class,
                array(
                    'label' => 'label.type',
                    'required' => false,
                    'attr' => array('class' => 'form-control'),
                    'empty_value' => 'item.inventory.choose_type',
                    'choices' => array(
                        ProductSoldSupervision::TRANSFORMED_PRODUCT => ProductSoldSupervision::TRANSFORMED_PRODUCT,
                        ProductSoldSupervision::NON_TRANSFORMED_PRODUCT => ProductSoldSupervision::NON_TRANSFORMED_PRODUCT,
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
