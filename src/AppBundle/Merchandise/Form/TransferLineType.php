<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 02/03/2016
 * Time: 14:13
 */

namespace AppBundle\Merchandise\Form;

use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\TransferLine;
use Doctrine\ORM\EntityManager;
use Proxies\__CG__\AppBundle\Merchandise\Entity\CaPrev;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TransferLineType extends AbstractType
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->em;
        $builder
            ->add(
                'product',
                ProductType::class,
                array(
                    'constraints' => array(
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) use ($em) {
                                    $purchasedProduct = $em
                                        ->getRepository(ProductPurchased::class)
                                        ->find($value);

                                    if ($purchasedProduct === null) {
                                        $context->buildViolation('product_not_found')->addViolation();

                                        return;
                                    }

                                    if ($purchasedProduct->getStatus() === ProductPurchased::INACTIVE) {
                                        $context->buildViolation('product_not_active')->addViolation();

                                        return;
                                    }
                                },
                            )
                        ),
                    ),
                )
            )
            ->add(
                'qty',
                TextType::class,
                array(
                    'required' => false,
                    'constraints' => array(
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if (preg_match('/^[0-9]+$/', $value) == 0) {
                                        $context->buildViolation('int_postive_field')->addViolation();

                                        return;
                                    }
                                },
                            )
                        ),
                    ),
                )
            )
            ->add(
                'qtyExp',
                TextType::class,
                array(
                    'required' => false,
                    'constraints' => array(
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if (preg_match('/^[0-9]+$/', $value) == 0) {
                                        $context->buildViolation('int_postive_field')->addViolation();

                                        return;
                                    }
                                },
                            )
                        ),
                    ),
                )
            )
            ->add(
                'qtyUse',
                TextType::class,
                array(
                    'required' => false,
                    'constraints' => array(
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if (preg_match('/^[0-9]+$/', $value) == 0) {
                                        $context->buildViolation('int_postive_field')->addViolation();

                                        return;
                                    }
                                },
                            )
                        ),
                    ),
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => TransferLine::class,
            )
        );
    }
}
