<?php

namespace AppBundle\General\Service\Remote\Merchandise;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Entity\SheetModelLine;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Httpful\Httpful;
use Httpful\Request;
use Monolog\Logger;
use RestClient\CurlRestClient;

class SheetModels extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::SHEET_MODELS;
    }

    /**
     * @param SheetModel[] $sheetModels
     * @return array
     */
    public function serialize($sheetModels)
    {
        $data = [];
        //Create the data
        foreach ($sheetModels as $sheetModel) {
            $this->logger->addInfo('Sheetmodel lines count : '.count($sheetModel->getLines()), ['SheetModels']);
            $oData = $sheetModel->serialize();
            $this->logger->addInfo('Upload sheetmodel  for Employee globalId : '.$oData['employee'], ['SheetModels']);
            foreach ($sheetModel->getLines() as $line) {
                /**
                 * @var SheetModelLine $line
                 */
                $oData['lines'][] = $line->serialize();
            }
            $data['data'][] = json_encode($oData);
        }

        return $data;
    }

    public function uploadSheetModels($idSynCmd = null)
    {
        parent::preUpload();
        //Get sheetModels not uploaded
        $sheetModels = $this->em->getRepository("Merchandise:SheetModel")->createQueryBuilder('sheetModel')
            ->leftJoin('sheetModel.employee', 'employee')
            ->leftJoin('sheetModel.lines', 'lines')
            ->select('sheetModel', 'lines', 'employee')
            ->where("sheetModel.synchronized = false")
            ->orWhere("sheetModel.synchronized is null")
            ->getQuery()->getResult();
        if (count($sheetModels)) {
            $data = $this->serialize($sheetModels);
            $response = parent::startUpload($this->params[$this->remoteHistoricType], $data, $idSynCmd);
            if ($response['error'] == null) {
                $events = Utilities::removeEvents(SheetModel::class, $this->em);
                foreach ($sheetModels as $sheetModel) {
                    /**
                     * @var SheetModel $sheetModel
                     */
                    $sheetModel->setSynchronized(true);
                }
                $this->em->flush();
                Utilities::returnEvents(SheetModel::class, $this->em, $events);
                $this->uploadFinishWithSuccess();

                return true;
            } else {
                $this->uploadFinishWithFail();

                return false;
            }
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
    public function start($idSynCmd = null)
    {
        return $this->uploadSheetModels($idSynCmd);
    }
}
