<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 25/11/2015
 * Time: 10:38
 */

namespace AppBundle\ToolBox\Traits;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;

/**
 * Class LastSynchronizedDateTrait
 *
 * @package AppBundle\Traits
 */
trait LastSynchronizedAtTrait
{

    /**
     * @var \Datetime $createdAt
     *
     * @ORM\Column(name="last_synchronized_at", type="datetime" , nullable=true, options={"default" = null})
     */
    private $lastSynchronizedAt;

    /**
     * @return \Datetime
     */
    public function getLastSynchronizedAt($format = null)
    {
        if (!is_null($format) && !is_null($this->lastSynchronizedAt)) {
            return $this->lastSynchronizedAt->format($format);
        }

        return $this->lastSynchronizedAt;
    }

    /**
     * @param \Datetime $lastSynchronizedAt
     * @return LastSynchronizedAtTrait
     */
    public function setLastSynchronizedAt($lastSynchronizedAt = null)
    {
        if (!$lastSynchronizedAt) {
            $this->lastSynchronizedAt = new \DateTime("NOW");
        } else {
            $this->lastSynchronizedAt = $lastSynchronizedAt;
        }

        return $this;
    }
}
