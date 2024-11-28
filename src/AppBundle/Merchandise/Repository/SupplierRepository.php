<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/02/2016
 * Time: 15:16
 */

namespace AppBundle\Merchandise\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Symfony\Component\Intl\Exception\NotImplementedException;
use AppBundle\Merchandise\Entity\SupplierPlanning;
use AppBundle\ToolBox\Utils\Utilities;

class SupplierRepository extends EntityRepository
{

    public function getSupplierOrdered($criteria, $order, $offset, $limit, $onlyList = false)
    {

        $queryBuilder = $this->createQueryBuilder('s');

        if (!$onlyList) {
            $qb1 = clone $queryBuilder;
            if (isset($criteria['restaurant'])) {
                $qb1->andWhere(":restaurant MEMBER OF s.restaurants")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
            $total = $qb1->select('count(s)')
                ->getQuery()->getSingleScalarResult();
        }

        //filtering
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'supplier_search[code')) {
                $queryBuilder->andWhere("lower(s.code) LIKE :code ")
                    ->setParameter("code", "%".strtolower($criteria['supplier_search[code'])."%");
            }

            if (Utilities::exist($criteria, 'supplier_search[name')) {
                $queryBuilder->andWhere("lower(s.name) LIKE :name ")
                    ->setParameter("name", "%".strtolower($criteria['supplier_search[name'])."%");
            }

            if (isset($criteria['restaurant'])) {
                $queryBuilder->andWhere(":restaurant MEMBER OF s.restaurants")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
        }

        if (!$onlyList) {
            $qb2 = clone $queryBuilder;
            $filtredTotal = $qb2->select('count(s)')
                ->getQuery()->getSingleScalarResult();
        }

        if ($order !== null && is_array($order) && count($order) > 0) {
            if (Utilities::exist($order, 'col')) {
                if (Utilities::exist($order, 'dir')) {
                    $orderDir = $order['dir'];
                } else {
                    $orderDir = 'asc';
                }

                switch ($order['col']) {
                    case 'code':
                        $queryBuilder->orderBy('s.code', $orderDir);
                        break;
                    case 'supplier':
                        $queryBuilder->orderBy('s.name', $orderDir);
                        break;
                    case 'designation':
                        $queryBuilder->orderBy('s.designation', $orderDir);
                        break;
                    case 'address':
                        $queryBuilder->orderBy('s.address', $orderDir);
                        break;
                    case 'phone':
                        $queryBuilder->orderBy('s.phone', $orderDir);
                        break;
                    case 'mail':
                        $queryBuilder->orderBy('s.email', $orderDir);
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

    /* The supervision version*/
    public function getSupplierOrderedForSupervision($criteria, $order, $offset, $limit, $onlyList = false)
    {

        $queryBuilder = $this->createQueryBuilder('s');

        $queryBuilder->andWhere("s.active = :active ")
            ->setParameter("active", true);

        if (!$onlyList) {
            $qb1 = clone $queryBuilder;
            $total = $qb1->select('count(s)')
                ->getQuery()->getSingleScalarResult();
        }


        //filtering
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'supplier_search[code')) {
                $queryBuilder->andWhere("lower(s.code) LIKE :code ")
                    ->setParameter("code", "%".strtolower($criteria['supplier_search[code'])."%");
            }

            if (Utilities::exist($criteria, 'supplier_search[name')) {
                $queryBuilder->andWhere("lower(s.name) LIKE :name ")
                    ->setParameter("name", "%".strtolower($criteria['supplier_search[name'])."%");
            }
        }


        if (!$onlyList) {
            $qb2 = clone $queryBuilder;
            $filtredTotal = $qb2->select('count(s)')
                ->getQuery()->getSingleScalarResult();
        }

        if ($order !== null && is_array($order) && count($order) > 0) {
            if (Utilities::exist($order, 'col')) {
                if (Utilities::exist($order, 'dir')) {
                    $orderDir = $order['dir'];
                } else {
                    $orderDir = 'asc';
                }

                switch ($order['col']) {
                    case 'code':
                        $queryBuilder->orderBy('s.code', $orderDir);
                        break;
                    case 'supplier':
                        $queryBuilder->orderBy('s.name', $orderDir);
                        break;
                    case 'designation':
                        $queryBuilder->orderBy('s.designation', $orderDir);
                        break;
                    case 'address':
                        $queryBuilder->orderBy('s.address', $orderDir);
                        break;
                    case 'phone':
                        $queryBuilder->orderBy('s.phone', $orderDir);
                        break;
                    case 'mail':
                        $queryBuilder->orderBy('s.email', $orderDir);
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

    public function getSuppliersByRestaurant($restaurant, $status = true)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->join("s.restaurants", "r")
            ->where('r = :restaurant')
            ->setParameter('restaurant', $restaurant)
            ->andWhere('s.active= :status')
            ->setParameter('status', $status);;

        $query = $queryBuilder->getQuery();
        $results = $query->getResult();

        return $results;
    }

    public function getRestaurantSuppliers($restaurant)
    {
        $queryBuilder = $this->createQueryBuilder('s');

        $queryBuilder->andWhere(":restaurant MEMBER OF s.restaurants")
            ->addSelect("plannings", 'categories')
            ->leftJoin("s.plannings", "plannings")
            ->leftJoin("plannings.categories", "categories")
            ->andWhere('s.active= :status')
            ->setParameter('status', true)
            ->setParameter("restaurant", $restaurant)
            ->addOrderBy("s.name", "ASC");

        $query = $queryBuilder->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker');
        $results = $query->getResult();

        return $results;
    }
}
