<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 25/04/2016
 * Time: 14:39
 */

namespace AppBundle\Staff\Form\Management;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
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
                    'label' => 'label.old_password',
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
                    'first_options' => array('label' => 'label.password', 'attr' => array('class' => 'form-control')),
                    'second_options' => array(
                        'label' => 'confirm_password',
                        'attr' => array('class' => 'form-control'),
                    ),
                    'constraints' => array(
                        new NotNull(),
                    ),
                )
            );
    }
}
