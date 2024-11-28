<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 25/04/2016
 * Time: 18:11
 */

namespace AppBundle\Staff\Form\Management;

use AppBundle\Security\Entity\Role;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class StaffSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'firstName',
                TextType::class,
                array(
                    'label' => 'user.first_name',
                )
            )
            /*->add('lastName', TextType::class, array(
                'label' => 'user.last_name'
            ))*/
            ->add(
                'role',
                EntityType::class,
                array(
                    'label' => 'label.role',
                    'class' => Role::class,
                    'choice_label' => 'textLabel',
                    'empty_value' => 'staff.list.all_roles',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('r')
                            ->where('r.type = :type')
                            ->setParameter('type', Role::RESTAURANT_ROLE_TYPE);
                    }
                )
            );
    }
}
