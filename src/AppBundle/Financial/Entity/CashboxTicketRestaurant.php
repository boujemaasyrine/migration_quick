<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/04/2016
 * Time: 17:55
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Financial\Interfaces\TypeInterface;
use AppBundle\ToolBox\Traits\IdTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * CashboxTicketRestaurant
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class CashboxTicketRestaurant implements TypeInterface
{
    use IdTrait;

    /**
     * @var CashboxCount
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\CashboxCheckRestaurantContainer",inversedBy="ticketRestaurantCounts")
     */
    private $checkRestaurantContainer;

    /**
     * @var ChestSmallChest
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\ChestSmallChest",inversedBy="ticketRestaurantCounts")
     */
    private $smallChest;

    /**
     * Null if type is automatic (relation with ticket payment)
     *
     * @var                    float
     * @ORM\Column(name="qty", type="float", nullable=TRUE)
     */
    private $qty;

    /**
     * Null if type is automatic (relation with ticket payment)
     *
     * @var                           float
     * @ORM\Column(name="unit_value", type="float")
     */
    private $unitValue;

    /**
     * @var string
     * @ORM\Column(name="ticket_name", type="string")
     */
    private $ticketName;

    /**
     * @var string
     * @ORM\Column(name="id_payment", type="string", nullable=TRUE)
     */
    private $idPayment;

    /**
     * @var boolean
     * @ORM\Column(name="electronic", type="boolean", nullable=TRUE)
     */
    private $electronic;

    /**
     * @return CashboxCount
     */
    public function getCheckRestaurantContainer()
    {
        return $this->checkRestaurantContainer;
    }

    /**
     * @param CashboxCheckRestaurantContainer $checkRestaurantContainer
     * @return self
     */
    public function setCheckRestaurantContainer(CashboxCheckRestaurantContainer $checkRestaurantContainer)
    {
        $this->checkRestaurantContainer = $checkRestaurantContainer;

        return $this;
    }

    /**
     * @return float
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * @param float $qty
     * @return self
     */
    public function setQty($qty)
    {
        if (is_string($qty)) {
            $qty = str_replace(',', '.', $qty);
        }
        $this->qty = $qty;

        return $this;
    }

    /**
     * @return float
     */
    public function getUnitValue()
    {
        return $this->unitValue;
    }

    /**
     * @param float $unitValue
     * @return self
     */
    public function setUnitValue($unitValue)
    {
        if (is_string($unitValue)) {
            $unitValue = str_replace(',', '.', $unitValue);
        }
        $this->unitValue = $unitValue;

        return $this;
    }

    /**
     * @return string
     */
    public function getTicketName()
    {
        return $this->ticketName;
    }

    /**
     * @param string $ticketName
     * @return self
     */
    public function setTicketName($ticketName)
    {
        $this->ticketName = $ticketName;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isElectronic()
    {
        return $this->electronic;
    }

    /**
     * @param boolean $electronic
     * @return CashboxTicketRestaurant
     */
    public function setElectronic($electronic)
    {
        $this->electronic = $electronic;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdPayment()
    {
        return $this->idPayment;
    }

    /**
     * @param string $idPayment
     * @return CashboxTicketRestaurant
     */
    public function setIdPayment($idPayment)
    {
        $this->idPayment = $idPayment;

        return $this;
    }


    /**
     * Get electronic
     *
     * @return boolean
     */
    public function getElectronic()
    {
        return $this->electronic;
    }

    /**
     * Set smallChest
     *
     * @param \AppBundle\Financial\Entity\ChestSmallChest $smallChest
     *
     * @return CashboxTicketRestaurant
     */
    public function setSmallChest(\AppBundle\Financial\Entity\ChestSmallChest $smallChest = null)
    {
        $this->smallChest = $smallChest;

        return $this;
    }

    /**
     * Get smallChest
     *
     * @return \AppBundle\Financial\Entity\ChestSmallChest
     */
    public function getSmallChest()
    {
        return $this->smallChest;
    }

    public function calculateTotal()
    {
        return $this->getQty() * $this->getUnitValue();
    }
}
