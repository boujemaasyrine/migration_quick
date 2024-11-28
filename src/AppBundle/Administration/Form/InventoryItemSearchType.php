<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 15/03/2016
 * Time: 14:53
 */

namespace AppBundle\Administration\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class InventoryItemSearchType
 * @package AppBundle\Administration\Form
 */
class InventoryItemSearchType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                array('label' => 'label.name')
            )
            ->add(
                'supplier',
                EntityType::class,
                [
                    'label' => 'keyword.supplier',
                    'class' => 'AppBundle\Merchandise\Entity\Supplier',
                    'choice_label' => 'name',
                    'empty_value' => 'item.inventory.choose_supplier',
                ]
            )
            ->add(
                'status',
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
            );
    }
}
