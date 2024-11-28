<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/04/2016
 * Time: 10:40
 */

namespace AppBundle\Financial\Model;

use AppBundle\Financial\Entity\CashboxCount;

class DayIncome
{

    public function __construct()
    {
    }

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var Mixed
     */
    private $cashboxCounts;

    /**
     * @var float
     */
    private $discountsTotal;

    /**
     * @var float
     */
    private $discountsTheoricalTotal;

    /**
     * @var float
     */
    private $discountsGap;

    /**
     * @var float
     */
    private $cashboxTotalGap;


    /**
     * @return CashboxCount[]
     */
    public function getCashboxCounts()
    {
        if ($this->cashboxCounts === null) {
            return [];
        }

        return $this->cashboxCounts;
    }

    /**
     * @param Mixed $cashboxCounts
     * @return DayIncome
     */
    public function setCashboxCounts($cashboxCounts)
    {
        $this->cashboxCounts = $cashboxCounts;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     * @return DayIncome
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountsTotal()
    {
        return $this->discountsTotal;
    }

    /**
     * @param $discountsTotal
     */
    public function setDiscountsTotal($discountTotal)
    {
        $this->discountsTotal = $discountTotal;
    }

    /**
     * @return float
     */
    public function getCashboxTotalGap()
    {
        return $this->cashboxTotalGap;
    }

    /**
     * @param float $cashboxTotalGap
     */
    public function setCashboxTotalGap($cashboxTotalGap)
    {
        $this->cashboxTotalGap = $cashboxTotalGap;
    }

    /**
     * @return float
     */
    public function getDiscountsTheoricalTotal()
    {
        return $this->discountsTheoricalTotal;
    }

    /**
     * @param mixed $discountsTheoricalTotal
     */
    public function setDiscountsTheoricalTotal($discountsTheoricalTotal)
    {
        $this->discountsTheoricalTotal = $discountsTheoricalTotal;
    }

    /**
     * @return float
     */
    public function getDiscountsGap()
    {
        return $this->discountsGap;
    }

    /**
     * @param mixed $discountsGap
     */
    public function setDiscountsGap($discountsGap)
    {
        $this->discountsGap = $discountsGap;
    }

    /* DayIncome Calculation */
    // Real Cash
    /**
     * @return float
     * @deprecated
     */
    public function calculateRealCashTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getCashContainer()->getTotalAmount();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function getRealCashTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getCashContainer()->getTotalAmount();
        }

