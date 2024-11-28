<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 09/05/2016
 * Time: 17:26
 */

namespace AppBundle\Administration\Form\Cashbox;

use AppBundle\Administration\Entity\Parameter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class LabelsType
 * @package AppBundle\Administration\Form\Cashbox
 */
class LabelsType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'translations',
                CollectionType::class,
                array(
                    'entry_type' => ParameterTranslationType::class,
                    'label' => 'keyword.label_nl',
                    'entry_options' => array(
                        'label' => false,
                    ),
                )
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Parameter::class,
            )
        );
    }
}
