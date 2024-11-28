<?php
/**
 * Date: 24/12/2018
 * Time: 17:27
 */

namespace AppBundle\ToolBox\Service\Cache;

/**
 * Class SimpleGeneratorKey
 * @package AppBundle\ToolBox\Service\Cache
 */
class SimpleGeneratorKey implements KeyGeneratoryStrategyInterface
{

    /**
     * @param $reportName
     * @param $filters
     * @return string
     */
    public function generateKey($reportName, $filters,$restautantId = null)
    {
        $serializedFilters = md5(serialize($filters).$restautantId);

        return $reportName."_".$serializedFilters;
    }
}