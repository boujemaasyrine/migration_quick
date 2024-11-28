<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 07/04/2016
 * Time: 10:13
 */

namespace AppBundle\Financial\Form\Envelope;

use AppBundle\Financial\Entity\Envelope;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnvelopeTicketSearchType extends AbstractType
{

    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentRestaurant = $options['restaurant'];
        $builder
            ->add(
                'startDate',
                DateType::class,
                [
                    "label" => 'envelope.filter_labels.from',
                    'attr' => ['class' => 'datepicker'],
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
                    'attr' => ['class' => 'datepicker'],
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "required" => false,
                    "constraints" => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'sousType',
                ChoiceType::class,
                array(
                    'label' => 'envelope.source.title',
                    'required' => true,
                    'attr' => ['class' => 'form-control sortable'],
                    'empty_value' => 'envelope.choose_source',
                    'choices' => $this->container->get('paremeter.service')->getTicketRestaurantTypes(false),
                )
            )
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'label' => 'envelope.status.title',
                    'attr' => ['class' => 'form-control sortable'],
                    'required' => false,
                    'empty_value' => 'envelope.all',
                    'choices' => array(
                        Envelope::VERSED => 'envelope.status.versed',
                        Envelope::NOT_VERSED => 'envelope.status.not_versed',
                    ),
                )
            )
            ->add(
                'owner',
                EntityType::class,
                array(
                    'query_builder' => function (EntityRepository $er) use ($currentRestaurant) {
                        return $er->createQueryBuilder('r')->where('r.fromCentral = false')
                            ->andWhere(":restaurant MEMBER OF r.eligibleRestaurants")
                            ->setParameter('restaurant', $currentRestaurant);
                    },
                    'label' => 'label.manager',
                    'attr' => ['class' => 'form-control sortable'],
                    'class' => 'AppBundle\Staff\Entity\Employee',
                    'choice_label' => function ($member) {
                        return $member->getFirstName().' '.$member->getLastName();
                    },
                    'empty_value' => 'envelope.search.choose_owner',
                    'required' => false,
                )
            );
        ;
    }

    public function getName()
    {
        return 'envelope_search';
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
