<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/01/2016
 * Time: 17:39
 */

namespace AppBundle\Financial\Form\Chest;

use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\CashboxRealCashContainer;
use AppBundle\Financial\Entity\ChestSmallChest;
use AppBundle\Financial\Form\Cashbox\BankCardType;
use AppBundle\Financial\Form\Cashbox\CheckQuickType;
use AppBundle\Financial\Form\Cashbox\CheckRestaurantType;
use AppBundle\Financial\Form\Cashbox\ForeignCurrencyType;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Form\InventorySheet\InventorySheetLineType;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SmallChestType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add(
                'totalCash',
                TextType::class,
                [
                    'constraints' => [
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+([\.,][0-9]+)?$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ],
                    "error_bubbling" => true,
                    "required" => false,
                ]
            )
            ->add(
                'foreignCurrencyCounts',
                CollectionType::class,
                [
                    'entry_type' => ForeignCurrencyType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'mapped' => true,
                    'by_reference' => false,
                ]
            )
            ->add(
                'ticketRestaurantCounts',
                CollectionType::class,
                [
                    'entry_type' => CheckRestaurantType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'mapped' => true,
                    'by_reference' => false,
                ]
            )
            ->add(
                'bankCardCounts',
                CollectionType::class,
                [
                    'entry_type' => BankCardType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'mapped' => true,
                    'by_reference' => false,
                ]
            )
            ->add(
                'checkQuickCounts',
                CollectionType::class,
                [
                    'entry_type' => CheckQuickType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'mapped' => true,
                    'by_reference' => false,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => ChestSmallChest::class,
            ]
        );
    }
}
