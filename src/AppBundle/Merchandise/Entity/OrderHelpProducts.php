<?php

namespace AppBundle\Merchandise\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OrderHelpProducts
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class OrderHelpProducts
{

    const TYPE_THEO = 'theo';
    const TYPE_REAL = 'real';

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
     * @ORM\Column(name="heb_theo", type="float",nullable=true)
     */
    private $hebTheo;

    /**
     * @var integer
     *
     * @ORM\Column(name="heb_real", type="float",nullable=true)
     */
    private $hebReal;

    /**
     * @var float
     *
     * @ORM\Column(name="coeff", type="float",nullable=true)
     */
    private $coeff;

    /**
     * @var float
     *
     * @ORM\Column(name="stockByDay", type="float",nullable=true)
     */
    private $stockByDay;

    /**
     * @var $stockQty
     * @ORM\Column(name="stock_qty_theo",type="float",nullable=true)
     */
    private $stockQtyTheo;

    /**
     * @var $stockQty
     * @ORM\Column(name="stock_qty_real",type="float",nullable=true)
     */
    private $stockQtyReal;

    /**
     * @var float
     * @ORM\Column(name="last_stock_qty",type="float",nullable=true)
     */
    private $lastStockQty;

    /**
     * @var float
     * @ORM\Column(name="last_stock_qty_is_real",type="boolean",nullable=true)
     */
    private $lastStockQtyIsReal;

    /**
     * @var ProductPurchased
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased")
     */
    private $product;

    /**
     * @var OrderHelpSupplier
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\OrderHelpSupplier",inversedBy="products")
     */
    private $supplier;

    /**
     * @var $orderHelp
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\OrderHelpTmp",inversedBy="products")
     */
    private $orderHelp;

    /**
     * @var $type
     * @ORM\Column(name="type",type="string",length=10,nullable=true)
     */
    private $type;

    /**
     * @var boolean
     * @ORM\Column(name="fixed",type="boolean",nullable=true,options={"default"=false})
     */
    private $fixed = false;

    /**
     * @var boolean
     * @ORM\Column(name="stock_final_exist",type="boolean",nullable=true,options={"default"=true})
     */
    private $stockFinalExist;

    /**
     * @var string
     * @ORM\Column(name="meta",type="text",nullable=true)
     */
    private $meta;

    /**
     * @var
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\OrderHelpMaskProduct",mappedBy="helpProduct")
     */
    private $helpMaskProducts;

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
     * Set hebTheo
     *
     * @param float $heb
     *
     * @return OrderHelpProducts
     */
    public function setHebTheo($heb)
    {
        $this->hebTheo = $heb;

        return $this;
    }

    /**
     * Get heb
     *
     * @return float
     */
    public function getHebTheo()
    {
        return $this->hebTheo;
    }

    /**
     * Set coeff
     *
     * @param float $coeff
     *
     * @return OrderHelpProducts
     */
    public function setCoeff($coeff)
    {
        $this->coeff = $coeff;

        return $this;
    }

    /**
     * Get coeff
     *
     * @return float
     */
    public function getCoeff()
    {
        return $this->coeff;
    }


    /**
     * Set stockByDay
     *
     * @param float $stockByDay
     *
     * @return OrderHelpProducts
     */
    public function setStockByDay($stockByDay)
    {
        $this->stockByDay = $stockByDay;

        return $this;
    }

    /**
     * Get stockByDay
     *
     * @return float
     */
    public function getStockByDay()
    {
        return $this->stockByDay;
    }


    /**
     * Set product
     *
     * @param \AppBundle\Merchandise\Entity\ProductPurchased $product
     *
     * @return OrderHelpProducts
     */
    public function setProduct(\AppBundle\Merchandise\Entity\ProductPurchased $product = null)
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
     * Set supplier
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpSupplier $supplier
     *
     * @return OrderHelpProducts
     */
    public function setSupplier(\AppBundle\Merchandise\Entity\OrderHelpSupplier $supplier = null)
    {
        $this->supplier = $supplier;

        return $this;
    }

    /**
     * Get supplier
     *
     * @return \AppBundle\Merchandise\Entity\OrderHelpSupplier
     */
    public function getSupplier()
    {
        return $this->supplier;
    }

    /**
     * Set orderHelp
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpTmp $orderHelp
     *
     * @return OrderHelpProducts
     */
    public function setOrderHelp(\AppBundle\Merchandise\Entity\OrderHelpTmp $orderHelp = null)
    {
        $this->orderHelp = $orderHelp;

        return $this;
    }

    /**
     * Get orderHelp
     *
     * @return \AppBundle\Merchandise\Entity\OrderHelpTmp
     */
    public function getOrderHelp()
    {
        return $this->orderHelp;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return OrderHelpProducts
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
     * Set hebReal
     *
     * @param float $hebReal
     *
     * @return OrderHelpProducts
     */
    public function setHebReal($hebReal)
    {
        $this->hebReal = $hebReal;

        return $this;
    }

    /**
     * Get hebReal
     *
     * @return float
     */
    public function getHebReal()
    {
        return $this->hebReal;
    }


    /**
     * Set fixed
     *
     * @param boolean $fixed
     *
     * @return OrderHelpProducts
     */
    public function setFixed($fixed)
    {
        $this->fixed = $fixed;

        return $this;
    }

    /**
     * Get fixed
     *
     * @return boolean
     */
    public function getFixed()
    {
        return $this->fixed;
    }

    /**
     * Set stockQtyTheo
     *
     * @param float $stockQtyTheo
     *
     * @return OrderHelpProducts
     */
    public function setStockQtyTheo($stockQtyTheo)
    {
        $this->stockQtyTheo = $stockQtyTheo;

        return $this;
    }

    /**
     * Get stockQtyTheo
     *
     * @return float
     */
    public function getStockQtyTheo()
    {
        return $this->stockQtyTheo;
    }

    /**
     * Set stockQtyReal
     *
     * @param float $stockQtyReal
     *
     * @return OrderHelpProducts
     */
    public function setStockQtyReal($stockQtyReal)
    {
        $this->stockQtyReal = $stockQtyReal;

        return $this;
    }

    /**
     * Get stockQtyReal
     *
     * @return float
     */
    public function getStockQtyReal()
    {
        return $this->stockQtyReal;
    }

    /**
     * Set stockFinalExist
     *
     * @param boolean $stockFinalExist
     *
     * @return OrderHelpProducts
     */
    public function setStockFinalExist($stockFinalExist)
    {
        $this->stockFinalExist = $stockFinalExist;

        return $this;
    }

    /**
     * Get stockFinalExist
     *
     * @return boolean
     */
    public function getStockFinalExist()
    {
        return $this->stockFinalExist;
    }

    /**
     * Set meta
     *
     * @param string $meta
     *
     * @return OrderHelpProducts
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Get meta
     *
     * @return string
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->helpMaskProducts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add helpMaskProduct
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpMaskProduct $helpMaskProduct
     *
     * @return OrderHelpProducts
     */
    public function addHelpMaskProduct(\AppBundle\Merchandise\Entity\OrderHelpMaskProduct $helpMaskProduct)
    {
        $this->helpMaskProducts[] = $helpMaskProduct;

        return $this;
    }

    /**
     * Remove helpMaskProduct
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpMaskProduct $helpMaskProduct
     */
    public function removeHelpMaskProduct(\AppBundle\Merchandise\Entity\OrderHelpMaskProduct $helpMaskProduct)
    {
        $this->helpMaskProducts->removeElement($helpMaskProduct);
    }

    /**
     * Get helpMaskProducts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHelpMaskProducts()
    {
        $helpProducts = $this->helpMaskProducts->toArray();
        usort(
            $helpProducts,
            function (OrderHelpMaskProduct $p1, OrderHelpMaskProduct $p2) {

                if ($p1->getMask()->getOrderDate()->format('Ymd') < $p2->getMask()->getOrderDate()->format('Ymd')) {
                    return -1;
                }

                return 1;
            }
        );

        return $helpProducts;
    }


    /**
     * Set lastStockQty
     *
     * @param float $lastStockQty
     *
     * @return OrderHelpProducts
     */
    public function setLastStockQty($lastStockQty)
    {
        $this->lastStockQty = $lastStockQty;

        return $this;
    }

    /**
     * Get lastStockQty
     *
     * @return float
     */
    public function getLastStockQty()
    {
        return $this->lastStockQty;
    }

    /**
     * Set lastStockQtyIsReal
     *
     * @param boolean $lastStockQtyIsReal
     *
     * @return OrderHelpProducts
     */
    public function setLastStockQtyIsReal($lastStockQtyIsReal)
    {
        $this->lastStockQtyIsReal = $lastStockQtyIsReal;

        return $this;
    }

    /**
     * Get lastStockQtyIsReal
     *
     * @return boolean
     */
    public function getLastStockQtyIsReal()
    {
        return $this->lastStockQtyIsReal;
    }
}
