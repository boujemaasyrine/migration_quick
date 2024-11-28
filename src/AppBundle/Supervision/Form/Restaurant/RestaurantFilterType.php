<?php
/**
 * Created by PhpStorm.
 * User: bchebbi
 * Date: 01/06/2018
 * Time: 10:14
 */

namespace AppBundle\Supervision\Form\Restaurant;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\NotNull;

class RestaurantFilterType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'date',
                DateType::class,
                array(
                    "label" => "last_closured_date",
                    "format" => "dd/MM/y",
                    "widget" => "single_text",
                    "required" => true,
                    "constraints" => [
                        new NotNull()
                    ]
                )
            )

        ;


    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => null,
            )
        );
    }

    public function getName()
    {
        return 'filter_type';
    }
}
