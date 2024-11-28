<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 02/03/2016
 * Time: 14:09
 */

namespace AppBundle\Merchandise\Form;

use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Transfer;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\TransferLine;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TransferOutType extends AbstractType
{

    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentRestaurant = $options["current_restaurant"];
        $builder
            ->add(
                'restaurant',
                EntityType::class,
                array(
                    'class' => Restaurant::class,
                    'required' => false,
                    'choice_label' => function (Restaurant $r) {
                        return $r->getName()." (".$r->getCode().")";
                    },
                    'placeholder' => $this->translator->trans("select_restaurant"),
                    'query_builder' => function (EntityRepository $er) use ($currentRestaurant) {
                        return $er->createQueryBuilder('r')
                            ->where('r.orderable = :true')
                            ->orderBy('r.name', 'ASC')
                            ->andWhere("r != :current_restaurant")
                            ->setParameters(
                                array(
                                    "true" => true,
                                    "current_restaurant" => $currentRestaurant,
                                )
                            );
                    },
                    'constraints' => array(
                        new NotNull(),
                    ),
                )
            )
            ->add(
                'dateTransfer',
                DateType::class,
                array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'constraints' => array(
                        new NotNull(),
                    ),
                )
            )
            ->add('valorization', HiddenType::class)
            ->add(
                'lines',
                CollectionType::class,
                array(
                    'entry_type' => TransferLineType::class,
                    'allow_add' => true,
                    'constraints' => array(
                        new Count(array('min' => 1)),
                    ),
                    'error_bubbling' => false,
                    'by_reference' => false,
                    'entry_options' => array(
                        'error_bubbling' => false,
                        'constraints' => array(
                            'callback' => new Callback(
                                array(
                                    'callback' => function ($value, ExecutionContextInterface $context) {

                                        $transfer = $context->getRoot()->getData();

                                        if (!$transfer instanceof Transfer) {
                                            throw new \Exception(
                                                "Expected an Transfer Object , got ".get_class($transfer)
                                            );
                                        }

                                        if (!$value instanceof TransferLine) {
                                            throw new \Exception(
                                                "Expected an TransferLine Object , got ".get_class($value)
                                            );
                                        }

                                        if ($value->getProduct()->getStatus() == ProductPurchased::INACTIVE) {
                                            $context->buildViolation('product_not_active')->addViolation();

                                            return;
                                        }

                                        foreach ($transfer->getLines() as $l) {
                                            if ($l === $value) {
                                                break;
                                            }

                                            if ($l->getProduct()->getId() == $value->getProduct()->getId()) {
                                                $context
                                                    ->buildViolation("product_already_entred")
                                                    ->addViolation();

                                                return;
                                            }
                                        }

                                        if (intval($value->getQty()) === 0
                                            && intval($value->getQtyExp()) === 0
                                            && intval($value->getQtyUse()) === 0
                                        ) {
                                            $context
                                                ->buildViolation("no_qty_was_introduced")
                                                ->addViolation();

                                            return;
                                        }
                                    },
                                )
                            ),
                        ),
                    ),
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Transfer::class,
                'current_restaurant' => null,
            )
        );
    }
}
