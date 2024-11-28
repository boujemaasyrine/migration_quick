<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 16/05/2016
 * Time: 10:05
 */

namespace AppBundle\General\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping as ORM;

/**
 * Notification
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\General\Repository\NotificationRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Notification
{

    use TimestampableTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;

    //Notifications Type
    const PREPARED_NOT_SEND_ORDER_NOTIFICATION = 'PREPARED_NOT_SEND_ORDER_NOTIFICATION';
    const REJECTED_ORDER_NOTIFICATION = 'REJECTED_ORDER_NOTIFICATION';
    const NOT_DELIVERED_ORDER_NOTIFICATION = 'NOT_DELIVERED_ORDER_NOTIFICATION';
    const NONEXISTENT_PLU_CODE_NOTIFICATION = 'NONEXISTENT_PLU_CODE_NOTIFICATION';
    const SCHEDULE_DELIVERY_CHANGED_NOTIFICATION = 'SCHEDULE_DELIVERY_CHANGED_NOTIFICATION';
    const PREVIOUS_INVENTORY_LOSS_NOTIFICATION = 'YESTERDAY_INVENTORY_LOSS_NOTIFICATION';
    const PREVIOUS_SOLD_LOSS_NOTIFICATION = 'YESTERDAY_SOLD_LOSS_NOTIFICATION';

    //Notifications Path
    const LIST_PENDINGS_COMMANDS_PATH = 'list_pendings_commands';
    const PLANNING_SUPPLIERS_PATH = 'planning_suppliers';
    const MISSING_PLUS_PATH = 'missing_plu';
    const PREVIOUS_LOSS_PATH = 'previous_day_loss';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="type",type="string",length=50,nullable=true)
     */
    private $type;

    /**
     * @var ArrayCollection[Role]
     * @ManyToMany(targetEntity="AppBundle\Security\Entity\Role")
     */
    private $roles;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\General\Entity\NotificationInstance", mappedBy="notification")
     */
    private $notificationInstance;

    /**
     * @var string
     * @ORM\Column(name="data", type="text", nullable= TRUE)
     */
    private $data;

    /**
     * @var string
     * @ORM\Column(name="route_name", type="text", nullable= TRUE)
     */
    private $route;

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
     * Set type
     *
     * @param string $type
     *
     * @return Notification
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->notificationInstance = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add role
     *
     * @param \AppBundle\Security\Entity\Role $role
     *
     * @return Notification
     */
    public function addRole(\AppBundle\Security\Entity\Role $role)
    {
        $this->roles[] = $role;

        return $this;
    }

    /**
     * Remove role
     *
     * @param \AppBundle\Security\Entity\Role $role
     */
    public function removeRole(\AppBundle\Security\Entity\Role $role)
    {
        $this->roles->removeElement($role);
    }

    /**
     * Get roles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Add notificationInstance
     *
     * @param \AppBundle\General\Entity\NotificationInstance $notificationInstance
     *
     * @return Notification
     */
    public function addNotificationInstance(\AppBundle\General\Entity\NotificationInstance $notificationInstance)
    {
        $this->notificationInstance[] = $notificationInstance;

        return $this;
    }

    /**
     * Remove notificationInstance
     *
     * @param \AppBundle\General\Entity\NotificationInstance $notificationInstance
     */
    public function removeNotificationInstance(\AppBundle\General\Entity\NotificationInstance $notificationInstance)
    {
        $this->notificationInstance->removeElement($notificationInstance);
    }

    /**
     * Get notificationInstance
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotificationInstance()
    {
        return $this->notificationInstance;
    }

    /**
     * @return string
     */
    public function getData()
    {
        if (Utilities::is_serialized($this->data)) {
            return unserialize($this->data);
        } else {
            return $this->data;
        }
    }

    /**
     * @param Mixed $data
     * @return Notification
     */
    public function setData($data)
    {
        if (is_array($data)) {
            $data = serialize($data);
        }
        $this->data = $data;

        return $this;
    }

    /**
     * Set route
     *
     * @param string $route
     *
     * @return Notification
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Get route
     *
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }
}
