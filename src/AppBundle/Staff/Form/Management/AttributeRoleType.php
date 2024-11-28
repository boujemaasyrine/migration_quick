<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 25/04/2016
 * Time: 09:53
 */

namespace AppBundle\Staff\Form\Management;

use AppBundle\Security\Entity\Role;
use AppBundle\Security\Repository\RoleRepository;
use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class AttributeRoleType extends AbstractType
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * @var Employee $staff
         */
        $staff = $options['staff'];
        $rolesIds = array();
        $roles = $staff->getEmployeeRoles();
        foreach ($roles as $role) {
            $rolesIds[] = $role->getId();
        }
        if (empty($rolesIds)) {
            $rolesIds[] = -1;
        }
        $builder
            ->add('staff', HiddenType::class)
            ->add(
                'role',
                EntityType::class,
                array(
                    'label' => 'label.role',
                    'class' => 'AppBundle\Security\Entity\Role',
                    'empty_value' => 'staff.list.choose_role',
                    'empty_data' => null,
                    'choice_label' => 'textLabel',
                    'required' => false,
                    'query_builder' => function (RoleRepository $er) use ($rolesIds) {
                        return $er->createQueryBuilder('r')
                            ->where('r.id not in (:rolesIds)')
                            ->andWhere('r.label <> :roleEmployee')
                            ->andWhere('r.type <> :supAdmin')
                            ->setParameter('roleEmployee', Role::ROLE_EMPLOYEE)
                            ->setParameter('supAdmin', Role::CENTRAL_ROLE_TYPE)
                            ->setParameter('rolesIds', $rolesIds);
                    },
//                    'constraints' => array(
//                        new NotNull(
//                            array(
//                                'message' => 'null_value',
//                            )
//                        ),
//                    ),
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'staff' => Employee::class,
            )
        );
    }
}