        return $total;
    }

    /**
     * @return float
     * @deprecated
     */
    public function calculateRealCashTheoricalTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getTheoricalCashTotal();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function getRealCashTheoricalTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getTheoricalCashTotal();
        }

        return $total;
    }

    /**
     * @return float
     * @deprecated
     */
    public function calculateRealCashGap()
    {
        return $this->calculateRealCashTotal() - $this->calculateRealCashTheoricalTotal();
    }

    /**
     * @return float
     */
    public function getRealCashGap()
    {
        return $this->getRealCashTotal() - $this->getRealCashTheoricalTotal();
    }

    // Check Quick

    /**
     * @return float
     */
    public function calculateCheckQuickTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getCheckQuickContainer()->calculateCheckQuickTotal();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateCheckQuickTheoricalTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getCheckQuickContainer()->calculateTheoricalTotal();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateCheckQuickGap()
    {
        return abs($this->calculateCheckQuickTotal()) - abs($this->calculateCheckQuickTheoricalTotal());
    }
    // Bank Card

    /**
     * @return float
     */
    public function calculateBankCardTotal($idPayment = null)
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getBankCardContainer()->calculateBankCardTotal($idPayment);
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateBankCardTheoricalTotal($idPayment = null)
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getBankCardContainer()->calculateTheoricalTotal($idPayment);
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateBankCardGap($idPayment = null)
    {
        return abs($this->calculateBankCardTotal($idPayment)) - abs($this->calculateBankCardTheoricalTotal($idPayment));
    }
    // Foreign Currency

    /**
     * @return float
     */
    public function calculateForeignCurrencyTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getForeignCurrencyContainer()->calculateTotalForeignCurrencyAmount();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateForeignCurrencyThoricalTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getForeignCurrencyContainer()->calculateTheoricalTotal();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateForeignCurrencyGap()
    {
        return abs($this->calculateForeignCurrencyTotal()) - abs($this->calculateForeignCurrencyThoricalTotal());
    }
    // Check Restaurant

    /**
     * @return float
     */
    public function calculateCheckRestaurantTotal($electronic = null,$paymentId= null)
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getCheckRestaurantContainer()->calculateRealTotalAmount($electronic,$paymentId);
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateCheckRestaurantTheoricalTotal($electronic = null, $paymentId = null)
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getCheckRestaurantContainer()->calculateTheoricalTotal($electronic,$paymentId);
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateCheckRestaurantGap($electronic = null, $paymentId= null)
    {
        return abs($this->calculateCheckRestaurantTotal($electronic, $paymentId)) - abs(
            $this->calculateCheckRestaurantTheoricalTotal($electronic,$paymentId)
        );
    }
    // Discounts

    /**
     * @return float
     */
    public function calculateDiscountsTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getDiscountContainer()->getTotalAmount();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateDiscountsTheoricalTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getDiscountContainer()->calculateTheoricalTotal();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateDiscountsGap()
    {
        return abs($this->calculateDiscountsTotal()) - abs($this->calculateDiscountsTheoricalTotal());
    }
    // Meal Tickets

    /**
     * @return float
     */
    public function calculateMealTicketsTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getMealTicketContainer()->getTotalAmount();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateMealTicketsTheoricalTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getMealTicketContainer()->calculateTheoricalTotal();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateMealTicketsGap()
    {
        return abs($this->calculateMealTicketsTotal()) - abs($this->calculateMealTicketsTheoricalTotal());
    }
    // Withdrawals

    /**
     * @return float
     */
    public function calculateTotalWithdrawals()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->calculateTotalWithdrawals();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateWithdrawalsGap()
    {
        return abs($this->calculateTotalWithdrawals()) - abs($this->calculateTotalWithdrawals());
    }
    // Cashbox Total

    /**
     * @return float
     */
    public function calculateCashboxTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->calculateTotalCashbox();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function getCashboxTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getRealCaCounted();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateCashboxTheoricalTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->calculateTheoricalTotalCashbox();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateCashboxTotalGap()
    {
        return abs($this->calculateCashboxTotal()) - abs($this->calculateCashboxTheoricalTotal());
    }
    // Real Cashbox Total

    /**
     * @deprecated
     * @return float
     */
    public function calculateRealCashboxTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->calculateRealTotalCashbox();
        }

        return $total;
    }

    /**
     * @deprecated
     * @return float
     */
    public function calculateRealCashboxTheoricalTotal()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->calculateTheoricalRealTotalCashbox();
        }

        return $total;
    }

    /**
     * @deprecated
     * @return float
     */
    public function calculateRealCashboxTotalGap()
    {
        return abs($this->calculateRealCashboxTotal()) - abs($this->calculateRealCashboxTheoricalTotal());
    }

    // Cancels

    /**
     * @return float
     */
    public function calculateQuantityCancels()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getNumberCancels();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateTotalCancels()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getTotalCancels();
        }

        return $total;
    }
    // Corrections

    /**
     * @return float
     */
    public function calculateQuantityCorrections()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getNumberCorrections();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateTotalCorrections()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getTotalCorrections();
        }

        return $total;
    }
    // Abondons

    /**
     * @return float
     */
    public function calculateQuantityAbondons()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getNumberAbondons();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function calculateTotalAbondons()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getTotalAbondons();
        }

        return $total;
    }


    public function getCashBoxGap()
    {
        $total = 0;
        foreach ($this->getCashboxCounts() as $cashboxCount) {
            $total += $cashboxCount->getGap();
        }

        return $total;
    }
}
