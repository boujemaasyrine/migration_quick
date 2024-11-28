<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 18:30
 */

namespace AppBundle\General\Service\Download;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Supplier;
use Doctrine\ORM\EntityManager;

class DownloadPaymentMethods extends AbstractDownloaderService
{
    public function download($idSynCmd = null)
    {
        $this->logger->addDebug("Start Download Payment Methods", ['Download', 'DownloadPaymentMthods']);
        $data = $this->startDownload($this->supervisionParams['payment_methods'], $idSynCmd);
        if (isset($data['data']) && is_array($data['data'])) {
            try {
                foreach ($data['data'] as $item) {
                    $item = json_decode($item, true);
                    $this->logger->addDebug(
                        "Downloading Payment Method : ".$item['valueLabel'],
                        ['Download', 'DownloadPaymentMthods']
                    );
                    $pm = $this->em->getRepository("Financial:PaymentMethod")->findOneBy(
                        array(
                            'globalId' => $item['globalId'],
                        )
                    );
                    if ($pm) {
                        $pm->setType($item['parameterType'])
                            ->setLabel($item['valueLabel'])
                            ->setValue($item['value']);
                    } else {
                        $this->logger->addDebug(
                            "New Payment Method : ".$item['valueLabel'],
                            ['Download', 'DownloadPaymentMthods']
                        );
                        $pm = new PaymentMethod();
                        $pm->setType($item['parameterType'])
                            ->setLabel($item['valueLabel'])
                            ->setValue($item['value'])
                            ->setGlobalId($item['globalId']);
                        $this->em->persist($pm);
                        $parameter = new Parameter();
                        $parameter
                            ->setValue($pm->getValue())
                            ->setType($pm->getType())
                            ->setLabel($pm->getLabel())
                            ->setGlobalId($pm->getGlobalId());
                        $this->em->persist($parameter);
                    }
                    $this->em->flush();
                }
            } catch (\Exception $e) {
                $this->logger->addAlert($e->getMessage(), ['Download', 'DownloadPaymentMthods']);
                throw $e;
            }
        }
    }
}
