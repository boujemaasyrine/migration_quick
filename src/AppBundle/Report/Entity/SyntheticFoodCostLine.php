<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/04/2016
 * Time: 09:27
 */

namespace AppBundle\Report\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * Class SyntheticFoodCostLine
 *
 * @package  AppBundle\Report\Entity
 * @Entity()
 */
class SyntheticFoodCostLine extends RapportLineTmp
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

    public function getDate()
    {
        return $this->date;
    }

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

    public function setDate(\DateTime $dateTime)
    {
        $this->date = $dateTime;

        return $this;
    }


}
