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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DepositType extends AbstractType
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('owner', HiddenType::class)
            ->add('source', HiddenType::class)
            ->add('destination', HiddenType::class)
            ->add('reference', HiddenType::class);

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
