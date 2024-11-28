<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Report\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PortionControlFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentRestaurant = null;
        if ($options["currentRestaurant"]) {
            $currentRestaurant = $options["currentRestaurant"];
        }
        $builder
            ->add(
                'startDate',
                DateType::class,
                [
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "required" => true,
                    "constraints" => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'endDate',
                DateType::class,
                [
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "required" => true,
                    "constraints" => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'selection',
                ChoiceType::class,
                [
                    "choices" => [
                        "portion_control.filter_values.all_items" => "all_items",
                        "portion_control.filter_values.inventory_done" => "inventory_done",
                        "portion_control.filter_values.error" => "error",
                    ],
                    "choices_as_values" => true,
                    "multiple" => false,
                    "expanded" => false,
                    "required" => true,
                    "constraints" => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'threshold',
                TextType::class,
                [
                    "required" => true,
                    "constraints" => [
                        new NotNull(),
                        new Regex(
                            array(
                                'pattern' => '/^[-]?[0-9]+([,\.]{1}[0-9]+)?$/',
                            )
                        ),
                    ],
                ]
            )
            ->add(
                'code',
                TextType::class,
                [
                    "required" => false,
                ]
            )
            ->add(
                'name',
                TextType::class,
                [
                    "required" => false,
                ]
            )
            ->add(
                'category',
                EntityType::class,
                [
                    'expanded' => false,
                    'multiple' => true,
                    "required" => false,
                    'class' => 'AppBundle\Merchandise\Entity\ProductCategories',
                    'query_builder' => function (EntityRepository $repo) use ($currentRestaurant) {
                        return $repo->createQueryBuilder('pc')
                            ->join("pc.products", "pr")
                            ->where("pr.originRestaurant = :restaurant")
                            ->setParameter("restaurant", $currentRestaurant);
                    },
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'currentRestaurant' => null,
            )
        );
    }
}
