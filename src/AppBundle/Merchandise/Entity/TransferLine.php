<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * TransferLine
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class TransferLine
{
    use TimestampableTrait;
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
     * @ORM\Column(name="qty", type="integer",nullable=true)
     */
    private $qty;

    /**
     * @var integer
     *
     * @ORM\Column(name="qty_exp", type="integer",nullable=true)
     */
    private $qtyExp;

    /**
     * @var integer
     *
     * @ORM\Column(name="qty_use", type="integer",nullable=true)
     */
    private $qtyUse;

    /**
     * @var Transfer
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Transfer",inversedBy="lines")
     */
    private $transfer;

    /**
     * @var ProductPurchased
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased")
     */
    private $product;

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
     * Set qty
     *
     * @param integer $qty
     *
     * @return TransferLine
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
     * Set transfer
     *
     * @param \AppBundle\Merchandise\Entity\Transfer $transfer
     *
     * @return TransferLine
     */
    public function setTransfer(\AppBundle\Merchandise\Entity\Transfer $transfer = null)
    {
        $this->transfer = $transfer;

        return $this;
    }

    /**
     * Get transfer
     *
     * @return \AppBundle\Merchandise\Entity\Transfer
     */
    public function getTransfer()
    {
        return $this->transfer;
    }

    /**
     * Set product
     *
     * @param \AppBundle\Merchandise\Entity\ProductPurchased $product
     *
     * @return TransferLine
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
     * Set qtyExp
     *
     * @param integer $qtyExp
     *
     * @return TransferLine
     */
    public function setQtyExp($qtyExp)
    {
        $this->qtyExp = $qtyExp;

        return $this;
    }

    /**
     * Get qtyExp
     *
     * @return integer
     */
    public function getQtyExp()
    {
        return $this->qtyExp;
    }

    /**
     * Set qtyUse
     *
     * @param integer $qtyUse
     *
     * @return TransferLine
     */
    public function setQtyUse($qtyUse)
    {
        $this->qtyUse = $qtyUse;

        return $this;
    }

    /**
     * Get qtyUse
     *
     * @return integer
     */
    public function getQtyUse()
    {
        return $this->qtyUse;
    }

    public function getTotal()
    {
        $val = ($this->getQty() === null) ? 0 : $this->getQty();
        $val = $val + (($this->getQtyExp() === null) ? 0 : ($this->getQtyExp() * $this->getProduct()->getInventoryQty(
        )));
        $val = $val + (($this->getQtyUse() === null) ? 0 : ($this->getQtyUse() / ($this->getProduct()->getUsageQty())));

        return $val;
    }

    public function getValorization()
    {
        $qty = $this->getTotal() / $this->getProduct()->getInventoryQty();

        return $qty * $this->getProduct()->getBuyingCost();
    }
}
