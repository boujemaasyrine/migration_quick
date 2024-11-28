<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 29/04/2016
 * Time: 09:48
 */

namespace AppBundle\Administration\Form\Procedure;

use AppBundle\Administration\Entity\Action;
use AppBundle\Administration\Entity\Procedure;
use AppBundle\Security\Entity\Role;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;

class ProcedureType extends AbstractType
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var EntityManager
     */
    private $em;


    public function __construct(Translator $translator, EntityManager $em)
    {
        $this->translator = $translator;
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $actions = [];
        $actionsViaOption = $options['actions'];
        if ($actionsViaOption && is_array($actionsViaOption)) {
            foreach ($actionsViaOption as $a) {
                $actions[] = $a;
            }
        }

        $allAction = $this->em->getRepository("Administration:Action")->findBy(
            array(
                'isPage' => true,
                'type'=> Action::RESTAURANT_ACTION_TYPE
            )
        );

        $notDeletableActions = $options['not_deletable_actions'];

        foreach ($allAction as $a) {
            if (!in_array($a, $actions)) {
                $actions[] = $a;
            }
        }

        $builder
            ->add(
                'name',
                TextType::class,
                array(
                    'constraints' => new NotNull(),
                )
            )
            ->add(
                'actions',
                EntityType::class,
                array(
                    'mapped' => false,
                    'choices' => $actions,
                    'class' => Action::class,
                    'property' => function (Action $action) {
                        return $this->translator->trans($action->getName(), [], 'actions');
                    },
                    'choice_attr' => function (Action $a) use ($notDeletableActions) {
                        if (in_array($a, $notDeletableActions)) {
                            return ['cannot_be_deletable' => ''];
                        }

                        return [];
                    },
                    'multiple' => true,
                    'expanded' => false,
                    'constraints' => new Count(
                        array(
                            'min' => 1,
                        )
                    ),
                )
            )
            ->add(
                'eligibleRoles',
                EntityType::class,
                array(
                    'multiple' => true,
                    'class' => Role::class,
                    'choice_label' => 'textLabel',
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('r')->orderBy('r.textLabel', 'asc');
                    },
                )
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => Procedure::class,
                'actions' => [],
                'not_deletable_actions' => [],
            )
        );
    }
}
