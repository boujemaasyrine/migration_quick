<?php

namespace AppBundle\Merchandise\Repository;

use AppBundle\Merchandise\Entity\ProductPurchased;
use Doctrine\ORM\Query;
use Gedmo\Translatable\TranslatableListener;

/**
 * ProductCategoriesRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProductRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Filter by category id if not null
     *
     * @param  null $searchArray
     * @param  null $filters
     * @return array
     */
    public function findProduct($restaurant, $searchArray = null, $filters = null, $onlyActiveProducts = false)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('p')->from('Merchandise:Product', 'p')->leftJoin(
            'p.productCategory',
            'c'
        );
        $qb->where('1 = 1');

        if ($onlyActiveProducts) {
            $qb->where('p.active = TRUE');
        }

        if (!is_null($searchArray)) {
            if (array_key_exists('term', $searchArray)) {
                $term = $searchArray['term'];
                $qb->andWhere('UPPER(p.name) LIKE :term');
                $qb->setParameter(
                    'term',
                    "%".strtoupper($term)."%"
                );

                $qb->andWhere('UPPER(p.externalId) LIKE :code');
                $qb->setParameter(
                    'code',
                    "%".strtoupper($term)."%"
                );
            }
            if (array_key_exists('code', $searchArray)) {
                $code = $searchArray['code'];
                $qb->andWhere('UPPER(p.externalId) LIKE :code');
                $qb->setParameter(
                    'code',
                    "%".$code."%"
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

        if(!array_key_exists("locale",$filters)){
            $locale='fr';
        }else{
            $locale=$filters['locale'];
        }

        $qb->andWhere('p.originRestaurant=:restaurant')->setParameter('restaurant', $restaurant);
        $query=$qb->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker');
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE,$locale );
        $products = $query->getResult();
        $data = [];
        foreach ($products as $product)
        {
            $data[] = array(
                'id' => $product->getId(),
                'name' => $product->getName(),
                'externalId' => $product->getExternalId()
            );
        }

        return $products;
    }

    public function findAllProductsGroupedByCategory(
        $restaurant,
        $currentOffset = null,
        $limit = null,
        $filterSurg = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select(['productCategories', 'products'])
            ->from('Merchandise:ProductCategories', 'productCategories')
            ->leftJoin('productCategories.products', 'products')
            ->where('products.status IN (:allowedStatus)')
            ->andWhere('products.originRestaurant= :restaurant')
            ->setParameters(
                array(
                    'allowedStatus' => [ProductPurchased::ACTIVE, ProductPurchased::TO_INACTIVE],
                    'restaurant' => $restaurant,
                )
            );
        if (!is_null($filterSurg) && $filterSurg === true) {
            $qb->andWhere('products.primaryItem is null');
        }
        $qb->orderBy('productCategories.order')
            ->setFirstResult($currentOffset)
            ->setMaxResults($limit);
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    public function countFindAllProductsGroupedByCategory()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $result = $qb->select(['COUNT(productCategories)'])
            ->from('Merchandise:ProductCategories', 'productCategories')
            ->where('SIZE(productCategories.products) > 0')
            ->getQuery()->getSingleScalarResult();

        return $result;
    }
}
