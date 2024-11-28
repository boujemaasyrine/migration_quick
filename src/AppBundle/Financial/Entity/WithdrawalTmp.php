<?php

namespace AppBundle\Financial\Entity;

use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;

/**
 * Class WithdrawalTmp
 * @package AppBundle\Financial\Entity
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\WithdrawalTmpRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class WithdrawalTmp
{
    use TimestampableTrait;
    use OriginRestaurantTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $member;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $responsible;
    /**
     * @var float
     *
     * @ORM\Column(name="amount_withdrawal",nullable=true ,type="float")
     */
    private $amountWithdrawal;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time",nullable=true, type="datetime")
     */
    private $time;

    /**
     * @var boolean
     * @ORM\Column(name="validated",type="boolean",nullable=true, options={"default" : false})
     */
    private $validated = false;

    /**
     * @var \Datetime $validatedAt
     *
     * @ORM\Column(name="validated_at", type="datetime" , nullable=true, options={"default" = null})
     */
    private $validatedAt;

    /**
     * @var Withdrawal
     * @ORM\OneToOne(targetEntity="AppBundle\Financial\Entity\Withdrawal")
     */
    private $withdrawal;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return Employee
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @param Employee $member
     */
    public function setMember($member)
    {
        $this->member = $member;
    }

    /**
     * @return Employee
     */
    public function getResponsible()
    {
        return $this->responsible;
    }

    /**
     * @param Employee $responsible
     */
    public function setResponsible($responsible)
    {
        $this->responsible = $responsible;
    }

    /**
     * @return float
     */
    public function getAmountWithdrawal()
    {
        return $this->amountWithdrawal;
    }

    /**
     * @param float $amountWithdrawal
     */
    public function setAmountWithdrawal($amountWithdrawal)
    {
        $this->amountWithdrawal = $amountWithdrawal;
    }

    /**
     * @return bool
     */
    public function isValidated()
    {
        return $this->validated;
    }

    /**
     * @param bool $validated
     */
    public function setValidated($validated)
    {
        $this->validated = $validated;
    }

    /**
     * @return \Datetime
     */
    public function getValidatedAt()
    {
        return $this->validatedAt;
    }

    /**
     * @param \Datetime $validatedAt
     */
    public function setValidatedAt($validatedAt)
    {
        $this->validatedAt = $validatedAt;
    }

    /**
     * @return \DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param \DateTime $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * @return Withdrawal
     */
    public function getWithdrawal()
    {
        return $this->withdrawal;
    }

    /**
     * @param Withdrawal $withdrawal
     */
    public function setWithdrawal($withdrawal)
    {
        $this->withdrawal = $withdrawal;
    }


}