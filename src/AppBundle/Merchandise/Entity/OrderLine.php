<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * OrderLine
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\OrderLineRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class OrderLine
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
     * @ORM\Column(name="qty", type="float",nullable=true)
     */
    private $qty;

    /**
     * @var Order
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Order",inversedBy="lines")
     */
    private $order;

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
     * @param float $qty
     *
     * @return OrderLine
     */
    public function setQty($qty)
    {
        $this->qty = $qty;

        return $this;
    }

    /**
     * Get qty
     *
     * @return float
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * Set order
     *
     * @param \AppBundle\Merchandise\Entity\Order $order
     *
     * @return OrderLine
     */
    public function setOrder(\AppBundle\Merchandise\Entity\Order $order = null)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return \AppBundle\Merchandise\Entity\Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set product
     *
     * @param \AppBundle\Merchandise\Entity\ProductPurchased $product
     *
     * @return OrderLine
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

    public function getValorization()
    {
        return number_format($this->qty * $this->getProduct()->getBuyingCost(), 2, ',', '');
    }
}
