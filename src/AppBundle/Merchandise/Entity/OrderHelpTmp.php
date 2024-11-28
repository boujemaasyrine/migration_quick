<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * OrderHelpTmp
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class OrderHelpTmp
{

    use TimestampableTrait;
    use OriginRestaurantTrait;

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
     * @ORM\Column(name="week", type="smallint")
     */
    private $week;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startDateLastWeek", type="date")
     */
    private $startDateLastWeek;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endDateLastWeek", type="date")
     */
    private $endDateLastWeek;

    /**
     * @var float
     *
     * @ORM\Column(name="ca", type="float",nullable=true)
     */
    private $ca;

    /**
     * @var $suppliers
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\OrderHelpSupplier",mappedBy="orderHelp",cascade={"remove"})
     */
    private $suppliers;

    /**
     * @var $products
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\OrderHelpProducts",mappedBy="orderHelp",cascade={"remove"})
     */
    private $products;

    /**
     * @var $products
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\OrderHelpMaskProduct",mappedBy="orderHelp",cascade={"remove"})
     */
    private $helpMaskProducts;

    /**
     * @var Employee
     * @ORM\ManyToOne(targetEntity="AppBundle\Staff\Entity\Employee")
     */
    private $createdBy;

    /**
     * @var
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\OrderHelpMask",mappedBy="helpTmp")
     */
    private $masks;

    /**
     * @var array
     * @ORM\Column(name="generated_couples",type="array",nullable=true)
     * will contains the genrations of orders format supplierID/NumberDay
     */
    private $generatedCouples;

    /**
     * @var bool
     * @ORM\Column(name="locked",type="boolean",nullable=true)
     */
    private $locked = false;

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
     * Set week
     *
     * @param integer $week
     *
     * @return OrderHelpTmp
     */
    public function setWeek($week)
    {
        $this->week = $week;

        return $this;
    }

    /**
     * Get week
     *
     * @return integer
     */
    public function getWeek()
    {
        return $this->week;
    }

    /**
     * Set startDateLastWeek
     *
     * @param \DateTime $startDateLastWeek
     *
     * @return OrderHelpTmp
     */
    public function setStartDateLastWeek($startDateLastWeek)
    {
        $this->startDateLastWeek = $startDateLastWeek;

        return $this;
    }

    /**
     * Get startDateLastWeek
     *
     * @return \DateTime
     */
    public function getStartDateLastWeek()
    {
        return $this->startDateLastWeek;
    }

    /**
     * Set endDateLastWeek
     *
     * @param \DateTime $endDateLastWeek
     *
     * @return OrderHelpTmp
     */
    public function setEndDateLastWeek($endDateLastWeek)
    {
        $this->endDateLastWeek = $endDateLastWeek;

        return $this;
    }

    /**
     * Get endDateLastWeek
     *
     * @return \DateTime
     */
    public function getEndDateLastWeek()
    {
        return $this->endDateLastWeek;
    }

    /**
     * Set ca
     *
     * @param float $ca
     *
     * @return OrderHelpTmp
     */
    public function setCa($ca)
    {
        $this->ca = $ca;

        return $this;
    }

    /**
     * Get ca
     *
     * @return float
     */
    public function getCa()
    {
        return $this->ca;
    }

    /**
     * Set createdBy
     *
     * @param \AppBundle\Staff\Entity\Employee $createdBy
     *
     * @return OrderHelpTmp
     */
    public function setCreatedBy(\AppBundle\Staff\Entity\Employee $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \AppBundle\Staff\Entity\Employee
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->suppliers = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add supplier
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpSupplier $supplier
     *
     * @return OrderHelpTmp
     */
    public function addSupplier(\AppBundle\Merchandise\Entity\OrderHelpSupplier $supplier)
    {
        $this->suppliers[] = $supplier;

        return $this;
    }

    /**
     * Remove supplier
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpSupplier $supplier
     */
    public function removeSupplier(\AppBundle\Merchandise\Entity\OrderHelpSupplier $supplier)
    {
        $this->suppliers->removeElement($supplier);
    }

    /**
     * Get suppliers
     *
     * @return OrderHelpSupplier[]
     */
    public function getSuppliers()
    {
        return $this->suppliers;
    }

    /**
     * Add product
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpProducts $product
     *
     * @return OrderHelpTmp
     */
    public function addProduct(\AppBundle\Merchandise\Entity\OrderHelpProducts $product)
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpProducts $product
     */
    public function removeProduct(\AppBundle\Merchandise\Entity\OrderHelpProducts $product)
    {
        $this->products->removeElement($product);
    }

    /**
     * Get products
     *
     * @return OrderHelpProducts[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Add mask
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpMask $mask
     *
     * @return OrderHelpTmp
     */
    public function addMask(\AppBundle\Merchandise\Entity\OrderHelpMask $mask)
    {
        $this->masks[] = $mask;

        return $this;
    }

    /**
     * Remove mask
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpMask $mask
     */
    public function removeMask(\AppBundle\Merchandise\Entity\OrderHelpMask $mask)
    {
        $this->masks->removeElement($mask);
    }

    /**
     * Get masks
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMasks()
    {
        return $this->masks;
    }

    /**
     * Set generatedCouples
     *
     * @param array $generatedCouples
     *
     * @return OrderHelpTmp
     */
    public function setGeneratedCouples($generatedCouples)
    {
        $this->generatedCouples = $generatedCouples;

        return $this;
    }

    /**
     * Get generatedCouples
     *
     * @return array
     */
    public function getGeneratedCouples()
    {
        if ($this->generatedCouples == null) {
            $this->generatedCouples = [];
        }

        return $this->generatedCouples;
    }

    public function addGeneratedCouples($couple)
    {
        if ($this->generatedCouples == null) {
            $this->generatedCouples = [];
        }
        $this->generatedCouples[] = $couple;
    }

    /**
     * Set locked
     *
     * @param boolean $locked
     *
     * @return OrderHelpTmp
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked
     *
     * @return boolean
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Add helpMaskproduct
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpMaskProduct $helpMaskproduct
     *
     * @return OrderHelpTmp
     */
    public function addHelpMaskProduct(\AppBundle\Merchandise\Entity\OrderHelpMaskProduct $helpMaskproduct)
    {
        $this->helpMaskProducts[] = $helpMaskproduct;

        return $this;
    }

    /**
     * Remove helpMaskproduct
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpMaskProduct $helpMaskproduct
     */
    public function removeHelpMaskProduct(\AppBundle\Merchandise\Entity\OrderHelpMaskProduct $helpMaskproduct)
    {
        $this->helpMaskProducts->removeElement($helpMaskproduct);
    }

    /**
     * Get helpMaskproducts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHelpMaskProducts()
    {
        return $this->helpMaskProducts;
    }
}
