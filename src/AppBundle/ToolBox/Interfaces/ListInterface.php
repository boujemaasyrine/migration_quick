<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 16/05/2016
 * Time: 09:31
 */

namespace AppBundle\ToolBox\Interfaces;

interface ListInterface
{
    public function serializeItems($items);

    public function listItems($criteria, $order, $limit, $offset, $search = null, $onlyList = false);
}
