<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 19/05/2016
 * Time: 11:05
 */

namespace AppBundle\General\Service\Remote\Merchandise;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\Order;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Httpful\Httpful;
use Httpful\Request;
use RestClient\CurlRestClient;

class RemoteOrders extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::ORDERS;
    }

    public function start($idSynCmd = null)
    {

        $this->preUpload();
        $events = Utilities::removeEvents(Order::class, $this->em);
        $step = 50;
        $exist = true;
        $response = [];
        $allOrders = $this->em->getRepository("Merchandise:Order")->createQueryBuilder('o')
            ->where("o.synchronized != :true")
            ->setParameter('true', true)->getQuery()->getResult();
        while ($exist) {
            //Get orders not remoted
            $orders = array_slice($allOrders, 0, $step);
            array_splice($allOrders, 0, $step);

            if (count($orders) > 0) {
                $response = array_merge($response, $this->send($orders, $idSynCmd));
            } else {
                $exist = false;
            }
        }
        foreach ($response as $o) {
            echo $o['id']." ".$o['error']."\n";
            if ($o['id'] !== null) {
                $order = $this->em->getRepository("Merchandise:Order")->find($o['id']);
            } else {
                $order = null;
            }

            if ($order && $o['status'] == 1) {
                $order->setSynchronized(true);
            } else {
                if ($order) {
                    $order->setSynchronized(false);
                }
            }
        }
        $this->em->flush();
        Utilities::returnEvents(Order::class, $this->em, $events);

        $this->uploadFinishWithSuccess();
    }

    /**
     * @param Order[] $orders
     * @return array
     */
    public function send($orders, $idSynCmd = null)
    {
        $data = [];
        //Create the data
        foreach ($orders as $o) {
            $oData = array(
                'id' => $o->getId(),
                'numOrder' => $o->getNumOrder(),
                'dateOrder' => $o->getDateDelivery()->format('Y-m-d'),
                'dateDelivery' => $o->getDateDelivery()->format('Y-m-d'),
                'status' => $o->getStatus(),
                'createdAt' => $o->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $o->getUpdatedAt()->format('Y-m-d H:i:s'),
                'supplier' => $o->getSupplier()->getCode(),
                'employee' => $o->getEmployee()->getGlobalEmployeeID(),
            );
            $lines = [];
            foreach ($o->getLines() as $l) {
                $lines[] = array(
                    'product' => $l->getProduct()->getGlobalProductID(),
                    'qty' => $l->getQty(),
                    'createdAt' => $l->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updatedAt' => $l->getUpdatedAt()->format('Y-m-d H:i:s'),
                );
            }
            $oData['lines'] = $lines;
            $data['data'][] = $oData;
        }
        $this->em->flush();
        $response = $this->startUpload($this->params['orders'], $data, $idSynCmd);

        //If sended, mark as sended, else create a notification
        return $response;
    }
}
