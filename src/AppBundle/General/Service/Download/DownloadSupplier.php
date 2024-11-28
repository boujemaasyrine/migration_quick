<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 18:30
 */

namespace AppBundle\General\Service\Download;

use AppBundle\Merchandise\Entity\Supplier;
use Doctrine\ORM\EntityManager;

class DownloadSupplier extends AbstractDownloaderService
{
    public function download($idSynCmd = null)
    {
        echo "Start Download Suppliers \n";
        $data = $this->startDownload($this->supervisionParams['suppliers'], $idSynCmd);
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $item) {
                echo "Downloading supplier ".$item['name']." \n";
                $supplier = $this->em->getRepository(Supplier::class)->findOneBy(
                    array(
                        'code' => $item['code'],
                    )
                );

                if (!$supplier) {
                    echo "New Supplier ".$item['name']." \n";
                    $supplier = new Supplier();
                    $supplier->setCode($item['code']);
                    $this->em->persist($supplier);
                }

                $supplier->setActive($item['active'])
                    ->setAddress($item['address'])
                    ->setDesignation($item['designation'])
                    ->setEmail($item['email'])
                    ->setName($item['name'])
                    ->setPhone($item['phone'])
                    ->setZone($item['zone']);

                $this->em->flush();
            }
        }
    }
}
