<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 02/01/2017
 * Time: 17:08
 */

namespace AppBundle\Report\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * Class MargeFoodCostLine
 *
 * @package  AppBundle\Report\Entity
 * @Entity()
 */
class MargeFoodCostLine extends RapportLineTmp
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
}
