<?php

namespace AppBundle\Merchandise\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProductPurchased
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\ProductPurchasedRepository")
 */
class ProductPurchased extends Product
{

    const RAW_MATERIAL_TYPE = "raw";
    const CONSUMABLE = "consumable";
    const GADGET = "gadget";

    const ACTIVE = "active";
    const INACTIVE = "inactive";
    const TO_INACTIVE = "toInactive";

    public static $units = [
        'units.colis' => 'units.colis',
        'units.piece' => 'units.piece',
        'units.kilo' => 'units.kilo',
        'units.litre' => 'units.litre',
        'units.sachet' => 'units.sachet',
        'units.barquette' => 'units.barquette',
        'units.gramme' => 'units.gramme',
        'centilitre' => 'units.centilitre',
        'units.seau' => 'units.seau',
        'units.pile' => 'units.pile',
        'units.bidon' => 'units.bidon',
        'units.portion' => 'units.portion',
    ];

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
     * @ORM\OneToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased")
     */
    private $primaryItem;

    /**
     * @var ProductPurchased
     * @ORM\OneToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased")
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
     * @ORM\ManyToMany(targetEntity="AppBundle\Merchandise\Entity\Supplier",inversedBy="products")
     */
    private $suppliers;

    /**
     * @var UnitNeedProducts
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\UnitNeedProducts",inversedBy="products")
     */
    private $unitNeed;

    /**
     * @var string
     * @ORM\Column(name="id_item_inv", type="string", nullable=TRUE)
     */
    private $idItemInv;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductCategories", inversedBy="products")
     * @ORM\JoinColumn(name="product_category_id", referencedColumnName="id", nullable=false)
     */
    protected $productCategory;


    /**
     * @var boolean
     *
     * @ORM\Column(name="reusable", type="boolean")
     */
    private $reusable;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_date_cmd", type="date", nullable=true)
     */
    private $startDateCmd;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date_cmd", type="date", nullable=true)
     */
    private $endDateCmd;

    public function __construct()
    {
        parent::__construct();
        $this->suppliers = new ArrayCollection();
    }



    /**
     * Get reusable
     *
     * @return boolean
     */
    public function getReusable()
    {
        return $this->reusable;
    }

    /**
     * @param bool $reusable
     */
    public function setReusable($reusable)
    {
        $this->reusable = $reusable;
    }


    /**
     * Set type
     *
     * @param string $type
     *
     * @return ProductPurchased
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /*public*
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
     * @return ProductPurchased
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
     * @return ProductPurchased
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
     * Get supplier
     *
     * @return \AppBundle\Merchandise\Entity\Supplier
     */
    public function getSuppliers()
    {
        return $this->suppliers;
    }

    public function setSuppliers($suppliers)
    {
        $this->suppliers = $suppliers;

        return $this;
    }

    /**
     * Add supplier
     *
     * @param  Supplier $supplier
     * @return ProductPurchased
     */
    public function addSupplier(Supplier $supplier)
    {
        $this->suppliers[] = $supplier;

        return $this;
    }

    /**
     * Remove supplier
     *
     * @param Supplier $supplier
     */
    public function removeSupplier(Supplier $supplier)
    {
        $this->suppliers->removeElement($supplier);
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
     * @return UnitNeedProducts
     */
    public function getUnitNeed()
    {
        return $this->unitNeed;
    }

    /**
     * @param $unitNeed
     * @return $this
     */
    public function setUnitNeed($unitNeed)
    {
        $this->unitNeed = $unitNeed;

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
     * @param $variation
     */
    public function modifyStock($variation)
    {
        parent::modifyStock($variation);

        if ($this->unitNeed !== null) {
            $this->unitNeed->modifyStock($variation);
        }
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
     * Set status
     *
     * @param string $status
     *
     * @return ProductPurchased
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
     * @return ProductPurchased
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
     * @return ProductPurchased
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
     * @return $this
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
     * @return ProductPurchased
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
     * @param  \AppBundle\Merchandise\Entity\ProductPurchased $primaryItem
     * @return ProductPurchased
     */
    public function setPrimaryItem(\AppBundle\Merchandise\Entity\ProductPurchased $primaryItem = null)
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
     * @param \AppBundle\Merchandise\Entity\ProductPurchased $secondaryItem
     *
     * @return ProductPurchased
     */
    public function setSecondaryItem(\AppBundle\Merchandise\Entity\ProductPurchased $secondaryItem = null)
    {
        $this->secondaryItem = $secondaryItem;

        return $this;
    }

    /**
     * Get secondaryItem
     *
     * @return \AppBundle\Merchandise\Entity\ProductPurchased
     */
    public function getSecondaryItem()
    {
        return $this->secondaryItem;
    }

    public function getBuyingCostInUsageUnit()
    {
        return $this->buyingCost / ($this->inventoryQty * $this->usageQty);
    }

    public function getUsageBuyingCost()
    {
        return ($this->getBuyingCost() / ($this->getInventoryQty() * $this->getUsageQty()));
    }

    /**
     * Get startDateCmd
     *
     * @return \DateTime
     */
    public function getStartDateCmd()
    {
        return $this->startDateCmd;
    }

    /**
     * set startDateCmd
     *
     * @param \DateTime $startDateCmd
     *
     * @return  ProductPurchased
     */
    public function setStartDateCmd($startDateCmd)
    {
        $this->startDateCmd = $startDateCmd;

        return $this;
    }

    /**
     *  Get endDateCmd
     *
     * @return \DateTime
     */
    public function getEndDateCmd()
    {
        return $this->endDateCmd;
    }

    /**
     * set endDateCmd
     *
     * @param \DateTime $endDateCmd
     *
     * @return  ProductPurchased
     */
    public function setEndDateCmd($endDateCmd)
    {
        $this->endDateCmd = $endDateCmd;

        return $this;
    }

}
