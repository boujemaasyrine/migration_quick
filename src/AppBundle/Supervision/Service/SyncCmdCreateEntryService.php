<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 27/05/2016
 * Time: 09:14
 */

namespace AppBundle\Supervision\Service;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Security\Entity\User;
use AppBundle\General\Entity\SyncCmdQueue;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;

class SyncCmdCreateEntryService
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(EntityManager $em, Logger $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }


    /**
     * @param ProductPurchasedSupervision $product
     * @param bool                        $force
     * @param null                        $restaurant
     * @param bool                        $deleteExistantSynchro
     * @throws \Exception
     */
    public function createProductPurchasedEntry(
        ProductPurchasedSupervision $product,
        $force = false,
        $restaurant = null,
        $deleteExistantSynchro = true
    ) {
        try {
            if ($deleteExistantSynchro) {
                $waitingSyncCmd = $this->em->getRepository(SyncCmdQueue::class)
                    ->createQueryBuilder("cmd")
                    ->where("cmd.status = :waiting")
                    ->andWhere("cmd.cmd = :item_inv")
                    ->andWhere("cmd.params = :params")
                    ->setParameter("waiting", SyncCmdQueue::WAITING)
                    ->setParameter("item_inv", SyncCmdQueue::DOWNLOAD_INV_ITEMS)
                    ->setParameter("params", json_encode(['product_code' => $product->getId()]))
                    ->getQuery()
                    ->getResult();
                foreach ($waitingSyncCmd as $cmd) {
                    $this->em->remove($cmd);
                }
                $this->em->flush();
            }
            if ($restaurant != null) {
                $cmd = new SyncCmdQueue();
                $cmd->setParams(json_encode(['product_code' => $product->getId()]))
                    ->setStatus(SyncCmdQueue::WAITING)
                    ->setCmd(SyncCmdQueue::DOWNLOAD_INV_ITEMS)
                    ->setProduct($product)
                    ->setOriginRestaurant($restaurant);
                if ($force == false) {
                    $cmd->setSyncDate($product->getDateSynchro());
                } else {
                    $cmd->setSyncDate(null);
                }
                $this->em->persist($cmd);
            } else {
                foreach ($product->getRestaurants() as $restaurant) {
                    $cmd = new SyncCmdQueue();
                    $cmd->setParams(json_encode(['product_code' => $product->getId()]))
                        ->setStatus(SyncCmdQueue::WAITING)
                        ->setCmd(SyncCmdQueue::DOWNLOAD_INV_ITEMS)
                        ->setProduct($product)
                        ->setOriginRestaurant($restaurant);
                    if ($force == false) {
                        $cmd->setSyncDate($product->getDateSynchro());
                    } else {
                        $cmd->setSyncDate(null);
                    }
                    $this->em->persist($cmd);
                }
            }
            $this->em->flush();
        } catch (\Exception $e) {
            //dump("here " .$e->getMessage());die;
            throw $e;
        }
    }

    public function createProductSoldEntry(
        ProductSoldSupervision $product,
        $force = false,
        $deleteExistantSynchro = true,
        $restaurant = null
    ) {
        try {
            if ($deleteExistantSynchro) {
                $waitingSyncCmd = $this->em->getRepository(SyncCmdQueue::class)->createQueryBuilder("cmd")->where(
                    "cmd.status = :waiting"
                )->andWhere("cmd.cmd = :sold_items")->andWhere("cmd.params = :params")->setParameter(
                    "waiting",
                    SyncCmdQueue::WAITING
                )->setParameter("sold_items", SyncCmdQueue::DOWNLOAD_SOLD_ITEMS)->setParameter(
                    "params",
                    json_encode(
                        [
                            'globalProductID' => is_null($product->getGlobalProductID()) ? $product->getId(
                            ) : $product->getGlobalProductID(),
                        ]
                    )
                )->getQuery()->getResult();
                foreach ($waitingSyncCmd as $cmd) {
                    $this->em->remove($cmd);
                }
                $this->em->flush();
            }

            if ($restaurant != null) {
                $cmd = new SyncCmdQueue();
                $cmd->setParams(
                    json_encode(
                        [
                            'globalProductID' => is_null($product->getGlobalProductID()) ? $product->getId(
                            ) : $product->getGlobalProductID(),
                        ]
                    )
                )
                    ->setStatus(SyncCmdQueue::WAITING)
                    ->setCmd(SyncCmdQueue::DOWNLOAD_SOLD_ITEMS)
                    ->setProduct($product)
                    ->setOriginRestaurant($restaurant);
                if ($force == false) {
                    $cmd->setSyncDate($product->getDateSynchro());
                } else {
                    $cmd->setSyncDate(null);
                }
                $this->em->persist($cmd);
            }
            else
            {
                foreach ($product->getRestaurants() as $restaurant) {
                    $cmd = new SyncCmdQueue();
                    $cmd->setParams(
                        json_encode(
                            [
                                'globalProductID' => is_null($product->getGlobalProductID()) ? $product->getId(
                                ) : $product->getGlobalProductID(),
                            ]
                        )
                    )
                        ->setStatus(SyncCmdQueue::WAITING)
                        ->setCmd(SyncCmdQueue::DOWNLOAD_SOLD_ITEMS)
                        ->setProduct($product)
                        ->setOriginRestaurant($restaurant);
                    if ($force == false) {
                        $cmd->setSyncDate($product->getDateSynchro());
                    } else {
                        $cmd->setSyncDate(null);
                    }
                    $this->em->persist($cmd);
                }
            }

            $this->em->flush();
        } catch (\Exception $e) {
            //dump("here " .$e->getMessage());die;
            throw $e;
        }
    }
}
