<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 05/04/2016
 * Time: 08:44
 */

namespace AppBundle\Supervision\Interfaces;

interface CompartmentCalculationInterface
{
    public function calculateRealTotal();

    public function calculateTheoricalTotal();

    public function calculateGap();
}
