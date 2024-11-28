<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Report\Form;

use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\OrderLine;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Form\OrderLineType;
use AppBundle\Merchandise\Repository\ProductPurchasedRepository;
use AppBundle\Merchandise\Repository\ProductRepository;
use AppBundle\Merchandise\Repository\RestaurantRepository;
use AppBundle\Security\Entity\Role;
use AppBundle\Supervision\Entity\ProductSupervision;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class StockControlFilterType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $products = null;
        $currentDay= new \DateTime('now');
        if ($options["currentRestaurant"]) {
            $currentRestaurant = $options["currentRestaurant"];

        }
        $data = array(

            'currentDay' => $currentDay,

        );

//add(
//            'startDate',
//            DateType::class,
//            [
//                "format" => "dd/MM/y",
//                "widget" => "single_text",
//                "required" => true,
//                "constraints" => [
//                    new NotNull(),
//                ],
//            ]
//        )
        $builder->add(
            'currentDay',
         \Symfony\Component\Form\Extension\Core\Type\DateTimeType::class,
            [
                "disabled" => true,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'data' => new \DateTime()
            ]
        )
            ->add(
                'restaurants',
                EntityType::class,
                [
                    'required' => false,

                    'class' => Restaurant::class,
                    'query_builder' => function (EntityRepository $er) {

                        return $er->createQueryBuilder('r')
                            ->where('r.active=:true')
                            ->setParameter('true', true);


                    },
                    'choice_label' => function (Restaurant $restaurant) {
                        return $restaurant->getName() . ' (' . $restaurant->getCode() . ')';
                    },
                    'multiple' => true
                ]
            )

            ->add(
                'products',
                EntityType::class,
                [

                    'class' => ProductSupervision::class,
                    'query_builder' => function (EntityRepository $er) {

                        return $er->createQueryBuilder('p')
                           ->select('p')
                           // ->leftJoin('Supervision:ProductPurchasedSupervision', 'pp', 'WITH', 'pp.productDiscr = p.productDiscr')
                            ->where('p.active = :t and p INSTANCE OF :purchased ')
                            ->setParameter('t',true)
                            ->setParameter('purchased','purchased');
                    },
                    'choice_label' => function (ProductSupervision $product) {
                        return $product->getName() . ' (' . $product->getExternalId() . ')' ;
                    },
                    'multiple' => true,
                    'required' => true,
                    'empty_data' => function (FormInterface $form) {
                        return '';
                    },
                    'invalid_message' => 'null_value',
                    'constraints' => array(
                        new NotBlank(
                            array(
                                'message' => 'null_value',
                            )
                        )
                    ),
                    ]
            );

  }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'currentRestaurant' => 'null',
                'currentDay' => null
            )
        );
    }
}
