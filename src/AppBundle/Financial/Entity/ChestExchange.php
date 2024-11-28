<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/04/2016
 * Time: 18:02
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Financial\Interfaces\TypeInterface;
use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * ChestExchange
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class ChestExchange implements TypeInterface
{
    use IdTrait;

    const BAG_TYPE = "BAG_TYPE";
    const ROLS_TYPE = "ROLS_TYPE";

    /**
     * @var ChestExchangeFund
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\ChestExchangeFund",inversedBy="chestExchanges")
     */
    private $chestExchangeFund;

    /**
     * @var integer
     * @ORM\Column(name="qty", type="float", nullable=TRUE)
     */
    private $qty;

    /**
     * @var integer
     * @ORM\Column(name="unit_param_id", type="integer", nullable=TRUE)
     */
    private $unitParamsId;

    /**
     * @var float
     * @ORM\Column(name="unit_value", type="float", nullable=TRUE)
     */
    private $unitValue;

    /**
     * @var string
     * @ORM\Column(name="unit_label", type="string", nullable=true)
     */
    private $unitLabel;

    /**
     * @var string
     * @ORM\Column(name="exchange_type", type="string", nullable=TRUE)
     */
    private $type;

    /**
     * Set qty
     *
     * @param float $qty
     *
     * @return ChestExchange
     */
    public function setQty($qty)
    {
        $this->qty = $qty;

        return $this;
    }

    /**
     * Get qty
     *
     * @return float
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * Set unitValue
     *
     * @param float $unitValue
     *
     * @return ChestExchange
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
     * Get unitValue
     *
     * @return float
     */
    public function getUnitValue()
    {
        return $this->unitValue;
    }

    /**
     * Set unitLabel
     *
     * @param string $unitLabel
     *
     * @return ChestExchange
     */
    public function setUnitLabel($unitLabel)
    {
        $this->unitLabel = $unitLabel;

        return $this;
    }

    /**
     * Get unitLabel
     *
     * @return string
     */
    public function getUnitLabel()
    {
        return $this->unitLabel;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return ChestExchange
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
     * Set chestExchangeFund
     *
     * @param \AppBundle\Financial\Entity\ChestExchangeFund $chestExchangeFund
     *
     * @return ChestExchange
     */
    public function setChestExchangeFund(\AppBundle\Financial\Entity\ChestExchangeFund $chestExchangeFund = null)
    {
        $this->chestExchangeFund = $chestExchangeFund;

        return $this;
    }

    /**
     * Get chestExchangeFund
     *
     * @return \AppBundle\Financial\Entity\ChestExchangeFund
     */
    public function getChestExchangeFund()
    {
        return $this->chestExchangeFund;
    }

    /**
     * @return int
     */
    public function getUnitParamsId()
    {
        return $this->unitParamsId;
    }

    /**
     * @param int $unitParamsId
     * @return ChestExchange
     */
    public function setUnitParamsId($unitParamsId)
    {
        $this->unitParamsId = $unitParamsId;

        return $this;
    }

    public function getTotal()
    {
        return $this->getQty() * $this->getUnitValue();
    }

    public function setTypeFromParameters($parameters)
    {
        foreach ($parameters as $parameter) {
            /**
             * @var Parameter $parameter
             */
            if ($parameter->getId() === $this->getUnitParamsId()) {
                $this->setType($parameter->getValue()[Parameter::TYPE]);
                break;
            }
        }
    }

    public function calculateTotal()
    {
        return $this->getQty() * $this->getUnitValue();
    }
}
