<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 28/03/2016
 * Time: 13:37
 */

namespace AppBundle\Financial\Form\Withdrawal;

use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\Financial\Service\AdministrativeClosingService;
use AppBundle\Staff\Entity\Employee;
use AppBundle\Staff\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use AppBundle\Financial\Validator\MaxAmountWithdrawalConstraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class WithdrawalType extends AbstractType
{

    private $em;
    private $translator;
    private $administrativeClosingService;

    public function __construct(
        EntityManager $em,
        Translator $translator,
        AdministrativeClosingService $administrativeClosingService
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->administrativeClosingService = $administrativeClosingService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentRestaurant = $options['restaurant'];
        $date = $this->administrativeClosingService->getLastWorkingEndDate();
        $members = $this->em->getRepository("Financial:Ticket")->getUserPerDay($date, $currentRestaurant);
        $active = array();

        foreach ($members as $m) {
            $active[] = $m['operator'];
        }

        $builder
            ->add(
                'date',
                DateType::class,
                array(
                    'label' => 'keyword.date',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                )
            )
            ->add(
                'member',
                EntityType::class,
                array(
                    'label' => 'fund_management.withdrawal.entry.team_member',
                    'class' => 'AppBundle\Staff\Entity\Employee',
                    'choice_label' => function (Employee $member) {
                        return $member->getWyndId().'- '.$member->getFirstName().' '.$member->getLastName();
                    },
                    'query_builder' => function (EmployeeRepository $er) use ($active, $currentRestaurant) {
                        return $er->createQueryBuilder('e')
                            ->where('e.wyndId in (:active)')
                            ->andWhere(':restaurant MEMBER OF e.eligibleRestaurants')
                            ->setParameter('restaurant', $currentRestaurant)
                            ->setParameter('active', $active);
                    },
                    'empty_value' => 'fund_management.withdrawal.list.choose_member',
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
                'amountWithdrawal',
                TextType::class,
                array(
                    'label' => 'keyword.amount',
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+([\.,][0-9]+)?$/',
                                'message' => 'invalid_format',
                            )
                        ),
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if ($value < 5 && $value != null) {
                                        $context->buildViolation($this->translator->trans('min_five'))->addViolation();
                                    }
                                },
                            )
                        ),
                    ),

                )
            )
            ->add(
                'previousAmount',
                TextType::class,
                array(
                    'label' => 'fund_management.withdrawal.entry.previous_amount',
                    'mapped' => false,
                    'required' => false,
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Withdrawal::class,
                'restaurant' => null,
            )
        );
    }
}
