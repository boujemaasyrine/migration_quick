<?php
/**
 * Created by PhpStorm.
 * User: bchebbi
 * Date: 03/07/2018
 * Time: 11:23
 */

namespace AppBundle\Report\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotNull;

class CaBySliceHourReportType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

            ->add('caType', ChoiceType::class, array(
                'choices' => array(
                    'ca_brut_ttc' => 0,
                    'ca_net_htva' => 1
                ),
                'data' => 'ca_brut_ttc',
                "choices_as_values" => true,
                "multiple" => false,
                "expanded" => false,
                "required" => true,
                "constraints" => array(
                    new NotNull()
                )
            ))
            ->add('scheduleType', ChoiceType::class, array(
                'choices' => array(
                    'report.sales.hour_by_hour.hour' => 0,
                    'report.sales.hour_by_hour.half_hour' => 1,
                    'report.sales.hour_by_hour.quarter_hour' => 2
                ),
                'data' => 'hour',
                "choices_as_values" => true,
                "multiple" => false,
                "expanded" => false,
                "required" => true,
                "constraints" => array(
                    new NotNull()
                )
            ))
            ->add('date1', DateType::class, array(
                "format" => "dd/MM/y",
                "widget" => "single_text",
                "required" => true,
                "constraints" => array(
                    new NotNull()
                )
            ))
            ->add('date2', DateType::class, array(
                "format" => "dd/MM/y",
                "widget" => "single_text",
                "required" => true,
                "constraints" => array(
                    new NotNull()
                )
            ))
            ->add('date3', DateType::class, array(
                "format" => "dd/MM/y",
                "widget" => "single_text",
                "required" => true,
                "constraints" => array(
                    new NotNull()
                )
            ))
            ->add('date4', DateType::class, array(
                "format" => "dd/MM/y",
                "widget" => "single_text",
                "required" => true,
                "constraints" => array(
                    new NotNull()
                )
            ))
        ;
    }
}