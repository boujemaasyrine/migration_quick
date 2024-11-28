<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 25/04/2016
 * Time: 10:47
 */

namespace AppBundle\Report\Entity;

use AppBundle\ToolBox\Traits\OriginRestaurantTrait;
use AppBundle\ToolBox\Traits\TimestampableTrait;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;

/**
 *
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="report_line_type", type="string")
 * @ORM\DiscriminatorMap({"rapport_line" = "RapportLineTmp", "synthetic_food_cost_line" = "SyntheticFoodCostLine", "marge_food_cost_line" = "MargeFoodCostLine", "three_week_line" = "ThreeWeekReportLine"})
 * @ORM\HasLifecycleCallbacks()
 */
class RapportLineTmp
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
    private $id;

    /**
     * @var string
     * @ORM\Column(name="data",type="text",nullable=true)
     */
    private $data;

    /**
     * @var
     * @ORM\ManyToOne(targetEntity="AppBundle\Report\Entity\RapportTmp",inversedBy="lines")
     */
    private $rapportTmp;

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
     * @param string|mixed $data
     *
     * @return RapportLineTmp
     */
    public function setData($data)
    {
        if (is_string($data)) {
            $this->data = $data;
        } else {
            $this->data = json_encode($data);
        }

        return $this;
    }

    /**
     * Get data
     *
     * @return string|mixed
     */
    public function getData()
    {
        if ($this->data == 'null') {
            return null;
        }

        return $this->data;
    }

    /**
     * Set rapportTmp
     *
     * @param \AppBundle\Report\Entity\RapportTmp $rapportTmp
     *
     * @return RapportLineTmp
     */
    public function setRapportTmp(\AppBundle\Report\Entity\RapportTmp $rapportTmp = null)
    {
        $this->rapportTmp = $rapportTmp;

        return $this;
    }

    /**
     * Get rapportTmp
     *
     * @return \AppBundle\Report\Entity\RapportTmp
     */
    public function getRapportTmp()
    {
        return $this->rapportTmp;
    }

}
