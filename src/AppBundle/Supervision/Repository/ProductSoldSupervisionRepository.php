<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 04/10/2017
 * Time: 15:09
 */

namespace AppBundle\Supervision\Repository;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\Supervision\Utils\Utilities;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Gedmo\Translatable\TranslatableListener;

class ProductSoldSupervisionRepository extends EntityRepository
{
    public function checkIfRestaurantsHaveAlreadyProductWithThisPlu(ProductSoldSupervision $productSold, $plu)
    {
    }

    public function findProductSupervision($searchArray = null, $filters = null)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('p.id', 'p.name', 'p.externalId')->from('Supervision:ProductSoldSupervision', 'p')->leftJoin(
            'p.division',
            'c'
        );
        $qb->where('1 = 1');

        if (!is_null($searchArray)) {
            if (array_key_exists('term', $searchArray)) {
                $term = $searchArray['term'];
                $qb->andWhere('UPPER(p.name) LIKE :term');
                $qb->setParameter(
                    'term',
                    strtoupper($term)."%"
                );
            }
            if (array_key_exists('code', $searchArray)) {
                $code = $searchArray['code'];
                $qb->andWhere('p.externalId LIKE :code');
                $qb->setParameter(
                    'code',
                    "$code%"
                );
            }
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

    public function getProductsSold($criteria, $order, $offset, $limit, $getResult = true)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->select('productSold')->from('Supervision:ProductSoldSupervision', 'productSold');

        // searching
        if (count($criteria) > 0) {
            $value = $criteria['value'];
            $qb->where("lower(productSold.name) LIKE :search")
                ->orWhere("lower(productSold.codePlu) LIKE :search")
                ->setParameter('search', "%".strtolower($value)."%");
        }

        // ordering
        if (count($order)) {
            switch ($order[0]['column']) {
                case 0:
                    $qb->orderBy('productSold.codePlu', $order[0]['dir']);
                    break;
                case 1:
                    $qb->orderBy('productSold.name', $order[0]['dir']);
                    break;
                case 2:
                    $qb->orderBy('productSold.type', $order[0]['dir']);
                    break;
                case 3:
                    $qb->orderBy('productSold.active', $order[0]['dir']);
                    break;
            }
        }

        if (!is_null($offset) && !is_null($limit)) {
            $preparedQuery = $qb->getQuery()->setFirstResult(intval($offset))->setMaxResults(intval($limit));
        } else {
            $preparedQuery = $qb->getQuery();
        }

        return $getResult ? $preparedQuery->getArrayResult() : $preparedQuery;
    }

    public function getProductsSoldCount()
    {
        $qb = $this->_em->getRepository(ProductSoldSupervision::class)->createQueryBuilder('productSold')
            ->select('COUNT(productSold)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getFiltredProductsSoldCount($criteria, $getResult = true)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('COUNT(productSold)')->from('Supervision:ProductSoldSupervision', 'productSold');
        // searching
        if (count($criteria) > 0) {
            $value = $criteria['value'];
            $qb->where("lower(productSold.name) LIKE :search")
                ->orWhere("productSold.codePlu LIKE :search")
                ->setParameter('search', "%".strtolower($value)."%");
        }
        $preparedQuery = $qb->getQuery();

        return $getResult ? $preparedQuery->getSingleScalarResult() : $preparedQuery;
    }

    public function getProductsSoldOrdered($criteria, $order, $offset, $limit, $onlyList = false)
    {

        $queryBuilder = $this->createQueryBuilder('i');

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

        if(!array_key_exists("locale",$criteria)){
            $locale='fr';
        }else{
            $locale=$criteria['locale'];
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

    public function getProductsSoldSupervisionByRestaurant(Restaurant $restaurant)
    {
        return $this->createQueryBuilder('pss')
            ->where(':restaurant MEMBER OF pss.restaurants')
            ->setParameter('restaurant', $restaurant)
            ->getQuery()
            ->getResult();
    }
}
