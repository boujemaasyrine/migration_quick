<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 01/03/2016
 * Time: 13:51
 */

namespace AppBundle\Merchandise\Form;

use AppBundle\Merchandise\Form\DataTransformer\ProductToNumberTransformer;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{

    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new ProductToNumberTransformer($this->manager);
        $builder->addModelTransformer($transformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'invalid_message' => 'The selected issue does not exist',
            )
        );
    }

    public function getParent()
    {
        return TextType::class;
    }
}
