<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 26/05/2016
 * Time: 09:28
 */

namespace AppBundle\General\Service;

use AppBundle\General\Entity\SyncCmdQueue;
use AppBundle\General\Service\Download\AbstractDownloaderService;

class GetCmdService extends AbstractDownloaderService
{

    public function download($idSynCmd = null)
    {
        echo "Start Getting Cmd \n";
        echo $this->supervisionParams['cmd']."\n";
        $data = $this->startDownload($this->supervisionParams['cmd'], $idSynCmd);
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $item) {
                echo "Downloading Cmd ".$item['cmd'].' '.$item['id']." \n";
                $objs = $this->em->getRepository('General:SyncCmdQueue')
                    ->findBy(
                        [
                            'globalId' => $item['id'],
                        ]
                    );
                if (count($objs)) {
                    foreach ($objs as $obj) {
                        $this->em->remove($obj);
                    }
                    $this->em->flush();
                }
                $obj = new SyncCmdQueue();
                if (is_array($item['params'])) {
                    $item['params'] = json_encode($item['params']);
                }
                $obj->setStatus(SyncCmdQueue::WAITING)
                    ->setCmd($item['cmd'])
                    ->setParams($item['params'])
                    ->setDirection($item['direction'])
                    ->setOrder($item['order'])
                    ->setGlobalId($item['id']);
                $this->em->persist($obj);
                $this->em->flush();
            }
        }
    }
}
