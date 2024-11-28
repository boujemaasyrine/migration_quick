<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\Merchandise\Entity\Translation\DivisionTranslation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Division
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\DivisionRepository")
 * @UniqueEntity("name")
 * @Gedmo\TranslationEntity(class="AppBundle\Merchandise\Entity\Translation\DivisionTranslation")
 */
class Division
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
     * @var integer
     *
     * @ORM\Column(name="external_id", type="integer")
     */
    private $externalId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Gedmo\Translatable
     */
    private $name;

    /**
     * @var Product
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\ProductSold", mappedBy="division", fetch="EAGER")
     */
    private $products;

    /**
     * @var string
     * @ORM\Column(name="tax_letter", type="string")
     */
    private $taxLetter;

    /**
     * @var float
     * @ORM\Column(name="tva", type="float")
     */
    private $tva;

    /**
     * @var string
     * @ORM\Column(name="special_tax_letter", type="string")
     */
    private $specialTaxLetter;

    /**
     * @var float
     * @ORM\Column(name="special_tva", type="float")
     */
    private $specialTva;

    /**
     * @ORM\OneToMany(
     *   targetEntity="AppBundle\Merchandise\Entity\Translation\DivisionTranslation",
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
     * @param DivisionTranslation $t
     */
    public function addTranslation(DivisionTranslation $t)
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
             * @var DivisionTranslation $translation
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
            /**
             * @var DivisionTranslation $t
             */
            if ($t->getLocale() == $locale) {
                $exist = true;
                $t->setContent($value);
            }
        }
        if (!$exist) {
            $translation = new DivisionTranslation($locale, 'name', $value);
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
     * @return Division
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
     * Constructor
     */
    public function __construct()
    {
        $this->products = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add product
     *
     * @param \AppBundle\Merchandise\Entity\ProductSold $product
     *
     * @return ProductCategories
     */
    public function addProduct(\AppBundle\Merchandise\Entity\ProductSold $product)
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product
     *
     * @param \AppBundle\Merchandise\Entity\ProductSold $product
     */
    public function removeProduct(\AppBundle\Merchandise\Entity\ProductSold $product)
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
     * @return int
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @param $externalId
     * @return $this
     */
    public function setExternalId($externalId)
    {
        $this->externalId = $externalId;

        return $this;
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
        $this->tva = $tva;

        return $this;
    }

    /**
     * @return string
     */
    public function getSpecialTaxLetter()
    {
        return $this->specialTaxLetter;
    }

    /**
     * @param $specialTaxLetter
     * @return Division
     */
    public function setSpecialTaxLetter($specialTaxLetter)
    {
        $this->specialTaxLetter = $specialTaxLetter;

        return $this;
    }

    /**
     * @return float
     */
    public function getSpecialTva()
    {
        return $this->specialTva;
    }

    /**
     * @param $specialTva
     * @return Division
     */
    public function setSpecialTva($specialTva)
    {
        $this->specialTva = $specialTva;

        return $this;
    }
}
