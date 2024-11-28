<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 05/04/2016
 * Time: 16:29
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Financial\Interfaces\CompartmentCalculationInterface;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Traits\GlobalIdTrait;
use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * ChestSmallChest
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class ChestSmallChest implements CompartmentCalculationInterface
{

    public function __construct()
    {
        $this->cashboxCounts = new ArrayCollection();
        $this->foreignCurrencyCounts = new ArrayCollection();
        $this->ticketRestaurantCounts = new ArrayCollection();
    }

    use IdTrait;
    use GlobalIdTrait;
    use ImportIdTrait;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\CashboxCount", mappedBy="smallChest")
     */
    private $cashboxCounts;

    /**
     * @var ChestCount
     * @OneToOne(targetEntity="AppBundle\Financial\Entity\ChestCount", inversedBy="smallChest")
     */
    private $chestCount;

    /**
     * @var float
     * @ORM\Column(name="total_cash", type="float", nullable=TRUE)
     */
    private $totalCash;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\CashboxForeignCurrency", mappedBy="smallChest", cascade={"persist"})
     */
    private $foreignCurrencyCounts;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\CashboxCheckQuick", mappedBy="smallChest", cascade={"persist"})
     */
    private $checkQuickCounts;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\CashboxTicketRestaurant", mappedBy="smallChest", cascade={"persist"})
     */
    private $ticketRestaurantCounts;

    /**
     * @var ArrayCollection
     * @OneToMany(targetEntity="AppBundle\Financial\Entity\CashboxBankCard", mappedBy="smallChest", cascade={"persist"})
     */
    private $bankCardCounts;

    /**
     * @var bool
     * @ORM\Column(name="electronic_deposed", type="boolean", nullable=TRUE)
     */
    private $electronicDeposed = false;

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
     * @var float
     * @ORM\Column(name="real_cash_total", type="float", nullable= TRUE)
     */
    private $realCashTotal;
    /**
     * @var float
     * @ORM\Column(name="real_tr_total", type="float", nullable= TRUE)
     */
    private $realTrTotal;
    /**
     * @var array
     * @ORM\Column(name="real_tr_total_detail", type="array", nullable= TRUE)
     */
    private $realTrTotalDetail;
    /**
     * @var array
     * @ORM\Column(name="theorical_tr_total_detail", type="array", nullable= TRUE)
     */
    private $theoricalTrTotalDetail;
    /**
     * @var float
     * @ORM\Column(name="real_tre_total", type="float", nullable= TRUE)
     */
    private $realTreTotal;
    /**
     * @var float
     * @ORM\Column(name="real_cbtotal", type="float", nullable= TRUE)
     */
    private $realCBTotal;
    /**
     * @var float
     * @ORM\Column(name="real_check_quick_total", type="float", nullable= TRUE)
     */
    private $realCheckQuickTotal;
    /**
     * @var float
     * @ORM\Column(name="real_foreign_currency_total", type="float", nullable= TRUE)
     */
    private $realForeignCurrencyTotal;

    /**
     * @var float
     * @ORM\Column(name="theorical_cash_total", type="float", nullable= TRUE)
     */
    private $theoricalCashTotal;
    /**
     * @var float
     * @ORM\Column(name="theorical_tr_total", type="float", nullable= TRUE)
     */
    private $theoricalTrTotal;
    /**
     * @var float
     * @ORM\Column(name="theorical_tre_total", type="float", nullable= TRUE)
     */
    private $theoricalTreTotal;
    /**
     * @var float
     * @ORM\Column(name="theorical_cbtotal", type="float", nullable= TRUE)
     */
    private $theoricalCBTotal;
    /**
     * @var float
     * @ORM\Column(name="theorical_check_quick_total", type="float", nullable= TRUE)
     */
    private $theoricalCheckQuickTotal;
    /**
     * @var float
     * @ORM\Column(name="theorical_foreign_currency_total", type="float", nullable= TRUE)
     */
    private $theoricalForeignCurrencyTotal;

    /**
     * @return boolean
     */
    public function isElectronicDeposed()
    {
        return $this->electronicDeposed;
    }

    /**
     * @param $electronicDeposed
     *
     * @return $this
     */
    public function setElectronicDeposed($electronicDeposed)
    {
        $this->electronicDeposed = $electronicDeposed;

        return $this;
    }

    /**
     * @return ChestCount
     */
    public function getChestCount()
    {
        return $this->chestCount;
    }

    /**
     * @param ChestCount $chestCount
     *
     * @return ChestSmallChest
     */
    public function setChestCount($chestCount)
    {
        $this->chestCount = $chestCount;

        return $this;
    }

    /**
     * Set totalCash
     *
     * @param float $totalCash
     *
     * @return ChestSmallChest
     */
    public function setTotalCash($totalCash)
    {
        if (is_string($totalCash)) {
            $totalCash = str_replace(',', '.', $totalCash);
        }
        $this->totalCash = $totalCash;

        return $this;
    }

    /**
     * Get totalCash
     *
     * @return float
     */
    public function getTotalCash()
    {
        return $this->totalCash;
    }

    /**
     * Add foreignCurrencyCount
     *
     * @param \AppBundle\Financial\Entity\CashboxForeignCurrency $foreignCurrencyCount
     *
     * @return ChestSmallChest
     */
    public function addForeignCurrencyCount(
        \AppBundle\Financial\Entity\CashboxForeignCurrency $foreignCurrencyCount
    )
    {
        $foreignCurrencyCount->setSmallChest($this);
        $this->foreignCurrencyCounts[] = $foreignCurrencyCount;

        return $this;
    }

    /**
     * Remove foreignCurrencyCount
     *
     * @param \AppBundle\Financial\Entity\CashboxForeignCurrency $foreignCurrencyCount
     */
    public function removeForeignCurrencyCount(
        \AppBundle\Financial\Entity\CashboxForeignCurrency $foreignCurrencyCount
    )
    {
        $this->foreignCurrencyCounts->removeElement($foreignCurrencyCount);
    }

    /**
     * Get foreignCurrencyCounts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getForeignCurrencyCounts()
    {
        return $this->foreignCurrencyCounts;
    }

    /**
     * Add checkQuickCount
     *
     * @param \AppBundle\Financial\Entity\CashboxCheckQuick $checkQuickCount
     *
     * @return ChestSmallChest
     */
    public function addCheckQuickCount(
        \AppBundle\Financial\Entity\CashboxCheckQuick $checkQuickCount
    )
    {
        $checkQuickCount->setSmallChest($this);
        $this->checkQuickCounts[] = $checkQuickCount;

        return $this;
    }

    /**
     * Remove checkQuickCount
     *
     * @param \AppBundle\Financial\Entity\CashboxCheckQuick $checkQuickCount
     */
    public function removeCheckQuickCount(
        \AppBundle\Financial\Entity\CashboxCheckQuick $checkQuickCount
    )
    {
        $this->checkQuickCounts->removeElement($checkQuickCount);
    }

    /**
     * Get checkQuickCounts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCheckQuickCounts()
    {
        return $this->checkQuickCounts;
    }

    /**
     * Add ticketRestaurantCount
     *
     * @param \AppBundle\Financial\Entity\CashboxTicketRestaurant $ticketRestaurantCount
     *
     * @return ChestSmallChest
     */
    public function addTicketRestaurantCount(
        \AppBundle\Financial\Entity\CashboxTicketRestaurant $ticketRestaurantCount
    )
    {
        $ticketRestaurantCount->setSmallChest($this);
        $this->ticketRestaurantCounts[] = $ticketRestaurantCount;

        return $this;
    }

    /**
     * Remove ticketRestaurantCount
     *
     * @param \AppBundle\Financial\Entity\CashboxTicketRestaurant $ticketRestaurantCount
     */
    public function removeTicketRestaurantCount(
        \AppBundle\Financial\Entity\CashboxTicketRestaurant $ticketRestaurantCount
    )
    {
        $ticketRestaurantCount->setSmallChest(null);
        $this->ticketRestaurantCounts->removeElement($ticketRestaurantCount);
    }

    /**
     * Get ticketRestaurantCounts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTicketRestaurantCounts()
    {
        return $this->ticketRestaurantCounts;
    }

    /**
     * Get ticketRestaurantId
     *
     * @return array
     */
    public function getTicketRestaurantId()
    {
        $ids = array();
        /**
         * @var CashboxTicketRestaurant $item
         */
        foreach ($this->ticketRestaurantCounts as $item) {
            $ids[] = $item->getIdPayment();
        }

        return array_unique($ids);
    }

    /**
     * Add cashboxCount
     *
     * @param \AppBundle\Financial\Entity\CashboxCount $cashboxCount
     *
     * @return ChestCount
     */
    public function addCashboxCount(
        \AppBundle\Financial\Entity\CashboxCount $cashboxCount
    )
    {
        $cashboxCount->setSmallChest($this);
        $this->cashboxCounts[] = $cashboxCount;

        return $this;
    }

    /**
     * @param $cashboxes
     *
     * @return $this
     */
    public function setCashboxCounts($cashboxes)
    {
        foreach ($cashboxes as $cashbox) {
            $this->addCashboxCount($cashbox);
        }

        return $this;
    }

    /**
     * Remove cashboxCount
     *
     * @param \AppBundle\Financial\Entity\CashboxCount $cashboxCount
     */
    public function removeCashboxCount(
        \AppBundle\Financial\Entity\CashboxCount $cashboxCount
    )
    {
        $this->cashboxCounts->removeElement($cashboxCount);
    }

    /**
     * Get cashboxCounts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCashboxCounts()
    {
        return $this->cashboxCounts;
    }

    /**
     * Add bankCardCount
     *
     * @param \AppBundle\Financial\Entity\CashboxBankCard $bankCardCount
     *
     * @return ChestSmallChest
     */
    public function addBankCardCount(
        \AppBundle\Financial\Entity\CashboxBankCard $bankCardCount
    )
    {
        $bankCardCount->setSmallChest($this);
        $this->bankCardCounts[] = $bankCardCount;

        return $this;
    }

    /**
     * Remove bankCardCount
     *
     * @param \AppBundle\Financial\Entity\CashboxBankCard $bankCardCount
     */
    public function removeBankCardCount(
        \AppBundle\Financial\Entity\CashboxBankCard $bankCardCount
    )
    {
        $this->bankCardCounts->removeElement($bankCardCount);
    }

    /**
     * Get bankCardCounts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBankCardCounts()
    {
        return $this->bankCardCounts;
    }

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
                } elseif ($electronic
                    && $ticketRestaurantCount->isElectronic()
                ) {
                    $result[] = $ticketRestaurantCount->getTicketName();
                } elseif (!$electronic
                    && !$ticketRestaurantCount->isElectronic()
                ) {
                    $result[] = $ticketRestaurantCount->getTicketName();
                }
            }
        }

        return $result;
    }

    public function calculateTotalByTicketName($ticketName)
    {
        $result = 0;
        foreach ($this->getTicketRestaurantCounts() as $ticketRestaurantCount) {
            if ($ticketRestaurantCount->getTicketName() === $ticketName) {
                $result += $ticketRestaurantCount->getQty()
                    * $ticketRestaurantCount->getUnitValue();
            }
        }

        return $result;
    }

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
                } elseif ($electronic
                    && $ticketRestaurantCount->isElectronic()
                ) {
                    $result[] = $ticketRestaurantCount;
                } elseif (!$electronic
                    && !$ticketRestaurantCount->isElectronic()
                ) {
                    $result[] = $ticketRestaurantCount;
                }
            }
        }

        return $result;
    }

    /**
     * @param null $idPayment
     *
     * @return array
     */
    public function retrievePaymentTickets($idPayment = null)
    {
        $paymentTickets = [];
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            /**
             * @var CashboxCount $cashboxCount
             */
            $cashPayments = $cashboxCount->getCashContainer()
                ->getTicketPayments();
            $ticketRestaurantPayments
                = $cashboxCount->getCheckRestaurantContainer()
                ->getTicketPayments();
            $checkQuickPayments = $cashboxCount->getCheckQuickContainer()
                ->getTicketPayments();
            $bankCardPayments = $cashboxCount->getBankCardContainer()
                ->getTicketPayments();
            $tmpPaymentTickets = [];
            $tmpPaymentTickets = array_merge(
                $tmpPaymentTickets,
                $cashPayments->toArray(),
                $ticketRestaurantPayments->toArray(),
                $checkQuickPayments->toArray(),
                $bankCardPayments->toArray()
            );
            foreach ($tmpPaymentTickets as $paymentTicket) {
                /**
                 * @var TicketPayment $paymentTicket
                 */
                if (intval($paymentTicket->getIdPayment()) === intval(
                        $idPayment
                    )
                    || is_null($idPayment)
                ) {
                    $paymentTickets[] = $paymentTicket;
                }
            }
        }

        return $paymentTickets;
    }

    public function calculatePaymentTicketsTotalByPaymentId($paymentId = null)
    {
        $result = 0;
        foreach ($this->retrievePaymentTickets($paymentId) as $ticketPayment) {
            /**
             * @var TicketPayment $ticketPayment
             */
            if (is_null($paymentId)
                || intval($ticketPayment->getIdPayment()) === intval($paymentId)
            ) {
                $result += $ticketPayment->getAmount();
            }
        }

        return $result;
    }

    public function calculateCheckQuickTotal()
    {
        $result = 0;
        if ($this->getCheckQuickCounts() != null) {
            foreach ($this->getCheckQuickCounts() as $checkQuickCount) {
                $result += $checkQuickCount->getQty()
                    * $checkQuickCount->getUnitValue();
            }
        }

        return $result;
    }

    public function calculateBankCardTotal($idPayment = null)
    {
        return $this->calculateTheoricalBankCardTotal($idPayment);
    }

    /**
     * Retrieve Bank Card Payment tickets
     *
     * @return array
     */
    public function retrieveBankCardPaymentTickets()
    {
        $paymentTickets = [];
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            /**
             * @var CashboxCount $cashboxCount
             */
            $bankCardPayments = $cashboxCount->getBankCardContainer()
                ->getTicketPayments();
            $tmpPaymentTickets = [];
            $tmpPaymentTickets = array_merge(
                $tmpPaymentTickets,
                $bankCardPayments->toArray()
            );
            foreach ($tmpPaymentTickets as $paymentTicket) {
                /**
                 * @var TicketPayment $paymentTicket
                 */
                $paymentTickets[] = $paymentTicket;
            }
        }

        return $paymentTickets;
    }

    public function calculateTheoricalBankCardTotal($idPayment = null)
    {
        $total = 0;
        foreach ($this->retrieveBankCardPaymentTickets() as $ticketPayment) {
            /**
             * @var TicketPayment $ticketPayment
             */
            if ($ticketPayment->getTicket()->getStatus()
                != Ticket::ABONDON_STATUS_VALUE
                && $ticketPayment->getTicket()->getStatus()
                != Ticket::CANCEL_STATUS_VALUE
            ) {
                if (is_null($idPayment)
                    || (intval($idPayment) === intval(
                            $ticketPayment->getIdPayment()
                        ))
                ) {
                    $total += $ticketPayment->getAmount();
                }
            }
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateRealTotal($restaurant = null)
    {
        $total = 0.0;
        $total += $this->calculateRealCashTotal();
        $total += $this->calculateRealTrTotal();
        $total += $this->calculateRealTreTotal($restaurant);
        $total += $this->calculateRealCBTotal($restaurant);
        $total += $this->calculateRealCheckQuickTotal();
        $total += $this->calculateRealForeignCurrencyTotal();

        $this->realTotal = $total;

        return $total;
    }

    public function getInitialTheoricalAmount()
    {
        $total = 0.0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $smallChest = $this->getChestCount()->getLastChestCount()
                ->getSmallChest();
            $total = $smallChest->calculateRealTotal();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateTotalCashboxesCount()
    {
        $total = 0.0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            /**
             * @var CashboxCount $cashboxCount
             */
            $total += $cashboxCount->calculateTotalCashbox();
        }


        return $total;
    }

    /**
     * Calculate bank card real amount from cashbox counts
     *
     * @var    integer $paymentId
     * @return float
     */
    public function calculateBankCardRealTotal($paymentId = null)
    {
        $total = 0.0;
        if (!is_null($this->getChestCount()->getLastChestCount())
            && !$this->getChestCount()->getLastChestCount()->isClosure()
        ) {
            $smallChest = $this->getChestCount()->getLastChestCount()
                ->getSmallChest();
            $total = $smallChest->calculateBankCardRealTotal($paymentId);
        }
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            /**
             * @var CashboxCount $cashboxCount
             */
            $total += $cashboxCount->getBankCardContainer() ?
                $cashboxCount->getBankCardContainer()->calculateBankCardTotal(
                    $paymentId
                ) : 0;
        }

        return $total;
    }

    /**
     * Calculate check restaurant real amount from cashbox counts
     *
     * @var    integer $paymentId
     * @return float
     */
    public function calculateCheckRestaurantRealTotal($paymentId = null)
    {
        $total = 0.0;
        if (!is_null($this->getChestCount()->getLastChestCount())
            && !$this->getChestCount()->getLastChestCount()->isClosure()
        ) {
            $smallChest = $this->getChestCount()->getLastChestCount()
                ->getSmallChest();
            $total = $smallChest->calculateCheckRestaurantRealTotal($paymentId);
        }
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            /**
             * @var CashboxCount $cashboxCount
             */
            $total += $cashboxCount->getCheckRestaurantContainer() ?
                $cashboxCount->getCheckRestaurantContainer()
                    ->calculateRealTotalAmountId(true, $paymentId) : 0;
        }

        return $total;
    }

    // Cash
    public function calculateRealCashTotal()
    {
        $this->realCashTotal = $this->getTotalCash();

        return $this->realCashTotal;
    }

    public function calculateTheoricalCashTotal($restaurant)
    {
        $total = 0.0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $total += $this->getChestCount()->getLastChestCount()
                ->getSmallChest()->getRealCashTotal();
        }
        foreach ($this->cashboxCounts as $cashboxCount) {
            /**
             * @var CashboxCount $cashboxCount
             */
            $total += $cashboxCount->getCashContainer()->getTotal();
            //Ajouter l'espèce des prélèvements qui n'ont pas été placés dans des enveloppes
            $total += $cashboxCount->CalculateCashTotalOfWithdrawalsThatWereNotEnveloped();
        }

        $total -= $this->calculateTotalEnvelopeNotLinkedToAnyWithdrawal($restaurant);

        // Recipe tickets != recipe monnaie & cashbox count
        $recipeTickets = $this->getChestCount()
            ->getRecipeTickets(
                [
                    'label' => [
                        RecipeTicket::CHANGE_RECIPE,
                        RecipeTicket::CACHBOX_RECIPE,
                        RecipeTicket::CASHBOX_ERROR,
                        RecipeTicket::CHEST_ERROR,
                    ],
                    "restaurant" => $restaurant,
                ],
                true
            );
        foreach ($recipeTickets as $recipeTicket) {
            /**
             * @var RecipeTicket $recipeTicket
             */
            $total += $recipeTicket->getAmount();
        }
        // Enveloppes cashbox + small chest
        $cashEnvelopes = $this->getChestCount()->getEnvelopes(
            [
                "type" => Envelope::TYPE_CASH,
                "source" => [
                    Envelope::SMALL_CHEST,
                    Envelope::CASHBOX_COUNTS,
                ],
                "restaurant" => $restaurant,
            ]
        );
        foreach ($cashEnvelopes as $envelope) {
            /**
             * @var Envelope $envelope
             */
            $total -= abs($envelope->getAmount());
        }
        // Diverses Expenses
        $total -= abs(
            $this->getChestCount()->calculateTotalExpenses(
                Expense::GROUP_OTHERS,
                [Expense::DISCOUNT_CHECK_QUICK]
            )
        );

        $this->theoricalCashTotal = $total;

        return $total;
    }

    /**
     * Calculer la valeur totale des enveloppes ( avec source est prélèvement et non liée à aucun prélèvement)
     * @return float|int
     */
    private function calculateTotalEnvelopeNotLinkedToAnyWithdrawal($restaurant)
    {
        $total = 0;
        $envelopes = $this->getChestCount()->getEnvelopes(
            [
                "type" => Envelope::TYPE_CASH,
                "source" => [
                    Envelope::WITHDRAWAL,
                ],
                "restaurant" => $restaurant,
            ]
        );

        foreach ($envelopes as $e) {
            if ($e->getSource() == Envelope::WITHDRAWAL && empty($e->getSourceId())) {
                $total += abs($e->getAmount());
            }
        }

        return $total;
    }

    public function calculateCashGap($restaurant)
    {
        return $this->calculateRealCashTotal()
            - $this->calculateTheoricalCashTotal($restaurant);
    }

    public function getCashGap()
    {
        return $this->getRealCashTotal() - $this->getTheoricalCashTotal();
    }

    // TR
    public function calculateRealTrTotal($paymentId = null)
    {
        $total = 0.0;
        foreach ($this->getTicketRestaurantCounts() as $ticketRestaurantCount) {
            /**
             * @var CashboxTicketRestaurant $ticketRestaurantCount
             */
            if ((!$ticketRestaurantCount->isElectronic() && is_null($paymentId))
                || ($paymentId
                    && intval(
                        $paymentId
                    ) === intval($ticketRestaurantCount->getIdPayment()))
            ) {
                $total += $ticketRestaurantCount->calculateTotal();
            }
        }

        if (is_null($paymentId)) {
            $this->realTrTotal = round($total, 2);
        } else {
            $this->realTrTotalDetail[$paymentId] = round($total, 2);
        }

        return $total;
    }

    public function calculateTheoricalTrTotal($restaurant, $paymentId = null)
    {
        $total = 0.0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            if (is_null($paymentId)) {
                $total += $this->getChestCount()->getLastChestCount()
                    ->getSmallChest()->getRealTrTotal();
            } else {
                $total += $this->getChestCount()->getLastChestCount()
                    ->getSmallChest()->calculateRealTrTotal(
                        $paymentId
                    );
            }
        }
        // TR issue comptage caisse
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            /**
             * @var CashboxCount $cashboxCount
             */
            $total += $cashboxCount->getCheckRestaurantContainer()
                ->calculateRealTotalAmount(false, $paymentId);
        }

        // - TR enveloppe small chest
        $enveloppes = $this->getChestCount()->getEnvelopes(
            [
                "type" => Envelope::TYPE_TICKET,
                "idPayment" => $paymentId,
                "restaurant" => $restaurant,
            ]
        );
        foreach ($enveloppes as $envelope) {
            /**
             * @var Envelope $envelope
             */
            $total -= $envelope->getAmount();
        }

        if (is_null($paymentId)) {
            $this->theoricalTrTotal = round($total, 2);
        } else {
            $this->theoricalTrTotalDetail[$paymentId] = round($total, 2);
        }

        return $total;
    }

    public function calculateTrGap($restaurant)
    {
        return $this->calculateRealTrTotal() - $this->calculateTheoricalTrTotal(
                $restaurant
            );
    }

    public function getTrGap()
    {
        return $this->getRealTrTotal() - $this->getTheoricalTrTotal();
    }

    // TRE
    public function calculateRealTreTotal($restaurant = null)
    {
        $total = $this->calculateTheoricalTreTotal($restaurant);

        $this->realTreTotal = $total;

        return $total;
    }

    public function calculateTheoricalTreTotal($restaurant)
    {
        $total = 0.0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $total += $this->getChestCount()->getLastChestCount()
                ->getSmallChest()->getRealTreTotal();
        }


        // TR issue comptage caisse
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            /**
             * @var CashboxCount $cashboxCount
             */
            $total += $cashboxCount->getCheckRestaurantContainer()
                ->calculateRealTotalAmount(true);
        }


        // - TRE deposit
        $tRDeposits = $this->getChestCount()->getDeposits(
            ['type' => Deposit::TYPE_E_TICKET, "restaurant" => $restaurant]
        );
        foreach ($tRDeposits as $deposit) {
            /**
             * @var Deposit $deposit
             */
            $total -= abs($deposit->getTotalAmount());
        }


        $this->theoricalTreTotal = $total;

        return $total;
    }

    public function calculateTreGap($restaurant)
    {
        return $this->calculateRealTreTotal($restaurant)
            - $this->calculateTheoricalTreTotal($restaurant);
    }

    public function getTreGap()
    {
        return $this->getRealTreTotal() - $this->getTheoricalTreTotal();
    }

    // CB
    public function calculateRealCBTotal($restaurant)
    {
        $this->realCBTotal = $this->calculateTheoricalCBTotal($restaurant);

        return $this->realCBTotal;
    }

    public function calculateTheoricalCBTotal($restaurant)
    {
        $total = 0.0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $total += $this->getChestCount()->getLastChestCount()
                ->getSmallChest()->getRealCBTotal();
        }


        foreach ($this->getCashboxCounts() as $cashboxCount) {
            /**
             * @var CashboxCount $cashboxCount
             */
            $total += $cashboxCount->getBankCardContainer()
                ->calculateBankCardTotal();
        }


        $bankCardDeposits = $this->getChestCount()->getDeposits(
            ['type' => Deposit::TYPE_BANK_CARD, "restaurant" => $restaurant]
        );


        foreach ($bankCardDeposits as $deposit) {
            /**
             * @var Deposit $deposit
             */
            $total -= abs($deposit->getTotalAmount());
        }


        $this->theoricalCBTotal = $total;


        return $total;
    }

    public function calculateCBGap($restaurant)
    {
        return $this->calculateRealCBTotal($restaurant)
            - $this->calculateTheoricalCBTotal($restaurant);
    }

    public function getCBGap()
    {
        return $this->getRealCBTotal() - $this->getTheoricalCBTotal();
    }

    // Check quick

    /**
     * @return float
     */
    public function calculateRealCheckQuickTotal()
    {
        $total = 0.0;
        if ($this->getCheckQuickCounts() != null) {


            foreach ($this->getCheckQuickCounts() as $checkQuickCount) {
                /**
                 * @var CashboxCheckQuick $checkQuickCount
                 */
                $total += $checkQuickCount->calculateTotal();
            }

        }

        $this->realCheckQuickTotal = $total;

        return $total;
    }

    /**
     * @return float|int
     */
    public function calculateTheoricalCheckQuickTotal($restaurant)
    {
        $total = 0.0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $total += $this->getChestCount()->getLastChestCount()
                ->getSmallChest()->getRealCheckQuickTotal();
        }
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            /**
             * @var CashboxCount $cashboxCount
             */
            $total += $cashboxCount->getCheckQuickContainer()
                ->calculateCheckQuickTotal();
        }
        // Expenses discount check quick
        $total -= abs(
            $this->getChestCount()->calculateExpenses(
                $this->getChestCount()->getExpensesByLabels(
                    $restaurant,
                    [Expense::DISCOUNT_CHECK_QUICK]
                )
            )
        );

        $this->theoricalCheckQuickTotal = $total;

        return $total;
    }

    /**
     * @return float.
     */
    public function calculateCheckQuickGap($restaurant)
    {
        return $this->calculateRealCheckQuickTotal()
            - $this->calculateTheoricalCheckQuickTotal($restaurant);
    }

    /**
     * @return float.
     */
    public function getCheckQuickGap()
    {
        return $this->getRealCheckQuickTotal()
            - $this->getTheoricalCheckQuickTotal();
    }

    // Foreign currency

    /**
     * @return float
     */
    public function calculateRealForeignCurrencyTotal()
    {
        $total = 0.0;
        foreach ($this->getForeignCurrencyCounts() as $foreignCurrencyCount) {
            /**
             * @var CashboxForeignCurrency $foreignCurrencyCount
             */
            $total += $foreignCurrencyCount->getTotal();
        }

        $this->realForeignCurrencyTotal = $total;

        return $total;
    }

    /**
     * @return float|int
     */
    public function calculateTheoricalForeignCurrencyTotal()
    {
        $total = 0.0;
        if (!is_null($this->getChestCount()->getLastChestCount())) {
            $total += $this->getChestCount()->getLastChestCount()
                ->getSmallChest()->getRealForeignCurrencyTotal();
        }
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            /**
             * @var CashboxCount $cashboxCount
             */
            $total += $cashboxCount->getForeignCurrencyContainer()
                ->calculateTotalForeignCurrencyAmount();
        }

        $this->theoricalForeignCurrencyTotal = $total;

        return $total;
    }

    /**
     * @return float
     */
    public function calculateForeignCurrencyGap()
    {
        return $this->calculateRealForeignCurrencyTotal()
            - $this->calculateTheoricalForeignCurrencyTotal();
    }


    /**
     * @return float
     */
    public function getForeignCurrencyGap()
    {
        return $this->getRealForeignCurrencyTotal()
            - $this->getTheoricalForeignCurrencyTotal();
    }

    /**
     * @return float
     */
    public function calculateTheoricalTotal(Restaurant $restaurant = null)
    {
        $total = 0.0;
        $total += $this->calculateTheoricalCashTotal($restaurant);
        $total += $this->calculateTheoricalTrTotal($restaurant);
        $total += $this->calculateTheoricalTreTotal($restaurant);
        $total += $this->calculateTheoricalCBTotal($restaurant);
        $total += $this->calculateTheoricalCheckQuickTotal($restaurant);
        $total += $this->calculateTheoricalForeignCurrencyTotal();

        $this->theoricalTotal = $total;
        $this->calculateTheoricalTrDetail();
        $this->calculateRealTrDetail();

        return $total;
    }

    public function calculateGap($restaurant = null)
    {
        $this->setGap(
            $this->calculateRealTotal($restaurant)
            - $this->calculateTheoricalTotal($restaurant)
        );

        return $this->getGap();
    }

    /**
     * @return float
     */
    public function getRealTotal()
    {
        return $this->realTotal;
    }

    /**
     * @param float $realTotal
     */
    public function setRealTotal($realTotal)
    {
        $this->realTotal = $realTotal;
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
     * @return float
     */
    public function getGap()
    {
        return $this->gap;
    }

    /**
     * @param float $gap
     */
    public function setGap($gap)
    {
        $this->gap = $gap;
    }

    /**
     * @return float
     */
    public function getTheoricalCashTotal()
    {
        return $this->theoricalCashTotal;
    }

    /**
     * @param float $theoricalCashTotal
     */
    public function setTheoricalCashTotal($theoricalCashTotal)
    {
        $this->theoricalCashTotal = $theoricalCashTotal;
    }

    /**
     * @return float
     */
    public function getTheoricalTrTotal()
    {
        return $this->theoricalTrTotal;
    }

    /**
     * @param float $theoricalTrTotal
     */
    public function setTheoricalTrTotal($theoricalTrTotal)
    {
        $this->theoricalTrTotal = $theoricalTrTotal;
    }

    /**
     * @return float
     */
    public function getTheoricalTreTotal()
    {
        return $this->theoricalTreTotal;
    }

    /**
     * @param float $theoricalTreTotal
     */
    public function setTheoricalTreTotal($theoricalTreTotal)
    {
        $this->theoricalTreTotal = $theoricalTreTotal;
    }

    /**
     * @return float
     */
    public function getTheoricalCBTotal()
    {
        return $this->theoricalCBTotal;
    }

    /**
     * @param float $theoricalCBTotal
     */
    public function setTheoricalCBTotal($theoricalCBTotal)
    {
        $this->theoricalCBTotal = $theoricalCBTotal;
    }

    /**
     * @return float
     */
    public function getTheoricalCheckQuickTotal()
    {
        return $this->theoricalCheckQuickTotal;
    }

    /**
     * @param float $theoricalCheckQuickTotal
     */
    public function setTheoricalCheckQuickTotal($theoricalCheckQuickTotal)
    {
        $this->theoricalCheckQuickTotal = $theoricalCheckQuickTotal;
    }

    /**
     * @return float
     */
    public function getTheoricalForeignCurrencyTotal()
    {
        return $this->theoricalForeignCurrencyTotal;
    }

    /**
     * @param float $theoricalForeignCurrencyTotal
     */
    public function setTheoricalForeignCurrencyTotal(
        $theoricalForeignCurrencyTotal
    )
    {
        $this->theoricalForeignCurrencyTotal = $theoricalForeignCurrencyTotal;
    }

    /**
     * @return float
     */
    public function getRealCashTotal()
    {
        return $this->realCashTotal;
    }

    /**
     * @param float $realCashTotal
     */
    public function setRealCashTotal($realCashTotal)
    {
        $this->realCashTotal = $realCashTotal;
    }

    /**
     * @return float
     */
    public function getRealTrTotal()
    {
        return $this->realTrTotal;
    }

    /**
     * @param float $realTrTotal
     */
    public function setRealTrTotal($realTrTotal)
    {
        $this->realTrTotal = $realTrTotal;
    }

    /**
     * @param null $paymentId
     *
     * @return array
     */
    public function getRealTrTotalDetail($paymentId = null)
    {
        if (is_null($paymentId)) {
            return $this->realTrTotalDetail;
        } else {
            return $this->realTrTotalDetail[$paymentId];
        }
    }

    /**
     * @param array $realTrTotalDetail
     */
    public function setRealTrTotalDetail($realTrTotalDetail)
    {
        $this->realTrTotalDetail = $realTrTotalDetail;
    }

    /**
     * @param null $paymentId
     *
     * @return array
     */
    public function getTheoricalTrTotalDetail($paymentId = null)
    {
        if (is_null($paymentId)) {
            return $this->theoricalTrTotalDetail;
        } else {
            return $this->theoricalTrTotalDetail[$paymentId];
        }
    }

    /**
     * @param array $theoricalTrTotalDetail
     */
    public function setTheoricalTrTotalDetail($theoricalTrTotalDetail)
    {
        $this->theoricalTrTotalDetail = $theoricalTrTotalDetail;
    }

    /**
     * @return float
     */
    public function getRealTreTotal()
    {
        return $this->realTreTotal;
    }

    /**
     * @param float $realTreTotal
     */
    public function setRealTreTotal($realTreTotal)
    {
        $this->realTreTotal = $realTreTotal;
    }

    /**
     * @return float
     */
    public function getRealCBTotal()
    {
        return $this->realCBTotal;
    }

    /**
     * @param float $realCBTotal
     */
    public function setRealCBTotal($realCBTotal)
    {
        $this->realCBTotal = $realCBTotal;
    }

    /**
     * @return float
     */
    public function getRealCheckQuickTotal()
    {
        return $this->realCheckQuickTotal;
    }

    /**
     * @param float $realCheckQuickTotal
     */
    public function setRealCheckQuickTotal($realCheckQuickTotal)
    {
        $this->realCheckQuickTotal = $realCheckQuickTotal;
    }

    /**
     * @return float
     */
    public function getRealForeignCurrencyTotal()
    {
        return $this->realForeignCurrencyTotal;
    }

    /**
     * @param float $realForeignCurrencyTotal
     */
    public function setRealForeignCurrencyTotal($realForeignCurrencyTotal)
    {
        $this->realForeignCurrencyTotal = $realForeignCurrencyTotal;
    }


    public function calculateTheoricalTrDetail()
    {
        $ids = $this->getTicketPaymentsId();
        foreach ($ids as $id) {
            $this->theoricalTrTotalDetail[$id] = round(
                $this->calculateTheoricalTrTotal($id),
                2
            );
        }
    }


    public function calculateRealTrDetail()
    {
        $ids = $this->getTicketPaymentsId();
        foreach ($ids as $id) {
            $this->realTrTotalDetail[$id] = round(
                $this->calculateRealTrTotal($id),
                2
            );
        }
    }

    public function getTicketPaymentsId()
    {
        $ids = [];
        if ($this->getChestCount()->getLastChestCount()) {
            $ids = $this->getChestCount()->getLastChestCount()->getSmallChest()
                ->getTicketRestaurantId();
            if ($this->getChestCount()->getLastChestCount()->getSmallChest()
                ->getRealTrTotalDetail()
            ) {
                foreach (
                    $this->getChestCount()->getLastChestCount()->getSmallChest()
                        ->getRealTrTotalDetail() as $key => $item
                ) {
                    $ids[] = $key;
                }
            }
        }
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            foreach (
                $cashboxCount->getCheckRestaurantContainer()->getPaymentId() as
                $id
            ) {
                $ids[] = $id;
            }
        }

        return array_unique($ids);
    }

    public function getCheckQuickNames()
    {

        $result = [];
        foreach ($this->getCheckQuickCounts() as $checkQuickCount) {
            /**
             * @var CashboxCheckQuick $checkQuickCount
             */
            if (!in_array($checkQuickCount->getCheckName(), $result)) {

                $result[] = $checkQuickCount->getCheckName();

            }
        }

        return $result;

    }


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
}
