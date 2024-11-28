<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\Merchandise\Entity\Recipe;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\CssSelector\Exception\InternalErrorException;

/**
 * ProductSold
 *
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\ProductSoldRepository")
 * @ORM\Table()
 */
class ProductSold extends Product
{

    public function __construct()
    {
        parent::__construct();
        $this->recipes = new ArrayCollection();
    }

    const TRANSFORMED_PRODUCT = 'transformed_product';
    const NON_TRANSFORMED_PRODUCT = 'non_transformed_product';
    const IGNORED_PLUS=['900003','900004','900006','900007','900009','900010','900012','900013','900015','900016','900018','900019','900021','900022','900042','900043','900057','900058','900099','900100','900121','900123','900130','900150','900151','900210','900211','900271','900272','900310','900311','905200','905201','366','97000','900005','900200','900201','900202','900330','900492','900501','905222','900203','900324','900325'];

    /**
     * @var Division
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Division", inversedBy="products")
     */
    private $division;

    /**
     * @var string
     * type values: (TRANSFORMED_PRODUCT, NON_TRANSFORMED_PRODUCT);
     * @ORM\Column(name="type", type="string")
     */
    private $type;


    /**
     * @var ProductPurchased
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased")
     */
    private $productPurchased;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\Recipe", mappedBy="productSold", cascade={"persist", "remove"}, fetch="EAGER")
     */
    private $recipes;

    /**
     * @var string
     * @ORM\Column(name="code_plu", type="string", nullable=true)
     */
    private $codePlu;

    private $productPurchasedName;

    /**
     * @return Division
     */
    public function getDivision()
    {
        return $this->division;
    }

    /**
     * @param Division $division
     * @return $this
     */
    public function setDivision($division)
    {
        $this->division = $division;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set recipe
     *
     * @param Recipe []
     *
     * @return ProductSold
     */
    public function setRecipes($recipes = null)
    {
        $this->recipes = $recipes;

        return $this;
    }

    /**
     * @param Recipe $recipe
     * @return $this
     */
    public function addRecipe(Recipe $recipe)
    {
        $recipe->setProductSold($this);
        $this->recipes->add($recipe);

        return $this;
    }


    /**
     * @param Recipe $recipe
     * @return ArrayCollection
     */
    public function removeRecipe(Recipe $recipe)
    {
        $recipe->setProductSold(null);
        $this->recipes->removeElement($recipe);

        return $this;
    }


    /**
     * Get recipe
     *
     * @return ArrayCollection
     */
    public function getRecipes()
    {
        return $this->recipes;
    }

    /**
     * @return \AppBundle\Merchandise\Entity\Recipe|mixed|null
     */
    public function getDefaultRecipe()
    {
        foreach ($this->recipes as $recipe) {
            /**
             * @var Recipe $recipe
             */
            if ($recipe->getSoldingCanal()->isDefault()) {
                return $recipe;
            }
        }

        return null;
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
     * @return ProductSold
     */
    public function setProductPurchased($productPurchased)
    {
        $this->productPurchased = $productPurchased;

        return $this;
    }

    /**
     * @return string
     */
    public function getCodePlu()
    {
        return $this->codePlu;
    }

    /**
     * @param string $codePlu
     * @return ProductSold
     */
    public function setCodePlu($codePlu)
    {
        $this->codePlu = $codePlu;

        return $this;
    }

    /**
     * @param $variation
     * @throws InternalErrorException
     */
    public function modifyStock($variation, Recipe $concernedRecipe = null)
    {
        parent::modifyStock($variation);
        if ($this->getType() === self::TRANSFORMED_PRODUCT) {
          //  foreach ($this->getRecipes() as $recipe) {
             //   if ($recipe->getId() === $concernedRecipe->getId()) {
            if(is_object($concernedRecipe)){
                /**
                 * @var $concernedRecipe Recipe
                 */
                foreach ($concernedRecipe->getRecipeLines() as $line) {
                    /**
                     * @var  $line RecipeLine
                     */
                    $newVar = $variation * ($line->getQty() / $line->getProductPurchased()->getUsageQty());
                    $line->getProductPurchased()->modifyStock($newVar);
                }

                return;
            }

               // }
          //  }
            throw new InternalErrorException("The given recipe not found there is a problem !");
        } elseif ($this->getType() === self::NON_TRANSFORMED_PRODUCT) {
            $this->getProductPurchased()->modifyStock($variation / $this->getProductPurchased()->getUsageQty());
        }
    }

    public function getSoldingCanalRecipe(SoldingCanal $soldingCanal)
    {
        foreach ($this->getRecipes() as $recipe) {
            if ($soldingCanal->getId() === $recipe->getSoldingCanal()->getId()) {
                return $recipe;
            }
        }

        return null;
    }

    public function getSoldingCanalsIds()
    {
        $result = [];
        foreach ($this->getRecipes() as $recipe) {
            $result[] = $recipe->getSoldingCanal()->getId();
        }

        return '['.implode(",", $result).']';
    }

    public function isTransformedProduct()
    {
        return $this->getType() === $this::TRANSFORMED_PRODUCT;
    }


    /**
     * @return mixed
     */
    public function getProductPurchasedName()
    {
        if (!is_null($this->getProductPurchased())) {
            $this->productPurchasedName = $this->getProductPurchased()->getName();
        }

        return $this->productPurchasedName;
    }

    /**
     * @param $productPurchasedName
     * @return $this
     */
    public function setProductPurchasedName($productPurchasedName)
    {
        $this->productPurchasedName = $productPurchasedName;

        return $this;
    }

    /**
     * This function will calculate default revenu:
     * If transformed product it will show the revenu of the default recipe
     * If it's a non transformed produit it will show the revenu of the product purchased
     */
    public function calculateDefaultRevenu()
    {
        $result = 0;
        if (is_null($this->getProductPurchased())) {
            // get default recipe
            $defaultRecipe = $this->getDefaultRecipe();
            $result = is_null($defaultRecipe) ? 0 : $defaultRecipe->calculateRevenu();
        } else {
            $result = $this->getProductPurchased()->getBuyingCostInUsageUnit();
        }

        return $result;
    }
}
