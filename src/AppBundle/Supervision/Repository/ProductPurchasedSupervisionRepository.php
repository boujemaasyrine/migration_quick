<?php
/**
 * Created by PhpStorm.
 * User: hmnaouar
 * Date: 03/10/2017
 * Time: 15:54
 */

namespace AppBundle\Supervision\Repository;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Utils\Utilities;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Gedmo\Translatable\TranslatableListener;

class ProductPurchasedSupervisionRepository extends EntityRepository
{

    public function findProductSupervision($searchArray = null, $filters = null)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select(
            'p.id',
            'p.name',
            'p.externalId',
            'p.labelUnitUsage',
            'p.buyingCost',
            'p.inventoryQty',
            'p.usageQty'
        )
            ->from('Supervision:ProductPurchasedSupervision', 'p')->leftJoin('p.productCategory', 'c');
        $conditions = [];

        if (!is_null($searchArray)) {
            if (array_key_exists('term', $searchArray)) {
                $term = $searchArray['term'];
                $conditions[] = $qb->expr()->orX()->addMultiple(
                    [
                        $qb->expr()->like('UPPER(p.name)', "'%".strtoupper($term)."%'"),
                        $qb->expr()->like('UPPER(p.externalId)', "'%".strtoupper($term)."%'"),
                    ]
                );
            }
            if (array_key_exists('code', $searchArray)) {
                $code = $searchArray['code'];
                $conditions[] = $qb->expr()->orX()->addMultiple(
                    [
                        $qb->expr()->like('UPPER(p.name)', "'%".strtoupper($code)."%'"),
                        $qb->expr()->like('UPPER(p.externalId)', "'%".strtoupper($code)."%'"),
                    ]
                );
            }
        }
        if (!is_null($filters) && array_key_exists('categoryId', $filters)) {
            $categoryId = $filters['categoryId'];
            $conditions[] = $qb->expr()->orX()->addMultiple(
                [
                    $qb->expr()->eq('c.id = :categoryId', $categoryId),
                ]
            );
        }
        $conditions = call_user_func_array([$qb->expr(), 'andx'], $conditions);
        $qb->where($conditions);

        return $qb->getQuery()->getArrayResult();
    }

    public function getSupervisonInventoryItemsOrdered($criteria, $order, $offset, $limit, $onlyList = false)
    {

        $queryBuilder = $this->createQueryBuilder('i');

        if (!$onlyList) {
            $qb1 = clone $queryBuilder;
            $total = $qb1->select('count(i)')
                ->getQuery()->getSingleScalarResult();
        }


        //filtering
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'inventory_item_search[supplierSearch')
                or Utilities::exist($criteria, 'inventory_item_search[keyword')
            ) {
                $queryBuilder->leftJoin('i.suppliers', 's');
            }
            if (Utilities::exist($criteria, 'inventory_item_search[supplierSearch')) {
                $queryBuilder->andWhere(":supplier MEMBER OF i.suppliers")
                    ->setParameter("supplier", $criteria['inventory_item_search[supplierSearch']);
            }

            if (Utilities::exist($criteria, 'inventory_item_search[nameSearch')) {
                $queryBuilder->andWhere("lower(i.name) LIKE :name ")
                    ->setParameter("name", "%".strtolower($criteria['inventory_item_search[nameSearch'])."%");
            }

            if (Utilities::exist($criteria, 'inventory_item_search[statusSearch')) {
                $queryBuilder->andWhere("i.status = :status ")
                    ->setParameter("status", $criteria['inventory_item_search[statusSearch']);
            }

            if (Utilities::exist($criteria, 'inventory_item_search[codeSearch')) {
                $queryBuilder->andWhere("lower(i.externalId) LIKE :code ")
                    ->setParameter("code", "%".strtolower($criteria['inventory_item_search[codeSearch'])."%");
            }

            if (Utilities::exist($criteria, 'inventory_item_search[dateSynchro')) {
                $queryBuilder->andWhere("i.dateSynchro = :dateSynchro ")
                    ->setParameter("dateSynchro", $criteria['inventory_item_search[dateSynchro']);
            }

            if (Utilities::exist($criteria, 'inventory_item_search[lastDateSynchro')) {
                $queryBuilder->andWhere("DATE_STRING(i.lastDateSynchro) like :lastDateSynchro ")
                    ->setParameter("lastDateSynchro", $criteria['inventory_item_search[lastDateSynchro']);
            }

            if (Utilities::exist($criteria, 'inventory_item_search[keyword')) {
                $queryBuilder->andWhere(
                    "lower(i.name) LIKE :keyword OR lower(i.externalId) LIKE :keyword
                OR lower(s.name) LIKE :keyword"
                )
                    ->setParameter("keyword", "%".strtolower($criteria['inventory_item_search[keyword'])."%");
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
                    case 'code':
                        $queryBuilder->orderBy('i.externalId', $orderDir);
                        break;
                    case 'name':
                        $queryBuilder->orderBy('i.name', $orderDir);
                        break;
                    case 'buyingCost':
                        $queryBuilder->orderBy('i.buyingCost', $orderDir);
                        break;
                    case 'deliveryUnit':
                        $queryBuilder->orderBy('i.labelUnitExped', $orderDir);
                        break;
                    case 'inventoryUnit':
                        $queryBuilder->orderBy('i.labelUnitInventory', $orderDir);
                        break;
                    case 'usageUnit':
                        $queryBuilder->orderBy('i.labelUnitUsage', $orderDir);
                        break;
                    case 'qtyInventory':
                        $queryBuilder->orderBy('i.inventoryQty', $orderDir);
                        break;
                    case 'dateSynchro':
                        $queryBuilder->orderBy('i.dateSynchro', $orderDir);
                        break;
                    case 'lastDateSynchro':
                        $queryBuilder->orderBy('i.lastDateSynchro', $orderDir);
                        break;
                }
            }
        }
        if(!array_key_exists("locale",$criteria)){
            $locale='fr';
        }else{
            $locale=$criteria['locale'];
        }
        if ($limit !== null) {
            $queryBuilder->setMaxResults(intval($limit));
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult(intval($offset));
        }
        $query=$queryBuilder->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker');
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
        if ($onlyList) {
            return $query->getResult();
        } else {
            return array(
                'list' => $query->getResult(),
                'total' => $total,
                'filtred' => $filtredTotal,
            );
        }
    }

    public function getProductsPurchasedSupervisionByRestaurant(Restaurant $restaurant)
    {
        return $this->createQueryBuilder('pps')
                            ->where(':restaurant MEMBER OF pps.restaurants')
                            ->setParameter('restaurant', $restaurant)
                            ->getQuery()
                            ->getResult();
    }
}
