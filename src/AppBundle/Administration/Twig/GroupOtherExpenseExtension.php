<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 11/05/2016
 * Time: 18:16
 */

namespace AppBundle\Administration\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GroupOtherExpenseExtension
 */
class GroupOtherExpenseExtension extends \Twig_Extension
{
    private $container;

    /**
     * GroupOtherExpenseExtension constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('other_group_label', array($this, 'otherGroupLabel')),
        );
    }

    /**
     * @param $value
     *
     * @return null|string
     */
    public function otherGroupLabel($value)
    {
        $code = $this->container->get('paremeter.service')->getExpenseLabel($value);

        return $code;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'other_group_label_extension';
    }
}
