<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * DeliveryLine
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class DeliveryLineTmp
{
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
     * @var integer
     *
     * @ORM\Column(name="qty", type="integer")
     */
    private $qty;

    /**
     * @var float
     *
     * @ORM\Column(name="valorization", type="float")
     */
    private $valorization;

    /**
     * @var Delivery
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\DeliveryTmp",inversedBy="lines")
     */
    private $delivery;

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
     * @return DeliveryLine
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
     * Set delivery
     *
     * @param DeliveryTmp $delivery
     *
     * @return DeliveryLine
     */
    public function setDelivery(DeliveryTmp $delivery = null)
    {
        $this->delivery = $delivery;

        return $this;
    }

    /**
     * Get delivery
     *
     * @return \AppBundle\Merchandise\Entity\DeliveryTmp
     */
    public function getDelivery()
    {
        return $this->delivery;
    }

    /**
     * Set product
     *
     * @param \AppBundle\Merchandise\Entity\ProductPurchased $product
     *
     * @return DeliveryLine
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
     * Set valorization
     *
     * @param float $valorization
     *
     * @return DeliveryLine
     */
    public function setValorization($valorization)
    {
        $valorization = str_replace(',', '.', $valorization);
        $this->valorization = $valorization;

        return $this;
    }

    /**
     * Get valorization
     *
     * @return float
     */
    public function getValorization()
    {
        return $this->valorization;
    }

    public static function createFromOrderLine(OrderLine $line)
    {
        $deliveryLine = new DeliveryLine();
        $deliveryLine->setProduct($line->getProduct());
        $deliveryLine->setQty($line->getQty());
        $deliveryLine->setValorization($line->getQty() * $line->getProduct()->getBuyingCost());

        return $deliveryLine;
    }
}
