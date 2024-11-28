<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 11/10/2016
 * Time: 09:49
 */
namespace AppBundle\Report\Form;

use AppBundle\Report\Service\ReportCancellationService;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CancellationFormType extends AbstractType
{

    private $reportCancellationService;

    public function __construct(ReportCancellationService $reportCancellationService)
    {
        $this->reportCancellationService = $reportCancellationService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentRestaurant = $options['restaurant'];
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
                'choices'=>$this->reportCancellationService->getHoursList($currentRestaurant),
                'required'=>false
            ))
            ->add('endHour', ChoiceType::class, array(
                "label"=>"keyword.to",
                'choices'=>$this->reportCancellationService->getHoursList($currentRestaurant),
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
            ->add('InvoiceNumber', TextType::class,array(
                'label'=>'cancellation_report.invoice_number',
                'required'=>false
            ))
            ->add('cashier', EntityType::class, array(
                'query_builder' => function(EntityRepository $er) use ($currentRestaurant){
                    return $er->createQueryBuilder('r')->where('r.fromCentral = false')
                        ->andWhere(':restaurant MEMBER OF r.eligibleRestaurants')
                        ->setParameter('restaurant', $currentRestaurant);
                },
                'label' => 'label.member',
                'attr' => ['class' => 'form-control sortable'],
                'class' => 'AppBundle\Staff\Entity\Employee',
                'empty_value' => 'envelope.choose_cashier',
                'required' => false,
            ));

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => null,
                'restaurant' => null
            )
        );
    }

    public function getName()
    {
        return 'app_bundle_cancellation_form_type';
    }
}
