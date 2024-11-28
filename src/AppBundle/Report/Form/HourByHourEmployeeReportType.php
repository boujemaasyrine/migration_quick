<?php
/**
 * Created by PhpStorm.
 * User: hmnaouar
 * Date: 30/01/2018
 * Time: 15:48
 */

namespace AppBundle\Report\Form;

use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class HourByHourEmployeeReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentDay = new \DateTime('NOW');
        $builder
            ->add('from', DateType::class, [
                "format" => "dd/MM/y",
                "label" => "keyword.from",
                "widget" => "single_text",
                "required" => true,
                "constraints" => [
                    new NotNull()
                ],
                'data' => $currentDay
            ])
            ->add('to', DateType::class, [
                "format" => "dd/MM/y",
                "label" => "keyword.to",
                "widget" => "single_text",
                "required" => true,
                "constraints" => [
                    new NotNull(),
                    new Callback(array(
                        'callback' => function ($value, ExecutionContextInterface $context) {
                            if ($value === null) {
                                return;
                            }

                            if (!$value instanceof \DateTime) {
                                return;
                            }

                            $rootData = $context->getRoot()->getData();

                            $startDate = $rootData['from'];
                            if ($startDate === null) {
                                return;
                            }

                            if (!$startDate instanceof \DateTime) {
                                return;
                            }

                            if (Utilities::compareDates($startDate, $value) > 0) {
                                $context->buildViolation('startdate_inf_enddate')->addViolation();
                            }

                        }
                    ))
                ],
                'data' => $currentDay
            ])
            ->add('scheduleType', ChoiceType::class, array(
                'choices' => array(
                    'report.sales.hour_by_hour.hour' => 0,
                    'report.sales.hour_by_hour.quarter_hour' => 1
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