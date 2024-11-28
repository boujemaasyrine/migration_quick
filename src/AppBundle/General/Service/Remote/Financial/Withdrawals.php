<?php

namespace AppBundle\General\Service\Remote\Financial;

use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Httpful\Httpful;
use Httpful\Request;
use Monolog\Logger;
use RestClient\CurlRestClient;

class Withdrawals extends SynchronizerService
{

    // Must synchronize enveloppes before

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::WITHDRAWALS;
    }

    /**
     * @param Withdrawal[] $withdrawals
     */
    public function serialize($withdrawals)
    {
        $data = [];
        //Create the data
        foreach ($withdrawals as $withdrawal) {
            /**
             * @var Withdrawal $withdrawal
             */
            $oData = array(
                'id' => $withdrawal->getId(),
                'date' => $withdrawal->getDate('Y-m-d H:i:s'),
                'amountWithdrawal' => $withdrawal->getAmountWithdrawal(),
                'statusCount' => $withdrawal->getStatusCount(),
                'member' => $withdrawal->getMember()->getGlobalEmployeeID(),
                'responsable' => $withdrawal->getResponsible()->getGlobalEmployeeID(),
                'envelopeId' => $withdrawal->getEnvelopeId(),
                'createdAt' => $withdrawal->getCreatedAt('Y-m-d H:i:s'),
                'updatedAt' => $withdrawal->getUpdatedAt('Y-m-d H:i:s'),
            );

            $data['data'][] = json_encode($oData);
        }

        return $data;
    }

    public function uploadWithdrawals($idCmd = null, $rawResponse = false)
    {
        parent::preUpload();
        //Get inventories not uploaded
        $withdrawals = $this->em->getRepository("Financial:Withdrawal")->createQueryBuilder('withdrawal')
            ->where("withdrawal.synchronized = false")
            ->orWhere("withdrawal.synchronized is NULL")
            ->getQuery()
            ->getResult();
        $success = null;
        $response = null;
        if (count($withdrawals)) {
            $data = $this->serialize($withdrawals);
            $response = parent::startUpload($this->params[$this->remoteHistoricType], $data, $idCmd);

            if (!is_null($response) && count($response['error']) === 0) {
                $events = Utilities::removeEvents(Withdrawal::class, $this->em);
                foreach ($withdrawals as $withdrawal) {
                    /**
                     * @var Withdrawal $withdrawal
                     */
                    $withdrawal->setSynchronized(true);
                }
                $this->em->flush();
                Utilities::returnEvents(Withdrawal::class, $this->em, $events);
                $this->uploadFinishWithSuccess();
                $success = true;
            } else {
                $this->uploadFinishWithFail();
                $success = false;
            }
        } else {
            $success = true;
        }

        if ($rawResponse) {
            return $response;
        } else {
            return $success;
        }
    }

    /**
     *
     * +
     *
     * @return array
     */
    public function start($idCmd = null)
    {
        return $this->uploadWithdrawals($idCmd);
    }
}
