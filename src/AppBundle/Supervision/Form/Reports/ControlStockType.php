<?php
/**
 * Created by PhpStorm.
 * User: hmnaouar
 * Date: 14/11/2017
 * Time: 15:50
 */

namespace AppBundle\Supervision\Form\Reports;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\SheetModel;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class ControlStockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $restaurants = $options["restaurants"];
        $builder->add(
            'startDate',
            DateType::class,
            array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'constraints' => new NotNull(),
            )
        )->add(
            'endDate',
            DateType::class,
            array(
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'constraints' => new NotNull(),
            )
        )->add(
            'restaurant',
            EntityType::class,
            array(
                'class' => Restaurant::class,
                'choice_label' => 'name',
                'choices' => $restaurants,
                'constraints' => new NotNull(),
                'data' => $restaurants[0],
            )
        );

        $formModifier = function (FormInterface $form, Restaurant $restaurant = null) {
            if ($restaurant != null) {
                $form->add(
                    'sheetModel',
                    EntityType::class,
                    array(
                        'class' => SheetModel::class,
                        'choice_name' => 'label',
                        'query_builder' => function (EntityRepository $er) use ($restaurant) {
                            return $er->createQueryBuilder('s')
                                ->where('s.type = :inv')
                                ->andWhere("s.originRestaurant = :restaurant")
                                ->setParameters(
                                    array(
                                        "restaurant" => $restaurant,
                                        'inv' => SheetModel::INVENTORY_MODEL,
                                    )
                                );
                        },
                        'constraints' => new NotNull(),
                    )
                );
            }
        };


        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $form = $event->getForm();
                $formModifier($form, $form->get("restaurant")->getData());
            }
        );
        $builder->get('restaurant')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $form = $event->getForm()->getParent();
                $formModifier($form, $form->get("restaurant")->getData());
            }
        );
    }

    public function getName()
    {
        return 'form';
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array("restaurants" => null,));
    }
}
