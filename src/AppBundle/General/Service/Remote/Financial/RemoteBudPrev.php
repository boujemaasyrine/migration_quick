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
use AppBundle\Merchandise\Entity\CaPrev;
use AppBundle\ToolBox\Utils\Utilities;

class RemoteBudPrev extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::BUDGET_PREVISIONNELS;
    }


    /**
     * @param CaPrev[] $items
     */
    public function send($items)
    {
        $data = [];
        //Create the data
        foreach ($items as $d) {
            echo $d->getDate()->format('Y-m-d')."\n";
            $oData = array(
                'id' => $d->getId(),
                'date' => $d->getDate()->format('Y-m-d'),
                'date1' => $d->getDate1()->format('Y-m-d'),
                'date2' => $d->getDate2()->format('Y-m-d'),
                'date3' => $d->getDate3()->format('Y-m-d'),
                'date4' => $d->getDate4()->format('Y-m-d'),
                'date5' => $d->getDate5()->format('Y-m-d'),
                'date6' => $d->getDate6()->format('Y-m-d'),
                'date7' => $d->getDate7()->format('Y-m-d'),
                'date8' => $d->getDate8()->format('Y-m-d'),
                'ca' => $d->getCa(),
                'variance' => $d->getVariance(),
                'fixed' => $d->getFixed(),
                'isTyped' => $d->getIsTyped(),
                'comparableDay' => ($d->getComparableDay()) ? $d->getComparableDay()->format('Y-m-d') : null,
            );
            $data['data'][] = $oData;
        }

        $response = $this->startUpload($this->params['bud_prev'], $data);
        $events = Utilities::removeEvents(CaPrev::class, $this->em);
        foreach ($response as $o) {
            $ca = $this->em->getRepository("Merchandise:CaPrev")->find($o['id']);
            if ($ca && $o['status'] == 1) {
                $ca->setSynchronized(true);
            }
        }
        $this->em->flush();
        Utilities::returnEvents(CaPrev::class, $this->em, $events);
    }

    public function start($idSyncCmd = null)
    {
        $step = 50;
        $exist = true;

        $allItems = $this->em->getRepository("Merchandise:CaPrev")->createQueryBuilder('d')
            ->where("d.synchronized != :true")
            ->setParameter('true', true)
            ->getQuery()
            ->getResult();

        while ($exist) {
            $this->preUpload();
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
