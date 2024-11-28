<?php
/**
 * Author: Amjed NOUIRA <anouir@bouyguestelecom.fr>
 * Date: 24/12/2018
 * Time: 17:26
 */

namespace AppBundle\ToolBox\Service\Cache;

/**
 * Interface KeyGeneratoryStrategyInterface
 * @package AppBundle\ToolBox\Service\Cache
 */
interface KeyGeneratoryStrategyInterface
{

    /**
     * @param $reportName
     * @param $filters
     * @return string
     */
    public function generateKey($reportName,$filters);

}