<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 05/04/2016
 * Time: 08:44
 */

namespace AppBundle\Financial\Interfaces;

use AppBundle\Merchandise\Entity\Restaurant;

interface CompartmentCalculationInterface
{
    public function calculateRealTotal($estaurant);

    public function calculateTheoricalTotal(Restaurant $restaurant);

    public function calculateGap($restaurant);
}
