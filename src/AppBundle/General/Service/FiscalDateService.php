<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/03/2016
 * Time: 09:39
 */

namespace AppBundle\General\Service;

use Doctrine\ORM\EntityManager;

class FiscalDateService
{

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function lastFiscalDate($date = null)
    {
        if ($date == null) {
            $date = new \DateTime("NOW");
        }
        $lastDateTimestamp = mktime(
            0,
            0,
            0,
            intval($date->format('m')),
            intval($date->format('d')) - 1,
            intval($date->format('Y'))
        );
        $lastDate = new \DateTime();
        $lastDate->setTimestamp($lastDateTimestamp);
        if ($this->isHoliday($lastDate) || $lastDate->format('w') == 0) {
            return $this->lastFiscalDate($lastDate);
        }

        return $lastDate;
    }

    public function isHoliday($date = null)
    {
        if ($date == null) {
            $date = new \DateTime("NOW");
        }
        $holiday = $this->em->getRepository("General:Holiday")->findOneBy(
            array(
                'date' => $date,
            )
        );
        if ($holiday) {
            return true;
        }

        return false;
    }
}
