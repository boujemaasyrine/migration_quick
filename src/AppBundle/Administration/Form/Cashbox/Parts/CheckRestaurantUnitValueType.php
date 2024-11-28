<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 06/05/2016
 * Time: 11:24
 */

namespace AppBundle\Administration\Form\Cashbox\Parts;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Class CheckRestaurantUnitValueType
 * @package AppBundle\Administration\Form\Cashbox\Parts
 */
class CheckRestaurantUnitValueType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'unitValue',
                TextType::class,
                array(
                    'label' => 'label.value',
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Regex(
                            array(
                                'pattern' => '/^[0-9]+([\.][0-9]+)?$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ),
                )
            );
    }
}
