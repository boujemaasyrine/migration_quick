<?php

namespace AppBundle\Financial\Entity;

use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * DeletedEnvelope
 *
 * @ORM\Table(name="deleted_envelope")
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\DeletedEnvelopeRepository")
 */
class DeletedEnvelope
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
     * @ORM\Column(name="original_id", type="integer", nullable=true)
     */
    private $originalId;

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
     * @ORM\Column(name="source", type="string", length=20)
     */
    private $source;

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=20, nullable=true)
     */
    private $status;

    /**
     * @var string
     * @ORM\Column(name="type", type="string", length=20, nullable=false, options={"default" : "cash"})
     */
    private $type = Envelope::TYPE_CASH;

    /**
     * @var string
     * @ORM\Column(name="sous_type", type="string", length=20, nullable=false, options={"default" : "cash"})
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
     * @var \DateTime
     *
     * @ORM\Column(name="deleted_at", type="datetime")
     */
    private $deletedAt;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $deletedBy;

    // Getters and setters for all properties

    public function getId()
    {
        return $this->id;
    }

    public function setNumEnvelope($numEnvelope)
    {
        $this->numEnvelope = $numEnvelope;
        return $this;
    }

    public function getNumEnvelope()
    {
        return $this->numEnvelope;
    }

    public function setReference($reference)
    {
        $this->reference = $reference;
        return $this;
    }

    public function getReference()
    {
        return $this->reference;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;
        return $this;
    }

    public function getSourceId()
    {
        return $this->sourceId;
    }

    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setSousType($sousType)
    {
        $this->sousType = $sousType;
        return $this;
    }

    public function getSousType()
    {
        return $this->sousType;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
        return $this;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setCashier($cashier)
    {
        $this->cashier = $cashier;
        return $this;
    }

    public function getCashier()
    {
        return $this->cashier;
    }

    public function setDeposit($deposit)
    {
        $this->deposit = $deposit;
        return $this;
    }

    public function getDeposit()
    {
        return $this->deposit;
    }

    public function setChestCount($chestCount)
    {
        $this->chestCount = $chestCount;
        return $this;
    }

    public function getChestCount()
    {
        return $this->chestCount;
    }

    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedBy($deletedBy)
    {
        $this->deletedBy = $deletedBy;
        return $this;
    }

    public function getDeletedBy()
    {
        return $this->deletedBy;
    }


    public function getOriginalId()
    {
        return $this->originalId;
    }

    public function setOriginalId($originalId)
    {
        $this->originalId = $originalId;

        return $this;
    }
}
