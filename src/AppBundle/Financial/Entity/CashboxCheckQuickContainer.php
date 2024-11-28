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
 * CashboxCheckQuickContainer
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class CashboxCheckQuickContainer
{


    use IdTrait;
    use ImportIdTrait;

    /**
     * @var CashboxCount
     *
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\CashboxCount", inversedBy="checkQuickContainer")
     */
    private $cashbox;

    /**
     * @var ArrayCollection
     *
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\CashboxCheckQuick", mappedBy="checkQuickContainer", cascade={"persist"})
     */
    private $checkQuickCounts;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AppBundle\Financial\Entity\TicketPayment", mappedBy="checkQuickContainer")
     */
    private $ticketPayments;

    /**
     * @var float $theoricalTotal
     */
    public $theoricalTotal;


    /**
     * CashboxCheckQuickContainer constructor.
     */
    public function __construct()
    {
        $this->checkQuickCounts = new ArrayCollection();
        $this->ticketPayments = new ArrayCollection();
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
    public function getCheckQuickCounts()
    {
        return $this->checkQuickCounts;
    }

    /**
     * @param ArrayCollection $checkQuickCounts
     *
     * @return self
     */
    public function setCheckQuickCounts($checkQuickCounts)
    {
        $this->checkQuickCounts = $checkQuickCounts;

        return $this;
    }

    /**
     * @param CashboxCheckQuick $checkQuick
     *
     * @return self
     */
    public function addCheckQuickCount(CashboxCheckQuick $checkQuick)
    {
        $checkQuick->setCheckQuickContainer($this);
        $this->checkQuickCounts->add($checkQuick);

        return $this;
    }

    /**
     * @param CashboxCheckQuick $checkQuick
     */
    public function removeCheckQuickCount(CashboxCheckQuick $checkQuick)
    {
        $this->checkQuickCounts->removeElement($checkQuick);
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
     * @return self
     */
    public function setTicketPayments($ticketPayments)
    {
        foreach ($ticketPayments as $ticketPayment) {
            /**
             * @var TicketPayment $ticketPayment
             */
            $ticketPayment->setCheckQuickContainer($this);
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
        $ticketPayment->setCheckQuickContainer($this);
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
     * @return float|int
     */
    public function calculateCheckQuickTotal()
    {
        $result = 0;
        foreach ($this->getCheckQuickCounts() as $checkQuickCount) {
            $result += $checkQuickCount->getQty()
                * $checkQuickCount->getUnitValue();
        }

        return $result;
    }

    /**
     * @return float|int
     */
    public function calculateTheoricalTotal()
    {
        if (empty($this->theoricalTotal) && $this->theoricalTotal !== 0) {
            $total = 0;
            foreach ($this->getTicketPayments() as $ticketPayment) {
                $status = $ticketPayment->getTicket()->getStatus();
                /**
                 * @var TicketPayment $ticketPayment
                 */
                if (Ticket::ABONDON_STATUS_VALUE !== $status
                    && Ticket::CANCEL_STATUS_VALUE !== $status
                ) {
                    $total += $ticketPayment->getAmount();
                } elseif (Ticket::CANCEL_STATUS_VALUE === $status
                    && $ticketPayment->getTicket()->isCountedCanceled()
                ) {
                    $total -= abs($ticketPayment->getAmount());
                }
            }

            return $total;
        } else {
            return $this->theoricalTotal;
        }

    }

    /**
     * @return float|int
     */
    public function calculateTotalGap()
    {
        return $this->calculateCheckQuickTotal()
            - $this->calculateTheoricalTotal();
    }

    public function getCheckQuickNamesAndIdPayment()
    {
        $result = [];
        foreach ($this->getCheckQuickCounts() as $checkQuickCount) {
            /**
             * @var CashboxCheckQuick $checkQuickCount
             */
            if (!in_array($checkQuickCount->getIdPayment(), $result) && !empty($checkQuickCount->getIdPayment())) {
                $result[$checkQuickCount->getIdPayment()] = [
                    'name' => $checkQuickCount->getCheckName(),
                    'id' => $checkQuickCount->getIdPayment(),
                ];
            }
        }


        return $result;
    }

    /**
     * @param $checkName
     *
     * @return float|int
     */
    public function calculateTotalByCheckName($checkName)
    {
        $result = 0;
        /**
         * @var CashboxCheckQuick $checkQuickCount
         */
        foreach ($this->getCheckQuickCounts() as $checkQuickCount) {
            if ($checkQuickCount->getCheckName() === $checkName) {
                $result += $checkQuickCount->getQty() * $checkQuickCount->getUnitValue();
            }
        }

        return $result;
    }

    /**
     * @return float
     */
    public function getTheoricalTotal()
    {
        return $this->theoricalTotal;
    }

    /**
     * @param float $theoricalTotal
     */
    public function setTheoricalTotal($theoricalTotal)
    {
        $this->theoricalTotal = $theoricalTotal;
    }

}
