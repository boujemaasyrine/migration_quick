<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 05/04/2016
 * Time: 16:29
 */

namespace AppBundle\Financial\Entity;

use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * RealCashContainer
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class CashboxBankCardContainer
{

    use IdTrait;
    use ImportIdTrait;

    /**
     * @var CashboxCount
     *
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\CashboxCount", inversedBy="bankCardContainer")
     */
    private $cashbox;

    /**
     * @var ArrayCollection
     *
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\CashboxBankCard", mappedBy="bankCardContainer", cascade={"persist"})
     */
    private $bankCardCounts;

    /**
     * @var ArrayCollection
     *
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\TicketPayment", mappedBy="bankCardContainer")
     */
    private $ticketPayments;

    /**
     * CashboxBankCardContainer constructor.
     */
    public function __construct()
    {
        $this->ticketPayments = new ArrayCollection();
        $this->bankCardCounts = new ArrayCollection();
    }

    /**
     * @return CashboxCount
     */
    public function getCashbox()
    {
        return $this->cashbox;
    }

    /**
     * @param CashboxCount $cashbox
     *
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
    public function getBankCardCounts()
    {
        return $this->bankCardCounts;
    }

    /**
     * @param ArrayCollection $bankCardCounts
     *
     * @return CashboxBankCardContainer
     */
    public function setBankCardCounts($bankCardCounts)
    {
        $this->bankCardCounts = $bankCardCounts;

        return $this;
    }

    /**
     * @param CashboxBankCard $cashboxBankCard
     *
     * @return self
     */
    public function addBankCardCount(CashboxBankCard $cashboxBankCard)
    {
        $cashboxBankCard->setBankCardContainer($this);
        $this->bankCardCounts->add($cashboxBankCard);

        return $this;
    }

    /**
     * @param CashboxBankCard $cashboxBankCard
     */
    public function removeBankCardCount(CashboxBankCard $cashboxBankCard)
    {
        $this->bankCardCounts->removeElement($cashboxBankCard);
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
     *
     * @return CashboxCount
     */
    public function setTicketPayments($ticketPayments)
    {
        foreach ($ticketPayments as $ticketPayment) {
            $ticketPayment->setBankCardContainer($this);
        }
        $this->ticketPayments = $ticketPayments;

        return $this;
    }

    /**
     * @param TicketPayment $ticketPayment
     *
     * @return self
     */
    public function addTicketPayment(TicketPayment $ticketPayment)
    {
        $ticketPayment->setBankCardContainer($this);
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
     * @param null $idPayment
     *
     * @return float|int|number
     */
    public function calculateBankCardTotal($idPayment = null)
    {
        $result = 0;
        if ($this->getCashbox()->isEft()) {
            return $this->calculateTheoricalTotal($idPayment);
        }
            foreach ($this->getBankCardCounts() as $bankCardCount) {
                /**
                 * @var CashboxBankCard $bankCardCount
                 */
                if (is_null($idPayment) || ($idPayment === $bankCardCount->getIdPayment())) {
                    $result += $bankCardCount->getAmount();
                }
            }


        return $result;
    }

    /**
     * @param null $idPayment
     *
     * @return int|number
     */
    public function calculateTheoricalTotal($idPayment = null)
    {
        $total = 0;
        foreach ($this->getTicketPayments() as $ticketPayment) {
            $status = $ticketPayment->getTicket()->getStatus();
            /**
             * @var TicketPayment $ticketPaymentcalculateTheoricalTotal
             */
            if (Ticket::ABONDON_STATUS_VALUE !== $status && Ticket::CANCEL_STATUS_VALUE !== $status) {
                if (is_null($idPayment) || ($idPayment === $ticketPayment->getIdPayment())) {
                    $total += $ticketPayment->getAmount();
                }
            } elseif ( Ticket::CANCEL_STATUS_VALUE === $status && $ticketPayment->getTicket()->isCountedCanceled()) {
                $total -= abs($ticketPayment->getAmount());
            }

            if (Ticket::CANCEL_STATUS_VALUE === $status && !$ticketPayment->getTicket()->isCountedCanceled()
                && (is_null($idPayment) || ($idPayment === $ticketPayment->getIdPayment()))
            ) {
                $total += $ticketPayment->getAmount();
            }
        }

        return $total;
    }

    /**
     * @return int
     */
    public function calculateCanceledBankCardPayments()
    {
        $total = 0;
        foreach ($this->getTicketPayments() as $ticketPayment) {
            /**
             * @var TicketPayment $ticketPaymentcalculateTheoricalTotal
             */
            if ($ticketPayment->getTicket()->getStatus() === Ticket::CANCEL_STATUS_VALUE
                //                && !$ticketPayment->getTicket()->isCountedCanceled()
            ) {
                $total += $ticketPayment->getAmount();
            }
        }

        return $total;
    }

    public function calculateTotalGap()
    {
        return $this->calculateBankCardTotal() - $this->calculateTheoricalTotal();
    }
}
