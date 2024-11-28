<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 27/05/2016
 * Time: 16:25
 */

namespace AppBundle\Supervision\Form\Reports;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Security\Entity\User;
use AppBundle\Supervision\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DailyResultsType extends AbstractType
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

                                    $startDate = $rootData['startDate'];
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
                'compareStartDate',
                DateType::class,
                [
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "required" => false,
                ]
            )
            ->add(
                'compareEndDate',
                DateType::class,
                [
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "required" => false,
                ]
            )
            ->add(
                'restaurants',
                EntityType::class,
                array(
                    'class' => Restaurant::class,
                    'choice_label' => function (Restaurant $restaurant) {
                        return $restaurant->getName().' ('.$restaurant->getCode().')';
                    },
                    'choices' => $restaurants,
                    'multiple' => true,
                    'constraints' => array(
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if (count($value) == 0 || $value === null) {
                                        $context->buildViolation('null_value')
                                            ->addViolation();
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
                'user' => User::class,
                'translation_domain' => 'supervision',
            )
        );
    }
}
