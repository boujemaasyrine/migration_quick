<?php
/**
 * Date: 25/12/2018
 * Time: 11:02
 */

namespace AppBundle\Report\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * Class GenericCachedReport
 * @package AppBundle\Report\Entity
 * @Entity()
 */
class GenericCachedReport extends RapportTmp
{
    const REPORT_EXPIRED_TIME = 3600;

    /**
     * @var string
     * @Column(type="string",length=100)
     */
    protected $key;

    /**
     * @var string
     * @Column(type="string",nullable=true)
     */
    protected $reportName;

    /**
     * @var int
     * @Column(type="integer")
     */
    protected $expiredTime;

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     * @return GenericCachedReport
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return string
     */
    public function getReportName()
    {
        return $this->reportName;
    }

    /**
     * @param string $reportName
     * @return GenericCachedReport
     */
    public function setReportName($reportName)
    {
        $this->reportName = $reportName;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpiredTime()
    {
        return $this->expiredTime;
    }

    /**
     * @param int $expiredTime
     * @return GenericCachedReport
     */
    public function setExpiredTime($expiredTime)
    {
        $this->expiredTime = $expiredTime;
        return $this;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {

        if ($this->getUpdatedAt() === null) {
            return true;
        }

        return ($this->getUpdatedAt()->getTimestamp() + $this->expiredTime) < time();

    }

}