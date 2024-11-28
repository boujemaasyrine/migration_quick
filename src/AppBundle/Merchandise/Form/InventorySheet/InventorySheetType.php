<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/01/2016
 * Time: 17:39
 */

namespace AppBundle\Merchandise\Form\InventorySheet;

use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\Merchandise\Entity\SheetModel;
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
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class InventorySheetType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $restaurant = $options['restaurant'];
        $builder
            ->add('id', HiddenType::class)
            ->add(
                'sheetModel',
                EntityType::class,
                [
                    "mapped" => true,
                    "class" => 'AppBundle\Merchandise\Entity\SheetModel',
                    'placeholder' => "inventory.select_an_inventory_sheet",
                    'query_builder' => function (EntityRepository $er) use ($restaurant) {
                        return $er->createQueryBuilder('entityRepository')
                            ->where('entityRepository.type = :type')
                            ->andWhere('entityRepository.originRestaurant= :restaurant')
                            ->orderBy('entityRepository.label')
                            ->setParameters(array('type' => SheetModel::INVENTORY_MODEL, 'restaurant' => $restaurant));
                    },
                ]
            )
            ->add(
                'fiscalDate',
                DateTimeType::class,
                [
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => true,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ),
                ]
            )
            ->add(
                'lines',
                CollectionType::class,
                [
                    'entry_type' => InventorySheetLineType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'mapped' => true,
                    'by_reference' => false,
                    'constraints' => [
                        new Count(
                            [
                                'min' => 1,
                                'minMessage' => 'inventory.sheet_line_min',
                            ]
                        ),
                        new Callback(
                            [
                                'groups' => 'loaded_inventory',
                                'callback' => function ($value, ExecutionContextInterface $context) {
                                    $inventorySheet = $context->getRoot()->getData();
                                    if (!$inventorySheet instanceof InventorySheet) {
                                        throw new \Exception(
                                            "Expected an InventorySheet Object , got ".get_class(
                                                $inventorySheet
                                            )
                                        );
                                    }

                                    // check inventory line unicity
                                    // map will contain all the selected product ids
                                    $productIds = [];
                                    $duplication = false;
                                    $duplicatedProducts = [];

                                    foreach ($inventorySheet->getLines() as $inventoryLine) {
                                        $id = $inventoryLine->getProduct()->getId();
                                        if (in_array($id, $productIds)) {
                                            $duplication = true;
                                            if (!in_array(
                                                $inventoryLine->getProduct()->getName(),
                                                $duplicatedProducts
                                            )) {
                                                $duplicatedProducts[] = $inventoryLine->getProduct()->getName();
                                            }
                                        } else {
                                            $productIds[] = $id;
                                        }
                                    }

                                    if ($duplication) {
                                        $context->buildViolation(
                                            "inventory.lines_must_be_unique_per_inventory_sheet",
                                            [
                                                '%product%' => implode(' , ', $duplicatedProducts),
                                            ]
                                        )->addViolation();
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
                'data_class' => InventorySheet::class,
                'restaurant' => null,
            ]
        );
    }
}
