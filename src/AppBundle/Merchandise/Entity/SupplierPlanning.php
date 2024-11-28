<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * SupplierPlanning
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\SupplierPlanningRepository")
 */
class SupplierPlanning
{
    use TimestampableTrait;
    use OriginRestaurantTrait;
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="order_day", type="smallint",nullable=true)
     */
    private $orderDay;

    /**
     * @var int
     *
     * @ORM\Column(name="delivery_day", type="smallint",nullable=true)
     */
    private $deliveryDay;


    /**
     * @var Supplier
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Supplier",inversedBy="plannings")
     */
    private $supplier;

    /**
     * @var string
     * @ORM\Column(name="start_time",type="string",length=5, nullable=true)
     */
    private $startTime;

    /**
     * @var string
     * @ORM\Column(name="end_time",type="string",length=5, nullable=true)
     */
    private $endTime;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Merchandise\Entity\ProductCategories", cascade={"persist"})
     */
    private $categories;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->supplier = new ArrayCollection();
    }

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
     * Set supplier
     *
     * @param \AppBundle\Merchandise\Entity\Supplier $supplier
     *
     * @return SupplierPlanning
     */
    public function setSupplier(\AppBundle\Merchandise\Entity\Supplier $supplier = null)
    {
        $this->supplier = $supplier;

        return $this;
    }

    /**
     * Get supplier
     *
     * @return \AppBundle\Merchandise\Entity\Supplier
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * Set startTime
     *
     * @param  string $startTime
     * @return SupplierPlanning
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return string
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime
     *
     * @param  string $endTime
     * @return SupplierPlanning
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return string
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set orderDay
     *
     * @param integer $orderDay
     *
     * @return SupplierPlanning
     */
    public function setOrderDay($orderDay)
    {
        $this->orderDay = $orderDay;

        return $this;
    }

    /**
     * Get orderDay
     *
     * @return integer
     */
    public function getOrderDay()
    {
        return $this->orderDay;
    }

    /**
     * Set deliveryDay
     *
     * @param integer $deliveryDay
     *
     * @return SupplierPlanning
     */
    public function setDeliveryDay($deliveryDay)
    {
        $this->deliveryDay = $deliveryDay;

        return $this;
    }

    /**
     * Get deliveryDay
     *
     * @return integer
     */
    public function getDeliveryDay()
    {
        return $this->deliveryDay;
    }

    public function nextOrderDate()
    {
        $today = intval(date('w'));
        $diff = $this->orderDay - $today;

        if ($diff < 0) {
            $diff = 7 + $diff;
        }

        $date = date('d/m/Y', mktime(0, 0, 0, date('m'), date('d') + $diff, date('Y')));

        return \DateTime::createFromFormat('d/m/Y', $date);
    }

    public function nextDeliveryDate()
    {
        $nextOrderDate = $this->nextOrderDate();
        $diff = $this->deliveryDay - $this->orderDay;
        if ($diff < 0) {
            $diff = 7 + $diff;
        }
        $date = date(
            'd/m/Y',
            mktime(
                0,
                0,
                0,
                $nextOrderDate->format('m'),
                intval($nextOrderDate->format('d')) + $diff,
                $nextOrderDate->format('Y')
            )
        );

        return \DateTime::createFromFormat('d/m/Y', $date);
    }


    /**
     * Add category
     *
     * @param \AppBundle\Merchandise\Entity\ProductCategories $category
     *
     * @return SupplierPlanning
     */
    public function addCategory(\AppBundle\Merchandise\Entity\ProductCategories $category)
    {
        $this->categories[] = $category;

        return $this;
    }

    /**
     * Remove category
     *
     * @param \AppBundle\Merchandise\Entity\ProductCategories $category
     */
    public function removeCategory(\AppBundle\Merchandise\Entity\ProductCategories $category)
    {
        $this->categories->removeElement($category);
    }

    /**
     * Get categories
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCategories()
    {
        return $this->categories;
    }

    public function isEligible()
    {

        if (count($this->getCategories()) == 0) {
            return true;
        }

        $eligible = false;
        foreach ($this->getCategories() as $c) {
            if ($c->getEligible()) {
                $eligible = true;
            }
        }

        return $eligible;
    }
}
