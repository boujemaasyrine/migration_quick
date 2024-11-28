<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/01/2016
 * Time: 17:39
 */

namespace AppBundle\Supervision\Form\Items;

use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\Supervision\Form\DataTransformer\ProductToIdTransformer;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Tests\Extension\Core\Type\CheckboxTypeTest;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use AppBundle\Merchandise\Entity\ProductSold;

class ProductSoldType extends AbstractType
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            /* ->add('name', TextType::class, array(
                 'attr' => array('class' => 'form-control'),
             ))*/
            ->add(
                'translations',
                CollectionType::class,
                array(
                    'entry_type' => ProductSupervisionTranslationType::class,
                    'entry_options' => array(
                        'label' => 'label.name',
                        'required' => true,
                        'constraints' => array(
                            new NotNull(
                                array(
                                    'groups' => array('transformed_product', 'non_transformed_product'),
                                    'message' => 'null_value',
                                )
                            ),
                        ),
                    ),
                )
            )
            ->add(
                'codePlu',
                TextType::class,
                array(
                    'attr' => array('class' => 'form-control'),
                    'constraints' => [
                        new NotNull(
                            array(
                                'groups' => array('transformed_product', 'non_transformed_product'),
                                'message' => 'null_value',
                            )
                        ),
                        new Callback(
                            [
                                'groups' => ['transformed_product', 'non_transformed_product'],
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    $productSold = $context->getRoot()->getData();
                                    if (!$productSold instanceof ProductSoldSupervision) {
                                        throw new \Exception(
                                            "Expected a ProductSold Object , got ".get_class($productSold)
                                        );
                                    }
//                                    $pluNOk = $this->em->getRepository(
//                                        ProductSold::class
//                                    )->checkIfRestaurantsHaveAlreadyProductWithThisPlu($productSold, $value);
//                                    if ($pluNOk) {
//                                        $context->buildViolation(
//                                            "product_sold.code_plu_already_exist_for_at_least_one_restaurant"
//                                        )->addViolation();
//                                    }
                                },
                            ]
                        ),
                    ],
                )
            )
            ->add(
                'active',
                ChoiceType::class,
                [
                    'choices' => [
                        'keyword.inactive',
                        'keyword.active',
                    ],
                    "expanded" => true,
                    "multiple" => false,
                    "required" => true,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'groups' => array('transformed_product', 'non_transformed_product'),
                                'message' => 'null_value',
                            )
                        ),
                    ),
                ]
            )

            ->add(
                'venteAnnexe',
                CheckboxType::class,
                [
                    "required" => true
                ]
            )
            ->add(
                'type',
                ChoiceType::class,
                [
                    "choices" => [
                        ProductSoldSupervision::NON_TRANSFORMED_PRODUCT => ProductSoldSupervision::NON_TRANSFORMED_PRODUCT,
                        ProductSoldSupervision::TRANSFORMED_PRODUCT => ProductSoldSupervision::TRANSFORMED_PRODUCT,
                    ],
                    "expanded" => true,
                    "multiple" => false,
                    "required" => true,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'groups' => array('transformed_product', 'non_transformed_product'),
                                'message' => 'null_value',
                            )
                        ),
                    ),
                ]
            )
            ->add(
                'recipes',
                CollectionType::class,
                [
                    'entry_type' => RecipeType::class,
                    'allow_add' => true,
                    'allow_delete' => false,
                    'mapped' => true,
                    'required' => false,
                    'by_reference' => false,
                    'error_bubbling' => false,
                    'prototype' => true,
                    "constraints" => [
                        new Valid(),
                        new Callback(
                            [
                                'groups' => 'transformed_product',
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    $productSold = $context->getRoot()->getData();
                                    if (!$productSold instanceof ProductSoldSupervision) {
                                        throw new \Exception(
                                            "Expected a ProductSold Object , got ".get_class($productSold)
                                        );
                                    }
                                    if ($productSold->getType() === ProductSoldSupervision::TRANSFORMED_PRODUCT) {
                                        if (count($productSold->getRecipes()) === 0) {
                                            $context->buildViolation(
                                                "product_sold.transformed_product_sold_must_have_at_least_one_recipe"
                                            )->addViolation();
                                        } else {
                                            $soldingCanalIds = [];
                                            foreach ($productSold->getRecipes() as $recipe) {
                                                /**
                                                 * @var Recipe $recipe
                                                 */
                                                if (!in_array($recipe->getSoldingCanal(), $soldingCanalIds)) {
                                                    $soldingCanalIds[] = $recipe->getSoldingCanal();
                                                } else {
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                },
                            ]
                        ),
                    ],
                    'entry_options' => [
                        'error_bubbling' => false,
                    ],
                ]
            )
            ->add(
                'productPurchased',
                HiddenType::class,
                [
                    "error_bubbling" => false,
                    "constraints" => [
                        new Callback(
                            [
                                'groups' => 'transformed_product',
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    $productSold = $context->getRoot()->getData();
                                    if (!$productSold instanceof ProductSoldSupervision) {
                                        throw new \Exception(
                                            "Expected a ProductSold Object , got ".get_class($productSold)
                                        );
                                    }
                                    if ($productSold->getType() === ProductSoldSupervision::NON_TRANSFORMED_PRODUCT) {
                                        if (!$value) {
                                            $context->buildViolation(
                                                "product_sold.product_purchased_is_required"
                                            )->addViolation();
                                        }
                                    }
                                },
                            ]
                        ),
                    ],
                ]
            )
            /* ->add('supplierCode', TextType::class, [
                 'required' => false,
                 'attr' => array('class' => 'form-control'),
                 "constraints" => [
                     new Callback([
                         'groups' => 'transformed_product',
                         'callback' => function ($value, ExecutionContextInterface $context) {
                             $productSold = $context->getRoot()->getData();
                             if (!$productSold instanceof ProductSold) {
                                 throw new \Exception("Expected a ProductSold Object , got " . get_class($productSold));
                             }
                             if($productSold->getType() === ProductSold::NON_TRANSFORMED_PRODUCT) {
                                 if (!$value) {
                                     $context->buildViolation(
                                         "product_sold.product_purchased_is_required"
                                     )->addViolation();
                                 }
                             }
                         }
                     ])
                 ]
             ])*/
            ->add(
                'productPurchasedName',
                TextType::class,
                [
                    'required' => false,
                    'attr' => array('class' => 'form-control'),
                    "constraints" => [
                        new Callback(
                            [
                                'groups' => 'transformed_product',
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    $productSold = $context->getRoot()->getData();
                                    if (!$productSold instanceof ProductSoldSupervision) {
                                        throw new \Exception(
                                            "Expected a ProductSold Object , got ".get_class($productSold)
                                        );
                                    }
                                    if ($productSold->getType() === ProductSoldSupervision::NON_TRANSFORMED_PRODUCT) {
                                        if (!$value) {
                                            $context->buildViolation(
                                                "product_sold.product_purchased_is_required"
                                            )->addViolation();
                                        }
                                    }
                                },
                            ]
                        ),
                    ],
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
                    }
                )
            )
            /* ->add('restaurants', EntityType::class, array(
                 'label' => 'keyword.restaurants',
                 'class' => Restaurant::class,
                 'choice_label' => function (Restaurant $restaurant) {
                     return $restaurant->getName().' ( '.$restaurant->getCode(). ' )';
                 },
                 'multiple' => true,
                 'required' => false,
                 'constraints' => array(
        //                    new NotNull(
        //                        array(
        //                            'groups' => array('transformed_product', 'non_transformed_product'),
        //                            'message' => 'null_value'
        //                        )
        //                    ),
                 )
             ))*/

            ->add(
                'dateSynchro',
                DateType::class,
                array(
                    'label' => 'item.inventory.synchro_date',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => false,
                    'constraints' => array(
                        //                    new NotNull(
                        //                        array(
                        //                            'groups' => array('transformed_product', 'non_transformed_product'),
                        //                            'message' => 'null_value'
                        //                        )
                        //                    ),
                        new Callback(
                            array(
                                'groups' => array('transformed_product', 'non_transformed_product'),
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if (!is_null($value)) {
                                        if ($value->format('Y-m-d') <= date('Y-m-d')) {
                                            $context->buildViolation('date_in_futur')->addViolation();
                                        }
                                    }
                                },
                            )
                        ),
                    ),
                )
            );

        $builder->get('productPurchased')->addModelTransformer(new ProductToIdTransformer($this->em));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => array('transformed_product', 'non_transformed_product'),
                'data_class' => ProductSoldSupervision::class,
                'translation_domain' => 'supervision',
            ]
        );
    }
}
