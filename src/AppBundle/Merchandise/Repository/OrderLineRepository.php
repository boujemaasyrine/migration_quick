<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 05/04/2016
 * Time: 13:53
 */

namespace AppBundle\Merchandise\Repository;

use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityRepository;
use AppBundle\Merchandise\Entity\OrderLine;

class OrderLineRepository extends EntityRepository
{

    /**
     * @param ProductPurchased $productPurchased
     * @param \DateTime $deliveryDate
     * @return OrderLine[]
     */
    public function getOrderLineToBeDelivered(ProductPurchased $productPurchased, \DateTime $deliveryDate,Restaurant $restaurant=null)
    {

        $qb = $this->createQueryBuilder('ol')
            ->join('ol.order', 'o')
            ->where(
                'o.dateDelivery <= :deliveryDate and ol.product = :product
            and o.status in (:status)'
            )
            ->setParameter('product', $productPurchased)
            ->setParameter('deliveryDate', $deliveryDate)
            ->setParameter('status', [Order::SENDED, Order::DRAFT, Order::MODIFIED, Order::REJECTED, Order::SENDING]);
        if($restaurant){
            $qb->andWhere("o.originRestaurant = :restaurant")->setParameter('restaurant',$restaurant);
        }
        return $qb->getQuery()->getResult();
    }

    public function getOrderLineToBeDeliveredInDate(
        ProductPurchased $productPurchased,
        \DateTime $deliveryDate,
        Restaurant $restaurant = null
    ) {
        $qb = $this->createQueryBuilder('ol')
            ->join('ol.order', 'o')
            ->where(
                'o.dateDelivery = :deliveryDate and ol.product = :product
            and o.status in (:status)'
            )
            ->setParameter('product', $productPurchased)
            ->setParameter('deliveryDate', $deliveryDate)
            ->setParameter('status', [Order::SENDED, Order::DRAFT, Order::MODIFIED, Order::REJECTED, Order::SENDING]);
        if ($restaurant != null) {
            $qb->andWhere("o.originRestaurant = :restaurant")
                ->setParameter("restaurant", $restaurant);
        }

        return $qb->getQuery()->getResult();
    }

    /**************
     *
     * Supervision Section
     *******************/

    public function getSupervisionOrderLineToBeDeliveredInDate(
        ProductPurchased $productPurchased,
        \DateTime $deliveryDate,
        Restaurant $restaurant
    ) {
        $qb = $this->createQueryBuilder('ol')
            ->join('ol.order', 'o')
            ->where(
                'o.dateDelivery = :deliveryDate and
            o.originRestaurant = :restaurant
            and ol.product = :product
            and o.status in (:status)'
            )
            ->setParameter('restaurant', $restaurant)
            ->setParameter('product', $productPurchased)
            ->setParameter('deliveryDate', $deliveryDate)
            ->setParameter('status', [Order::SENDED, Order::DRAFT, Order::MODIFIED, Order::REJECTED, Order::SENDING]);

        return $qb->getQuery()->getResult();
    }
}
