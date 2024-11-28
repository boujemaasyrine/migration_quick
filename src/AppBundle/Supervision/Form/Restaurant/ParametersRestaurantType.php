<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 25/05/2016
 * Time: 14:46
 */

namespace AppBundle\Supervision\Form\Restaurant;

use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\Supervision\Service\ParameterService;
use Doctrine\ORM\EntityManager;
use function Sodium\add;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParametersRestaurantType extends AbstractType
{

    private $em;
    private $paramService;

    public function __construct(EntityManager $em, ParameterService $paramService)
    {
        $this->em = $em;
        $this->paramService = $paramService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $version=$options['version'];
        $ticketRestaurant = $this->paramService->getTicketRestaurantMethod();
        $electronicTicketRestaurant = $this->paramService->getTicketRestaurantMethod(true);
        $bankCard = $this->paramService->getBankCardMethod();
        $checkQuick=$this->paramService->getCheckQuickMethod();
        $builder
            ->add(
                'eft',
                ChoiceType::class,
                [
                    "choices" => [
                        "false" => "keyword.no",
                        "true" => "keyword.yes",
                    ],
                ]
            )
            ->add(
                'paymentMethod',
                ChoiceType::class,
                array(
                    'label' => 'restaurant_parameter.payment_method',
                    'multiple' => true,
                    'expanded' => true,
                    'choices' => [
                        PaymentMethod::REAL_CASH_TYPE => 'restaurant_parameter.real_cash',
                        PaymentMethod::CHECK_QUICK_TYPE => $version=='quick'?'restaurant_parameter.check_quick':'restaurant_parameter.check_bk',
                        PaymentMethod::FOREIGN_CURRENCY_TYPE => 'restaurant_parameter.foreign_currency',
                        PaymentMethod::BANK_CARD_TYPE => 'restaurant_parameter.bank_card',
                        PaymentMethod::TICKET_RESTAURANT_PAPER => 'restaurant_parameter.ticket_restaurant',
                        PaymentMethod::TICKET_RESTAURANT_ELECTRONIC => 'restaurant_parameter.electronic_ticket_restaurant',
                    ],
                )
            )
            ->add(
                'ticketRestaurant',
                EntityType::class,
                array(
                    'class' => PaymentMethod::class,
                    'choice_label' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'choices' => $ticketRestaurant,
                )
            )
            ->add(
                'electronicTicketRestaurant',
                EntityType::class,
                array(
                    'class' => PaymentMethod::class,
                    'choice_label' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'choices' => $electronicTicketRestaurant,
                )
            )
            ->add(
                'bankCard',
                EntityType::class,
                array(
                    'class' => PaymentMethod::class,
                    'choice_label' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'choices' => $bankCard,
                )
            )

           ->add('checkQuick',
                 EntityType::class,
           array('class'=>PaymentMethod::class,
               'choice_label'=>'label',
               'multiple'=>true,
               'expanded'=>true,
               'choices'=>$checkQuick)
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'translation_domain' => 'supervision',
                'version'=>null
            )
        );
    }
}
