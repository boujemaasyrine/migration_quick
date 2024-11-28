<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 09/05/2016
 * Time: 17:26
 */

namespace AppBundle\Administration\Form\Cashbox;

use AppBundle\Administration\Entity\Translation\ParameterTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Class ParameterTranslationType
 * @package AppBundle\Administration\Form\Cashbox
 */
class ParameterTranslationType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'content',
                TextType::class,
                array(
                    'label' => false,
                    'attr' => array('class' => 'form-control'),
                    'constraints' => array(
                        new NotNull(),
                    ),
                )
            )
            ->add('locale', HiddenType::class);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => ParameterTranslation::class,
            )
        );
    }
}
