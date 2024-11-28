<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 11/04/2016
 * Time: 17:16
 */

namespace AppBundle\Merchandise\Twig;

use AppBundle\Merchandise\Entity\ProductPurchased;

class ProductTwigExtension extends \Twig_Extension
{

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('offName', array($this, 'offName')),
        );
    }

    public function offName($name)
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '_', $name));
    }

    public function getName()
    {
        return 'product_twig_extension';
    }
}
