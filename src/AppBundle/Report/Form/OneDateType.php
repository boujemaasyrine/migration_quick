<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 10/05/2016
 * Time: 11:13
 */

namespace AppBundle\Report\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotNull;

class OneDateType extends AbstractType
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
}
