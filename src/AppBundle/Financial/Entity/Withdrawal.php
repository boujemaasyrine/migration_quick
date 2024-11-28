<?php

namespace AppBundle\Financial\Entity;

use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\GlobalIdTrait;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Financial\Validator as FinancialAssert;

/**
 * Withdrawal
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\WithdrawalRepository")
 * @FinancialAssert\MaxAmountWithdrawalConstraint
 * @ORM\HasLifecycleCallbacks()
 */
class Withdrawal
{

    use TimestampableTrait;
    use SynchronizedFlagTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;

    const COUNTED = 'counted';
    const NOT_COUNTED = 'not_counted';


    const VERSED = 'versed';
    const NOT_VERSED = 'not_versed';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var float
     *
     * @ORM\Column(name="amount_withdrawal", type="float")
     */
    private $amountWithdrawal;

    /**
     * @var string
     * @ORM\Column(name="status_count",type="string",length=20,nullable=true)
     */
    private $statusCount;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee",inversedBy="withdrawals")
     */
    private $member;

    /**
     * Le responsable de ce prélèvement(dans la caisse)
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $responsible;

    /**
     * Le responsable qui a validé ce prélèvement(dans BO)
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $validatedBy;

    /**
     * @var integer
     *
     * @ORM\Column(name="envelope_id", type="integer", nullable=true)
     */
    private $envelopeId;

    /**
     * @var CashboxCount
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\CashboxCount", inversedBy="withdrawals")
     */
    private $cashboxCount;

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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Withdrawal
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate($format = null)
    {
        if (!is_null($format) && !is_null($this->date)) {
            return $this->date->format($format);
        }

        return $this->date;
    }

    /**
     * Set amountWithdrawal
     *
     * @param float $amountWithdrawal
     *
     * @return Withdrawal
     */
    public function setAmountWithdrawal($amountWithdrawal)
    {
        $this->amountWithdrawal = $amountWithdrawal;

        return $this;
    }

    /**
     * Get amountWithdrawal
     *
     * @return float
     */
    public function getAmountWithdrawal()
    {
        return $this->amountWithdrawal;
    }


    /**
     * Set member
     *
     * @param \AppBundle\Staff\Entity\Employee $member
     *
     * @return Withdrawal
     */
    public function setMember(\AppBundle\Staff\Entity\Employee $member = null)
    {
        $this->member = $member;

        return $this;
    }

    /**
     * Get member
     *
     * @return \AppBundle\Staff\Entity\Employee
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * Set responsible
     *
     * @param \AppBundle\Staff\Entity\Employee $responsible
     *
     * @return Withdrawal
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
     * Set envelopeId
     *
     * @param integer $envelopeId
     *
     * @return Withdrawal
     */
    public function setEnvelopeId($envelopeId)
    {
        $this->envelopeId = $envelopeId;

        return $this;
    }

    /**
     * Get envelopeId
     *
     * @return integer
     */
    public function getEnvelopeId()
    {
        return $this->envelopeId;
    }

    /**
     * Set statusCount
     *
     * @param string $statusCount
     *
     * @return Withdrawal
     */
    public function setStatusCount($statusCount)
    {
        $this->statusCount = $statusCount;

        return $this;
    }

    /**
     * Get statusCount
     *
     * @return string
     */
    public function getStatusCount()
    {
        return $this->statusCount;
    }

    /**
     * @return CashboxCount
     */
    public function getCashboxCount()
    {
        return $this->cashboxCount;
    }

    /**
     * @param CashboxCount $cashboxCount
     * @return Withdrawal
     */
    public function setCashboxCount($cashboxCount)
    {
        $this->statusCount = self::COUNTED;
        $this->cashboxCount = $cashboxCount;

        return $this;
    }

    /**
     * @return Employee
     */
    public function getValidatedBy()
    {
        return $this->validatedBy;
    }

    /**
     * @param Employee $validatedBy
     */
    public function setValidatedBy($validatedBy)
    {
        $this->validatedBy = $validatedBy;
    }


}
