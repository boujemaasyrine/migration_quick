<?php

namespace AppBundle\Merchandise\Entity;

use AppBundle\ToolBox\Traits\ImportIdTrait;
use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * CoefBase
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Merchandise\Repository\CoefBaseRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class CoefBase
{

    use TimestampableTrait;
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
     * @var \DateTime
     *
     * @ORM\Column(name="startDate", type="date",nullable=true)
     */
    private $startDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endDate", type="date",nullable=true)
     */
    private $endDate;

    /**
     * @var float
     *
     * @ORM\Column(name="ca", type="float",nullable=true)
     */
    private $ca;

    /**
     * @var int
     * @ORM\Column(name="week",type="integer",nullable=true)
     */
    private $week;

    /**
     * @var string
     * @ORM\Column(name="type",type="string",length=20,nullable=true)
     */
    private $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="locked", type="boolean",nullable=true)
     */
    private $locked;

    /**
     * @var
     * @ORM\OneToMany(targetEntity="AppBundle\Merchandise\Entity\Coefficient",mappedBy="base",cascade={"remove"})
     */
    private $coefs;

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
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return CoefBase
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
     * @return CoefBase
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
     * Set ca
     *
     * @param float $ca
     *
     * @return CoefBase
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
     * Set locked
     *
     * @param boolean $locked
     *
     * @return CoefBase
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked
     *
     * @return boolean
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->coefs = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add coef
     *
     * @param \AppBundle\Merchandise\Entity\Coefficient $coef
     *
     * @return CoefBase
     */
    public function addCoef(\AppBundle\Merchandise\Entity\Coefficient $coef)
    {
        $this->coefs[] = $coef;

        return $this;
    }

    /**
     * Remove coef
     *
     * @param \AppBundle\Merchandise\Entity\Coefficient $coef
     */
    public function removeCoef(\AppBundle\Merchandise\Entity\Coefficient $coef)
    {
        $this->coefs->removeElement($coef);
    }

    /**
     * Get coefs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCoefs()
    {
        $coefs = $this->coefs->toArray();

        usort(
            $coefs,
            function (Coefficient $c1, Coefficient $c2) {
                if ($c1->getProduct()->getName() > $c2->getProduct()->getName()) {
                    return -1;
                } else {
                    return 1;
                }
            }
        );

        return $coefs;
    }

    /**
     * Set week
     *
     * @param integer $week
     *
     * @return CoefBase
     */
    public function setWeek($week)
    {
        $this->week = $week;

        return $this;
    }

    /**
     * Get week
     *
     * @return integer
     */
    public function getWeek()
    {
        return $this->week;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return CoefBase
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
}
