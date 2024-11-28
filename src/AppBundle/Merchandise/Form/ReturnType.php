<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 01/03/2016
 * Time: 13:24
 */

namespace AppBundle\Merchandise\Form;

use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ReturnLine;
use AppBundle\Merchandise\Entity\Returns;
use AppBundle\Merchandise\Entity\Supplier;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ReturnType extends AbstractType
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
                'supplier',
                EntityType::class,
                array(
                    'required' => false,
                    'class' => Supplier::class,
                    'placeholder' => $this->translator->trans("select_supplier"),
                    'choice_label' => 'name',
                    'query_builder' => function (EntityRepository $repository) use ($currentRestaurant) {
                        return $repository->createQueryBuilder('s')
                            ->join("s.restaurants", "r")
                            ->where('s.active = :true')
                            ->andWhere("r = :currentRestaurant")
                            ->orderBy('s.name', 'ASC')
                            ->setParameters(
                                array(
                                    "true" => true,
                                    "currentRestaurant" => $currentRestaurant,
                                )
                            );
                    },
                    'constraints' => array(
                        new NotNull(),
                    ),
                )
            )
            ->add(
                'comment',
                TextareaType::class,
                array(
                    'required' => false,
                )
            )
            ->add(
                'lines',
                CollectionType::class,
                array(
                    'entry_type' => ReturnLineType::class,
                    'constraints' => array(
                        new Count(array('min' => 1)),
                    ),
                    'error_bubbling' => false,
                    'allow_add' => true,
                    'by_reference' => false,
                    'entry_options' => array(
                        'error_bubbling' => false,
                        'constraints' => array(
                            'callback' => new Callback(
                                array(
                                    'callback' => function ($value, ExecutionContextInterface $context) {

                                        $return = $context->getRoot()->getData();

                                        if (!$return instanceof Returns) {
                                            throw new \Exception(
                                                "Expected an Returns Object , got ".get_class($return)
                                            );
                                        }

                                        if (!$value instanceof ReturnLine) {
                                            throw new \Exception(
                                                "Expected an ReturnLine Object , got ".get_class($value)
                                            );
                                        }

                                        if ($value->getProduct()->getStatus() == ProductPurchased::INACTIVE) {
                                            $context->buildViolation('product_not_active')->addViolation();

                                            return;
                                        }

                                        foreach ($return->getLines() as $l) {
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
                'data_class' => Returns::class,
                "current_restaurant" => null,
            )
        );
    }
}
