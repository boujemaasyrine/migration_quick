<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 25/04/2016
 * Time: 17:13
 */

namespace AppBundle\Financial\Form\Envelope;

use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Service\EnvelopeService;
use AppBundle\Staff\Form\DataTransformer\EmployeeToIdTransformer;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EnvelopeCreateType extends AbstractType
{

    private $em;
    private $translator;
    private $closingDate;
    private $lastClosingDate;
    private $envelopeService;
    private $currentRestaurant;

    public function __construct(EntityManager $em, Translator $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->currentRestaurant = $options['restaurant'];
        /**
         * @var EnvelopeService $envelopeService
         */
        $this->envelopeService = $options['envelopeService'];
        $this->closingDate = $options['closingDate'];
        $this->lastClosingDate = $options['lastClosingDate'];
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
                'source',
                ChoiceType::class,
                array(
                    'label' => 'envelope.source.title',
                    'required' => true,
                    'attr' => ['class' => 'form-control sortable'],
                    'empty_value' => 'envelope.choose_source',
                    'choices' => array(
                        Envelope::CASHBOX_COUNTS => 'envelope.source.cashbox_counts',
                        Envelope::WITHDRAWAL => 'envelope.source.withdrawal',
                        Envelope::EXCHANGE_FUNDS => 'envelope.source.exchange_funds',
                        Envelope::SMALL_CHEST => 'envelope.source.small_chest',
                        Envelope::CASHBOX_FUNDS => 'envelope.source.cashbox_funds',
                    ),
                )
            )
            ->add(
                'cashier',
                EntityType::class,
                array(
                    'query_builder' => function (EntityRepository $er)  {
                        return $er->createQueryBuilder('r')->where('r.fromCentral = false')
                            ->andWhere('r.deleted = false')
                            ->andWhere(':restaurant MEMBER OF r.eligibleRestaurants')
                            ->andWhere('r.wyndId is not null')
                            ->setParameter('restaurant', $this->currentRestaurant);
                    },
                    'label' => 'label.member',
                    'attr' => ['class' => 'form-control sortable'],
                    'class' => 'AppBundle\Staff\Entity\Employee',
                    'empty_value' => 'envelope.choose_cashier',
                    'required' => true,
                )
            )
            ->add(
                'amount',
                TextType::class,
                [
                    'required' => true,
                    'attr' => ['class' => 'form-control force-modulo-5', 'maxlength' => 10],
                    'constraints' => [
                        new NotBlank(),
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]{1,8}([\.,][0-9]+)?$/',
                                'message' => 'invalid_format',
                            )
                        ),
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                   /* if ($value != null && ($value % 5 > 0 or ($value - intval($value)) != 0)) {
                                        $context->buildViolation($this->translator->trans('modulo_five'))->addViolation();
                                        return;
                                    } */
                                    $root = $context->getRoot();
                                    $envelope = $root->getData();
                                    $source = $envelope->getSource();
                                    if ($source == Envelope::WITHDRAWAL) {
                                        $wamount = $this->envelopeService->calculateTotalAmountOfWithdrwals($this->currentRestaurant, $this->closingDate);
                                        $eamount=$this->envelopeService->calculateTotalAmountOfEnvelopeSourceWithdrawalOfClosing($this->currentRestaurant,$this->lastClosingDate);
                                        $maxAmount=$wamount-$eamount;
                                        if ($maxAmount < $value)
                                            $context->buildViolation($this->translator->trans('envelope.error_amount_envelope_more_than_amount_withdrwal_without_envelope',
                                                ['%wamount%' => $maxAmount]))->addViolation();
                                    }

                                },
                            )
                        ),
                    ],
                ]
            );

        $builder->get('cashier')
            ->addModelTransformer(new EmployeeToIdTransformer($this->em));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Envelope::class,
                'restaurant' => null,
                'closingDate' => new \DateTime('now'),
                'lastClosingDate' => new \DateTime('now'),
                'envelopeService' => null
            ]
        );
    }
}
