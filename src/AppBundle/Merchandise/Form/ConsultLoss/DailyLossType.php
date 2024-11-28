<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 01/03/2016
 * Time: 14:55
 */

namespace AppBundle\Merchandise\Form\ConsultLoss;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class DailyLossType extends AbstractType
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
                'endDate',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'label' => 'Au',
                    'format' => 'dd/MM/yyyy',
                )
            );
    }
}
