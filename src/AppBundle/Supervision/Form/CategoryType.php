<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 08/03/2016
 * Time: 10:54
 */

namespace AppBundle\Supervision\Form;

use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Repository\CategoryGroupRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManager;

class CategoryType extends AbstractType
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            /*->add('name', TextType::class,
                array('label' => 'label.name',
                    'required' => true,
                    'attr' => array('class' => 'form-control'),
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value'
                            )
                        ))
                )
            )*/
            ->add(
                'translations',
                CollectionType::class,
                array(
                    'entry_type' => ProductCategoriesTranslationType::class,
                    'entry_options' => array(
                        'label' => 'keyword.label',
                        'required' => true,
                    ),
                )
            )
            ->add(
                'eligible',
                ChoiceType::class,
                array(
                    'choices' => array(
                        true => 'keyword.yes',
                        false => 'keyword.no',
                    ),
                    'label' => 'category.list.label_help',
                    'expanded' => true,
                )
            )
            ->add(
                'order',
                TextType::class,
                [
                    'required' => true,
                    'attr' => array('class' => 'form-control'),
                    "constraints" => [
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]{1,3}?$/',
                            )
                        ),
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ],
                ]
            )
            ->add(
                'categoryGroup',
                EntityType::class,
                [
                    'label' => 'label.group',
                    'required' => true,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ),
                    'class' => 'AppBundle\Merchandise\Entity\CategoryGroup',
                    'choice_label' => 'name',
                    'empty_value' => 'category.list.group_select',
                    'query_builder' => function (CategoryGroupRepository $er) {
                        return $er->createQueryBuilder('g')
                            ->where('g.active = :true')
                            ->setParameter('true', true);
                    },
                ]
            )
            ->add(
                'taxBe',
                ChoiceType::class,
                array(
                    'label' => 'category.list.tvaBel',
                    'empty_value' => 'category.list.choose_tva_bel',
                    'choices' => array(
                        '21' => '21%',
                        '12' => '12%',
                        '6' => '6%',
                        '0' => '0%',
                    ),
                    'required' => true,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ),
                )
            )
            ->add(
                'taxLux',
                ChoiceType::class,
                array(
                    'label' => 'category.list.tvaLux',
                    'empty_value' => 'category.list.choose_tva_lux',
                    'choices' => array(
                        '17' => '17%',
                        '16' => '16%',
                        '3' => '3%',
                        '0' => '0%',
                    ),
                    'required' => true,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
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
                'data_class' => ProductCategories::class,
                'translation_domain' => 'supervision',
            )
        );
    }
}
