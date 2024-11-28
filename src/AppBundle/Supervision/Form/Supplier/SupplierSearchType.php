<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 16/03/2016
 * Time: 09:14
 */

namespace AppBundle\Supervision\Form\Supplier;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SupplierSearchType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                array(
                    'label' => 'label.name',
                    'attr' => array('class' => 'form-control'),
                    'required' => false,
                )
            )
            ->add(
                'code',
                TextType::class,
                array(
                    'label' => 'label.code',
                    'attr' => array('class' => 'form-control'),
                    'required' => false,
                )
            );
    }
}
