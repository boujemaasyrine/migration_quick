<?php

namespace AppBundle\Financial\Entity;

use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Envelope
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\EnvelopeRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Envelope
{

    // source values
    const WITHDRAWAL = 'withdrawal';
    const CASHBOX_COUNTS = 'cashbox_counts';
    const EXCHANGE_FUNDS = 'exchange_funds';
    const SMALL_CHEST = 'small_chest';
    const CASHBOX_FUNDS = 'cashbox_funds';

    // status values
    const VERSED = 'versed';
    const NOT_VERSED = 'not_versed';

    const TYPE_CASH = 'cash';
    const TYPE_TICKET = 'ticket';

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
     * @var integer
     *
     * @ORM\Column(name="number", type="integer")
     */
    private $numEnvelope;

    /**
     * @var integer
     *
     * @ORM\Column(name="reference", type="string", nullable=true)
     */
    private $reference;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     */
    private $amount;

    /**
     * @var integer
     *
     * @ORM\Column(name="source_id", type="integer", nullable=true)
     */
    private $sourceId;

    /**
     * @var string
     * @ORM\Column(name="source",type="string",length=20)
     */
    private $source;

    /**
     * @var string
     * @ORM\Column(name="status",type="string",length=20, nullable=TRUE)
     */
    private $status;

    /**
     * @var string
     * @ORM\Column(name="type",type="string",length=20, nullable=false, options={"default" : "cash"})
     */
    private $type = Envelope::TYPE_CASH;

    /**
     * @var string
     * @ORM\Column(name="sous_type",type="string",length=20, nullable=false, options={"default" : "cash"})
     */
    private $sousType = Envelope::TYPE_CASH;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $owner;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $cashier;

    /**
     * @var Deposit
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\Deposit", inversedBy="envelopes")
     */
    private $deposit;

    /**
     * @var ChestCount
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\ChestCount", inversedBy="envelopes")
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
     * Set numEnvelope
     *
     * @param integer $numEnvelope
     *
     * @return Envelope
     */
    public function setNumEnvelope($numEnvelope)
    {
        $this->numEnvelope = $numEnvelope;

        return $this;
    }

    /**
     * Get numEnvelope
     *
     * @return integer
     */
    public function getNumber()
    {
        return $this->numEnvelope;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return Envelope
     */
    public function setAmount($amount)
    {
        $this->amount = str_replace(',', '.', $amount);

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
     * Get numEnvelope
     *
     * @return integer
     */
    public function getNumEnvelope()
    {
        return $this->numEnvelope;
    }


    /**
     * Set source
     *
     * @param string $source
     *
     * @return Envelope
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }


    /**
     * Set sourceId
     *
     * @param integer $sourceId
     *
     * @return Envelope
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    /**
     * Get sourceId
     *
     * @return integer
     */
    public function getSourceId()
    {
        return $this->sourceId;
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
     * @return Envelope
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Employee
     */
    public function getCashier()
    {
        return $this->cashier;
    }

    /**
     * @param Employee $cashier
     * @return Envelope
     */
    public function setCashier($cashier)
    {
        $this->cashier = $cashier;

        return $this;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return Envelope
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set reference
     *
     * @param integer $reference
     *
     * @return Envelope
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
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Set deposit
     *
     * @param \AppBundle\Financial\Entity\Deposit $deposit
     *
     * @return Envelope
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
     * @return Envelope
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
     * Set sousType
     *
     * @param string $sousType
     *
     * @return Envelope
     */
    public function setSousType($sousType)
    {
        $this->sousType = $sousType;

        return $this;
    }

    /**
     * Get sousType
     *
     * @return string
     */
    public function getSousType()
    {
        return $this->sousType;
    }
}
