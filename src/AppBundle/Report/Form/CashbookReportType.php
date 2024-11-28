<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 24/08/2016
 * Time: 08:49
 */

namespace AppBundle\Report\Form;

use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CashbookReportType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $lastClosingDate = $options['lastClosingDate'];
        $builder
            ->add(
                'startDate',
                DateType::class,
                [
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "label" => "keyword.from",
                    "required" => true,
                    "constraints" => [
                        new NotNull(),
                        new Callback(
                            array(
                                'callback' => function (
                                    $value,
                                    ExecutionContextInterface $context
                                ) use (
                                    $lastClosingDate
                                ) {

                                    if (!$value instanceof \DateTime) {
                                        return;
                                    }
                                    if (Utilities::compareDates($lastClosingDate, $value) < 0) {
                                        $context->buildViolation('bigger_than_closure_date')->addViolation();
                                    }
                                },
                            )
                        ),
                    ],
                ]
            )
            ->add(
                'endDate',
                DateType::class,
                [
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "label" => "keyword.to",
                    "required" => true,
                    "constraints" => [
                        new NotNull(),
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if ($value === null) {
                                        return;
                                    }

                                    if (!$value instanceof \DateTime) {
                                        return;
                                    }

                                    $rootData = $context->getRoot()->getData();

                                    $startDate = $rootData['startDate'];
                                    if ($startDate === null) {
                                        return;
                                    }

                                    if (!$startDate instanceof \DateTime) {
                                        return;
                                    }

                                    if (Utilities::compareDates($startDate, $value) > 0) {
                                        $context->buildViolation('startdate_inf_enddate')->addViolation();
                                    }
                                },
                            )
                        ),
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'lastClosingDate' => \DateTime::class,
            )
        );
    }
}
