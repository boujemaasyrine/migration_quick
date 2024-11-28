<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 29/05/2016
 * Time: 18:27
 */

namespace AppBundle\Supervision\Form\Reports;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Security\Entity\User;
use AppBundle\Supervision\Utils\Utilities;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MarginFoodCostType extends AbstractType
{

    function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * @var User $user
         */
        $user = $options['user'];
        $restaurants = $user->getEligibleRestaurants();
        //        foreach ($EligibleRestaurants as $restaurant){
        //            /**
        //             * @var Restaurant $restaurant
        //             */
        //            if (!is_null($restaurant->getLastPingTime())){
        //                $restaurants[] = $restaurant;
        //            }
        //        }

        $builder
            ->add(
                'beginDate',
                DateType::class,
                [
                    "format" => "dd/MM/y",
                    "label" => "keywords.from",
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
                    "label" => "keywords.to",
                    "widget" => "single_text",
                    "required" => true,
                    "constraints" => [
                        new NotNull(),
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if ($value === null) {
                                        return;
                                    }

                                    if (!$value instanceof \DateTime) {
                                        return;
                                    }

                                    $rootData = $context->getRoot()->getData();

                                    $startDate = $rootData['beginDate'];
                                    if ($startDate === null) {
                                        return;
                                    }

                                    if (!$startDate instanceof \DateTime) {
                                        return;
                                    }

                                    if (Utilities::compareDates($startDate, $value) > 0) {
                                        $context->buildViolation('Superieur à la date de début')->addViolation();
                                    }
                                },
                            )
                        ),
                    ],
                ]
            )
            ->add(
                'restaurant',
                EntityType::class,
                array(
                    'class' => Restaurant::class,
                    'choices' => $restaurants,
                    'label' => 'keywords.restaurant',
                    'empty_value' => 'choose_restaurant',
                    'choice_label' => function (Restaurant $restaurant) {
                        return $restaurant->getName().' ('.$restaurant->getCode().')';
                    },
                    "required" => true,
                    "constraints" => [
                        new NotNull(),
                    ],
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'user' => User::class,
                'translation_domain' => 'supervision',
            )
        );
    }
}
