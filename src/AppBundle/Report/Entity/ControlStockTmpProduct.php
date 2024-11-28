<?php

namespace AppBundle\Report\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ControlStockTmpProduct
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ControlStockTmpProduct
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
     * @var float
     *
     * @ORM\Column(name="coef", type="float")
     */
    private $coef;

    /**
     * @var float
     *
     * @ORM\Column(name="stock", type="float")
     */
    private $stock;

    /**
     * @var
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased")
     */
    private $product;

    /**
     * @var ControlStockTmpProductDay
     * @ORM\OneToMany(targetEntity="ControlStockTmpProductDay",mappedBy="productTmp",cascade={"remove"})
     */
    private $days;

    /**
     * @var string
     * @ORM\Column(name="stock_type",type="string",length=6,nullable=true)
     */
    private $stockType;

    /**
     * @var float
     * @ORM\Column(name="conso_theo",type="float",nullable=true)
     */
    private $consoTheo;

    /**
     * @var float
     * @ORM\Column(name="conso_real",type="float",nullable=true)
     */
    private $consoReal;

    /**
     * @var ControlStockTmp
     * @ORM\ManyToOne(targetEntity="AppBundle\Report\Entity\ControlStockTmp",inversedBy="products")
     */
    private $tmp;

    /**
     * @var integer
     * @ORM\Column(name="orderr",type="integer",nullable=true)
     */
    private $order;

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
     * Set coef
     *
     * @param float $coef
     *
     * @return ControlStockTmpProduct
     */
    public function setCoef($coef)
    {
        $this->coef = $coef;

        return $this;
    }

    /**
     * Get coef
     *
     * @return float
     */
    public function getCoef()
    {
        return $this->coef;
    }

    /**
     * Set stock
     *
     * @param float $stock
     *
     * @return ControlStockTmpProduct
     */
    public function setStock($stock)
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * Get stock
     *
     * @return float
     */
    public function getStock()
    {
        return $this->stock;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->days = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add day
     *
     * @param ControlStockTmpProductDay $day
     *
     * @return ControlStockTmpProduct
     */
    public function addDay(ControlStockTmpProductDay $day)
    {
        $this->days[] = $day;

        return $this;
    }

    /**
     * Remove day
     *
     * @param ControlStockTmpProductDay $day
     */
    public function removeDay(ControlStockTmpProductDay $day)
    {
        $this->days->removeElement($day);
    }

    /**
     * Get days
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDays()
    {
        $days = $this->days->toArray();

        usort(
            $days,
            function (ControlStockTmpProductDay $d1, ControlStockTmpProductDay $d2) {
                if ($d1->getDay()->getDate()->format('Ymd') < $d2->getDay()->getDate()->format('Ymd')) {
                    return -1;
                } else {
                    return 1;
                }
            }
        );

        return $days;
    }

    /**
     * Set product
     *
     * @param \AppBundle\Merchandise\Entity\ProductPurchased $product
     *
     * @return ControlStockTmpProduct
     */
    public function setProduct(\AppBundle\Merchandise\Entity\ProductPurchased $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \AppBundle\Merchandise\Entity\ProductPurchased
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set stockType
     *
     * @param string $stockType
     *
     * @return ControlStockTmpProduct
     */
    public function setStockType($stockType)
    {
        $this->stockType = $stockType;

        return $this;
    }

    /**
     * Get stockType
     *
     * @return string
     */
    public function getStockType()
    {
        return $this->stockType;
    }

    /**
     * Set consoTheo
     *
     * @param float $consoTheo
     *
     * @return ControlStockTmpProduct
     */
    public function setConsoTheo($consoTheo)
    {
        $this->consoTheo = $consoTheo;

        return $this;
    }

    /**
     * Get consoTheo
     *
     * @return float
     */
    public function getConsoTheo()
    {
        return $this->consoTheo;
    }

    /**
     * Set consoReal
     *
     * @param float $consoReal
     *
     * @return ControlStockTmpProduct
     */
    public function setConsoReal($consoReal)
    {
        $this->consoReal = $consoReal;

        return $this;
    }

    /**
     * Get consoReal
     *
     * @return float
     */
    public function getConsoReal()
    {
        return $this->consoReal;
    }

    /**
     * Set tmp
     *
     * @param \AppBundle\Report\Entity\ControlStockTmp $tmp
     *
     * @return ControlStockTmpProduct
     */
    public function setTmp(\AppBundle\Report\Entity\ControlStockTmp $tmp = null)
    {
        $this->tmp = $tmp;

        return $this;
    }

    /**
     * Get tmp
     *
     * @return \AppBundle\Report\Entity\ControlStockTmp
     */
    public function getTmp()
    {
        return $this->tmp;
    }

    public function getTotalLiv()
    {
        $liv = 0;
        foreach ($this->getDays() as $d) {
            $liv += ($d->getLiv()) ? $d->getLiv() : 0;
        }

        return $liv;
    }

    public function getTotalNeed()
    {
        if ($this->getCoef() != 0) {
            $need = $this->getTmp()->getTotalCaPrev() / $this->getCoef();
        } else {
            $need = 0;
        }

        return $need;
    }

    /**
     * Set order
     *
     * @param integer $order
     *
     * @return ControlStockTmpProduct
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get order
     *
     * @return integer
     */
    public function getOrder()
    {
        return $this->order;
    }
}
