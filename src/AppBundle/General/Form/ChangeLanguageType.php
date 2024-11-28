<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 15/03/2016
 * Time: 14:53
 */

namespace AppBundle\General\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ChangeLanguageType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'language',
                ChoiceType::class,
                array(
                    'label' => 'language.label',
                    'attr' => ['class' => 'form-control'],
                    'choices' => array(
                        'fr' => 'language.fr',
                        'nl' => 'language.nl',
                    ),
                )
            );
    }
}
