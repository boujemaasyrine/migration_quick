<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 07/04/2016
 * Time: 10:13
 */

namespace AppBundle\Financial\Form\Envelope;

use AppBundle\Financial\Entity\Envelope;
use AppBundle\Staff\Form\DataTransformer\EmployeeToIdTransformer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EnvelopeType extends AbstractType
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add('owner', TextType::class)
            ->add('cashier', TextType::class)
            ->add('source', HiddenType::class)
            ->add('sourceId', HiddenType::class)
            ->add(
                'reference',
                TextType::class,
                [
                    'required' => true,
                    "constraints" => [
                        new NotBlank(),
                    ],
                ]
            )
            ->add(
                'amount',
                TextType::class,
                [
                    'required' => true,
                    'constraints' => [
                        new NotBlank(),
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]{1,8}([\.,][0-9]+)?$/',
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
                                    $totalRealAmount = $this->em->getRepository('Financial:CashboxCount')->find(
                                        $envelope->getSourceId()
                                    )->getCashContainer()->getOnlyBillsTotal();
                                    if ($totalRealAmount < $value) {
                                        $context->buildViolation('error.envelope_amount_over_cash_real')
                                            ->addViolation();
                                    }
                                    if ($envelope->getAmount() <= 0) {
                                        $context->buildViolation('error.envelope_amount_is_invalid')
                                            ->addViolation();
                                    }
                                },
                            ]
                        ),
                    ],
                ]
            );

        $builder->get('owner')
            ->addModelTransformer(new EmployeeToIdTransformer($this->em));
        $builder->get('cashier')
            ->addModelTransformer(new EmployeeToIdTransformer($this->em));
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
