<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 11/03/2016
 * Time: 15:31
 */

namespace AppBundle\Merchandise\Repository;

use Doctrine\ORM\EntityRepository;

class SoldingCanalRepository extends EntityRepository
{

    public function getSoldingCanalsIds()
    {
        $ids = $this
            ->createQueryBuilder('soldingCanal')
            ->select('soldingCanal.id')
            ->getQuery()->getArrayResult();

        return array_map(
            function ($elem) {
                return $elem['id'];
            },
            $ids
        );
    }
}
