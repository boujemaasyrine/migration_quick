<?php

namespace AppBundle\Merchandise\Repository;

use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\LossLine;

/**
 * LossLineTypesRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LossLineTypeRepository extends \Doctrine\ORM\EntityRepository
{
    public function getTypebyHour($data)
    {


        $queryBuilder = $this->createQueryBuilder('t')
            ->join('t.lossLine', 'l')
            ->join("l.lossSheet", "s")
            ->join("l.product", "p");


        $queryBuilder->where("s.type = :type")
            ->setParameter("type", LossSheet::FINALPRODUCT);

        $queryBuilder->andwhere("s.entryDate = :date")
            ->setParameter("date", $data['date']);

        $queryBuilder->andwhere("t.label >= :entryTime")
            ->setParameter("entryTime", $data['entryTime']);

        $queryBuilder->andwhere("t.label <= :endTime")
            ->setParameter("endTime", $data['endTime']);

        $queryBuilder->andWhere("s.status = :set")
            ->setParameter("set", LossSheet::SET);

        $queryBuilder->select('SUM(t.value) AS total', '(p.id) AS productId', '(p.name) AS productName');
        $queryBuilder->groupBy('p');

        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }
}
