<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 19/02/2016
 * Time: 14:36
 */

namespace AppBundle\Merchandise\Form;

use AppBundle\Merchandise\Entity\OrderLine;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OrderLineType extends AbstractType
{

    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("product", ProductType::class)
            ->add(
                "qty",
                TextType::class,
                array(
                    'constraints' => array(
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    if (preg_match('/^[0-9]+([,\.]{1}[0-9]+)?$/', $value) == 0) {
                                        $context->addViolation("Erreur de saisie");
                                    } else {
                                        $intValue = intval($value);
                                        if ($intValue <= 0) {
                                            $context->addViolation("positive_field");
                                        }
                                    }
                                },
                                'groups' => 'validated_order',
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
                'data_class' => OrderLine::class,
            )
        );
    }
}
