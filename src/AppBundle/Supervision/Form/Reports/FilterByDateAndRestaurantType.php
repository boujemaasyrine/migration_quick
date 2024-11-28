<?php

namespace AppBundle\Supervision\Form\Reports;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Utils\Utilities;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\NotNull;

class FilterByDateAndRestaurantType extends AbstractType
{

    private $currentUser;

    public function __construct(UserInterface $currentUser)
    {
        $this->currentUser = $currentUser;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $restaurants = $this->currentUser->getEligibleRestaurants()->toArray();
        usort(
            $restaurants,
            function (Restaurant $r1, Restaurant $r2) {
                if ($r1->getName() < $r2->getName()) {
                    return -1;
                }

                return 1;
            }
        );

        $builder->add(
            'beginDate',
            DateType::class,
            array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'constraints' => array(
                    new NotNull(),
                ),
            )
        )
            ->add(
                'endDate',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => array(
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
                    ),
                )
            )
            ->add(
                'restaurant',
                EntityType::class,
                array(
                    'class' => Restaurant::class,
                    'choices' => $restaurants,
                    'label' => 'keyword.restaurants',
                    'multiple' => true,
                    'required' => false,
                    'choice_label' => function (Restaurant $r) {
                        return $r->getName() . " (" . $r->getCode() . ")";
                    },
                    'placeholder' => 'Veuillez choisir un restaurant'
                )
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