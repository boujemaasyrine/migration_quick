<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Returns
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class ReturnLine
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
     * @ORM\Column(name="qty", type="integer")
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
     * @var ProductPurchased
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased")
     */
    private $product;

    /**
     * @var Returns
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Returns",inversedBy="lines")
     */
    private $return;


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
     * @return Returns
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
     * Set product
     *
     * @param \AppBundle\Merchandise\Entity\ProductPurchased $product
     *
     * @return ReturnLine
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

    public function __construct()
    {
        $this->qty = 0;
    }


    /**
     * Set return
     *
     * @param \AppBundle\Merchandise\Entity\Returns $return
     *
     * @return ReturnLine
     */
    public function setReturn(\AppBundle\Merchandise\Entity\Returns $return = null)
    {
        $this->return = $return;

        return $this;
    }

    /**
     * Get return
     *
     * @return \AppBundle\Merchandise\Entity\Returns
     */
    public function getReturn()
    {
        return $this->return;
    }

    /**
     * Set qtyExp
     *
     * @param integer $qtyExp
     *
     * @return ReturnLine
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
     * @return ReturnLine
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
        $val += ($this->getQtyExp() === null) ? 0 : ($this->getQtyExp() * $this->getProduct()->getInventoryQty());
        $val += ($this->getQtyUse() === null) ? 0 : ($this->getQtyUse() / ($this->getProduct()->getUsageQty()));

        return $val;
    }

    public function getValorization()
    {
        $qty = $this->getTotal() / $this->getProduct()->getInventoryQty();

        return $qty * $this->getProduct()->getBuyingCost();
    }
}
