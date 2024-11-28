<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 01/04/2016
 * Time: 16:20
 */

namespace AppBundle\Administration\Repository;

use AppBundle\Supervision\Utils\Utilities;
use Doctrine\ORM\Query;
use Gedmo\Translatable\TranslatableListener;

class ParameterRepository extends \Doctrine\ORM\EntityRepository
{
    public function findParameterByType($type, $locale = null)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->where('p.type = :type')
            ->setParameter('type', $type);

        $queryBuilder->orderBy('p.id');

        $query = $queryBuilder->getQuery();
        if ($locale) {
            $query->setHint(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
            );

            $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
        }

        return $query->getResult();
    }

    public function findParametersByTypeAndRestaurant($type, $restaurant, $locale = null)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->where('p.type = :type')
            ->andWhere('p.originRestaurant = :restaurant')
            ->setParameter('type', $type)
            ->setParameter('restaurant', $restaurant);

        $queryBuilder->orderBy('p.id');

        $query = $queryBuilder->getQuery();
        if ($locale) {
            $query->setHint(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
            );

            $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
        }

        return $query->getResult();
    }

    public function findParameterById($id, $locale = null)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->where('p.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1);


        $query = $queryBuilder->getQuery();
        if ($locale) {
            $query->setHint(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
            );

            $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
        }

        return $query->getOneOrNullResult();
    }

    public function getLabelsOrdered($criteria, $order, $offset, $limit, $type)
    {

        $queryBuilder = $this->createQueryBuilder('l');

        $queryBuilder->andWhere('l.type = :type')
            ->setParameter('type', $type);

        //filtering
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'labelsSearch[keyword')) {
                $queryBuilder->andWhere("lower(l.label) LIKE :keyword")
                    ->setParameter("keyword", "%".strtolower($criteria['labelsSearch[keyword'])."%");
            }
        }

        if ($order !== null && is_array($order) && count($order) > 0) {
            if (Utilities::exist($order, 'col')) {
                if (Utilities::exist($order, 'dir')) {
                    $orderDir = $order['dir'];
                } else {
                    $orderDir = 'asc';
                }
                switch ($order['col']) {
                    case 'label':
                        $queryBuilder->orderBy('l.label', $orderDir);
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

    //get passed parameter for the current restaurant
    public function findParameterByTypeAndRestaurant($parameterType, $restaurant)
    {
        $parameter = $this->createQueryBuilder('parameter')
            ->where('parameter.type = :type_param')
            ->andWhere('parameter.originRestaurant = :restaurant')
            ->setParameter('type_param', $parameterType)
            ->setParameter('restaurant', $restaurant)
            ->getQuery()
            ->getOneOrNullResult();

        return $parameter;
    }

    //get passed parameter for the current restaurant by id
    public function findParameterByIdAndRestaurant($parameterId, $restaurant)
    {
        $parameter = $this->createQueryBuilder('parameter')
            ->where('parameter.id = :id_param')
            ->andWhere('parameter.originRestaurant = :restaurant')
            ->setParameter('id_param', $parameterId)
            ->setParameter('restaurant', $restaurant)
            ->getQuery()
            ->getOneOrNullResult();

        return $parameter;
    }
}
