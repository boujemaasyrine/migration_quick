<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 07/04/2016
 * Time: 09:27
 */

namespace AppBundle\Financial\Form\Cashbox;

use AppBundle\Financial\Entity\CashboxBankCardContainer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BankCardContainerType extends AbstractType
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
                'bankCardCounts',
                CollectionType::class,
                [
                    'entry_type' => BankCardType::class,
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
                'data_class' => CashboxBankCardContainer::class,
            ]
        );
    }
}