<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 18:30
 */

namespace AppBundle\General\Service\Download;

use AppBundle\Merchandise\Entity\CategoryGroup;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\SoldingCanal;

class DownloadSoldingCanals extends AbstractDownloaderService
{
    public function download($idSynCmd = null)
    {
        echo "Start Download Solding canals \n";
        $data = $this->startDownload($this->supervisionParams['solding_canals'], $idSynCmd);
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $item) {
                echo "Downloading Solding Canal ".$item['label']." \n";
                $soldingCanal = $this->em->getRepository("Merchandise:SoldingCanal")->findOneBy(
                    array(
                        'globalId' => $item['globalId'],
                    )
                );

                if (!$soldingCanal) {
                    echo "New Solding Canal ".$item['label']." \n";
                    $soldingCanal = new SoldingCanal();
                    $soldingCanal->setGlobalId($item['globalId']);
                    $this->em->persist($soldingCanal);
                }

                $soldingCanal
                    ->setLabel($item['label'])
                    ->setType($item['type'])
                    ->setDefault($item['default'])
                    ->setWyndMppingColumn($item['wyndMappingColumn']);

                $this->em->flush();
            }
        }
    }
}
