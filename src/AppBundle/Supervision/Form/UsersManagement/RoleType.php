<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 31/05/2016
 * Time: 18:07
 */

namespace AppBundle\Supervision\Form\UsersManagement;

use AppBundle\Security\Entity\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class RoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'textLabel',
                TextType::class,
                array(
                    'label' => 'keyword.label',
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
                'type',
                ChoiceType::class,
                array(
                    'label' => 'label.type',
                    'choices' => [
                        Role::RESTAURANT_ROLE_TYPE => 'parameters.restaurant',
                        Role::CENTRAL_ROLE_TYPE => 'parameters.central',
                    ],
                    'empty_value' => 'roles.choose_type',
                    'constraints' => [
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ],
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Role::class,
                "translation_domain" => "supervision",
            )
        );
    }
}
