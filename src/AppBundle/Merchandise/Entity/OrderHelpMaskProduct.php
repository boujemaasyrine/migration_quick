<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 11/05/2016
 * Time: 10:01
 */

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\Mapping as ORM;

/**
 * OrderHelpMask
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class OrderHelpMaskProduct
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
     * @ORM\Column(name="lp", type="float",nullable=true)
     */
    private $lp;

    /**
     * @var integer
     *
     * @ORM\Column(name="qtyToBeOrdred", type="float",nullable=true)
     */
    private $qtyToBeOrdred;

    /**
     * @var OrderHelpMask
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\OrderHelpMask",inversedBy="products")
     */
    private $mask;

    /**
     * @var OrderHelpMask
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\OrderHelpSupplier",inversedBy="helpMaskProducts")
     */
    private $supplier;

    /**
     * @var $orderHelp
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\OrderHelpTmp",inversedBy="helpMaskProducts")
     */
    private $orderHelp;

    /**
     * @var float
     * @ORM\Column(name="need",type="float",nullable=true)
     */
    private $need;

    /**
     * @var OrderHelpProducts
     * @ORM\ManyToOne(targetEntity="AppBundle\Merchandise\Entity\OrderHelpProducts",inversedBy="helpMaskProducts")
     */
    private $helpProduct;


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
     * Set lp
     *
     * @param float $lp
     *
     * @return OrderHelpMaskProduct
     */
    public function setLp($lp)
    {
        $this->lp = $lp;

        return $this;
    }

    /**
     * Get lp
     *
     * @return float
     */
    public function getLp()
    {
        return $this->lp;
    }

    /**
     * Set qtyToBeOrdred
     *
     * @param float $qtyToBeOrdred
     *
     * @return OrderHelpMaskProduct
     */
    public function setQtyToBeOrdred($qtyToBeOrdred)
    {
        $this->qtyToBeOrdred = $qtyToBeOrdred;

        return $this;
    }

    /**
     * Get qtyToBeOrdred
     *
     * @return float
     */
    public function getQtyToBeOrdred()
    {
        return $this->qtyToBeOrdred;
    }

    /**
     * Set need
     *
     * @param float $need
     *
     * @return OrderHelpMaskProduct
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
     * Set mask
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpMask $mask
     *
     * @return OrderHelpMaskProduct
     */
    public function setMask(\AppBundle\Merchandise\Entity\OrderHelpMask $mask = null)
    {
        $this->mask = $mask;

        return $this;
    }

    /**
     * Get mask
     *
     * @return \AppBundle\Merchandise\Entity\OrderHelpMask
     */
    public function getMask()
    {
        return $this->mask;
    }

    /**
     * Set orderHelp
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpTmp $orderHelp
     *
     * @return OrderHelpMaskProduct
     */
    public function setOrderHelp(\AppBundle\Merchandise\Entity\OrderHelpTmp $orderHelp = null)
    {
        $this->orderHelp = $orderHelp;

        return $this;
    }

    /**
     * Get orderHelp
     *
     * @return \AppBundle\Merchandise\Entity\OrderHelpTmp
     */
    public function getOrderHelp()
    {
        return $this->orderHelp;
    }

    /**
     * Set helpProduct
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpProducts $helpProduct
     *
     * @return OrderHelpMaskProduct
     */
    public function setHelpProduct(\AppBundle\Merchandise\Entity\OrderHelpProducts $helpProduct = null)
    {
        $this->helpProduct = $helpProduct;

        return $this;
    }

    /**
     * Get helpProduct
     *
     * @return \AppBundle\Merchandise\Entity\OrderHelpProducts
     */
    public function getHelpProduct()
    {
        return $this->helpProduct;
    }

    /**
     * Set supplier
     *
     * @param \AppBundle\Merchandise\Entity\OrderHelpSupplier $supplier
     *
     * @return OrderHelpMaskProduct
     */
    public function setSupplier(\AppBundle\Merchandise\Entity\OrderHelpSupplier $supplier = null)
    {
        $this->supplier = $supplier;

        return $this;
    }

    /**
     * Get supplier
     *
     * @return \AppBundle\Merchandise\Entity\OrderHelpSupplier
     */
    public function getSupplier()
    {
        return $this->supplier;
    }
}
