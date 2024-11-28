<?php

namespace AppBundle\Financial\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * TicketIntervention
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class TicketIntervention
{

    use ImportIdTrait;

    const DELETE_ACTION = "Deletion";
    const ABONDON_ACTION = "Abondon commande";
    const DECREASE_QUANTITY_ACTION = 'Réduction de quantité';
    const DELETE_PAYMENT_ACTION = "Suppression paiement";

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
     *
     * @ORM\Column(name="action", type="string", length=50,nullable=true)
     */
    private $action;

    /**
     * @var string
     *
     * @ORM\Column(name="managerID", type="string", length=10,nullable=true)
     */
    private $managerID;

    /**
     * @var string
     *
     * @ORM\Column(name="managerName", type="string", length=100,nullable=true)
     */
    private $managerName;

    /**
     * @var string
     *
     * @ORM\Column(name="itemId", type="string", length=30,nullable=true)
     */
    private $itemId;

    /**
     * @var string
     *
     * @ORM\Column(name="itemLabel", type="string", length=50,nullable=true)
     */
    private $itemLabel;

    /**
     * @var float
     *
     * @ORM\Column(name="itemPrice", type="float",nullable=true)
     */
    private $itemPrice;

    /**
     * @var string
     *
     * @ORM\Column(name="itemPLU", type="string", length=10,nullable=true)
     */
    private $itemPLU;

    /**
     * @var integer
     *
     * @ORM\Column(name="itemQty", type="integer",nullable=true)
     */
    private $itemQty;

    /**
     * @var float
     *
     * @ORM\Column(name="itemAmount", type="float",nullable=true)
     */
    private $itemAmount;

    /**
     * @var string
     *
     * @ORM\Column(name="itemCode", type="string", length=20,nullable=true)
     */
    private $itemCode;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime",nullable=true)
     */
    private $date;

    /**
     * @var boolean
     *
     * @ORM\Column(name="postTotal", type="boolean",nullable=true)
     */
    private $postTotal;

    /**
     * @var Ticket
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\Ticket",inversedBy="interventions", cascade="persist")
     */
    private $ticket;

    /**
     * @var TicketInterventionSub
     * @ORM\OneToMany(targetEntity="TicketInterventionSub",mappedBy="intervention",cascade={"persist", "remove"})
     */
    private $subs;

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
     * Set action
     *
     * @param string $action
     *
     * @return TicketIntervention
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set managerID
     *
     * @param string $managerID
     *
     * @return TicketIntervention
     */
    public function setManagerID($managerID)
    {
        $this->managerID = $managerID;

        return $this;
    }

    /**
     * Get managerID
     *
     * @return string
     */
    public function getManagerID()
    {
        return $this->managerID;
    }

    /**
     * Set managerName
     *
     * @param string $managerName
     *
     * @return TicketIntervention
     */
    public function setManagerName($managerName)
    {
        $this->managerName = $managerName;

        return $this;
    }

    /**
     * Get managerName
     *
     * @return string
     */
    public function getManagerName()
    {
        return $this->managerName;
    }

    /**
     * Set itemId
     *
     * @param string $itemId
     *
     * @return TicketIntervention
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return string
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set itemLabel
     *
     * @param string $itemLabel
     *
     * @return TicketIntervention
     */
    public function setItemLabel($itemLabel)
    {
        $this->itemLabel = $itemLabel;

        return $this;
    }

    /**
     * Get itemLabel
     *
     * @return string
     */
    public function getItemLabel()
    {
        return $this->itemLabel;
    }

    /**
     * Set itemPrice
     *
     * @param float $itemPrice
     *
     * @return TicketIntervention
     */
    public function setItemPrice($itemPrice)
    {
        $this->itemPrice = $itemPrice;

        return $this;
    }

    /**
     * Get itemPrice
     *
     * @return float
     */
    public function getItemPrice()
    {
        return $this->itemPrice;
    }

    /**
     * Set itemPLU
     *
     * @param string $itemPLU
     *
     * @return TicketIntervention
     */
    public function setItemPLU($itemPLU)
    {
        $this->itemPLU = $itemPLU;

        return $this;
    }

    /**
     * Get itemPLU
     *
     * @return string
     */
    public function getItemPLU()
    {
        return $this->itemPLU;
    }

    /**
     * Set itemQty
     *
     * @param integer $itemQty
     *
     * @return TicketIntervention
     */
    public function setItemQty($itemQty)
    {
        $this->itemQty = $itemQty;

        return $this;
    }

    /**
     * Get itemQty
     *
     * @return integer
     */
    public function getItemQty()
    {
        return $this->itemQty;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return TicketIntervention
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
        if (!is_null($format)) {
            return $this->date->format($format);
        }

        return $this->date;
    }

    /**
     * Set postTotal
     *
     * @param boolean $postTotal
     *
     * @return TicketIntervention
     */
    public function setPostTotal($postTotal)
    {
        $this->postTotal = $postTotal;

        return $this;
    }

    /**
     * Get postTotal
     *
     * @return boolean
     */
    public function getPostTotal()
    {
        return $this->postTotal;
    }

    /**
     * Set ticket
     *
     * @param \AppBundle\Financial\Entity\Ticket $ticket
     *
     * @return TicketIntervention
     */
    public function setTicket(\AppBundle\Financial\Entity\Ticket $ticket = null)
    {
        $this->ticket = $ticket;

        return $this;
    }

    /**
     * Get ticket
     *
     * @return \AppBundle\Financial\Entity\Ticket
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * Set itemAmount
     *
     * @param float $itemAmount
     *
     * @return TicketIntervention
     */
    public function setItemAmount($itemAmount)
    {
        $this->itemAmount = $itemAmount;

        return $this;
    }

    /**
     * Get itemAmount
     *
     * @return float
     */
    public function getItemAmount()
    {
        return $this->itemAmount;
    }

    /**
     * Set itemCode
     *
     * @param string $itemCode
     *
     * @return TicketIntervention
     */
    public function setItemCode($itemCode)
    {
        $this->itemCode = $itemCode;

        return $this;
    }

    /**
     * Get itemCode
     *
     * @return string
     */
    public function getItemCode()
    {
        return $this->itemCode;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->subs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add sub
     *
     * @param \AppBundle\Financial\Entity\TicketInterventionSub $sub
     *
     * @return TicketIntervention
     */
    public function addSub(\AppBundle\Financial\Entity\TicketInterventionSub $sub)
    {
        $this->subs[] = $sub;

        return $this;
    }

    /**
     * Remove sub
     *
     * @param \AppBundle\Financial\Entity\TicketInterventionSub $sub
     */
    public function removeSub(\AppBundle\Financial\Entity\TicketInterventionSub $sub)
    {
        $this->subs->removeElement($sub);
    }

    /**
     * Get subs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSubs()
    {
        return $this->subs;
    }
}
