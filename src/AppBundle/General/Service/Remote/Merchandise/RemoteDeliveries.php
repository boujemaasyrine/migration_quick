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
use AppBundle\Merchandise\Entity\Delivery;
use AppBundle\ToolBox\Utils\Utilities;

class RemoteDeliveries extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::DELIVERIES;
    }

    /**
     * @param Delivery[] $deliveries
     */
    public function send($deliveries, $idSynCmd = null)
    {
        $data = [];
        //Create the data
        foreach ($deliveries as $d) {
            $oData = array(
                'id' => $d->getId(),
                'date' => $d->getDate()->format('Y-m-d'),
                'deliveryBordereau' => $d->getDeliveryBordereau(),
                'createdAt' => $d->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $d->getUpdatedAt()->format('Y-m-d H:i:s'),
                'order' => $d->getOrder()->getId(),
                'employee' => $d->getEmployee()->getGlobalEmployeeID(),
                'val' => $d->getValorization(),
            );
            $lines = [];
            foreach ($d->getLines() as $l) {
                $lines[] = array(
                    'product' => $l->getProduct()->getGlobalProductID(),
                    'qty' => $l->getQty(),
                    'createdAt' => $l->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updatedAt' => $l->getUpdatedAt()->format('Y-m-d H:i:s'),
                    'val' => $l->getValorization(),
                );
            }
            $oData['lines'] = $lines;
            $data['data'][] = $oData;
        }
        $response = $this->startUpload($this->params['deliveries'], $data, $idSynCmd);
        $events = Utilities::removeEvents(Delivery::class, $this->em);
        foreach ($response as $o) {
            if (isset($o['id'])) {
                $delivery = $this->em->getRepository("Merchandise:Delivery")->find($o['id']);
                if ($delivery && $o['status'] == 1) {
                    $delivery->setSynchronized(true);
                }
            }
        }
        $this->em->flush();
        Utilities::returnEvents(Delivery::class, $this->em, $events);
    }

    public function start($idSynCmd = null)
    {
        $this->preUpload();
        $step = 50;
        $exist = true;
        $allDeliveries = $this->em->getRepository("Merchandise:Delivery")->createQueryBuilder('d')
            ->where("d.synchronized != :true")
            ->setParameter('true', true)->getQuery()->setMaxResults($step)->getResult();
        while ($exist) {
            $deliveries = array_slice($allDeliveries, 0, $step);
            array_splice($allDeliveries, 0, $step);

            if (count($deliveries) > 0) {
                $this->send($deliveries, $idSynCmd);
            } else {
                $exist = false;
            }
        }

        $this->uploadFinishWithSuccess();
    }
}
