<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 02/01/2017
 * Time: 14:52
 */

namespace AppBundle\Report\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * Class MargeFoodCostRapport
 *
 * @package  AppBundle\Report\Entity
 * @Entity()
 */
class MargeFoodCostRapport extends RapportTmp
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
     * Set startDate
     *
     * @param \DateTime $startDate
     *
     * @return MargeFoodCostRapport
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
     * @return MargeFoodCostRapport
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
}
