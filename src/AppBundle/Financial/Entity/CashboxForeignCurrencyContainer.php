<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 05/04/2016
 * Time: 16:29
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * CashboxForeignCurrencyContainer
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class CashboxForeignCurrencyContainer
{

    public function __construct()
    {
        $this->ticketPayments = new ArrayCollection();
        $this->foreignCurrencyCounts = new ArrayCollection();
    }

    use IdTrait;
    use ImportIdTrait;

    /**
     * @var CashboxCount
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\CashboxCount", inversedBy="foreignCurrencyContainer")
     */
    private $cashbox;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\CashboxForeignCurrency", mappedBy="foreignCurrencyContainer", cascade={"persist"})
     */
    private $foreignCurrencyCounts;

    /**
     * @deprecated
     * @var ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\TicketPayment", mappedBy="foreignCurrencyContainer")
     */
    private $ticketPayments;

    /**
     * @return self
     */
    public function getCashbox()
    {
        return $this->cashbox;
    }

    /**
     * @param CashboxCount $cashbox
     * @return self
     */
    public function setCashbox($cashbox)
    {
        $this->cashbox = $cashbox;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getTicketPayments()
    {
        return $this->ticketPayments;
    }

    /**
     * @param ArrayCollection $ticketPayments
     * @return CashboxCount
     */
    public function setTicketPayments($ticketPayments)
    {
        foreach ($ticketPayments as $ticketPayment) {
            $ticketPayment->setForeignCurrencyContainer($this);
        }
        $this->ticketPayments = $ticketPayments;

        return $this;
    }

    /**
     * @param TicketPayment $ticketPayment
     * @return self
     */
    public function addTicketPayment(TicketPayment $ticketPayment)
    {
        $ticketPayment->setForeignCurrencyContainer($this);
        $this->ticketPayments->add($ticketPayment);

        return $this;
    }

    /**
     * @param TicketPayment $ticketPayment
     */
    public function removeTicketPayment(TicketPayment $ticketPayment)
    {
        $this->ticketPayments->removeElement($ticketPayment);
    }

    /**
     * @return ArrayCollection
     */
    public function getForeignCurrencyCounts()
    {
        return $this->foreignCurrencyCounts;
    }

    /**
     * @param ArrayCollection $foreignCurrencyCounts
     * @return CashboxCount
     */
    public function setForeignCurrencyCounts($foreignCurrencyCounts)
    {
        $this->foreignCurrencyCounts = $foreignCurrencyCounts;

        return $this;
    }

    /**
     * @param CashboxForeignCurrency $foreignCurrencyCount
     * @return self
     */
    public function addForeignCurrencyCount(CashboxForeignCurrency $foreignCurrencyCount)
    {
        $foreignCurrencyCount->setForeignCurrencyContainer($this);
        $this->foreignCurrencyCounts->add($foreignCurrencyCount);

        return $this;
    }

    /**
     * @param CashboxForeignCurrency $foreignCurrencyCount
     */
    public function removeForeignCurrencyCount(CashboxForeignCurrency $foreignCurrencyCount)
    {
        $this->foreignCurrencyCounts->removeElement($foreignCurrencyCount);
    }

    /**
     * Calculate Real foreign currency
     *
     * @return float|int
     */
    public function calculateTotalForeignCurrencyAmount()
    {
        $total = 0;
        foreach ($this->getForeignCurrencyCounts() as $foreignCurrencyCount) {
            /**
             * @var CashboxForeignCurrency $foreignCurrencyCount
             */
            $total += $foreignCurrencyCount->getTotal();
        }

        return $total;
    }

    public function calculateTheoricalTotal()
    {

        return $this->calculateTotalForeignCurrencyAmount();
    }

    public function calculateTotalGap()
    {
        return $this->calculateTotalForeignCurrencyAmount() - $this->calculateTheoricalTotal();
    }
}
