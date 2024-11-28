<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 27/05/2016
 * Time: 11:49
 */

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\GlobalIdTrait;
use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class ProductSoldHistoric
 *
 * @package AppBundle\Merchandise\Entity
 *
 * @Entity()
 * @Table()
 * @HasLifecycleCallbacks()
 */
class ProductSoldHistoric
{

    use IdTrait;
    use TimestampableTrait;
    use GlobalIdTrait;
    use OriginRestaurantTrait;

    public function __construct()
    {
        $this->recipes = new ArrayCollection();
    }

    const TRANSFORMED_PRODUCT = 'transformed_product';
    const NON_TRANSFORMED_PRODUCT = 'non_transformed_product';

    /**
     * @var \DateTime
     * @ORM\Column(name="start_date", type="datetime", nullable=TRUE)
     */
    protected $startDate;

    /**
     * @var string
     * @ORM\Column(name="name",type="string",length=100)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="reference", type="string", length=255, nullable=true)
     */
    protected $reference;

    /**
     * @var boolean
     * @ORM\Column(name="active", type="boolean", options={"default"=true})
     */
    protected $active;

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
     * @var ProductPurchasedHistoric
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchasedHistoric")
     */
    private $productPurchasedHistoric;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\RecipeHistoric", mappedBy="productSold", cascade={"persist", "remove"})
     */
    private $recipes;

    /**
     * @var string
     * @ORM\Column(name="code_plu", type="string", nullable=true)
     */
    private $codePlu;

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
     * @return ProductSoldHistoric
     */
    public function setRecipes($recipes = null)
    {
        $this->recipes = $recipes;

        return $this;
    }

    /**
     * @param RecipeHistoric $recipeHistoric
     * @return $this
     */
    public function addRecipe(RecipeHistoric $recipeHistoric)
    {
        $recipeHistoric->setProductSold($this);
        $this->recipes[] = $recipeHistoric;

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
     * @return ProductPurchased
     */
    public function getProductPurchased()
    {
        return $this->productPurchased;
    }

    /**
     * @param ProductPurchased $productPurchased
     * @return ProductSoldHistoric
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
     * @return ProductSoldHistoric
     */
    public function setProductPurchasedHistoric($productPurchasedHistoric)
    {
        $this->productPurchasedHistoric = $productPurchasedHistoric;

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
     * @return ProductSoldHistoric
     */
    public function setCodePlu($codePlu)
    {
        $this->codePlu = $codePlu;

        return $this;
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ProductSoldHistoric
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     * @return ProductSoldHistoric
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

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
     * @return ProductSoldHistoric
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     * @return ProductSoldHistoric
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }
}
