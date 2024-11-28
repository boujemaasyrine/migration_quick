<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/04/2016
 * Time: 09:31
 */

namespace AppBundle\Report\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * Class SyntheticFoodCostRapport
 *
 * @package  AppBundle\Report\Entity
 * @Entity()
 */
class SyntheticFoodCostRapport extends RapportTmp
{

    /**
     * @var
     * @Column(name="start_date",type="date")
     */
    private $startDate;

    /**
     * @var
     * @Column(name="end_date",type="date")
     */
    private $endDate;


    /**
     *
     *
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return SyntheticFoodCostRapport
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     *
     *
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     *
     *
     * Set endDate
     *
     * @param \DateTime $endDate
     *
     * @return SyntheticFoodCostRapport
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     *
     *
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }
}
