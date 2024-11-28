<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 29/03/2016
 * Time: 10:07
 */
namespace AppBundle\Administration\Entity\Optikitchen;

use Doctrine\ORM\Mapping as ORM;

/**
 * OptikitchenMatrix
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class OptikitchenMatrix
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="integer")
     */
    private $level;

    /**
     * @var float
     *
     * @ORM\Column(name="min", type="float")
     */
    private $min;

    /**
     * @var float
     *
     * @ORM\Column(name="max", type="float")
     */
    private $max;

    /**
     * @var float
     *
     * @ORM\Column(name="avg", type="float")
     */
    private $avg;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="float")
     */
    private $value;


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
     * Set level
     *
     * @param integer $level
     *
     * @return OptikitchenMatrix
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level
     *
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set min
     *
     * @param float $min
     *
     * @return OptikitchenMatrix
     */
    public function setMin($min)
    {
        $this->min = $min;

        return $this;
    }

    /**
     * Get min
     *
     * @return float
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Set max
     *
     * @param float $max
     *
     * @return OptikitchenMatrix
     */
    public function setMax($max)
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Get max
     *
     * @return float
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Set avg
     *
     * @param float $avg
     *
     * @return OptikitchenMatrix
     */
    public function setAvg($avg)
    {
        $this->avg = $avg;

        return $this;
    }

    /**
     * Get avg
     *
     * @return float
     */
    public function getAvg()
    {
        return $this->avg;
    }

    /**
     * Set value
     *
     * @param float $value
     *
     * @return OptikitchenMatrix
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }
}
