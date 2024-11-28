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
 * RealCashContainer
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class CashboxMealTicketContainer
{

    public function __construct()
    {
        $this->ticketPayments = new ArrayCollection();
//        $this->mealTicketCounts= new ArrayCollection();
    }

    use IdTrait;
    use ImportIdTrait;

    /**
     * @var CashboxCount
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\CashboxCount", inversedBy="mealTicketContainer")
     */
    private $cashbox;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\TicketPayment", mappedBy="mealTicketContainer")
     */
    private $ticketPayments;

    /**
     * @var ArrayCollection
     *
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\CashboxMealTicket", mappedBy="mealTicketContainer", cascade={"persist"})
     */
    private $mealTicketCounts;

    /**
     * @var float $theoricalTotal
     */
    public $theoricalTotal;


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
            $ticketPayment->setMealTicketContainer($this);
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
        $ticketPayment->setMealTicketContainer($this);
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

    public function getListBeneficiariesWyndId()
    {
        $list = [];
        $addedNames = [];
        foreach ($this->getTicketPayments() as $ticketPayment) {
            /**
             * @var TicketPayment $ticketPayment
             */
            if (!in_array($ticketPayment->getFirstName(), $addedNames)) {
                $addedNames[] = $ticketPayment->getFirstName();
                $list[] = [
                    "operator" => $ticketPayment->getFirstName(),
                    "label" => $ticketPayment->getFirstName().' '.$ticketPayment->getLastName(),
                ];
            }
        }

        return $list;
    }

    public function getTotalAmount()
    {
        if (empty($this->theoricalTotal) && $this->theoricalTotal !== 0) {
            $total = 0;
            foreach ($this->getTicketPayments() as $ticketPayment) {
                /**
                 * @var TicketPayment $ticketPayment
                 */
                if ($ticketPayment->getTicket()->getStatus() == Ticket::CANCEL_STATUS_VALUE
                    && $ticketPayment->getTicket()->isCountedCanceled()
                ) {
                    $total -= abs($ticketPayment->getAmount());
                } elseif ($ticketPayment->getTicket()->getStatus() != Ticket::CANCEL_STATUS_VALUE
                    && $ticketPayment->getTicket()->getStatus() != Ticket::ABONDON_STATUS_VALUE
                ) {
                    $total += ($ticketPayment->getAmount());
                }
            }

            return $total;
        } else {
            return $this->theoricalTotal;
        }
    }

    public function getTotalQuantity()
    {
        $total = 0;
        foreach ($this->getTicketPayments() as $ticketPayment) {
            /**
             * @var TicketPayment $ticketPayment
             */
            $total++;
        }

        return $total;
    }

    public function getAmountByOperator($operator)
    {
        $total = 0;
        foreach ($this->getTicketPayments() as $ticketPayment) {
            /**
             * @var TicketPayment $ticketPayment
             */
            if ($ticketPayment->getOperator() === $operator) {
                $total += $ticketPayment->getAmount();
            }
        }

        return $total;
    }

    public function getQuantityByOperator($operator)
    {
        $total = 0;
        foreach ($this->getTicketPayments() as $ticketPayment) {
            /**
             * @var TicketPayment $ticketPayment
             */
            if ($ticketPayment->getOperator() === $operator) {
                $total++;
            }
        }

        return $total;
    }

    public function calculateTheoricalTotal()
    {
        if (empty($this->theoricalTotal) && $this->theoricalTotal !== 0) {
            $total = 0;
            foreach ($this->getTicketPayments() as $ticketPayment) {
                /**
                 * @var TicketPayment $ticketPayment
                 */
                if ($ticketPayment->getTicket()->getStatus() == Ticket::CANCEL_STATUS_VALUE
                    && $ticketPayment->getTicket()->isCountedCanceled()
                ) {
                    $total -= abs($ticketPayment->getAmount());
                } elseif ($ticketPayment->getTicket()->getStatus() != Ticket::CANCEL_STATUS_VALUE
                    && $ticketPayment->getTicket()->getStatus() != Ticket::ABONDON_STATUS_VALUE
                ) {
                    $total += ($ticketPayment->getAmount());
                }
            }

            return $total;
        } else {
            return $this->theoricalTotal;
        }
    }

    public function calculateTotalGap()
    {
        return $this->getTotalAmount() - $this->calculateTheoricalTotal();
    }



    /**
     * @return ArrayCollection
     */
    public function getMealTicketCounts()
    {
        return $this->mealTicketCounts;
    }

    /**
     * @param ArrayCollection $mealTicketCounts
     */
    public function setMealTicketCounts($mealTicketCounts)
    {
        $this->mealTicketCounts = $mealTicketCounts;
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


//    /**
//     * @param CashboxMealTicket $cashboxMealTicket
//     *
//     * @return self
//     */
//    public function addMealTicketCount(CashboxMealTicket $cashboxMealTicket)
//    {
//        $cashboxMealTicket->setMealTicketContainer($this);
//        $this->mealTicketCounts->add($cashboxMealTicket);
//
//        return $this;
//    }
//
//    /**
//     * @param CashboxMealTicket $cashboxMealTicket
//     */
//    public function removeBankCardCount(CashboxMealTicket $cashboxMealTicket)
//    {
//        $this->mealTicketCounts->removeElement($cashboxMealTicket);
//    }
}
