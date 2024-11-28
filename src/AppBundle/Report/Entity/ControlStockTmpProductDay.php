<?php

namespace AppBundle\Report\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ControlStockTmpProductDay
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class ControlStockTmpProductDay
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
     * @var ControlStockTmpProduct
     * @ORM\ManyToOne(targetEntity="ControlStockTmpProduct",inversedBy="days")
     */
    private $productTmp;

    /**
     * @var ControlStockTmpProduct
     * @ORM\ManyToOne(targetEntity="ControlStockTmpDay",inversedBy="products")
     */
    private $day;

    /**
     * @var float
     * @ORM\Column(name="need",type="float",nullable=true)
     */
    private $need;

    /**
     * @var float
     * @ORM\Column(name="liv",type="float",nullable=true)
     */
    private $liv;

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
     * Set productTmp
     *
     * @param ControlStockTmpProduct $productTmp
     *
     * @return ControlStockTmpProductDay
     */
    public function setProductTmp(ControlStockTmpProduct $productTmp = null)
    {
        $this->productTmp = $productTmp;

        return $this;
    }

    /**
     * Get productTmp
     *
     * @return ControlStockTmpProduct
     */
    public function getProductTmp()
    {
        return $this->productTmp;
    }

    /**
     * Set day
     *
     * @param ControlStockTmpDay $day
     *
     * @return ControlStockTmpProductDay
     */
    public function setDay(ControlStockTmpDay $day = null)
    {
        $this->day = $day;

        return $this;
    }

    /**
     * Get day
     *
     * @return ControlStockTmpDay
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * Set need
     *
     * @param float $need
     *
     * @return ControlStockTmpProductDay
     */
    public function setNeed($need)
    {
        $this->need = $need;

        return $this;
    }

    /**
     * Get need
     *
     * @return float
     */
    public function getNeed()
    {
        return $this->need;
    }

    /**
     * Set liv
     *
     * @param float $liv
     *
     * @return ControlStockTmpProductDay
     */
    public function setLiv($liv)
    {
        $this->liv = $liv;

        return $this;
    }

    /**
     * Get liv
     *
     * @return float
     */
    public function getLiv()
    {
        return $this->liv;
    }
}
