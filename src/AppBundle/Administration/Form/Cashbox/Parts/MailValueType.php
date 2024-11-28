<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 24/05/2016
 * Time: 11:12
 */

namespace AppBundle\Administration\Form\Cashbox\Parts;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Class MailValueType
 * @package AppBundle\Administration\Form\Cashbox\Parts
 */
class MailValueType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', HiddenType::class)
            ->add(
                'mail',
                TextType::class,
                array(
                    'label' => 'label.mail',
                    'constraints' => array(
                        new NotNull(
                            array(
                                'message' => 'null_value',
                            )
                        ),
                        new Regex(
                            array(
                                'pattern' => '/^[^\W][a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)*\.[a-zA-Z]{2,4}$/',
                                'message' => 'invalid_format',
                            )
                        ),
                    ),
                )
            );
    }
}
