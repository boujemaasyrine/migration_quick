<?php
/**
 * Created by PhpStorm.
 * User: zbessassi
 * Date: 14/12/2018
 * Time: 10:17
 */

namespace AppBundle\Supervision\Form\Items;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Entity\ProductSupervision;
use AppBundle\Supervision\Service\ItemsService;
use AppBundle\Supervision\Utils\Utilities;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints\NotNull;

class DeactivateProductForRestaurantType extends AbstractType
{

    /**
     * @var array $restaurants
     */
    private $restaurants;

    public function __construct($restaurants)
    {
        $this->restaurants = $restaurants;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'restaurant',
            EntityType::class,
            array(
                'class' => Restaurant::class,
                'choices' => $this->restaurants,
                'label' => 'keyword.restaurants',
                'multiple' => true,
                'required' => true,
                'choice_label' => function (Restaurant $r) {
                    return $r->getName() . " (" . $r->getCode() . ")";
                },
                'placeholder' => 'Veuillez choisir un/des restaurant(s)'
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'translation_domain' => 'supervision',
            )
        );
    }
}