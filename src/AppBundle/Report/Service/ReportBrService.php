<?php
/**
 * Created by PhpStorm.
 * User: schabchoub
 * Date: 14/10/2016
 * Time: 10:15
 */

namespace AppBundle\Report\Service;


use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Merchandise\Service\RestaurantService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Translator;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Liuggio\ExcelBundle\Factory;
use AppBundle\ToolBox\Utils\ExcelUtilities;


class ReportBrService
{
    const BENEFICIARY = 1;
    const RESPONSIBLE = 2;

    private $em;
    private $translator;
    private $paramService;
    private $restaurantService;
    private $phpExcel;

    /**
     * ReportBrService constructor.
     * @param EntityManager $em
     * @param Translator $translator
     * @param ParameterService $paramService
     * @param RestaurantService $restaurantService
     * @param Factory $factory
     */
    public function __construct(
        EntityManager $em,
        Translator $translator,
        ParameterService $paramService,
        RestaurantService $restaurantService,
        Factory $factory
    )
    {
        $this->em = $em;
        $this->translator = $translator;
        $this->paramService = $paramService;
        $this->restaurantService = $restaurantService;
        $this->phpExcel = $factory;
    }

    public function getHoursList()
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $openingHour = ($this->paramService->getRestaurantOpeningHour(
                $currentRestaurant
            ) == null)
            ? 0
            : $this->paramService->getRestaurantOpeningHour(
                $currentRestaurant
            );
        $closingHour = ($this->paramService->getRestaurantClosingHour(
                $currentRestaurant
            ) == null)
            ? 23
            : $this->paramService->getRestaurantClosingHour(
                $currentRestaurant
            );
        $hoursArray = array();
        if ($closingHour <= $openingHour) {
            ;
        }
        $closingHour += 24;
        for ($i = intval($openingHour); $i <= intval($closingHour); $i++) {
            $hoursArray[$i] = (($i >= 24) ? ($i - 24) : $i) . ":00";
        }

