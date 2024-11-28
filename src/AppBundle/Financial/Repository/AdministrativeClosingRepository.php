<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 04/05/2016
 * Time: 09:13
 */

namespace AppBundle\Financial\Repository;

use Doctrine\ORM\EntityRepository;
use AppBundle\Financial\Entity\AdministrativeClosing;
use Doctrine\ORM\NoResultException;

class AdministrativeClosingRepository extends EntityRepository
{

    public function isComparable($date, $currentRestaurant)
    {
        $queryBuilder = $this->createQueryBuilder('ac');
        $queryBuilder->where('ac.date = :date')->setParameter('date', $date)->andWhere("ac.originRestaurant = :restaurant")->setParameter("restaurant", $currentRestaurant);
        $queryBuilder->select('ac.comparable as isComaparable');
        try {
            $queryBuilder->setMaxResults(1);
            $result = $queryBuilder->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            $result = true;
        }
        return $result;
    }

    public function getComment($date, $currentRestaurant = null)
    {
        $queryBuilder = $this->createQueryBuilder('ac');
        $queryBuilder->where('ac.date = :date')->setParameter('date', $date);
        if ($currentRestaurant != null) {
            $queryBuilder->andWhere("ac.originRestaurant = :restaurant")->setParameter("restaurant", $currentRestaurant);
        }
        $queryBuilder->select('ac.comment as comment');
        try {
            $queryBuilder->setMaxResults(1);
            $result = $queryBuilder->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
            $result = '';
        }
        return $result;
    }

    public function getLastClosingDate($restaurant)
    {
        $qb = $this->createQueryBuilder('ac')->select('ac.date as date')->where('ac.originRestaurant = :restaurant')->setParameter('restaurant', $restaurant)->orderBy('ac.date', 'desc');
        try {
            $qb->setMaxResults(1);
            $result = $qb->getQuery()->getSingleResult();
            if ($result['date']) {
                $result = $result['date'];
            }
        } catch (NoResultException $e) {
            $result = null;
        }
        return $result;
    }

    public function getFirstClosingDate($currentRestaurant = null)
    {
        $qb = $this->createQueryBuilder('ac')->select('ac.date as date')->orderBy('ac.date');
        if ($currentRestaurant != null) {
            $qb->andWhere("ac.originRestaurant = :restaurant")->setParameter("restaurant", $currentRestaurant);
        }
        try {
            $qb->setMaxResults(1);
            $result = $qb->getQuery()->getSingleResult();
            if ($result['date']) {
                $result = $result['date'];
            }
        } catch (NoResultException $e) {
            $result = null;
        }
        return $result;
    }

    /**
     * @param \DateTime $d1
     * @param \DateTime $d2
     * @return AdministrativeClosing[]
     */
    public function getAdminClosingBetweenDates(\DateTime $d1, \DateTime $d2, $restaurant = null)
    {

        return $this->createQueryBuilder('x')->where('x.date >= :d1')->andWhere('x.date <= :d2')->andWhere('x.originRestaurant=:restaurant')->setParameter('d1', $d1)->setParameter('d2', $d2)->setParameter('restaurant', $restaurant)->getQuery()->getResult();
    }

    public function isComparableSupervision($date)
    {
        $queryBuilder = $this->createQueryBuilder('ac');
        $queryBuilder->where('ac.date = :date')->setParameter('date', $date);
        $queryBuilder->select('ac.comparable as isComaparable');
        try {
            $queryBuilder->setMaxResults(1);
            $result = $queryBuilder->getQuery()->getSingleScalarResult();

        } catch (NoResultException $e) {
            $result = true;
        }

        return $result;
    }
}
