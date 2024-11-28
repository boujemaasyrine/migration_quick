<?php

namespace AppBundle\Supervision\Entity;

use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Merchandise\Entity\SubSoldingCanal;
use AppBundle\ToolBox\Traits\GlobalIdTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Recipe
 *
 * @ORM\Table()
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class RecipeSupervision
{

    use GlobalIdTrait;

    const ALL_CANALS = 'Tous Canals';

    public function __construct()
    {
        $this->recipeLines = new ArrayCollection();
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var ProductSoldSupervision
     * @ORM\ManyToOne(targetEntity="AppBundle\Supervision\Entity\ProductSoldSupervision", inversedBy="recipes")
     */
    private $productSold;

    /**
     * @var SoldingCanal
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\SoldingCanal")
     */
    private $soldingCanal;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Supervision\Entity\RecipeLineSupervision", mappedBy="recipe", cascade={"persist", "remove"}, fetch="EAGER",orphanRemoval=true)
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
     * @return RecipeSupervision
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
     * @return ProductSoldSupervision
     */
    public function getProductSold()
    {
        return $this->productSold;
    }

    /**
     * @param ProductSoldSupervision $productSold
     * @return RecipeSupervision
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
     * @return RecipeSupervision
     */
    public function setSoldingCanal($soldingCanal)
    {
        $this->soldingCanal = $soldingCanal;

        return $this;
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
     * @return RecipeSupervision
     */
    public function setSubSoldingCanal($subSoldingCanal)
    {
        $this->subSoldingCanal = $subSoldingCanal;
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
     * @return RecipeSupervision
     */
    public function setRecipeLines($recipeLines)
    {
        $this->recipeLines = $recipeLines;

        return $this;
    }

    public function addRecipeLine(RecipeLineSupervision $recipeLine)
    {
        $recipeLine->setRecipe($this);
        $this->recipeLines->add($recipeLine);

        return $this;
    }

    public function removeRecipeLine(RecipeLineSupervision $recipeLine)
    {
        $this->recipeLines->removeElement($recipeLine);
        $recipeLine->setRecipe(null);

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
     * @return RecipeSupervision
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
     * @return RecipeSupervision
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
             * @var ProductPurchasedSupervision $product
             * @var RecipeLineSupervision $recipeLine
             */
            $product = $recipeLine->getProductPurchased();
            $revenu += ($product->getBuyingCost() / ($product->getInventoryQty() * $product->getUsageQty())) * $recipeLine->getQty();
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
     * @return RecipeSupervision
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
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function setRevenu()
    {
        $this->setRevenuePrice($this->calculateRevenu());
    }
}
