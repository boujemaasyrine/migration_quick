<?php

namespace AppBundle\General\Entity;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\Supervision\Entity\ProductSupervision;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Mapping as ORM;

/**
 * SyncCmdQueue
 *
 * @ORM\Table(name="sync_cmd_queue")
 * @ORM\Entity(repositoryClass="AppBundle\General\Repository\SyncCmdQueueRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class SyncCmdQueue
{
    use OriginRestaurantTrait;
    use TimestampableTrait;

    const WAITING = 'waiting';
    const PENDING = 'pending';
    const EXECUTED = 'executed';
    const EXECUTING = 'executing';

    const UPLOAD = 'upload';
    const DOWNLOAD = 'download';

    const EXECUTED_SUCCESS = 'executed_success';
    const EXECUTED_FAIL = 'executed_fail';


    const DOWNLOAD_SOLD_ITEMS = 'sold_items';
    const DOWNLOAD_INV_ITEMS = 'inv_items';

    static public $cmdOrder = [
        self::DOWNLOAD_INV_ITEMS => -4,
        self::DOWNLOAD_SOLD_ITEMS => -3,
    ];


    public static function getDownloadConstant()
    {
        $syncReflectionObj = new \ReflectionClass(SyncCmdQueue::class);
        $constants = $syncReflectionObj->getConstants();
        $return = [];
        foreach ($constants as $key => $c) {
            if (strpos($key, 'DOWNLOAD_') === 0) {
                $return[] = $c;
            }
        }

        return $return;
    }


    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="cmd", type="string", length=255)
     */
    private $cmd;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=20)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="syncDate", type="date", nullable=true)
     */
    private $syncDate;

    /**
     * @var string
     *
     * @ORM\Column(name="params", type="text", nullable=true)
     */
    private $params;

    /**
     * @var int
     * @ORM\Column(name="order_cmd",type="integer",nullable=true)
     */
    private $order = 0;

    /**
     * @var array
     * @ORM\Column(name="errors",type="array",nullable=true)
     */
    private $errors = [];

    /**
     * @var ProductSupervision
     * @ORM\ManyToOne(targetEntity="AppBundle\Supervision\Entity\ProductSupervision", inversedBy="syncCmdQueues")
     */
    private $product;


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
     * Set cmd
     *
     * @param  string $cmd
     * @return SyncCmdQueue
     */
    public function setCmd($cmd)
    {
        $this->cmd = $cmd;

        return $this;
    }

    /**
     * Get cmd
     *
     * @return string
     */
    public function getCmd()
    {
        return $this->cmd;
    }

    /**
     * Set status
     *
     * @param  string $status
     * @return SyncCmdQueue
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set syncDate
     *
     * @param  \DateTime $syncDate
     * @return SyncCmdQueue
     */
    public function setSyncDate($syncDate)
    {
        $this->syncDate = $syncDate;

        return $this;
    }

    /**
     * Get syncDate
     *
     * @return \DateTime
     */
    public function getSyncDate()
    {
        return $this->syncDate;
    }

    /**
     * Set params
     *
     * @param  string $params
     * @return SyncCmdQueue
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get params
     *
     * @return string
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set order
     *
     * @param integer $order
     *
     * @return SyncCmdQueue
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return integer
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set errors
     *
     * @param  array $errors
     * @return SyncCmdQueue
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Get errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Set product
     *
     * @param  $product
     * @return SyncCmdQueue
     */
    public function setProduct(ProductSupervision $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return ProductSupervision
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param EventArgs $args
     * @ORM\PrePersist()
     */
    public function setCmdOrder(EventArgs $args)
    {
        $this->setOrder(self::$cmdOrder[$this->getCmd()]);
    }
}
