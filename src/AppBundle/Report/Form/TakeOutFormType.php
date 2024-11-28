<?php

namespace AppBundle\Report\Form;

use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\Security\Entity\Role;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TakeOutFormType extends AbstractType
{
    private $translator;
    private $restaurantService;
    private $entityManager;

    /**
     * TakeOutFormType constructor.
     * @param $translator
     */
    public function __construct( Translator $translator, RestaurantService $restaurantService, EntityManager $entityManager)
    {
        $this->translator = $translator;
        $this->restaurantService = $restaurantService;
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $roleAdmin = $this->entityManager->getRepository(Role::class)->findOneBy(
            [
                'label' => Role::ROLE_ADMIN,
            ]
        );
        $roleSupervision = $this->entityManager->getRepository(Role::class)->findOneBy(
            array(
                'label' => Role::ROLE_SUPERVISION,
            )
        );

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
            ->add('cashier', EntityType::class, array(
                'query_builder' => function(EntityRepository $er) use($roleAdmin, $roleSupervision){
                    $qb = $er->createQueryBuilder('e');
                    return $qb->where(':restaurant MEMBER OF e.eligibleRestaurants')
                        ->andWhere($qb->expr()->orX(
                            $qb->expr()->eq('e.deleted', ':false'),
                            $qb->expr()->isNull('e.deleted'))
                        )
                        ->andWhere(':adminRole not MEMBER OF e.employeeRoles')
                        ->andWhere(':supervisionRole not MEMBER OF e.employeeRoles')
                        ->setParameter('restaurant', $this->restaurantService->getCurrentRestaurant())
                        ->setParameter('adminRole',$roleAdmin)
                        ->setParameter('supervisionRole', $roleSupervision)
                        ->setParameter('false', false)
                        ->distinct();
                },
                'label' => 'takeout_report.cashier',
                'attr' => ['class' => 'form-control sortable'],
                'class' => 'AppBundle\Staff\Entity\Employee',
                'empty_value' => 'envelope.choose_cashier',
                'required' => false,
            ));

    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }

    public function getName()
    {
        return 'app_bundle_take_out_form_type';
    }
}
