<?php
/**
 * Created by PhpStorm.
 * User: hmnaouar
 * Date: 25/04/2016
 * Time: 14:39
 */

namespace AppBundle\Staff\Form\Management;

use AppBundle\Security\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

class ChangeEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder ->add('email', TextType::class,
            array('label' => 'label.mail',
                'required' => true,
                'attr' => array('class' => 'form-control'),
                'constraints' => array(
                    new NotNull(
                        array(
                            'message' => 'null_value',
                        )
                    ),
                    new Email(
                        array(
                            'message' => 'invalid_format',
                        )
                    )
                )
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => User::class
        ));
    }
}