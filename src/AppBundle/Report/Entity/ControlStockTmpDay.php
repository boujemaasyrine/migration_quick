<?php

namespace AppBundle\Report\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ControlStockTmpDay
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ControlStockTmpDay
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
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var float
     *
     * @ORM\Column(name="caPrev", type="float")
     */
    private $caPrev;

    /**
     * @var float
     *
     * @ORM\Column(name="caPrevCum", type="float")
     */
    private $caPrevCum;

    /**
     * @var ControlStockTmp
     * @ORM\ManyToOne(targetEntity="ControlStockTmp",inversedBy="days")
     */
    private $controlStockTmp;

    /**
     * @var ControlStockTmpProductDay
     * @ORM\OneToMany(targetEntity="ControlStockTmpProduct",mappedBy="day")
     */
    private $products;

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
     * Set date
     *
     * @param \DateTime $date
     *
     * @return ControlStockTmpDay
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set caPrev
     *
     * @param float $caPrev
     *
     * @return ControlStockTmpDay
     */
    public function setCaPrev($caPrev)
    {
        $this->caPrev = $caPrev;

        return $this;
    }

    /**
     * Get caPrev
     *
     * @return float
     */
    public function getCaPrev()
    {
        return $this->caPrev;
    }

    /**
     * Set caPrevCum
     *
     * @param float $caPrevCum
     *
     * @return ControlStockTmpDay
     */
    public function setCaPrevCum($caPrevCum)
    {
        $this->caPrevCum = $caPrevCum;

        return $this;
    }

    /**
     * Get caPrevCum
     *
     * @return float
     */
    public function getCaPrevCum()
    {
        return $this->caPrevCum;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->products = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Add product
     *
     * @param ControlStockTmpProductDay $product
     *
     * @return ControlStockTmpDay
     */
    public function addProduct(ControlStockTmpProductDay $product)
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product
     *
     * @param ControlStockTmpProductDay $product
     */
    public function removeProduct(ControlStockTmpProductDay $product)
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
     * Set controlStockTmp
     *
     * @param \AppBundle\Report\Entity\ControlStockTmp $controlStockTmp
     *
     * @return ControlStockTmpDay
     */
    public function setControlStockTmp(\AppBundle\Report\Entity\ControlStockTmp $controlStockTmp = null)
    {
        $this->controlStockTmp = $controlStockTmp;

        return $this;
    }

    /**
     * Get controlStockTmp
     *
     * @return \AppBundle\Report\Entity\ControlStockTmp
     */
    public function getControlStockTmp()
    {
        return $this->controlStockTmp;
    }
}
