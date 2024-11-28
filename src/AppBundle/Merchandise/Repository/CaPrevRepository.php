<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 09/03/2016
 * Time: 12:43
 */

namespace AppBundle\Merchandise\Repository;

use Doctrine\ORM\EntityRepository;
use AppBundle\Merchandise\Entity\CaPrev;

class CaPrevRepository extends EntityRepository
{

    /**
     * @param $min
     * @param $max
     * @return CaPrev[]
     */
    public function getBetween($min, $max, $restaurant = null)
    {

        $qb = $this->createQueryBuilder('c')
            ->where('c.date >= :min')
            ->andWhere('c.date < :max')
            ->setParameter('min', $min)
            ->setParameter('max', $max);
        if (isset($restaurant)) {
            $qb->andWhere('c.originRestaurant = :restaurant')->setParameter('restaurant', $restaurant);
        }

        return $qb->getQuery()->getResult();
    }

    public function getAmountByDate($date, $currentRestaurant = null)
    {
        $queryBuilder = $this->createQueryBuilder('cp');

        $queryBuilder
            ->where('cp.date = :date')
            ->setParameter('date', $date);
        if ($currentRestaurant != null) {
            $queryBuilder->andWhere("cp.originRestaurant = :restaurant")
                ->setParameter("restaurant", $currentRestaurant);
        }

        $queryBuilder->select('Max (cp.ca) as total');
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }

    public function getSupervisionAmountByDate($date, $restaurants = null)
    {
        $queryBuilder = $this->createQueryBuilder('cp');

        $queryBuilder
            ->where('cp.date = :date')
            ->setParameter('date', $date);

        if ($restaurants and count($restaurants) > 0) {
            $restaurantsIds = array();

            foreach ($restaurants as $restaurant) {
                $restaurantsIds[] = $restaurant->getId();
            }
            $queryBuilder
                ->andWhere('cp.originRestaurant IN (:restaurants)')
                ->setParameter('restaurants', $restaurantsIds);
        }

        $queryBuilder->select('SUM (cp.ca) as total');
        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        return $result;
    }
}
