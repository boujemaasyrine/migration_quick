<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 31/03/2016
 * Time: 10:50
 */

namespace AppBundle\Financial\Form\Expense;

use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\Expense;
use AppBundle\Financial\Repository\ExpenseRepository;
use AppBundle\Financial\Service\ExpenseService;
use AppBundle\Security\Entity\Role;
use AppBundle\Staff\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use AppBundle\Administration\Entity\Parameter;
use AppBundle\Administration\Repository\ParameterRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExpenseSearchType extends AbstractType
{

    private $em;
    private $translator;
    private $paramService;
    private $expenseService;

    public function __construct(
        EntityManager $em,
        Translator $translator,
        ParameterService $paramService,
        ExpenseService $expenseService
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->paramService = $paramService;
        $this->expenseService = $expenseService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentRestaurant = $options['restaurant'];
        $labels = $this->expenseService->getAllLabelsOfGroup();

        $builder
            ->add(
                'startDate',
                DateType::class,
                [
                    'label' => 'keyword.from',
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "attr" => ['class' => "datepicker"],
                    "required" => false,
                ]
            )
            ->add(
                'endDate',
                DateType::class,
                [
                    'label' => 'keyword.to',
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "attr" => ['class' => "datepicker"],
                    "required" => false,
                ]
            )
            ->add(
                'responsible',
                EntityType::class,
                array(
                    'label' => 'label.manager',
                    'class' => 'AppBundle\Staff\Entity\Employee',
                    'query_builder' => function (EmployeeRepository $er) use ($currentRestaurant) {
                        $roleAdmin = $this->em->getRepository(Role::class)->findOneBy(
                            [
                                'label' => Role::ROLE_ADMIN
                            ]
                        );
                        $roleSupervision = $this->em->getRepository(Role::class)->findOneBy(
                            array(
                                'label' => Role::ROLE_SUPERVISION
                            )
                        );
                        $queryBuilder=$er->createQueryBuilder('e')
                            ->where(':restaurant MEMBER OF e.eligibleRestaurants')
                            ->setParameter('restaurant', $currentRestaurant)
                            ->orderBy('e.firstName');
                        if ($roleAdmin) {
                            $queryBuilder->andWhere(':roleAdmin NOT MEMBER OF e.employeeRoles')
                                ->setParameter('roleAdmin', $roleAdmin);
                        }

                        if ($roleSupervision) {
                            $queryBuilder->andWhere(":roleSupervision NOT MEMBER OF e.employeeRoles")
                                ->setParameter("roleSupervision", $roleSupervision);
                        }
                        return $queryBuilder;
                    },
                    'choice_label' => function ($member) {
                        return $member->getFirstName().' '.$member->getLastName();
                    },
                    'attr' => ['class' => 'sortable'],
                    'empty_value' => 'expense.list.choose_member',
                    'required' => false,
                )
            )
            ->add(
                'group',
                ChoiceType::class,
                array(
                    'label' => 'label.group',
                    'required' => false,
                    'empty_value' => 'expense.list.choose_group',
                    'attr' => ['class' => 'sortable'],
                    'choices' => array(
                        Expense::GROUP_BANK_CASH_PAYMENT => 'expense.group.'.Expense::GROUP_BANK_CASH_PAYMENT,
                        Expense::GROUP_BANK_RESTAURANT_PAYMENT => 'expense.group.'.Expense::GROUP_BANK_RESTAURANT_PAYMENT,
                        Expense::GROUP_BANK_E_RESTAURANT_PAYMENT => 'expense.group.'.Expense::GROUP_BANK_E_RESTAURANT_PAYMENT,
                        Expense::GROUP_BANK_CARD_PAYMENT => 'expense.group.'.Expense::GROUP_BANK_CARD_PAYMENT,
                        Expense::GROUP_ERROR_COUNT => 'expense.group.'.Expense::GROUP_ERROR_COUNT,
                        Expense::GROUP_OTHERS => 'expense.group.'.Expense::GROUP_OTHERS,
                    ),
                )
            )
            ->add(
                'label',
                ChoiceType::class,
                array(
                    'label' => 'keyword.label',
                    'required' => false,
                    'empty_value' => 'expense.list.choose_label',
                    'choices' => $labels,
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'restaurant' => null,
            )
        );
    }
}
