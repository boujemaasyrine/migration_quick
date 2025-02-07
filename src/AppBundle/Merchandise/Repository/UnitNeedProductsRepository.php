<?php

namespace AppBundle\Merchandise\Repository;

/**
 * UnitNeedProductsRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UnitNeedProductsRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Filter by category id if not null
     *
     * @param  null $searchArray
     * @param  null $filters
     * @return array
     */
    public function findUnitNeed($searchArray = null, $filters = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('un.id', 'un.name', 'un.externalId')->from('Merchandise:UnitNeedProducts', 'un')->leftJoin(
            'un.productCategory',
            'c'
        );
        $qb->where('1 = 1');
        if (!is_null($searchArray) && array_key_exists('term', $searchArray)) {
            $term = $searchArray['term'];
            $qb->andWhere('UPPER(un.name) LIKE :term');
            $qb->setParameter(
                'term',
                strtoupper($term)."%"
            );
        }
        if (!is_null($searchArray) && array_key_exists('code', $searchArray)) {
            $code = $searchArray['code'];
            $qb->andWhere('un.id = :code');
            $qb->setParameter(
                'code',
                $code
            );
        }
        if (!is_null($filters) && array_key_exists('categoryId', $filters)) {
            $categoryId = $filters['categoryId'];
            $qb->andWhere('c.id = :categoryId')->setParameter(
                'categoryId',
                $categoryId
            );
        }

        return $qb->getQuery()->getArrayResult();
    }
}
