<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 05/04/2016
 * Time: 11:52
 */

namespace AppBundle\Report\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class CashboxCountsAnomaliesFilterType extends AbstractType
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
            )
            ->add(
                'diffCashbox',
                new CompareFilterType(),
                [
                    'label' => 'cashbox_counts_anomalies.report_labels.diff_caisse',
                    'required' => false,
                ]
            )
            ->add(
                'annulations',
                new CompareFilterType(),
                [
                    'label' => 'cashbox_counts_anomalies.report_labels.annulations',
                    'required' => false,
                ]
            )
            ->add(
                'corrections',
                new CompareFilterType(),
                [
                    'label' => 'cashbox_counts_anomalies.report_labels.corrections',
                    'required' => false,
                ]
            )
            ->add(
                'abandons',
                new CompareFilterType(),
                [
                    'label' => 'cashbox_counts_anomalies.report_labels.abandons',
                    'required' => false,
                ]
            )
            ->add(
                'especes',
                new CompareFilterType(),
                [
                    'label' => 'cashbox_counts_anomalies.report_labels.especes',
                    'required' => false,
                ]
            )
            ->add(
                'titreRestaurant',
                new CompareFilterType(),
                [
                    'label' => 'cashbox_counts_anomalies.report_labels.titres_restaurant',
                    'required' => false,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array());
    }
}
