<?php

namespace AppBundle\Report\Form;

use AppBundle\Report\Service\ReportCorrectionsService;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CorrectionsFormType extends AbstractType
{
    private $reportCorrectionsService;
    private $translator;

    /**
     * BrFormType constructor.
     * @param $reportCorrectionsService
     * @param $translator
     */
    public function __construct(ReportCorrectionsService $reportCorrectionsService, Translator $translator)
    {
        $this->reportCorrectionsService = $reportCorrectionsService;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentRestaurant = $this->reportCorrectionsService->getCurrentRestaurant();
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
                    'choices' => $this->reportCorrectionsService->getHoursList(),
                    'required' => false,
                )
            )
            ->add(
                'endHour',
                ChoiceType::class,
                array(
                    "label" => "keyword.to",
                    'choices' => $this->reportCorrectionsService->getHoursList(),
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
                'amountMin',
                NumberType::class,
                array(
                    'label' => 'corrections_report.amount_min',
                    'required' => false,
                )
            )
            ->add(
                'amountMax',
                NumberType::class,
                array(
                    'label' => 'corrections_report.amount_max',
                    'required' => false,
                )
            )
            ->add(
                'cashier',
                EntityType::class,
                array(
                    'query_builder' => function (EntityRepository $er) use ($currentRestaurant) {
                        return $er->createQueryBuilder('r')->where('r.fromCentral = false')->andWhere(
                            ':restaurant MEMBER OF r.eligibleRestaurants'
                        )->setParameter('restaurant', $currentRestaurant);
                    },
                    'label' => '',
                    'attr' => ['class' => 'form-control sortable'],
                    'class' => 'AppBundle\Staff\Entity\Employee',
                    'empty_value' => 'envelope.choose_cashier',
                    'required' => false,
                )
            )
//            ->add('responsible', EntityType::class, array(
//            'query_builder' => function(EntityRepository $er)use ($currentRestaurant){
//                return $er->createQueryBuilder('r')->where('r.fromCentral = false')->andWhere(':restaurant MEMBER OF r.eligibleRestaurants')
//                ->setParameter('restaurant', $currentRestaurant);
//            },
//            'label' => '',
//            'attr' => ['class' => 'form-control sortable'],
//            'class' => 'AppBundle\Staff\Entity\Employee',
//            'empty_value' => 'envelope.choose_cashier',
//            'required' => false,
//        ))
//            ->add('corrections', ChoiceType::class,array(
//                "label" => "corrections_report.correction",
//                'choices'=>array(0=>'corrections_report.before_total', 1=>'corrections_report.after_total'),
//                'empty_value' => 'corrections_report.all',
//                'required'=>false
//            ))
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }

    public function getName()
    {
        return 'app_bundle_corrections_form_type';
    }
}
