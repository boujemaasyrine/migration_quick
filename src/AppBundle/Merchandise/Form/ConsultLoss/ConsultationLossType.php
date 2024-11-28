<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 25/02/2016
 * Time: 16:28
 */

namespace AppBundle\Merchandise\Form\ConsultLoss;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

class ConsultationLossType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'type',
                ChoiceType::class,
                array(
                    'label' => 'loss.check.title',
                    'choices' => array(
                        'loss.check.hourly' => 'hourly',
                        'loss.check.daily' => 'daily',
                        'loss.check.weekly' => 'weekly',
                        'loss.check.monthly' => 'monthly',
                    ),
                    'choices_as_values' => true,
                    'empty_value' => 'Faites votre choix',
                )
            );
    }
}
