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
use AppBundle\Merchandise\Entity\Returns;
use AppBundle\ToolBox\Utils\Utilities;

class RemoteReturns extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::RETURNS;
    }

    public function start($idSynCmd = null)
    {

        $this->preUpload();

        $step = 50;
        $exist = true;
        $allReturns = $this->em->getRepository("Merchandise:Returns")->createQueryBuilder('d')
            ->where("d.synchronized != :true")
            ->setParameter('true', true)->getQuery()->getResult();
        while ($exist) {
            $returns = array_slice($allReturns, 0, $step);
            array_splice($allReturns, 0, $step);

            if (count($returns) > 0) {
                $this->send($returns, $idSynCmd);
            } else {
                $exist = false;
            }
        }
        $this->uploadFinishWithSuccess();
    }

    /**
     * @param Returns[] $returns
     */
    public function send($returns, $idSynCmd = null)
    {
        $data = [];
        //Create the data
        foreach ($returns as $d) {
            $oData = array(
                'id' => $d->getId(),
                'date' => $d->getDate()->format('Y-m-d'),
                'createdAt' => $d->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $d->getUpdatedAt()->format('Y-m-d H:i:s'),
                'supplier' => $d->getSupplier()->getCode(),
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

        $response = $this->startUpload($this->params['returns'], $data, $idSynCmd);
        $events = Utilities::removeEvents(Returns::class, $this->em);
        foreach ($response as $o) {
            $transfer = $this->em->getRepository("Merchandise:Returns")->find($o['id']);
            if ($transfer && $o['status'] == 1) {
                $transfer->setSynchronized(true);
            }
        }
        $this->em->flush();
        Utilities::returnEvents(Returns::class, $this->em, $events);
    }
}
