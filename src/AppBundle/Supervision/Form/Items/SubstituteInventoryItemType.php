<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/01/2016
 * Time: 17:39
 */

namespace AppBundle\Supervision\Form\Items;

use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Form\DataTransformer\ProductToIdTransformer;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SubstituteInventoryItemType extends AbstractType
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
            ->add(
                'mainProduct',
                EntityType::class,
                [
                    'class' => ProductPurchasedSupervision::class,
                ]
            )
            ->add(
                'productPurchased',
                EntityType::class,
                [
                    'label' => 'item.inventory.choose_product',
                    'class' => ProductPurchasedSupervision::class,
                    'required' => true,
                    'attr' => array('class' => 'selectize'),
                    'empty_value' => '',
                    'choice_label' => function ($product) {
                        return $product->getExternalId().' - '.$product->getName();
                    },
                    'constraints' => array(
                        new NotBlank(),
                    ),
                ]
            )
            ->add(
                'dateSynchro',
                DateType::class,
                array(
                    'label' => 'item.inventory.synchro_date',
                    'attr' => array('class' => 'form-control datepicker'),
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => false,
                )
            );

        $builder->get('productPurchased')->addModelTransformer(new ProductToIdTransformer($this->em));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => null,
                "translation_domain" => "supervision",
            ]
        );
    }
}
