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
use AppBundle\Merchandise\Entity\CategoryGroup;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\SoldingCanal;

class DownloadRestaurantParameters extends AbstractDownloaderService
{
    public function download($idSynCmd = null)
    {
        $this->logger->addDebug("Start Download Restaurant Parameters", ['Download', 'DownloadRestaurantParameters']);
        $data = $this->startDownload($this->supervisionParams['restaurant_parameters'], $idSynCmd);
        if (isset($data['data'])) {
            try {
                $this->em->beginTransaction();
                $item = json_decode($data['data'], true);

                // EFT
                $this->logger->addDebug("EFT is set to : ".$item['eft'], ['Download', 'DownloadRestaurantParameters']);
                $parameter = $this->em->getRepository('Administration:Parameter')->findOneBy(
                    ['type' => Parameter::EFT_ACTIVATED_TYPE]
                );
                if (!$parameter) {
                    $parameter = new Parameter();
                    $parameter->setType(Parameter::EFT_ACTIVATED_TYPE);
                    $this->em->persist($parameter);
                }
                $parameter->setValue($item['eft'] ? true : false);
                $this->em->flush();

                // Payment methods status
                $pms = $this->em->getRepository("Financial:PaymentMethod")->findAll();
                if (isset($item['activePaymentMethods'])) {
                    // Non existant payment methods is not handled, this import require the download of all payment methods before.
                    foreach ($pms as $pm) {
                        /**
                         * @var PaymentMethod $pm
                         */
                        if (in_array($pm->getGlobalId(), $item['activePaymentMethods'])) {
                            $active = true;
                        } else {
                            $active = false;
                        }
                        $pm->setActive($active);
                        $this->logger->addDebug(
                            "Payment method status ".$pm->getLabel()." is set to : ".$pm->isActive(
                            ) ? 'active' : 'inactive',
                            ['Download', 'DownloadRestaurantParameters']
                        );
                    }
                }

                $this->em->flush();
                $this->em->commit();
            } catch (\Exception $e) {
                $this->em->rollback();
                $this->logger->addAlert($e->getMessage(), ['Download', 'DownloadRestaurantParameters']);
                throw $e;
            }
        }
    }
}
