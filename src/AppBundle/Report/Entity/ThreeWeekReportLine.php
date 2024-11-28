<?php


namespace AppBundle\Report\Entity;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * Class ThreeWeekReportLine
 *
 * @package  AppBundle\Report\Entity
 * @Entity()
 */

class ThreeWeekReportLine  extends RapportLineTmp
{
    /**
     * @var
     * @Column(name="date",type="date")
     */
    private $date;

    /**
     * @var
     * @Column(name="end_date",type="date")
     */
    private $endDate;

    /**
     * @var integer
     * @Column(name="week_number",type="integer")
     */
    private $weekNumber;

    /**
     * @return mixed
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param mixed $endDate
     */
    public function setEndDate(\DateTime $dateTime)
    {
        $this->endDate = $dateTime;
        return $this;
    }



    public function getDate()
    {
        return $this->date;
    }

    public function setDate(\DateTime $dateTime)
    {
        $this->date = $dateTime;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getWeekNumber()
    {
        return $this->weekNumber;
    }

    /**
     * @param mixed $weekNumber
     */
    public function setWeekNumber($weekNumber)
    {
        $this->weekNumber = $weekNumber;
    }

}