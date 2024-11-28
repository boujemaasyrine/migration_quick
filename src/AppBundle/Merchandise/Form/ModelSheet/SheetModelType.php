<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/01/2016
 * Time: 17:39
 */

namespace AppBundle\Merchandise\Form\ModelSheet;

use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Entity\SheetModelLine;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SheetModelType extends AbstractType
{

    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add(
                'label',
                TextType::class,
                [
                    'required' => true,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ),
                ]
            )->add(
                'category',
                EntityType::class,
                [
                    'class' => 'AppBundle\Merchandise\Entity\ProductCategories',
                    'mapped' => false,
                    'required' => false,
                ]
            )->add(
                'lines',
                CollectionType::class,
                [
                    'entry_type' => SheetModelLineType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'mapped' => true,
                    'by_reference' => false,
                    'constraints' => [
                        new Count(
                            [
                                'min' => 1,
                                'minMessage' => 'sheet_model.sheet_line_min',
                            ]
                        ),
                        new Callback(
                            [
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    $sheetModel = $context->getRoot()->getData();
                                    if (!$sheetModel instanceof SheetModel) {
                                        throw new \Exception(
                                            "Expected an SheetModel Object , got ".get_class(
                                                $sheetModel
                                            )
                                        );
                                    }
                                    // check inventory line unicity
                                    // map will contain all the selected product ids
                                    $productIds = [];
                                    $duplication = false;
                                    $duplicatedProducts = [];

                                    foreach ($sheetModel->getLines() as $line) {
                                        /**
                                         * @var SheetModelLine $line
                                         */
                                        if (!is_null($line->getProduct()))
                                        {
                                            $id = $line->getProduct()->getId();
                                            if (in_array($id, $productIds)) {
                                                $duplication = true;
                                                if (!in_array($line->getProduct()->getName(), $duplicatedProducts)) {
                                                    $duplicatedProducts[] = $line->getProduct()->getName();
                                                }
                                            } else {
                                                $productIds[] = $id;
                                            }
                                        }
                                    }

                                    if ($duplication) {
                                        $context->buildViolation(
                                            $this->translator->trans(
                                                "sheet_model.lines_must_be_unique_per_inventory_sheet",
                                                [
                                                    '%product%' => implode(' , ', $duplicatedProducts),
                                                ],
                                                'validation'
                                            )
                                        )
                                            ->addViolation();
                                    }
                                },
                            ]
                        ),
                    ],
                    'error_bubbling' => false,
                    'entry_options' => [
                        'error_bubbling' => false,
                    ],
                ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => SheetModel::class,
            ]
        );
    }
}
