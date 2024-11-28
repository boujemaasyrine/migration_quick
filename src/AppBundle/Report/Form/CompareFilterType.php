<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 06/04/2016
 * Time: 11:59
 */

namespace AppBundle\Report\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CompareFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'firstInput',
                'hidden',
                [
                    'label' => false,
                ]
            )
            ->add(
                'secondInput',
                'hidden',
                [
                    'label' => false,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array());
    }
}
