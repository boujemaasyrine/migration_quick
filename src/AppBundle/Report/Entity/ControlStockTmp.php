<?php

namespace AppBundle\Report\Entity;

use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * ControlStockTmp
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class ControlStockTmp
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
     * @var
     * @ORM\Column(name="start_date",type="date",nullable=true)
     */
    private $startDate;

    /**
     * @var
     * @ORM\Column(name="end_date",type="date",nullable=true)
     */
    private $endDate;

    /**
     * @var
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\SheetModel", inversedBy="controlStocks")
     */
    private $sheet;

    /**
     * @var ControlStockTmpDay
     * @ORM\OneToMany(targetEntity="ControlStockTmpDay",mappedBy="controlStockTmp",cascade={"remove"})
     */
    private $days;

    /**
     * @var ControlStockTmpProduct
     * @ORM\OneToMany(targetEntity="ControlStockTmpProduct",mappedBy="tmp",cascade={"remove"})
     */
    private $products;

    /**
     * @var float
     * @ORM\Column(name="ca",type="float",nullable=true)
     */
    private $ca;

    /**
     * @var
     * @ORM\Column(name="d1",type="date",nullable=true)
     */
    private $d1;

    /**
     * @var
     * @ORM\Column(name="d2",type="date",nullable=true)
     */
    private $d2;

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
     * Constructor
     */
    public function __construct()
    {
        $this->days = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add day
     *
     * @param ControlStockTmpDay $day
     *
     * @return ControlStockTmp
     */
    public function addDay(ControlStockTmpDay $day)
    {
        $this->days[] = $day;

        return $this;
    }

    /**
     * Remove day
     *
     * @param ControlStockTmpDay $day
     */
    public function removeDay(ControlStockTmpDay $day)
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
            function (ControlStockTmpDay $d1, ControlStockTmpDay $d2) {
                if ($d1->getDate()->format('Ymd') < $d2->getDate()->format('Ymd')) {
                    return -1;
                } else {
                    return 1;
                }
            }
        );

        return $days;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return ControlStockTmp
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return ControlStockTmp
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set sheet
     *
     * @param \AppBundle\Merchandise\Entity\SheetModel $sheet
     *
     * @return ControlStockTmp
     */
    public function setSheet(\AppBundle\Merchandise\Entity\SheetModel $sheet = null)
    {
        $this->sheet = $sheet;

        return $this;
    }

    /**
     * Get sheet
     *
     * @return \AppBundle\Merchandise\Entity\SheetModel
     */
    public function getSheet()
    {
        return $this->sheet;
    }

    /**
     * Add product
     *
     * @param \AppBundle\Report\Entity\ControlStockTmpProduct $product
     *
     * @return ControlStockTmp
     */
    public function addProduct(\AppBundle\Report\Entity\ControlStockTmpProduct $product)
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product
     *
     * @param \AppBundle\Report\Entity\ControlStockTmpProduct $product
     */
    public function removeProduct(\AppBundle\Report\Entity\ControlStockTmpProduct $product)
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
        $products = $this->products->toArray();
        usort(
            $products,
            function (ControlStockTmpProduct $p1, ControlStockTmpProduct $p2) {
                if ($p1->getOrder() < $p2->getOrder()) {
                    return -1;
                } else {
                    return 1;
                }
            }
        );

        return $products;
    }

    public function getTotalCaPrev()
    {
        $ca = 0;
        foreach ($this->getDays() as $d) {
            $ca = $ca + $d->getCaPrev();
        }

        return $ca;
    }

    /**
     * Set ca
     *
     * @param float $ca
     *
     * @return ControlStockTmp
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
     * Set d1
     *
     * @param \DateTime $d1
     *
     * @return ControlStockTmp
     */
    public function setD1($d1)
    {
        $this->d1 = $d1;

        return $this;
    }

    /**
     * Get d1
     *
     * @return \DateTime
     */
    public function getD1()
    {
        return $this->d1;
    }

    /**
     * Set d2
     *
     * @param \DateTime $d2
     *
     * @return ControlStockTmp
     */
    public function setD2($d2)
    {
        $this->d2 = $d2;

        return $this;
    }

    /**
     * Get d2
     *
     * @return \DateTime
     */
    public function getD2()
    {
        return $this->d2;
    }
}
