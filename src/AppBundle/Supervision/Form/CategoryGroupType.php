<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 08/03/2016
 * Time: 15:13
 */

namespace AppBundle\Supervision\Form;

use AppBundle\Merchandise\Entity\CategoryGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryGroupType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'translations',
                CollectionType::class,
                array(
                    'entry_type' => CategoryGroupTranslationType::class,
                    'entry_options' => array(
                        'label' => 'keyword.label',
                        'required' => true,
                    ),
                )
            )
            ->add(
                'isFoodCost',
                ChoiceType::class,
                array(
                    'choices' => array(
                        true => 'keyword.yes',
                        false => 'keyword.no',
                    ),
                    'label' => 'group.label.is_food_cost',
                    'expanded' => true,
                )
            );
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => CategoryGroup::class,
                'translation_domain' => 'supervision',
            )
        );
    }
}
