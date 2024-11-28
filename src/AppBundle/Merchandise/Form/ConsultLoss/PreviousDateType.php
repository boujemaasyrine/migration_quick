<?php
/**
 * Created by PhpStorm.
 * User: zbessassi
 * Date: 19/04/2019
 * Time: 17:16
 */

namespace AppBundle\Merchandise\Form\ConsultLoss;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class PreviousDateType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {


        $builder->add(
            'date',
            DateType::class,
            [
                "format" => "dd/MM/y",
                "widget" => "single_text",
                "required" => true,
                "label" => 'keyword.date',
                "constraints" => [
                    new NotNull(),
                ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => null,
                'date' => null,
            )
        );
    }
}
