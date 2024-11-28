<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 11/07/2016
 * Time: 15:20
 */

namespace AppBundle\General\Service\Remote\Merchandise;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\ProductPurchasedMvmt;
use AppBundle\ToolBox\Utils\Utilities;

class ProductPurchasedMovements extends SynchronizerService
{
    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::PRODUCT_PURCHASED_MOVEMENTS;
    }

    public function uploadProductPurchasedMvmt($idCmd = null)
    {

        parent::preUpload();
        $this->logger->addInfo(
            'Uploading Product Purchased movement to Central.',
            ['Movement:uploadProductPurchasedMovement']
        );

        $param = $this->em->getRepository('Administration:Parameter')
            ->findOneBy(['type' => Parameter::MOVEMENT_UPLOAD]);
        $key = Utilities::generateRandomString(5);
        if ($param) {
            $this->logger->addInfo('Existing lock, checking timeout.', ['Movement:uploadProductPurchasedMovement']);
            // Check timeout
            $now = new \DateTime('now');
            $diffInSeconds = $now->getTimestamp() - $param->getUpdatedAt()->getTimestamp();
            // If Product Purchased movement lock wasn't updated since 1h delete it
            $this->logger->addInfo(
                'Lock isn\'t updated since '.$diffInSeconds.'second',
                ['Movement:uploadProductPurchasedMovement']
            );
            if ($diffInSeconds > 7200) {
                $this->logger->addInfo(
                    'Lock expired for uploading Product Purchased movement to Central.',
                    ['Movement:uploadProductPurchasedMovement']
                );
                $this->em->remove($param);
                $this->em->flush();

                $param = new Parameter();
                $param->setType(Parameter::MOVEMENT_UPLOAD)
                    ->setValue($key);
                $this->em->persist($param);
                $this->em->flush();
                $this->logger->addInfo(
                    'Launching upload with renew existing lock due to a timeout.',
                    ['Movement:uploadProductPurchasedMovement']
                );
                $this->launchUpload($idCmd, $key);
            } else {
                $this->logger->addInfo(
                    'Process exit because another process has lock.',
                    ['Movement:uploadProductPurchasedMovement']
                );

                return;
            }
        } else {
            $param = new Parameter();
            $param->setType(Parameter::MOVEMENT_UPLOAD)
                ->setValue($key);
            $this->em->persist($param);
            $this->em->flush();
            $this->logger->addInfo('Launching upload with new lock.', ['Movement:uploadProductPurchasedMovement']);
            $this->launchUpload($idCmd, $key);
        }
    }

    public function launchUpload($idCmd, $key)
    {
        try {
            //Get Movements paginated not uploaded
            $totalOfMovement = $this->em->getRepository("Merchandise:ProductPurchasedMvmt")->createQueryBuilder(
                'movement'
            )
                ->select('count(movement)')
                ->where("movement.synchronized = false")
                ->orWhere("movement.synchronized is NULL")
                ->getQuery()
                ->getSingleScalarResult();
            $this->logger->info(
                'Try to upload '.$totalOfMovement.' Product Purchased movements to Central.',
                ['uploadProductPurchasedMovement']
            );
            $max_per_page = 300;
            $pages = ceil($totalOfMovement / $max_per_page);
            $this->logger->info(
                'Pages : '.$pages.' , max per page : '.$max_per_page,
                ['uploadProductPurchasedMovement']
            );
            for ($i = 1; $i <= $pages; $i++) {
                $movements = $this->em->getRepository("Merchandise:ProductPurchasedMvmt")->createQueryBuilder(
                    'movement'
                )
                    ->where("movement.synchronized = false")
                    ->orWhere("movement.synchronized is NULL")
                    ->orderBy('movement.dateTime', 'desc')
                    ->setMaxResults(intval($max_per_page))
                    ->getQuery()
                    ->getResult();
                $this->logger->info(
                    'Page '.$i.' / '.$pages.' , Number of items : '.count($movements),
                    ['uploadProductPurchasedMovement']
                );
                if (count($movements)) {
                    $data = $this->serialize($movements);
                    $response = parent::startUpload($this->params[$this->remoteHistoricType], $data, $idCmd);
                    $this->logger->info(
                        'Number of error in response from central : '.count($response['error']),
                        ['uploadProductPurchasedMovement']
                    );
                    if (count($response['error']) === 0) {
                        $events = Utilities::removeEvents(ProductPurchasedMvmt::class, $this->em);
                        foreach ($movements as $movement) {
                            /**
                             * @var ProductPurchasedMvmt $movement
                             */
                            $movement->setSynchronized(true);
                        }
                        $this->em->flush();
                        Utilities::returnEvents(ProductPurchasedMvmt::class, $this->em, $events);
                        $this->uploadFinishWithSuccess();
                    } else {
                        $this->uploadFinishWithFail();
                    }
                } else {
                    $this->logger->info('No movements to upload in this page.', ['uploadProductPurchasedMovement']);
                }
                $param = $this->em->getRepository('Administration:Parameter')
                    ->findOneBy(['type' => Parameter::MOVEMENT_UPLOAD]);
                if ($param->getValue() == $key) {
                    $this->logger->addInfo(
                        'Updating current lock key : '.$key.'.',
                        ['Movement:uploadProductPurchasedMovement']
                    );
                    $param->setUpdatedAt(new \DateTime('now'));
                } else {
                    $this->logger->addInfo(
                        'Uploading this page took too much time and exceeded the timeout, process will be exited.',
                        ['Movement:uploadProductPurchasedMovement']
                    );

                    return;
                }
                $this->em->flush();
                $this->em->clear();
            }

            $this->logger->info(
                'Uploading Product Purchased movement finished, deleting lock.',
                ['Movement:uploadProductPurchasedMovement']
            );
            $param = $this->em->getRepository('Administration:Parameter')
                ->findOneBy(['type' => Parameter::MOVEMENT_UPLOAD]);
            $this->em->remove($param);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->addError($e->getMessage(), ['uploadProductPurchasedMovement']);
            $param = $this->em->getRepository('Administration:Parameter')
                ->findOneBy(['type' => Parameter::MOVEMENT_UPLOAD]);
            $this->em->remove($param);
            $this->em->flush();
            $this->em->clear();
            throw $e;
        }
    }

    public function start($idCmd = null)
    {
        $this->logger->addInfo(
            'Uploading Product Purchased movement to Central.',
            ['Movement:uploadProductPurchasedMovement']
        );

        return $this->uploadProductPurchasedMvmt($idCmd);
    }

    public function serialize($movements)
    {

        $data = array();
        foreach ($movements as $movement) {
            /**
             * @var ProductPurchasedMvmt $movement
             */
            $odata = array(
                'id' => $movement->getId(),
                'productID' => $movement->getProduct()->getGlobalProductID(),
                'variation' => $movement->getVariation(),
                'sourceId' => $movement->getSourceId(),
                'stockQty' => $movement->getStockQty(),
                'type' => $movement->getType(),
                'buyingCost' => $movement->getBuyingCost(),
                'labelUnitExped' => $movement->getLabelUnitExped(),
                'labelUnitInventory' => $movement->getLabelUnitInventory(),
                'labelUnitUsage' => $movement->getLabelUnitUsage(),
                'inventoryQty' => $movement->getInventoryQty(),
                'usageQty' => $movement->getUsageQty(),
                'createdAt' => $movement->getCreatedAt('Y-m-d H:i:s'),
                'updatedAt' => $movement->getUpdatedAt('Y-m-d H:i:s'),
                'dateTime' => $movement->getDateTime('Y-m-d H:i:s'),
                'deleted' => $movement->getDeleted(),
            );
            $data['data'][] = json_encode($odata);
        }

        return $data;
    }
}
