<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 18:39
 */

namespace AppBundle\General\Service;

class WsSecurityService
{

    /**
     * @var string
     */
    protected $supervisionKey;


    public function setSupervisionKey($key)
    {
        $this->supervisionKey = $key;
    }

    public function getSupervisionKey()
    {
        return $this->supervisionKey;
    }

    public function hashKey()
    {
        //Todo to modify
        return $this->supervisionKey;
    }
}
