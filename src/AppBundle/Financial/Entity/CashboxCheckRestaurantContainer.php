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
class CashboxCheckRestaurantContainer
{

    use IdTrait;
    use ImportIdTrait;

    /**
     * @var CashboxCount
     *
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\CashboxCount", inversedBy="checkRestaurantContainer")
     */
    private $cashbox;

    /**
     * @var ArrayCollection
     *
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\CashboxTicketRestaurant", mappedBy="checkRestaurantContainer", cascade={"persist"})
     */
    private $ticketRestaurantCounts;

    /**
     * @var ArrayCollection
     *
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\TicketPayment", mappedBy="checkRestaurantContainer")
     */
    private $ticketPayments;

    /**
     * @var float
     */
    public $theoricalTotalElectronic;

    /**
     * @var float
     */
    public $theoricalTotal;

    /**
     * CashboxCheckRestaurantContainer constructor.
     */
    public function __construct()
    {
        $this->ticketPayments = new ArrayCollection();
        $this->ticketRestaurantCounts = new ArrayCollection();
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
            $ticketPayment->setCheckRestaurantContainer($this);
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
        $ticketPayment->setCheckRestaurantContainer($this);
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
    public function getTicketRestaurantCounts()
    {
        return $this->ticketRestaurantCounts;
    }

    /**
     * @param ArrayCollection $ticketRestaurantCounts
     *
     * @return CashboxCheckRestaurantContainer
     */
    public function setTicketRestaurantCounts($ticketRestaurantCounts)
    {
        $this->ticketRestaurantCounts = $ticketRestaurantCounts;

        return $this;
    }

    /**
     * @param CashboxTicketRestaurant $ticketRestaurant
     *
     * @return self
     */
    public function addTicketRestaurantCount(CashboxTicketRestaurant $ticketRestaurant)
    {
        $ticketRestaurant->setCheckRestaurantContainer($this);
        $this->ticketRestaurantCounts->add($ticketRestaurant);

        return $this;
    }

    /**
     * @param CashboxTicketRestaurant $ticketRestaurant
     */
    public function removeTicketRestaurantCount(CashboxTicketRestaurant $ticketRestaurant)
    {
        $this->ticketRestaurantCounts->removeElement($ticketRestaurant);
    }

    /**
     * @param null $electronic
     *
     * @return array
     */
    public function getCheckRestaurantNames($electronic = null)
    {
        $result = [];
        foreach ($this->getTicketRestaurantCounts() as $ticketRestaurantCount) {
            /**
             * @var CashboxTicketRestaurant $ticketRestaurantCount
             */
            if (!in_array($ticketRestaurantCount->getTicketName(), $result)) {
                if (is_null($electronic)) {
                    $result[] = $ticketRestaurantCount->getTicketName();
                } elseif ($electronic && $ticketRestaurantCount->isElectronic()) {
                    $result[] = $ticketRestaurantCount->getTicketName();
                } elseif (!$electronic && !$ticketRestaurantCount->isElectronic()) {
                    $result[] = $ticketRestaurantCount->getTicketName();
                }
            }
        }

        return $result;
    }

    /**
     * @param null $electronic
     *
     * @return array
     */
    public function getCheckRestaurantNamesAndIdPayment($electronic = null)
    {
        $result = [];
        foreach ($this->getTicketRestaurantCounts() as $ticketRestaurantCount) {
            /**
             * @var CashboxTicketRestaurant $ticketRestaurantCount
             */
            if (!in_array($ticketRestaurantCount->getTicketName(), $result)) {
                if (is_null($electronic)) {
                    $result[$ticketRestaurantCount->getIdPayment()] = [
                        'name' => $ticketRestaurantCount->getTicketName(),
                        'id' => $ticketRestaurantCount->getIdPayment(),
                    ];
                } elseif ($electronic && $ticketRestaurantCount->isElectronic()) {
                    $result[$ticketRestaurantCount->getIdPayment()] = [
                        'name' => $ticketRestaurantCount->getTicketName(),
                        'id' => $ticketRestaurantCount->getIdPayment(),
                    ];
                } elseif (!$electronic && !$ticketRestaurantCount->isElectronic()) {
                    $result[$ticketRestaurantCount->getIdPayment()] = [
                        'name' => $ticketRestaurantCount->getTicketName(),
                        'id' => $ticketRestaurantCount->getIdPayment(),
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * @param null $electronic
     *
     * @return array
     */
    public function getCheckRestaurantFiltered($electronic = null)
    {
        $result = [];
        foreach ($this->getTicketRestaurantCounts() as $ticketRestaurantCount) {
            /**
             * @var CashboxTicketRestaurant $ticketRestaurantCount
             */
            if (!in_array($ticketRestaurantCount->getTicketName(), $result)) {
                if (is_null($electronic)) {
                    $result[] = $ticketRestaurantCount;
                } elseif ($electronic && $ticketRestaurantCount->isElectronic()) {
                    $result[] = $ticketRestaurantCount;
                } elseif (!$electronic && !$ticketRestaurantCount->isElectronic()) {
                    $result[] = $ticketRestaurantCount;
                }
            }
        }

        return $result;
    }

    /**
     * @param $ticketName
     *
     * @return float|int
     */
    public function calculateTotalByTicketName($ticketName)
    {
        $result = 0;
        foreach ($this->getTicketRestaurantCounts() as $ticketRestaurantCount) {
            if ($ticketRestaurantCount->getTicketName() === $ticketName) {
                $result += $ticketRestaurantCount->getQty() * $ticketRestaurantCount->getUnitValue();
            }
        }

        return $result;
    }

    /**
     * @param $paymentId
     *
     * @return float|int
     */
    public function calculatePaymentTicketsTotalByPaymentId($paymentId)
    {
        $result = 0;
        foreach ($this->getTicketPayments() as $ticketPayment) {
            /**
             * @var TicketPayment $ticketPayment
             */
            if ($ticketPayment->getIdPayment() === $paymentId) {
                $result += $ticketPayment->getAmount();
            }
        }

        return $result;
    }

    /**
     * @param null $electronic
     * @param null $paymentId
     *
     * @return float|int
     */
    public function calculateRealTotalAmount($electronic = null, $paymentId = null)
    {
        $total = 0;
        if (!is_null($electronic) && true === $electronic && $this->getCashbox()->isEft()) {
            return $this->calculateTheoricalTotal($electronic, $paymentId);
        }
        foreach ($this->getTicketRestaurantCounts() as $ticketRestaurantCount) {
            /**
             * @var CashboxTicketRestaurant $ticketRestaurantCount
             */
            if (is_null($electronic) && is_null($paymentId)) {
                $total += $ticketRestaurantCount->calculateTotal();
            } elseif (is_null($paymentId) && !$electronic && !$ticketRestaurantCount->isElectronic()) {
                $total += $ticketRestaurantCount->calculateTotal();
            } elseif (is_null($paymentId) && $electronic && $ticketRestaurantCount->isElectronic()) {
                $total += $ticketRestaurantCount->calculateTotal();
            } elseif (!is_null($paymentId) && intval($paymentId) === intval(
                    $ticketRestaurantCount->getIdPayment()
                )
            ) {
                $total += $ticketRestaurantCount->calculateTotal();
            }
        }


        return $total;
    }

    /**
     * @param null $electronic
     * @param null $paymentId
     *
     * @return float|int
     */
    public function calculateRealTotalAmountId($electronic = null, $paymentId = null)
    {
        $total = 0;
        if (!is_null($electronic) && true === $electronic && $this->getCashbox()->isEft()) {
            return $this->calculateTheoricalTotal($electronic, $paymentId);
        }

        foreach ($this->getTicketRestaurantCounts() as $ticketRestaurantCount) {
            /**
             * @var CashboxTicketRestaurant $ticketRestaurantCount
             */
            if ($paymentId == $ticketRestaurantCount->getIdPayment()) {
                $total += $ticketRestaurantCount->calculateTotal();
            }
        }


        return $total;
    }

    /**
     * @param null $electronic
     * @param null $paymentId
     *
     * @return int
     */
    public function calculateTheoricalTotal($electronic = null, $paymentId = null)
    {
        //Si l'appel de la fonction a été effectué à partir de l'écran comptage caisse
        if (is_bool($electronic) && is_null($paymentId)) {
            if ($electronic) {
                if (!empty($this->theoricalTotalElectronic)) {
                    return $this->theoricalTotalElectronic;
                }
            } else {
                if (!empty($this->theoricalTotal)) {
                    return $this->theoricalTotal;
                }
            }
        }
        $total = 0;
        foreach ($this->getTicketPayments() as $ticketPayment) {
            /**
             * @var TicketPayment $ticketPayment
             */
            if (!is_null($electronic) && $electronic && !is_null($paymentId)) {
                if ($ticketPayment->getIdPayment() == $paymentId) {
                    $this->updateTotal($ticketPayment, $total);
                }
            } elseif (is_null($electronic)) {
                $this->updateTotal($ticketPayment, $total);
            } elseif ($electronic && $ticketPayment->isElectronic()) {
                $this->updateTotal($ticketPayment, $total);
            } elseif (!$electronic && !$ticketPayment->isElectronic() && !is_null($paymentId)) {
                if ($ticketPayment->getIdPayment() == $paymentId) {
                    $this->updateTotal($ticketPayment, $total);
                }
            } elseif (!$electronic && !$ticketPayment->isElectronic()) {
                $this->updateTotal($ticketPayment, $total);
            }
        }

        return $total;
    }

    /**
     * @param null $electronic
     *
     * @return float|int
     */
    public function calculateTotalGap($electronic = null)
    {
        return $this->calculateRealTotalAmount($electronic) - $this->calculateTheoricalTotal($electronic);
    }

    /**
     * @param null $electronic
     *
     * @return array
     */
    public function getPaymentId($electronic = null)
    {
        $paymentId = [];
        foreach ($this->getTicketRestaurantCounts() as $ticketRestaurantCount) {
            /**
             * @var CashboxTicketRestaurant $ticketRestaurantCount
             */
            if (is_null($electronic)) {
                $paymentId[] = $ticketRestaurantCount->getIdPayment();
            } elseif (!$electronic && !$ticketRestaurantCount->isElectronic()) {
                $paymentId[] = $ticketRestaurantCount->getIdPayment();
            }
        }

        return array_unique($paymentId);
    }

    /**
     * @param TicketPayment $ticketPayment
     * @param $total
     */
    private function updateTotal($ticketPayment, &$total)
    {
        $status = $ticketPayment->getTicket()->getStatus();
        if (Ticket::ABONDON_STATUS_VALUE !== $status && Ticket::CANCEL_STATUS_VALUE !== $status) {
            $total += $ticketPayment->getAmount();
        } elseif (Ticket::CANCEL_STATUS_VALUE === $status && $ticketPayment->getTicket()->isCountedCanceled()) {
            $total -= abs($ticketPayment->getAmount());
        }
    }

    /**
     * @return float
     */
    public function getTheoricalTotalElectronic()
    {
        return $this->theoricalTotalElectronic;
    }

    /**
     * @param float $theoricalTotalElectronic
     */
    public function setTheoricalTotalElectronic($theoricalTotalElectronic)
    {
        $this->theoricalTotalElectronic = $theoricalTotalElectronic;
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