        return $hoursArray;
    }

    public function getBeneficiaryNamesList()
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $sql
            = "SELECT first_name FROM ticket_payment
                  JOIN  ticket ON ticket_payment.ticket_id = ticket.id
                  WHERE
                  ticket.origin_restaurant_id = :restaurant_id AND
                  ticket.status <> :canceled                  AND
                  ticket.status <> :abondon                   AND
                  ticket_payment.id_payment = :meal_ticket
              ";

        $mealTicket = TicketPayment::MEAL_TICKET;
        $stm = $this->em->getConnection()->prepare($sql);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abondon', $abondon);
        $stm->bindParam('meal_ticket', $mealTicket);
        $restaurantId = $currentRestaurant->getId();
        $stm->bindParam('restaurant_id', $restaurantId);
        $stm->execute();
        $result = $stm->fetchAll();
        if (count($result)) {
            return $result["first_name"];
        } else {
            return [];
        }
    }

    public function getBrBeneficiaryList($filter)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $dateStart = $filter['startDate'];
        $dateEnd = $filter['endDate'];
        $startHour = (is_null($filter['startHour'])) ? 0 : $filter['startHour'];
        $endHour = (is_null($filter['endHour'])) ? 23 : $filter['endHour'];
        $cashier = $filter['cashier'];
        $tmpDate = $dateStart->format('Y-m-d');
        $output = array();
        //Recherche des tickets dans l'intervalle date et heure
        $allTickets = array();
        while (strtotime($tmpDate) <= strtotime($dateEnd->format("Y-m-d"))) {
            $tmpDateStart_1 = clone new \DateTime($tmpDate);
            $tmpDateStart_1->setTime($startHour, 0, 0);
            $tmpDateStart_2 = clone new \DateTime($tmpDate);
            $tmpDateStart_2->setTime($endHour, 0, 0);
            $sql
                = "SELECT ticket_payment.first_name,ticket.operator,ticket_payment.amount,ticket.enddate FROM ticket_payment
                  JOIN  ticket ON ticket_payment.ticket_id = ticket.id
                  WHERE
                  ticket.date BETWEEN :startDate AND :endDate AND
                  ticket.origin_restaurant_id = :restaurant_id AND 
                  ticket.status <> :canceled                  AND
                  ticket.status <> :abondon                   AND
                  ticket_payment.id_payment = :meal_ticket

              ";
//
            //Choix du user
            if (!is_null($cashier)) {
                //Bénéficiaire
                $sql .= " AND ticket_payment.first_name = :first_name";
            }
            //Montant BR
            if (isset($filter['amountMin']) && !is_null($filter['amountMin'])) {
                $sql .= " AND ticket_payment.amount >= :amountMin";
            }
            if (isset($filter['amountMax']) && !is_null($filter['amountMax'])) {
                $sql .= " AND ticket_payment.amount <= :amountMax";
            }

            $sql .= "  GROUP BY ticket_payment.id , ticket_payment.first_name,ticket.id,ticket.operator,ticket.enddate";
            $sql .= "  ORDER BY ticket.enddate , ticket_payment.first_name";

            $canceled = Ticket::CANCEL_STATUS_VALUE;
            $abondon = Ticket::ABONDON_STATUS_VALUE;

            /*
            $start = $filter['startDate']->format('Y-m-d');
            $end = $filter['endDate']->format('Y-m-d');
            */

            $start = $tmpDateStart_1->format('Y-m-d H:i:s');
            $end = $tmpDateStart_2->format('Y-m-d H:i:s');
            $mealTicket = TicketPayment::MEAL_TICKET;
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('startDate', $start);
            $stm->bindParam('endDate', $end);
            $stm->bindParam('canceled', $canceled);
            $stm->bindParam('abondon', $abondon);
            $stm->bindParam('meal_ticket', $mealTicket);
            $restaurantId = $currentRestaurant->getId();
            $stm->bindParam('restaurant_id', $restaurantId);

            if (!is_null($cashier)) {
                $stm->bindParam('first_name', $cashier);
            }
            //Montant BR
            if (isset($filter['amountMin']) && !is_null($filter['amountMin'])) {
                $stm->bindParam('amountMin', $filter['amountMin']);
            }
            if (isset($filter['amountMax']) && !is_null($filter['amountMax'])) {
                $stm->bindParam('amountMax', $filter['amountMax']);
            }
            $stm->execute();
            $result = $stm->fetchAll();
            $allTickets = array_merge($allTickets, $result);

            $tmpDate = date("Y-m-d", strtotime("+1 day", strtotime($tmpDate)));
        }
        //Serialisation tableau 1
        $usersOne = array();
        $reportOneTmp = array();
        foreach ($allTickets as $ticket) {
            $operator = $this->getEmployeeFullName($ticket['first_name'],$currentRestaurant);
            $reportOneTmp[$operator][] = $ticket;
            if (!isset($usersOne[$operator])) {
                $usersOne[$operator] = $operator;
            }
        }

        $reportOne = array();
        foreach ($reportOneTmp as $user => $brs) {
            foreach ($brs as $br) {
                $date = new \DateTime($br['enddate']);
                if (isset($reportOne[$user][$date->format('Y-m-d')])) {
                    $reportOne[$user][$date->format('Y-m-d')]['amount'] = $reportOne[$user][$date->format('Y-m-d')]['amount'] + $br['amount'];
                    $reportOne[$user][$date->format('Y-m-d')]['br']++;
                } else {
                    $reportOne[$user][$date->format('Y-m-d')]['amount']
                        = $br['amount'];
                    $reportOne[$user][$date->format('Y-m-d')]['br'] = 1;
                }
            }
        }
        //Serialisation tableau 2
        $usersTwo = array();
        $reportTwoTmp = array();
        foreach ($allTickets as $ticket) {
            $reportTwoTmp[$ticket['operator']][] = $ticket;
            if (!isset($usersTwo[$ticket['operator']])) {
                //$fullName = $this->em->getRepository("Staff:Employee")->findOneBy(array("wyndId"=>$ticket['operator']));
                $fullName = $this->em->getRepository("Staff:Employee")
                    ->createQueryBuilder('e')->where(
                        ':restaurant MEMBER OF e.eligibleRestaurants'
                    )->andWhere('e.wyndId= :wyndId')
                    ->setParameter('restaurant', $currentRestaurant)
                    ->setParameter('wyndId', $ticket['operator'])
                    ->getQuery()->getOneOrNullResult();
                if (isset($fullName)) {
                    $usersTwo[$ticket['operator']] = $fullName->getName();
                } else {
                    //Pour gérer exception BD
                    $usersTwo[$ticket['operator']] = "_USER_";
                }
            }
        }
        $reportTwo = array();
        foreach ($reportTwoTmp as $user => $brs) {
            foreach ($brs as $br) {
                if (isset($reportTwo[$user])) {
                    $reportTwo[$user]['amount'] = $reportTwo[$user]['amount']
                        + $br['amount'];
                    $reportTwo[$user]['br']++;
                } else {
                    $reportTwo[$user]['amount'] = $br['amount'];
                    $reportTwo[$user]['br'] = 1;
                }
            }
        }
        $output['report_one']['users'] = $usersOne;
        $output['report_one']['stats'] = $reportOne;
        $output['report_two']['users'] = $usersTwo;
        $output['report_two']['stats'] = $reportTwo;
        $output['startDate'] = $dateStart->format('d-m-Y');
        $output['endDate'] = $dateEnd->format('d-m-Y');
        if (isset($filter['startHour'])) {
            $output['startHour'] = $filter['startHour'];
        }
        if (isset($filter['endHour'])) {
            $output['endHour'] = $filter['endHour'];
        }
        if (isset($filter['cashier'])) {
            $output['cashier'] = $filter['cashier'];
        }
        if (isset($filter['amountMin'])) {
            $output['amountMin'] = $filter['amountMin'];
        }
        if (isset($filter['amountMax'])) {
            $output['amountMax'] = $filter['amountMax'];
        }
        $output['report_one']['users']=$this->arraySort($output['report_one']['users']);
        $output['report_two']['users']=$this->arraySort($output['report_two']['users']);
        return $output;
    }

    /**
     * Retourne le nom complet de l'employee a partie de leur nom qui se trouve dans le ticket_payment
     * @param $employee
     * @param $currentRestaurant
     * @return string
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
  private function getEmployeeFullName($employee,$currentRestaurant){
      $employee=trim($employee);
      if(ctype_digit($employee)){
          $wyndID=$employee;
      }else{
          $wyndID= substr(strrchr(trim($employee), " "), 1);
      }

       if(ctype_digit($wyndID)){
         try{
         $oEmployee = $this->em->getRepository("Staff:Employee")
               ->createQueryBuilder('e')->where(
                   ':restaurant MEMBER OF e.eligibleRestaurants'
               )->andWhere('e.wyndId= :wyndId')
               ->setParameter('restaurant', $currentRestaurant)
               ->setParameter('wyndId', $wyndID)
               ->getQuery()->getOneOrNullResult();
           }catch (\Exception $e){
               throw $e;
           }         
           if(is_object($oEmployee)){
          return $oEmployee->getFirstName();
         }
       }
       return $employee;
    }
    /**
     * trier une tableau suivant l'ordre alphabétique
     * @param $array
     * @return mixed
     */
    private function arraySort($array){
        asort($array,SORT_STRING);

        return $array;
    }

    public function getBrResponsibleList($filter)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $dateStart = $filter['startDate'];
        $dateEnd = $filter['endDate'];
        $startHour = (is_null($filter['startHour'])) ? 0 : $filter['startHour'];
        $endHour = (is_null($filter['endHour'])) ? 23 : $filter['endHour'];
        $cashier = $filter['cashier'];
        $tmpDate = $dateStart->format('Y-m-d');
        $output = array();
        //Recherche des tickets dans l'intervalle date et heure
        $allTickets = array();
        while (strtotime($tmpDate) <= strtotime($dateEnd->format("Y-m-d"))) {
            $tmpDateStart_1 = clone new \DateTime($tmpDate);
            $tmpDateStart_1->setTime($startHour, 0, 0);
            $tmpDateStart_2 = clone new \DateTime($tmpDate);
            $tmpDateStart_2->setTime($endHour, 0, 0);

            $sql
                = "SELECT ticket_payment.first_name,ticket.operator,ticket_payment.amount,ticket.enddate,ticket.totalttc FROM ticket_payment 
                  JOIN  ticket ON ticket_payment.ticket_id = ticket.id
                  WHERE
                  ticket.origin_restaurant_id = :restaurant_id AND 
                  ticket.date BETWEEN :startDate AND :endDate AND
                  ticket.status <> :canceled                  AND
                  ticket.status <> :abondon                   AND
                  ticket_payment.id_payment = :meal_ticket

              ";
            //Choix du user
            if (!is_null($cashier)) {
                //caissier
                $sql .= " AND ticket.operator = :operator";
            }
            //Montant ticket
            if (isset($filter['ticketMin']) && !is_null($filter['ticketMin'])) {
                $sql .= " AND ticket.totalttc >= :ticketMin";
            }
            if (isset($filter['ticketMax']) && !is_null($filter['ticketMax'])) {
                $sql .= " AND ticket.totalttc <= :ticketMax";
            }
            //Montant BR
            if (isset($filter['amountMin']) && !is_null($filter['amountMin'])) {
                $sql .= " AND ticket_payment.amount >= :amountMin";
            }
            if (isset($filter['amountMax']) && !is_null($filter['amountMax'])) {
                $sql .= " AND ticket_payment.amount <= :amountMax";
            }
            $sql .= " GROUP BY ticket_payment.id , ticket_payment.first_name,ticket.id,ticket.operator,ticket.enddate,ticket.date,ticket.totalttc";
            $sql .= " ORDER BY ticket.date";

            $canceled = Ticket::CANCEL_STATUS_VALUE;
            $abondon = Ticket::ABONDON_STATUS_VALUE;
            $start = $tmpDateStart_1->format('Y-m-d H:i:s');
            $end = $tmpDateStart_2->format('Y-m-d H:i:s');
            $mealTicket = TicketPayment::MEAL_TICKET;
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('startDate', $start);
            $stm->bindParam('endDate', $end);
            $stm->bindParam('canceled', $canceled);
            $stm->bindParam('abondon', $abondon);
            $stm->bindParam('meal_ticket', $mealTicket);
            $restaurantId = $currentRestaurant->getId();
            $stm->bindParam('restaurant_id', $restaurantId);

            if (!is_null($cashier)) {
                $operator = $cashier->getWyndId();
                $stm->bindParam('operator', $operator);
            }
            //Montant ticket
            if (isset($filter['ticketMin']) && !is_null($filter['ticketMin'])) {
                $stm->bindParam('ticketMin', $filter['ticketMin']);
            }
            if (isset($filter['ticketMax']) && !is_null($filter['ticketMax'])) {
                $stm->bindParam('ticketMax', $filter['ticketMax']);
            }
            //Montant BR
            if (isset($filter['amountMin']) && !is_null($filter['amountMin'])) {
                $stm->bindParam('amountMin', $filter['amountMin']);
            }
            if (isset($filter['amountMax']) && !is_null($filter['amountMax'])) {
                $stm->bindParam('amountMax', $filter['amountMax']);
            }
            $stm->execute();
            $result = $stm->fetchAll();
            $somme = 0;
            foreach ($result as $r) {
                $somme += floatval($r["amount"]);
            }

            $allTickets = array_merge($allTickets, $result);
            $tmpDate = date("Y-m-d", strtotime("+1 day", strtotime($tmpDate)));
        }
        //Serialisation tableau 2
        $usersTwo = array();
        $reportTwoTmp = array();
        foreach ($allTickets as $ticket) {
            $reportTwoTmp[$ticket['operator']][] = $ticket;
            if (!isset($usersTwo[$ticket['operator']])) {
                $fullName = $this->em->getRepository("Staff:Employee")
                    ->createQueryBuilder('e')->where(
                        ':restaurant MEMBER OF e.eligibleRestaurants'
                    )->andWhere('e.wyndId = :wyndId')
                    ->setParameter('restaurant', $currentRestaurant)
                    ->setParameter('wyndId', $ticket['operator'])
                    ->getQuery()->getOneOrNullResult();
                if (isset($fullName)) {
                    $usersTwo[$ticket['operator']] = $fullName->getName();
                } else {
                    //Pour gérer exception BD
                    $usersTwo[$ticket['operator']] = "_USER_";
                }
            }
        }
        $reportThree = array();
        foreach ($reportTwoTmp as $user => $brs) {
            foreach ($brs as $br) {
                $date = new \DateTime($br['enddate']);
                //montant ticket
                if (isset(
                    $reportThree[$user][$date->format(
                        'Y-m-d'
                    )][$date->format('H:s:i')]['amount']
                )
                ) {
                    $reportThree[$user][$date->format('Y-m-d')][$date->format(
                        'H:s:i'
                    )]['amount']
                        += $br['totalttc'];
                } else {
                    $reportThree[$user][$date->format('Y-m-d')][$date->format(
                        'H:s:i'
                    )]['amount']
                        = $br['totalttc'];
                }

                if (isset(
                    $reportThree[$user][$date->format(
                        'Y-m-d'
                    )][$date->format('H:s:i')]['br']
                )
                ) {
                    $reportThree[$user][$date->format('Y-m-d')][$date->format(
                        'H:s:i'
                    )]['br']
                        += $br['amount'];
                } else {
                    $reportThree[$user][$date->format('Y-m-d')][$date->format(
                        'H:s:i'
                    )]['br']
                        = $br['amount'];
                }

            }
        }

        $output['report_three']['users'] = $usersTwo; // même liste
        $output['report_three']['stats'] = $reportThree;
        $output['startDate'] = $dateStart->format('d-m-Y');
        $output['endDate'] = $dateEnd->format('d-m-Y');
        if (isset($filter['startHour'])) {
            $output['startHour'] = $filter['startHour'];
        }
        if (isset($filter['endHour'])) {
            $output['endHour'] = $filter['endHour'];
        }
        if (isset($filter['cashier'])) {
            $output['cashier'] = $filter['cashier'];
        }
        if (isset($filter['amountMin'])) {
            $output['amountMin'] = $filter['amountMin'];
        }
        if (isset($filter['amountMax'])) {
            $output['amountMax'] = $filter['amountMax'];
        }
        if (isset($filter['ticketMin'])) {
            $output['ticketMin'] = $filter['ticketMin'];
        }
        if (isset($filter['ticketMax'])) {
            $output['ticketMax'] = $filter['ticketMax'];
        }

        return $output;
    }


    /**
     *
     * @param $filter
     * @param $result
     * @param $logoPath
     * @param $type responsible OR beneficiary
     * @return mixed
     * @throws \Exception
     */
    public function getBrReportExcelFile($filter, $result, $logoPath, $type)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $colorOne = "ECECEC";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $headerData = [
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV,
            'logoPath' => $logoPath,
            'currentRestaurant' => $currentRestaurant
        ];
        if ($type == self::BENEFICIARY) {
            $headerData['title'] = $this->translator->trans('br_report.beneficiary_report.title');
            $this->setExcelFileHeader($sheet, $headerData);
            $startLine = $this->setReportExcelFileFilterArea($sheet, $filter, $colorOne);
            $startLine += 2;
            $this->setReportBody($sheet, $result, $startLine);
            $filename = "Rapport_bon_repas_beneficiaire__" . date('dmY_His') . ".xls";
        } elseif ($type == self::RESPONSIBLE) {
            $headerData['title'] = $this->translator->trans('br_report.responsible_report.title');
            $this->setExcelFileHeader($sheet, $headerData);
            $startLine = $this->setReportExcelFileFilterArea($sheet, $filter, $colorOne, false);
            $startLine += 2;
            $this->setResponsibleReportBody($sheet, $result, $startLine);
            $filename = "Rapport_bon_repas_responsables__" . date('dmY_His') . ".xls";
        }

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

    /**
     * Cette fonction permet d'insérer la partie entête(logo, titre, date de creation ) de document Excel.
     * @param $sheet
     * @param $headerData
     * @throws \PHPExcel_Exception
     */
    private function setExcelFileHeader(&$sheet, $headerData)
    {
        extract($headerData, EXTR_OVERWRITE);
        $sheet->mergeCells("B5:K8");
        $sheet->setCellValue('B5', $title);
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
        $objDrawing->setWidth(28);
        $objDrawing->setHeight(32);
        $objDrawing->setWorksheet($sheet);
        //restaurant name
        $sheet->mergeCells("B2:F2");
        $content = $currentRestaurant->getCode() . ' ' . $currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);
        //created at
        $sheet->mergeCells("R2:W2");
        $content = $this->translator->trans('generated_at') . ' ' . date('d-m-Y H:i:s');
        $sheet->setCellValue('R2', $content);

    }

    /**
     * Création de la zone du filtre
     * @param $sheet
     * @param $filter
     * @param $colorOne
     * @param bool $forBeneficiary
     * @return int
     */
    private function setReportExcelFileFilterArea(&$sheet, $filter, $colorOne, $forBeneficiary = true)
    {
        extract($filter, EXTR_OVERWRITE);
        $startLine = 10;
        $startCell = 1;
        //Date line
        $lineData = ['startCell' => $startCell, 'startLine' => $startLine,
            'colorOne' => $colorOne,
            'ct1' => $this->translator->trans('keyword.from') . ': ',
            'c1' => $startDate->format('d-m-Y'),
            'ct2' => $this->translator->trans('keyword.to') . ': ',
            'c2' => $endDate->format('d-m-Y')
        ];
        $this->setFilterAreaLine($sheet, $lineData);

        $startLine++;
        $startCell = 1;
        //hours line
        if (!empty($startHour) || !empty($endHour)) {
            $startHour = !empty($startHour) ? $startHour . ':00 H' : '--';
            $endHour = !empty($endHour) ? $endHour . ':00 H' : '--';
            $lineData = ['startCell' => $startCell, 'startLine' => $startLine,
                'colorOne' => $colorOne,
                'ct1' => $this->translator->trans('keyword.from') . ': ',
                'c1' => $startHour,
                'ct2' => $this->translator->trans('keyword.to') . ': ',
                'c2' => $endHour
            ];
            $this->setFilterAreaLine($sheet, $lineData);
            $startLine++;
        }
        $startCell = 1;

        //amount line
        if (!empty($amountMin) || !empty($amountMax)) {
            $amountMin = !empty($amountMin) ? (string)$amountMin . ' ' : '--';
            $amountMax = !empty($amountMax) ? (string)$amountMax . ' ' : '--';
            $lineData = ['startCell' => $startCell, 'startLine' => $startLine,
                'colorOne' => $colorOne,
                'ct1' => $this->translator->trans('br_report.amount_br_min') . ': ',
                'c1' => $amountMin,
                'ct2' => $this->translator->trans('br_report.amount_br_max') . ': ',
                'c2' => $amountMax
            ];
            $this->setFilterAreaLine($sheet, $lineData);
            $startLine++;
        }
        if (!$forBeneficiary) {
            //amount line
            if (!empty($ticketMin) || !empty($ticketMin)) {
                $ticketMin = !empty($ticketMin) ? (string)$ticketMin . ' ' : '--';
                $ticketMax = !empty($ticketMax) ? (string)$ticketMax . ' ' : '--';
                $lineData = ['startCell' => $startCell, 'startLine' => $startLine,
                    'colorOne' => $colorOne,
                    'ct1' => $this->translator->trans('br_report.amount_ticket_min') . ': ',
                    'c1' => $ticketMin,
                    'ct2' => $this->translator->trans('br_report.amount_ticket_max') . ': ',
                    'c2' => $ticketMax
                ];
                $this->setFilterAreaLine($sheet, $lineData);
                $startLine++;
            }
        }
        return $startLine;
    }

    /**
     * Ajouter une ligne du filtre dans le document excel
     * Comme par exemple: Du:   startdate      Au: EndDate
     * @param  $sheet
     * @param array $lineData
     */
    private function setFilterAreaLine(&$sheet, $lineData)
    {
        extract($lineData, EXTR_OVERWRITE);
        $elementData = ['colorOne' => $colorOne,
            'startCell' => $this->getNameFromNumber($startCell) . $startLine,
            'endCell' => $this->getNameFromNumber($startCell + 1) . $startLine,
            'content' => $ct1,
            'size' => 11,
            'bold' => true
        ];
        $this->setLineElement($sheet, $elementData);

        $elementData = ['colorOne' => $colorOne,
            'startCell' => $this->getNameFromNumber($startCell + 2) . $startLine,
            'endCell' => $this->getNameFromNumber($startCell + 3) . $startLine,
            'content' => $c1,
            'size' => 11,
            'bold' => true
        ];

        $this->setLineElement($sheet, $elementData);
        $startCell += 5;
        $elementData = ['colorOne' => $colorOne,
            'startCell' => $this->getNameFromNumber($startCell) . $startLine,
            'endCell' => $this->getNameFromNumber($startCell + 1) . $startLine,
            'content' => $ct2,
            'size' => 11,
            'bold' => true
        ];
        $this->setLineElement($sheet, $elementData);
        $elementData = ['colorOne' => $colorOne,
            'startCell' => $this->getNameFromNumber($startCell + 2) . $startLine,
            'endCell' => $this->getNameFromNumber($startCell + 3) . $startLine,
            'content' => $c2,
            'size' => 11,
            'bold' => true
        ];
        $this->setLineElement($sheet, $elementData);
    }

    /**
     * Ajouter un élément d'une ligne
     * Comme par exemple:   Du:
     * @param $sheet
     * @param $elementData
     */
    private function setLineElement(&$sheet, $elementData)
    {
        extract($elementData, EXTR_OVERWRITE);
        ExcelUtilities::setFont($sheet->getCell($startCell), $size, $bold);
        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell), $colorOne);
        $sheet->setCellValue($startCell, $content);
        if (!empty($alignmentV)) {
            $sheet->getStyle($startCell)->getAlignment()->setVertical($alignmentV);
        }
        if (!empty($alignmentH)) {
            $sheet->getStyle($startCell)->getAlignment()->setHorizontal($alignmentH);
        }
        if (!empty($isNum)) {
            $sheet->getStyle($startCell)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
        }
        ExcelUtilities::setBorder($sheet->getStyle(($startCell . ':' . $endCell)));

        $sheet->mergeCells($startCell . ':' . $endCell);
    }

    private function setReportBody(&$sheet, $data, &$startLine)
    {
        $startCell = 1;
        $elementData = ['colorOne' => 'FFFFFF',
            'startCell' => $this->getNameFromNumber($startCell) . $startLine,
            'endCell' => $this->getNameFromNumber($startCell + 3) . $startLine,
            'content' => $this->translator->trans('br_report.report_one_title'),
            'size' => 14,
            'bold' => true,
        ];
        $this->setLineElement($sheet, $elementData);
        $startLine = $this->incrementValue($startLine, 2);
        $this->setBeneficiaryReport($sheet, $data['report_one'], $startLine);
        $startLine = $this->incrementValue($startLine, 2);
        $this->setCashierReport($sheet, $data['report_two'], $startLine);
    }

    private function setBeneficiaryReport(&$sheet, $data, &$startLine)
    {
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        $headerTitle = [
            $this->translator->trans('br_report.beneficiary'),
            $this->translator->trans('corrections_report.date'),
            $this->translator->trans('corrections_report.amount')
        ];
        $elementSize = 6;
        $metaData = [
            'elementSize' => $elementSize,
            'startLine' => $startLine,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV,
            'bold' => true
        ];
        $this->setReportBodyTableHeader($sheet, $headerTitle, $metaData);
        $startLine = $this->incrementValue($startLine, 1);

        $metaData = ['colorFirstCol' => 'fafac2',
            'colorLine' => 'f9f9f9',
            'startLine' => $startLine,
            'elementSize' => $elementSize,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV
        ];
        $totalAmount = 0;
        $totalNbr = 0;
        foreach ($data['users'] as $username) {
            list($nbr, $tab) = $this->setReportBodyTableLine($sheet, $data['stats'][$username], $username, $metaData);
            $startLine = $this->incrementValue($startLine, count($data['stats'][$username]) + 1);
            $metaData['startLine'] = $startLine;
            $totalAmount += $tab;
            $totalNbr += $nbr;
        }
        //line total
        $metaData = [
            'elementSize' => $elementSize,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV,
            'startLine' => $startLine,
            'statCell' => 0,
            'size' => 9,
            'colorHeader' => 'dddddd'
        ];
        $liteTitles = [
            $this->translator->trans('corrections_report.total'),
            $this->translator->trans('br_report.br_nb') . ': ' . $totalNbr,
            $totalAmount
        ];
        $this->setReportBodyTableHeader($sheet, $liteTitles, $metaData);

    }

    /**
     * @param $sheet
     * @param $data
     * @param $startLine
     */
    private function setCashierReport(&$sheet, $data, $startLine)
    {
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        $headerTitle = [
            $this->translator->trans('corrections_report.cashier'),
            $this->translator->trans('label.number'),
            $this->translator->trans('br_report.br_nb'),
            $this->translator->trans('corrections_report.amount')
        ];
        $elementSize = 6;
        $metaData = [
            'elementSize' => $elementSize,
            'startLine' => $startLine,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV,
            'bold' => true
        ];
        $this->setReportBodyTableHeader($sheet, $headerTitle, $metaData);
        $startLine = $this->incrementValue($startLine, 1);

        $metaData = ['colorFirstCol' => 'fafac2',
            'colorLine' => 'f9f9f9',
            'startLine' => $startLine,
            'elementSize' => $elementSize,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV
        ];
        $totalAmount = 0;
        $totalNbr = 0;
        $i = 0;
        foreach ($data['users'] as $key => $username) {
            $br = $data['stats'][$key]['br'];
            $amount = $data['stats'][$key]['amount'];
            $num_cashier= array_keys($data['users']);
            $this->setCashierReportLine($sheet, $data['stats'][$key], $username,$num_cashier[$i], $metaData);
            $startLine = $this->incrementValue($startLine, +1);
            $metaData['startLine'] = $startLine;
            $i++;
            $totalAmount += $amount;
            $totalNbr += $br;
        }
        //line total
        $metaData = [
            'elementSize' => $elementSize,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV,
            'startLine' => $startLine,
            'statCell' => 0,
            'size' => 9,
            'colorHeader' => 'dddddd'
        ];
        $liteTitles = [
            $this->translator->trans('corrections_report.total'),
            $this->translator->trans(''),
            $this->translator->trans('br_report.br_nb') . ': ' . $totalNbr,
            $totalAmount
        ];
        $this->setReportBodyTableHeader($sheet, $liteTitles, $metaData);
    }

    /**
     * @param $sheet
     * @param $lineData
     * @param $username
     * @param $metaData
     */
    private function setCashierReportLine(&$sheet, $lineData, $username,$cashier_num, $metaData)
    {
        extract($metaData, EXTR_OVERWRITE);
        $startCell = !empty($startCell) ? $startCell : 1;
        //firstElement
        $elementData = ['colorOne' => $colorFirstCol,
            'startCell' => $this->getNameFromNumber($startCell) . $startLine,
            'endCell' => $this->getNameFromNumber($this->incrementValue($startCell, $elementSize)) . $startLine,
            'content' => $username,
            'size' => 7,
            'bold' => false,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV
        ];
        $this->setLineElement($sheet, $elementData);
        $startCell = $this->incrementValue($startCell, $elementSize + 1);

        $elementData = ['colorOne' => $colorFirstCol,
            'startCell' => $this->getNameFromNumber($startCell) . $startLine,
            'endCell' => $this->getNameFromNumber($this->incrementValue($startCell, $elementSize)) . $startLine,
            'content' => $cashier_num,
            'size' => 7,
            'bold' => false,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV
        ];
        $this->setLineElement($sheet, $elementData);
        $startCell = $this->incrementValue($startCell, $elementSize + 1);


        $elementData = ['colorOne' => $colorLine,
            'startCell' => $this->getNameFromNumber($startCell) . $startLine,
            'endCell' => $this->getNameFromNumber($this->incrementValue($startCell, $elementSize)) . $startLine,
            'content' => $lineData['br'],
            'size' => 7,
            'bold' => false,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV,
            'isNum' => true
        ];
        $this->setLineElement($sheet, $elementData);
        $startCell = $this->incrementValue($startCell, $elementSize + 1);
        $elementData = ['colorOne' => $colorLine,
            'startCell' => $this->getNameFromNumber($startCell) . $startLine,
            'endCell' => $this->getNameFromNumber($this->incrementValue($startCell, $elementSize)) . $startLine,
            'content' => $lineData['amount'],
            'size' => 7,
            'bold' => false,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV,
            'isNum' => true
        ];
        $this->setLineElement($sheet, $elementData);
    }

    /**
     *
     * @param $sheet
     * @param $headerTitle
     * @param $metaData
     */
    private function setReportBodyTableHeader(&$sheet, $headerTitle, $metaData)
    {
        extract($metaData, EXTR_OVERWRITE);
        $startCell = !empty($startCell) ? $startCell : 1;
        $colorHeader = !empty($colorHeader) ? $colorHeader : '579bde';
        foreach ($headerTitle as $title) {
            $elementData = ['colorOne' => $colorHeader,
                'startCell' => $this->getNameFromNumber($startCell) . $startLine,
                'endCell' => $this->getNameFromNumber($this->incrementValue($startCell, $elementSize)) . $startLine,
                'content' => $title,
                'size' => !empty($size) ? $size : 12,
                'bold' => !empty($bold) ? $bold : false,
                'alignmentH' => $alignmentH,
                'alignmentV' => $alignmentV
            ];
            $this->setLineElement($sheet, $elementData);
            $startCell = $this->incrementValue($startCell, $elementSize + 1);
        }
    }

    private function setReportBodyTableLine(&$sheet, $lineData, $username, $metaData)
    {
        extract($metaData, EXTR_OVERWRITE);
        $startCell = !empty($startCell) ? $startCell : 1;
        $initStarCell = $startCell;
        $totalAmount = 0;
        $nbr = 0;
        $endLine = $startLine + count($lineData) - 1;
        //firstElement
        $elementData = ['colorOne' => $colorFirstCol,
            'startCell' => $this->getNameFromNumber($startCell) . $startLine,
            'endCell' => $this->getNameFromNumber($this->incrementValue($startCell, $elementSize)) . $endLine,
            'content' => $username,
            'size' => 7,
            'bold' => false,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV
        ];
        $this->setLineElement($sheet, $elementData);
        $startCell = $this->incrementValue($startCell, $elementSize + 1);
        foreach ($lineData as $keyLi => $li) {
            $elementData = ['colorOne' => $colorLine,
                'startCell' => $this->getNameFromNumber($startCell) . $startLine,
                'endCell' => $this->getNameFromNumber($this->incrementValue($startCell, $elementSize)) . $startLine,
                'content' => $keyLi,
                'size' => 7,
                'bold' => false,
                'alignmentH' => $alignmentH,
                'alignmentV' => $alignmentV
            ];
            $this->setLineElement($sheet, $elementData);
            $startCell = $this->incrementValue($startCell, $elementSize + 1);
            $elementData = ['colorOne' => $colorLine,
                'startCell' => $this->getNameFromNumber($startCell) . $startLine,
                'endCell' => $this->getNameFromNumber($this->incrementValue($startCell, $elementSize)) . $startLine,
                'content' => $li['amount'],
                'size' => 7,
                'bold' => false,
                'alignmentH' => $alignmentH,
                'alignmentV' => $alignmentV,
                'isNum' => true
            ];
            $this->setLineElement($sheet, $elementData);
            $totalAmount += $li['amount'];
            $nbr += $li['br'];
            $startLine = $this->incrementValue($startLine, 1);
            $startCell = $initStarCell + $elementSize + 1;
        }
        $startCell = $initStarCell;
        $metaData = [
            'elementSize' => $elementSize,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV,
            'startLine' => $startLine,
            'statCell' => $startCell,
            'size' => 7,
            'bold' => false,
            'colorHeader' => 'cfcfff'
        ];
        $liteTitles = [
            $username,
            $this->translator->trans('br_report.br_nb') . ': ' . $nbr,
            $totalAmount
        ];
        $this->setReportBodyTableHeader($sheet, $liteTitles, $metaData);
        return [$nbr, $totalAmount];
    }

    private function incrementValue($value, $size)
    {
        return $value + $size;
    }

    private function getNameFromNumber($num)
    {
        $numeric = $num % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval($num / 26);
        if ($num2 > 0) {
            return $this->getNameFromNumber($num2 - 1) . $letter;
        } else {
            return $letter;
        }
    }

    private function setResponsibleReportBody(&$sheet, $data, &$startLine)
    {
        $startCell = 1;
        $elementData = ['colorOne' => 'FFFFFF',
            'startCell' => $this->getNameFromNumber($startCell) . $startLine,
            'endCell' => $this->getNameFromNumber($startCell + 3) . $startLine,
            'content' => $this->translator->trans('br_report.responsible_report.title'),
            'size' => 14,
            'bold' => true,
        ];
        $this->setLineElement($sheet, $elementData);
        $startLine = $this->incrementValue($startLine, 2);
        $this->setResponsibleReportContentBody($sheet, $data['report_three'], $startLine);
    }

    private function setResponsibleReportContentBody(&$sheet, $data, &$startLine)
    {
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;
        $headerTitle = [
            $this->translator->trans('br_report.responsible_report.responsible'),
            $this->translator->trans('corrections_report.date'),
            $this->translator->trans('corrections_report.hour'),
            $this->translator->trans('br_report.amount_br'),
            $this->translator->trans('br_report.amount_ticket')
        ];
        $elementSize = 3;
        $metaData = [
            'elementSize' => $elementSize,
            'startLine' => $startLine,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV,
            'bold' => true
        ];
        $this->setReportBodyTableHeader($sheet, $headerTitle, $metaData);
        $startLine = $this->incrementValue($startLine, 1);

        $metaData = ['colorFirstCol' => 'fafac2',
            'colorLine' => 'f9f9f9',
            'startLine' => $startLine,
            'elementSize' => $elementSize,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV
        ];
        $totalAmountTicket = 0;
        $totalAmountbr = 0;
        foreach ($data['users'] as $key => $username) {
            list($tabr, $tat, $isl) = $this->setResponsibleReportBodyTableLine($sheet, $data['stats'][$key], $username, $metaData);
            $startLine = $this->incrementValue($startLine, $isl + 1);
            $metaData['startLine'] = $startLine;
            $totalAmountTicket += $tat;
            $totalAmountbr += $tabr;
        }
        //line total
        $metaData = [
            'elementSize' => $elementSize,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV,
            'startLine' => $startLine,
            'statCell' => 0,
            'size' => 9,
            'colorHeader' => 'dddddd'
        ];
        $liteTitles = [
            $this->translator->trans('corrections_report.total'),
            '',
            '',
            $totalAmountbr,
            $totalAmountTicket
        ];
        $this->setReportBodyTableHeader($sheet, $liteTitles, $metaData);
    }

    private function setResponsibleReportBodyTableLine(&$sheet, $lineData, $username, $metaData)
    {
        extract($metaData, EXTR_OVERWRITE);
        $startCell = !empty($startCell) ? $startCell : 1;
        $initStarCell = $startCell;
        $tat = 0;
        $tabr = 0;
        $endLine = $startLine + ((count($lineData, COUNT_RECURSIVE) - count($lineData)) / 3) - 1;
        //firstElement
        $elementData = ['colorOne' => $colorFirstCol,
            'startCell' => $this->getNameFromNumber($startCell) . $startLine,
            'endCell' => $this->getNameFromNumber($this->incrementValue($startCell, $elementSize)) . $endLine,
            'content' => $username,
            'size' => 7,
            'bold' => false,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV
        ];
        $this->setLineElement($sheet, $elementData);
        $startCell = $this->incrementValue($startCell, $elementSize + 1);
        $isl = 0;
        foreach ($lineData as $datekeyLi => $dli) {

            foreach ($dli as $hourKeyLi => $hli) {
                //date
                $elementData = ['colorOne' => $colorLine,
                    'startCell' => $this->getNameFromNumber($startCell) . $startLine,
                    'endCell' => $this->getNameFromNumber($this->incrementValue($startCell, $elementSize)) . $startLine,
                    'content' => $datekeyLi,
                    'size' => 7,
                    'bold' => false,
                    'alignmentH' => $alignmentH,
                    'alignmentV' => $alignmentV
                ];
                $this->setLineElement($sheet, $elementData);
                //hour
                $startCell = $this->incrementValue($startCell, $elementSize + 1);
                $elementData = ['colorOne' => $colorLine,
                    'startCell' => $this->getNameFromNumber($startCell) . $startLine,
                    'endCell' => $this->getNameFromNumber($this->incrementValue($startCell, $elementSize)) . $startLine,
                    'content' => $hourKeyLi,
                    'size' => 7,
                    'bold' => false,
                    'alignmentH' => $alignmentH,
                    'alignmentV' => $alignmentV
                ];
                $this->setLineElement($sheet, $elementData);
                //amount br
                $startCell = $this->incrementValue($startCell, $elementSize + 1);
                $elementData = ['colorOne' => $colorLine,
                    'startCell' => $this->getNameFromNumber($startCell) . $startLine,
                    'endCell' => $this->getNameFromNumber($this->incrementValue($startCell, $elementSize)) . $startLine,
                    'content' => $hli['br'],
                    'size' => 7,
                    'bold' => false,
                    'alignmentH' => $alignmentH,
                    'alignmentV' => $alignmentV,
                    'isNum' => true
                ];
                $this->setLineElement($sheet, $elementData);
                //amount ticket
                $startCell = $this->incrementValue($startCell, $elementSize + 1);
                $elementData = ['colorOne' => $colorLine,
                    'startCell' => $this->getNameFromNumber($startCell) . $startLine,
                    'endCell' => $this->getNameFromNumber($this->incrementValue($startCell, $elementSize)) . $startLine,
                    'content' => $hli['amount'],
                    'size' => 7,
                    'bold' => false,
                    'alignmentH' => $alignmentH,
                    'alignmentV' => $alignmentV,
                    'isNum' => true
                ];
                $this->setLineElement($sheet, $elementData);
                $tat += $hli['amount'];
                $tabr += $hli['br'];
                $startLine = $this->incrementValue($startLine, 1);
                $startCell = $this->incrementValue($initStarCell, $elementSize + 1);
            }
            $startCell = $this->incrementValue($initStarCell, $elementSize + 1);
            $isl = $this->incrementValue($isl, count($dli));
        }

        $metaData = [
            'elementSize' => $elementSize,
            'alignmentH' => $alignmentH,
            'alignmentV' => $alignmentV,
            'startLine' => $startLine,
            'statCell' => $startCell,
            'size' => 7,
            'bold' => false,
            'colorHeader' => 'cfcfff',
            'isNum' => true
        ];
        $liteTitles = [
            $username,
            '',
            '',
            $tabr,
            $tat,
        ];
        $this->setReportBodyTableHeader($sheet, $liteTitles, $metaData);
        return [$tabr, $tat, $isl];
    }


}