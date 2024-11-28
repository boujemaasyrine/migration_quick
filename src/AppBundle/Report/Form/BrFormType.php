<?php

namespace AppBundle\Report\Form;

use AppBundle\Report\Service\ReportBrService;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class BrFormType extends AbstractType
{
    private $reportBrService;
    private $translator;
    /**
     * BrFormType constructor.
     * @param $reportBrService
     * @param $translator
     */
    public function __construct( ReportBrService $reportBrService, Translator $translator)
    {
        $this->reportBrService = $reportBrService;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

            ->add('startDate', DateType::class, [
                "label" => "keyword.from",
                "format" => "dd/MM/y",
                "widget" => "single_text",
                "required" => true,
                "constraints" => [
                    new NotNull()
                ]
            ])
            ->add('endDate', DateType::class, [
                "label" => "keyword.to",
                "format" => "dd/MM/y",
                "widget" => "single_text",
                "required" => true,
                "constraints" => [
                    new NotNull(),
                    new Callback(array(
                        'callback'=> function($value,ExecutionContextInterface $context){
                            if ($value === null){
                                return ;
                            }

                            if (! $value instanceof \DateTime){
                                return ;
                            }

                            $rootData = $context->getRoot()->getData();

                            $startDate = $rootData['startDate'];
                            if ($startDate === null){
                                return ;
                            }

                            if (! $startDate instanceof \DateTime){
                                return ;
                            }

                            if (Utilities::compareDates($startDate,$value)>0){
                                $context->buildViolation('startdate_inf_enddate')->addViolation();
                            }

                        }
                    ))
                ]
            ])

            ->add('startHour', ChoiceType::class,array(
                "label" => "keyword.from",
                'choices'=>$this->reportBrService->getHoursList(),
                'required'=>false
            ))
            ->add('endHour', ChoiceType::class, array(
                "label"=>"keyword.to",
                'choices'=>$this->reportBrService->getHoursList(),
                'required'=>false,
                "constraints" => [
                    new Callback(array(
                        'callback'=> function($value,ExecutionContextInterface $context){
                            if ($value === null){
                                return ;
                            }
                            $rootData = $context->getRoot()->getData();

                            $startHour = $rootData['startHour'];
                            if ($startHour === null){
                                return ;
                            }

                            if ($startHour > $value){
                                $context->buildViolation('startHour_inf_endHour')->addViolation();
                            }

                        }
                    ))
                ]
            ))
            ->add('amountMin', NumberType::class, array(
                'label'=>'br_report.amount_br_min',
                'required'=>false
            ))
            ->add('amountMax', NumberType::class, array(
                'label'=>'br_report.amount_br_max',
                'required'=>false
            ))
            ->add('ticketMin', NumberType::class, array(
                'label'=>'br_report.amount_ticket_min',
                'required'=>false
            ))
            ->add('ticketMax', NumberType::class, array(
                'label'=>'br_report.amount_ticket_max',
                'required'=>false
            ))
            ->add('cashier', ChoiceType::class, array(
                'choices' => $this->reportBrService->getBeneficiaryNamesList(),
                'label' => '',
                'attr' => ['class' => 'form-control sortable'],
                'empty_value' => 'envelope.choose_cashier',
                'required' => false,
            ));

    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }

    public function getName()
    {
        return 'app_bundle_br_form_type';
    }
}
