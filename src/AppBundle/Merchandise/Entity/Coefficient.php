<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\ORM\Mapping as ORM;

/**
 * Coefficient
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Coefficient
{

    use ImportIdTrait;

    const TYPE_THEO = 'theo';
    const TYPE_REAL = 'real';

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
     * @ORM\Column(name="hebTheo", type="float",nullable=true)
     */
    private $hebTheo;

    /**
     * @var float
     *
     * @ORM\Column(name="coef", type="float",nullable=true)
     */
    private $coef;

    /**
     * @var float
     *
     * @ORM\Column(name="hebReal", type="float",nullable=true)
     */
    private $hebReal;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20,nullable=true)
     */
    private $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="fixed", type="boolean",nullable=true)
     */
    private $fixed;

    /**
     * @var float
     *
     * @ORM\Column(name="real_stock", type="float",nullable=true)
     */
    private $realStock;

    /**
     * @var float
     *
     * @ORM\Column(name="theo_stock", type="float",nullable=true)
     */
    private $theoStock;

    /**
     * @var CoefBase
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\CoefBase",inversedBy="coefs")
     */
    private $base;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\Product")
     */
    private $product;

    /**
     * @var boolean
     * @ORM\Column(name="stock_final_exist",type="boolean",nullable=true,options={"default"=true})
     */
    private $stockFinalExist;

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
     * Set hebTheo
     *
     * @param float $hebTheo
     *
     * @return Coefficient
     */
    public function setHebTheo($hebTheo)
    {
        $this->hebTheo = $hebTheo;

        return $this;
    }

    /**
     * Get hebTheo
     *
     * @return float
     */
    public function getHebTheo()
    {
        return $this->hebTheo;
    }

    /**
     * Set coef
     *
     * @param float $coef
     *
     * @return Coefficient
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
     * Set hebReal
     *
     * @param float $hebReal
     *
     * @return Coefficient
     */
    public function setHebReal($hebReal)
    {
        $this->hebReal = $hebReal;

        return $this;
    }

    /**
     * Get hebReal
     *
     * @return float
     */
    public function getHebReal()
    {
        return $this->hebReal;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Coefficient
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set fixed
     *
     * @param boolean $fixed
     *
     * @return Coefficient
     */
    public function setFixed($fixed)
    {
        $this->fixed = $fixed;

        return $this;
    }

    /**
     * Get fixed
     *
     * @return boolean
     */
    public function getFixed()
    {
        return $this->fixed;
    }

    /**
     * Set base
     *
     * @param \AppBundle\Merchandise\Entity\CoefBase $base
     *
     * @return Coefficient
     */
    public function setBase(\AppBundle\Merchandise\Entity\CoefBase $base = null)
    {
        $this->base = $base;

        return $this;
    }

    /**
     * Get base
     *
     * @return \AppBundle\Merchandise\Entity\CoefBase
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * Set product
     *
     * @param \AppBundle\Merchandise\Entity\Product $product
     *
     * @return Coefficient
     */
    public function setProduct(\AppBundle\Merchandise\Entity\Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \AppBundle\Merchandise\Entity\Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set stockFinalExist
     *
     * @param boolean $stockFinalExist
     *
     * @return Coefficient
     */
    public function setStockFinalExist($stockFinalExist)
    {
        $this->stockFinalExist = $stockFinalExist;

        return $this;
    }

    /**
     * Get stockFinalExist
     *
     * @return boolean
     */
    public function getStockFinalExist()
    {
        return $this->stockFinalExist;
    }

    /**
     * Set realStock
     *
     * @param float $realStock
     *
     * @return Coefficient
     */
    public function setRealStock($realStock)
    {
        $this->realStock = $realStock;

        return $this;
    }

    /**
     * Get realStock
     *
     * @return float
     */
    public function getRealStock()
    {
        return $this->realStock;
    }

    /**
     * Set theoStock
     *
     * @param float $theoStock
     *
     * @return Coefficient
     */
    public function setTheoStock($theoStock)
    {
        $this->theoStock = $theoStock;

        return $this;
    }

    /**
     * Get theoStock
     *
     * @return float
     */
    public function getTheoStock()
    {
        return $this->theoStock;
    }
}
