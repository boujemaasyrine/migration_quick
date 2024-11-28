<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 25/02/2016
 * Time: 10:48
 */

namespace AppBundle\Merchandise\Repository;

use AppBundle\Merchandise\Entity\Supplier;
use Doctrine\ORM\EntityRepository;
use AppBundle\Merchandise\Entity\SupplierPlanning;

class SupplierPlanningRepository extends EntityRepository
{

    /**
     * @param Supplier $supplier
     * @param \DateTime $order
     * @param \DateTime $delivery
     * @return SupplierPlanning[]
     */
    public function filter($supplier, $order, $delivery)
    {

        $qb = $this->createQueryBuilder('p')->where('1=1');

        if ($supplier) {
            $qb->andWhere('p.supplier = :supplier ')
                ->setParameter('supplier', $supplier);
        }

        if ($order) {
            $qb->andWhere('p.dateOrder = :order ')
                ->setParameter('order', $order);
        }

        if ($delivery) {
            $qb->andWhere('p.dateDelivery = :delivery ')
                ->setParameter('delivery', $delivery);
        }

        return $qb->getQuery()->getResult();
    }
}
