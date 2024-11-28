<?php

namespace AppBundle\General\Service\Remote\Financial;

use AppBundle\Financial\Entity\Deposit;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Httpful\Httpful;
use Httpful\Request;
use Monolog\Logger;
use RestClient\CurlRestClient;

class Deposits extends SynchronizerService
{

    // Expenses need to be uploaded first

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::DEPOSITS;
    }

    /**
     * @param Deposit[] $deposits
     */
    public function serialize($deposits)
    {
        $data = [];
        //Create the data
        foreach ($deposits as $deposit) {
            /**
             * @var Deposit $deposit
             */
            $oData = array(
                'id' => $deposit->getId(),
                'owner' => $deposit->getOwner()->getGlobalEmployeeID(),
                'expense' => $deposit->getExpense()->getId(),
                'reference' => $deposit->getReference(),
                'source' => $deposit->getSource(),
                'destination' => $deposit->getDestination(),
                'affiliateCode' => $deposit->getAffiliateCode(),
                'type' => $deposit->getType(),
                'sousType' => $deposit->getSousType(),
                'totalAmount' => $deposit->getTotalAmount(),
                'createdAt' => $deposit->getCreatedAt('Y-m-d H:i:s'),
                'updatedAt' => $deposit->getUpdatedAt('Y-m-d H:i:s'),
            );

            $data['data'][] = json_encode($oData);
        }

        return $data;
    }

    public function uploadDeposits($idCmd = null, $rawResponse = false)
    {
        parent::preUpload();
        //Get inventories not uploaded
        $deposits = $this->em->getRepository("Financial:Deposit")->createQueryBuilder('deposit')
            ->where("deposit.synchronized = false")
            ->orWhere("deposit.synchronized is NULL")
            ->getQuery()
            ->getResult();
        $success = null;
        $response = null;
        if (count($deposits)) {
            $data = $this->serialize($deposits);
            $response = parent::startUpload($this->params[$this->remoteHistoricType], $data, $idCmd);

            if (!is_null($response) && count($response['error']) === 0) {
                $events = Utilities::removeEvents(Deposit::class, $this->em);
                foreach ($deposits as $deposit) {
                    /**
                     * @var Deposit $deposit
                     */
                    $deposit->setSynchronized(true);
                }
                $this->em->flush();
                Utilities::returnEvents(Deposit::class, $this->em, $events);
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
        return $this->uploadDeposits($idCmd);
    }
}
