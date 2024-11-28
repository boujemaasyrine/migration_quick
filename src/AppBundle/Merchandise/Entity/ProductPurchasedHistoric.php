<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 27/05/2016
 * Time: 11:49
 */

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ProductPurchasedHistoric
 *
 * @package AppBundle\Merchandise\Entity
 *
 * @Entity()
 * @Table()
 * @HasLifecycleCallbacks()
 */
class ProductPurchasedHistoric
{

    use TimestampableTrait;
    use OriginRestaurantTrait;

    const INVENTORY_UNIT = 'inventory_unit';
    const EXPED_UNIT = 'exped_unit';
    const USE_UNIT = 'use_unit';

    static $unitsLabel = [
        "COLIS" => 'units.colis',
        "PIECE" => 'units.piece',
        "KILO" => 'units.kilo',
        "LITRE" => 'units.litre',
        "SACHET" => 'units.sachet',
        "BARQUETTE" => 'units.barquette',
        "GRAMME" => 'units.gramme',
        "CENTILITRE" => 'units.centilitre',
        "SEAU" => 'units.seau',
        "PILE" => 'units.pile',
        "BIDON" => 'units.bidon',
        "PORTION" => 'units.portion',
    ];

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="start_date", type="datetime", nullable=TRUE)
     */
    protected $startDate;

    /**
     * @var integer
     *
     * @ORM\Column(name="original_id",type="integer",nullable=true)
     */
    protected $originalID;

    /**
     * @var string
     * @ORM\Column(name="name",type="string",length=100)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="reference", type="string", length=255, nullable=true)
     */
    protected $reference;

    /**
     * @var float
     *
     * @ORM\Column(name="stock_current_qty", type="float")
     */
    protected $stockCurrentQty = 0;

    /**
     * @var boolean
     * @ORM\Column(name="active", type="boolean", options={"default"=true})
     */
    protected $active;

    /**
     * @var
     * @ORM\Column(name="global_product_id",type="integer",nullable=true)
     */
    private $globalProductID;

    /**
     * @var string
     * @ORM\Column(name="type",type="string",length=10, nullable=true)
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(name="external_id",type="string", nullable=true)
     */
    protected $externalId;

    /**
     * @var string
     * @ORM\Column(name="storage_condition",type="string",length=10,nullable=true)
     */
    private $storageCondition;

    /**
     * @var float
     * @ORM\Column(name="buying_cost",type="float",nullable=true)
     */
    private $buyingCost;

    /**
     * @var string
     * @ORM\Column(name="status",type="string",length=15, nullable=true)
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="deactivation_date", type="date", nullable=true)
     */
    private $deactivationDate;

    /**
     * @var string
     * @ORM\Column(name="dlc", type="date",nullable=true)
     */
    private $dlc;

    /**
     * @var ProductPurchased
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased")
     */
    private $primaryItem;

    /**
     * @var ProductPurchasedHistoric
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased")
     */
    private $secondaryItem;

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
     * @var Supplier
     * @ORM\ManyToMany(targetEntity="AppBundle\Merchandise\Entity\Supplier")
     */
    private $supplier;

    /**
     * @var string
     * @ORM\Column(name="id_item_inv", type="string", nullable=TRUE)
     */
    private $idItemInv;

    /**
     * @ORM\ManyToOne(targetEntity="ProductCategories", inversedBy="products")
     */
    protected $productCategory;

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
     * Set name
     *
     * @param string $name
     *
     * @return ProductPurchasedHistoric
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set reference
     *
     * @param string $reference
     *
     * @return ProductPurchasedHistoric
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
     * Set stockCurrentQty
     *
     * @param float $stockCurrentQty
     *
     * @return ProductPurchasedHistoric
     */
    public function setStockCurrentQty($stockCurrentQty)
    {
        $stockCurrentQty = str_replace(',', '.', $stockCurrentQty);
        $this->stockCurrentQty = $stockCurrentQty;

        return $this;
    }

    /**
     * Get stockCurrentQty
     *
     * @return float
     */
    public function getStockCurrentQty()
    {
        return $this->stockCurrentQty;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->supplier=new ArrayCollection();
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param $active
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    public function __toString()
    {
        return sprintf("%s - %s", $this->getId(), $this->getName());
    }

    public function modifyStock($variation)
    {
        $currentStock = ($this->getStockCurrentQty() !== null) ? $this->getStockCurrentQty() : 0;
        $this->setStockCurrentQty($currentStock + $variation);
    }


    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @return array
     */
    public static function getUnitsLabel()
    {
        return self::$unitsLabel;
    }

    /**
     * @param array $unitsLabel
     * @return ProductPurchasedHistoric
     */
    public static function setUnitsLabel($unitsLabel)
    {
        self::$unitsLabel = $unitsLabel;
    }

    /**
     * @return mixed
     */
    public function getGlobalProductID()
    {
        return $this->globalProductID;
    }

    /**
     * @param mixed $globalProductID
     * @return ProductPurchasedHistoric
     */
    public function setGlobalProductID($globalProductID)
    {
        $this->globalProductID = $globalProductID;

        return $this;
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
     * Set storageCondition
     *
     * @param string $storageCondition
     *
     * @return ProductPurchasedHistoric
     */
    public function setStorageCondition($storageCondition)
    {
        $this->storageCondition = $storageCondition;

        return $this;
    }

    /**
     * Get storageCondition
     *
     * @return string
     */
    public function getStorageCondition()
    {
        return $this->storageCondition;
    }

    /**
     * Set dlc
     *
     * @param \DateTime $dlc
     *
     * @return ProductPurchasedHistoric
     */
    public function setDlc($dlc)
    {
        $this->dlc = $dlc;

        return $this;
    }

    /**
     * Get dlc
     *
     * @return \DateTime
     */
    public function getDlc()
    {
        return $this->dlc;
    }

    /**
     * Set supplier
     *
     * @param Supplier $supplier
     *
     * @return ProductPurchasedHistoric
     */
    public function setSupplier(Supplier $supplier = null)
    {
        $this->supplier = $supplier;

        return $this;
    }

    /**
     * Get supplier
     *
     * @return Supplier
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * Add supplier
     *
     * @param  \AppBundle\Merchandise\Entity\Supplier $supplier
     * @return ProductPurchasedHistoric
     */
    public function addSupplier(\AppBundle\Merchandise\Entity\Supplier $supplier)
    {
        $this->supplier[] = $supplier;

        return $this;
    }

    /**
     * Remove supplier
     *
     * @param \AppBundle\Merchandise\Entity\Supplier $supplier
     */
    public function removeSupplier(\AppBundle\Merchandise\Entity\Supplier $supplier)
    {
        $this->supplier->removeElement($supplier);
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
     * Set status
     *
     * @param string $status
     *
     * @return ProductPurchasedHistoric
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
     * @return string
     */
    public function getIdItemInv()
    {
        return $this->idItemInv;
    }

    /**
     * @param string $idItemInv
     * @return ProductPurchasedHistoric
     */
    public function setIdItemInv($idItemInv)
    {
        $this->idItemInv = $idItemInv;

        return $this;
    }

    /**
     * @return ProductCategories
     */
    public function getProductCategory()
    {
        return $this->productCategory;
    }

    /**
     * @param mixed $productCategory
     * @return ProductPurchasedHistoric
     */
    public function setProductCategory($productCategory)
    {
        $this->productCategory = $productCategory;

        return $this;
    }

    /**
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @param $externalId
     * @return ProductPurchasedHistoric
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * Set deactivationDate
     *
     * @param \DateTime $deactivationDate
     *
     * @return ProductPurchasedHistoric
     */
    public function setDeactivationDate($deactivationDate)
    {
        $this->deactivationDate = $deactivationDate;

        return $this;
    }

    /**
     * Get deactivationDate
     *
     * @return \DateTime
     */
    public function getDeactivationDate()
    {
        return $this->deactivationDate;
    }

    /**
     * Set primaryItem
     *
     * @param  ProductPurchased $primaryItem
     * @return ProductPurchased
     */
    public function setPrimaryItem(ProductPurchased $primaryItem = null)
    {
        $this->primaryItem = $primaryItem;

        return $this;
    }

    /**
     * Get primaryItem
     *
     * @return \AppBundle\Merchandise\Entity\ProductPurchased
     */
    public function getPrimaryItem()
    {
        return $this->primaryItem;
    }

    /**
     * Set secondaryItem
     *
     * @param ProductPurchased $secondaryItem
     *
     * @return ProductPurchasedHistoric
     */
    public function setSecondaryItem(ProductPurchased $secondaryItem = null)
    {
        $this->secondaryItem = $secondaryItem;

        return $this;
    }

    /**
     * Get secondaryItem
     *
     * @return \AppBundle\Merchandise\Entity\ProductPurchasedHistoric
     */
    public function getSecondaryItem()
    {
        return $this->secondaryItem;
    }

    /**
     * Set originalID
     *
     * @param integer $originalID
     *
     * @return ProductPurchasedHistoric
     */
    public function setOriginalID($originalID)
    {
        $this->originalID = $originalID;

        return $this;
    }

    /**
     * Get originalID
     *
     * @return integer
     */
    public function getOriginalID()
    {
        return $this->originalID;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     * @return ProductPurchasedHistoric
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }
}
