<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/01/2016
 * Time: 17:39
 */

namespace AppBundle\Financial\Form\Cashbox;

use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\CashboxRealCash;
use AppBundle\Financial\Entity\CashboxRealCashContainer;
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

class RealCashContainerType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add('allAmount', CheckboxType::class, [])
            ->add(
                'totalAmount',
                TextType::class,
                [
                    'constraints' => [
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]{1,4}([\.,][0-9]+)?$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ],
                    "required" => false,
                    'empty_data' => 0
                ]
            )
            ->add(
                'change',
                TextType::class,
                [
                    'constraints' => [
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]{1,4}([\.,][0-9]+)?$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ],
                    "required" => false,
                ]
            )
            ->add(
                'billOf100',
                TextType::class,
                [
                    'required' => false,
                    'constraints' => [
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]{1,3}?$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ],
                ]
            )
            ->add(
                'billOf50',
                TextType::class,
                [
                    'required' => false,
                    'constraints' => [
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]{1,4}$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ],
                ]
            )
            ->add(
                'billOf20',
                TextType::class,
                [
                    'required' => false,
                    'constraints' => [
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]{1,4}?$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ],
                ]
            )
            ->add(
                'billOf10',
                TextType::class,
                [
                    'required' => false,
                    'constraints' => [
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]{1,4}$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ],
                ]
            )
            ->add(
                'billOf5',
                TextType::class,
                [
                    'required' => false,
                    'constraints' => [
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]{1,4}?$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => CashboxRealCashContainer::class,
            ]
        );
    }
}
