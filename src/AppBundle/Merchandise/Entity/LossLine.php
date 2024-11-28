<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\ManyToOne;
use Symfony\Component\CssSelector\Exception\InternalErrorException;

/**
 * LossLine
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\LossLineRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class LossLine
{
    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableTrait {
        prePersist as traitPrePersist;
    }
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
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\LossSheet", inversedBy="lossLines")
     * @ORM\JoinColumn(nullable=false)
     */
    private $lossSheet;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Product", inversedBy="lossLine")
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @var float
     * @ORM\Column(name="first_entry", type="float", nullable=true)
     */
    private $firstEntry;

    /**
     * @var float
     * @ORM\Column(name="second_entry", type="float", nullable=true)
     */
    private $secondEntry;

    /**
     * @var float
     * @ORM\Column(name="third_entry", type="float", nullable=true)
     */
    private $thirdEntry;


    /**
     * @var float
     *
     * @ORM\Column(name="total_loss", type="float", nullable=true)
     */
    private $totalLoss;

    /**
     * @var Recipe
     * @ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Recipe")
     */
    private $recipe;

    /**
     * @var RecipeHistoric
     * @ManyToOne(targetEntity="AppBundle\Merchandise\Entity\RecipeHistoric")
     */
    private $recipeHistoric;

    /**
     * @var SoldingCanal
     */
    private $soldingCanal;

    /**
     * @var float
     * @ORM\Column(name="total_revenue_price", type="float", nullable=TRUE)
     */
    private $totalRevenuePrice;

    /**
     * @var ProductPurchasedHistoric
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchasedHistoric")
     */
    private $productPurchasedHistoric;

    /**
     * Constructor
     */
    public function __construct()
    {
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
     * Set id
     *
     * @return integer
     */
    public function setId($id)
    {
        return $this->id = $id;
    }

    /**
     * Set totalLoss
     *
     * @param float $totalLoss
     *
     * @return LossLine
     */
    public function setTotalLoss($totalLoss)
    {
        $this->totalLoss = $totalLoss;

        return $this;
    }

    /**
     * Get totalLoss
     *
     * @return float
     */
    public function getTotalLoss()
    {
        return $this->totalLoss;
    }

    /**
     * Set lossSheet
     *
     * @param \AppBundle\Merchandise\Entity\LossSheet $lossSheet
     *
     * @return LossLine
     */
    public function setLossSheet(\AppBundle\Merchandise\Entity\LossSheet $lossSheet)
    {
        $this->lossSheet = $lossSheet;

        return $this;
    }

    /**
     * Get lossSheet
     *
     * @return \AppBundle\Merchandise\Entity\LossSheet
     */
    public function getLossSheet()
    {
        return $this->lossSheet;
    }

    /**
     * Set product
     *
     * @param \AppBundle\Merchandise\Entity\Product $product
     *
     * @return LossLine
     */
    public function setProduct(\AppBundle\Merchandise\Entity\Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \AppBundle\Merchandise\Entity\Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return float
     */
    public function getFirstEntry()
    {
        return $this->firstEntry;
    }

    /**
     * @param float $firstEntry
     * @return LossLine
     */
    public function setFirstEntry($firstEntry)
    {
        $firstEntry = str_replace(',', '.', $firstEntry);
        $this->firstEntry = $firstEntry;
        $current = $this->getFirstEntry() + $this->getSecondEntry() + $this->getThirdEntry();
        $this->setTotalLoss($current);

        return $this;
    }

    /**
     * @return float
     */
    public function getSecondEntry()
    {
        return $this->secondEntry;
    }

    /**
     * @param float $secondEntry
     * @return LossLine
     */
    public function setSecondEntry($secondEntry)
    {
        $secondEntry = str_replace(',', '.', $secondEntry);
        $this->secondEntry = $secondEntry;
        $current = $this->getFirstEntry() + $this->getSecondEntry() + $this->getThirdEntry();
        $this->setTotalLoss($current);

        return $this;
    }

    /**
     * @return float
     */
    public function getThirdEntry()
    {
        return $this->thirdEntry;
    }

    /**
     * @param float $thirdEntry
     * @return LossLine
     */
    public function setThirdEntry($thirdEntry)
    {
        $thirdEntry = str_replace(',', '.', $thirdEntry);
        $this->thirdEntry = $thirdEntry;
        $current = $this->getFirstEntry() + $this->getSecondEntry() + $this->getThirdEntry();
        $this->setTotalLoss($current);

        return $this;
    }

    /**
     * @return Recipe
     */
    public function getRecipe()
    {
        return $this->recipe;
    }

    /**
     * @param Recipe $recipe
     * @return LossLine
     */
    public function setRecipe($recipe)
    {
        $this->recipe = $recipe;

        return $this;
    }

    private function setCalculateTotalLossCnt()
    {
        $result = $this->getFirstEntry() + $this->getSecondEntry() + $this->getThirdEntry();
        $this->totalLoss = $result;
    }

    /**
     * @param EventArgs $args
     * @PrePersist
     */
    public function prePersist(EventArgs $args)
    {
        $this->setCalculateTotalLossCnt();
        $this->traitPrePersist($args);
    }

    /**
     * @return SoldingCanal
     */
    public function getSoldingCanal()
    {
        if (is_null($this->soldingCanal) && !is_null($this->getRecipe())) {
            $this->soldingCanal = $this->getRecipe()->getSoldingCanal();
        }

        return $this->soldingCanal;
    }

    /**
     * @param SoldingCanal $soldingCanal
     * @return LossLine
     */
    public function setSoldingCanal(SoldingCanal $soldingCanal)
    {
        if ($this->getProduct() instanceof ProductSold) {
            // search recipe related with that solding canal
            foreach ($this->getProduct()->getRecipes() as $recipe) {
                /**
                 * @var $recipe Recipe
                 */
                if ($recipe->getSoldingCanal()->getId() === $soldingCanal->getId()) {
                    $this->setRecipe($recipe);
                }
            }
        }
        $this->soldingCanal = $soldingCanal;

        return $this;
    }


    /**
     * Set recipeHistoric
     *
     * @param \AppBundle\Merchandise\Entity\RecipeHistoric $recipeHistoric
     *
     * @return LossLine
     */
    public function setRecipeHistoric(\AppBundle\Merchandise\Entity\RecipeHistoric $recipeHistoric = null)
    {
        $this->recipeHistoric = $recipeHistoric;

        return $this;
    }

    /**
     * Get recipeHistoric
     *
     * @return \AppBundle\Merchandise\Entity\RecipeHistoric
     */
    public function getRecipeHistoric()
    {
        return $this->recipeHistoric;
    }

    /**
     * @return float
     */
    public function getTotalRevenuePrice()
    {
        return $this->totalRevenuePrice;
    }

    /**
     * @param float $totalRevenuePrice
     * @return LossLine
     */
    public function setTotalRevenuePrice($totalRevenuePrice)
    {
        $this->totalRevenuePrice = $totalRevenuePrice;

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
     * @return LossLine
     */
    public function setProductPurchasedHistoric($productPurchasedHistoric)
    {
        $this->productPurchasedHistoric = $productPurchasedHistoric;

        return $this;
    }

    /**
     * @PrePersist()
     * @PreUpdate()
     */
    public function calculateLossTotalRevenue()
    {
        $lossSheetDate = $this->getLossSheet()->getEntryDate();

        if ($this->getRecipeHistoric()) {
            $dateRecipe = $this->getRecipeHistoric()->getProductSold()->getStartDate();
            if ($lossSheetDate <= $dateRecipe) {
                $loosedRecipe = $this->getRecipeHistoric();
            } else {
                $loosedRecipe = $this->getRecipe();
            }
        } else {
            $loosedRecipe = $this->getRecipe();
        }
        $total = null;
        $product = $this->getProduct();
        if ($loosedRecipe) {
            $total = 0;
            foreach ($loosedRecipe->getRecipeLines() as $line) {
                $total += $this->getTotalLoss() * (($line->getQty() / $line->getProductPurchased()->getUsageQty(
                            )) * $line->getProductPurchased()->getBuyingCost() / $line->getProductPurchased(
                        )->getInventoryQty());
            }
        } elseif ($product instanceof ProductPurchased) {
            if ($product->getInventoryQty()) {
                $total = $product->getBuyingCost() * ($this->getTotalLoss() / $product->getInventoryQty());
            }
        } elseif ($product instanceof ProductSold) {
            if ($product->getProductPurchased() && $product->getProductPurchased()->getInventoryQty()) {
                $total = $product->getProductPurchased()->getBuyingCost() * ($this->getTotalLoss(
                        ) / $product->getProductPurchased()->getInventoryQty());
            }
        } else {
            throw new InternalErrorException(
                'The product is expected to be an instance of class '.ProductPurchased::class.', but it\'s an instance of'.get_class($product)
            );
        }
        $this->setTotalRevenuePrice($total);

        return $this->getTotalRevenuePrice();
    }
}
