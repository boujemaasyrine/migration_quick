<?php

namespace AppBundle\Financial\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * TicketInterventionSub
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class TicketInterventionSub
{
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
     * @ORM\Column(name="subId", type="integer",nullable=true)
     */
    private $subId;

    /**
     * @var string
     *
     * @ORM\Column(name="subLabel", type="string", length=50,nullable=true)
     */
    private $subLabel;

    /**
     * @var float
     *
     * @ORM\Column(name="subPrice", type="float",nullable=true)
     */
    private $subPrice;

    /**
     * @var string
     *
     * @ORM\Column(name="subPLU", type="string", length=10,nullable=true)
     */
    private $subPLU;

    /**
     * @var integer
     *
     * @ORM\Column(name="subQty", type="integer",nullable=true)
     */
    private $subQty;

    /**
     * @var TicketIntervention
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\TicketIntervention",inversedBy="subs", cascade="persist")
     */
    private $intervention;


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
     * Set subId
     *
     * @param integer $subId
     *
     * @return TicketInterventionSub
     */
    public function setSubId($subId)
    {
        $this->subId = $subId;

        return $this;
    }

    /**
     * Get subId
     *
     * @return integer
     */
    public function getSubId()
    {
        return $this->subId;
    }

    /**
     * Set subLabel
     *
     * @param string $subLabel
     *
     * @return TicketInterventionSub
     */
    public function setSubLabel($subLabel)
    {
        $this->subLabel = $subLabel;

        return $this;
    }

    /**
     * Get subLabel
     *
     * @return string
     */
    public function getSubLabel()
    {
        return $this->subLabel;
    }

    /**
     * Set subPrice
     *
     * @param float $subPrice
     *
     * @return TicketInterventionSub
     */
    public function setSubPrice($subPrice)
    {
        $this->subPrice = $subPrice;

        return $this;
    }

    /**
     * Get subPrice
     *
     * @return float
     */
    public function getSubPrice()
    {
        return $this->subPrice;
    }

    /**
     * Set subPLU
     *
     * @param string $subPLU
     *
     * @return TicketInterventionSub
     */
    public function setSubPLU($subPLU)
    {
        $this->subPLU = $subPLU;

        return $this;
    }

    /**
     * Get subPLU
     *
     * @return string
     */
    public function getSubPLU()
    {
        return $this->subPLU;
    }

    /**
     * Set subQty
     *
     * @param integer $subQty
     *
     * @return TicketInterventionSub
     */
    public function setSubQty($subQty)
    {
        $this->subQty = $subQty;

        return $this;
    }

    /**
     * Get subQty
     *
     * @return integer
     */
    public function getSubQty()
    {
        return $this->subQty;
    }

    /**
     * Set intervention
     *
     * @param \AppBundle\Financial\Entity\TicketIntervention $intervention
     *
     * @return TicketInterventionSub
     */
    public function setIntervention(\AppBundle\Financial\Entity\TicketIntervention $intervention = null)
    {
        $this->intervention = $intervention;

        return $this;
    }

    /**
     * Get intervention
     *
     * @return \AppBundle\Financial\Entity\TicketIntervention
     */
    public function getIntervention()
    {
        return $this->intervention;
    }
}
