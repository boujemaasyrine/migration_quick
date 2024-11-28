<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 09/05/2016
 * Time: 17:26
 */

namespace AppBundle\Supervision\Form\Parameters;

use AppBundle\Administration\Entity\Parameter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LabelsType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'translations',
                CollectionType::class,
                array(
                    'entry_type' => LabelsTranslationType::class,
                    'label' => 'keyword.label_nl',
                    'entry_options' => array(
                        'label' => 'keyword.label',
                        'required' => true,
                    ),
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Parameter::class,
                "translation_domain" => "supervision",
            )
        );
    }
}
