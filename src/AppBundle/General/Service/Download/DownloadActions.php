<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 18:30
 */

namespace AppBundle\General\Service\Download;

use AppBundle\Administration\Entity\Action;

class DownloadActions extends AbstractDownloaderService
{
    public function download($idSynCmd = null)
    {

        echo "Start Download Actions \n";
        $data = $this->startDownload($this->supervisionParams['actions'], $idSynCmd);
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $item) {
                echo "Downloading Action ".$item['name']." \n";
                $obj = $this->em->getRepository("Administration:Action")->findOneBy(
                    array(
                        'globalId' => $item['globalId'],
                    )
                );

                if (!$obj) {
                    echo "New Action ".$item['name']." \n";
                    $obj = new Action();
                    $obj->setGlobalId($item['globalId']);
                    $this->em->persist($obj);
                }

                $obj->setName($item['name'])
                    ->setRoute($item['route'])
                    ->setHasExit($item['hasExist'])
                    ->setIsPage($item['isPage'])
                    ->setParams($item['params']);

                $this->em->flush();
            }
        }
    }
}
