<?php

namespace AppBundle\General\Service\Remote\Merchandise;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\InventoryLine;
use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Httpful\Httpful;
use Httpful\Request;
use Monolog\Logger;
use RestClient\CurlRestClient;

class Inventories extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::INVENTORIES;
    }

    /**
     * @param InventorySheet[] $inventories
     * @return array
     */
    public function serialize($inventories)
    {
        $data = [];
        //Create the data
        foreach ($inventories as $inventory) {
            if (is_object($inventory->getEmployee())) {
                $globalId = $inventory->getEmployee()->getGlobalEmployeeID();
            } else {
                $globalId = null;
            }
            $oData = array(
                'id' => $inventory->getId(),
                'fiscalDate' => $inventory->getFiscalDate('Y-m-d'),
                'employee' => $globalId,
                'status' => $inventory->getStatus(),
                'createdAt' => $inventory->getCreatedAt('Y-m-d H:i:s'),
                'updatedAt' => $inventory->getUpdatedAt('Y-m-d H:i:s'),
                'sheetModelLabel' => $inventory->getSheetModelLabel(),
            );
            foreach ($inventory->getLines() as $line) {
                /**
                 * @var InventoryLine $line
                 */
                if (!is_null($line->getTotalInventoryCnt())) {
                    $oData['lines'][] = [
                        'totalInventoryCnt' => $line->getTotalInventoryCnt(),
                        'inventoryCnt' => $line->getInventoryCnt(),
                        'usageCnt' => $line->getUsageCnt(),
                        'expedCnt' => $line->getExpedCnt(),
                        'product' => $line->getProduct()->getGlobalProductID(),
                        'createdAt' => $line->getCreatedAt('Y-m-d H:i:s'),
                        'updatedAt' => $line->getUpdatedAt('Y-m-d H:i:s'),
                    ];
                }
            }
            $data['data'][] = json_encode($oData);
        }
        $data['token'] = 'yyy';

        return $data;
    }

    public function uploadInventories($idSynCmd = null)
    {
        parent::preUpload();
        //Get inventories not uploaded
        $inventories = $this->em->getRepository("Merchandise:InventorySheet")->createQueryBuilder('inventorySheet')
            ->leftJoin('inventorySheet.lines', 'lines')
            ->where("inventorySheet.synchronized = false")
            ->getQuery()->getResult();
        if (count($inventories)) {
            $data = $this->serialize($inventories);
            $response = parent::startUpload($this->params[$this->remoteHistoricType], $data, $idSynCmd);
            if ($response['error'] == null) {
                $events = Utilities::removeEvents(InventorySheet::class, $this->em);
                foreach ($inventories as $inventory) {
                    /**
                     * @var InventorySheet $inventory
                     */
                    $inventory->setSynchronized(true);
                }
                $this->em->flush();
                Utilities::returnEvents(InventorySheet::class, $this->em, $events);
                $this->uploadFinishWithSuccess();

                return true;
            } else {
                $this->uploadFinishWithFail();

                return false;
            }
        } else {
            $this->uploadFinishWithSuccess();

            return true;
        }
    }

    /**
     *
     * +
     *
     * @return array
     */
    public function start($idSynCmd = null)
    {
        return $this->uploadInventories($idSynCmd);
    }
}
