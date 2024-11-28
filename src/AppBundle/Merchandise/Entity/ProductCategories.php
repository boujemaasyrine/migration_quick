<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\Merchandise\Entity\Translation\ProductCategoriesTranslation;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\ToolBox\Traits\GlobalIdTrait;
use Assetic\Asset\GlobAsset;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ProductCategories
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\ProductCategoriesRepository")
 * @UniqueEntity("name")
 * @Gedmo\TranslationEntity(class="AppBundle\Merchandise\Entity\Translation\ProductCategoriesTranslation")
 */
class ProductCategories
{
    use GlobalIdTrait;

    const FOOD_COST = 'FOOD COST';
    const NON_FOOD_COST = 'NON FOOD COST';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Gedmo\Translatable
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="reference", type="string", length=255, nullable=true)
     */
    private $reference;

    /**
     * @var Product
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased", mappedBy="productCategory")
     */
    private $products;

    /**
     * @var Product
     * @ORM\OneToMany(targetEntity="AppBundle\Supervision\Entity\ProductPurchasedSupervision", mappedBy="productCategory")
     */
    private $supervisionProducts;

    /**
     * @var string
     * @ORM\Column(name="tax_letter", type="string",nullable=true)
     */
    private $taxLetter;

    /**
     * @var float
     * @ORM\Column(name="tva", type="float",nullable=true)
     */
    private $tva;

    /**
     * @var float
     * @ORM\Column(name="tax_be", type="float",nullable=true)
     */
    private $taxBe;

    /**
     * @var float
     * @ORM\Column(name="tax_lux", type="float",nullable=true)
     */
    private $taxLux;

    /**
     * @var boolean
     * @ORM\Column(name="eligible",type="boolean",nullable=true)
     */
    private $eligible = false;

    /**
     * @var CategoryGroup
     * @ORM\ManyToOne(targetEntity="CategoryGroup")
     */
    private $categoryGroup;

    /**
     * @var integer
     * @ORM\Column(name="category_order",type="integer",nullable=true)
     */
    private $order;

    /**
     * @var boolean
     * @ORM\Column(name="active",type="boolean",nullable=true)
     */
    private $active;

    /**
     * @ORM\OneToMany(
     *   targetEntity="AppBundle\Merchandise\Entity\Translation\ProductCategoriesTranslation",
     *   mappedBy="object",
     *   cascade={"persist", "remove"}
     * )
     */
    private $translations;

    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param ProductCategoriesTranslation $t
     */
    public function addTranslation(ProductCategoriesTranslation $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    public function getNameTranslation($locale)
    {
        $label = null;
        foreach ($this->translations as $translation) {
            /**
             * @var ProductCategoriesTranslation $translation
             */
            if ($translation->getLocale() == $locale) {
                $label = $translation->getcontent();
            }
        }

        return $label;
    }

    public function addNameTranslation($locale, $value)
    {
        $exist = false;
        foreach ($this->translations as $t) {
            if ($t->getLocale() == $locale) {
                $exist = true;
                $t->setContent($value);
            }
        }
        if (!$exist) {
            $translation = new ProductCategoriesTranslation($locale, 'name', $value);
            $this->addTranslation($translation);
        }

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
     * Set name
     *
     * @param string $name
     *
     * @return ProductCategories
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set reference
     *
     * @param string $reference
     *
     * @return ProductCategories
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Get reference
     *
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->products = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->supervisionProducts = new ArrayCollection();

        $this->addNameTranslation('fr', '');
        $this->addNameTranslation('nl', '');
    }

    /**
     * Add product
     *
     * @param \AppBundle\Merchandise\Entity\Product $product
     *
     * @return ProductCategories
     */
    public function addProduct(Product $product)
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product
     *
     * @param \AppBundle\Merchandise\Entity\Product $product
     */
    public function removeProduct(Product $product)
    {
        $this->products->removeElement($product);
    }

    /**
     * Get products
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSupervisionProducts()
    {
        return $this->supervisionProducts;
    }

    /**
     * Add product
     *
     * @param ProductPurchasedSupervision $product
     *
     * @return ProductCategories
     */
    public function addSupervisionProduct(ProductPurchasedSupervision $product)
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product
     *
     * @param ProductPurchasedSupervision $product
     */
    public function removeSupervisionProduct(ProductPurchasedSupervision $product)
    {
        $this->products->removeElement($product);
    }

    /**
     * Get products
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @return string
     */
    public function getTaxLetter()
    {
        return $this->taxLetter;
    }

    /**
     * @param $taxLetter
     * @return $this
     */
    public function setTaxLetter($taxLetter)
    {
        $this->taxLetter = $taxLetter;

        return $this;
    }

    /**
     * @return float
     */
    public function getTva()
    {
        return $this->tva;
    }

    /**
     * @param $tva
     * @return $this
     */
    public function setTva($tva)
    {
        $tva = str_replace(',', '.', $tva);
        $this->tva = $tva;

        return $this;
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Set eligible
     *
     * @param boolean $eligible
     *
     * @return ProductCategories
     */
    public function setEligible($eligible)
    {
        $this->eligible = $eligible;

        return $this;
    }

    /**
     * Get eligible
     *
     * @return boolean
     */
    public function getEligible()
    {
        return $this->eligible;
    }

    /**
     * @return float
     */
    public function getTaxBe()
    {
        return $this->taxBe;
    }

    /**
     * @param float $taxBe
     * @return ProductCategories
     */
    public function setTaxBe($taxBe)
    {
        $this->taxBe = $taxBe;

        return $this;
    }

    /**
     * @return float
     */
    public function getTaxLux()
    {
        return $this->taxLux;
    }

    /**
     * @param float $taxLux
     * @return ProductCategories
     */
    public function setTaxLux($taxLux)
    {
        $this->taxLux = $taxLux;

        return $this;
    }

    /**
     * Set categoryGroup
     *
     * @param  \AppBundle\Merchandise\Entity\CategoryGroup $categoryGroup
     * @return ProductCategories
     */
    public function setCategoryGroup(\AppBundle\Merchandise\Entity\CategoryGroup $categoryGroup = null)
    {
        $this->categoryGroup = $categoryGroup;

        return $this;
    }

    /**
     * Get categoryGroup
     *
     * @return \AppBundle\Merchandise\Entity\CategoryGroup
     */
    public function getCategoryGroup()
    {
        return $this->categoryGroup;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param int $order
     * @return ProductCategories
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }


    /**
     * Set active
     *
     * @param  boolean $active
     * @return ProductCategories
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
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
}
