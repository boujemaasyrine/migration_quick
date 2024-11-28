<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 29/05/2016
 * Time: 16:30
 */

namespace AppBundle\Supervision\Form\Reports;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Security\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class HourByHourType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
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
                'date',
                DateType::class,
                [
                    "format" => "dd/MM/y",
                    "label" => "keywords.date",
                    "widget" => "single_text",
                    "required" => true,
                    "constraints" => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'restaurant',
                EntityType::class,
                array(
                    'class' => Restaurant::class,
                    'placeholder' => 'choose_restaurant',
                    'choices' => $restaurants,
                    'label' => 'keywords.restaurant',
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
