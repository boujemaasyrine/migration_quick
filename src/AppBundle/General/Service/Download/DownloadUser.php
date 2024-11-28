<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/06/2016
 * Time: 14:06
 */

namespace AppBundle\General\Service\Download;

use AppBundle\Security\Entity\Role;
use AppBundle\Staff\Entity\Employee;

class DownloadUser extends AbstractDownloaderService
{

    public function download($idSynCmd = null)
    {
        echo "Start Download Users \n";
        $data = $this->startDownload($this->supervisionParams['users'], $idSynCmd);
        echo count($data['data'])."\n";
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $item) {
                echo "Downloading user ".$item['firstName']." \n";
                /**
                 * @var Employee $employee
                 */
                $employee = $this->em->getRepository("Staff:Employee")->findOneBy(
                    array(
                        'username' => $item['username'],
                    )
                );

                if (!$employee) {
                    $employee = $this->em->getRepository("Staff:Employee")->findOneBy(
                        array(
                            'email' => $item['email'],
                        )
                    );
                    if (!$employee) {
                        //echo "New User ".$item['firstName']." \n";
                        $employee = new Employee();
                        $employee->setFromWynd(false)
                            ->setFromCentral(true);
                        $this->em->persist($employee);
                    }
                }
                if ($employee->getFromCentral() == true) {
                    foreach ($employee->getEmployeeRoles() as $role) {
                        /**
                         * @var Role $role
                         */
                        $employee->removeEmployeeRole($role);
                        $role->removeUser($employee);
                    }

                    $employee
                        ->setUsername($item['username'])
                        ->setPassword($item['password'])
                        ->setFirstName($item['firstName'])
                        ->setLastName($item['lastName'])
                        ->setEmail($item['email'])
                        ->setGlobalEmployeeID($item['globalId'])
                        ->setFirstConnection($item['firstConnection'])
                        ->setDefaultLocale($item['defaultLocal'] ? $item['defaultLocal'] : 'fr')
                        ->setActive(true)
                        ->setDeleted($item['deleted']);
                    if ($item['role'] != null) {
                        $roleEmployee = $this->em->getRepository('Security:Role')->findOneBy(
                            ['label' => Role::ROLE_EMPLOYEE]
                        );
                        if ($roleEmployee) {
                            $employee->addEmployeeRole($roleEmployee);
                            $roleEmployee->addUser($employee);
                        }

                        /**
                         * @var Role $newRole
                         */
                        $newRole = $this->em->getRepository('Security:Role')->findOneByGlobalId($item['role']);

                        if ($newRole) {
                            $employee->addEmployeeRole($newRole);
                            $newRole->addUser($employee);
                        }
                    }
                    echo "user ".$employee->getUsername()." updated with success ".$employee->getId()." \n";
                } else {
                    echo "user ".$employee->getUsername(
                    )." cannot be inserted, email or login exists in restaurant employee \n";
                }

                $this->em->flush();
            }
        }
    }
}
