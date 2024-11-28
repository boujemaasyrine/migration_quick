<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 19/02/2016
 * Time: 15:14
 */

namespace AppBundle\Merchandise\Form;

use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Repository\SheetModel\SheetModelRepository;
use Doctrine\ORM\EntityManager;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\SheetModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class LossSheetType extends AbstractType
{
    private $em;
    private $translator;

    public function __construct(EntityManager $entityManager, Translator $translator)
    {
        $this->em = $entityManager;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add(
                'status',
                HiddenType::class
            )
            ->add(
                'entryDate',
                DateTimeType::class,
                [
                    'attr' => [
                        'class' => 'hidden',
                    ],
                    "widget" => 'single_text',
                ]
            )
            ->add(
                'model',
                EntityType::class,
                [
                    'label' => 'Modèle de feuille',
                    'class' => 'AppBundle\Merchandise\Entity\SheetModel',
                    'mapped' => true,
                    'choice_label' => 'label',
                    'placeholder' => 'loss_sheet.label.select_a_loss_sheet_model',
                    'query_builder' => function (SheetModelRepository $er) use ($options) {
                        return $er->createQueryBuilder('u')
                            ->where('u.type = :loss')
                            ->andWhere('u.originRestaurant = :restaurant')
                            ->setParameters(
                                array('loss' => $options['product_type'], 'restaurant' => $options['restaurant'])
                            );
                    },
                ]
            )
            ->add(
                'lossLines',
                CollectionType::class,
                array(
                    'entry_type' => LossLineFormType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    "error_bubbling" => false,
                    'prototype' => true,
                    //                "prototype_name" => "_loss_lines_",
                    'mapped' => true,
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
                                    $lossSheet = $context->getRoot()->getData();
                                    if (!$lossSheet instanceof LossSheet) {
                                        throw new \Exception(
                                            "Expected an LossSheet Object , got ".get_class(
                                                $lossSheet
                                            )
                                        );
                                    }
                                    // check inventory line unicity
                                    // map will contain all the selected product ids
                                    $productIds = [];
                                    $duplication = false;
                                    $duplicatedProducts = [];

                                    foreach ($lossSheet->getLossLines() as $line) {
                                        /**
                                         * @var LossLine $line
                                         */
                                        if (!$line->getProduct()) {
                                            die;
                                        }
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
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => LossSheet::class,
                'product_type' => SheetModel::ARTICLES_LOSS_MODEL,
                'restaurant' => null,
            )
        );
    }
}
