<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/01/2016
 * Time: 17:39
 */

namespace AppBundle\Supervision\Form\Items;

use AppBundle\Merchandise\Entity\Division;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\RecipeLine;
use AppBundle\Supervision\Entity\RecipeLineSupervision;
use AppBundle\Supervision\Form\DataTransformer\ProductToIdTransformer;
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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RecipeLineType extends AbstractType
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
            //            ->add('id', HiddenType::class)
            //            ->add('supplierCode', TextType::class, [
            //                'attr' => array('class' => 'form-control'),
            //                "constraints" => [
            //                    new NotBlank([
            //                        'groups' => 'transformed_product',
            //                    ])
            //                ],
            //                'mapped' => true
            //            ])
            ->add(
                'qty',
                TextType::class,
                [
                    'attr' => array('class' => 'form-control'),
                    "constraints" => [
                        //                    new NotBlank([
                        //                        'groups' => 'transformed_product',
                        //                    ])
                    ],
                    "required" => false,
                ]
            )
            ->add(
                'productPurchased',
                HiddenType::class,
                [
                    "constraints" => [
                        //                    new NotBlank([
                        //                        'groups' => 'transformed_product',
                        //                    ])
                    ],
                    "required" => false,
                ]
            )
            ->add(
                'productPurchasedName',
                TextType::class,
                [
                    'attr' => array('class' => 'form-control'),
                    'mapped' => true,
                ]
            );

        $builder->get('productPurchased')->addModelTransformer(new ProductToIdTransformer($this->em));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_groups' => array('transformed_product'),
                'data_class' => RecipeLineSupervision::class,
                'translation_domain' => 'supervision',
            ]
        );
    }
}
