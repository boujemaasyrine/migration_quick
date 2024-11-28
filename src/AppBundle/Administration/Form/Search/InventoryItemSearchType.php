<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 15/03/2016
 * Time: 14:53
 */

namespace AppBundle\Administration\Form\Search;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Doctrine\ORM\EntityRepository;

/**
 * Class InventoryItemSearchType
 * @package AppBundle\Administration\Form\Search
 */
class InventoryItemSearchType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentRestaurant = $options['restaurant'];
        $builder
            ->add(
                'name',
                TextType::class,
                array('label' => 'label.name')
            )
            ->add(
                'suppliers',
                EntityType::class,
                [
                    'label' => 'keyword.supplier',
                    'class' => 'AppBundle\Merchandise\Entity\Supplier',
                    'choice_label' => 'name',
                    'empty_value' => 'item.inventory.choose_supplier',
                    'query_builder' => function (EntityRepository $repo) use ($currentRestaurant) {
                        return $repo->createQueryBuilder('s')
                            ->join("s.restaurants", "r")
                            ->andWhere("r = :currentRestaurant")
                            ->andWhere('s.active = :status')
                            ->setParameter('currentRestaurant', $currentRestaurant)
                            ->setParameter('status', true)
                            ->orderBy('s.name', 'asc');
                    },
                ]
            )
            ->add(
                'status',
                ChoiceType::class,
                array(
                    'label' => 'keyword.status',
                    'empty_value' => 'item.inventory.choose_status',
                    'choices' => array(
                        'active' => 'status.active',
                        'toInactive' => 'status.toInactive',
                    ),
                )
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'restaurant' => null,
            )
        );
    }
}
