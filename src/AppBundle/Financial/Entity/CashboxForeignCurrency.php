<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/04/2016
 * Time: 17:56
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Financial\Interfaces\TypeInterface;
use AppBundle\Financial\Traits\TypeTrait;
use AppBundle\ToolBox\Traits\IdTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * ForeignCurrency
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class CashboxForeignCurrency implements TypeInterface
{
    use IdTrait;

    /**
     * @var CashboxForeignCurrencyContainer
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\CashboxForeignCurrencyContainer",inversedBy="foreignCurrencyCounts")
     */
    private $foreignCurrencyContainer;

    /**
     * @var ChestSmallChest
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\ChestSmallChest",inversedBy="foreignCurrencyCounts")
     */
    private $smallChest;

    /**
     * @var float
     * @ORM\Column(name="amount", type="float", nullable=TRUE)
     */
    private $amount;

    /**
     * @var float
     * @ORM\Column(name="exchange_rate", type="float")
     */
    private $exchangeRate;

    /**
     * @var string
     * @ORM\Column(name="foreign_currency_label", type="string")
     */
    private $foreignCurrencyLabel;

    /**
     * @return CashboxForeignCurrencyContainer
     */
    public function getForeignCurrencyContainer()
    {
        return $this->foreignCurrencyContainer;
    }

    /**
     * @param CashboxForeignCurrencyContainer $foreignCurrencyContainer
     * @return CashboxForeignCurrency
     */
    public function setForeignCurrencyContainer($foreignCurrencyContainer)
    {
        $this->foreignCurrencyContainer = $foreignCurrencyContainer;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     * @return self
     */
    public function setAmount($amount)
    {
        if (is_string($amount)) {
            $amount = str_replace(',', '.', $amount);
        }
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return float
     */
    public function getExchangeRate()
    {
        return $this->exchangeRate;
    }

    /**
     * @param float $exchangeRate
     * @return self
     */
    public function setExchangeRate($exchangeRate)
    {
        if (is_string($exchangeRate)) {
            $exchangeRate = str_replace(',', '.', $exchangeRate);
        }
        $this->exchangeRate = $exchangeRate;

        return $this;
    }

    public function getTotal()
    {
        return $this->getAmount() * $this->getExchangeRate();
    }

    /**
     * @return string
     */
    public function getForeignCurrencyLabel()
    {
        return $this->foreignCurrencyLabel;
    }

    /**
     * @param string $foreignCurrencyLabel
     * @return CashboxForeignCurrency
     */
    public function setForeignCurrencyLabel($foreignCurrencyLabel)
    {
        $this->foreignCurrencyLabel = $foreignCurrencyLabel;

        return $this;
    }


    /**
     * Set smallChest
     *
     * @param \AppBundle\Financial\Entity\ChestSmallChest $smallChest
     *
     * @return CashboxForeignCurrency
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
}
