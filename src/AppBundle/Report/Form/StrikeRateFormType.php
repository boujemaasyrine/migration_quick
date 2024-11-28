<?php

namespace AppBundle\Report\Form;

use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class StrikeRateFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('startDate', DateType::class, [
                "label" => "keyword.from",
                "format" => "dd/MM/y",
                "widget" => "single_text",
                "required" => true,
                "constraints" => [
                    new NotNull()
                ]
            ])
            ->add('endDate', DateType::class, [
                "label" => "keyword.to",
                "format" => "dd/MM/y",
                "widget" => "single_text",
                "required" => true,
                "constraints" => [
                    new NotNull()
                ]
            ])
            ->add('itemName', TextType::class,array(
                'label'=>'strike_rate.item_name',
                'required'=>false
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }

    public function getName()
    {
        return 'app_bundle_strike_rate_form_type';
    }
}
