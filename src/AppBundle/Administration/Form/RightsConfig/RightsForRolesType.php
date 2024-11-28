<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 26/04/2016
 * Time: 14:39
 */

namespace AppBundle\Administration\Form\RightsConfig;

use AppBundle\Security\Entity\Role;
use AppBundle\Security\Repository\RoleRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Administration\Form\RightsConfig\RightConfigType;

/**
 * Class RightsForRolesType
 */
class RightsForRolesType extends AbstractType
{

    private $manager;

    /**
     * RightsForRolesType constructor.
     * @param ObjectManager $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'rolesLabel',
                EntityType::class,
                array(
                    'label' => 'label.roles',
                    'class' => 'AppBundle\Security\Entity\Role',
                    'query_builder' => function (RoleRepository $er) {
                        return $er->createQueryBuilder('r')
                            ->where('r.label <> :roleEmployee')
                            ->andWhere('r.type <> :central')
                            ->setParameter('roleEmployee', Role::ROLE_EMPLOYEE)
                            ->setParameter('central', Role::CENTRAL_ROLE_TYPE);
                    },
                    'choice_label' => 'textLabel',
                )
            )
            ->add(
                'roles',
                CollectionType::class,
                array(
                    'entry_type' => RightConfigType::class,
                    'by_reference' => false,
                    'error_bubbling' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'prototype' => true,

                )
            );
    }
}
