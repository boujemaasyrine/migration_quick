<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\GlobalIdTrait;
use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * RecipeLineHistoric
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\RecipeHistoricRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class RecipeHistoric
{

    use IdTrait;
    use GlobalIdTrait;
    use TimestampableTrait;

    public function __construct()
    {
        $this->recipeLines = new ArrayCollection();
    }

    /**
     * @var ProductSoldHistoric
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductSoldHistoric", inversedBy="recipes")
     */
    private $productSold;

    /**
     * @var SoldingCanal
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\SoldingCanal")
     */
    private $soldingCanal;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\RecipeLineHistoric", mappedBy="recipe", cascade={"persist", "remove"})
     */
    private $recipeLines;

    /**
     * @var string
     * @ORM\Column(name="external_id", type="string", nullable=TRUE)
     */
    private $externalId;

    /**
     * @var boolean
     * @ORM\Column(name="active", type="boolean", nullable=TRUE)
     */
    private $active;

    /**
     * @var float
     * @ORM\Column(name="revenue_price",type="float",nullable=true)
     */
    private $revenuePrice;

    /**
     * @var SubSoldingCanal
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\SubSoldingCanal")
     */
    private $subSoldingCanal;

    /**
     * @param int $id
     * @return Recipe
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
     * @return ProductSoldHistoric
     */
    public function getProductSold()
    {
        return $this->productSold;
    }

    /**
     * @param ProductSoldHistoric $productSold
     * @return Recipe
     */
    public function setProductSold($productSold)
    {
        $this->productSold = $productSold;

        return $this;
    }

    /**
     * @return SoldingCanal
     */
    public function getSoldingCanal()
    {
        return $this->soldingCanal;
    }

    /**
     * @param SoldingCanal $soldingCanal
     * @return Recipe
     */
    public function setSoldingCanal($soldingCanal)
    {
        $this->soldingCanal = $soldingCanal;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getRecipeLines()
    {
        return $this->recipeLines;
    }

    /**
     * @param ArrayCollection $recipeLines
     * @return Recipe
     */
    public function setRecipeLines($recipeLines)
    {
        $this->recipeLines = $recipeLines;

        return $this;
    }

    public function addRecipeLine(RecipeLineHistoric $recipeLine)
    {
        $recipeLine->setRecipe($this);
        $this->recipeLines->add($recipeLine);

        return $this;
    }

    public function removeRecipeLine(RecipeLineHistoric $recipeLine)
    {
        $this->recipeLines->removeElement($recipeLine);

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
     * @param string $externalId
     * @return Recipe
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     * @return Recipe
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    public function __clone()
    {
        if ($this->id) {
            $this->setId(null);
            $mClone = new ArrayCollection();
            foreach ($this->recipeLines as $item) {
                $itemClone = clone $item;
                $itemClone->setRecipe($this);
                $mClone->add($itemClone);
            }
            $this->recipeLines = $mClone;
        }
    }

    public function calculateRevenu()
    {
        $revenu = 0;
        foreach ($this->recipeLines as $recipeLine) {
            /**
             * @var ProductPurchased $product
             * @var RecipeLineHistoric $recipeLine
             */
            $product = $recipeLine->getProductPurchased();
            $revenu += ($product->getBuyingCost() / ($product->getInventoryQty() * $product->getUsageQty(
            ))) * $recipeLine->getQty();
        }

        return $revenu;
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
     * Set revenuePrice
     *
     * @param float $revenuePrice
     *
     * @return RecipeHistoric
     */
    public function setRevenuePrice($revenuePrice)
    {
        $this->revenuePrice = $revenuePrice;

        return $this;
    }

    /**
     * Get revenuePrice
     *
     * @return float
     */
    public function getRevenuePrice()
    {
        return $this->revenuePrice;
    }

    /**
     * @return SubSoldingCanal
     */
    public function getSubSoldingCanal()
    {
        return $this->subSoldingCanal;
    }

    /**
     * @param SubSoldingCanal $subSoldingCanal
     * @return Recipe
     */
    public function setSubSoldingCanal($subSoldingCanal)
    {
        $this->subSoldingCanal = $subSoldingCanal;
    }

}
