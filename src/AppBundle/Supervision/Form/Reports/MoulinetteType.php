<?php
/**
 * Created by PhpStorm.
 * User: bchebbi
 * Date: 21/05/2019
 * Time: 09:29
 */

namespace AppBundle\Supervision\Form\Reports;


use AppBundle\ToolBox\Utils\Utilities;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MoulinetteType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentDay = new \DateTime('NOW');
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
                "data" => $currentDay
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
                    "data" => $currentDay
                ]
            )
            ->add('type', ChoiceType::class, array(
                'choices' => array(
                    'Chiffre d\'affaire' => 0,
                    'Bons' => 1
                ),
                'data' => 'Chiffre d\'affaire',
                "choices_as_values" => true,
                "multiple" => false,
                "expanded" => false,
                "required" => true,
                "constraints" => [
                    new NotNull()
                ]
            ))
            ->add(
                'restaurants',
                EntityType::class,
                array(
                    'label' => 'Restaurants',
                    'class' => Restaurant::class,
                    'choice_label' => 'name',
                    'required' => false,
                    'multiple' => true,
                    'query_builder' => function(EntityRepository $e)
                    {
                        return $e->getOpenedRestaurantsQuery();
                    },
                )
            )
           ;


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