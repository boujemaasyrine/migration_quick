<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 30/05/2016
 * Time: 16:31
 */

namespace AppBundle\Supervision\Form\UsersManagement;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Security\Repository\RoleRepository;
use AppBundle\Security\Entity\Role;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AddUserType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $role = $options['role'];

        $builder
            ->add(
                'username',
                TextType::class,
                array(
                    'label' => 'users.username',
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
                'firstName',
                TextType::class,
                array(
                    'label' => 'users.first_name',
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
                'lastName',
                TextType::class,
                array(
                    'label' => 'users.last_name',
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
                        new Regex(
                            array(
                                'pattern' => '/^[^\W][a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+?)*\@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.[a-zA-Z]{2,4}$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ),
                )
            )
            ->add(
                'role',
                EntityType::class,
                array(
                    'label' => 'central_role',
                    'empty_value' => 'users.list.choose_role',
                    'mapped' => false,
                    'data' => $role,
                    'class' => Role::class,
                    'query_builder' => function (RoleRepository $rr) {
                        return $rr->createQueryBuilder('r')
                            ->where('r.type = :central')
                            ->andWhere('r.label <> :roleAdmin')
                            ->andWhere('r.label <> :roleSupervision')
                            ->setParameter('roleAdmin', Role::ROLE_ADMIN)
                            ->setParameter('roleSupervision', Role::ROLE_SUPERVISION)
                            ->setParameter('central', Role::CENTRAL_ROLE_TYPE);
                    },
                    'attr' => array('class' => 'form-control'),
                    'choice_label' => 'textLabel',
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
                'eligibleRestaurants',
                EntityType::class,
                array(
                    'label' => 'access_bo_restaurants',
                    'class' => Restaurant::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'query_builder' => function(EntityRepository $e)
                    {
                       return $e->getOpenedRestaurantsQuery();
                    },
                    'constraints' => array(
                        new Callback(
                            array(
                                'callback' => function ($value, ExecutionContextInterface $context) {

                                    if (count($value) == 0) {
                                        $context->buildViolation('null_value')->addViolation();
                                    }
                                },
                            )
                        ),
                    ),
                )
            );

        if ($builder->getData()->getId() == null) {
            $builder
                ->add(
                    'password',
                    PasswordType::class,
                    array(
                        'label' => 'labels.password',
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
                    'confirmPassword',
                    PasswordType::class,
                    array(
                        'label' => 'password_confirmation',
                        'attr' => array('class' => 'form-control'),
                        'mapped' => false,
                        'constraints' => array(
                            new NotNull(
                                array(
                                    'message' => 'null_value',
                                )
                            ),
                            new Callback(
                                array(
                                    'callback' => function ($value, ExecutionContextInterface $context) {

                                        $rootData = $context->getRoot()->getData();

                                        $password = $rootData->getPassword();
                                        if ($password === null) {
                                            return;
                                        }

                                        if ($value != $password && $value != '' && $password != '') {
                                            $context->buildViolation('confirm_password_failed')->addViolation();
                                        }
                                    },
                                )
                            ),
                        ),
                    )
                );
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Employee::class,
                'role' => Role::class,
                'translation_domain' => 'supervision',
            )
        );
    }
}
