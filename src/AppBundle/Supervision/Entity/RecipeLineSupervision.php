<?php

namespace AppBundle\Supervision\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Recipe
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Supervision\Repository\RecipeLineSupervisionRepository")
 */
class RecipeLineSupervision
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var ProductPurchasedSupervision
     * @ORM\ManyToOne(targetEntity="AppBundle\Supervision\Entity\ProductPurchasedSupervision")
     */
    private $productPurchased;

    /**
     * Quantity per purchased products
     *
     * @var                                             float
     * @ORM\Column(name="qty",                          type="float")
     * @Assert\NotBlank(groups={"transformed_product"})
     */
    private $qty;

    /**
     * @var RecipeSupervision
     * @ORM\ManyToOne(targetEntity="AppBundle\Supervision\Entity\RecipeSupervision", inversedBy="recipeLines")
     */
    private $recipe;

    private $supplierCode;

    private $productPurchasedName;

    /**
     * @param int $id
     * @return RecipeLineSupervision
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
     * @return ProductPurchasedSupervision
     */
    public function getProductPurchased()
    {
        return $this->productPurchased;
    }

    /**
     * @param ProductPurchasedSupervision $productPurchased
     * @return RecipeLineSupervision
     */
    public function setProductPurchased($productPurchased)
    {
        $this->productPurchased = $productPurchased;

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
     * @return RecipeLineSupervision
     */
    public function setQty($qty)
    {
        $qty = str_replace(',', '.', $qty);
        $this->qty = $qty;

        return $this;
    }

    /**
     * @return RecipeSupervision
     */
    public function getRecipe()
    {
        return $this->recipe;
    }

    /**
     * @param RecipeSupervision $recipe
     * @return RecipeLineSupervision
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
     * @return RecipeLineSupervision
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
            return $this->getProductPurchased()->getExternalId() . '- ' . $this->getProductPurchased()->getName();
        }

        return "";
    }

    /**
     * @param mixed $productPurchasedName
     * @return RecipeLineSupervision
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
