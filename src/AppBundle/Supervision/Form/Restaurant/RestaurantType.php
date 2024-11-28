<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 07/03/2016
 * Time: 15:49
 */

namespace AppBundle\Supervision\Form\Restaurant;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Repository\SupplierRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\Url;

class RestaurantType extends AbstractType
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                array(
                    'label' => 'label.name',
                    'required' => true,
                    'attr' => array('class' => 'form-control'),
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ),
                )
            )
            //Gestion des recettes
            ->add(
                'reusable',
                CheckboxType::class,
                [
                    "required" => false,
                    'label' => 'Réutilisable ?',
                ]
            )
            ->add(
                'email',
                TextType::class,
                array(
                    'label' => 'label.mail',
                    'required' => true,
                    'attr' => array('class' => 'form-control'),
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Email(
                            array(
                                'message' => 'invalid_format',
                            )
                        )
                        /*new regex(
                            array(
                                'pattern' => '/^[^\W][a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\@[a-zA-Z0-9_]+(\.[a-zA-Z0-9_]+)*\.[a-zA-Z]{2,4}$/',
                                'message' => 'invalid_format',
                            )
                        ),*/
                    ),
                )
            )
            ->add(
                'manager',
                TextType::class,
                array(
                    'label' => 'label.manager',
                    'required' => true,
                    'attr' => array('class' => 'form-control'),
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
                'managerEmail',
                TextType::class,
                array(
                    'label' => 'label.managerEmail',
                    'attr' => array('class' => 'form-control'),
                    'constraints' => array(
                        new Email(
                            array(
                                'message' => 'invalid_format',
                            )
                        )
                    ),
                )
            )
            ->add(
                'managerPhone',
                TextType::class,
                array(
                    'label' => 'label.managerPhone',
                    'attr' => array('class' => 'form-control'),
                    'constraints' => array(
                        new regex(
                            array(
                                'pattern' => '/^[+]?[0-9][0-9_ ]*$/',
                                'message' => 'invalid_format',
                            )
                        )
                    ),
                )
            )
            ->add(
                'address',
                TextType::class,
                array(
                    'label' => 'label.address',
                    'attr' => array('class' => 'form-control'),
                    'required' => false,
                )
            )
            ->add(
                'phone',
                TextType::class,
                array(
                    'label' => 'label.phone',
                    'attr' => array('class' => 'form-control'),
                    'required' => true,
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new regex(
                            array(
                                'pattern' => '/^[+]?[0-9][0-9_ ]*$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ),
                )
            )
            ->add(
                'type',
                ChoiceType::class,
                array(
                    'label' => 'label.type',
                    'required' => true,
                    'attr' => array('class' => 'form-control'),
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                    ),
                    'choices' => array(
                        'restaurant.type.company' => Restaurant::COMPANY,
                        'restaurant.type.franchise' => Restaurant::FRANCHISE,
                    ),
                    'empty_value' => 'restaurant.type.choose',
                    'choices_as_values' => true,
                )
            )
            ->add(
                'suppliers',
                EntityType::class,
                [
                    'label' => 'restaurant.list.supplier_list',
                    'class' => 'AppBundle\Merchandise\Entity\Supplier',
                    'choice_label' => 'name',
                    'multiple' => true,
                    'required' => false,
                    'query_builder' => function (SupplierRepository $er) {
                        return $er->createQueryBuilder('s')
                            ->where('s.active = :true')
                            ->setParameter('true', true);
                    },
                ]
            )
            ->add('active', ChoiceType::class, array(
                'choices' => array('status.active' => true, 'status.inactive' => false),
                'expanded' => true,
                'choices_as_values' => true,
                'label' => 'label.status'
            ))

            ->add('country', ChoiceType::class, array(
                'choices' => array_combine( array_map(function($val){
                    return 'country.'.$val;
                },Restaurant::COUNTRIES),Restaurant::COUNTRIES),
                'empty_value' => 'restaurant.type.choose',
                'choices_as_values' => true,
                'label' => 'country.label'
            ))
      ;


        if (is_null($builder->getData()->getId())) {
            $builder->add(
                'code',
                TextType::class,
                array(
                    'label' => 'label.code',
                    'attr' => array('class' => 'form-control'),
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

        $formModifier =  function (FormInterface $form, Restaurant $restaurant = null)
        {
            if($restaurant and $form->get('active')->getData())
            {
                $form->add(
                    'ordersUrl',
                    TextType::class,
                    array(
                        'label' => 'label.orders_url',
                        'attr' => array('class' => 'form-control'),
                        'required' => true,
                        'mapped' => false,
                        'constraints' => array(
                            new NotNull(
                                array(
                                    'message' => 'null_value',
                                )
                            ),
                            new Url(),
                        ),
                    )
                )
                    ->add(
                        'usersUrl',
                        TextType::class,
                        array(
                            'label' => 'label.users_url',
                            'attr' => array('class' => 'form-control'),
                            'required' => true,
                            'mapped' => false,
                            'constraints' => array(
                                new NotNull(
                                    array(
                                        'message' => 'null_value',
                                    )
                                ),
                                new Url(),
                            ),
                        )
                    )
                    ->add(
                        'withdrawalUrl',
                        TextType::class,
                        array(
                            'label' => 'label.withdrawal_url',
                            'attr' => array('class' => 'form-control'),
                            'required' => false,
                            'mapped' => false,
//                            'constraints' => array(
//                                new NotNull(
//                                    array(
//                                        'message' => 'null_value',
//                                    )
//                                ),
//                                new Url(),
//                            ),
                        )
                    )

                    ->add(
                        'wyndUser',
                        TextType::class,
                        array(
                            'label' => 'label.wynd_user',
                            'attr' => array('class' => 'form-control'),
                            'required' => true,
                            'mapped' => false,
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
                        'secretKey',
                        TextType::class,
                        array(
                            'label' => 'label.secret_key',
                            'attr' => array('class' => 'form-control'),
                            'required' => true,
                            'mapped' => false,
                            'constraints' => array(
                                new NotNull(
                                    array(
                                        'message' => 'null_value',
                                    )
                                ),
                            ),
                        )
                    )->add(
                        'optikitchenPath',
                        TextType::class,
                        array(
                            'label' => 'label.optikitchen_path',
                            'attr' => array('class' => 'form-control'),
                            'required' => false,
                            'mapped' => false
                        )
                    )->add(
                        'wyndActive',
                        CheckboxType::class,
                        array(
                            'label' => 'label.wynd_active',
                            'attr' => array('class' => 'form-control'),
                            'required' => false,
                            'mapped' => false,
                            'constraints' => array(
                                new NotNull(
                                    array(
                                        'message' => 'null_value',
                                    )
                                ),
                            ),
                        )
                    )

                    ->add('openingDate', DateTimeType::class, array(
                        'mapped' => false,
                        'label' => 'label.opening_date',
                        'required' => true,
                        'widget' => 'single_text',
                        'format' => 'dd/MM/yyyy',
                        'constraints' => array(
                            new NotNull(
                                array(
                                    'message' => 'null_value',
                                )
                            )
                        ),
                    ));
            }
        };
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($formModifier){
            $restaurant = $event->getData();
           $form = $event->getForm();
            $form->get('active')->setData($restaurant->getActive());
//             $ac=$form->get('active')->getData();
//             dump($ac);
//             if($ac==false){
                 $formModifier($form, $restaurant);
//             }

        });
        $builder->get('active')->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) use($formModifier)
        {
            $restaurant = $event->getForm()->getParent()->getData();
            $formModifier($event->getForm()->getParent(), $restaurant);
        });

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Restaurant::class,
                'translation_domain' => 'supervision',
            )
        );
    }
}
