<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Delivery
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\DeliveryRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Delivery
{
    use TimestampableTrait;
    use SynchronizedFlagTrait;
    use OriginRestaurantTrait;
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
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="deliveryBordereau", type="string",length=50)
     */
    private $deliveryBordereau;

    /**
     * @var float
     * @ORM\Column(name="valorization",type="float")
     */
    private $valorization;

    /**
     * @var Order
     * @ORM\OneToOne(targetEntity="AppBundle\Merchandise\Entity\Order",inversedBy="delivery")
     * @ORM\JoinColumn(nullable=true)
     */
    private $order;

    /**
     * @var DeliveryLine
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\DeliveryLine",mappedBy="delivery",cascade={"persist","remove"})
     */
    private $lines;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $employee;

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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Delivery
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set deliveryBordereau
     *
     * @param string $deliveryBordereau
     *
     * @return Delivery
     */
    public function setDeliveryBordereau($deliveryBordereau)
    {
        $this->deliveryBordereau = $deliveryBordereau;

        return $this;
    }

    /**
     * Get deliveryBordereau
     *
     * @return string
     */
    public function getDeliveryBordereau()
    {
        return $this->deliveryBordereau;
    }

    /**
     * Set valorization
     *
     * @param float $valorization
     *
     * @return Delivery
     */
    public function setValorization($valorization)
    {

        $valorization = str_replace(',', '.', $valorization);

        $this->valorization = $valorization;

        return $this;
    }

    /**
     * Get valorization
     *
     * @return float
     */
    public function getValorization()
    {
        return $this->valorization;
    }

    /**
     * Set order
     *
     * @param \AppBundle\Merchandise\Entity\Order $order
     *
     * @return Delivery
     */
    public function setOrder(\AppBundle\Merchandise\Entity\Order $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return \AppBundle\Merchandise\Entity\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Add line
     *
     * @param \AppBundle\Merchandise\Entity\DeliveryLine $line
     *
     * @return Delivery
     */
    public function addLine(\AppBundle\Merchandise\Entity\DeliveryLine $line)
    {
        $line->setDelivery($this);
        $this->lines[] = $line;

        return $this;
    }

    /**
     * Remove line
     *
     * @param \AppBundle\Merchandise\Entity\DeliveryLine $line
     */
    public function removeLine(\AppBundle\Merchandise\Entity\DeliveryLine $line)
    {
        $this->lines->removeElement($line);
    }

    /**
     * Get lines
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * Set employee
     *
     * @param \AppBundle\Staff\Entity\Employee $employee
     *
     * @return Delivery
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
     * Constructor
     */
    public function __construct()
    {
        $this->lines = new \Doctrine\Common\Collections\ArrayCollection();
    }
}
