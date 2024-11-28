<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 28/03/2016
 * Time: 17:55
 */

namespace AppBundle\Financial\Form\Withdrawal;

use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\Staff\Entity\Employee;
use AppBundle\Staff\Repository\EmployeeRepository;
use AppBundle\Staff\Service\EmployeeService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Tests\Fixtures\Entity;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WithdrawalSearchType extends AbstractType
{

    private $em;
    private $employeeService;

    public function __construct(EntityManager $em, EmployeeService $employeeService)
    {
        $this->em = $em;
        $this->employeeService = $employeeService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $cashiers = array();
        $cashiersAsArray = $this->em->getRepository('Financial:Withdrawal')->getCashiers();
        foreach ($cashiersAsArray as $cashier) {
            $cashiers[] = $cashier[1];
        }

        $currentRestaurant = $options['restaurant'];
        $builder
            ->add(
                'statusCount',
                ChoiceType::class,
                array(
                    'label' => 'keyword.status',
                    'required' => false,
                    'empty_value' => 'fund_management.withdrawal.list.choose_status',
                    'choices' => array(
                        Withdrawal::COUNTED => 'status.counted',
                        Withdrawal::NOT_COUNTED => 'status.not_counted',
                    ),
                )
            )
            ->add(
                'startDate',
                DateType::class,
                array(
                    'label' => 'keyword.from',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => false,
                )
            )
            ->add(
                'endDate',
                DateType::class,
                array(
                    'label' => 'keyword.to',
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => false,
                )
            )
            ->add(
                'member',
                EntityType::class,
                array(
                    'class' => 'AppBundle\Staff\Entity\Employee',
                    'label' => 'fund_management.withdrawal.entry.team_member',
                    'empty_value' => 'fund_management.withdrawal.list.choose_member_search',
                    'required' => false,
                    'query_builder' => function (EmployeeRepository $er) use ($cashiers, $currentRestaurant) {
                        return $er->createQueryBuilder('e')
                            ->where('e.id in (:cashiers)')
                            ->andWhere(':restaurant MEMBER OF e.eligibleRestaurants')
                            ->setParameter('restaurant', $currentRestaurant)
                            ->setParameter('cashiers', $cashiers);
                    },
                )
            )
            ->add(
                'owner',
                EntityType::class,
                array(
                    'class' => Employee::class,
                    'label' => 'label.manager',
                    'empty_value' => 'fund_management.withdrawal.list.choose_owner',
                    'choice_label' => function ($owner) {
                        return $owner->getFirstName().' '.$owner->getLastName();
                    },
                    'required' => false,
                    'query_builder' => function (EntityRepository $repo) use ($currentRestaurant) {
                        return $repo->createQueryBuilder('e')
                            ->where(':restaurant MEMBER OF e.eligibleRestaurants')
                            ->setParameter('restaurant', $currentRestaurant);
                    },

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
