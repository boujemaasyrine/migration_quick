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
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\PrePersist;

/**
 * RealCashContainer
 *
 * @ORM\Table()
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class CashboxRealCashContainer
{

    public function __construct()
    {
        $this->ticketPayments = new ArrayCollection();
    }

    use IdTrait;
    use ImportIdTrait;

    /**
     * @var CashboxCount
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\CashboxCount", inversedBy="cashContainer")
     */
    private $cashbox;

    /**
     * @var float
     * @ORM\Column(name="total_amount", type="float")
     */
    private $totalAmount;

    /**
     * @var boolean
     * @ORM\Column(name="all_amount", type="boolean")
     */
    private $allAmount = false;

    /**
     * @var integer
     * @ORM\Column(name="bill_of_100", type="integer", nullable=TRUE)
     */
    private $billOf100;

    /**
     * @var integer
     * @ORM\Column(name="bill_of_50", type="integer", nullable=TRUE)
     */
    private $billOf50;

    /**
     * @var integer
     * @ORM\Column(name="bill_of_20", type="integer", nullable=TRUE)
     */
    private $billOf20;

    /**
     * @var integer
     * @ORM\Column(name="bill_of_10", type="integer", nullable=TRUE)
     */
    private $billOf10;

    /**
     * @var integer
     * @ORM\Column(name="bill_of_5", type="integer", nullable=TRUE)
     */
    private $billOf5;

    /**
     * @var float
     * @ORM\Column(name="change", type="float", nullable=TRUE)
     */
    private $change;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\TicketPayment", mappedBy="realCashContainer")
     */
    private $ticketPayments;

    /**
     * @return float
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * @param float $totalAmount
     * @return self
     */
    public function setTotalAmount($totalAmount)
    {
        if (is_string($totalAmount)) {
            $totalAmount = str_replace(',', '.', $totalAmount);
        }
        $this->totalAmount = $totalAmount;

        return $this;
    }

    /**
     * @return float
     */
    public function getChange()
    {
        return $this->change;
    }

    /**
     * @param float $change
     * @return self
     */
    public function setChange($change)
    {
        if (is_string($change)) {
            $change = str_replace(',', '.', $change);
        }
        $this->change = $change;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAllAmount()
    {
        return $this->allAmount;
    }

    /**
     * @param boolean $allAmount
     * @return self
     */
    public function setAllAmount($allAmount)
    {
        $this->allAmount = $allAmount;

        return $this;
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
            $ticketPayment->setRealCashContainer($this);
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
        $ticketPayment->setRealCashContainer($this);
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
     * @return int
     */
    public function getBillOf100()
    {
        return $this->billOf100;
    }

    /**
     * @param int $billOf100
     * @return CashboxRealCashContainer
     */
    public function setBillOf100($billOf100)
    {
        $this->billOf100 = $billOf100;

        return $this;
    }

    /**
     * @return int
     */
    public function getBillOf50()
    {
        return $this->billOf50;
    }

    /**
     * @param int $billOf50
     * @return CashboxRealCashContainer
     */
    public function setBillOf50($billOf50)
    {
        $this->billOf50 = $billOf50;

        return $this;
    }

    /**
     * @return int
     */
    public function getBillOf20()
    {
        return $this->billOf20;
    }

    /**
     * @param int $billOf20
     * @return CashboxRealCashContainer
     */
    public function setBillOf20($billOf20)
    {
        $this->billOf20 = $billOf20;

        return $this;
    }

    /**
     * @return int
     */
    public function getBillOf10()
    {
        return $this->billOf10;
    }

    /**
     * @param int $billOf10
     * @return CashboxRealCashContainer
     */
    public function setBillOf10($billOf10)
    {
        $this->billOf10 = $billOf10;

        return $this;
    }

    /**
     * @return int
     */
    public function getBillOf5()
    {
        return $this->billOf5;
    }

    /**
     * @param int $billOf5
     * @return CashboxRealCashContainer
     */
    public function setBillOf5($billOf5)
    {
        $this->billOf5 = $billOf5;

        return $this;
    }

    public function getTotal()
    {
        if ($this->isAllAmount()) {
            return $this->getTotalAmount();
        } else {
            $total = 0;
            $total += $this->getBillOf100() * 100;
            $total += $this->getBillOf50() * 50;
            $total += $this->getBillOf20() * 20;
            $total += $this->getBillOf10() * 10;
            $total += $this->getBillOf5() * 5;
            $total += $this->getChange();

            return $total;
        }
    }

    public function getOnlyBillsTotal()
    {
        if ($this->isAllAmount()) {
            return $this->getTotalAmount();
        } else {
            $total = 0;
            $total += $this->getBillOf100() * 100;
            $total += $this->getBillOf50() * 50;
            $total += $this->getBillOf20() * 20;
            $total += $this->getBillOf10() * 10;
            $total += $this->getBillOf5() * 5;

            return $total;
        }
    }

    public function calculateTheoricalTotal()
    {
        $total = 0;
        foreach ($this->getTicketPayments() as $ticketPayment) {
            $status = $ticketPayment->getTicket()->getStatus();
            /**
             * @var TicketPayment $ticketPayment
             */
            if ($status != Ticket::ABONDON_STATUS_VALUE && $status != Ticket::CANCEL_STATUS_VALUE) {
                $total += $ticketPayment->getAmount();
            } elseif ($status == Ticket::CANCEL_STATUS_VALUE && $ticketPayment->getTicket()->isCountedCanceled()) {
                $total -= abs($ticketPayment->getAmount());
            }
        }
        if ($this->getCashbox()->getBankCardContainer()) {
            $total -= abs($this->getCashbox()->getBankCardContainer()->calculateCanceledBankCardPayments());
        }

        return $total;
    }

    public function calculateTotalGap()
    {
        return $this->getTotal() - $this->calculateTheoricalTotal();
    }

    /**
     * @ORM\PreUpdate
     * @ORM\PrePersist
     */
    public function updateTotalAmount()
    {
        $this->setTotalAmount($this->getTotal());
    }
}
