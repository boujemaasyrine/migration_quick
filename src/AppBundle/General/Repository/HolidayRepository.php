<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 24/08/2016
 * Time: 11:42
 */

namespace AppBundle\General\Repository;

use AppBundle\General\Entity\Holiday;
use Doctrine\ORM\EntityRepository;

class HolidayRepository extends EntityRepository
{
    public function getHolidaysDateBetween(\DateTime $min, \DateTime $max)
    {
        $qb = $this->createQueryBuilder('h')
            ->where('h.date >= :min')
            ->andWhere('h.date < :max')
            ->setParameter('min', $min)
            ->setParameter('max', $max);
        $holidays = $qb->getQuery()->getResult();
        $result = [];
        foreach ($holidays as $holiday) {
            /**
             * @var $holiday Holiday
             */
            $result[$holiday->getDate()->format('Y-m-d')] = $holiday;
        }

        return $result;
    }

    public function isHoliday(\DateTime $dateTime)
    {
        $qb = $this->createQueryBuilder('h')
            ->where('h.date = :date')
            ->setParameter('date', $dateTime);
        $holiday = $qb->setMaxResults(1)->getQuery()->getResult();

        return count($holiday);
    }
}
