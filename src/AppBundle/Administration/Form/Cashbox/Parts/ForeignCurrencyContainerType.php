<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 07/04/2016
 * Time: 09:27
 */

namespace AppBundle\Administration\Form\Cashbox\Parts;

use AppBundle\Administration\Entity\Currency;
use AppBundle\Administration\Repository\CurrencyRepository;
use AppBundle\Financial\Entity\CashboxBankCardContainer;
use AppBundle\Financial\Entity\CashboxForeignCurrencyContainer;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Class ForeignCurrencyContainerType
 * @package AppBundle\Administration\Form\Cashbox\Parts
 */
class ForeignCurrencyContainerType extends AbstractType
{

    /**
     * @var EntityManager
     */
    private $em;


    /**
     * ForeignCurrencyContainerType constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'allCurrencies',
                EntityType::class,
                array(
                    'class' => 'AppBundle\Administration\Entity\Currency',
                    'empty_value' => 'parameters.choose_currency',
                    'label' => 'parameters.currencies',
                    'required' => false,
                    'choice_label' => function (Currency $currency) {
                        return $currency->getCountry().' ('.$currency->getCode().')';
                    },
                    'choice_value' => 'code',
                )
            )
            ->add(
                'foreignCurrencyCounts',
                CollectionType::class,
                [
                    'entry_type' => ForeignCurrencyType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'mapped' => true,
                    'by_reference' => false,
                    'constraints' => array(
                        new Valid(),
                    ),
                    'entry_options' => [
                        'error_bubbling' => false,
                    ],
                    'error_bubbling' => false,
                ]
            );
    }
}
