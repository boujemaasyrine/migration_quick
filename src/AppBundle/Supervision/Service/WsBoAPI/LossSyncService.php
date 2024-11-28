<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 20/05/2016
 * Time: 09:50
 */

namespace AppBundle\Supervision\Service\WsBoAPI;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\NoResultException;

class LossSyncService extends AbstractSyncService
{

    /**
     * @param $lossSheets
     * @param Restaurant    $restaurant
     * @param $lossSheetType
     * @return array
     * @throws \Exception
     */
    public function deserialize($lossSheets, Restaurant $restaurant, $lossSheetType)
    {
        $result = [];
        foreach ($lossSheets as $lossSheet) {
            $newLossSheet = new LossSheet();
            try {
                $employee = $this->em->getRepository('AppBundle:Staff\Employee')
                    ->createQueryBuilder('staff\employee')
                    ->select('staff\employee')
                    ->where('staff\employee.globalEmployeeID = :id')
                    ->setParameter('id', $lossSheet['employee'])
                    ->getQuery()
                    ->setMaxResults(1)
                    ->getSingleResult();
            } catch (NoResultException $e) {
                $this->logger->addAlert(
                    'Uknown employee code ('.$lossSheet['employee'].') '.$e->getMessage(),
                    ['LossSyncService', 'deserialize', 'UknownProduct']
                );
                throw new \Exception("Employee : ".$lossSheet['employee']." not found.");
            }

            $existantLossSheet = $this->em->getRepository('AppBundle:Merchandise\LossSheet')->findOneBy(
                array(
                    'originalID' => $lossSheet['id'],
                    'originRestaurant' => $restaurant,
                )
            );
            if (!is_null($existantLossSheet)) {
                $this->em->remove($existantLossSheet);
                $this->em->flush();
            }
            $newLossSheet
                ->setOriginalID($lossSheet['id'])
                ->setEmployee($employee)
                ->setEntryDate($lossSheet['entryDate'], 'Y-m-d H:i:s')
                ->setCreatedAt($lossSheet['createdAt'], 'Y-m-d H:i:s')
                ->setType($lossSheetType)
                ->setSheetModelLabel($lossSheet['sheetModelLabel'])
                ->setOriginRestaurant($restaurant);
            if (array_key_exists('updatedAt', $lossSheet)) {
                $newLossSheet->setUpdatedAt($lossSheet['updatedAt'], 'Y-m-d H:i:s');
            }

            if (isset($lossSheet['lossLines'])) {
                foreach ($lossSheet['lossLines'] as $line) {
                    $newLine = new LossLine();
                    try {
                        $product = $this->em->getRepository('AppBundle:Product')
                            ->createQueryBuilder('product')
                            ->select('product')
                            ->where('product.globalProductID = :productId')
                            ->setParameter('productId', $line['product'])
                            ->getQuery()
                            ->setMaxResults(1)
                            ->getSingleResult();
                        $newLine
                            ->setTotalRevenuePrice($line['totalRevenuePrice'])
                            ->setProduct($product)
                            ->setTotalLoss($line['totalLoss'])
                            ->setCreatedAt($line['createdAt'], 'Y-m-d H:i:s')
                            ->setUpdatedAt($line['updatedAt'], 'Y-m-d H:i:s');
                        if (array_key_exists('firstEntry', $line)) {
                            $newLine->setFirstEntry($line['firstEntry']);
                        }
                        if (array_key_exists('secondEntry', $line)) {
                            $newLine->setSecondEntry($line['secondEntry']);
                        }
                        if (array_key_exists('thirdEntry', $line)) {
                            $newLine->setThirdEntry($line['thirdEntry']);
                        }
                        if (array_key_exists('updatedAt', $line)) {
                            $newLine->setUpdatedAt($line['updatedAt'], 'Y-m-d H:i:s');
                        }
                    } catch (NoResultException $e) {
                        $this->logger->addAlert(
                            'Uknown product imported from Quick bo ('.$restaurant->getCode(
                            ).') with id : '.$line['product'],
                            ['LossSyncService', 'deserialize', 'UknownProduct']
                        );
                        throw new \Exception("Product : ".$line['product']." not found.");
                    }

                    if ($lossSheetType === LossSheet::FINALPRODUCT) {
                        try {
                            if (array_key_exists('recipe', $line)) {
                                $recipe = $this->em->getRepository('AppBundle:Recipe')
                                    ->createQueryBuilder('recipe')
                                    ->select('recipe')
                                    ->where('recipe.globalId = :recipeGloablId')
                                    ->setParameter('recipeGloablId', $line['recipe'])
                                    ->setMaxResults(1)
                                    ->getQuery()
                                    ->getSingleResult();
                                $newLine->setRecipe($recipe);
                            }
                        } catch (NoResultException $e) {
                            $this->logger->addAlert(
                                'Uknown recipe imported from Quick bo ('.$restaurant->getCode(
                                ).') with id : '.$line['product'],
                                ['LossSyncService', 'deserialize', 'UknownProduct']
                            );
                            throw new \Exception("Product : ".$line['product']." not found.");
                        }
                    }
                    $newLossSheet->addLossLine($newLine);
                }
            }

            $result[] = $newLossSheet;
        }

        return $result;
    }

    public function importLossSheets($lossSheetsData, Restaurant $restaurant, $lossSheetType)
    {
        try {
            $this->em->beginTransaction();
            $lossSheets = $this->deserialize($lossSheetsData, $restaurant, $lossSheetType);
            foreach ($lossSheets as $lossSheet) {
                $this->em->persist($lossSheet);
                $this->em->flush();
            }
            $this->em->commit();
            if (LossSheet::ARTICLE === $lossSheetType) {
                $this->remoteHistoricService
                    ->createSuccessEntry($restaurant, RemoteHistoric::LOSS_PURCHASED, []);
            } elseif (LossSheet::FINALPRODUCT === $lossSheetType) {
                $this->remoteHistoricService
                    ->createSuccessEntry($restaurant, RemoteHistoric::LOSS_SOLD, []);
            }
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->addAlert(
                'Exception occured when importing loss sheets, import was rollback : '.$e->getMessage(),
                ['LossSyncService', 'importLossSheets']
            );
            throw new \Exception($e);
        }
    }
}
