<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 20/05/2016
 * Time: 09:50
 */

namespace AppBundle\Supervision\Service\WsBoAPI;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Service\RemoteHistoricService;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;

abstract class AbstractSyncService
{

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Logger
     */
    protected $logger;
    protected $restaurant;

    /**
     * @var RemoteHistoricService
     */
    protected $remoteHistoricService;

    public function __construct(EntityManager $em, Logger $logger, RemoteHistoricService $remoteHistoric)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->remoteHistoricService = $remoteHistoric;
    }

    /**
     * @return EntityManager
     */
    public function getEm()
    {
        return $this->em;
    }

    /**
     * @param EntityManager $em
     * @return AbstractSyncService
     */
    public function setEm($em)
    {
        $this->em = $em;

        return $this;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param Logger $logger
     * @return AbstractSyncService
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return RemoteHistoricService
     */
    public function getRemoteHistoricService()
    {
        return $this->remoteHistoricService;
    }

    /**
     * @param RemoteHistoricService $remoteHistoricService
     * @return AbstractSyncService
     */
    public function setRemoteHistoricService($remoteHistoricService)
    {
        $this->remoteHistoricService = $remoteHistoricService;

        return $this;
    }

    /**
     * @param Restaurant $restaurant
     * @return AbstractSyncService
     */
    public function setRestaurant($restaurant)
    {
        $this->restaurant = $restaurant;

        return $this;
    }
}
