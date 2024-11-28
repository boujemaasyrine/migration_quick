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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DefaultPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'password',
                PasswordType::class,
                array(
                    'label' => 'label.password',
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
                'confirmPassword',
                PasswordType::class,
                array(
                    'label' => 'confirm_password',
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {

                                    $rootData = $context->getRoot()->getData();

                                    $password = $rootData['password'];
                                    if ($password === null) {
                                        return;
                                    }

                                    if ($value != $password) {
                                        $context->buildViolation('confirm_password_failed')->addViolation();
                                    }
                                },
                            )
                        ),
                    ),
                )
            );
    }
}
