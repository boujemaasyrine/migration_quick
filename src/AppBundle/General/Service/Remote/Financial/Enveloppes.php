<?php

namespace AppBundle\General\Service\Remote\Financial;

use AppBundle\Financial\Entity\Envelope;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Httpful\Httpful;
use Httpful\Request;
use Monolog\Logger;
use RestClient\CurlRestClient;

class Enveloppes extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::ENVELOPPES;
    }

    /**
     * @param Envelope[] $enveloppes
     */
    public function serialize($enveloppes)
    {
        $data = [];
        //Create the data
        foreach ($enveloppes as $enveloppe) {
            /**
             * @var Envelope $enveloppe
             */
            $oData = array(
                'id' => $enveloppe->getId(),
                'numEnvelope' => $enveloppe->getNumEnvelope(),
                'amount' => $enveloppe->getAmount(),
                'sourceId' => $enveloppe->getSourceId(),
                'source' => $enveloppe->getSource(),
                'owner' => $enveloppe->getOwner()->getGlobalEmployeeID(),
                'cashier' => is_null($enveloppe->getCashier()) ? null : $enveloppe->getCashier()->getGlobalEmployeeID(),
                'reference' => $enveloppe->getReference(),
                'status' => $enveloppe->getStatus(),
                'type' => $enveloppe->getType(),
                'sousType' => $enveloppe->getSousType(),
                'createdAt' => $enveloppe->getCreatedAt('Y-m-d H:i:s'),
                'updatedAt' => $enveloppe->getUpdatedAt('Y-m-d H:i:s'),
            );

            $data['data'][] = json_encode($oData);
        }

        return $data;
    }

    public function uploadEnveloppes($idCmd = null, $rawResponse = false)
    {
        parent::preUpload();
        //Get inventories not uploaded
        $enveloppes = $this->em->getRepository("Financial:Envelope")->createQueryBuilder('enveloppe')
            ->where("enveloppe.synchronized = false")
            ->orWhere("enveloppe.synchronized is NULL")
            ->getQuery()
            ->getResult();
        $success = null;
        $response = null;
        if (count($enveloppes)) {
            $data = $this->serialize($enveloppes);
            $response = parent::startUpload($this->params[$this->remoteHistoricType], $data, $idCmd);

            if (!is_null($response) && count($response['error']) === 0) {
                $events = Utilities::removeEvents(Envelope::class, $this->em);
                foreach ($enveloppes as $enveloppe) {
                    /**
                     * @var Envelope $enveloppe
                     */
                    $enveloppe->setSynchronized(true);
                }
                $this->em->flush();
                Utilities::returnEvents(Envelope::class, $this->em, $events);
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
        return $this->uploadEnveloppes($idCmd);
    }
}
