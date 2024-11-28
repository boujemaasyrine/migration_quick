<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 13/02/2018
 * Time: 15:11
 */

namespace AppBundle\Merchandise\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

class InventorySearchType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder,array $options)
    {
        $builder ->add('startDate', DateType::class, [
            'label' => 'keyword.from',
            "format" => "dd/MM/y",
            "widget" => "single_text",
            "required" => false,
        ])
            ->add('endDate', DateType::class, [
                'label' => 'keyword.to',
                "format" => "dd/MM/y",
                "widget" => "single_text",
                "required" => false,
            ]);
        
    }
}