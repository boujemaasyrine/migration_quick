<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/01/2016
 * Time: 17:39
 */

namespace AppBundle\Supervision\Form\Items;

use AppBundle\Merchandise\Entity\Division;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Merchandise\Entity\SubSoldingCanal;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\Supervision\Entity\RecipeSupervision;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RecipeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add(
                'soldingCanal',
                EntityType::class,
                [
                    'class' => SoldingCanal::class,
                    'choice_label' => 'label',
                    'attr' => array('class' => 'form-control'),
                    'required' => false,
                    'query_builder' => function (EntityRepository $em) {
                        return $em->createQueryBuilder('s')
                            ->where('s.type = :destination')
                            ->setParameter('destination', SoldingCanal::DESTINATION)
                            ->orderBy('s.default', 'DESC');
                    },
                    'empty_value' => false,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'groups' => 'transformed_product',
                            )
                        ),
                    ),
                    'choice_translation_domain' => true,
                ]
            )
            ->add(
                'subSoldingCanal',
                EntityType::class,
                [
                    'class' => SubSoldingCanal::class,
                    'choice_label' => 'label',
                    'attr' => array('class' => 'form-control'),
                    'required' => false
                ]
            )

            ->add(
                'recipeLines',
                CollectionType::class,
                [
                    'entry_type' => RecipeLineType::class,
                    'prototype_name' => '__recipe_line__',
                    'allow_add' => true,
                    'allow_delete' => true,
                    'mapped' => true,
                    'by_reference' => false,
                    'prototype' => true,
                    "constraints" => [
                        //                    new Valid(),
                        new Callback(
                            [
                                'groups' => 'transformed_product',
                                'callback' => function ($object, ExecutionContextInterface $context) {
                                    $productSold = $context->getRoot()->getData();
                                    if (!$productSold instanceof ProductSoldSupervision) {
                                        throw new \Exception(
                                            "Expected a ProductSold Object , got ".get_class($productSold)
                                        );
                                    }
                                    if ($productSold->getType() === ProductSoldSupervision::TRANSFORMED_PRODUCT) {
                                        $test = false;
                                        foreach ($productSold->getRecipes() as $recipe) {
                                            /**
                                             * @var Recipe $recipe
                                             */
                                            if (count($recipe->getRecipeLines()) > 0) {
                                                $test = true;
                                                break;
                                            }
                                        }
//                                        if (!$test) {
//                                            $context->buildViolation(
//                                                "product_sold.recipe_must_have_at_least_one_recipe_line"
//                                            )->addViolation();
//                                        }
                                    }
                                },
                            ]
                        ),
                    ],
                    'error_bubbling' => false,
                    'entry_options' => [
                        'error_bubbling' => false,
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => array('transformed_product', 'non_transformed_product'),
                'data_class' => RecipeSupervision::class,
                'translation_domain' => 'supervision',
            ]
        );
    }
}
