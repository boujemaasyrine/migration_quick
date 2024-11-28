<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 16/03/2016
 * Time: 09:14
 */

namespace AppBundle\Administration\Form\Search;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class SupplierSearchType
 * @package AppBundle\Administration\Form\Search
 */
class SupplierSearchType extends AbstractType
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
                array(
                    'label' => 'label.name',
                    'required' => false,
                )
            )
            ->add(
                'code',
                TextType::class,
                array(
                    'label' => 'label.code',
                    'required' => false,
                )
            );
    }
}
