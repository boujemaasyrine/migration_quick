<?php
/**
 * Created by PhpStorm.
 * User: hmnaouar
 * Date: 30/01/2018
 * Time: 15:48
 */

namespace AppBundle\Report\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotNull;

class HourByHourReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentDay = new \DateTime('NOW');
        $builder
            ->add('from', DateType::class, [
                "format" => "dd/MM/y",
                "widget" => "single_text",
                "required" => true,
                "constraints" => [
                    new NotNull()
                ],
                "data" => $currentDay
            ])
            ->add('to', DateType::class, [
                "format" => "dd/MM/y",
                "widget" => "single_text",
                "required" => true,
                "constraints" => [
                    new NotNull()
                ],
                'data' => $currentDay
            ])
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
                "constraints" => [
                    new NotNull()
                ]
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
                "constraints" => [
                    new NotNull()
                ]
            ));
    }


}