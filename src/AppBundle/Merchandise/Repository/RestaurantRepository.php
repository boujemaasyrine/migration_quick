<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 11/03/2016
 * Time: 11:05
 */

namespace AppBundle\Merchandise\Repository;

use AppBundle\Financial\Entity\AdministrativeClosing;
use Doctrine\ORM\EntityRepository;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\Query\Expr\Join;

class RestaurantRepository extends EntityRepository
{


    public function getRestaurantOrdered($criteria, $order, $offset, $limit)
    {

        $queryBuilder = $this->createQueryBuilder('r');

        if ($order !== null && is_array($order) && count($order) > 0) {
            if (Utilities::exist($order, 'col')) {
                if (Utilities::exist($order, 'dir')) {
                    $orderDir = $order['dir'];
                } else {
                    $orderDir = 'asc';
                }

                switch ($order['col']) {
                    case 'code':
                        $queryBuilder->orderBy('r.code', $orderDir);
                        break;
                    case 'name':
                        $queryBuilder->orderBy('r.name', $orderDir);
                        break;
                    case 'email':
                        $queryBuilder->orderBy('r.email', $orderDir);
                        break;
                    case 'manager':
                        $queryBuilder->orderBy('r.manager', $orderDir);
                        break;
                    case 'adress':
                        $queryBuilder->orderBy('r.address', $orderDir);
                        break;
                    case 'phone':
                        $queryBuilder->orderBy('r.phone', $orderDir);
                        break;
                    case 'type':
                        $queryBuilder->orderBy('r.type', $orderDir);
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

    public function getOpenedRestaurantsQuery()
    {
        $today = new \DateTime();
        return $this->createQueryBuilder("r")
            ->join(AdministrativeClosing::class, 'ad', Join::WITH, 'ad.originRestaurant = r')
            ->where('ad.date < :today')
            ->andWhere('r.active = true')
            ->setParameter('today', $today);
    }

    public function getOpenedRestaurants()
    {
        return $this->getOpenedRestaurantsQuery()
                            ->getQuery()
                            ->getResult();
    }


}
