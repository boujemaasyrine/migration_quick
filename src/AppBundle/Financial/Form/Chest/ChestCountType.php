<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/01/2016
 * Time: 17:39
 */

namespace AppBundle\Financial\Form\Chest;

use AppBundle\Financial\Entity\ChestCashboxFund;
use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Financial\Entity\ChestExchangeFund;
use AppBundle\Financial\Entity\ChestSmallChest;
use AppBundle\Financial\Entity\ChestTirelire;
use AppBundle\Financial\Form\DataTransformer\ChestCountToIdTransformer;
use AppBundle\Staff\Entity\Employee;
use AppBundle\Staff\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ChestCountType extends AbstractType
{

    private $em;

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
            ->add('id', HiddenType::class)
            ->add(
                'lastChestCount',
                HiddenType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'date',
                DateTimeType::class,
                [
                    "disabled" => true,
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy HH:mm:ss',
                    'required' => true,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ),
                ]
            )
            ->add(
                'smallChest',
                SmallChestType::class,
                [
                    'data_class' => ChestSmallChest::class,
                ]
            )
            ->add(
                'exchangeFund',
                ExchangeFundType::class,
                [
                    'data_class' => ChestExchangeFund::class,
                ]
            )
            ->add(
                'cashboxFund',
                CashboxFundType::class,
                [
                    'data_class' => ChestCashboxFund::class,
                ]
            );

        $builder->get('lastChestCount')->addModelTransformer(new ChestCountToIdTransformer($this->em));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => ChestCount::class,
            ]
        );
    }
}
