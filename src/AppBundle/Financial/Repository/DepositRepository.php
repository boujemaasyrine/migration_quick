<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 17/03/2016
 * Time: 16:40
 */

namespace AppBundle\Financial\Repository;

use Doctrine\ORM\EntityRepository;

class DepositRepository extends EntityRepository
{
    public function getPeriod($id)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->join('d.envelopes', 'e')
            ->select('MAX(e.createdAt) as endDate, MIN(e.createdAt) as startDate')
            ->where('d.id = :id')
            ->setParameter('id', $id);

        return $qb->getQuery()->getResult();
    }
}
