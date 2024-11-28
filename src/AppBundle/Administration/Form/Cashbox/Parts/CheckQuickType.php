<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 28/05/2018
 * Time: 08:03
 */

namespace AppBundle\Administration\Form\Cashbox\Parts;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class CheckQuickType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder,array $options)
    {
        $builder->add('id',HiddenType::class)
            ->add('checkName',HiddenType::class,['required'=>false])
            ->add('value',CheckQuickValueType::class);
    }

}