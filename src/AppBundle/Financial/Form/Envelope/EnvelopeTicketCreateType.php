<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 25/04/2016
 * Time: 17:13
 */

namespace AppBundle\Financial\Form\Envelope;

use AppBundle\Financial\Entity\Envelope;
use AppBundle\Staff\Form\DataTransformer\EmployeeToIdTransformer;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EnvelopeTicketCreateType extends AbstractType
{

    private $em;
    private $container;

    public function __construct(EntityManager $em, Container $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('id', HiddenType::class)
            ->add(
                'reference',
                TextType::class,
                [
                    'attr' => ['class' => 'form-control', 'maxlength' => 10],
                    'required' => true,
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
                    'constraints' => [
                        new Callback(
                            [
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    $restaurant= $this->container->get('restaurant.service')->getCurrentRestaurant();
                                    $envelope = $context->getRoot()->getData();
                                    if (!$envelope instanceof Envelope) {
                                        throw new \Exception(
                                            "Expected an envelope Object , got ".get_class(
                                                $envelope
                                            )
                                        );
                                    }
                                    if ($this->em->getRepository('Financial:Envelope')->getEnvelopeToday(
                                        $value,$restaurant
                                    ) != null) {
                                        $context->buildViolation('error.envelope_unique_source_date')
                                            ->addViolation();
                                    }
                                },
                            ]
                        ),
                    ],
                )
            )
            ->add(
                'amount',
                TextType::class,
                [
                    'required' => true,
                    'attr' => ['class' => 'form-control', 'maxlength' => 10],
                    'constraints' => [
                        new NotBlank(),
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]{1,8}([\.,][0-9]{0,2})?$/',
                                'message' => 'invalid_format',
                            )
                        ),
                        new Callback(
                            [
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    $envelope = $context->getRoot()->getData();
                                    if (!$envelope instanceof Envelope) {
                                        throw new \Exception(
                                            "Expected an envelope Object , got ".get_class(
                                                $envelope
                                            )
                                        );
                                    }
                                    $maxAmount = $this->container->get('envelope.service')->getTrMaxAmount(
                                        $envelope->getSousType()
                                    );

                                    if (floatval(
                                        str_replace(',', '.', $value) > floatval(str_replace(',', '.', $maxAmount))
                                    )) {
                                        $context->buildViolation('error.envelope_max_amount')
                                            ->addViolation();
                                    } else {
                                        if (floatval(str_replace(',', '.', $value)) <= 0) {
                                            $context->buildViolation('error.envelope_amount_is_invalid')
                                                ->addViolation();
                                        }
                                    }
                                },
                            ]
                        ),
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Envelope::class,
            ]
        );
    }
}
