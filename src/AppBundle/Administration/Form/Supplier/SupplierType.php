<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/03/2016
 * Time: 14:58
 */

namespace AppBundle\Administration\Form\Supplier;

use AppBundle\Merchandise\Entity\Supplier;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SupplierType
 * @package AppBundle\Administration\Form\Supplier
 */
class SupplierType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                array('label' => 'provider.list.provider')
            )
            ->add(
                'designation',
                TextType::class,
                array('label' => 'provider.list.designation')
            )
            ->add(
                'code',
                TextType::class,
                array('label' => 'CNUF')
            )
            ->add(
                'cif',
                TextType::class,
                array('label' => 'CIF')
            )
            ->add('phone', TextType::class)
            ->add(
                'address',
                TextType::class,
                array('label' => 'provider.list.address')
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Supplier::class,
            )
        );
    }
}
