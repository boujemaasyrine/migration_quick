<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/01/2016
 * Time: 17:39
 */

namespace AppBundle\Financial\Form\Cashbox;

use AppBundle\Financial\Entity\CashboxBankCardContainer;
use AppBundle\Financial\Entity\CashboxCheckQuickContainer;
use AppBundle\Financial\Entity\CashboxCheckRestaurantContainer;
use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\CashboxForeignCurrencyContainer;
use AppBundle\Financial\Entity\CashboxRealCashContainer;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Form\InventorySheet\InventorySheetLineType;
use AppBundle\Staff\Entity\Employee;
use AppBundle\Staff\Repository\EmployeeRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CashboxCountType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $restaurant = $options['restaurant'];
        $builder
            ->add('id', HiddenType::class)
            ->add(
                'date',
                DateTimeType::class,
                [
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => true,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ),
                ]
            )
            ->add(
                'cashContainer',
                RealCashContainerType::class,
                [
                    'data_class' => CashboxRealCashContainer::class,
                ]
            )
            ->add(
                'checkRestaurantContainer',
                CheckRestaurantContainerType::class,
                [
                    'data_class' => CashboxCheckRestaurantContainer::class,
                ]
            )
            ->add(
                'checkQuickContainer',
                CheckQuickContainerType::class,
                [
                    'data_class' => CashboxCheckQuickContainer::class,
                ]
            )
            ->add(
                'bankCardContainer',
                BankCardContainerType::class,
                [
                    'data_class' => CashboxBankCardContainer::class,
                ]
            )
            ->add(
                'foreignCurrencyContainer',
                ForeignCurrencyContainerType::class,
                [
                    'data_class' => CashboxForeignCurrencyContainer::class,
                ]
            )
            ->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($restaurant) {
                    $form = $event->getForm();
                    $data = $event->getData();

                    /* Check we're looking at the right data/form */
                    if ($data instanceof CashboxCount) {
                        $form->add(
                            'cashier',
                            EntityType::class,
                            [
                                'query_builder' => function (EmployeeRepository $repository) use ($data, $restaurant) {
                                    /**
                                     * @var CashboxCount $data
                                     */
                                    $query = true;

                                    return $repository->findCashierThatHaveARelatedTicketAtDate(
                                        $data->getDate(),
                                        $query,
                                        $restaurant
                                    );
                                },
                                'class' => Employee::class,
                                "empty_value" => "cashbox.select_a_cashier",
                            ]
                        );
                    }
                }
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => CashboxCount::class,
                'restaurant' => null,
            ]
        );
    }
}
