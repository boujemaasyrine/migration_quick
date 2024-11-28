<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 15/07/2016
 * Time: 09:58
 */

namespace AppBundle\General\Service\Remote\Merchandise;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;

class RemoveMovement extends SynchronizerService
{

    protected $remoteHistoricType;

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::REMOVE_MOVEMENT;
    }

    public function removeMovement($movements)
    {
        parent::preUpload();
        $data['data'] = json_encode($movements);

        $success = null;
        $response = null;

        $response = parent::startUpload($this->params[$this->remoteHistoricType], $data);

        if ($response) {
            if (count($response['error']) === 0) {
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
    }
}
