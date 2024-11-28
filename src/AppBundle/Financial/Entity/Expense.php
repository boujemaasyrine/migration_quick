<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 30/03/2016
 * Time: 18:05
 */

namespace AppBundle\Financial\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Staff\Entity\Employee;

/**
 * Expense
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\ExpenseRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Expense
{

    // Groups for Expense
    const GROUP_OTHERS = 'GROUP_OTHERS';
    const GROUP_BANK_CASH_PAYMENT = 'GROUP_BANK_CASH_PAYMENT';
    const GROUP_BANK_RESTAURANT_PAYMENT = 'GROUP_BANK_RESTAURANT_PAYMENT';
    const GROUP_BANK_E_RESTAURANT_PAYMENT = 'GROUP_BANK_E_RESTAURANT_PAYMENT';
    const GROUP_BANK_CARD_PAYMENT = 'GROUP_BANK_CARD_PAYMENT';
    const GROUP_ERROR_COUNT = 'GROUP_ERROR_COUNT';

    // Static labels
    const ERROR_CHEST = "chest_error";
    const ERROR_CASHBOX = "cashbox_error";
    const ERROR_DAY_INCOME = "day_income_error";

    const DISCOUNT_CHECK_QUICK = '38';

    use TimestampableTrait;
    use SynchronizedFlagTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="group_expense",type="string",length=40)
     */
    private $groupExpense;

    /**
     * @var string
     * @ORM\Column(name="sous_group",type="string",length=40, nullable=TRUE)
     */
    private $sousGroup;


    /**
     * @var string
     * @ORM\Column(name="comment",type="text", nullable=true)
     */
    private $comment;

    /**
     * @var float
     *
     * @ORM\Column(name="tva", type="float", nullable=true)
     */
    private $tva;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $responsible;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     */
    private $amount;

    /**
     * @var integer
     *
     * @ORM\Column(name="reference", type="integer")
     */
    private $reference;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_expense", type="date")
     */
    private $dateExpense;

    /**
     * @var Deposit
     * @ORM\OneToOne(targetEntity="AppBundle\Financial\Entity\Deposit", mappedBy="expense")
     */
    private $deposit;

    /**
     * @var ChestCount
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\ChestCount", inversedBy="expenses")
     */
    private $chestCount;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set comment
     *
     * @param string $comment
     *
     * @return Expense
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set tva
     *
     * @param float $tva
     *
     * @return Expense
     */
    public function setTva($tva)
    {
        $this->tva = $tva;

        return $this;
    }

    /**
     * Get tva
     *
     * @return float
     */
    public function getTva()
    {
        return $this->tva;
    }

    /**
     * Set responsible
     *
     * @param \AppBundle\Staff\Entity\Employee $responsible
     *
     * @return Expense
     */
    public function setResponsible(\AppBundle\Staff\Entity\Employee $responsible = null)
    {
        $this->responsible = $responsible;

        return $this;
    }

    /**
     * Get responsible
     *
     * @return \AppBundle\Staff\Entity\Employee
     */
    public function getResponsible()
    {
        return $this->responsible;
    }


    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return Expense
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }


    /**
     * Set groupExpense
     *
     * @param string $groupExpense
     *
     * @return Expense
     */
    public function setGroupExpense($groupExpense)
    {
        $this->groupExpense = $groupExpense;

        return $this;
    }

    /**
     * Get groupExpense
     *
     * @return string
     */
    public function getGroupExpense()
    {
        return $this->groupExpense;
    }


    /**
     * Set reference
     *
     * @param integer $reference
     *
     * @return Expense
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Get reference
     *
     * @return integer
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set dateExpense
     *
     * @param \DateTime $dateExpense
     *
     * @return Expense
     */
    public function setDateExpense($dateExpense)
    {
        $this->dateExpense = $dateExpense;

        return $this;
    }

    /**
     * Get dateExpense
     *
     * @return \DateTime
     */
    public function getDateExpense($format = null)
    {
        if (!is_null($format) && !is_null($this->dateExpense)) {
            return $this->dateExpense->format($format);
        }

        return $this->dateExpense;
    }

    /**
     * Set deposit
     *
     * @param \AppBundle\Financial\Entity\Deposit $deposit
     *
     * @return Expense
     */
    public function setDeposit(\AppBundle\Financial\Entity\Deposit $deposit = null)
    {
        $this->deposit = $deposit;

        return $this;
    }

    /**
     * Get deposit
     *
     * @return \AppBundle\Financial\Entity\Deposit
     */
    public function getDeposit()
    {
        return $this->deposit;
    }

    /**
     * Set chestCount
     *
     * @param \AppBundle\Financial\Entity\ChestCount $chestCount
     *
     * @return Expense
     */
    public function setChestCount(\AppBundle\Financial\Entity\ChestCount $chestCount = null)
    {
        $this->chestCount = $chestCount;

        return $this;
    }

    /**
     * Get chestCount
     *
     * @return \AppBundle\Financial\Entity\ChestCount
     */
    public function getChestCount()
    {
        return $this->chestCount;
    }

    /**
     * Set sousGroup
     *
     * @param string $sousGroup
     *
     * @return Expense
     */
    public function setSousGroup($sousGroup)
    {
        $this->sousGroup = $sousGroup;

        return $this;
    }

    /**
     * Get sousGroup
     *
     * @return string
     */
    public function getSousGroup()
    {
        return $this->sousGroup;
    }
}
