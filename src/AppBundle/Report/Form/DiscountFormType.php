<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 10/10/2016
 * Time: 09:49
 */
namespace AppBundle\Report\Form;

use AppBundle\Report\Service\ReportDiscountService;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DiscountFormType extends AbstractType
{

    private $reportDiscountService;

    public function __construct(ReportDiscountService $reportDiscountService)
    {
        $this->reportDiscountService = $reportDiscountService;
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
                'choices'=>$this->reportDiscountService->getHoursList(),
                'required'=>false
            ))
            ->add('endHour', ChoiceType::class, array(
                "label"=>"keyword.to",
                'choices'=>$this->reportDiscountService->getHoursList(),
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
                'label'=>'discount_report.invoice_number',
                'required'=>false
            ))
            ->add('discountPerCentMin', NumberType::class, array(
                'label'=>'discount_report.discount_min',
                'required'=>false
            ))
            ->add('discountPerCentMax', NumberType::class, array(
                'label'=>'discount_report.discount_max',
                'required'=>false,
                "constraints" => [
                    new Callback(array(
                        'callback'=> function($value,ExecutionContextInterface $context){
                            if ($value === null){
                                return ;
                            }
                            $rootData = $context->getRoot()->getData();

                            $discountPerCentMin = $rootData['discountPerCentMin'];
                            if ($discountPerCentMin === null){
                                return ;
                            }

                            if ($discountPerCentMin > $value){
                                $context->buildViolation('Cette valeur doit être suppérieur à la valeur discount Min')->addViolation();
                            }

                        }
                    ))
                ]
            ))

            ->add('cashier', EntityType::class, array(
                'query_builder' => function(EntityRepository $er) use ($currentRestaurant){
                    return $er->createQueryBuilder('r')->where('r.fromCentral = false')
                        ->andWhere(':restaurant MEMBER OF r.eligibleRestaurants')
                        ->andWhere('r.wyndId is not null')
                        ->setParameter('restaurant', $currentRestaurant)
                        ->orderBy('r.firstName, r.lastName');
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
        return 'app_bundle_discount_form_type';
    }
}
