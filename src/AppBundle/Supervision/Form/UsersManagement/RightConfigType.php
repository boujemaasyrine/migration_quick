<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 31/05/2016
 * Time: 11:25
 */

namespace AppBundle\Supervision\Form\UsersManagement;

use AppBundle\Administration\Entity\Action;
use AppBundle\Administration\Repository\ActionRepository;
use AppBundle\Supervision\Form\UsersManagement\DataTransformer\IdToRoleTransformer;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Types\StringType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RightConfigType extends AbstractType
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $actionsType = "";
        switch ($options['type']) {
            case 'restaurant':
                $actionsType = Action::RESTAURANT_ACTION_TYPE;
                break;
            case 'central':
                $actionsType = Action::CENTRAL_ACTION_TYPE;
                break;
        }
        $builder
            ->add('role', HiddenType::class)
            ->add(
                'right',
                EntityType::class,
                array(
                    'label' => 'label.rights',
                    'class' => Action::class,
                    'query_builder' => function (ActionRepository $ar) use ($actionsType) {
                        return $ar->createQueryBuilder('a')
                            ->where('a.type = :actionsType')
                            ->setParameter('actionsType', $actionsType);
                    },
                    'choice_label' => 'route',
                    'multiple' => true,
                )
            );

        $builder->get('role')
            ->addModelTransformer(new IdToRoleTransformer($this->manager));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'type' => StringType::class,
                "translation_domain" => "supervision",
            )
        );
    }
}
