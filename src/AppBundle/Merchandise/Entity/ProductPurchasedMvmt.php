<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/06/2016
 * Time: 12:44
 */

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ProductPurchasedMvmt
 *
 * @package AppBundle\Merchandise\Entity
 *
 * @Entity()
 * @Table(indexes={
 * @ORM\Index(name="product_purchased_mvmt_index_date", columns={"date_time"}),
 * @ORM\Index(name="product_purchased_mvmt_index_type", columns={"type"})
 * })
 * @HasLifecycleCallbacks()
 */
class ProductPurchasedMvmt
{
    // Mouvement types
    const PURCHASED_LOSS_TYPE = 'purchased_loss';
    const SOLD_LOSS_TYPE = 'sold_loss';
    const SOLD_TYPE = 'sold';
    const RETURNS_TYPE = 'returns';
    const TRANSFER_IN_TYPE = 'transfer_in';
    const TRANSFER_OUT_TYPE = 'transfer_out';
    const DELIVERY_TYPE = 'delivery';
    const INVENTORY_TYPE = 'inventory';

    // Variation direction
    static $variationDirection = [
        self::PURCHASED_LOSS_TYPE => -1,
        self::SOLD_LOSS_TYPE => -1,
        self::SOLD_TYPE => -1,
        self::RETURNS_TYPE => -1,
        self::TRANSFER_IN_TYPE => 1,
        self::TRANSFER_OUT_TYPE => -1,
        self::DELIVERY_TYPE => 1,
        self::INVENTORY_TYPE => 0,
    ];

    use TimestampableTrait;
    use SynchronizedFlagTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="decimal")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_time", type="datetime")
     */
    protected $dateTime;

    /**
     * @var float
     * @ORM\Column(name="variation", type="float")
     */
    protected $variation = 0;

    /**
     * @var ProductPurchased
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased")
     */
    private $product;
    /**
     * Source Id
     *
     * @var                          bigint
     * @ORM\Column(name="source_id", type="bigint", nullable=TRUE)
     */
    protected $sourceId;

    /**
     * For inventory
     *
     * @var float
     *
     * @ORM\Column(name="stock_qty", type="float", nullable=TRUE)
     */
    protected $stockQty = null;

    /**
     * @var string
     * @ORM\Column(name="type",type="string",length=50, nullable=true)
     */
    private $type;

    /**
     * @var float
     * @ORM\Column(name="buying_cost",type="float",nullable=true)
     */
    private $buyingCost;

    /**
     * @var string
     * @ORM\Column(name="label_unit_exped", type="string", nullable=true)
     */
    private $labelUnitExped;

    /**
     * @var string
     * @ORM\Column(name="label_unit_inventory", type="string", nullable=true)
     */
    private $labelUnitInventory;

    /**
     * @var string
     * @ORM\Column(name="label_unit_usage", type="string", nullable=true)
     */
    private $labelUnitUsage;

    /**
     * @var float
     * @ORM\Column(name="inventory_qty", type="float", nullable=true)
     */
    private $inventoryQty;

    /**
     * @var float
     * @ORM\Column(name="usage_qty", type="float", nullable=true)
     */
    private $usageQty;

    /**
     * @var boolean
     * @ORM\Column(name="deleted", type="boolean", options={"default"=false}, nullable=true)
     */
    protected $deleted = false;

    public function __toString()
    {
        return sprintf("%s", $this->getId());
    }

    /**
     * Constructor
     */
    public function __construct()
    {
    }

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
     * Set type
     *
     * @param string $type
     *
     * @return ProductPurchasedHistoric
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     *
     * public
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getLabelUnitExped()
    {
        return $this->labelUnitExped;
    }

    /**
     * @param $labelUnitExped
     * @return $this
     */
    public function setLabelUnitExped($labelUnitExped)
    {
        $this->labelUnitExped = $labelUnitExped;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabelUnitInventory()
    {
        return $this->labelUnitInventory;
    }

    /**
     * @param $labelUnitInventory
     * @return $this
     */
    public function setLabelUnitInventory($labelUnitInventory)
    {
        $this->labelUnitInventory = $labelUnitInventory;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabelUnitUsage()
    {
        return $this->labelUnitUsage;
    }

    /**
     * @param $labelUnitUsage
     * @return $this
     */
    public function setLabelUnitUsage($labelUnitUsage)
    {
        $this->labelUnitUsage = $labelUnitUsage;

        return $this;
    }

    /**
     * @return float
     */
    public function getInventoryQty()
    {
        return $this->inventoryQty;
    }

    /**
     * @param $inventoryQty
     * @return $this
     */
    public function setInventoryQty($inventoryQty)
    {
        $inventoryQty = str_replace(',', '.', $inventoryQty);
        $this->inventoryQty = $inventoryQty;

        return $this;
    }

    /**
     * @return float
     */
    public function getUsageQty()
    {
        return $this->usageQty;
    }

    /**
     * @param $usageQty
     * @return $this
     */
    public function setUsageQty($usageQty)
    {
        $usageQty = str_replace(',', '.', $usageQty);
        $this->usageQty = $usageQty;

        return $this;
    }

    /**
     * @return float
     */
    public function getBuyingCost()
    {
        return $this->buyingCost;
    }

    /**
     * @param float $buyingCost
     * @return $this
     */
    public function setBuyingCost($buyingCost)
    {
        $buyingCost = str_replace(',', '.', $buyingCost);
        $this->buyingCost = $buyingCost;

        return $this;
    }

    /**
     * @return int
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param int $sourceId
     * @return ProductPurchasedMvmt
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    /**
     * @return float
     */
    public function getStockQty()
    {
        return $this->stockQty;
    }

    /**
     * @param float $stockQty
     * @return ProductPurchasedMvmt
     */
    public function setStockQty($stockQty)
    {
        $this->stockQty = $stockQty;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param mixed $product
     * @return ProductPurchasedMvmt
     */
    public function setProduct(ProductPurchased $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return float
     */
    public function getVariation()
    {
        return $this->variation;
    }

    /**
     * @param float $variation
     * @return ProductPurchasedMvmt
     */
    public function setVariation($variation)
    {
        $this->variation = $variation;

        return $this;
    }

    public function setProductInformations($product)
    {
        /**
         * @var ProductPurchased|ProductPurchasedHistoric $product
         */
        $this->setBuyingCost($product->getBuyingCost());
        $this->setInventoryQty($product->getInventoryQty());
        $this->setUsageQty($product->getUsageQty());
        $this->setLabelUnitExped($product->getLabelUnitExped());
        $this->setLabelUnitInventory($product->getLabelUnitInventory());
        $this->setLabelUnitUsage($product->getLabelUnitUsage());

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateTime($format = null)
    {
        if ($format) {
            return $this->dateTime->format($format);
        }

        return $this->dateTime;
    }

    /**
     * @param \DateTime $dateTime
     * @return ProductPurchasedMvmt
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;

        return $this;
    }


    /**
     * Set deleted
     *
     * @param boolean $deleted
     *
     * @return ProductPurchasedMvmt
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return boolean
     */
    public function getDeleted()
    {
        return $this->deleted;
    }
}
