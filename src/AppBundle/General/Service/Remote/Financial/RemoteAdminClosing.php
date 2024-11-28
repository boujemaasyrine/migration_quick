<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 09:01
 */

namespace AppBundle\General\Service\Remote\Financial;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\ToolBox\Utils\Utilities;

class RemoteAdminClosing extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::ADMIN_CLOSING;
    }

    /**
     * @param AdministrativeClosing[] $items
     */
    public function send($items, $idSyncCmd = null)
    {
        $data = [];
        //Create the data
        foreach ($items as $d) {
            $oData = array(
                'id' => $d->getId(),
                'date' => $d->getDate()->format('Y-m-d'),
                'comparable' => $d->getComparable(),
                'comment' => $d->getComment(),
                'createdAt' => $d->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $d->getUpdatedAt()->format('Y-m-d H:i:s'),
                'creditAmount' => $d->getCreditAmount(),
            );
            $data['data'][] = $oData;
        }

        $response = $this->startUpload($this->params['admin_closing'], $data, $idSyncCmd);
        $events = Utilities::removeEvents(AdministrativeClosing::class, $this->em);
        foreach ($response as $o) {
            $ca = $this->em->getRepository("Financial:AdministrativeClosing")->find($o['id']);
            if ($ca && $o['status'] == 1) {
                $ca->setSynchronized(true);
            }
        }
        $this->em->flush();
        Utilities::returnEvents(AdministrativeClosing::class, $this->em, $events);
    }

    public function start($idSyncCmd = null)
    {
        $this->preUpload();
        $exist = true;
        $step = 10;
        //Get items not synchronized
        $allItems = $this->em->getRepository("Financial:AdministrativeClosing")->createQueryBuilder('d')
            ->where("d.synchronized != :true")
            ->setParameter('true', true)
            ->getQuery()
            ->getResult();


        while ($exist) {
            $items = array_slice($allItems, 0, $step);
            array_splice($allItems, 0, $step);

            if (count($items) > 0) {
                $this->send($items);
            } else {
                $exist = false;
            }
        }

        $this->uploadFinishWithSuccess();
    }
}
