<?php

namespace AppBundle\Financial\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * TicketLine
 *
 * @ORM\Table(indexes={@ORM\Index(name="ticket_line_index_plu",columns={"plu"}),@ORM\Index(name="ticket_line_restaurant_date_index",columns={"origin_restaurant_id","date"}),@ORM\Index(name="ticket_line_restaurant_dateinterval_index",columns={"origin_restaurant_id","startDate","endDate"})})
 * @ORM\Entity(repositoryClass="AppBundle\Financial\Repository\TicketLineRepository")
 */
class TicketLine
{

    use ImportIdTrait;
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="decimal")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="line", type="integer",nullable=true)
     */
    private $line;

    /**
     * @var integer
     *
     * @ORM\Column(name="qty", type="integer",nullable=true)
     */
    private $qty;

    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float",nullable=true)
     */
    private $price;

    /**
     * @var float
     *
     * @ORM\Column(name="totalHT", type="float",nullable=true)
     */
    private $totalHT;

    /**
     * @var float
     *
     * @ORM\Column(name="totalTVA", type="float",nullable=true)
     */
    private $totalTVA;

    /**
     * Total ttc discount are counted.
     *
     * @var float
     *
     * @ORM\Column(name="totalTTC", type="float",nullable=true)
     */
    private $totalTTC;

    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=100,nullable=true)
     */
    private $category;

    /**
     * @var integer
     *
     * @ORM\Column(name="division", type="integer",nullable=true)
     */
    private $division;

    /**
     * @var integer
     *
     * @ORM\Column(name="product",type="integer",nullable=true)
     */
    private $product;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", length=100,nullable=true)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=100,nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="plu", type="string", length=10,nullable=true)
     */
    private $plu;

    /**
     * @var boolean
     *
     * @ORM\Column(name="combo", type="boolean",nullable=true,nullable=true)
     */
    private $combo;

    /**
     * @var boolean
     *
     * @ORM\Column(name="composition", type="boolean",nullable=true,nullable=true)
     */
    private $composition;

    /**
     * @var integer
     *
     * @ORM\Column(name="parentLine", type="integer",nullable=true,nullable=true)
     */
    private $parentLine;

    /**
     * @var float
     *
     * @ORM\Column(name="tva", type="float",nullable=true)
     */
    private $tva;

    /**
     * @var Ticket
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\Ticket",inversedBy="lines",cascade={"persist"})
     */
    private $ticket;

    /**
     * @var CashboxDiscountContainer
     * @ORM\ManyToOne(targetEntity="AppBundle\Financial\Entity\CashboxDiscountContainer", inversedBy="ticketLines")
     */
    private $discountContainer;

    /**
     * @var boolean
     * @ORM\Column(name="is_discount", type="boolean", nullable=true)
     */
    private $isDiscount;

    /**
     * @var float
     * @ORM\Column(name="revenue_price",type="float",nullable=true)
     */
    private $revenuePrice;

    /**
     * @var bool
     * @ORM\Column(name="mvmt_recorded", type="boolean", nullable=TRUE)
     */
    private $mvmtRecorded = false;

    /**
     * @var string
     * @ORM\Column(name="discount_id", type="string", nullable=TRUE)
     */
    private $discountId;

    /**
     * @var string
     * @ORM\Column(name="discount_code", type="string", nullable=TRUE)
     */
    private $discountCode;

    /**
     * @var string
     * @ORM\Column(name="discount_label", type="string", nullable=TRUE)
     */
    private $discountLabel;

    /**
     * @var float
     * @ORM\Column(name="discount_ht", type="float", nullable=TRUE)
     */
    private $discountHt;

    /**
     * @var float
     * @ORM\Column(name="discount_tva", type="float", nullable=TRUE)
     */
    private $discountTva;

    /**
     * @var float
     * @ORM\Column(name="discount_ttc", type="float", nullable=TRUE)
     */
    private $discountTtc;


