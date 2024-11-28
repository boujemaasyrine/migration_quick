<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 26/04/2016
 * Time: 17:53
 */

namespace AppBundle\Financial\Entity;

use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * CashboxCount
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\DepositRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Deposit
{
    const TYPE_CASH = 'cash';
    const TYPE_TICKET = 'ticket';
    const TYPE_E_TICKET = 'e_ticket';
    const TYPE_BANK_CARD = 'bank_card';

    const SOURCE_TIRELIRE = 'tirelire';
    const SOURCE_SMALL_CHEST = 'small_chest';

    const DESTINATION_BANK = 'bank';

    use TimestampableTrait;
    use IdTrait;
    use SynchronizedFlagTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="reference", type="integer", nullable=true)
     */
    private $reference;

    /**
     * @var string
     * @ORM\Column(name="source",type="string",length=20)
     */
    private $source;

    /**
     * @var string
     * @ORM\Column(name="destination",type="string",length=20)
     */
    private $destination;

    /**
     * @var string
     * @ORM\Column(name="affiliate_code",type="string",length=20, nullable=true)
     */
    private $affiliateCode;

    /**
     * @var string
     * @ORM\Column(name="type",type="string",length=20, nullable=false)
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(name="sous_type",type="string",length=20, nullable=true)
     */
    private $sousType;

    /**
     * @var string
     * @ORM\Column(name="total_amount",type="float")
     */
    private $totalAmount;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $owner;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Financial\Entity\Envelope", mappedBy="deposit")
     */
    private $envelopes;

    /**
     * @var ArrayCollection
     * @ORM\OneToOne(targetEntity="AppBundle\Financial\Entity\Expense", inversedBy="deposit")
     */
    private $expense;

    /**
     * @var ChestCount
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\ChestCount", inversedBy="deposits")
     */
    private $chestCount;

    /**
     * CashboxCount constructor.
     */
    public function __construct()
    {
        $this->envelopes = new ArrayCollection();
    }

    /**
     * Set reference
     *
     * @param string $reference
     *
     * @return Deposit
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Get reference
     *
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set source
     *
     * @param string $source
     *
     * @return Deposit
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
     * Set destination
     *
     * @param string $destination
     *
     * @return Deposit
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;

        return $this;
    }

    /**
     * Get destination
     *
     * @return string
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Deposit
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set owner
     *
     * @param \AppBundle\Staff\Entity\Employee $owner
     *
     * @return Deposit
     */
    public function setOwner(\AppBundle\Staff\Entity\Employee $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return \AppBundle\Staff\Entity\Employee
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Add envelope
     *
     * @param \AppBundle\Financial\Entity\Envelope $envelope
     *
     * @return Deposit
     */
    public function addEnvelope(\AppBundle\Financial\Entity\Envelope $envelope)
    {
        $this->envelopes[] = $envelope;

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
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEnvelopes()
    {
        return $this->envelopes;
    }

    /**
     * Set totalAmount
     *
     * @param float $totalAmount
     *
     * @return Deposit
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    /**
     * Get totalAmount
     *
     * @return float
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Set expense
     *
     * @param Expense $expense
     *
     * @return Deposit
     */
    public function setExpense(Expense $expense = null)
    {
        $this->expense = $expense;

        return $this;
    }

    /**
     * Get expense
     *
     * @return \AppBundle\Financial\Entity\Expense
     */
    public function getExpense()
    {
        return $this->expense;
    }

    /**
     * Set chestCount
     *
     * @param \AppBundle\Financial\Entity\ChestCount $chestCount
     *
     * @return Deposit
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
     * Set affiliateCode
     *
     * @param string $affiliateCode
     *
     * @return Deposit
     */
    public function setAffiliateCode($affiliateCode)
    {
        $this->affiliateCode = $affiliateCode;

        return $this;
    }

    /**
     * Get affiliateCode
     *
     * @return string
     */
    public function getAffiliateCode()
    {
        return $this->affiliateCode;
    }


    /**
     * Set sousType
     *
     * @param string $sousType
     *
     * @return Deposit
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
