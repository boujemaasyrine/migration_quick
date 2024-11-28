<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 07/04/2016
 * Time: 09:27
 */

namespace AppBundle\Financial\Form\Cashbox;

use AppBundle\Financial\Entity\CashboxBankCardContainer;
use AppBundle\Financial\Entity\CashboxForeignCurrencyContainer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ForeignCurrencyContainerType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add(
                'foreignCurrencyCounts',
                CollectionType::class,
                [
                    'entry_type' => ForeignCurrencyType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'mapped' => true,
                    'by_reference' => false,
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => CashboxForeignCurrencyContainer::class,
            ]
        );
    }
}
