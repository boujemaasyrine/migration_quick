<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 07/04/2016
 * Time: 10:13
 */

namespace AppBundle\Financial\Form\Chest;

use AppBundle\Financial\Entity\Envelope;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class ChestCountsSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentRestaurant = $options['restaurant'];

        $builder
            ->add(
                'startDate',
                DateType::class,
                [
                    "label" => 'envelope.filter_labels.from',
                    "attr" => ['class' => 'datepicker'],
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "required" => false,
                    "constraints" => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'endDate',
                DateType::class,
                [
                    "label" => 'envelope.filter_labels.to',
                    "attr" => ['class' => 'datepicker'],
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "required" => false,
                    "constraints" => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'owner',
                EntityType::class,
                array(
                    'label' => 'label.manager',
                    'class' => 'AppBundle\Staff\Entity\Employee',
                    'choice_label' => function ($member) {
                        return $member->getFirstName().' '.$member->getLastName();
                    },
                    'attr' => ['class' => 'sortable form-control'],
                    'empty_value' => 'cashbox.listing.search.choose_owner',
                    'required' => false,
                    'query_builder' => function (EntityRepository $repo) use ($currentRestaurant) {
                        return $repo->createQueryBuilder('e')
                            ->where(':restaurant MEMBER OF e.eligibleRestaurants')
                            ->setParameter('restaurant', $currentRestaurant);
                    },
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'restaurant' => null,
            )
        );
    }
}
