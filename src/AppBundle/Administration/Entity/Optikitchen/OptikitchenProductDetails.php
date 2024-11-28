<?php

namespace AppBundle\Administration\Entity\Optikitchen;

use Doctrine\ORM\Mapping as ORM;

/**
 * OptikitchenProductDetails
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class OptikitchenProductDetails
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
     * @ORM\Column(name="t1", type="datetime",nullable=true)
     */
    private $t1;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="t2", type="datetime",nullable=true)
     */
    private $t2;

    /**
     * @var float
     *
     * @ORM\Column(name="ca", type="float",nullable=true)
     */
    private $ca;

    /**
     * @var float
     *
     * @ORM\Column(name="conso", type="float",nullable=true)
     */
    private $conso;

    /**
     * @var float
     *
     * @ORM\Column(name="bud", type="float",nullable=true)
     */
    private $bud;

    /**
     * @var float
     *
     * @ORM\Column(name="coef", type="float",nullable=true)
     */
    private $coef;

    /**
     * @var integer
     *
     * @ORM\Column(name="binQty", type="integer",nullable=true)
     */
    private $binQty;

    /**
     * @var
     * @ORM\ManyToOne(targetEntity="AppBundle\Administration\Entity\Optikitchen\OptikitchenProduct",inversedBy="details")
     */
    private $optiProduct;

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
     * Set t1
     *
     * @param \DateTime $t1
     *
     * @return OptikitchenProductDetails
     */
    public function setT1($t1)
    {
        $this->t1 = $t1;

        return $this;
    }

    /**
     * Get t1
     *
     * @return \DateTime
     */
    public function getT1()
    {
        return $this->t1;
    }

    /**
     * Set t2
     *
     * @param \DateTime $t2
     *
     * @return OptikitchenProductDetails
     */
    public function setT2($t2)
    {
        $this->t2 = $t2;

        return $this;
    }

    /**
     * Get t2
     *
     * @return \DateTime
     */
    public function getT2()
    {
        return $this->t2;
    }

    /**
     * Set ca
     *
     * @param float $ca
     *
     * @return OptikitchenProductDetails
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
     * Set conso
     *
     * @param float $conso
     *
     * @return OptikitchenProductDetails
     */
    public function setConso($conso)
    {
        $this->conso = $conso;

        return $this;
    }

    /**
     * Get conso
     *
     * @return float
     */
    public function getConso()
    {
        return $this->conso;
    }

    /**
     * Set coef
     *
     * @param float $coef
     *
     * @return OptikitchenProductDetails
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
     * Set binQty
     *
     * @param integer $binQty
     *
     * @return OptikitchenProductDetails
     */
    public function setBinQty($binQty)
    {
        $this->binQty = $binQty;

        return $this;
    }

    /**
     * Get binQty
     *
     * @return integer
     */
    public function getBinQty()
    {
        return $this->binQty;
    }

    /**
     * Set optiProduct
     *
     * @param \AppBundle\Administration\Entity\Optikitchen\OptikitchenProduct $optiProduct
     *
     * @return OptikitchenProductDetails
     */
    public function setOptiProduct(OptikitchenProduct $optiProduct = null)
    {
        $this->optiProduct = $optiProduct;

        return $this;
    }

    /**
     * Get optiProduct
     *
     * @return \AppBundle\Administration\Entity\Optikitchen\OptikitchenProduct
     */
    public function getOptiProduct()
    {
        return $this->optiProduct;
    }

    /**
     * Set bud
     *
     * @param float $bud
     *
     * @return OptikitchenProductDetails
     */
    public function setBud($bud)
    {
        $this->bud = $bud;

        return $this;
    }

    /**
     * Get bud
     *
     * @return float
     */
    public function getBud()
    {
        return $this->bud;
    }

    public function getQuartCount()
    {
        $t1 = $this->getT1()->getTimestamp();
        $t2 = $this->getT2()->getTimestamp();

        $diff = $t2 - $t1;

        $n = $diff / (15 * 60);

        return $n;
    }

    public function getCoefPerFifteenMin()
    {

        if ($this->getCoef() || $this->getOptiProduct()->getCoef()) {
            if ($this->getOptiProduct()->getCoef() != null) {
                return $this->getOptiProduct()->getCoef();
            } else {
                return $this->getCoef();
            }
        } else {
            return 0;
        }
    }
}
