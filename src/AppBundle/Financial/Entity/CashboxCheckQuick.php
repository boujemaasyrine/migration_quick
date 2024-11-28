<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/04/2016
 * Time: 17:56
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Financial\Interfaces\TypeInterface;
use AppBundle\ToolBox\Traits\IdTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * CheckQuick
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class CashboxCheckQuick implements TypeInterface
{
    use IdTrait;

    /**
     * @var CashboxCheckQuickContainer
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\CashboxCheckQuickContainer",inversedBy="checkQuickCounts")
     */
    private $checkQuickContainer;

    /**
     * @var ChestSmallChest
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\ChestSmallChest",inversedBy="checkQuickCounts")
     */
    private $smallChest;

    /**
     * Null if type is automatic (relation with ticket payment)
     *
     * @var float
     *
     * @ORM\Column(name="qty", type="float", nullable=TRUE)
     */
    private $qty;

    /**
     * Null if type is automatic (relation with ticket payment)
     *
     * @var float
     *
     * @ORM\Column(name="unit_value", type="float")
     */
    private $unitValue;


    /**
     * @var string
     * @ORM\Column(name="check_name", type="string",nullable=TRUE)
     */
    private $checkName;

    /**
     * @var string
     * @ORM\Column(name="id_payment", type="string", nullable=TRUE)
     */
    private $idPayment;


    /**
     * @return CashboxCheckQuickContainer
     */
    public function getCheckQuickContainer()
    {
        return $this->checkQuickContainer;
    }

    /**
     * @param CashboxCheckQuickContainer $checkQuickContainer
     *
     * @return self
     */
    public function setCheckQuickContainer($checkQuickContainer)
    {
        $this->checkQuickContainer = $checkQuickContainer;

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
     *
     * @return self
     */
    public function setQty($qty)
    {
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
     *
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
     * Set smallChest
     *
     * @param \AppBundle\Financial\Entity\ChestSmallChest $smallChest
     *
     * @return CashboxCheckQuick
     */
    public function setSmallChest(
        \AppBundle\Financial\Entity\ChestSmallChest $smallChest = null
    ) {
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

    /**
     * @return string
     */
    public function getCheckName()
    {
        return $this->checkName;
    }

    /**
     * @param string $checkName
     */
    public function setCheckName($checkName)
    {
        $this->checkName = $checkName;
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
     */
    public function setIdPayment($idPayment)
    {
        $this->idPayment = $idPayment;
    }


    /**
     * @return float
     */
    public function calculateTotal()
    {
        return $this->getQty() * $this->getUnitValue();
    }
}
