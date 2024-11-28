<?php

namespace AppBundle\Report\Form;

use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Report\Service\ReportTicketsService;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TicketsFormType extends AbstractType
{
    private $reportTicketsService;

    public function __construct(ReportTicketsService $reportTicketsService)
    {
        $this->reportTicketsService = $reportTicketsService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentRestaurant = $options['restaurant'];
        $builder
            ->add(
                'startDate',
                DateType::class,
                [
                    "label" => "keyword.from",
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
                    "label" => "keyword.to",
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
                                        $context->buildViolation('startdate_inf_enddate')->addViolation();
                                    }
                                },
                            )
                        ),
                    ],
                ]
            )
            ->add(
                'startHour',
                ChoiceType::class,
                array(
                    "label" => "keyword.from",
                    'choices' => $this->reportTicketsService->getHoursList($currentRestaurant),
                    'required' => false,
                )
            )
            ->add(
                'endHour',
                ChoiceType::class,
                array(
                    "label" => "keyword.to",
                    'choices' => $this->reportTicketsService->getHoursList($currentRestaurant),
                    'required' => false,
                    "constraints" => [
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if ($value === null) {
                                        return;
                                    }
                                    $rootData = $context->getRoot()->getData();

                                    $startHour = $rootData['startHour'];
                                    if ($startHour === null) {
                                        return;
                                    }

                                    if ($startHour > $value) {
                                        $context->buildViolation('startHour_inf_endHour')->addViolation();
                                    }

                                },
                            )
                        ),
                    ],
                )
            )
            ->add(
                'startInvoiceNumber',
                TextType::class,
                array(
                    'label' => 'tickets_report.invoiceFrom',
                    'required' => false,
                )
            )
            ->add(
                'endInvoiceNumber',
                TextType::class,
                array(
                    'label' => 'tickets_report.invoiceTo',
                    'required' => false,
                )
            )
            ->add(
                'solding_canal',
                ChoiceType::class,
                array(
                    'label' => 'tickets_report.saleCanal',
                    'multiple' => true,
                    'choices' => array('Eat In' => 'EatIn', 'Take Out' => "TakeOut","Drive"=>"Drive","Delivery"=>"Delivery","Kiosk IN"=>"KioskIN","Kiosk OUT"=>"KioskOut","E-ordering IN"=>"e_ordering_in","E-ordering OUT"=>"e_ordering_out"),
                    'choices_as_values' => true,
                    'attr' => ['class' => 'form-control sortable'],
                    'required' => false,
                )
            )
            ->add(
                'paymentMethod',
                EntityType::class,
                array(
                    'label' => 'tickets_report.paymentMethod',
                    'multiple' => true,
                    'class' => PaymentMethod::class,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('p')->where('p.type != :type')
                            ->setParameter('type', PaymentMethod::FOREIGN_CURRENCY_TYPE);
                    },
                    'choice_label' => function ($paymentMethod) {
                        return $paymentMethod->getLabel();
                    },
                    'attr' => ['class' => 'form-control sortable'],
                    'required' => false,
                )
            )
            ->add(
                'amountMin',
                NumberType::class,
                array(
                    'label' => 'tickets_report.amountMin',
                    'required' => false,
                )
            )
            ->add(
                'amountMax',
                NumberType::class,
                array(
                    'label' => 'tickets_report.amountMax',
                    'required' => false,
                    "constraints" => [
                        new Callback(array(
                            'callback'=> function($value,ExecutionContextInterface $context){
                                if ($value === null){
                                    return ;
                                }
                                $rootData = $context->getRoot()->getData();

                                $amountMin = $rootData['amountMin'];
                                if ($amountMin === null){
                                    return ;
                                }

                                if ($amountMin > $value){
                                    $context->buildViolation('Cette valeur doit être suppérieur à la valeur Min')->addViolation();
                                }

                            }
                        ))
                    ]
                )
            )
            ->add(
                'cashier',
                EntityType::class,
                array(
                    'query_builder' => function (EntityRepository $er) use ($currentRestaurant) {
                        return $er->createQueryBuilder('r')->where('r.fromCentral = false')
                            ->andWhere(':restaurant MEMBER OF r.eligibleRestaurants')
                            ->orderBy('r.firstName', 'asc')
                            ->setParameter('restaurant', $currentRestaurant);
                    },
                    'label' => 'label.member',
                    'attr' => ['class' => 'form-control sortable'],
                    'class' => 'AppBundle\Staff\Entity\Employee',
                    'empty_value' => 'envelope.choose_cashier',
                    'required' => false,
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

    public function getBlockPrefix()
    {
        return 'app_bundle_tickets_form_type';
    }
}
