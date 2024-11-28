<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 17/03/2016
 * Time: 09:28
 */

namespace AppBundle\Merchandise\Twig;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;

class TestInstanceOfExtension extends \Twig_Extension
{
    public function getTests()
    {
        return [
            new \Twig_SimpleTest(
                'productPurchased',
                function (Product $product) {
                    return $product instanceof ProductPurchased;
                }
            ),
            new \Twig_SimpleTest(
                'productSold',
                function (Product $product) {
                    return $product instanceof ProductSold;
                }
            ),
        ];
    }


    public function getName()
    {
        return 'test_instance_of_extension';
    }
}
