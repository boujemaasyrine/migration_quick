<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 29/03/2016
 * Time: 10:53
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Financial\Controller\CashBoxController;
use AppBundle\Financial\Interfaces\CompartmentCalculationInterface;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;

/**
 * ChestCount
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\ChestCountRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ChestCount implements CompartmentCalculationInterface
{
    use IdTrait;
    use TimestampableTrait;
    use SynchronizedFlagTrait;
    use OriginRestaurantTrait; // this Trait add $originRestaurant attribut ManyToOne
    use ImportIdTrait;

    /**
     * ChestCount constructor.
     */
    public function __construct()
    {
        $this->tirelire = new ChestTirelire();
        $this->tirelire->setChestCount($this);

        $this->smallChest = new ChestSmallChest();
        $this->smallChest->setChestCount($this);

        $this->exchangeFund = new ChestExchangeFund();
        $this->exchangeFund->setChestCount($this);

        $this->cashboxFund = new ChestCashboxFund();
        $this->cashboxFund->setChestCount($this);

        $this->deposits = new ArrayCollection();
        $this->envelopes = new ArrayCollection();
        $this->recipeTickets = new ArrayCollection();
        $this->expenses = new ArrayCollection();
    }

    /**
     * @var ChestCount
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\ChestCount")
     */
    private $lastChestCount;

    /**
     * @var \DateTime
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var \DateTime
     * @ORM\Column(name="closure_date", type="datetime", nullable=TRUE)
     */
    private $closureDate;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $owner;

    /**
     * @var ChestTirelire
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\ChestTirelire", mappedBy="chestCount", cascade={"persist"})
     */
    private $tirelire;

    /**
     * @var ChestSmallChest
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\ChestSmallChest", mappedBy="chestCount", cascade={"persist"})
     */
    private $smallChest;

    /**
     * @var ChestExchangeFund
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\ChestExchangeFund", mappedBy="chestCount", cascade={"persist"})
     */
    private $exchangeFund;

    /**
     * @var ChestCashboxFund
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\ChestCashboxFund", mappedBy="chestCount", cascade={"persist"})
     */
    private $cashboxFund;

    /**
     * Type = Cash; source in (comptage caisse, withdrawal, exchange fund, small chest)
     * Type titres rest; source = small chest
     *
     * @var                                                           ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\Envelope", mappedBy="chestCount")
     */
    private $envelopes;

    /**
     * Cash deposit|Titres rest deposit
     *
     * @var                                                          ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\Deposit", mappedBy="chestCount")
     */
    private $deposits;

    /**
     * Cash deposit|Titres rest deposit
     *
     * @var                                                               ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\RecipeTicket", mappedBy="chestCount")
     */
    private $recipeTickets;

    /**
     * Cash deposit|Titres rest deposit
     *
     * @var                                                          ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\Expense", mappedBy="chestCount")
     */
    private $expenses;

    /**
     * @var boolean
     * @ORM\Column(name="closure", type="boolean", nullable= TRUE)
     */
    private $closure;

    /**
     * @var boolean
     * @ORM\Column(name="eft", type="boolean", nullable= TRUE)
     */
    private $eft;

    /**
     * @var float
     * @ORM\Column(name="real_total", type="float", nullable= TRUE)
     */
    private $realTotal;

    /**
     * @var float
     * @ORM\Column(name="theorical_total", type="float", nullable= TRUE)
     */
    private $theoricalTotal;

    /**
     * @var float
     * @ORM\Column(name="gap", type="float", nullable= TRUE)
     */
    private $gap;

    /**
     * @return float
     */
    public function getRealTotal()
    {
        return $this->realTotal;
    }

    /**
     * @param float $realTotal
     * @return ChestCount
     */
    public function setRealTotal($realTotal)
    {
        $this->realTotal = $realTotal;

        return $this;
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
     * @return ChestCount
     */
    public function setTheoricalTotal($theoricalTotal)
    {
        $this->theoricalTotal = $theoricalTotal;

        return $this;
    }

    /**
     * @return float
     */
    public function getGap()
    {
        return $this->gap;
    }

    /**
     * @param float $gap
     * @return ChestCount
     */
    public function setGap($gap)
    {
        $this->gap = $gap;

        return $this;
    }

    /**
     * @param null $format
     * @return \DateTime|string
     */
    public function getDate($format = null)
    {
        if ($format) {
            return $this->date->format($format);
        }

        return $this->date;
    }

    /**
     * @param \DateTime $date
     * @return self
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @param null $format
     * @return \DateTime|string
     */
    public function getClosureDate($format = null)
    {
        if ($format && $this->closureDate) {
            return $this->closureDate->format($format);
        }

        return $this->closureDate;
    }

    /**
     * @param \DateTime $closureDate
     * @return ChestCount
     */
    public function setClosureDate($closureDate)
    {
        $this->closureDate = $closureDate;

        return $this;
    }


    /**
     * @return Employee
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Employee $owner
     * @return self
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    //
    // Calculation methods
    //

    /**
     * Set tirelire
     *
     * @param \AppBundle\Financial\Entity\ChestTirelire $tirelire
     *
     * @return ChestCount
     */
    public function setTirelire(\AppBundle\Financial\Entity\ChestTirelire $tirelire = null)
    {
        $this->tirelire = $tirelire;

        return $this;
    }

    /**
     * Get tirelire
     *
     * @return \AppBundle\Financial\Entity\ChestTirelire
     */
    public function getTirelire()
    {
        return $this->tirelire;
    }

    /**
     * Set smallChest
     *
     * @param \AppBundle\Financial\Entity\ChestSmallChest $smallChest
     *
     * @return ChestCount
     */
    public function setSmallChest(\AppBundle\Financial\Entity\ChestSmallChest $smallChest = null)
    {
        $this->smallChest = $smallChest;

        return $this;
    }

    /**
     * Get smallChest
     *
     * @return ChestSmallChest
     */
    public function getSmallChest()
    {
        return $this->smallChest;
    }

    /**
     * Set exchangeFund
     *
     * @param \AppBundle\Financial\Entity\ChestExchangeFund $exchangeFund
     *
     * @return ChestCount
     */
    public function setExchangeFund(\AppBundle\Financial\Entity\ChestExchangeFund $exchangeFund = null)
    {
        $this->exchangeFund = $exchangeFund;

        return $this;
    }

    /**
     * Get exchangeFund
     *
     * @return ChestExchangeFund
     */
    public function getExchangeFund()
    {
        return $this->exchangeFund;
    }

    /**
     * Set cashboxFund
     *
     * @param \AppBundle\Financial\Entity\ChestCashboxFund $cashboxFund
     *
     * @return ChestCount
     */
    public function setCashboxFund(\AppBundle\Financial\Entity\ChestCashboxFund $cashboxFund = null)
    {
        $this->cashboxFund = $cashboxFund;

        return $this;
    }

    /**
     * Get cashboxFund
     *
     * @return ChestCashboxFund
     */
    public function getCashboxFund()
    {
        return $this->cashboxFund;
    }

    /**
     * Add envelope
     *
     * @param \AppBundle\Financial\Entity\Envelope $envelope
     *
     * @return ChestCount
     */
    public function addEnvelope(\AppBundle\Financial\Entity\Envelope $envelope)
    {
        $envelope->setChestCount($this);
        $this->envelopes[] = $envelope;

        return $this;
    }

    public function setEnvelopes($envelopes)
    {
        foreach ($envelopes as $envelope) {
            $this->addEnvelope($envelope);
        }

        return $this;
    }

    /**
     * Remove envelope
     *
     * @param \AppBundle\Financial\Entity\Envelope $envelope
     */
    public function removeEnvelope(\AppBundle\Financial\Entity\Envelope $envelope)
    {
        $this->envelopes->removeElement($envelope);
    }

    /**
     * Get envelopes
     *
     * @param  null $filter
     * @return ArrayCollection
     */
    public function getEnvelopes($filter = null, $status = null)
    {
        if (!is_null($filter)) {
            $envelopes = [];
            $type = isset($filter['type']) ? $filter['type'] : false;
            $source = isset($filter['source']) ? $filter['source'] : [];
            $idPayment = isset($filter['idPayment']) ? $filter['idPayment'] : null;
            $restaurant = isset($filter['restaurant']) ? $filter['restaurant'] : null;
            foreach ($this->envelopes as $envelope) {
                /**
                 * @var Envelope $envelope
                 */
                if ($idPayment) {
                    if ($envelope->getSousType() == $idPayment && $envelope->getOriginRestaurant() == $restaurant) {
                        $envelopes[] = $envelope;
                    }
                } elseif ($type && !count($source)) {
                    if ($envelope->getType() === $type && (is_null($status) || (!is_null(
                                    $status
                                ) && $envelope->getStatus() === $status) && $envelope->getOriginRestaurant(
                            ) == $restaurant)
                    ) {
                        $envelopes[] = $envelope;
                    }
                } elseif (!$type && count($source) && (is_null($status) || (!is_null($status) && $envelope->getStatus(
                            ) === $status)) && $envelope->getOriginRestaurant() == $restaurant
                ) {
                    if (in_array($envelope->getSource(), $source)) {
                        $envelopes[] = $envelope;
                    }
                } else {
                    if ($envelope->getType() === $type && in_array($envelope->getSource(), $source) && (is_null(
                                $status
                            ) || (!is_null($status) && $envelope->getStatus(
                                ) === $status)) && $envelope->getOriginRestaurant() == $restaurant
                    ) {
                        $envelopes[] = $envelope;
                    }
                }
            }

            return $envelopes;
        } else {
            return $this->envelopes;
        }
    }

    /**
     * Add deposit
     *
     * @param \AppBundle\Financial\Entity\Deposit $deposit
     *
     * @return ChestCount
     */
    public function addDeposit(\AppBundle\Financial\Entity\Deposit $deposit)
    {
        $deposit->setChestCount($this);
        $this->deposits[] = $deposit;

        return $this;
    }

    public function setDeposits($deposits)
    {
        foreach ($deposits as $deposit) {
            $this->addDeposit($deposit);
        }

        return $this;
    }

    /**
     * Remove deposit
     *
     * @param \AppBundle\Financial\Entity\Deposit $deposit
     */
    public function removeDeposit(\AppBundle\Financial\Entity\Deposit $deposit)
    {
        $this->deposits->removeElement($deposit);
    }

    /**
     * Get deposits
     *
     * @param  null $filter
     * @return ArrayCollection
     */
    public function getDeposits($filter = null)
    {
        if (!is_null($filter)) {
            $deposits = [];
            foreach ($this->deposits as $deposit) {
                /**
                 * @var Deposit $deposit
                 */
                $type = isset($filter['type']) ? $filter['type'] : false;
                $restaurant = isset($filter['restaurant']) ? $filter['restaurant'] : null;
                if ($type && $restaurant) {
                    if ($deposit->getType() === $type && $deposit->getOriginRestaurant() == $restaurant) {
                        $deposits[] = $deposit;
                    }
                }
            }

            return $deposits;
        } else {
            return $this->deposits;
        }
    }

    /**
     * Add recipeTicket
     *
     * @param \AppBundle\Financial\Entity\RecipeTicket $recipeTicket
     *
     * @return ChestCount
     */
    public function addRecipeTicket(\AppBundle\Financial\Entity\RecipeTicket $recipeTicket)
    {
        $recipeTicket->setChestCount($this);
        $this->recipeTickets[] = $recipeTicket;

        return $this;
    }

    public function setRecipeTickets($recipeTickets)
    {
        foreach ($recipeTickets as $recipeTicket) {
            $this->addRecipeTicket($recipeTicket);
        }

        return $this;
    }

    /**
     * Remove recipeTicket
     *
     * @param \AppBundle\Financial\Entity\RecipeTicket $recipeTicket
     */
    public function removeRecipeTicket(\AppBundle\Financial\Entity\RecipeTicket $recipeTicket)
    {
        $this->recipeTickets->removeElement($recipeTicket);
    }

    /**
     * Get recipeTickets
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRecipeTickets($filter = null, $inversed = false)
    {
        if (!is_null($filter)) {
            $recipeTickets = [];
            foreach ($this->recipeTickets as $recipeTicket) {
                /**
                 * @var RecipeTicket $recipeTicket
                 */
                $type = isset($filter['label']) ? $filter['label'] : [];
                $restaurant = isset($filter['restaurant']) ? $filter['restaurant'] : null;
                if (count($type)) {
                    if ($recipeTicket->getOriginRestaurant() == $restaurant) {
                        if ((is_null($inversed) || !$inversed) && in_array($recipeTicket->getLabel(), $type)) {
                            $recipeTickets[] = $recipeTicket;
                        } else {
                            if ($inversed && !in_array($recipeTicket->getLabel(), $type)) {
                                $recipeTickets[] = $recipeTicket;
                            }
                        }
                    }
                }
            }

            return $recipeTickets;
        } else {
            return $this->recipeTickets;
        }
    }

    /**
     * Add expense
     *
     * @param \AppBundle\Financial\Entity\Expense $expense
     *
     * @return ChestCount
     */
    public function addExpense(\AppBundle\Financial\Entity\Expense $expense)
    {
        $expense->setChestCount($this);
        $this->expenses[] = $expense;

        return $this;
    }

    public function setExpenses($expenses)
    {
        foreach ($expenses as $expense) {
            $this->addExpense($expense);
        }

        return $this;
    }

    /**
     * Remove expense
     *
     * @param \AppBundle\Financial\Entity\Expense $expense
     */
    public function removeExpense(\AppBundle\Financial\Entity\Expense $expense)
    {
        $this->expenses->removeElement($expense);
    }

    /**
     * Get expenses
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getExpenses()
    {
        return $this->expenses;
    }

    public function getExpensesByLabels($restaurant, $labels)
    {
        $result = [];

        foreach ($this->getExpenses() as $item) {
            /**
             * @var Expense $item
             */
            if (in_array($item->getSousGroup(), $labels) && $item->getOriginRestaurant() == $restaurant) {
                $result[] = $item;
            }
        }

        return $result;
    }

    public function calculateExpenses($expenses)
    {
        $totalExpenses = 0.0;
        foreach ($expenses as $expense) {
            /**
             * @var Expense $expense
             */
            $totalExpenses += $expense->getAmount();
        }

        return $totalExpenses;
    }

    public function calculateTotalExpenses($group = null, $labelNotIn = null)
    {
        $expenses = $this->getExpenses();
        $totalExpenses = 0.0;
        foreach ($expenses as $expense) {
            /**
             * @var Expense $expense
             */
            if (is_null($group) || ($expense->getGroupExpense() === $group)) {
                if (is_null($labelNotIn) || count($labelNotIn) === 0) {
                    $totalExpenses += $expense->getAmount();
                } elseif (!in_array($expense->getSousGroup(), $labelNotIn)) {
                    $totalExpenses += $expense->getAmount();
                }
            }
        }

        return $totalExpenses;
    }

    /**
     * @return boolean
     */
    public function isClosure()
    {
        return $this->closure;
    }

    /**
     * @param boolean $closure
     * @return ChestCount
     */
    public function setClosure($closure)
    {
        $this->closure = $closure;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isEft()
    {
        return $this->eft;
    }

    /**
     * @param boolean $eft
     * @return ChestCount
     */
    public function setEft($eft)
    {
        $this->eft = $eft;

        return $this;
    }

    /**
     * Get closure
     *
     * @return boolean
     */
    public function getClosure()
    {
        return $this->closure;
    }

    /**
     * Get eft
     *
     * @return boolean
     */
    public function getEft()
    {
        return $this->eft;
    }

    /**
     * Set lastChestCount
     *
     * @param \AppBundle\Financial\Entity\ChestCount $lastChestCount
     *
     * @return ChestCount
     */
    public function setLastChestCount(\AppBundle\Financial\Entity\ChestCount $lastChestCount = null)
    {
        $this->lastChestCount = $lastChestCount;

        return $this;
    }

    /**
     * Get lastChestCount
     *
     * @return \AppBundle\Financial\Entity\ChestCount
     */
    public function getLastChestCount()
    {
        return $this->lastChestCount;
    }

    /**
     * Calculate real amount in chest
     *
     * @return float.
     */
    public function calculateRealTotal($restaurant = null)
    {
        $total = 0.0;
        $total += $this->getTirelire()->calculateRealTotal($restaurant);
        $total += $this->getSmallChest()->calculateRealTotal($restaurant);
        $total += $this->getExchangeFund()->calculateRealTotal();
        $total += $this->getCashboxFund()->calculateRealTotal();

        return $total;
    }

    /**
     * @return float
     */
    public function calculateTheoricalTotal(Restaurant $restaurant = null)
    {
        $total = 0.0;
        $total += $this->getTirelire()->calculateTheoricalTotal($restaurant);
        $total += $this->getSmallChest()->calculateTheoricalTotal($restaurant);
        $total += $this->getExchangeFund()->calculateTheoricalTotal($restaurant);
        $total += $this->getCashboxFund()->calculateTheoricalTotal();

        return $total;
    }

    /**
     * @return float
     */
    public function calculateGap($restaurant = null)
    {

        if (isset($restaurant)) {
            $this->smallChest->calculateGap($restaurant);
            $this->tirelire->calculateGap($restaurant);
        }
        $this->exchangeFund->calculateGap($restaurant);
        $this->gap = $this->calculateRealTotal($restaurant) - $this->calculateTheoricalTotal($restaurant);

        return $this->gap;
    }

    /**
     * @PreUpdate
     * @PrePersist
     */
    public function updateRealTheoricalTotal()
    {
        $this->setRealTotal($this->calculateRealTotal($this->getOriginRestaurant()));
        $this->setTheoricalTotal($this->calculateTheoricalTotal($this->getOriginRestaurant()));
        $this->setGap($this->calculateGap($this->getOriginRestaurant()));
    }
}
