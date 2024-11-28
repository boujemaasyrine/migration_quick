<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * RecipeLineHistoric
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class RecipeLineHistoric
{
    use IdTrait;
    use TimestampableTrait;

    /**
     * @var ProductPurchased
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased")
     */
    private $productPurchased;

    /**
     * @var ProductPurchasedHistoric
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchasedHistoric")
     */
    private $productPurchasedHistoric;

    /**
     * Quantity per purchased products
     *
     * @var                                             float
     * @ORM\Column(name="qty",                          type="float")
     * @Assert\NotBlank(groups={"transformed_product"})
     */
    private $qty;

    /**
     * @var RecipeHistoric
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\RecipeHistoric", inversedBy="recipeLines")
     */
    private $recipe;

    private $supplierCode;

    private $productPurchasedName;

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = intval($id);

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
     * @return ProductPurchased
     */
    public function getProductPurchased()
    {
        return $this->productPurchased;
    }

    /**
     * @param ProductPurchased $productPurchased
     * @return $this
     */
    public function setProductPurchased($productPurchased)
    {
        $this->productPurchased = $productPurchased;

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
     * @return $this
     */
    public function setProductPurchasedHistoric($productPurchasedHistoric)
    {
        $this->productPurchasedHistoric = $productPurchasedHistoric;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getQty()
    {
        return $this->qty;
    }

    /**
     * @param mixed $qty
     * @return $this
     */
    public function setQty($qty)
    {
        $qty = str_replace(',', '.', $qty);
        $this->qty = $qty;

        return $this;
    }

    /**
     * @return RecipeHistoric
     */
    public function getRecipe()
    {
        return $this->recipe;
    }

    /**
     * @param RecipeHistoric $recipe
     * @return $this
     */
    public function setRecipe($recipe)
    {
        $this->recipe = $recipe;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSupplierCode()
    {
        if (!is_null($this->getProductPurchased())) {
            return $this->getProductPurchased()->getExternalId();
        }

        return "";
    }

    /**
     * @param mixed $supplierCode
     * @return $this
     */
    public function setSupplierCode($supplierCode)
    {
    }

    /**
     * @return mixed
     */
    public function getProductPurchasedName()
    {
        if (!is_null($this->getProductPurchased())) {
            return $this->getProductPurchased()->getName();
        }

        return "";
    }

    /**
     * @param mixed $productPurchasedName
     * @return $this
     */
    public function setProductPurchasedName($productPurchasedName)
    {
    }

    public function __clone()
    {
        if ($this->id) {
            $this->setId(null);
        }
    }
}
