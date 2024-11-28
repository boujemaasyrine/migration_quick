<?php
/**
 * Created by PhpStorm.
 * User: bchebbi
 * Date: 30/07/2018
 * Time: 10:52
 */

namespace AppBundle\Report\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\Callback;
use AppBundle\ToolBox\Utils\Utilities;
class CaPerTvaFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder

            ->add('startDate', DateType::class, array(
                "label" => "keyword.from",
                "format" => "dd/MM/y",
                "widget" => "single_text",
                "required" => true,
                "constraints" => array(
                    new NotNull()
                )
            ))
            ->add('endDate', DateType::class, array(
                "label" => "keyword.to",
                "format" => "dd/MM/y",
                "widget" => "single_text",
                "required" => true,
                "constraints" => array(
                    new NotNull()
                )
            ))
           ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {

    }

    public function getName()
    {
        return 'app_bundle_ca_per_tva_form_type';
    }
}