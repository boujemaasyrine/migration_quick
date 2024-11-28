<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 02/03/2016
 * Time: 14:09
 */

namespace AppBundle\Merchandise\Form;

use AppBundle\Merchandise\Entity\Transfer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TransferInType extends AbstractType
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->em;
        $currentRestaurant = $options["current_restaurant"];
        $builder->add(
            'numTransfer',
            TextType::class,
            array(
                'constraints' => array(
                    new NotNull(),
                    new Callback(
                        array(
                            'callback' => function (
                                $value,
                                ExecutionContextInterface $context
                            ) use (
                                $em,
                                $currentRestaurant
                            ) {
                                $t = $em->getRepository(Transfer::class)->findBy(
                                    array(
                                        'numTransfer' => trim($value),
                                        'originRestaurant' => $currentRestaurant,
                                    )
                                );
                                if ($t != null && count($t) > 0) {
                                    $context->buildViolation('num_transfert_unique')->addViolation();
                                }
                            },
                        )
                    ),
                    new Length(array('max' => 50)),
                ),
                'required' => true,
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Transfer::class,
            )
        );
    }

    public function getParent()
    {
        return TransferOutType::class;
    }
}
