<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 04/05/2016
 * Time: 09:13
 */

namespace AppBundle\Administration\Repository;

use AppBundle\Administration\Entity\Procedure;
use AppBundle\Administration\Entity\ProcedureInstance;
use AppBundle\Security\Entity\User;
use Doctrine\ORM\EntityRepository;

/**
 * Class ProcedureInstanceRepository
 * @package AppBundle\Administration\Repository
 */
class ProcedureInstanceRepository extends EntityRepository
{

    /**
     * @param Procedure $procedure
     * @param \DateTime $date
     * @param User $user
     *
     * @return array
     */
    public function getInstanceByProcedureByDateByUser(Procedure $procedure, \DateTime $date, User $user)
    {

        $qb = $this->createQueryBuilder('i')
            ->where('DATE(i.createdAt) = :date ')
            ->andWhere('i.procedure = :procedure ')
            ->andWhere('i.user = :user ')
            ->setParameter('date', $date)
            ->setParameter('user', $user)
            ->setParameter('procedure', $procedure)
            ->getQuery();

        return $qb->getResult();
    }

    /**
     * @param Procedure $procedure
     * @param \DateTime $date
     *
     * @return array
     */
    public function getAllPendingInstanceForADate(Procedure $procedure, \DateTime $date)
    {
        $qb = $this->createQueryBuilder('i')
            ->where('DATE(i.createdAt) = :date ')
            ->andWhere('i.procedure = :procedure ')
            ->andWhere('i.status = :pending')
            ->setParameter('date', $date)
            ->setParameter('pending', ProcedureInstance::PENDING)
            ->setParameter('procedure', $procedure)
            ->getQuery();

        return $qb->getResult();
    }

    /**
     * @param Procedure $procedure
     *
     * @return array
     */
    public function getAllPendingInstance(Procedure $procedure)
    {
        $qb = $this->createQueryBuilder('i')
            ->where('i.procedure = :procedure ')
            ->andWhere('i.status = :pending')
            ->setParameter('pending', ProcedureInstance::PENDING)
            ->setParameter('procedure', $procedure)
            ->getQuery();

        return $qb->getResult();
    }

    /**
     * @param Procedure $procedure
     * @param \DateTime $date
     *
     * @return array
     */
    public function getAllInstanceForADate(Procedure $procedure, \DateTime $date)
    {
        $qb = $this->createQueryBuilder('i')
            ->where('DATE(i.createdAt) = :date ')
            ->andWhere('i.procedure = :procedure ')
            ->setParameter('date', $date)
            ->setParameter('procedure', $procedure)
            ->getQuery();

        return $qb->getResult();
    }
}