/** attributes for osql request optimisation */
/**********************************************/

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startDate", type="datetime",nullable=true)
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endDate", type="datetime",nullable=true)
     */
    private $endDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer",nullable=true)
     */
    private $status;

    /**
     * @var \DateTime
     * @ORM\Column(name="date",type="date",nullable=true)
     */
    private $date;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default" = false})
     */
    private $countedCanceled = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="origin_restaurant_id", type="integer",nullable=true)
     */
    private $originRestaurantId;


    /**
     * @var boolean
     *
     * @ORM\Column(name="flag_va", type="boolean",nullable=true,options={"default" = false})
     */
    private $flagVA;

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
     * Set line
     *
     * @param integer $line
     *
     * @return TicketLine
     */
    public function setLine($line)
    {
        $this->line = $line;

        return $this;
    }

    /**
     * Get line
     *
     * @return integer
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Set qty
     *
     * @param integer $qty
     *
     * @return TicketLine
     */
    public function setQty($qty)
    {
        $this->qty = $qty;

        return $this;
    }

    /**
     * Get qty
     *
     * @return integer
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * Set price
     *
     * @param float $price
     *
     * @return TicketLine
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set totalHT
     *
     * @param float $totalHT
     *
     * @return TicketLine
     */
    public function setTotalHT($totalHT)
    {
        $this->totalHT = $totalHT;

        return $this;
    }

    /**
     * Get totalHT
     *
     * @return float
     */
    public function getTotalHT()
    {
        return $this->totalHT;
    }

    /**
     * Set totalTVA
     *
     * @param float $totalTVA
     *
     * @return TicketLine
     */
    public function setTotalTVA($totalTVA)
    {
        $this->totalTVA = $totalTVA;

        return $this;
    }

    /**
     * Get totalTVA
     *
     * @return float
     */
    public function getTotalTVA()
    {
        return $this->totalTVA;
    }

    /**
     * Set totalTTC
     *
     * @param float $totalTTC
     *
     * @return TicketLine
     */
    public function setTotalTTC($totalTTC)
    {
        $this->totalTTC = $totalTTC;

        return $this;
    }

    /**
     * Get totalTTC
     *
     * @return float
     */
    public function getTotalTTC()
    {
        return $this->totalTTC;
    }

    /**
     * Set category
     *
     * @param string $category
     *
     * @return TicketLine
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set division
     *
     * @param integer $division
     *
     * @return TicketLine
     */
    public function setDivision($division)
    {
        $this->division = $division;

        return $this;
    }

    /**
     * Get division
     *
     * @return integer
     */
    public function getDivision()
    {
        return $this->division;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return TicketLine
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set plu
     *
     * @param string $plu
     *
     * @return TicketLine
     */
    public function setPlu($plu)
    {
        $this->plu = $plu;

        return $this;
    }

    /**
     * Get plu
     *
     * @return string
     */
    public function getPlu()
    {
        return $this->plu;
    }

    /**
     * Set combo
     *
     * @param boolean $combo
     *
     * @return TicketLine
     */
    public function setCombo($combo)
    {
        $this->combo = $combo;

        return $this;
    }

    /**
     * Get combo
     *
     * @return boolean
     */
    public function getCombo()
    {
        return $this->combo;
    }

    /**
     * Set composition
     *
     * @param boolean $composition
     *
     * @return TicketLine
     */
    public function setComposition($composition)
    {
        $this->composition = $composition;

        return $this;
    }

    /**
     * Get composition
     *
     * @return boolean
     */
    public function getComposition()
    {
        return $this->composition;
    }

    /**
     * Set parentLine
     *
     * @param integer $parentLine
     *
     * @return TicketLine
     */
    public function setParentLine($parentLine)
    {
        $this->parentLine = $parentLine;

        return $this;
    }

    /**
     * Get parentLine
     *
     * @return integer
     */
    public function getParentLine()
    {
        return $this->parentLine;
    }

    /**
     * Set tva
     *
     * @param float $tva
     *
     * @return TicketLine
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
     * Set ticket
     *
     * @param \AppBundle\Financial\Entity\Ticket $ticket
     *
     * @return TicketLine
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
     * Set product
     *
     * @param integer $product
     *
     * @return TicketLine
     */
    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return integer
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return TicketLine
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return CashboxDiscountContainer
     */
    public function getDiscountContainer()
    {
        return $this->discountContainer;
    }

    /**
     * @param CashboxDiscountContainer $discountContainer
     * @return TicketPayment
     */
    public function setDiscountContainer($discountContainer)
    {
        $this->getTicket()->setCounted(true);
        $this->discountContainer = $discountContainer;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsDiscount()
    {
        return $this->isDiscount;
    }

    /**
     * @param mixed $isDiscount
     * @return TicketLine
     */
    public function setIsDiscount($isDiscount)
    {
        $this->isDiscount = $isDiscount;

        return $this;
    }


    /**
     * Set revenuePrice
     *
     * @param float $revenuePrice
     *
     * @return TicketLine
     */
    public function setRevenuePrice($revenuePrice)
    {
        $this->revenuePrice = $revenuePrice;

        return $this;
    }

    /**
     * Get revenuePrice
     *
     * @return float
     */
    public function getRevenuePrice()
    {
        return $this->revenuePrice;
    }

    /**
     * @return boolean
     */
    public function isMvmtRecorded()
    {
        return $this->mvmtRecorded;
    }

    /**
     * @param boolean $mvmtRecorded
     * @return TicketLine
     */
    public function setMvmtRecorded($mvmtRecorded)
    {
        $this->mvmtRecorded = $mvmtRecorded;

        return $this;
    }

    /**
     * @return string
     */
    public function getDiscountId()
    {
        return $this->discountId;
    }

    /**
     * @param string $discountId
     * @return TicketLine
     */
    public function setDiscountId($discountId)
    {
        $this->discountId = $discountId;

        return $this;
    }

    /**
     * @return string
     */
    public function getDiscountCode()
    {
        return $this->discountCode;
    }

    /**
     * @param string $discountCode
     * @return TicketLine
     */
    public function setDiscountCode($discountCode)
    {
        $this->discountCode = $discountCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getDiscountLabel()
    {
        return $this->discountLabel;
    }

    /**
     * @param string $discountLabel
     * @return TicketLine
     */
    public function setDiscountLabel($discountLabel)
    {
        $this->discountLabel = $discountLabel;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountHt()
    {
        return $this->discountHt;
    }

    /**
     * @param float $discountHt
     * @return TicketLine
     */
    public function setDiscountHt($discountHt)
    {
        $this->discountHt = $discountHt;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountTva()
    {
        return $this->discountTva;
    }

    /**
     * @param float $discountTva
     * @return TicketLine
     */
    public function setDiscountTva($discountTva)
    {
        $this->discountTva = $discountTva;

        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountTtc()
    {
        return $this->discountTtc;
    }

    /**
     * @param float $discountTtc
     * @return TicketLine
     */
    public function setDiscountTtc($discountTtc)
    {
        $this->discountTtc = $discountTtc;

        return $this;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Ticket
     */
    public function setDate($date, $format = null)
    {
        if (is_string($date) && $format) {
            $this->date = \DateTime::createFromFormat($format, $date);
        } else {
            $this->date = $date;
        }

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
     * Set origin_restaurant_id
     *
     * @param integer $originRestaurantId
     *
     * @return TicketLine
     */
    public function setOriginRestaurantId($id)
    {
        $this->originRestaurantId = $id;

        return $this;
    }

    /**
     * Get origin_restaurant_id
     *
     * @return integer
     */
    public function getOriginRestaurantId()
    {
        return $this->originRestaurantId;
    }


    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return TicketLine
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return TicketLine
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return TicketLine
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set countedCanceled
     *
     * @param boolean $countedCanceled
     *
     * @return TicketLine
     */
    public function setCountedCanceled($countedCanceled)
    {
        $this->countedCanceled = $countedCanceled;

        return $this;
    }

    /**
     * Get countedCanceled
     *
     * @return boolean
     */
    public function getCountedCanceled()
    {
        return $this->countedCanceled;
    }

    /**
     * @return bool
     */
    public function isFlagVA()
    {
        return $this->flagVA;
    }

    /**
     * @param bool $flagVA
     */
    public function setFlagVA($flagVA)
    {
        $this->flagVA = $flagVA;
    }


}
