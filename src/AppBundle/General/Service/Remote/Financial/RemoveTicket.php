<?php

namespace AppBundle\General\Service\Remote\Financial;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketIntervention;
use AppBundle\Financial\Entity\TicketInterventionSub;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Httpful\Httpful;
use Httpful\Request;
use Monolog\Logger;
use RestClient\CurlRestClient;

class RemoveTicket extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::REMOVE_TICKETS;
    }

    public function removeTicket($idCmd = null, Ticket $ticket = null)
    {
        parent::preUpload();
        $success = null;
        $response = null;
        if ($ticket) {
            $data = ['data' => ['ticket_id' => $ticket->getId()]];
            $response = parent::startUpload($this->params[$this->remoteHistoricType], $data, null);
            if (isset($response['error']) && count($response['error']) === 0) {
                $this->uploadFinishWithSuccess();
                $success = true;
            } else {
                $this->uploadFinishWithFail();
                $success = false;
            }
        }

        return $success;
    }

    /**
     *
     * +
     *
     * @return array
     */
    public function start($idCmd = null)
    {
        return $this->removeTicket($idCmd);
    }
}
