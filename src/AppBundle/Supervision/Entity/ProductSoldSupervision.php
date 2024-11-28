<?php

namespace AppBundle\Supervision\Entity;

use AppBundle\Merchandise\Entity\SoldingCanal;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\CssSelector\Exception\InternalErrorException;

/**
 * ProductSold
 *
 * @ORM\Entity(repositoryClass="AppBundle\Supervision\Repository\ProductSoldSupervisionRepository")
 * @ORM\Table()
 */
class ProductSoldSupervision extends ProductSupervision
{

    public function __construct()
    {
        parent::__construct();
        $this->recipes = new ArrayCollection();
    }

    const TRANSFORMED_PRODUCT = 'transformed_product';
    const NON_TRANSFORMED_PRODUCT = 'non_transformed_product';

    /**
     * @var string
     * type values: (TRANSFORMED_PRODUCT, NON_TRANSFORMED_PRODUCT);
     * @ORM\Column(name="type", type="string")
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(name="external_id",type="string",nullable=true)
     */
    private $externalId;


    /**
     * @var ProductPurchasedSupervision
     * @ORM\ManyToOne(targetEntity="AppBundle\Supervision\Entity\ProductPurchasedSupervision")
     */
    private $productPurchased;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Supervision\Entity\RecipeSupervision", mappedBy="productSold", cascade={"persist", "remove"}, fetch="EAGER")
     */
    private $recipes;

    /**
     * @var string
     * @ORM\Column(name="code_plu", type="string", nullable=true)
     */
    private $codePlu;

    /**
     * @var boolean
     *
     * @ORM\Column(name="vente_annexe", type="boolean",nullable=true)
     */
    private $venteAnnexe;

    private $productPurchasedName;

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
     * @return ProductSoldSupervision
     */
    public function setRecipes($recipes = null)
    {
        $this->recipes = $recipes;

        return $this;
    }

    /**
     * @param RecipeSupervision $recipe
     * @return $this
     */
    public function addRecipe(RecipeSupervision $recipe)
    {
        $recipe->setProductSold($this);
        $this->recipes->add($recipe);

        return $this;
    }


    /**
     * @param RecipeSupervision $recipe
     * @return ProductSoldSupervision
     */
    public function removeRecipe(RecipeSupervision $recipe)
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
     * @return RecipeSupervision|mixed|null
     */
    public function getDefaultRecipe()
    {
        foreach ($this->recipes as $recipe) {
            /**
             * @var RecipeSupervision $recipe
             */
            if ($recipe->getSoldingCanal()->isDefault()) {
                return $recipe;
            }
        }

        return null;
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
     * @return ProductSoldSupervision
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
     * @return ProductSoldSupervision
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
    public function modifyStock($variation, RecipeSupervision $concernedRecipe = null)
    {
        parent::modifyStock($variation);
        if ($this->getType() === self::TRANSFORMED_PRODUCT) {
            foreach ($this->getRecipes() as $recipe) {
                if ($recipe->getId() === $concernedRecipe->getId()) {
                    /**
                     * @var $recipe RecipeSupervision
                     */
                    foreach ($recipe->getRecipeLines() as $line) {
                        /**
                         * @var  $line RecipeLineSupervision
                         */
                        $newVar = $variation * ($line->getQty() / $line->getProductPurchased()->getUsageQty());
                        $line->getProductPurchased()->modifyStock($newVar);
                    }

                    return;
                }
            }
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

    /**
     * @return string
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;
    }

    /**
     * @return bool
     */
    public function isVenteAnnexe()
    {
        return $this->venteAnnexe;
    }

    /**
     * @param bool $venteAnnexe
     */
    public function setVenteAnnexe($venteAnnexe)
    {
        $this->venteAnnexe = $venteAnnexe;
    }



}
