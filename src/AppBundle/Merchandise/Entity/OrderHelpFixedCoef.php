<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 07/04/2016
 * Time: 10:54
 */

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class OrderHelpFixedCoef
 *
 * @package                                                                                     AppBundle\Merchandise\Entity
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\OrderHelpFixedCoefRepository")
 */
class OrderHelpFixedCoef
{
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
     * @var float
     * @ORM\Column(name="coef",type="float",nullable=true)
     */
    private $coef;

    /**
     * @var boolean
     * @ORM\Column(name="real",type="boolean",nullable=true)
     */
    private $real;

    /**
     * @var
     * @ORM\OneToOne(targetEntity="AppBundle\Merchandise\Entity\ProductPurchased")
     */
    private $product;

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
     * Set product
     *
     * @param \AppBundle\Merchandise\Entity\ProductPurchased $product
     *
     * @return OrderHelpFixedCoef
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
     * Set coef
     *
     * @param float $coef
     *
     * @return OrderHelpFixedCoef
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
     * Set real
     *
     * @param boolean $real
     *
     * @return OrderHelpFixedCoef
     */
    public function setReal($real)
    {
        $this->real = $real;

        return $this;
    }

    /**
     * Get real
     *
     * @return boolean
     */
    public function getReal()
    {
        return $this->real;
    }
}
