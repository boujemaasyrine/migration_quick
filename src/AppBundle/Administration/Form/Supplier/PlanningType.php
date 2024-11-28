<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 03/03/2016
 * Time: 17:51
 */

namespace AppBundle\Administration\Form\Supplier;

use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\SupplierPlanning;
use Doctrine\DBAL\Types\StringType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class PlanningType
 */
class PlanningType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $categories = $options['categories'];
        $builder
            ->add(
                'orderDay',
                ChoiceType::class,
                array(
                    'choices' => array(
                        '1' => 'days.monday',
                        '2' => 'days.tuesday',
                        '3' => 'days.wednesday',
                        '4' => 'days.thursday',
                        '5' => 'days.friday',
                        '6' => 'days.saturday',
                        '0' => 'days.sunday',
                    ),
                    'expanded' => true,
                )
            )
            ->add(
                'deliveryDay',
                ChoiceType::class,
                array(
                    'choices' => array(
                        '1' => 'days.monday',
                        '2' => 'days.tuesday',
                        '3' => 'days.wednesday',
                        '4' => 'days.thursday',
                        '5' => 'days.friday',
                        '6' => 'days.saturday',
                        '0' => 'days.sunday',
                    ),
                    'expanded' => true,
                )
            )
            ->add(
                'categories',
                EntityType::class,
                [
                    'label' => 'provider.list.category_label',
                    'class' => 'AppBundle\Merchandise\Entity\ProductCategories',
                    'choices' => $categories,
                    'choice_label' => 'name',
                    'empty_value' => 'provider.planning.category_empty',
                    'multiple' => true,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Count(
                            array(
                                'min' => 1,
                                'minMessage' => 'null_value',
                            )
                        ),
                    ),
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => SupplierPlanning::class,
                'categories' => ProductCategories::class,
            )
        );
    }
}
