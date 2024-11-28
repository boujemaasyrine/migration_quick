<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 18/04/2016
 * Time: 14:03
 */

namespace AppBundle\Staff\Service;

use AppBundle\Staff\Entity\Employee;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;

class EmployeeService
{

    private $em;
    private $translator;

    public function __construct(EntityManager $em, Translator $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    public function getAllCashier()
    {
        $cashiers = array();
        $employees = $this->em->getRepository('Staff:Employee')->findAll();
        foreach ($employees as $employee) {
            if (in_array(Employee::$ROLE_CASHIER, $employee->getRoles())) {
                $cashiers[$employee->getId()] = $employee;
            }
        }

        return $cashiers;
    }
}
