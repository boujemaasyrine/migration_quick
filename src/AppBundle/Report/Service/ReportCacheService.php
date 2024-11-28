<?php
/**
 * Date: 24/12/2018
 * Time: 14:28
 */

namespace AppBundle\Report\Service;

use AppBundle\ToolBox\Service\Cache\KeyGeneratoryStrategyInterface;
use AppBundle\ToolBox\Service\Cache\SimpleGeneratorKey;
use Monolog\Logger;

/**
 * Class ReportCacheService
 * @package AppBundle\ToolBox\Service
 */
class ReportCacheService
{

    /**
     * @var DBDataCache
     */
    protected $cache;

    /**
     * @var KeyGeneratoryStrategyInterface
     */
    protected $keyGenerator;

    /**
     * @var Logger logger
     */
    protected $logger;

    /**
     * ReportCacheService constructor.
     * @param DBDataCache $cache
     * @param SimpleGeneratorKey $generatoryStrategy
     */
    public function __construct(DBDataCache $cache, SimpleGeneratorKey $generatoryStrategy, Logger $logger)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->keyGenerator = $generatoryStrategy;

    }

    /**
     * @param $reportName
     * @param $restaurantId
     * @param $data
     * @param $ttl
     * @param null $filters
     *
     * @return string
     */
    public function cacheReport($reportName, $restaurantId, $data, $filters = null, $ttl = 500)
    {

        $cachedData = $this->serializeData($reportName, $data, $filters);
        $key = $this->keyGenerator->generateKey($reportName, $filters, $restaurantId);

        $this->cache->save($key, $cachedData, $reportName, $ttl);

        return $key;
    }

    /**
     * @param $reportName
     * @param $restaurantId
     * @param null $filters
     *
     * @return mixed
     */
    public function getReportCache($reportName, $restaurantId, $filters = null)
    {

        $cachedData = $this->cache->get($this->keyGenerator->generateKey($reportName, $filters, $restaurantId));

        if ($cachedData !== null) {
            return $this->unserialize($reportName, $cachedData->getData(), $filters);
        }

        return null;
    }

    public function setReportCache($reportName, $restaurantId, $filters = null)
    {

        $cachedData = $this->cache->get($this->keyGenerator->generateKey($reportName, $filters, $restaurantId));

        if ($cachedData !== null) {
            return $this->unserialize($reportName, $cachedData->getData(), $filters);
        }

        return null;
    }

    /**
     * @param $reportName
     * @param $data
     * @param null $filters
     * @return string
     */
    protected function serializeData($reportName, $data, $filters = null)
    {
        return base64_encode(serialize($data));
    }

    /**
     * @param $reportName
     * @param $data
     * @param null $filters
     * @return mixed
     */
    protected function unserialize($reportName, $data, $filters = null)
    {
        return unserialize(base64_decode($data));
    }

}