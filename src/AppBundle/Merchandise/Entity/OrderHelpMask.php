<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\Mapping as ORM;

/**
 * OrderHelpMask
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class OrderHelpMask
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startDate", type="date",nullable=true)
     */
    private $startDate;

    /**
     * @var float
     *
     * @ORM\Column(name="range", type="float",nullable=true)
     */
    private $range;

    /**
     * @var float
     *
     * @ORM\Column(name="budget", type="float",nullable=true)
     */
    private $budget;

    /**
     * @var
     * @ORM\Column(name="order_day",type="integer",nullable=true)
     */
    private $orderDay;

    /**
     * @var
     * @ORM\Column(name="delivery_day",type="integer",nullable=true)
     */
    private $deliveryDay;

    /**
     * @var
     * @ORM\Column(name="absolute_order_day",type="float",nullable=true)
     */
    private $absoluteOrderDay;

    /**
     * @var
     * @ORM\Column(name="absolute_delivery_day",type="float",nullable=true)
     */
    private $absoluteDeliveryDay;

    /**
     * @var $category
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductCategories")
     */
    private $category;

    /**
     * @var $supplier
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\OrderHelpSupplier",inversedBy="days")
     */
    private $supplier;

    /**
     * @var
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\OrderHelpTmp",inversedBy="masks")
     */
    private $helpTmp;

    /**
     * @var
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\OrderHelpMaskProduct",mappedBy="mask")
     */
    private $products;

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
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return OrderHelpMask
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set range
     *
     * @param float $range
     *
     * @return $this
     */
    public function setRange($range)
    {
        $this->range = $range;

        return $this;
    }

    /**
     * Get range
     *
     * @return float
     */
    public function getRange()
    {
        return $this->range;
    }

    /**
     * Set budget
     *
     * @param float $budget
     *
     * @return OrderHelpMask
     */
    public function setBudget($budget)
    {
        $this->budget = $budget;

        return $this;
    }

    /**
     * Get budget
     *
     * @return float
     */
    public function getBudget()
    {
        return $this->budget;
    }

    /**
     * Set orderDay
     *
     * @param integer $orderDay
     *
     * @return OrderHelpMask
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
     * Set category
     *
     * @param \AppBundle\Merchandise\Entity\ProductCategories $category
     *
     * @return OrderHelpMask
     */
    public function setCategory(\AppBundle\Merchandise\Entity\ProductCategories $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \AppBundle\Merchandise\Entity\ProductCategories
     */
    public function getCategory()
    {
        return $this->category;
    }


    /**
     * Set supplier
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpSupplier $supplier
     *
     * @return OrderHelpMask
     */
    public function setSupplier(\AppBundle\Merchandise\Entity\OrderHelpSupplier $supplier = null)
    {
        $this->supplier = $supplier;

        return $this;
    }

    /**
     * Get supplier
     *
     * @return \AppBundle\Merchandise\Entity\OrderHelpSupplier
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * Set deliveryDay
     *
     * @param integer $deliveryDay
     *
     * @return OrderHelpMask
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

    /**
     * Set helpTmp
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpTmp $helpTmp
     *
     * @return OrderHelpMask
     */
    public function setHelpTmp(\AppBundle\Merchandise\Entity\OrderHelpTmp $helpTmp = null)
    {
        $this->helpTmp = $helpTmp;

        return $this;
    }

    /**
     * Get helpTmp
     *
     * @return \AppBundle\Merchandise\Entity\OrderHelpTmp
     */
    public function getHelpTmp()
    {
        return $this->helpTmp;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->products = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add product
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpMaskProduct $product
     *
     * @return OrderHelpMask
     */
    public function addProduct(\AppBundle\Merchandise\Entity\OrderHelpMaskProduct $product)
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpMaskProduct $product
     */
    public function removeProduct(\AppBundle\Merchandise\Entity\OrderHelpMaskProduct $product)
    {
        $this->products->removeElement($product);
    }

    /**
     * Get products
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Set absoluteOrderDay
     *
     * @param integer $absoluteOrderDay
     *
     * @return OrderHelpMask
     */
    public function setAbsoluteOrderDay($absoluteOrderDay)
    {
        $this->absoluteOrderDay = $absoluteOrderDay;

        return $this;
    }

    /**
     * Get absoluteOrderDay
     *
     * @return integer
     */
    public function getAbsoluteOrderDay()
    {
        return $this->absoluteOrderDay;
    }

    /**
     * Set absoluteDeliveryDay
     *
     * @param integer $absoluteDeliveryDay
     *
     * @return OrderHelpMask
     */
    public function setAbsoluteDeliveryDay($absoluteDeliveryDay)
    {
        $this->absoluteDeliveryDay = $absoluteDeliveryDay;

        return $this;
    }

    /**
     * Get absoluteDeliveryDay
     *
     * @return integer
     */
    public function getAbsoluteDeliveryDay()
    {
        return $this->absoluteDeliveryDay;
    }

    public function getAbsoluteOrderDate()
    {
        return Utilities::getDateFromDate($this->startDate, $this->absoluteOrderDay);
    }

    public function getAbsoluteDeliveryDate()
    {
        return Utilities::getDateFromDate($this->startDate, $this->absoluteDeliveryDay);
    }

    /**
     * @return \DateTime
     */
    public function getOrderDate()
    {
        $startDay = intval($this->startDate->format('w'));
        if ($startDay <= $this->orderDay) {
            $diff = $this->orderDay - $startDay;
        } else {
            $diff = 7 - $startDay + $this->orderDay;
        }
        $dayOrder = Utilities::getDateFromDate($this->startDate, $diff);

        return $dayOrder;
    }

    /**
     * @return \DateTime
     */
    public function getDeliveryDate()
    {

        $range = $this->deliveryDay - $this->orderDay;

        if ($range <= 0) {
            $range = 7 - $this->orderDay + $this->deliveryDay;
        }

        $deliveryDate = Utilities::getDateFromDate($this->getOrderDate(), $range);

        return $deliveryDate;
    }
}
