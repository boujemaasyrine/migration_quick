<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/03/2016
 * Time: 14:58
 */

namespace AppBundle\Supervision\Form\Supplier;

use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Validator\UniqueCodeSupplierConstraint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SupplierType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                array(
                    'label' => 'label.name',
                    'required' => true,
                    'attr' => array('class' => 'form-control'),
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
                'designation',
                TextType::class,
                array(
                    'label' => 'provider.list.designation',
                    'attr' => array('class' => 'form-control'),
                    'required' => false,
                )
            )
            ->add(
                'code',
                TextType::class,
                array(
                    'label' => 'label.code',
                    'attr' => array('class' => 'form-control'),
                    'required' => true,
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
                'phone',
                TextType::class,
                array(
                    'label' => 'label.phone',
                    'required' => false,
                    'attr' => array('class' => 'form-control'),
                    'constraints' => array(
                        new regex(
                            array(
                                'pattern' => '/^[+]?[0-9][0-9_ ]*$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ),
                )
            )
            ->add(
                'address',
                TextType::class,
                array(
                    'label' => 'provider.list.address',
                    'attr' => array('class' => 'form-control'),
                    'required' => false,
                )
            )
            ->add(
                'email',
                TextType::class,
                array(
                    'label' => 'label.mail',
                    'required' => false,
                    'attr' => array('class' => 'form-control'),
                    'constraints' => array(
                        new regex(
                            array(
                                'pattern' => '/^[^\W][a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\@[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\.[a-zA-Z]{2,4}$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ),
                )
            );
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Supplier::class,
                "translation_domain" => "supervision",
            )
        );
    }
}
