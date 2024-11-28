<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 15/03/2016
 * Time: 14:53
 */

namespace AppBundle\Administration\Form\ProductSold;

use AppBundle\Merchandise\Entity\ProductSold;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class ProductSoldSearchType
 * @package AppBundle\Administration\Form\ProductSold
 */
class ProductSoldSearchType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
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
                    'empty_value' => 'choose_type_item',
                    'choices' => array(
                        ProductSold::TRANSFORMED_PRODUCT => ProductSold::TRANSFORMED_PRODUCT,
                        ProductSold::NON_TRANSFORMED_PRODUCT => ProductSold::NON_TRANSFORMED_PRODUCT,
                    ),
                )
            );
    }
}
