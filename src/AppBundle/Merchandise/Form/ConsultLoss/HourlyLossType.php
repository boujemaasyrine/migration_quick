<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 26/02/2016
 * Time: 08:42
 */

namespace AppBundle\Merchandise\Form\ConsultLoss;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

class HourlyLossType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'date',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'label' => 'Du',
                    'format' => 'dd/MM/yyyy',
                )
            )
            ->add(
                'startTime',
                TimeType::class,
                array(
                    'label' => 'Début',
                    'input' => 'datetime',
                    'with_minutes' => false,
                    'empty_value' => '',
                    'widget' => 'choice',
                    'hours' => array(
                        '11' => '11',
                        '12' => '12',
                        '13' => '13',
                        '14' => '14'
                    ,
                        '15' => '15',
                        '16' => '16',
                        '17' => '17',
                        '18' => '18'
                    ,
                        '19' => '19',
                        '20' => '20',
                        '21' => '21',
                        '22' => '22',
                    ),
                )
            )
            ->add(
                'endTime',
                TimeType::class,
                array(
                    'label' => 'Fin',
                    'input' => 'datetime',
                    'with_minutes' => false,
                    'empty_value' => '',
                    'widget' => 'choice',
                    'hours' => array(
                        '11' => '11',
                        '12' => '12',
                        '13' => '13',
                        '14' => '14'
                    ,
                        '15' => '15',
                        '16' => '16',
                        '17' => '17',
                        '18' => '18'
                    ,
                        '19' => '19',
                        '20' => '20',
                        '21' => '21',
                        '22' => '22',
                    ),
                )
            );
    }
}
