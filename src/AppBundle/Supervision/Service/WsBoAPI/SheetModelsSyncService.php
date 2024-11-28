<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 20/05/2016
 * Time: 09:50
 */

namespace AppBundle\Supervision\Service\WsBoAPI;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Entity\SheetModelLine;
use Doctrine\ORM\NoResultException;

class SheetModelsSyncService extends AbstractSyncService
{

    public function deserialize($sheetModels, Restaurant $restaurant)
    {
        $result = [];
        foreach ($sheetModels as $sheetModel) {
            $sheetModel = json_decode($sheetModel, true);
            $existantSheet = $this->em->getRepository('AppBundle:Merchandise\SheetModel')->findOneBy(
                array(
                    'originalID' => $sheetModel['id'],
                    'originRestaurant' => $restaurant,
                )
            );
            if (!is_null($existantSheet)) {
                $this->em->remove($existantSheet);
                $this->em->flush();
            }
            $newSheetModel = new SheetModel();
            $employee = $this->em->getRepository('AppBundle:Staff\Employee')
                ->findOneBy(['globalEmployeeID' => $sheetModel['employee']]);
            if (is_null($employee)) {
                throw new NoResultException();
            }

            $newSheetModel->setOriginalID($sheetModel['id'])
                ->setLabel($sheetModel['label'])
                ->setType($sheetModel['type'])
                ->setDeleted(boolval($sheetModel['deleted']))
                ->setCreatedAt($sheetModel['createdAt'], 'Y-m-d H:i:s')
                ->setUpdatedAt($sheetModel['updatedAt'], 'Y-m-d H:i:s')
                ->setEmployee($employee)
                ->setOriginRestaurant($restaurant);
            foreach ($sheetModel['lines'] as $line) {
                $newLine = new SheetModelLine();
                $product = $this->em->getRepository('AppBundle:Product')
                    ->findOneBy(['globalProductID' => $line['product']]);
                if (is_null($product)) {
                    $this->logger->addAlert('Product is not found :'.$line['product'], ['SheetModelsSyncService']);
                    throw new NoResultException();
                }
                $newLine->setGlobalId($line['id'])
                    ->setProduct($product)
                    ->setCnt($line['cnt'])
                    ->setOrderInSheet($line['orderInSheet'])
                    ->setCreatedAt($line['createdAt'], 'Y-m-d H:i:s')
                    ->setUpdatedAt($line['updatedAt'], 'Y-m-d H:i:s');
                $newSheetModel->addLine($newLine);
            }
            $result[] = $newSheetModel;
        }

        return $result;
    }

    public function importSheetModels($sheetModelsData, $restaurant)
    {
        $this->em->beginTransaction();
        try {
            $sheetModels = $this->deserialize($sheetModelsData, $restaurant);
            foreach ($sheetModels as $sheetModel) {
                $this->em->persist($sheetModel);
                $this->em->flush();
            }
            $this->em->commit();
            $this->remoteHistoricService
                ->createSuccessEntry($restaurant, RemoteHistoric::SHEET_MODELS, []);
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->addAlert(
                'Exception occured when importing sheet models, import was rollback : '.$e->getMessage(),
                ['SheetModelService', 'ImportSheetModels']
            );
            throw new \Exception($e);
        }
    }
}
