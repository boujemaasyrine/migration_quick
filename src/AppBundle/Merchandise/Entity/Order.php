<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\Merchandise\Validator\PlanningDateOrderConstraint;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Order
 *
 * @ORM\Table(name="orders")
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\OrderRepository")
 * @PlanningDateOrderConstraint(groups={"validated_order"})
 * @ORM\HasLifecycleCallbacks()
 */
class Order
{
    use TimestampableTrait;
    use SynchronizedFlagTrait;
    use OriginRestaurantTrait; // this Trait add $originRestaurant attribut
    use ImportIdTrait;

    const DRAFT = 'draft';
    const REJECTED = 'rejected';
    const SENDING = 'sending';
    const SENDED = 'sended';
    const DELIVERED = 'delivered';
    const CANCELED = 'canceled';
    const MODIFIED = 'modified';

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
     * @ORM\Column(name="dateOrder", type="date", nullable=true)
     */
    private $dateOrder;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateDelivery", type="date", nullable=true)
     */
    private $dateDelivery;

    /**
     * @var integer
     *
     * @ORM\Column(name="numOrder", type="integer", nullable=true)
     */
    private $numOrder;

    /**
     * @var Supplier
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Supplier",inversedBy="orders")
     */
    private $supplier;

    /**
     * @var OrderLine
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\OrderLine",mappedBy="order" , cascade={"persist","remove"})
     */
    private $lines;

    /**
     * @var Delivery
     * @ORM\OneToOne(targetEntity="AppBundle\Merchandise\Entity\Delivery",mappedBy="order")
     */
    private $delivery;

    /**
     * @var \AppBundle\Staff\Entity\Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $employee;

    /**
     * @var string
     * @ORM\Column(name="status",type="string",length=10,nullable=true)
     */
    private $status;

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
     * Set dateOrder
     *
     * @param \DateTime $dateOrder
     *
     * @return Order
     */
    public function setDateOrder($dateOrder)
    {
        $this->dateOrder = $dateOrder;

        return $this;
    }

    /**
     * Get dateOrder
     *
     * @return \DateTime
     */
    public function getDateOrder()
    {
        return $this->dateOrder;
    }

    /**
     * Set dateDelivery
     *
     * @param \DateTime $dateDelivery
     *
     * @return Order
     */
    public function setDateDelivery($dateDelivery)
    {
        $this->dateDelivery = $dateDelivery;

        return $this;
    }

    /**
     * Get dateDelivery
     *
     * @return \DateTime
     */
    public function getDateDelivery()
    {
        return $this->dateDelivery;
    }

    /**
     * Set numOrder
     *
     * @param integer $numOrder
     *
     * @return Order
     */
    public function setNumOrder($numOrder)
    {
        $this->numOrder = $numOrder;

        return $this;
    }

    /**
     * Get numOrder
     *
     * @return integer
     */
    public function getNumOrder()
    {
        return $this->numOrder;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->lines = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set supplier
     *
     * @param \AppBundle\Merchandise\Entity\Supplier $supplier
     *
     * @return Order
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
     * Add line
     *
     * @param \AppBundle\Merchandise\Entity\OrderLine $line
     *
     * @return Order
     */
    public function addLine(\AppBundle\Merchandise\Entity\OrderLine $line)
    {
        $line->setOrder($this);
        $this->lines[] = $line;

        return $this;
    }

    /**
     * Remove line
     *
     * @param \AppBundle\Merchandise\Entity\OrderLine $line
     */
    public function removeLine(\AppBundle\Merchandise\Entity\OrderLine $line)
    {
        $this->lines->removeElement($line);
    }

    /**
     * Get lines
     *
     * @return OrderLine[]
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * Set delivery
     *
     * @param \AppBundle\Merchandise\Entity\Delivery $delivery
     *
     * @return Order
     */
    public function setDelivery(\AppBundle\Merchandise\Entity\Delivery $delivery = null)
    {
        $this->delivery = $delivery;

        return $this;
    }

    /**
     * Get delivery
     *
     * @return \AppBundle\Merchandise\Entity\Delivery
     */
    public function getDelivery()
    {
        return $this->delivery;
    }

    /**
     * Set employee
     *
     * @param \AppBundle\Staff\Entity\Employee $employee
     *
     * @return Order
     */
    public function setEmployee(\AppBundle\Staff\Entity\Employee $employee = null)
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
     * @param ArrayCollection $lines
     * @return $this
     */
    public function setLines(ArrayCollection $lines)
    {

        foreach ($lines as $l) {
            $l->setOrder($this);
        }

        $this->lines = $lines;

        return $this;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return Order
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

    public function getTotal()
    {
        $total = 0;

        foreach ($this->lines as $l) {
            $total += $l->getProduct()->getBuyingCost() * $l->getQty();
        }

        return $total;
    }
}
