<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 03/03/2016
 * Time: 16:38
 */

namespace AppBundle\Administration\Form\Supplier;

use AppBundle\Administration\Entity\PlanningCategory;
use AppBundle\Merchandise\Entity\ProductCategories;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Merchandise\Repository\SupplierRepository;
use AppBundle\Merchandise\Entity\Supplier;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Class SupplierPlanningType
 * @package AppBundle\Administration\Form\Supplier
 */
class SupplierPlanningType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $supplierSelected = !is_null($builder->getData()->getId()) ? $builder->getData() : null;
        $categories = $options['categories'];
        $restaurant = $options['restaurant'];
        $suppliers = $restaurant->getSuppliers();
        $builder
            ->add(
                'supplier',
                EntityType::class,
                [
                    'label' => 'provider.planning.supplier_select',
                    'class' => 'AppBundle\Merchandise\Entity\Supplier',
                    'choice_label' => 'name',
                    'mapped' => false,
                    'data' => $supplierSelected,
                    'empty_value' => 'provider.planning.supplier_select',
                    'query_builder' => function (SupplierRepository $er) use ($suppliers) {
                        return $er->createQueryBuilder('s')
                            ->orderBy('s.name', 'ASC')
                            ->where('s.active = :true')
                            ->setParameter('true', true)
                            ->andWhere('s IN (:suppliers)')
                            ->setParameter('suppliers', $suppliers);
                    },
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ),
                ]
            )
            ->add(
                'plannings',
                CollectionType::class,
                array(
                    'entry_type' => PlanningType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'entry_options' => ['categories' => $categories],
                )
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Supplier::class,
                'categories' => ProductCategories::class,
                'restaurant' => null,
            )
        );
    }
}
