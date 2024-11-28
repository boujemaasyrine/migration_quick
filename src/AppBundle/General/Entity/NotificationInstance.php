<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 16/05/2016
 * Time: 10:40
 */

namespace AppBundle\General\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * NotificationInstance
 *
 * @ORM\Table
 * @ORM\Entity(repositoryClass="AppBundle\General\Repository\NotificationInstanceRepository")
 */
class NotificationInstance
{

    use ImportIdTrait;
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\General\Entity\Notification", inversedBy="notificationInstance")
     * @ORM\JoinColumn(nullable=false)
     */
    private $notification;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     * @ORM\JoinColumn(nullable=false)
     */
    private $employee;

    /**
     * @var boolean
     * @ORM\Column(name="is_seen", type="boolean", options={"default"=false})
     */
    protected $seen;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set notification
     *
     * @param \AppBundle\General\Entity\Notification $notification
     *
     * @return NotificationInstance
     */
    public function setNotification(\AppBundle\General\Entity\Notification $notification)
    {
        $this->notification = $notification;

        return $this;
    }

    /**
     * Get notification
     *
     * @return \AppBundle\General\Entity\Notification
     */
    public function getNotification()
    {
        return $this->notification;
    }

    /**
     * Set employee
     *
     * @param \AppBundle\Staff\Entity\Employee $employee
     *
     * @return NotificationInstance
     */
    public function setEmployee(\AppBundle\Staff\Entity\Employee $employee)
    {
        $this->employee = $employee;

        return $this;
    }

    /**
     * Get employee
     *
     * @return \AppBundle\Staff\Entity\Employee
     */
    public function getEmployee()
    {
        return $this->employee;
    }


    /**
     * Set seen
     *
     * @param boolean $seen
     *
     * @return NotificationInstance
     */
    public function setSeen($seen)
    {
        $this->seen = $seen;

        return $this;
    }

    /**
     * Get seen
     *
     * @return boolean
     */
    public function iSeen()
    {
        return $this->seen;
    }
}
