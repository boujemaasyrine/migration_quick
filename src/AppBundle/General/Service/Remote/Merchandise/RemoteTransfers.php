<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 09:01
 */

namespace AppBundle\General\Service\Remote\Merchandise;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\Transfer;
use AppBundle\ToolBox\Utils\Utilities;

class RemoteTransfers extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::TRANSFERS;
    }

    public function start($idSynCmd = null)
    {

        $this->preUpload();
        $step = 50;
        $exist = true;
        $allTransfers = $this->em->getRepository("Merchandise:Transfer")->createQueryBuilder('d')
            ->where("d.synchronized != :true")
            ->setParameter('true', true)->getQuery()->getResult();
        while ($exist) {
            //Get orders not remoted
            $transfers = array_slice($allTransfers, 0, $step);
            array_splice($allTransfers, 0, $step);

            if (count($transfers) > 0) {
                $this->send($transfers, $idSynCmd);
            } else {
                $exist = false;
            }
        }

        $this->uploadFinishWithSuccess();
    }

    /**
     * @param Transfer[] $transfers
     */
    public function send($transfers, $idSynCmd = null)
    {
        $data = [];
        //Create the data
        foreach ($transfers as $d) {
            echo $d->getId()."\n";
            $oData = array(
                'id' => $d->getId(),
                'restaurant' => $d->getRestaurant()->getCode(),
                'createdAt' => $d->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $d->getUpdatedAt()->format('Y-m-d H:i:s'),
                'dateTransfer' => $d->getDateTransfer()->format('Y-m-d'),
                'type' => $d->getType(),
                'numTransfer' => $d->getNumTransfer(),
                'employee' => $d->getEmployee()->getGlobalEmployeeID(),
                'valorization' => $d->getValorization(),
            );
            $lines = [];
            foreach ($d->getLines() as $l) {
                $lines[] = array(
                    'product' => $l->getProduct()->getGlobalProductID(),
                    'qty' => $l->getQty(),
                    'qtyExp' => $l->getQtyExp(),
                    'qtyUse' => $l->getQtyUse(),
                    'createdAt' => $l->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updatedAt' => $l->getUpdatedAt()->format('Y-m-d H:i:s'),
                );
            }
            $oData['lines'] = $lines;
            $data['data'][] = $oData;
        }

        $response = $this->startUpload($this->params['transfers'], $data, $idSynCmd);
        $events = Utilities::removeEvents(Transfer::class, $this->em);
        foreach ($response as $o) {
            $transfer = $this->em->getRepository("Merchandise:Transfer")->find($o['id']);
            if ($transfer && $o['status'] == 1) {
                $transfer->setSynchronized(true);
            }
        }
        $this->em->flush();
        Utilities::returnEvents(Transfer::class, $this->em, $events);
    }
}
