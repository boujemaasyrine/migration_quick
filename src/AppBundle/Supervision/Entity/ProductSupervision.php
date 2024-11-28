<?php

namespace AppBundle\Supervision\Entity;

use AppBundle\General\Entity\SyncCmdQueue;
use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Translation\ProductTranslation;
use AppBundle\ToolBox\Traits\IdTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Product
 *
 * @ORM\Table
 * @ORM\Entity()
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="product_discr", type="string")
 * @ORM\DiscriminatorMap({"product"="ProductSupervision", "purchased" = "ProductPurchasedSupervision", "sold" = "ProductSoldSupervision"})
 * @Gedmo\TranslationEntity(class="ProductSupervisionTranslation")
 * @ORM\HasLifecycleCallbacks()
 */
class ProductSupervision
{

    const ARTICLE = 'article';
    const FINALPRODUCT = 'finalProduct';

    const INVENTORY_UNIT = 'inventory_unit';
    const EXPED_UNIT = 'exped_unit';
    const USE_UNIT = 'use_unit';

    static $unitsLabel = [
        "COLIS" => 'units.colis',
        "PIECE" => 'units.piece',
        "KILO" => 'units.kilo',
        "LITRE" => 'units.litre',
        "SACHET" => 'units.sachet',
        "BARQUETTE" => 'units.barquette',
        "GRAMME" => 'units.gramme',
        "CENTILITRE" => 'units.centilitre',
        "SEAU" => 'units.seau',
        "PILE" => 'units.pile',
        "BIDON" => 'units.bidon',
        "PORTION" => 'units.portion'
    ];
    use TimestampableTrait;
    use IdTrait;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     * and it is not necessary because globally locale can be set in listener
     */
    protected $locale;

    /**
     * @var LossLine
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\LossLine", mappedBy="product")
     */
    private $lossLine;

    /**
     * @var string
     * @ORM\Column(name="name",type="string",length=100)
     * @Gedmo\Translatable
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="reference", type="string", length=255, nullable=true)
     */
    protected $reference;

    /**
     * @var float
     *
     * @ORM\Column(name="stock_current_qty", type="float")
     */
    protected $stockCurrentQty = 0;

    /**
     * @var boolean
     * @ORM\Column(name="active", type="boolean", options={"default"=true})
     */
    protected $active;

    /**
     * @var bool
     * @ORM\Column(name="eligible_for_optikitchen",type="boolean",nullable=true)
     */
    private $eligibleForOptikitchen = false;

    /**
     * @var
     * @ORM\OneToMany(
     *   targetEntity="AppBundle\Supervision\Entity\ProductSupervisionTranslation",
     *   mappedBy="object",
     *   cascade={"persist", "remove"}
     * )
     */
    private $translations;

    /**
     * @var
     * @ORM\Column(name="global_product_id",type="integer",nullable=true)
     */
    private $globalProductID;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_synchro", type="date", nullable=true)
     */
    protected $dateSynchro;


