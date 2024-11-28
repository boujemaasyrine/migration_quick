<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 19/05/2016
 * Time: 10:27
 */

namespace AppBundle\ToolBox\Traits;

use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\Mapping\ManyToOne;

trait OriginRestaurantTrait
{

    /**
     * @ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Restaurant")
     */
    private $originRestaurant;

    /**
     * @return Restaurant
     */
    public function getOriginRestaurant()
    {
        return $this->originRestaurant;
    }

    /**
     * @param Restaurant $originRestaurant
     * @return OriginRestaurantTrait
     */
    public function setOriginRestaurant(Restaurant $originRestaurant)
    {
        $this->originRestaurant = $originRestaurant;

        return $this;
    }
}
