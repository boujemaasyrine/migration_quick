<?php

namespace AppBundle\Merchandise\Repository;

use AppBundle\Supervision\Utils\Utilities;
use Doctrine\ORM\EntityRepository;

/**
 * CategoryGroupRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CategoryGroupRepository extends EntityRepository
{
    public function getGroupsOrdered($criteria, $order, $offset, $limit){

        $queryBuilder = $this->createQueryBuilder('g');

        $queryBuilder->andWhere('g.active = :true')
            ->setParameter('true', true);

        if ($order !== null && is_array($order) && count($order) > 0) {
            if (Utilities::exist($order, 'col')) {

                if (Utilities::exist($order, 'dir')) {
                    $orderDir = $order['dir'];
                } else {
                    $orderDir = 'asc';
                }
                switch ($order['col']) {
                    case 'ref' :
                        $queryBuilder->orderBy('g.id', $orderDir);
                        break;
                    case 'name' :
                        $queryBuilder->orderBy('g.name', $orderDir);
                        break;
                }
            }
        }
        if ($limit !== null) {
            $queryBuilder->setMaxResults(intval($limit));
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult(intval($offset));
        }

        return $queryBuilder->getQuery()->getResult();
    }
}