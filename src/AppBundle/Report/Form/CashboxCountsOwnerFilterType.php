<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 06/04/2016
 * Time: 10:52
 */

namespace AppBundle\Report\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class CashboxCountsOwnerFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'startDate',
                DateType::class,
                [
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "required" => true,
                    "constraints" => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'endDate',
                DateType::class,
                [
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "required" => true,
                    "constraints" => [
                        new NotNull(),
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array());
    }
}
