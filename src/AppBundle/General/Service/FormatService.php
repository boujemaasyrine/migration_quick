<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/03/2016
 * Time: 09:39
 */

namespace AppBundle\General\Service;

class FormatService
{

    private $dateFormat = 'd/m/Y';
    private $timeFormat = 'H:i:s';
    private $dateTimeFormat = 'd/m/Y H:i:s';

    public function dateFormat($date)
    {
        if ($date instanceof \DateTime) {
            $return = $date->format($this->dateFormat);
        } else {
            $date = new \DateTime($date);
            $return = $date->format($this->dateFormat);
        }

        return $return;
    }

    public function dateTimeFormat($date)
    {
        if ($date instanceof \DateTime) {
            $return = $date->format($this->dateTimeFormat);
        } else {
            $date = new \DateTime($date);
            $return = $date->format($this->dateTimeFormat);
        }

        return $return;
    }

    public function timeFormat($date)
    {
        if ($date instanceof \DateTime) {
            $return = $date->format($this->timeFormat);
        } else {
            $date = new \DateTime($date);
            $return = $date->format($this->timeFormat);
        }

        return $return;
    }


    /**
     * @param float $x
     * @return string
     */
    public function floatFormat($x)
    {
        return number_format($x, 2, ',', '');
    }
}
