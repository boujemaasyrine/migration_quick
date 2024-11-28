<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 09/03/2016
 * Time: 11:45
 */

namespace AppBundle\Supervision\Form\Items;

use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Repository\ProductPurchasedRepository;
use AppBundle\Merchandise\Repository\SupplierRepository;
use AppBundle\Merchandise\Repository\ProductCategoriesRepository;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Repository\ProductPurchasedSupervisionRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Regex;
use Doctrine\ORM\EntityManager;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class InventoryItemType extends AbstractType
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $externalId = ($builder->getData()->getExternalId() != null) ? $builder->getData()->getExternalId() : '0';
        $thisPrimary = ($builder->getData()->getSecondaryItem() != null) ? $builder->getData()->getId() : '0';
        $builder
            ->add(
                'externalId',
                TextType::class,
                array(
                    'label' => 'label.code',
                    'required' => true,
                    'attr' => array('class' => 'form-control'),
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Regex(
                            array(
                                'pattern' => '/^([0-9]*)$/',
                                'message' => 'invalid_format',
                            )
                        ),
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {


                                    $product = $context->getRoot()->getData();
                                    $suppliers = is_object($product->getSuppliers()) ? $product->getSuppliers(
                                    )->toArray() : $product->getSuppliers();
                                    $qb = $this->em->getRepository(
                                        ProductPurchasedSupervision::class
                                    )->createQueryBuilder('pp');
                                    $qb->join('pp.suppliers', 's')
                                        ->join('pp.restaurants', 'r')
                                        ->where('pp.externalId = :externalId')->setParameter('externalId', $value)
                                        ->andWhere('s in (:suppliers) or r in (:restaurant)')
                                        ->setParameter('suppliers', $suppliers)
                                        ->setParameter('restaurant', $product->getRestaurants()->toArray());
                                    if (!is_null($product->getId())) {
                                        $qb->andWhere('pp.id != :id')->setParameter('id', $product->getId());
                                    }

                                    $products = $qb->getQuery()->getResult();
                                    if ($products != null && sizeof($products) > 0) {
                                        $context->buildViolation('unique_product_purchased')
                                            ->addViolation();
                                    }
                                },
                            )
                        ),
                    ),
                )
            )
            ->add(
                'translations',
                CollectionType::class,
                array(
                    'entry_type' => ProductSupervisionTranslationType::class,
                    'entry_options' => array(
                        'label' => 'keyword.label',
                        'required' => true,
                    ),
                )
            )
            ->add(
                'reusable',
                CheckboxType::class,
                [
                    "required" => false,
                    'label' => 'Réutilisable ?',
                ]
            )
            ->add(
                'buyingCost',
                TextType::class,
                array(
                    'label' => 'item.label.buying_cost',
                    'required' => true,
                    'attr' => array('class' => 'form-control'),
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+([\.,][0-9]+)?$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ),
                )
            )
            ->add(
                'labelUnitExped',
                ChoiceType::class,
                array(
                    'label' => 'item.label.unit_expedition',
                    'empty_value' => 'choose_unit_expedition',
                    'required' => true,
                    'choices' => ProductPurchased::$units,
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
                'labelUnitInventory',
                ChoiceType::class,
                array(
                    'label' => 'item.label.unit_inventory',
                    'empty_value' => 'choose_unit_inventory',
                    'required' => true,
                    'choices' => ProductPurchased::$units,
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
                'labelUnitUsage',
                ChoiceType::class,
                array(
                    'label' => 'item.label.unit_usage',
                    'empty_value' => 'choose_unit_usage',
                    'required' => true,
                    'choices' => ProductPurchased::$units,
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
                'inventoryQty',
                TextType::class,
                array(
                    'label' => 'item.label.inventory_qty',
                    'attr' => array('class' => 'form-control'),
                    'required' => true,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+([\.,][0-9]+)?$/',
                                'message' => 'invalid_format',
                            )
                        ),
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if ($value != null && $value <= 0) {
                                        $context->buildViolation('positive_value')
                                            ->addViolation();
                                    }
                                },
                            )
                        ),
                    ),
                )
            )
            ->add(
                'usageQty',
                TextType::class,
                array(
                    'label' => 'item.label.usage_qty',
                    'attr' => array('class' => 'form-control'),
                    'required' => true,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+([\.,][0-9]+)?$/',
                                'message' => 'invalid_format',
                            )
                        ),
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if ($value != null && $value <= 0) {
                                        $context->buildViolation('positive_value')
                                            ->addViolation();
                                    }
                                },
                            )
                        ),
                    ),
                )
            )
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'label' => 'label.status',
                    'choices' => array(
                        'keyword.active' => ProductPurchased::ACTIVE,
                        'keyword.inactive' => ProductPurchased::INACTIVE,
                        'keyword.toInactive' => ProductPurchased::TO_INACTIVE,
                    ),
                    'choices_as_values' => true,
                )
            )
            ->add(
                'deactivationDate',
                DateType::class,
                array(
                    'label' => 'item.inventory.deactivateDate',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                )
            )
            ->add(
                'startDateCmd',
                DateType::class,
                array(
                    'label' => 'item.inventory.startDateCmdItem',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                )
            )
            ->add(
                'endDateCmd',
                DateType::class,
                array(
                    'label' => 'item.inventory.endDateCmdItem',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                )
            )
            ->add(
                'suppliers',
                EntityType::class,
                [
                    'label' => 'keyword.suppliers',
                    'class' => Supplier::class,
                    'choice_label' => 'name',
                    'empty_value' => 'item.inventory.choose_supplier',
                    'multiple' => true,
                    'required' => true,
                    'mapped' => true,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Count(
                            array(
                                'max' => 1,
                                'min' => 1,
                                'minMessage' => 'null_value',
                            )
                        ),
                    ),
                    'query_builder' => function (SupplierRepository $er) {
                        return $er->createQueryBuilder('s')
                            ->where('s.active = :true')
                            ->setParameter('true', true);
                    },
                ]
            )
            ->add(
                'secondaryItem',
                EntityType::class,
                [
                    'label' => 'item.inventory.secondary',
                    'required' => false,
                    'class' => ProductPurchasedSupervision::class,
                    'choice_label' => function (ProductPurchasedSupervision $item) {
                        return $item->getName().' '.$item->getExternalId();
                    },
                    'empty_value' => 'item.inventory.choose_product',
                    'query_builder' => function (ProductPurchasedSupervisionRepository $er) use (
                        $externalId,
                        $thisPrimary
                    ) {
                        return $er->createQueryBuilder('p')
                            ->leftJoin('p.primaryItem', 'pp')
                            ->where('p.primaryItem IS NULL OR pp.id = :thisPrimary')
                            ->andWhere('p.secondaryItem IS NULL')
                            ->andWhere('p.status IN (:true)')
                            ->andWhere('p.externalId <> :currentObjectCode')
                            ->setParameter('currentObjectCode', $externalId)
                            ->setParameter('thisPrimary', $thisPrimary)
                            ->setParameter('true', array('active', 'toInactive'));
                    },
                ]
            )
            ->add(
                'productCategory',
                EntityType::class,
                [
                    'label' => 'item.list.category',
                    'class' => ProductCategories::class,
                    'choice_label' => 'name',
                    'empty_value' => 'item.inventory.choose_category',
                    'query_builder' => function (ProductCategoriesRepository $er) {
                        return $er->createQueryBuilder('c')
                            ->where('c.active = :true')
                            ->setParameter('true', true);
                    },
                    'required' => true,
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
                'restaurants',
                EntityType::class,
                array(
                    'label' => 'keyword.restaurants',
                    'class' => Restaurant::class,
                    'choice_label' => function (Restaurant $restaurant) {
                        return $restaurant->getName().' ( '.$restaurant->getCode().' )';
                    },
                    'multiple' => true,
                    'required' => false,
                    'query_builder' => function(EntityRepository $e)
                    {
                        return $e->getOpenedRestaurantsQuery();
                    },
                )
            )
            ->add(
                'dateSynchro',
                DateType::class,
                array(
                    'label' => 'item.inventory.synchro_date',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'disabled' => false,
                    'required' => false,
                    'constraints' => array(
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if ($value != '' && $value->format('Y-m-d') <= date('Y-m-d')) {
                                        $context->buildViolation('date_in_futur')->addViolation();
                                    }
                                },
                            )
                        ),
                    ),
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => ProductPurchasedSupervision::class,
                'translation_domain' => 'supervision',
            )
        );
    }
}
