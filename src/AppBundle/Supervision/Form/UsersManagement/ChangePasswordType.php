<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 25/04/2016
 * Time: 14:39
 */

namespace AppBundle\Supervision\Form\UsersManagement;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'oldPassword',
                PasswordType::class,
                array(
                    'label' => 'labels.old_password',
                    'attr' => array('class' => 'form-control'),
                    'constraints' => array(
                        new NotNull(),
                        new UserPassword(),
                    ),
                )
            )
            ->add(
                'password',
                RepeatedType::class,
                array(
                    'type' => 'password',
                    'required' => true,
                    'first_options' => array('label' => 'labels.password', 'attr' => array('class' => 'form-control')),
                    'second_options' => array(
                        'label' => 'labels.confirm_password',
                        'attr' => array('class' => 'form-control'),
                    ),
                    'constraints' => array(
                        new NotNull(),
                    ),
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'translation_domain' => 'supervision',
            )
        );
    }
}
