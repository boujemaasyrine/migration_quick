<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 09:01
 */

namespace AppBundle\General\Service\Remote\Financial;

use AppBundle\Financial\Entity\FinancialRevenue;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\ToolBox\Utils\Utilities;

class RemoteFinancialRevenue extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::FINANCIAL_REVENUES;
    }

    /**
     * @param FinancialRevenue[] $ca
     */
    public function send($ca, $idSyncCmd = null)
    {
        $data = [];
        //Create the data
        foreach ($ca as $d) {
            $oData = array(
                'id' => $d->getId(),
                'date' => $d->getDate()->format('Y-m-d'),
                'amount' => $d->getAmount(),
                'netHt' => $d->getNetHT(),
                'netTtc' => $d->getNetTTC(),
                'brutTtc' => $d->getBrutTTC(),
                'br' => $d->getBr(),
                'discount' => $d->getDiscount(),
                'brutHt' => $d->getBrutHT(),
            );
            $data['data'][] = $oData;
        }

        $response = $this->startUpload($this->params['financial_revenues'], $data, $idSyncCmd);
        $events = Utilities::removeEvents(FinancialRevenue::class, $this->em);
        foreach ($response as $o) {
            if (isset($o['id'])) {
                $ca = $this->em->getRepository("Financial:FinancialRevenue")->find($o['id']);
                if ($ca && $o['status'] == 1) {
                    $ca->setSynchronized(true);
                }
            }
        }
        $this->em->flush();
        Utilities::returnEvents(FinancialRevenue::class, $this->em, $events);
    }

    public function start($idSyncCmd = null)
    {
        $this->preUpload();
        $step = 100;
        $exist = true;
        //Get orders not synchronized
        $allCa = $this->em->getRepository("Financial:FinancialRevenue")->createQueryBuilder('d')
            ->where("d.synchronized != :true or d.synchronized IS NULL")
            ->setParameter('true', true)
            ->getQuery()
            ->getResult();

        while ($exist) {
            $ca = array_slice($allCa, 0, $step);
            array_splice($allCa, 0, $step);
            if (count($ca) > 0) {
                $this->send($ca, $idSyncCmd);
            } else {
                $exist = false;
            }
        }

        $this->uploadFinishWithSuccess();
    }
}
