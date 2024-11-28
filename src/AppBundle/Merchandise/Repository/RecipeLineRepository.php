<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 29/06/2016
 * Time: 10:40
 */

namespace AppBundle\Merchandise\Repository;

use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;

class RecipeLineRepository extends EntityRepository
{

    public function getRecipeLinesOrdered($criteria, $order, $offset, $limit, $onlyList = false)
    {

        $queryBuilder = $this->createQueryBuilder('rl');
        $queryBuilder
            ->leftJoin('rl.recipe', 'r')
            ->leftJoin('r.productSold', 'i');

        if (!$onlyList) {
            $qb1 = clone $queryBuilder;
            $total = $qb1->select('count(i)')
                ->getQuery()->getSingleScalarResult();
        }

        //filtering
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'product_sold_search[nameSearch')) {
                $queryBuilder->andWhere("lower(i.name) LIKE :name ")
                    ->setParameter("name", "%".strtolower($criteria['product_sold_search[nameSearch'])."%");
            }

            if (Utilities::exist($criteria, 'product_sold_search[statusSearch')) {
                $queryBuilder->andWhere("i.active = :active ")
                    ->setParameter("active", $criteria['product_sold_search[statusSearch'] == 0 ? false : true);
            }

            if (Utilities::exist($criteria, 'product_sold_search[typeSearch')) {
                $queryBuilder->andWhere("i.type = :type ")
                    ->setParameter("type", $criteria['product_sold_search[typeSearch']);
            }

            if (Utilities::exist($criteria, 'product_sold_search[codeSearch')) {
                $queryBuilder->andWhere("lower(i.codePlu) LIKE :codePlu ")
                    ->setParameter("codePlu", "%".strtolower($criteria['product_sold_search[codeSearch'])."%");
            }

            if (Utilities::exist($criteria, 'search')) {
                $queryBuilder->andWhere("lower(i.codePlu) LIKE :search or lower(i.name) LIKE :search")
                    ->setParameter("search", "%".strtolower($criteria['search'])."%");
            }
             if (is_object($criteria['restaurant'])) {
                $queryBuilder->andWhere("i.originRestaurant = :restaurant ")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
        }

        if (!$onlyList) {
            $qb2 = clone $queryBuilder;
            $filtredTotal = $qb2->select('count(i)')
                ->getQuery()->getSingleScalarResult();
        }

        //ordering
        if ($order !== null && is_array($order) && count($order) > 0) {
            if (Utilities::exist($order, 'col')) {
                if (Utilities::exist($order, 'dir')) {
                    $orderDir = $order['dir'];
                } else {
                    $orderDir = 'asc';
                }
                switch ($order['col']) {
                    case 'codePlu':
                        $queryBuilder->orderBy('i.codePlu', $orderDir);
                        break;
                    case 'name':
                        $queryBuilder->orderBy('i.name', $orderDir);
                        break;
                    case 'type':
                        $queryBuilder->orderBy('i.type', $orderDir);
                        break;
                    case 'active':
                        $queryBuilder->orderBy('i.active', $orderDir);
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

        if ($onlyList) {
            return $queryBuilder->getQuery()->getResult();
        } else {
            return array(
                'list' => $queryBuilder->getQuery()->getResult(),
                'total' => $total,
                'filtred' => $filtredTotal,
            );
        }
    }
}
