<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 30/03/2016
 * Time: 18:41
 */

namespace AppBundle\Financial\Form\Expense;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Administration\Repository\ParameterRepository;
use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\Expense;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

class ExpenseType extends AbstractType
{

    private $em;
    private $parameter;

    public function __construct(EntityManager $em, ParameterService $parameter)
    {
        $this->em = $em;
        $this->parameter = $parameter;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'sousGroup',
                ChoiceType::class,
                array(
                    'label' => 'keyword.label',
                    'empty_value' => 'expense.entry.choose_label',
                    'choices' => $this->parameter->getExpenseLabels(false),
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ),
                )
            )
            ->add(
                'tva',
                TextType::class,
                array(
                    'label' => 'keyword.tva',
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+([\.,][0-9]{0,2})?$/',
                            )
                        ),
                    ),
                )
            )
            ->add(
                'comment',
                TextareaType::class,
                array(
                    'label' => 'keyword.comment',
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ),
                )
            )
            ->add(
                'amount',
                TextType::class,
                array(
                    'label' => 'amount-ttc',
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'error.null_value',
                            )
                        ),
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+([\.,][0-9]{0,2})?$/',
                            )
                        ),
                    ),

                )
            )
            ->add(
                'dateExpense',
                DateTimeType::class,
                [
                    'label' => 'date_label',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'attr' => ['class' => ''],
                    'required' => true,
                    'constraints' => array(new NotNull()),
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Expense::class,
            )
        );
    }
}
