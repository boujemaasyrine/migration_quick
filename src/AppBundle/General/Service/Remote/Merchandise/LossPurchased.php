<?php

namespace AppBundle\General\Service\Remote\Merchandise;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Httpful\Httpful;
use Httpful\Request;
use Monolog\Logger;
use RestClient\CurlRestClient;

class LossPurchased extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::LOSS_PURCHASED;
    }

    /**
     * @param LossSheet[] $losses
     * @return array
     */
    public function serialize($losses)
    {
        //Create the data
        $data = [];
        foreach ($losses as $loss) {
            $oData = array(
                'id' => $loss->getId(),
                'employee' => $loss->getEmployee()->getGlobalEmployeeID(),
                'entryDate' => $loss->getEntryDate('Y-m-d H:i:s'),
                'createdAt' => $loss->getCreatedAt('Y-m-d H:i:s'),
                'updatedAt' => $loss->getUpdatedAt('Y-m-d H:i:s'),
                'type' => $loss->getType(),
                'sheetModelLabel' => $loss->getSheetModelLabel(),
            );
            $lines = [];
            foreach ($loss->getLossLines() as $line) {
                /**
                 * @var LossLine $line
                 */
                if (!is_null($line->getTotalLoss()) && $line->getTotalLoss() != 0) {
                    $lines[] = [
                        'product' => $line->getProduct()->getGlobalProductID(),
                        'firstEntry' => $line->getFirstEntry(),
                        'secondEntry' => $line->getSecondEntry(),
                        'thirdEntry' => $line->getThirdEntry(),
                        'totalLoss' => $line->getTotalLoss(),
                        'createdAt' => $line->getCreatedAt('Y-m-d H:i:s'),
                        'updatedAt' => $line->getUpdatedAt('Y-m-d H:i:s'),
                        'totalRevenuePrice' => $line->getTotalRevenuePrice(),
                    ];
                }
            }
            $oData['lossLines'] = $lines;
            $data['data'][] = $oData;
        }

        return $data;
    }

    public function uploadLosses($idSynCmd = null, $rawResponse = false)
    {
        parent::preUpload();
        //Get losses not uploaded
        $losses = $this->em->getRepository("Merchandise:LossSheet")->createQueryBuilder('lossSheet')
            ->select(['lossSheet', 'lossLines'])
            ->leftJoin('lossSheet.lossLines', 'lossLines')
            ->where("lossSheet.synchronized = false")
            ->andWhere('lossSheet.type = :purchased')
            ->setParameter('purchased', LossSheet::ARTICLE)
            ->getQuery()->getResult();
        if (count($losses) > 0) {
            $data = $this->serialize($losses);
            $response = parent::startUpload($this->params['loss_purchased_item'], $data, $idSynCmd);
            $success = $response['error'] == null;
            if ($response['error'] == null) {
                $events = Utilities::removeEvents(LossSheet::class, $this->em);
                foreach ($losses as $loss) {
                    /**
                     * @var LossSheet $loss
                     */
                    $loss->setSynchronized(true);
                }
                $this->em->flush();
                Utilities::returnEvents(LossSheet::class, $this->em, $events);
                $this->uploadFinishWithSuccess();
            } else {
                $this->uploadFinishWithFail();
            }

            return $rawResponse ? $response : $success;
        } else {
            return true;
        }
    }

    /**
     *
     * +
     *
     * @return array
     */
    public function start($idSynCmd = false)
    {
        return $this->uploadLosses($idSynCmd);
    }
}
