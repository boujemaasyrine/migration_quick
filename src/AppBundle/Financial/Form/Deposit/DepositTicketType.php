<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 27/04/2016
 * Time: 13:56
 */

namespace AppBundle\Financial\Form\Deposit;

use AppBundle\Financial\Entity\Deposit;
use AppBundle\Staff\Form\DataTransformer\EmployeeToIdTransformer;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class DepositTicketType extends AbstractType
{

    private $em;
    private $container;

    public function __construct(EntityManager $em, Container $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('owner', HiddenType::class)
            ->add(
                'sousType',
                ChoiceType::class,
                array(
                    'label' => 'deposit.report.labels.type',
                    'required' => true,
                    'attr' => ['class' => 'form-control sortable'],
                    'empty_value' => 'envelope.choose_source',
                    'choices' => $this->container->get('deposit.service')->getNotVersedTicketTypes(),
                    'constraints' => [
                        new NotNull(),
                    ],
                )
            )
            ->add('source', HiddenType::class)
            ->add('destination', HiddenType::class)
            ->add('reference', HiddenType::class)
            ->add('affiliateCode', HiddenType::class)
            ->add('totalAmount', HiddenType::class);

        $builder->get('owner')
            ->addModelTransformer(new EmployeeToIdTransformer($this->em));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Deposit::class,
            ]
        );
    }
}
