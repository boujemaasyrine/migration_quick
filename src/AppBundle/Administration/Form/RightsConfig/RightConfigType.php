<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 26/04/2016
 * Time: 12:01
 */

namespace AppBundle\Administration\Form\RightsConfig;

use AppBundle\Administration\Form\DataTransformer\IdToRoleTransformer;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Tests\Fixtures\Entity;

/**
 * Class RightConfigType
 * @package AppBundle\Administration\Form\RightsConfig
 */
class RightConfigType extends AbstractType
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * RightConfigType constructor.
     * @param ObjectManager $manager
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('role', HiddenType::class)
            ->add(
                'right',
                EntityType::class,
                array(
                    'label' => 'label.rights',
                    'class' => 'AppBundle\Administration\Entity\Action',
                    'choice_label' => 'route',
                    'multiple' => true,
                )
            );

        $builder->get('role')
            ->addModelTransformer(new IdToRoleTransformer($this->manager));
    }
}
