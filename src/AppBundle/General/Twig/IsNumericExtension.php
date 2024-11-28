<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 14/06/2016
 * Time: 08:53
 */

namespace AppBundle\General\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

class IsNumericExtension extends \Twig_Extension
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('is_numeric', array($this, 'isNumeric')),
        );
    }

    public function isNumeric($value)
    {
        return is_numeric($value);
    }

    public function getName()
    {
        return 'is_numeric';
    }
}
