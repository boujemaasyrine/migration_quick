<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 07/04/2016
 * Time: 09:27
 */

namespace AppBundle\Financial\Form\Chest;

use AppBundle\Financial\Entity\CashboxBankCardContainer;
use AppBundle\Financial\Entity\ChestTirelire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TirelireType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => ChestTirelire::class,
            ]
        );
    }
}
