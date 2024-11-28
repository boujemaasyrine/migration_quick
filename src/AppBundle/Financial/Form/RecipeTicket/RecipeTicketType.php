<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 30/03/2016
 * Time: 18:41
 */

namespace AppBundle\Financial\Form\RecipeTicket;

use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Staff\Form\DataTransformer\EmployeeToIdTransformer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

class RecipeTicketType extends AbstractType
{

    private $em;

    private $parameterService;

    public function __construct(EntityManager $em, ParameterService $parameterService)
    {
        $this->em = $em;
        $this->parameterService = $parameterService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'date',
                DateTimeType::class,
                [
                    'label' => 'date_label',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'attr' => ['class' => ''],
                    'required' => true,
                    'constraints' => array(new NotNull()),
                ]
            )
            ->add(
                'label',
                ChoiceType::class,
                array(
                    'label' => 'keyword.label',
                    'empty_value' => 'expense.entry.choose_label',
                    'choices' => $this->parameterService->getRecipeTicketLabels(false),
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
                'owner',
                TextType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'amount',
                TextType::class,
                array(
                    'label' => 'keyword.amount',
                    'attr' => array('maxlength' => 12),
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
            );

        $builder->get('owner')
            ->addModelTransformer(new EmployeeToIdTransformer($this->em));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => RecipeTicket::class,
            )
        );
    }
}
