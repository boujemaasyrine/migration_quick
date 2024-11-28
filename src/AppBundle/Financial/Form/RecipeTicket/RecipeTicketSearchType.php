<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 07/04/2016
 * Time: 10:13
 */

namespace AppBundle\Financial\Form\RecipeTicket;

use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Security\Entity\Role;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

class RecipeTicketSearchType extends AbstractType
{

    /**
     * @var ParameterService
     */
    private $parameterService;

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(ParameterService $parameterService,EntityManager $em)
    {
        $this->parameterService = $parameterService;
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentRestaurant = $options['restaurant'];
        $builder
            ->add(
                'startDate',
                DateType::class,
                [
                    "label" => 'envelope.filter_labels.from',
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "required" => false,
                    'attr' => ['class' => 'datepicker'],
                ]
            )
            ->add(
                'endDate',
                DateType::class,
                [
                    "label" => 'envelope.filter_labels.to',
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "required" => false,
                    'attr' => ['class' => 'datepicker'],
                ]
            )
            ->add(
                'owner',
                EntityType::class,
                [
                    'label' => 'label.owner',
                    'empty_value' => 'label.all',
                    "class" => Employee::class,
                    'choice_label' => function ($member) {
                        return $member->getFirstName().' '.$member->getLastName();
                    },
                    "required" => false,
                    'attr' => ['class' => 'sortable'],
                    'query_builder' => function (EntityRepository $repo) use ($currentRestaurant) {
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
                        $queryBuilder= $repo->createQueryBuilder('e')
                            ->where(':restaurant MEMBER OF e.eligibleRestaurants')
                            ->setParameter('restaurant', $currentRestaurant);
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
                ]
            )
            ->add(
                'label',
                ChoiceType::class,
                array(
                    'label' => 'filter.label_filter',
                    'required' => false,
                    'empty_value' => 'envelope.all',
                    'choices' => $this->parameterService->getRecipeTicketLabels(true)
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
