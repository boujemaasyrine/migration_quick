<?php
/**
 * Date: 25/12/2018
 * Time: 10:55
 */

namespace AppBundle\Report\Service;

use AppBundle\Report\Entity\GenericCachedReport;
use AppBundle\Report\Entity\RapportTmp;
use AppBundle\ToolBox\Service\Cache\DataCacheInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class DBDataCache
 * @package AppBundle\ToolBox\Service\Cache
 */
class DBDataCache implements DataCacheInterface
{

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * DBDataCache constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param $key
     * @param $data
     * @param $reportName
     * @param $ttl
     */
    public function save($key, $data, $reportName = null, $ttl = 30)
    {
        $cachedReport = $this->getReportByKey($key);
        if ($cachedReport === null) {
            $cachedReport = new GenericCachedReport();
            $cachedReport->setKey($key)
                ->setReportName($reportName)
                ->setExpiredTime($ttl)
                ->setData($data);

        } else {
            $cachedReport->setData($data);
        }
        $this->em->persist($cachedReport);
        $this->em->flush();
    }

    /**
     * @param $key
     * @return GenericCachedReport | null
     *
     */
    public function get($key)
    {
        /**
         * @var GenericCachedReport
         */
        $cachedReport = $this->getReportByKey($key);

        if ($cachedReport === null) {
            return null;
        }

        if ($cachedReport->isExpired()) {
            $this->remove($key);
            return null;
        }

        return $cachedReport;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function remove($key)
    {
        $cachedReport = $this->getReportByKey($key);
        if ($cachedReport !== null) {
            $this->em->remove($cachedReport);
            $this->em->flush();
        }
    }

    /**
     * @param $key
     * @return GenericCachedReport | null
     */
    protected function getReportByKey($key)
    {
        return $this->em->getRepository(GenericCachedReport::class)
            ->findOneByKey($key);
    }
}