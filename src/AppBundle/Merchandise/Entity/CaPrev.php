<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\SynchronizedFlagTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * CaPrev
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\CaPrevRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class CaPrev
{
    use SynchronizedFlagTrait;
    use OriginRestaurantTrait;
    use ImportIdTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \\DateTime
     *
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var float
     *
     * @ORM\Column(name="ca", type="float")
     */
    private $ca;

    /**
     * @var \DateTime
     * @ORM\Column(name="date1",type="date",nullable=true)
     */
    private $date1;

    /**
     * @var \DateTime
     * @ORM\Column(name="date2",type="date",nullable=true)
     */
    private $date2;

    /**
     * @var \DateTime
     * @ORM\Column(name="date3",type="date",nullable=true)
     */
    private $date3;

    /**
     * @var \DateTime
     * @ORM\Column(name="date4",type="date",nullable=true)
     */
    private $date4;

    /**
     * @var \DateTime
     * @ORM\Column(name="date5",type="date",nullable=true)
     */
    private $date5;

    /**
     * @var \DateTime
     * @ORM\Column(name="date6",type="date",nullable=true)
     */
    private $date6;

    /**
     * @var \DateTime
     * @ORM\Column(name="date7",type="date",nullable=true)
     */
    private $date7;

    /**
     * @var \DateTime
     * @ORM\Column(name="date8",type="date",nullable=true)
     */
    private $date8;

    /**
     * @var bool
     * @ORM\Column(name="is_typed",type="boolean",nullable=true)
     */
    private $isTyped = false;

    /**
     * @var float
     * @ORM\Column(name="variance",type="float",nullable=true)
     */
    private $variance;

    /**
     * @var boolean
     * @ORM\Column(name="fixed",type="boolean",nullable=true)
     */
    private $fixed;

    /**
     * @var \DateTime
     * @ORM\Column(name="comparable_day",type="date",nullable=true)
     */
    private $comparableDay;

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
     * @param \\DateTime $date
     *
     * @return CaPrev
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
     * Set ca
     *
     * @param float $ca
     *
     * @return CaPrev
     */
    public function setCa($ca)
    {
        $ca = str_replace(',', '.', $ca);
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
     * Set date1
     *
     * @param \DateTime $date1
     *
     * @return CaPrev
     */
    public function setDate1($date1)
    {
        $this->date1 = $date1;

        return $this;
    }

    /**
     * Get date1
     *
     * @return \DateTime
     */
    public function getDate1()
    {
        return $this->date1;
    }

    /**
     * Set date2
     *
     * @param \DateTime $date2
     *
     * @return CaPrev
     */
    public function setDate2($date2)
    {
        $this->date2 = $date2;

        return $this;
    }

    /**
     * Get date2
     *
     * @return \DateTime
     */
    public function getDate2()
    {
        return $this->date2;
    }

    /**
     * Set date3
     *
     * @param \DateTime $date3
     *
     * @return CaPrev
     */
    public function setDate3($date3)
    {
        $this->date3 = $date3;

        return $this;
    }

    /**
     * Get date3
     *
     * @return \DateTime
     */
    public function getDate3()
    {
        return $this->date3;
    }

    /**
     * Set date4
     *
     * @param \DateTime $date4
     *
     * @return CaPrev
     */
    public function setDate4($date4)
    {
        $this->date4 = $date4;

        return $this;
    }

    /**
     * Get date4
     *
     * @return \DateTime
     */
    public function getDate4()
    {
        return $this->date4;
    }

    /**
     * Set date5
     *
     * @param \DateTime $date5
     *
     * @return CaPrev
     */
    public function setDate5($date5)
    {
        $this->date5 = $date5;

        return $this;
    }

    /**
     * Get date5
     *
     * @return \DateTime
     */
    public function getDate5()
    {
        return $this->date5;
    }

    /**
     * Set date6
     *
     * @param \DateTime $date6
     *
     * @return CaPrev
     */
    public function setDate6($date6)
    {
        $this->date6 = $date6;

        return $this;
    }

    /**
     * Get date6
     *
     * @return \DateTime
     */
    public function getDate6()
    {
        return $this->date6;
    }

    /**
     * Set date7
     *
     * @param \DateTime $date7
     *
     * @return CaPrev
     */
    public function setDate7($date7)
    {
        $this->date7 = $date7;

        return $this;
    }

    /**
     * Get date7
     *
     * @return \DateTime
     */
    public function getDate7()
    {
        return $this->date7;
    }

    /**
     * Set date8
     *
     * @param \DateTime $date8
     *
     * @return CaPrev
     */
    public function setDate8($date8)
    {
        $this->date8 = $date8;

        return $this;
    }

    /**
     * Get date8
     *
     * @return \DateTime
     */
    public function getDate8()
    {
        return $this->date8;
    }

    /**
     * Set variance
     *
     * @param float $variance
     *
     * @return CaPrev
     */
    public function setVariance($variance)
    {
        $this->variance = $variance;

        return $this;
    }

    /**
     * Get variance
     *
     * @return float
     */
    public function getVariance()
    {
        return $this->variance;
    }

    /**
     * Set fixed
     *
     * @param boolean $fixed
     *
     * @return CaPrev
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
     * Set isTyped
     *
     * @param boolean $isTyped
     *
     * @return CaPrev
     */
    public function setIsTyped($isTyped)
    {
        $this->isTyped = $isTyped;

        return $this;
    }

    /**
     * Get isTyped
     *
     * @return boolean
     */
    public function getIsTyped()
    {
        return $this->isTyped;
    }

    /**
     * Set comparableDay
     *
     * @param \DateTime $comparableDay
     *
     * @return CaPrev
     */
    public function setComparableDay($comparableDay)
    {
        $this->comparableDay = $comparableDay;

        return $this;
    }

    /**
     * Get comparableDay
     *
     * @return \DateTime
     */
    public function getComparableDay()
    {
        return $this->comparableDay;
    }
}
