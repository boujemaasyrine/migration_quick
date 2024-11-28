<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 08/01/2018
 * Time: 09:05
 */

namespace AppBundle\Merchandise\Repository;


use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityRepository;

class CoefBaseRepository extends EntityRepository
{


    /**
     * @param Restaurant $restaurant
     * @return null
     */
    public function findCoefBaseOfCurrentWeek(Restaurant $restaurant)
    {
        $firstDateOfCurrentYear = '01-01-' . date('Y');
        $qb = $this->getEntityManager()->createQueryBuilder();
        $result = $qb->select('c')
            ->from('Merchandise:CoefBase', 'c')
            ->where('c.originRestaurant = :restaurant')
            ->andWhere('c.week = :currentWeek')
            ->andWhere('c.startDate >= :firstDayOfYear')
            ->setParameter('restaurant', $restaurant)
            ->setParameter('currentWeek', intval(date('W')))
            ->setParameter('firstDayOfYear', $firstDateOfCurrentYear)
            ->getQuery()->getOneOrNullResult();

        return $result;
    }

}