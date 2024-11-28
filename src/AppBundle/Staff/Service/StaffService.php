<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 19/04/2016
 * Time: 17:55
 */

namespace AppBundle\Staff\Service;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Security\Entity\Role;
use AppBundle\ToolBox\Service\CommandLauncher;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Translation\Translator;
use AppBundle\Staff\Entity\Employee;

class StaffService
{

    private $em;
    private $translator;
    private $apiUserCode;
    /**
     * @var CommandLauncher
     */
    private $commandLauncher;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var UserPasswordEncoder
     */
    private $encoder;

    private $phpExcel;

    public function __construct(
        EntityManager $em,
        Translator $translator,
        $apiUserCode,
        CommandLauncher $commandLauncher,
        Logger $logger,
        UserPasswordEncoder $encoder,
        Factory $factory
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->apiUserCode = $apiUserCode;
        $this->commandLauncher = $commandLauncher;
        $this->logger = $logger;
        $this->encoder = $encoder;
        $this->phpExcel = $factory;
    }

    public function getStaff($criteria, $order, $limit, $offset, $onlyList = false)
    {
        $staff = $this->em->getRepository("Staff:Employee")->getStaffFiltredOrdered(
            $criteria,
            $order,
            $offset,
            $limit,
            $onlyList
        );

        return $this->serializeStaff($staff);
    }

    /**
     * @param Employee[] $staff
     * @return array
     */

    public function serializeStaff($staff)
    {
        $result = [];
        foreach ($staff as $s) {
            $role = '';
            if (count($s->getEmployeeRoles()) > 0) {
                foreach ($s->getEmployeeRoles() as $r) {
                    if ($r->getLabel() != Role::ROLE_EMPLOYEE) {
                        $role = $r->getTextLabel();
                    }
                }
            }

            $result[] = array(
                'id' => $s->getId(),
                'socialSecurity' => $s->getSocialId(),
                'firstName' => $s->getFirstName()." ".$s->getLastName(),
                'username' => $s->getUsername(),
                'role' => $role,
                'email' => $s->getEmail(),
            );
        }

        return $result;
    }

    /**
     * @param Employee $staff
     * @param Role $role
     * @return void
     **/
    public function setRole($staff, $role)
    {

        foreach ($staff->getEmployeeRoles() as $roleEmployee) {
            $staff->removeEmployeeRole($roleEmployee);
            $roleEmployee->removeUser($staff);
            $this->em->persist($roleEmployee);
        }

        $employeeRole = $this->em->getRepository('Security:Role')->findOneByLabel(Role::ROLE_EMPLOYEE);
        if ($employeeRole) {
            $staff->addEmployeeRole($employeeRole);
            $employeeRole->addUser($staff);
        }
       if($role != null){
           $staff->addEmployeeRole($role);
           $role->addUser($staff);
       }



        $this->em->persist($staff);
        if($role != null) {
            $this->em->persist($role);
        }
        $this->em->flush();
    }

    /**
     * @param Employee $staff
     * @param String $password
     * @return void
     **/
    public function setDefaultPassword($staff, $password, $firstConnexion)
    {
        $newPwEncoded = $this->encoder->encodePassword($staff, $password);
        $staff->setPassword($newPwEncoded);
        $staff->setActive(true);
        if ($firstConnexion) {
            $staff->setFirstConnection(true);
        }

        $this->em->persist($staff);
        $this->em->flush();
    }

    public function getRightsForAllRoles()
    {

        $rights = array();
        $roles = $this->em->getRepository('Security:Role')->findAllButNotEmployee();
        foreach ($roles as $role) {
            $rights[$role->getId()] = array();
            $i = 0;
            foreach ($role->getActions() as $action) {
                $rights[$role->getId()][$i]['idRight'] = $action->getId();
                $rights[$role->getId()][$i]['labelRight'] = $action->getRoute();
                $i++;
            }
        }

        return $rights;
    }

