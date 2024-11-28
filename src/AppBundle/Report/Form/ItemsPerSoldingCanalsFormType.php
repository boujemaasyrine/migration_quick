<?php

namespace AppBundle\Report\Form;

use AppBundle\Report\Service\ReportItemsPerSoldingCanalsService;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ItemsPerSoldingCanalsFormType extends AbstractType
{
    private $reportService;
    private $translator;

    /**
     * ItemsPerSoldingCanalsFormType constructor.
     * @param $reportService
     * @param $translator
     */
    public function __construct(ReportItemsPerSoldingCanalsService $reportService, Translator $translator)
    {
        $this->reportService = $reportService;
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
                    new NotNull()
                ]
            ])
            ->add('startHour', ChoiceType::class,array(
                "label" => "keyword.from",
                'choices'=>$this->reportService->getHoursList(),
                'required'=>false
            ))
            ->add('endHour', ChoiceType::class, array(
                "label"=>"keyword.to",
                'choices'=>$this->reportService->getHoursList(),
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
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }

    public function getName()
    {
        return 'app_bundle_items_per_solding_canals_form_type';
    }
}
