<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 31/05/2016
 * Time: 11:23
 */

namespace AppBundle\Supervision\Form\UsersManagement;

use AppBundle\Security\Entity\Role;
use AppBundle\Security\Repository\RoleRepository;
use Doctrine\DBAL\Types\StringType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RightsForRolesType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $roleToIgnore = '';
        if ($options['type'] == 'central') {
            $roleToIgnore = Role::RESTAURANT_ROLE_TYPE;
        }


        $builder
            ->add(
                'rolesLabel',
                EntityType::class,
                array(
                    'label' => 'labels.roles',
                    'class' => Role::class,
                    'query_builder' => function (RoleRepository $er) use ($roleToIgnore) {
                        return $er->createQueryBuilder('r')
                            ->where('r.type <> :ignoredRole')
                            ->andWhere('r.label <> :roleEmployee')
                            ->andWhere('r.label <> :roleAdmin')
                            ->andWhere('r.label <> :roleSupervision')
                            ->setParameter('ignoredRole', $roleToIgnore)
                            ->setParameter('roleAdmin', Role::ROLE_ADMIN)
                            ->setParameter('roleEmployee', Role::ROLE_EMPLOYEE)
                            ->setParameter('roleSupervision',Role::ROLE_SUPERVISION);
                    },
                    'choice_label' => 'textLabel',
                )
            )
            ->add(
                'roles',
                CollectionType::class,
                array(
                    'entry_type' => RightConfigType::class,
                    'entry_options' => array(
                        'type' => $options['type'],
                    ),
                    'by_reference' => false,
                    'error_bubbling' => false,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'prototype' => true,

                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'type' => StringType::class,
                "translation_domain" => "supervision",
            )
        );
    }
}
