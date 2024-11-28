<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 20/05/2016
 * Time: 09:50
 */

namespace AppBundle\Supervision\Service\WsBoAPI;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\Merchandise\Entity\InventoryLine;
use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\NoResultException;

class InventorySyncService extends AbstractSyncService
{

    /**
     * @param $inventories
     * @param Restaurant $restaurant
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function deserialize($inventories, Restaurant $restaurant)
    {
        $result = [];
        if (count($inventories) > 0) {
            foreach ($inventories as $inventory) {
                $inventory = json_decode($inventory, true);
                $existantInventory = $this->em->getRepository('AppBundle:Merchandise\InventorySheet')->findOneBy(
                    array(
                        'originalID' => $inventory['id'],
                        'originRestaurant' => $restaurant,
                    )
                );
                if (!is_null($existantInventory)) {
                    $this->em->remove($existantInventory);
                    $this->em->flush();
                }

                $newInventory = new InventorySheet();
                if (isset($inventory['employee'])) {
                    try {
                        $employee = $this->em->getRepository('AppBundle:Staff\Employee')
                            ->createQueryBuilder('staff\employee')
                            ->select('staff\employee')
                            ->where('staff\employee.globalEmployeeID = :id')
                            ->setParameter('id', $inventory['employee'])
                            ->getQuery()
                            ->setMaxResults(1)
                            ->getSingleResult();
                    } catch (NoResultException $e) {
                        $this->logger->addAlert(
                            'Uknown employee code ('.$inventory['employee'].') '.$e->getMessage(),
                            ['InventoryService', 'deserialize', 'UknownProduct']
                        );
                        throw new \Exception("Employee : ".$inventory['employee']." not found.");
                    }
                }

                $newInventory
                    ->setFiscalDate($inventory['fiscalDate'], 'Y-m-d')
                    ->setOriginalID($inventory['id'])
                    ->setStatus($inventory['status'])
                    ->setSheetModelLabel($inventory['sheetModelLabel'])
                    ->setEmployee($employee)
                    ->setCreatedAt($inventory['createdAt'], 'Y-m-d H:i:s')
                    ->setUpdatedAt($inventory['updatedAt'], 'Y-m-d H:i:s')
                    ->setOriginRestaurant($restaurant);

                if (isset($inventory['lines'])) {
                    foreach ($inventory['lines'] as $line) {
                        try {
                            $product = $this->em->getRepository('AppBundle:Product')
                                ->createQueryBuilder('product')
                                ->select('product')
                                ->where('product.globalProductID = :productId')
                                ->setParameter('productId', $line['product'])
                                ->getQuery()
                                ->setMaxResults(1)
                                ->getSingleResult();
                            if ($product instanceof ProductPurchased) {
                                $newLine = new InventoryLine();
                                $newLine
                                    ->setTotalInventoryCnt($line['totalInventoryCnt'])
                                    ->setInventoryCnt(floatval($line['inventoryCnt']))
                                    ->setUsageCnt(floatval($line['usageCnt']))
                                    ->setExpedCnt(floatval($line['expedCnt']))
                                    ->setCreatedAt($line['createdAt'], 'Y-m-d H:i:s')
                                    ->setUpdatedAt($line['updatedAt'], 'Y-m-d H:i:s')
                                    ->setProduct($product);
                            } else {
                                throw new \Exception(
                                    'Product : '.$product->getId(
                                    ).' , gloabl id product searched : '.$line['product'].' is a product sold ! purchased product expected.'
                                );
                            }
                            $newInventory->addLine($newLine);
                        } catch (NoResultException $e) {
                            $this->logger->addAlert(
                                'Uknown product imported from Quick bo ('.$restaurant->getCode(
                                ).') with id : '.$line['product'],
                                ['InventoryService', 'deserialize', 'UknownProduct']
                            );
                            throw $e;
                        }
                    }
                }
                $result[] = $newInventory;
            }
        }

        return $result;
    }

    public function importInventories($inventoriesData, Restaurant $restaurant)
    {
        $this->em->beginTransaction();
        try {
            $inventories = $this->deserialize($inventoriesData, $restaurant);
            foreach ($inventories as $inventory) {
                $this->em->persist($inventory);
                $this->em->flush();
            }
            $this->em->commit();
            $this->remoteHistoricService
                ->createSuccessEntry($restaurant, RemoteHistoric::DEPOSITS, []);
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->addAlert(
                'Exception occured when importing inventories, import was rollback : '.$e->getMessage(),
                ['InventoryService', 'ImportInventories']
            );
            throw new \Exception($e);
        }
    }
}
