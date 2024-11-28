<?php

namespace AppBundle\Financial\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\ToolBox\Traits\TimestampableTrait;

/**
 * FinancialRevenue
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\FinancialRevenueRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class FinancialRevenue
{
    use SynchronizedFlagTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;
    use TimestampableTrait;
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
     * @ORM\Column(name="amount", type="float")
     */
    private $amount;

    /**
     * @var float
     *
     * @ORM\Column(name="net_ht", type="float", nullable=true, options={"default" : 0})
     */
    private $netHT = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="net_ttc", type="float", nullable=true, options={"default" : 0})
     */
    private $netTTC = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="brut_ttc", type="float", nullable=true, options={"default" : 0})
     */
    private $brutTTC = 0;


    /**
     * @var float
     *
     * @ORM\Column(name="br", type="float", nullable=true, options={"default" : 0})
     */
    private $br = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="br_ht", type="float", nullable=true, options={"default" : 0})
     */
    private $brHt = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="discount", type="float", nullable=true, options={"default" : 0})
     */
    private $discount = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="brut_ht", type="float", nullable=true, options={"default" : 0})
     */
    private $brutHT = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="ticket_number", type="float", nullable=true)
     */
    private $ticketNumber = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="ca_VA", type="float", nullable=true, options={"default" : 0})
     */
    private $caVA = 0;

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
     * @return FinancialRevenue
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
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return FinancialRevenue
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
     * Set netHT
     *
     * @param float $netHT
     *
     * @return FinancialRevenue
     */
    public function setNetHT($netHT)
    {
        $this->netHT = $netHT;

        return $this;
    }

    /**
     * Get netHT
     *
     * @return float
     */
    public function getNetHT()
    {
        return $this->netHT;
    }

    /**
     * Set netTTC
     *
     * @param float $netTTC
     *
     * @return FinancialRevenue
     */
    public function setNetTTC($netTTC)
    {
        $this->netTTC = $netTTC;

        return $this;
    }

    /**
     * Get netTTC
     *
     * @return float
     */
    public function getNetTTC()
    {
        return $this->netTTC;
    }

    /**
     * Set brutTTC
     *
     * @param float $brutTTC
     *
     * @return FinancialRevenue
     */
    public function setBrutTTC($brutTTC)
    {
        $this->brutTTC = $brutTTC;

        return $this;
    }

    /**
     * Get brutTTC
     *
     * @return float
     */
    public function getBrutTTC()
    {
        return $this->brutTTC;
    }

    /**
     * Set br
     *
     * @param float $br
     *
     * @return FinancialRevenue
     */
    public function setBr($br)
    {
        $this->br = $br;

        return $this;
    }

    /**
     * Get br
     *
     * @return float
     */
    public function getBr()
    {
        return $this->br;
    }

    /**
     * Set br
     *
     * @param float $brHt
     *
     * @return FinancialRevenue
     */
    public function setBrHt($brHt)
    {
        $this->brHt = $brHt;

        return $this;
    }

    /**
     * Get brHt
     *
     * @return float
     */
    public function getBrHt()
    {
        return $this->brHt;
    }

    /**
     * Set discount
     *
     * @param float $discount
     *
     * @return FinancialRevenue
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount
     *
     * @return float
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set brutHT
     *
     * @param float $brutHT
     *
     * @return FinancialRevenue
     */
    public function setBrutHT($brutHT)
    {
        $this->brutHT = $brutHT;

        return $this;
    }

    /**
     * Get brutHT
     *
     * @return float
     */
    public function getBrutHT()
    {
        return $this->brutHT;
    }

    /**
     * @return float
     */
    public function getTicketNumber()
    {
        return $this->ticketNumber;
    }

    /**
     * @param float $ticketNumber
     */
    public function setTicketNumber($ticketNumber)
    {
        $this->ticketNumber = $ticketNumber;
    }

    /**
     * @return float
     */
    public function getCaVA()
    {
        return $this->caVA;
    }

    /**
     * @param float $caVA
     */
    public function setCaVA($caVA)
    {
        $this->caVA = $caVA;
    }



}
