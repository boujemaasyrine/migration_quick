<?php


namespace AppBundle\Financial\Form\Envelope;

use AppBundle\Financial\Entity\DeletedEnvelope;
use ClassesWithParents\D;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeletedEnvelopeSearchType extends AbstractType
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
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "required" => false,
                    "constraints" => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'source',
                ChoiceType::class,
                array(
                    'label' => 'envelope.source.title',
                    'required' => false,
                    'empty_value' => 'envelope.all',
                    'attr' => ['class' => 'sortable'],
                    'choices' => array(
                        DeletedEnvelope::CASHBOX_COUNTS => 'envelope.source.cashbox_counts',
                        DeletedEnvelope::WITHDRAWAL => 'envelope.source.withdrawal',
                        DeletedEnvelope::EXCHANGE_FUNDS => 'envelope.source.exchange_funds',
                        DeletedEnvelope::SMALL_CHEST => 'envelope.source.small_chest',
                    ),
                )
            )
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'label' => 'envelope.status.title',
                    'required' => false,
                    'empty_value' => 'envelope.all',
                    'attr' => ['class' => 'sortable'],
                    'choices' => array(
                        DeletedEnvelope::VERSED => 'envelope.status.versed',
                        DeletedEnvelope::NOT_VERSED => 'envelope.status.not_versed',
                    ),
                )
            )
            ->add(
                'owner',
                EntityType::class,
                array(
                    'query_builder' => function (EntityRepository $er) use ($currentRestaurant) {
                        return $er->createQueryBuilder('r')->where('r.fromCentral = false')
                            ->andWhere(':restaurant MEMBER OF r.eligibleRestaurants')
                            ->setParameter('restaurant', $currentRestaurant);
                    },
                    'label' => 'label.manager',
                    'class' => 'AppBundle\Staff\Entity\Employee',
                    'choice_label' => function ($member) {
                        return $member->getFirstName().' '.$member->getLastName();
                    },
                    'attr' => ['class' => 'sortable form-control'],
                    'empty_value' => 'envelope.search.choose_owner',
                    'required' => false,
                )
            );
        ;
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
