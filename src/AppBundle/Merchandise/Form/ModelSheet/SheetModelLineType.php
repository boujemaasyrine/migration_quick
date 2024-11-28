<?php

namespace AppBundle\Merchandise\Form\ModelSheet;

/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/01/2016
 * Time: 17:39
 */

use AppBundle\Merchandise\Entity\SheetModelLine;
use AppBundle\Merchandise\Form\DataTransformer\ProductToIdTransformer;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class SheetModelLineType extends AbstractType
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
        $builder->add('id', HiddenType::class)
            ->add(
                'product',
                HiddenType::class,
                [
                    'constraints' => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'orderInSheet',
                HiddenType::class
            );

        $builder->get('product')->addModelTransformer(new ProductToIdTransformer($this->em));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => SheetModelLine::class,
            ]
        );
    }
}
