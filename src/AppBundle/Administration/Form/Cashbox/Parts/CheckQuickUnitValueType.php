<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 01/06/2018
 * Time: 09:53
 */

namespace AppBundle\Administration\Form\Cashbox\Parts;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

class CheckQuickUnitValueType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder,array $options)
    {
        $builder->add('unitValue',TextType::class,array(
            'label' => 'label.value',
            'constraints' => array(
                new NotNull(
                    array(
                        'message' => 'null_value',
                    )
                ),
                new Regex(
                    array(
                        'pattern' => '/^[0-9]+([\.][0-9]+)?$/',
                        'message' => 'invalid_format',
                    )
                ),
            ),
        ));
    }

}