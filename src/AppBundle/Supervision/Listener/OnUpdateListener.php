<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 27/05/2016
 * Time: 15:07
 */

namespace AppBundle\Supervision\Listener;

use AppBundle\Merchandise\Entity\ProductPurchased;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\DependencyInjection\Container;

class OnUpdateListener
{

    /**
     * @var Container $container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        $updatedEntities = $uow->getScheduledEntityUpdates();
        foreach ($updatedEntities as $entity) {
            if ($entity instanceof ProductPurchased) {
                $changes = $uow->getEntityChangeSet($entity);
                if (count($changes) > 1 or (count($changes) == 1 and !array_key_exists('dateSynchro', $changes))) {
                    $oldEntity = clone $entity;
                    foreach ($changes as $key => $change) {
                        $attribute = strtoupper(substr($key, 0, 1));
                        $attribute = 'set'.$attribute.substr($key, 1);
                        $oldEntity->$attribute($change['0']);
                    }
                    $this->container->get('historic.entities.service')->createProductPurchasedHistoric($oldEntity);
                }
            }
        }
    }
}
