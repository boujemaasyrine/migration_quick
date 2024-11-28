<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 04/04/2016
 * Time: 18:02
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Financial\Interfaces\TypeInterface;
use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use AppBundle\Financial\Traits\TypeTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * BankCard
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class CashboxBankCard implements TypeInterface
{
    use IdTrait;

    /**
     * @var CashboxBankCardContainer
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\CashboxBankCardContainer",inversedBy="bankCardCounts")
     */
    private $bankCardContainer;

    /**
     * @var ChestSmallChest
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\ChestSmallChest",inversedBy="bankCardCounts")
     */
    private $smallChest;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float", nullable=TRUE)
     */
    private $amount;

    /**
     * @var string
     *
     * @ORM\Column(name="card_name", type="string")
     */
    private $cardName;

    /**
     * @var string
     *
     * @ORM\Column(name="id_payment", type="string", nullable=TRUE)
     */
    private $idPayment;

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     *
     * @return CashboxBankCard
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
     * @return string
     */
    public function getCardName()
    {
        return $this->cardName;
    }

    /**
     * @param string $cardName
     *
     * @return CashboxBankCard
     */
    public function setCardName($cardName)
    {
        $this->cardName = $cardName;

        return $this;
    }

    /**
     * @return CashboxBankCardContainer
     */
    public function getBankCardContainer()
    {
        return $this->bankCardContainer;
    }

    /**
     * @param CashboxBankCardContainer $bankCardContainer
     *
     * @return CashboxBankCard
     */
    public function setBankCardContainer($bankCardContainer)
    {
        $this->bankCardContainer = $bankCardContainer;

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
     *
     * @return CashboxBankCard
     */
    public function setIdPayment($idPayment)
    {
        $this->idPayment = $idPayment;

        return $this;
    }


    /**
     * Set smallChest
     *
     * @param \AppBundle\Financial\Entity\ChestSmallChest $smallChest
     *
     * @return CashboxBankCard
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
