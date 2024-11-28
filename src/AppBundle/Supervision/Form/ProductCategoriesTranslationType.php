<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 09/05/2016
 * Time: 17:26
 */

namespace AppBundle\Supervision\Form;

use AppBundle\Merchandise\Entity\Translation\ProductCategoriesTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class ProductCategoriesTranslationType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'content',
                TextType::class,
                array(
                    'label' => false,
                    'attr' => array('class' => 'form-control'),
                    'required' => true,
                    'constraints' => array(
                        new NotNull(),
                    ),
                )
            )
            ->add('locale', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => ProductCategoriesTranslation::class,
                'translation_domain' => 'supervision',
            )
        );
    }
}
