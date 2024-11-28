<?php

namespace AppBundle\Financial\Repository;

use AppBundle\Financial\Entity\DeletedEnvelope;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityRepository;


class DeletedEnvelopeRepository extends  EntityRepository
{

    public function getDeletedEnvelopesFilteredOrdered(
        $criteria,
        $order,
        $offset,
        $limit,
        $search = null,
        $onlyList = false,
        $type = DeletedEnvelope::TYPE_CASH
    )
    {
        $queryBuilder = $this->createQueryBuilder('de');
        $queryBuilder->leftJoin('de.owner', 'o');
        $queryBuilder->leftJoin('de.cashier', 'c')
            ->andWhere('upper(de.type) = upper(:type)')
            ->setParameter('type', $type);

        if (!$onlyList) {
            $qb1 = clone $queryBuilder;
            if (isset($criteria['restaurant'])) {
                $qb1->andWhere(":restaurant = de.originRestaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
            $total = $qb1->select('count(de)')
                ->getQuery()->getSingleScalarResult();
        }

        // filtering
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'deleted_envelope_search[source')) {
                $queryBuilder->andWhere("upper(de.source) = upper(:source) ")
                    ->setParameter("source", $criteria['deleted_envelope_search[source']);
            }

            if (Utilities::exist($criteria, 'deleted_envelope_search[sousType')) {
                $queryBuilder->andWhere("upper(de.sousType) = upper(:sousType) ")
                    ->setParameter("sousType", $criteria['deleted_envelope_search[sousType']);
            }

            if (Utilities::exist($criteria, 'deleted_envelope_search[status')) {
                $queryBuilder->andWhere("de.status = :status ")
                    ->setParameter("status", $criteria['deleted_envelope_search[status']);
            }

            if (Utilities::exist($criteria, 'deleted_envelope_search[startDate') && Utilities::exist(
                    $criteria,
                    'deleted_envelope_search[endDate'
                )) {
                $startDate = \DateTime::createFromFormat('j/m/Y', $criteria['deleted_envelope_search[startDate']);
                $startDate = $startDate->format('Y-m-d');

                $endDate = \DateTime::createFromFormat('j/m/Y', $criteria['deleted_envelope_search[endDate']);
                $endDate = $endDate->format('Y-m-d');

                $from = new \DateTime($startDate . " 00:00:00");
                $to = new \DateTime($endDate . " 23:59:59");
                $queryBuilder
                    ->andWhere('de.createdAt BETWEEN :from AND :to ')
                    ->setParameter('from', $from)
                    ->setParameter('to', $to);
            }
            if (Utilities::exist($criteria, 'deleted_envelope_search[owner')) {
                $queryBuilder->andWhere("de.owner = :owner ")
                    ->setParameter("owner", $criteria['deleted_envelope_search[owner']);
            }

            if ($search) {
                $queryBuilder
                    ->andWhere(
                        '(
            LOWER(o.firstName) like :search
            or LOWER(o.lastName) like :search
            or LOWER(c.firstName) like :search
            or LOWER(c.lastName) like :search
            or LOWER(STRING(de.reference)) like :search
            or LOWER(STRING(de.amount)) like :search
            or LOWER(STRING(de.numEnvelope)) like :search
            or DATE_STRING(de.createdAt) like :search
            )'
                    )
                    ->setParameter('search', '%' . strtolower($search) . '%');
            }

            if (isset($criteria['restaurant'])) {
                $queryBuilder->andWhere(":restaurant = de.originRestaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
        }

        if (!$onlyList) {
            $qb2 = clone $queryBuilder;
            $filteredTotal = $qb2->select('count(de)')
                ->getQuery()->getSingleScalarResult();
        }

        // ordering
        if ($order !== null && is_array($order) && count($order) > 0) {
            if (Utilities::exist($order, 'col')) {
                if (Utilities::exist($order, 'dir')) {
                    $orderDir = $order['dir'];
                } else {
                    $orderDir = 'asc';
                }
                switch ($order['col']) {
                    case 'number':
                        $queryBuilder->orderBy('de.numEnvelope', $orderDir);
                        break;
                    case 'amount':
                        $queryBuilder->orderBy('de.amount', $orderDir);
                        break;
                    case 'date':
                        $queryBuilder->orderBy('de.createdAt', $orderDir);
                        break;
                    case 'owner':
                        $queryBuilder->orderBy('o.firstName', $orderDir);
                        break;
                    case 'cashier':
                        $queryBuilder->orderBy('c.firstName', $orderDir);
                        break;
                    case 'ref':
                        $queryBuilder->orderBy('de.reference', $orderDir);
                        break;
                    case 'status':
                        $queryBuilder->orderBy('de.status', $orderDir);
                        break;
                    case 'source':
                        $queryBuilder->orderBy('de.source', $orderDir);
                        break;
                    case 'sousType':
                        $queryBuilder->orderBy('de.sousType', $orderDir);
                        break;
                    case 'deletedAt':
                        $queryBuilder->orderBy('de.deletedAt', $orderDir);
                        break;
                    case 'deletedBy':
                        $queryBuilder->orderBy('de.deletedBy', $orderDir);
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
                'filtered' => $filteredTotal,
            );
        }
    }



}