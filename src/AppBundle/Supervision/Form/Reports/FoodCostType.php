<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 18/09/2018
 * Time: 11:12
 */

namespace AppBundle\Supervision\Form\Reports;


use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class FoodCostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'startDate',
            DateType::class,
            [
                "format"      => "dd/MM/y",
                "widget"      => "single_text",
                "required"    => true,
                "constraints" => [
                    new NotNull(),
                ],
            ]
        )
            ->add(
                'endDate',
                DateType::class,
                [
                    "format"      => "dd/MM/y",
                    "widget"      => "single_text",
                    "required"    => true,
                    "constraints" => [
                        new NotNull(),
                        new Callback(
                            array(
                                'callback' => function (
                                    $value,
                                    ExecutionContextInterface $context
                                ) {
                                    if ($value === null) {
                                        return;
                                    }

                                    if (!$value instanceof \DateTime) {
                                        return;
                                    }

                                    $rootData = $context->getRoot()->getData();

                                    $startDate = $rootData['startDate'];
                                    if ($startDate === null) {
                                        return;
                                    }

                                    if (!$startDate instanceof \DateTime) {
                                        return;
                                    }

                                    if (Utilities::compareDates(
                                            $startDate,
                                            $value
                                        ) > 0
                                    ) {
                                        $context->buildViolation(
                                            'Superieur à la date de début'
                                        )->addViolation();
                                    }
                                },
                            )
                        ),
                    ],
                ]
            )
           ->add(
                'restaurants',
                EntityType::class,
                [
                    'required'=>false,

                    'class'        => Restaurant::class,
                    'query_builder' => function(EntityRepository $er){

                     return $er->createQueryBuilder('r')
                               ->where('r.active=:true')
                               ->setParameter('true',true);


                    },
                    'choice_label' => function (Restaurant $restaurant) {
                        return $restaurant->getName().' ('.$restaurant->getCode(
                            ).')';
                    },
                    'multiple'     => true
                ]
            );


    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'translation_domain' => 'supervision',
            )
        );
    }

}