    public function importUsers(Restaurant $restaurant)
    {
        try {
            $usersCount = $this->em->getRepository('Staff:Employee')->createQueryBuilder('employee')
                ->select('COUNT(employee)')
                ->where(":restaurant MEMBER OF employee.eligibleRestaurants")
                ->setParameter("restaurant", $restaurant)
                ->getQuery()
                ->getSingleScalarResult();

            $usersDeleted = $this->em->getRepository('Staff:Employee')->createQueryBuilder('employee')
                ->where('employee.deleted = :true')
                ->andWhere(":restaurant MEMBER OF employee.eligibleRestaurants")
                ->setParameter("restaurant", $restaurant)
                ->setParameter('true', true)
                ->select('COUNT(employee)')
                ->getQuery()
                ->getSingleScalarResult();

            // import the latest Users
            $command = 'quick:user:wynd:rest:import '.$restaurant->getId();
            $this->commandLauncher->execute($command, true, false, false);
            $this->logger->info('Importing Users is successfully completed.', ['StaffService:ImportUsers']);

            $newUsersCount = $this->em->getRepository('Staff:Employee')->createQueryBuilder('employee')
                ->select('COUNT(employee)')
                ->where(":restaurant MEMBER OF employee.eligibleRestaurants")
                ->setParameter("restaurant", $restaurant)
                ->getQuery()
                ->getSingleScalarResult();

            $newUsersDeleted = $this->em->getRepository('Staff:Employee')->createQueryBuilder('employee')
                ->where('employee.deleted = :true')
                ->setParameter('true', true)
                ->andWhere(":restaurant MEMBER OF employee.eligibleRestaurants")
                ->setParameter("restaurant", $restaurant)
                ->select('COUNT(employee)')
                ->getQuery()
                ->getSingleScalarResult();

            $addedUsers = $newUsersCount - $usersCount;
            $deletedUsers = $newUsersDeleted - $usersDeleted;

            return [
                'addedUsers' => $addedUsers,
                'deletedUsers' => $deletedUsers,
            ];
        } catch (\Exception $e) {
            $this->logger->addAlert($e->getMessage(), ['StaffService:ImportUsers']);
            throw new \Exception($e);
        }
    }

    /**
     * @param Employee $staff
     * @param Role $role
     * @return
     */
    public function deleteStaffRole($staff, $role)
    {
        try {
            if (count($staff->getEmployeeRoles()) == 2) {
                /**
                 * @var Role $employeeRole
                 */
                $employeeRole = $this->em->getRepository('Security:Role')->findOneByLabel(Role::ROLE_EMPLOYEE);
                $staff->removeEmployeeRole($employeeRole);
                $employeeRole->removeUser($staff);
                $this->em->persist($employeeRole);
            }
            $staff->removeEmployeeRole($role);
            $role->removeUser($staff);
            $this->em->persist($staff);
            $this->em->persist($role);
            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            $this->logger->addAlert($e->getMessage());

            return false;
        }
    }

    /**
     * @param Role $role
     * @return bool
     */
    public function deleteRole($role)
    {
        if (count($role->getUsers()) == 0 && count($role->getActions()) == 0) {
            $this->em->remove($role);
            $this->em->flush();

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param Role $role
     */
    public function saveRole($role)
    {
        $label = str_replace(' ', '_', $role->getTextLabel());
        $label = strtoupper($label);
        $role->setLabel($label);
        $this->em->persist($role);
        $this->em->flush();
    }

    public function deleteUsers($newUsers, Restaurant $restaurant)
    {
        $oldUsers = array();
        $users = $restaurant->getEligibleUsers()->filter(
            function ($employee) {
                return $employee->getFromWynd() == true;
            }
        );
        foreach ($users as $user) {
            $oldUsers[] = $user->getId();
        }
        $usersToDelete = array_diff($oldUsers, $newUsers);
        foreach ($usersToDelete as $userId) {
            $user = $this->em->getRepository('Staff:Employee')->find($userId);
            $user->setDeleted(true);
        }
        $this->em->flush();
    }

    public function generateExcelFile($criteria, $orderBy, $logoPath)
    {
        $data = $this->getStaff($criteria, $orderBy, null, null, true);
        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle($this->translator->trans('staff.list.title'));

        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('staff.list.title');
        $sheet->setCellValue('B5', $content);
        ExcelUtilities::setCellAlignment($sheet->getCell("B5"), $alignmentH);
        ExcelUtilities::setVerticalCellAlignment($sheet->getCell("B5"), $alignmentV);
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 5), 22, true);

        //logo
        $objDrawing = new \PHPExcel_Worksheet_Drawing();
        $objDrawing->setName('Logo');
        $objDrawing->setDescription('Logo');
        $objDrawing->setPath($logoPath);
        $objDrawing->setOffsetX(35);
        $objDrawing->setOffsetY(0);
        $objDrawing->setCoordinates('A2');
        ExcelUtilities::setFont($sheet->getStyleByColumnAndRow(1, 2), 12, true);
        $objDrawing->setWidth(28);                 //set width, height
        $objDrawing->setHeight(32);
        $objDrawing->setWorksheet($sheet);
        //restaurant name
        $sheet->mergeCells("B2:F2");
        $currentRestaurant = $criteria['restaurant'];
        $content = $currentRestaurant->getCode().' '.$currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);

