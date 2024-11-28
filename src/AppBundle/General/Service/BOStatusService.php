<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/03/2016
 * Time: 09:39
 */

namespace AppBundle\General\Service;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Entity\SyncCmdQueue;
use AppBundle\General\Service\Download\Ping;
use Doctrine\ORM\EntityManager;

class BOStatusService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Ping
     */
    private $pingService;

    /**
     * BOStatusService constructor.
     *
     * @param EntityManager $entityManager
     * @param Ping $pingService
     */
    public function __construct(EntityManager $entityManager, Ping $pingService)
    {
        $this->em = $entityManager;
        $this->pingService = $pingService;
    }

    /*public function lastSynchronizationDate()
    {
        $lastUp = $this->em->getRepository("General:RemoteHistoric")->createQueryBuilder('e')
            ->orderBy('e.updatedAt', 'Desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        $lastDown = $this->em->getRepository("General:SyncCmdQueue")->createQueryBuilder('e')
            ->where('e.status != :status')
            ->setParameter('status', SyncCmdQueue::WAITING)
            ->andWhere('e.direction = :direction')
            ->setParameter('direction', SyncCmdQueue::DOWNLOAD)
            ->orderBy('e.updatedAt', 'Desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        if ($lastDown && $lastUp) {
            return $lastDown->getUpdatedAt() > $lastUp->getStartedAt() ? $lastDown->getUpdatedAt() : $lastUp->getStartedAt();
        } elseif ($lastDown) {
            return $lastDown->getUpdatedAt();
        } elseif ($lastUp) {
            return $lastUp->getStartedAt();
        }
        return '';
    }*/

    public function connectionStatus()
    {
        $res = $this->pingService->download();

        return !is_null($res);
    }
}
