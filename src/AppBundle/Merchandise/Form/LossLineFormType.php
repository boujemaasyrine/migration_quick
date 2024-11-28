<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 22/02/2016
 * Time: 09:57
 */

namespace AppBundle\Merchandise\Form;

use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Merchandise\Form\DataTransformer\ProductToIdTransformer;
use Doctrine\Common\Persistence\ObjectManager;
use AppBundle\Merchandise\Entity\LossLine;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class LossLineFormType extends AbstractType
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add(
                "product",
                HiddenType::class,
                [
                    'constraints' => [
                        new NotNull(),
                    ],
                ]
            )
            ->add(
                'firstEntry',
                TextType::class,
                [
                    "constraints" => [
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+([,\.]{1}[0-9]+)?$/',
                            )
                        ),
                    ],
                ]
            )
            ->add(
                'secondEntry',
                TextType::class,
                [
                    "constraints" => [
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+([,\.]{1}[0-9]+)?$/',
                            )
                        ),
                    ],
                ]
            )
            ->add(
                'thirdEntry',
                TextType::class,
                [
                    "constraints" => [
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+([,\.]{1}[0-9]+)?$/',
                            )
                        ),
                    ],
                ]
            )
            ->add(
                'totalLoss',
                TextType::class,
                [
                    'mapped' => false,
                    "constraints" => [
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+([,\.]{1}[0-9]+)?$/',
                            )
                        ),
                    ],
                ]
            )
            ->add(
                'soldingCanal',
                EntityType::class,
                [
                    'mapped' => true,
                    'class' => SoldingCanal::class,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('s')
                            ->where('s.type = :destination')
                            ->setParameter('destination', SoldingCanal::DESTINATION)
                            ->orderBy('s.default', 'DESC');
                    },
                    'choice_translation_domain' => 'messages',
                    "constraints" => [
                        new Callback(
                            [
                                'callback' => function ($object, ExecutionContextInterface $context) {
                                    /* if($object) {
                                        $loosSheet = $context->getRoot()->getData();
                                        if (!$loosSheet instanceof LossSheet) {
                                            throw new \Exception("Expected a LossSheet Object , got " . get_class($loosSheet));
                                        }
                                        if(!$object->getProduct() instanceof ProductSold) {
                                            throw new \Exception("Expected a ProductSold Object , got " . get_class($object->getProduct()));
                                        }
        
                                        if ($loosSheet->getType() === LossSheet::FINALPRODUCT) {
                                            /**
                                             * @var $object LossLine
                                             */
                                    /*
                                            $recipe = $object->getProduct()->getSoldingCanalRecipe();
                                            if (is_null($recipe)) {
                                                $context->buildViolation(
                                                    "loss_line.this_product_has_no_recipe_with_that_solding_canal"
                                                )->addViolation();
                                            }
                                        }
        
                                    }
                                    */
                                },
                            ]
                        ),
                    ],
                ]
            );
        $builder->get('product')
            ->addModelTransformer(new ProductToIdTransformer($this->manager));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => LossLine::class,
            )
        );
    }
}
