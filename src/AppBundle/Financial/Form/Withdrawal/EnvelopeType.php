<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 28/03/2016
 * Time: 16:57
 */

namespace AppBundle\Financial\Form\Withdrawal;

use AppBundle\Financial\Entity\Envelope;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

class EnvelopeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'reference',
                TextType::class,
                array(
                    'label' => 'label.reference',
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ),
                )
            )
            ->add(
                'amount',
                TextType::class,
                array(
                    'label' => 'keyword.amount',
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ),
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Envelope::class,
            )
        );
    }
}
