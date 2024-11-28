<?php

namespace AppBundle\Financial\Entity;

use AppBundle\Merchandise\Entity\ProductSold;
use Doctrine\ORM\Mapping as ORM;

/**
 * TicketLine
 *
 * @ORM\Table()
 * @ORM\Entity()
 */
class TicketLineTemp
{

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
     * @var boolean
     * @ORM\Column(name="is_discount", type="boolean", nullable=true)
     */
    private $isDiscount;

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

    /**
     * @var string
     * @ORM\Column(name="ticket_id", type="string", nullable=TRUE)
     */
    private $ticket;

    /**
     * @var bool
     * @ORM\Column(name="mvmt_recorded", type="boolean", nullable=TRUE)
     */
    private $mvmtRecorded = false;

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
     * @return TicketLineTemp
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
     * @return TicketLineTemp
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
     * @return TicketLineTemp
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
     * @return TicketLineTemp
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
     * @return TicketLineTemp
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
     * @return TicketLineTemp
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
     * @return TicketLineTemp
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
     * @return TicketLineTemp
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
     * @return TicketLineTemp
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
     * @return TicketLineTemp
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
     * @return TicketLineTemp
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
     * @return TicketLineTemp
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
     * @return TicketLineTemp
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
     * @return TicketLineTemp
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
     * Set product
     *
     * @param integer $product
     *
     * @return TicketLineTemp
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
     * @return TicketLineTemp
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
     * @return mixed
     */
    public function getIsDiscount()
    {
        return $this->isDiscount;
    }

    /**
     * @param mixed $isDiscount
     * @return TicketLineTemp
     */
    public function setIsDiscount($isDiscount)
    {
        $this->isDiscount = $isDiscount;

        return $this;
    }

    /**
     * @return string
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * @param string $ticket
     * @return TicketLineTemp
     */
    public function setTicket($ticket)
    {
        $this->ticket = $ticket;

        return $this;
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
     * @return TicketLineTemp
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
     * @param $discountId
     * @return $this
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
     * @param $discountCode
     * @return $this
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
     * @param $discountLabel
     * @return $this
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
     * @param $discountHt
     * @return $this
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
     * @param $discountTva
     * @return $this
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
     * @param $discountTtc
     * @return $this
     */
    public function setDiscountTtc($discountTtc)
    {
        $this->discountTtc = $discountTtc;

        return $this;
    }
}
