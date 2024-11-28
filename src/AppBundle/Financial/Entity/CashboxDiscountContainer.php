<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 05/04/2016
 * Time: 16:29
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Financial\Entity\TicketLine;
use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Common\Collections\Criteria;

/**
 * CashboxDiscountContainer
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class CashboxDiscountContainer
{

    public function __construct()
    {
        $this->ticketLines = new ArrayCollection();
    }

    use IdTrait;
    use ImportIdTrait;

    /**
     * @var CashboxCount
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\CashboxCount", inversedBy="discountContainer")
     */
    private $cashbox;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\TicketLine", mappedBy="discountContainer")
     */
    private $ticketLines;

    /**
     * @var float $theoricalTotal
     */
    public $theoricalTotal;

    /**
     * @var float $totalAmount
     */
    public $totalAmount;

    /**
     * @var array $discountLabels
     */
    private $discountLabels;

    /**
     * @var array $amountByLabelsArray
     */
    private $amountByLabelsArray;

    /**
     * @var array $quantityByLabelsArray
     */
    private $quantityByLabelsArray;

    /**
     * @var int $totalQuantity
     */
    private $totalQuantity;
	
	 /**
     * @var int $originRestaurantId
     */
    private $originRestaurantId;
	

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
    public function getTicketLines()
    {
        $criteria = Criteria::create();
        if (!empty($this->getOriginRestaurantId())) {
            $criteria->where(Criteria::expr()->eq('originRestaurantId', $this->getOriginRestaurantId()));
            return $this->ticketLines->matching($criteria);
        }
        return $this->ticketLines;
    }

    /**
     * @return mixed
     */
    public function getOriginRestaurantId()
    {
        return $this->originRestaurantId;
    }

    /**
     * @param mixed $originRestaurantId
     */
    public function setOriginRestaurantId($originRestaurantId)
    {
        $this->originRestaurantId = $originRestaurantId;
    }

    /**
     * @param ArrayCollection $ticketLines
     * @return CashboxCount
     */
    public function setTicketLines($ticketLines)
    {
        foreach ($ticketLines as $ticketLine) {
            /**
             * @var TicketLine $ticketLine
             */
            $ticketLine->setDiscountContainer($this);
        }

        $this->ticketLines = $ticketLines;

        return $this;
    }

    /**
     * @param TicketLine $ticketLine
     * @return self
     */
    public function addTicketLine(TicketLine $ticketLine)
    {
        $ticketLine->setDiscountContainer($this);
        $this->ticketLines->add($ticketLine);

        return $this;
    }

    /**
     * @param TicketLine $ticketLine
     */
    public function removeTicketLine(TicketLine $ticketLine)
    {
        $this->ticketLines->removeElement($ticketLine);
    }

    public function getTotalAmount()
    {
        if (empty($this->totalAmount) && $this->totalAmount !== 0) {
            $total = 0;
            foreach ($this->getTicketLines() as $ticketLine) {
                $status = $ticketLine->getTicket()->getStatus();
                /**
                 * @var TicketLine $ticketLine
                 */
                if ($status != Ticket::ABONDON_STATUS_VALUE
                    && $status != Ticket::CANCEL_STATUS_VALUE
                ) {
                    $total += $ticketLine->getDiscountTtc();
                } elseif ($status == Ticket::CANCEL_STATUS_VALUE && $ticketLine->getTicket()->isCountedCanceled()) {
                    $total -= abs($ticketLine->getDiscountTtc());
                }
            }

            return round(abs($total), 2);
        } else {
            return round(abs($this->totalAmount), 2);
        }
    }

    public function getTotalQuantity()
    {
        if(empty($this->totalQuantity) && $this->totalQuantity!==0){
            $labels = $this->listDiscountLabels();
            $total = 0;
            foreach ($labels as $label) {
                $tickets = [];
                foreach ($this->getTicketLines() as $ticketLine) {
                    /**
                     * @var TicketLine $ticketLine
                     */
                    if (!in_array($ticketLine->getTicket()->getId(), $tickets) && $ticketLine->getDiscountLabel() === $label
                    ) {
                        $total++;
                        $tickets[] = $ticketLine->getTicket()->getId();
                    }
                }
            }

            return $total;
        }else{
            $this->totalQuantity;
        }

    }

    public function calculateTheoricalTotal()
    {
        if (empty($this->theoricalTotal) && $this->theoricalTotal !== 0) {
            $total = 0;
            foreach ($this->getTicketLines() as $ticketLine) {
                /**
                 * @var TicketLine $ticketLine
                 */
                if ($ticketLine->getTicket()->getStatus() != Ticket::ABONDON_STATUS_VALUE
                    && $ticketLine->getTicket()->getStatus() != Ticket::CANCEL_STATUS_VALUE
                ) {
                    $total += $ticketLine->getDiscountTtc();
                }
            }

            return round(abs($total), 2);
        } else {
            return $this->theoricalTotal;
        }
    }

    public function calculateTotalGap()
    {
        return $this->getTotalAmount() - $this->calculateTheoricalTotal();
    }

    public function listDiscountLabels()
    {
        if (empty($this->getDiscountLabels())) {
            $labels = [];
            foreach ($this->getTicketLines() as $ticketLine) {
                /**
                 * @var TicketLine $ticketLine
                 */
                $labels[] = $ticketLine->getDiscountLabel();
            }
            $labels = array_unique($labels);
            $this->setDiscountLabels($labels);
            return $labels;
        } else {
            return $this->getDiscountLabels();
        }

    }

    public function quantityByLabel($label)
    {
        $quantityByLabel = $this->getQuantityByLabelsArray();
        if (array_key_exists($label, $quantityByLabel)) {
            return $quantityByLabel[$label];
        }

        $total = 0;
        $tickets = [];
        foreach ($this->getTicketLines() as $ticketLine) {
            /**
             * @var TicketLine $ticketLine
             */
            if (!in_array($ticketLine->getTicket()->getId(), $tickets) && $ticketLine->getDiscountLabel() === $label) {
                $total++;
                $tickets[] = $ticketLine->getTicket()->getId();
            }
        }

        return $total;
    }

    public function amountByLabel($label)
    {
        $amountByLabel = $this->getAmountByLabelsArray();
        if (array_key_exists($label, $amountByLabel)) {
            return round($amountByLabel[$label], 2);
        }
        $total = 0;
        foreach ($this->getTicketLines() as $ticketLine) {
            /**
             * @var TicketLine $ticketLine
             */
            if ($ticketLine->getDiscountLabel() === $label) {
                $total += ($ticketLine->getDiscountTtc());
            }
        }

        return round($total, 2);
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

    /**
     * @param float $totalAmount
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;
    }

    /**
     * @return array
     */
    public function getDiscountLabels()
    {
        return $this->discountLabels;
    }

    /**
     * @param array $discountLabels
     */
    public function setDiscountLabels($discountLabels)
    {
        $this->discountLabels = $discountLabels;
    }

    /**
     * @return mixed
     */
    public function getAmountByLabelsArray()
    {
        return $this->amountByLabelsArray;
    }

    /**
     * @param mixed $amountByLabelsArray
     */
    public function setAmountByLabelsArray($amountByLabelsArray)
    {
        $this->amountByLabelsArray = $amountByLabelsArray;
    }

    /**
     * @return array
     */
    public function getQuantityByLabelsArray()
    {
        return $this->quantityByLabelsArray;
    }

    /**
     * @param array $quantityByLabelsArray
     */
    public function setQuantityByLabelsArray($quantityByLabelsArray)
    {
        $this->quantityByLabelsArray = $quantityByLabelsArray;
    }

    /**
     * @param int $totalQuantity
     */
    public function setTotalQuantity($totalQuantity)
    {
        $this->totalQuantity = $totalQuantity;
    }

}
