<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 20/06/2016
 * Time: 11:46
 */

namespace AppBundle\General\Service\Remote\Staff;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Staff\Entity\Employee;
use AppBundle\ToolBox\Utils\CryptageHelpers;

class RemotePassword extends SynchronizerService
{

    public function sendPassword(Employee $employee)
    {
        $this->preUpload();
        $data['data'] = array(
            'username' => $employee->getUsername(),
            'password_encoded' => $this->_encodePassword($employee->getPassword()),
            'password_hash' => md5($employee->getPassword()),
        );

        $response = $this->startUpload($this->params['modify_pw'], $data);

        return $response;
    }

    private function _encodePassword($password)
    {
        return $password;
        //return CryptageHelpers::encrypt($password,'test');
    }

    public function start($idSynCmd = null)
    {
        // TODO: Implement start() method.
    }

    protected function preUpload()
    {
        $this->mySynchro = new RemoteHistoric();
    }
}
