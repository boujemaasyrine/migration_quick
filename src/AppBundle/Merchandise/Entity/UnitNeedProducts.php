<?php

namespace AppBundle\Merchandise\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * UnitNeedProducts
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\UnitNeedProductsRepository")
 */
class UnitNeedProducts extends Product
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->products = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased", mappedBy="unitNeed")
     */
    private $products;

    /**
     * @return ArrayCollection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param ArrayCollection $products
     */
    public function setProducts($products)
    {
        $this->products = $products;
    }

    /**
     * Add product
     *
     * @param \AppBundle\Merchandise\Entity\ProductPurchased $product
     *
     * @return ProductCategories
     */
    public function addProduct(\AppBundle\Merchandise\Entity\ProductPurchased $product)
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product
     *
     * @param \AppBundle\Merchandise\Entity\ProductPurchased $product
     */
    public function removeProduct(ProductPurchased $product)
    {
        $this->products->removeElement($product);
    }

    /**
     * Retrieve Inventory unit label from the first purchased product
     *
     * @return null
     */
    public function getLabelUnit()
    {
        $label = null;
        $firstPurchasedProduct = $this->getProducts()->first();
        if (!is_null($firstPurchasedProduct)) {
            $label = $firstPurchasedProduct->getLabelUnitInventory();
        }

        return $label;
    }
}
