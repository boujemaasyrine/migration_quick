<?php

namespace AppBundle\General\Service\Remote\Financial;

use AppBundle\Financial\Entity\ChestCount;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use Doctrine\ORM\EntityManager;
use Httpful\Httpful;
use Httpful\Request;
use Monolog\Logger;
use RestClient\CurlRestClient;

class ChestCounts extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::CHEST_COUNTS;
    }

    /**
     * @param ChestCount[] $chestCounts
     */
    public function serialize($chestCounts)
    {
        //Create the data
        foreach ($chestCounts as $loss) {
            $oData = array(
                'id' => $loss->getId(),
                'fiscalDate' => $loss->getFiscalDate('Y-m-d'),
                'employee' => $loss->getEmployee()->getGlobalEmployeeID(),
                'status' => $loss->getStatus(),
                'sheetModelLabel' => $loss->getSheetModelLabel(),
                'createdAt' => $loss->getCreatedAt('Y-m-d H:i:s'),
                'updatedAt' => $loss->getUpdatedAt('Y-m-d H:i:s'),
            );
            $lines = [];
            foreach ($loss->getLossLines() as $line) {
                /**
                 * @var InventoryLine $line
                 */
                if (!is_null($line->getTotalInventoryCnt())) {
                    $lines[] = [
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
            $oData['lines'] = $lines;
            $data['data'][] = $oData;
        }
        $data['token'] = 'yyy';

        return $data;
    }

    public function uploadInventories()
    {
        parent::preUpload();
        //Get inventories not uploaded
        if ($this->lastSynchro) {
            $chestCounts = $this->em->getRepository("Merchandise:InventorySheet")->createQueryBuilder('inventorySheet')
                ->leftJoin('inventorySheet.lines', 'lines')
                ->where("inventorySheet.synchronized = false")
                ->getQuery()->getResult();
        } else {
            $chestCounts = $this->em->getRepository("Merchandise:InventorySheet")->createQueryBuilder('inventorySheet')
                ->select(['inventorySheet', 'lines'])
                ->leftJoin('inventorySheet.lines', 'lines')
                ->getQuery()->getResult();
        }
        $data = $this->serialize($chestCounts);
        $response = parent::startUpload($this->params['inventories'], $data);

        if ($response['error'] == null) {
            foreach ($chestCounts as $loss) {
                /**
                 * @var ChestCount $loss
                 */
                $loss->setSynchronized(true);
            }
            $this->em->flush();
            $this->uploadFinishWithSuccess();

            return true;
        } else {
            $this->uploadFinishWithFail();

            return false;
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
        return $this->uploadInventories();
    }
}
