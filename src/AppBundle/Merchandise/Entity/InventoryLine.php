<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;

/**
 * InventoryLines
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class InventoryLine
{

    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
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
     * @var float
     * @ORM\Column(name="total_inventory_cnt", type="float", nullable=true)
     */
    private $totalInventoryCnt = 0;

    /**
     * @var float
     *
     * @ORM\Column(name="inventory_cnt", type="float", nullable=true)
     */
    private $inventoryCnt;

    /**
     * @var float
     * @ORM\Column(name="usage_cnt", type="float", nullable=true)
     */
    private $usageCnt;

    /**
     * @var float
     * @ORM\Column(name="exped_cnt", type="float", nullable=true)
     */
    private $expedCnt;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\InventorySheet", inversedBy="lines")
     */
    private $inventorySheet;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased")
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @var ProductPurchasedHistoric
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchasedHistoric")
     */
    private $productPurchasedHistoric;

    /**
     * Set id
     *
     * @param integer $id
     *
     * @return InventoryLine
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * @return float
     */
    public function getInventoryCnt()
    {
        return $this->inventoryCnt;
    }

    /**
     * @param float $inventoryCnt
     * @return InventoryLine
     */
    public function setInventoryCnt($inventoryCnt)
    {
        $inventoryCnt = str_replace(',', '.', $inventoryCnt);
        $this->inventoryCnt = $inventoryCnt;

        return $this;
    }

    /**
     * @return float
     */
    public function getUsageCnt()
    {
        return $this->usageCnt;
    }

    /**
     * @param float $usageCnt
     * @return InventoryLine
     */
    public function setUsageCnt($usageCnt)
    {
        $usageCnt = str_replace(',', '.', $usageCnt);
        $this->usageCnt = $usageCnt;

        return $this;
    }

    /**
     * @return float
     */
    public function getExpedCnt()
    {
        return $this->expedCnt;
    }

    /**
     * @param float $expedCnt
     * @return InventoryLine
     */
    public function setExpedCnt($expedCnt)
    {
        $expedCnt = str_replace(',', '.', $expedCnt);
        $this->expedCnt = $expedCnt;

        return $this;
    }

    /**
     * Set inventorySheet
     *
     * @param \AppBundle\Merchandise\Entity\InventorySheet $inventorySheet
     *
     * @return $this
     */
    public function setInventorySheet(InventorySheet $inventorySheet)
    {
        $this->inventorySheet = $inventorySheet;

        return $this;
    }

    /**
     * Get inventorySheet
     *
     * @return InventorySheet
     */
    public function getInventorySheet()
    {
        return $this->inventorySheet;
    }

    /**
     * @param ProductPurchased $product
     * @return $this
     */
    public function setProduct(ProductPurchased $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \AppBundle\Merchandise\Entity\ProductPurchased
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return float
     */
    public function getTotalInventoryCnt()
    {
        return $this->totalInventoryCnt;
    }

    /**
     * @param float $totalInventoryCnt
     * @return InventoryLine
     */
    public function setTotalInventoryCnt($totalInventoryCnt)
    {
        $totalInventoryCnt = str_replace(',', '.', $totalInventoryCnt);
        $this->totalInventoryCnt = $totalInventoryCnt;

        return $this;
    }

    /**
     * @return ProductPurchasedHistoric
     */
    public function getProductPurchasedHistoric()
    {
        return $this->productPurchasedHistoric;
    }

    /**
     * @param ProductPurchasedHistoric $productPurchasedHistoric
     * @return InventoryLine
     */
    public function setProductPurchasedHistoric($productPurchasedHistoric)
    {
        $this->productPurchasedHistoric = $productPurchasedHistoric;

        return $this;
    }

    /**
     * @PrePersist
     * @PreUpdate
     */
    public function refreshTotalInventoryCnt()
    {
        if (is_null($this->getUsageCnt())
            && is_null($this->getInventoryCnt())
            && is_null($this->getExpedCnt())
        ) {
            $this->totalInventoryCnt = null;
        } else {
            $result = $this->getInventoryCnt();
            $product = $this->getProduct();
            if (!is_null($product)) {
                $usageQty = $this->getProductPurchasedHistoric() ? $this->getProductPurchasedHistoric()->getUsageQty(
                ) : $this->getProduct()->getUsageQty();
                $inventoryQty = $this->getProductPurchasedHistoric() ? $this->getProductPurchasedHistoric(
                )->getInventoryQty() : $this->getProduct()->getInventoryQty();
                // usage to inventory conversion
                $usageCnt = $this->getUsageCnt();
                $result += $usageCnt / $usageQty;
                // exped to inventory conversion
                $expedCnt = $this->getExpedCnt();
                $result += $expedCnt * $inventoryQty;
            }
            $this->totalInventoryCnt = $result;
        }
    }
}
