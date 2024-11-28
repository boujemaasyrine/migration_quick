<?php
/**
 * Date: 24/12/2018
 * Time: 14:23
 */

namespace AppBundle\ToolBox\Service\Cache;

/**
 * Interface DataCacheInterface
 * @package AppBundle\ToolBox\Service\Cache
 */
interface DataCacheInterface
{

    /**
     * @param $key
     * @param $data
     * @return mixed
     */
    public function save($key,$data);

    /**
     * @param $key
     * @return mixed
     */
    public function get($key);

    /**
     * @param $key
     * @return mixed
     */
    public function remove($key);

}