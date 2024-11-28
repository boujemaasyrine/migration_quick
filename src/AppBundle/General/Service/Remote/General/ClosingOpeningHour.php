<?php

namespace AppBundle\General\Service\Remote\General;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Httpful\Httpful;
use Httpful\Request;
use Monolog\Logger;
use RestClient\CurlRestClient;

class ClosingOpeningHour extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::CLOSING_OPENING_HOUR;
    }

    public function uploadClosingOpeningHour($idCmd = null, $rawResponse = false)
    {
        parent::preUpload();
        //Get inventories not uploaded
        $opening = $this->em->getRepository("Administration:Parameter")->findOneBy(
            ['type' => Parameter::RESTAURANT_OPENING_HOUR]
        );
        $opening = is_null($opening) ? Parameter::RESTAURANT_OPENING_HOUR_DEFAULT : $opening->getValue();

        $closing = $this->em->getRepository("Administration:Parameter")->findOneBy(
            ['type' => Parameter::RESTAURANT_CLOSING_HOUR]
        );
        $closing = is_null($closing) ? Parameter::RESTAURANT_CLOSING_HOUR_DEFAULT : $closing->getValue();

        $success = null;
        $response = null;
        $data = [
            'opening' => $opening,
            'closing' => $closing,
        ];
        $data['data'] = json_encode($data);
        $response = parent::startUpload($this->params[$this->remoteHistoricType], $data, $idCmd);
        if (count($response['error']) === 0) {
            $this->uploadFinishWithSuccess();
            $success = true;
        } else {
            $this->uploadFinishWithFail();
            $success = false;
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
        return $this->uploadClosingOpeningHour($idCmd);
    }
}
