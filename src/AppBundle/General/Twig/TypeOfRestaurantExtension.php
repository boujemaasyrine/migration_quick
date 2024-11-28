<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 06/06/2016
 * Time: 14:01
 */

namespace AppBundle\General\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

class TypeOfRestaurantExtension extends \Twig_Extension
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('type_restaurant', array($this, 'typeRestaurant')),
        );
    }

    public function typeRestaurant()
    {
        $code = $this->container->getParameter('quick_code');
        $restaurant = $this->container->get('doctrine.orm.entity_manager')->getRepository(
            'Merchandise:Restaurant'
        )->findOneBy(
            [
                'code' => $code,
            ]
        );

        if ($restaurant) {
            return $restaurant->getType();
        }

        return null;
    }

    public function getName()
    {
        return 'type_restaurant_extension';
    }
}