    /**
     * @ORM\Column(name="is_synchronized",type="boolean", nullable= true)
     */
    protected $isSynchronized = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_date_synchro", type="datetime", nullable=true)
     */
    protected $lastDateSynchro;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\General\Entity\SyncCmdQueue", mappedBy="product")
     */
    protected $syncCmdQueues;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\Product", mappedBy="supervisionProduct")
     */
    protected $products;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Merchandise\Entity\Restaurant", inversedBy="supervisionProducts")
     */
    protected $restaurants;


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
     * Get id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param ProductSupervisionTranslation $t
     */
    public function addTranslation(ProductSupervisionTranslation $t)
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
            /**
             * @var ProductTranslation $t
             */
            if ($t->getLocale() == $locale) {
                $exist = true;
                $t->setContent($value);
            }
        }
        if (!$exist) {
            $translation = new ProductSupervisionTranslation($locale, 'name', $value);
            $this->addTranslation($translation);
        }

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ProductSupervision
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
     * @return ProductSupervision
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
     * Set stockCurrentQty
     *
     * @param float $stockCurrentQty
     *
     * @return ProductSupervision
     */
    public function setStockCurrentQty($stockCurrentQty)
    {
        $stockCurrentQty = str_replace(',', '.', $stockCurrentQty);
        $this->stockCurrentQty = $stockCurrentQty;

        return $this;
    }

    /**
     * Get stockCurrentQty
     *
     * @return float
     */
    public function getStockCurrentQty()
    {
        return $this->stockCurrentQty;
    }

    /**
     * Product constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->lossLine = new ArrayCollection();
        $this->syncCmdQueues = new ArrayCollection();
        $this->restaurants = new ArrayCollection();
        $this->products = new ArrayCollection();

        $this->addNameTranslation('fr', '');
        $this->addNameTranslation('nl', '');
    }

    /**
     * Add lossLine
     *
     * @param \AppBundle\Merchandise\Entity\LossLine $lossLine
     *
     * @return ProductSupervision
     */
    public function addLossLine(LossLine $lossLine)
    {
        $this->lossLine[] = $lossLine;

        return $this;
    }

    /**
     * Remove lossLine
     *
     * @param \AppBundle\Merchandise\Entity\LossLine $lossLine
     */
    public function removeLossLine(LossLine $lossLine)
    {
        $this->lossLine->removeElement($lossLine);
    }

    /**
     * Get lossLine
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLossLine()
    {
        return $this->lossLine;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param $active
     * @return ProductSupervision
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    public function __toString()
    {
        return sprintf("%s - %s", $this->getId(), $this->getName());
    }

    public function modifyStock($variation)
    {
        $currentStock = ($this->getStockCurrentQty() !== null) ? $this->getStockCurrentQty() : 0;
        $this->setStockCurrentQty($currentStock + $variation);
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
     * @return array
     */
    public static function getUnitsLabel()
    {
        return self::$unitsLabel;
    }

    /**
     * @param array $unitsLabel
     * @return ProductSupervision
     */
    public static function setUnitsLabel($unitsLabel)
    {
        self::$unitsLabel = $unitsLabel;
    }

    /**
     * @return mixed
     */
    public function getGlobalProductID()
    {
        return $this->globalProductID;
    }

    /**
     * @param mixed $globalProductID
     * @return ProductSupervision
     */
    public function setGlobalProductID($globalProductID)
    {
        $this->globalProductID = $globalProductID;

        return $this;
    }


    /**
     * Set eligibleForOptikitchen
     *
     * @param boolean $eligibleForOptikitchen
     *
     * @return ProductSupervision
     */
    public function setEligibleForOptikitchen($eligibleForOptikitchen)
    {
        $this->eligibleForOptikitchen = $eligibleForOptikitchen;

        return $this;
    }

    /**
     * Get eligibleForOptikitchen
     *
     * @return boolean
     */
    public function getEligibleForOptikitchen()
    {
        return $this->eligibleForOptikitchen;
    }

    /**
     * Remove translation
     *
     * @param \AppBundle\Merchandise\Entity\Translation\ProductTranslation $translation
     */
    public function removeTranslation(\AppBundle\Merchandise\Entity\Translation\ProductTranslation $translation)
    {
        $this->translations->removeElement($translation);
    }

    /**
     * Set dateSynchro
     *
     * @param  \DateTime $dateSynchro
     * @return ProductSupervision
     */
    public function setDateSynchro($dateSynchro)
    {
        $this->dateSynchro = $dateSynchro;

        return $this;
    }

    /**
     * Get dateSynchro
     *
     * @return \DateTime
     */
    public function getDateSynchro()
    {
        return $this->dateSynchro;
    }

    /**
     * @return mixed
     */
    public function getisSynchronized()
    {
        return $this->isSynchronized;
    }

    /**
     * @param mixed $isSynchronized
     */
    public function setIsSynchronized($isSynchronized)
    {
        $this->isSynchronized = $isSynchronized;
    }

    /**
     * @return \DateTime
     */
    public function getLastDateSynchro()
    {
        return $this->lastDateSynchro;
    }

    /**
     * @param \DateTime $lastDateSynchro
     * @return ProductSupervision
     */
    public function setLastDateSynchro($lastDateSynchro)
    {
        $this->lastDateSynchro = $lastDateSynchro;

        return $this;
    }

    public function getSyncCmdQueues()
    {
        return $this->syncCmdQueues;
    }

    public function addSyncCmdQueues(SyncCmdQueue $syncCmdQueue)
    {
        $this->syncCmdQueues->add($syncCmdQueue);
    }

    public function deleteSyncCmdQueues(SyncCmdQueue $syncCmdQueue)
    {
        $this->syncCmdQueues->removeElement($syncCmdQueue);
    }

    /**
     * @return mixed
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param mixed $product
     */
    public function setProducts($products)
    {
        $this->products = $products;
    }

    public function addProduct(Product $product)
    {
        $this->products->add($product);
    }

    public function removeProduct(Product $product)
    {
        $this->products->removeElement($product);
    }

    /**
     * @return mixed
     */
    public function getRestaurants()
    {
        return $this->restaurants;
    }

    /**
     * @param mixed $restaurants
     */
    public function setRestaurants($restaurants)
    {
        $this->restaurants = $restaurants;
    }

    public function addRestaurant(Restaurant $restaurant)
    {
        $this->restaurants->add($restaurant);
    }

    public function removeRestaurant(Restaurant $restaurant)
    {
        $this->restaurants->removeElement($restaurant);
    }
}
