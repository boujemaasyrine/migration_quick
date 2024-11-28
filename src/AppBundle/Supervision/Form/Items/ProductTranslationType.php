<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 09/05/2016
 * Time: 17:26
 */

namespace AppBundle\Supervision\Form\Items;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Administration\Entity\Translation\ParameterTranslation;
use AppBundle\Merchandise\Entity\Translation\CategoryGroupTranslation;
use AppBundle\Merchandise\Entity\Translation\ProductTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class ProductTranslationType extends AbstractType
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
                        new NotNull(
                            array(
                                'groups' => array('transformed_product', 'non_transformed_product', 'Default'),
                                'message' => 'null_value',
                            )
                        ),
                    ),
                )
            )
            ->add('locale', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => ProductTranslation::class,
            )
        );
    }
}