        //First Name
        $sheet->mergeCells("A10:B10");
        ExcelUtilities::setFont($sheet->getCell('A10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A10"), "ECECEC");
        $sheet->setCellValue('A10', $this->translator->trans('user.first_name').":");

        $sheet->mergeCells("C10:D10");
        ExcelUtilities::setFont($sheet->getCell('C10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C10"), "ECECEC");
        if (!is_null($criteria['staff_search[firstName']) && $criteria['staff_search[firstName'] != "") {
            $sheet->setCellValue('C10', $criteria['staff_search[firstName']);
        } else {
            $sheet->setCellValue('C10', "---");
        }

        //role
        $sheet->mergeCells("A12:B12");
        ExcelUtilities::setFont($sheet->getCell('A12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A12"), "ECECEC");
        $sheet->setCellValue('A12', $this->translator->trans('label.role').":");

        $sheet->mergeCells("C12:D12");
        ExcelUtilities::setFont($sheet->getCell('C12'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C10"), "ECECEC");
        if (!is_null($criteria['staff_search[role']) && $criteria['staff_search[role'] != "") {
            $role = $this->em->getRepository("Security:Role")->find($criteria['staff_search[role'])->getTextLabel();
            $sheet->setCellValue('C12', $role);
        } else {
            $sheet->setCellValue('C12', $this->translator->trans('label.all'));
        }

        //Table Header
        $sheet->mergeCells("A14:B14");
        ExcelUtilities::setFont($sheet->getCell('A14'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A14"), "ECECEC");
        $sheet->setCellValue('A14', $this->translator->trans('user.social_security'));
        ExcelUtilities::setBorder($sheet->getCell('A14'));
        ExcelUtilities::setBorder($sheet->getCell('B14'));
        $sheet->getStyle('A14')->getAlignment()->setWrapText(true);


        $sheet->mergeCells("C14:D14");
        ExcelUtilities::setFont($sheet->getCell('C14'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C14"), "ECECEC");
        $sheet->setCellValue('C14', $this->translator->trans('user.first_name'));
        ExcelUtilities::setBorder($sheet->getCell('C14'));
        ExcelUtilities::setBorder($sheet->getCell('D14'));


        $sheet->mergeCells("E14:F14");
        ExcelUtilities::setFont($sheet->getCell('E14'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E14"), "ECECEC");
        $sheet->setCellValue('E14', $this->translator->trans('user.username'));
        ExcelUtilities::setBorder($sheet->getCell('E14'));
        ExcelUtilities::setBorder($sheet->getCell('F14'));


        $sheet->mergeCells("G14:H14");
        ExcelUtilities::setFont($sheet->getCell('G14'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G14"), "ECECEC");
        $sheet->setCellValue('G14', $this->translator->trans('label.mail'));
        ExcelUtilities::setBorder($sheet->getCell('G14'));
        ExcelUtilities::setBorder($sheet->getCell('H14'));

        $sheet->mergeCells("I14:J14");
        ExcelUtilities::setFont($sheet->getCell('I14'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("I14"), "ECECEC");
        $sheet->setCellValue('I14', $this->translator->trans('label.role'));
        ExcelUtilities::setBorder($sheet->getCell('I14'));
        ExcelUtilities::setBorder($sheet->getCell('J14'));


        $startLine = 15;
        foreach ($data as $key => $line) {
            $sheet->mergeCells("A".$startLine.":B".$startLine);
            $sheet->setCellValue('A'.$startLine, $line['socialSecurity']);
            ExcelUtilities::setBorder($sheet->getCell('A'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('B'.$startLine));

            $sheet->mergeCells("C".$startLine.":D".$startLine);
            $sheet->setCellValue('C'.$startLine, $line['firstName']);
            ExcelUtilities::setBorder($sheet->getCell('C'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('D'.$startLine));

            $sheet->mergeCells("E".$startLine.":F".$startLine);
            $sheet->setCellValue('E'.$startLine, $line['username']);
            ExcelUtilities::setBorder($sheet->getCell('E'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('F'.$startLine));

            $sheet->mergeCells("G".$startLine.":H".$startLine);
            $sheet->setCellValue('G'.$startLine, $line['email']);
            ExcelUtilities::setBorder($sheet->getCell('G'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('H'.$startLine));
            $sheet->getStyle('G'.$startLine)->getAlignment()->setWrapText(true);

            $sheet->mergeCells("I".$startLine.":J".$startLine);
            $sheet->setCellValue('I'.$startLine, $line['role']);
            ExcelUtilities::setBorder($sheet->getCell('I'.$startLine));
            ExcelUtilities::setBorder($sheet->getCell('J'.$startLine));
            $startLine++;
        }
        $filename = "liste_du_personnel".date('dmY_His').".xls";
        // create the writer
        $writer = $this->phpExcel->createWriter($phpExcelObject, 'Excel5');
        // create the response
        $response = $this->phpExcel->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filename)
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }
}
