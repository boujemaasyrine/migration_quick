<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 19/05/2016
 * Time: 15:23
 */

namespace AppBundle\General\Service\Remote\Staff;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Service\Remote\SynchronizerService;
use AppBundle\Staff\Entity\Employee;

class RemoteStaff extends SynchronizerService
{

    public function __construct()
    {
        $this->remoteHistoricType = RemoteHistoric::EMPLOYEE;
    }

    public function syncEmployee(Employee $employee, $idSynCmd = null)
    {

        $this->preUpload();

        $data['data'] = array(
            'email' => $employee->getEmail(),
            'socialId' => $employee->getSocialId(),
            'firstName' => $employee->getFirstName(),
            'lastName' => $employee->getLastName(),
            'wyndId' => $employee->getWyndId(),
        );

        $response = $this->startUpload($this->params['employee'], $data, $idSynCmd);

        if ($response['error'] == null) {
            $globalId = $response['globalEmployeeID'];
            $employee->setGlobalEmployeeID($globalId);
            $this->em->flush();
            $this->uploadFinishWithSuccess();

            return true;
        } else {
            $this->uploadFinishWithFail();

            return false;
        }
    }

    public function syncAllEmployees($idSynCmd = null)
    {

        $employees = $this->em->getRepository("Staff:Employee")->createQueryBuilder('e')
            ->where('e.globalEmployeeID is null ')
            ->andWhere('e.fromWynd = TRUE')
            ->getQuery()
            ->getResult();

        foreach ($employees as $e) {
            $this->syncEmployee($e, $idSynCmd);
        }
    }

    public function start($idSynCmd = null)
    {
        return $this->syncAllEmployees($idSynCmd);
    }
}
