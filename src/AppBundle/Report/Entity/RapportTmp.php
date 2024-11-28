<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 25/04/2016
 * Time: 10:45
 */

namespace AppBundle\Report\Entity;

use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="report_type", type="string")
 * @ORM\DiscriminatorMap({"rapport" = "RapportTmp", "synthetic_food_cost_rapport" = "SyntheticFoodCostRapport", "marge_food_cost_rapport" = "MargeFoodCostRapport","generic_cached_report"="GenericCachedReport"})
 * @ORM\HasLifecycleCallbacks()
 */
class RapportTmp
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
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="data",type="text",nullable=true)
     */
    protected $data;

    /**
     * @var
     * @ORM\OneToMany(targetEntity="AppBundle\Report\Entity\RapportLineTmp",mappedBy="rapportTmp",cascade={"remove"})
     */
    protected $lines;

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
     * Set data
     *
     * @param string $data
     *
     * @return RapportTmp
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->lines = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add line
     *
     * @param \AppBundle\Report\Entity\RapportLineTmp $line
     *
     * @return RapportTmp
     */
    public function addLine(\AppBundle\Report\Entity\RapportLineTmp $line)
    {
        $this->lines[] = $line;

        return $this;
    }

    /**
     * Remove line
     *
     * @param \AppBundle\Report\Entity\RapportLineTmp $line
     */
    public function removeLine(\AppBundle\Report\Entity\RapportLineTmp $line)
    {
        $this->lines->removeElement($line);
    }

    /**
     * Get lines
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLines()
    {
        return $this->lines;
    }
}
