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
 * Class TimestampableTrait
 *
 * @package                     AppBundle\Traits
 * @ORM\HasLifecycleCallbacks()
 */
trait TimestampableTrait
{

    /**
     * @var \Datetime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime" , nullable=true, options={"default" = null})
     */
    private $createdAt;

    /**
     * @var \Datetime $updatedAt
     *
     * @ORM\Column(name="updated_at", nullable=true,  type="datetime")
     */
    private $updatedAt;

    /**
     * Get createdAt
     *
     * @return \Datetime
     */
    public function getCreatedAt($format = null)
    {
        if (is_null($this->createdAt)) {
            $this->createdAt = new \DateTime('now');
        }
        if (!is_null($format)) {
            return $this->createdAt->format($format);
        }

        return $this->createdAt;
    }

    /**
     * Set createdAt
     *
     * @param  \DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \Datetime
     */
    public function getUpdatedAt($format = null)
    {
        if (!is_null($format) && !is_null($this->updatedAt)) {
            return $this->updatedAt->format($format);
        }

        return $this->updatedAt;
    }

    /**
     * Set updatedAt
     *
     * @param  \DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @param EventArgs $args
     * @PreUpdate
     */
    public function preUpdate(EventArgs $args)
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime("NOW");
        }
        $this->updatedAt = new \DateTime("NOW");
    }

    /**
     * @param EventArgs $args
     * @PrePersist
     */
    public function prePersist(EventArgs $args)
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime("NOW");
        }
        $this->updatedAt = new \DateTime("NOW");
    }
}
