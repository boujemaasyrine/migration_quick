<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 23/03/2016
 * Time: 16:56
 */

namespace AppBundle\Report\Service;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Administration\Service\ParameterService;
use AppBundle\Financial\Entity\FinancialRevenue;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Merchandise\Entity\CaPrev;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Service\RestaurantService;
use AppBundle\ToolBox\Utils\ExcelUtilities;
use Doctrine\ORM\EntityManager;
use Liuggio\ExcelBundle\Factory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\Translator;

class ReportSalesService
{

    private $em;
    private $translator;
    private $paramService;
    private $phpExcel;
    private $restaurantService;

    public function __construct(
        EntityManager $em,
        Translator $translator,
        ParameterService $paramService,
        Factory $factory,
        RestaurantService $restaurantService
    ) {
        $this->em = $em;
        $this->translator = $translator;
        $this->paramService = $paramService;
        $this->phpExcel = $factory;
        $this->restaurantService = $restaurantService;
    }

    /**
     * @param $criteria
     * @param null $scheduleType
     * @return array
     * @throws \Exception
     */
    public function generateHourByHourReport($criteria, $scheduleType = null)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();

        if ($scheduleType == 0) {
            if ($this->restaurantService->isHistoricDate($currentRestaurant, new \DateTime($criteria['from']))) {
                $divisionsRaw = $this->em->getRepository(Ticket::class)->getCaPerHourForHisto(
                    $criteria,
                    $currentRestaurant
                );
            }else{
                $divisionsRaw = $this->em->getRepository(Ticket::class)->getCaTicketsPerHourAndOrigin(
                    $criteria,
                    $currentRestaurant
                );
            }


            return $this->serializeHourResult($criteria, $divisionsRaw, $currentRestaurant);
        } else {
            $divisionsRaw = $this->em->getRepository(Ticket::class)->getCaTicketsPerHalfOrQuarterHourAndOrigin(
                $criteria,
                $currentRestaurant,
                $scheduleType
            );

            return $this->serializeHalfOrQuarterHourResult($criteria, $divisionsRaw, $currentRestaurant, $scheduleType);
        }

    }

    /**
     * @param $criteria
     * @param null $scheduleType
     * @return array
     * @throws \Exception
     */
    public function generateCaHTvaHourByHourReport($criteria, $scheduleType = null)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();

        if ($scheduleType == 0) {
            if ($this->restaurantService->isHistoricDate($currentRestaurant, new \DateTime($criteria['from']))) {
                $divisionsRaw = $this->em->getRepository(Ticket::class)->getCaHTVAPerHourForHisto(
                    $criteria,
                    $currentRestaurant
                );
            }else{
                $divisionsRaw = $this->em->getRepository(Ticket::class)->getCaHTvaPerHourAndOrigin(
                    $criteria,
                    $currentRestaurant
                );
            }

            return $this->serializeHourResult($criteria, $divisionsRaw, $currentRestaurant);
        } else {
            $divisionsRaw = $this->em->getRepository(Ticket::class)->getCaHTvaPerHalfOrQuarterHourAndOrigin(
                $criteria,
                $currentRestaurant,
                $scheduleType
            );

            return $this->serializeHalfOrQuarterHourResult($criteria, $divisionsRaw, $currentRestaurant, $scheduleType);
        }
    }

    public function cmpOriginKey($key1, $key2)
    {
        $defaultSort = [
            'pos' => 0,
            'drive' => 1,
            'borne' => 2,
            'delivery' => 3,
            'e-ordering' => 4,
        ];
        if (!isset($defaultSort[strtolower($key1)])) {
            return -1;
        } else {
            if (!isset($defaultSort[strtolower($key2)])) {
                return 1;
            } else {
                return ($defaultSort[strtolower($key1)] >= $defaultSort[strtolower($key2)]) ? 1 : -1;
            }
        }
    }

    public function serializeHourByHourReportResult($result, $openingHour, $closingHour, $caType)
    {
        $serializedResult = [];

        if ($closingHour > $openingHour) {
            $limitHour = $closingHour;
        } else {
            $limitHour = 23;
        }


        $serializedResult['0']['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.ca_prev');
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            $serializedResult['0'][$i] = number_format($result['ca_prev'][$i], 2, ',', '');
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                $serializedResult['0'][$i] = number_format($result['ca_prev'][$i], 2, ',', '');
            }
        }

        $serializedResult['0'][] = number_format($result['ca_prev']['24'], 2, ',', '');
        if ($caType == 0){
           $serializedResult['1']['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.ca_brut');
        }else{
           $serializedResult['1']['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.ca_ht');
          }

        for ($i = $openingHour; $i <= $limitHour; $i++) {
            if (is_numeric($result['ca'][$i])) {
                $serializedResult['1'][$i] = number_format($result['ca'][$i], 2, ',', '');
            } else {
                $serializedResult['1'][$i] = '*';
            }
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                if (is_numeric($result['ca'][$i])) {
                    $serializedResult['1'][$i] = number_format($result['ca'][$i], 2, ',', '');
                } else {
                    $serializedResult['1'][$i] = '*';
                }
            }
        }
        $serializedResult['1'][] = number_format($result['ca']['24'], 2, ',', '');

        $serializedResult['2']['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.tickets');
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            if (is_numeric($result['ticket'][$i]['nbrTicket'])) {
                $serializedResult['2'][$i] = $result['ticket'][$i]['nbrTicket'];
            } else {
                $serializedResult['2'][$i] = '*';
            }
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                if (is_numeric($result['ticket'][$i]['nbrTicket'])) {
                    $serializedResult['2'][$i] = $result['ticket'][$i]['nbrTicket'];
                } else {
                    $serializedResult['2'][$i] = '*';
                }
            }
        }
        $serializedResult['2'][] = $result['ticket']['24']['nbrTicket'];

        $serializedResult['3']['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.avg_ticket');
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            if (is_numeric($result['ticket'][$i]['nbrTicket'])) {
                $serializedResult['3'][$i] = ($result['ticket'][$i]['nbrTicket'] != 0) ?
                    number_format($result['ca'][$i] / $result['ticket'][$i]['nbrTicket'], 2, ',', '') :
                    0.00;
            } else {
                $serializedResult['3'][$i] = '*';
            }
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                if (is_numeric($result['ticket'][$i]['nbrTicket'])) {
                    $serializedResult['3'][$i] = ($result['ticket'][$i]['nbrTicket'] != 0) ?
                        number_format($result['ca'][$i] / $result['ticket'][$i]['nbrTicket'], 2, ',', '') :
                        0.00;
                } else {
                    $serializedResult['3'][$i] = '*';
                }
            }
        }
        $serializedResult['3'][] = ($result['ticket']['24']['nbrTicket'] != 0) ?
            number_format($result['ca']['24'] / $result['ticket']['24']['nbrTicket'], 2, ',', ' ') :
            0.00;

        if (isset($result['origin'])) {
            $j = 4;
            foreach ($result['origin'] as $key => $origin) {
                if ($caType == 0) {
                    $serializedResult[$j]['titleColumn'] = $this->translator->trans(
                            'report.sales.hour_by_hour.ca_brut'
                        ).' ('.$this->translator->trans('canal.'.$key).')';
                } else {
                    $serializedResult[$j]['titleColumn'] = $this->translator->trans(
                            'report.sales.hour_by_hour.ca_ht'
                        ).' ('.$this->translator->trans('canal.'.$key).')';
                }

                $serializedResult[$j + 1]['titleColumn'] = '% CA ('.' '.$this->translator->trans('canal.'.$key).')';
                $serializedResult[$j + 2]['titleColumn'] = $this->translator->trans(
                        'report.sales.hour_by_hour.ticket'
                    ).' '.$this->translator->trans('canal.'.$key);
                for ($i = $openingHour; $i <= $limitHour; $i++) {
                    if (is_numeric($origin[$i]['ca'])) {
                        $serializedResult[$j][$i] = number_format($origin[$i]['ca'], 2, ',', ' ');
                        $serializedResult[$j + 1][$i] = ($result['ca'][$i] != 0) ?
                            number_format($origin[$i]['ca'] / $result['ca'][$i] * 100, 2, ',', '') :
                            0.00;
                        $serializedResult[$j + 2][$i] = number_format($origin[$i]['tickets'], 2, ',', ' ');
                    } else {
                        $serializedResult[$j][$i] = '*';
                        $serializedResult[$j + 1][$i] = '*';
                        $serializedResult[$j + 2][$i] = '*';
                    }
                }
                if ($closingHour < $openingHour) {
                    for ($i = 0; $i <= $closingHour; $i++) {
                        if (is_numeric($origin[$i]['ca'])) {
                            $serializedResult[$j][$i] = number_format($origin[$i]['ca'], 2, ',', ' ');
                            $serializedResult[$j + 1][$i] = ($result['ca'][$i] != 0) ?
                                number_format($origin[$i]['ca'] / $result['ca'][$i] * 100, 2, ',', '') :
                                0.00;
                            $serializedResult[$j + 2][$i] = number_format($origin[$i]['tickets'], 2, ',', ' ');
                        } else {
                            $serializedResult[$j][$i] = '*';
                            $serializedResult[$j + 1][$i] = '*';
                            $serializedResult[$j + 2][$i] = '*';
                        }
                    }
                }
                $serializedResult[$j][] = number_format($origin['24']['ca'], 2, ',', ' ');
                $serializedResult[$j + 1][] = ($result['ca']['24'] != 0) ?
                    number_format($origin['24']['ca'] / $result['ca']['24'] * 100, 2, ',', '') :
                    0.00;
                $serializedResult[$j + 2][] = number_format($origin['24']['tickets'], 2, ',', ' ');

                $j = $j + 3;
            }
        }

        return $serializedResult;
    }

    public function serializeHalfOrQuarterHourCSVResult($result, $openingHour, $closingHour, $schedule, $caType)
    {
        $length = 0;
        if ($schedule == 1) {
            $length = 2;
        } else {
            if ($schedule == 2) {
                $length = 4;
            }
        }
        $serializedResult = [];

        if ($closingHour > $openingHour) {
            $limitHour = $closingHour;
        } else {
            $limitHour = 23;
        }

        $serializedResult['0']['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.ca_prev');
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            for ($j = 0; $j < $length; $j++) {
                $serializedResult['0'][$i][$j] = number_format($result['ca_prev'][$i][$j], 2, ',', '');
            }
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                for ($j = 0; $j < $length; $j++) {
                    $serializedResult['0'][$i][$j] = number_format($result['ca_prev'][$i][$j], 2, ',', '');
                }
            }
        }


        $serializedResult['0'][] = number_format($result['ca_prev']['24'], 2, ',', '');
        if ($caType == 0){
            $serializedResult['1']['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.ca_brut');
        }else{
            $serializedResult['1']['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.ca_ht');
        }

        for ($i = $openingHour; $i <= $limitHour; $i++) {
            for ($j = 0; $j < $length; $j++) {
                if (is_numeric($result['ca'][$i][$j])) {
                    $serializedResult['1'][$i][$j] = number_format($result['ca'][$i][$j], 2, ',', '');
                } else {
                    $serializedResult['1'][$i][$j] = '*';
                }
            }
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                for ($j = 0; $j < $length; $j++) {
                    if (is_numeric($result['ca'][$i][$j])) {
                        $serializedResult['1'][$i][$j] = number_format($result['ca'][$i][$j], 2, ',', '');
                    } else {
                        $serializedResult['1'][$i][$j] = '*';
                    }
                }

            }
        }

        $serializedResult['1'][] = number_format($result['ca']['24'], 2, ',', '');

        $serializedResult['2']['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.tickets');
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            for ($j = 0; $j < $length; $j++) {
                if (is_numeric($result['ticket'][$i]['nbrTicket'][$j])) {
                    $serializedResult['2'][$i][$j] = $result['ticket'][$i]['nbrTicket'][$j];
                } else {
                    $serializedResult['2'][$i][$j] = '*';
                }
            }

        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                for ($j = 0; $j < $length; $j++) {
                    if (is_numeric($result['ticket'][$i]['nbrTicket'][$j])) {
                        $serializedResult['2'][$i][$j] = $result['ticket'][$i]['nbrTicket'][$j];
                    } else {
                        $serializedResult['2'][$i][$j] = '*';
                    }
                }
            }
        }
        $serializedResult['2'][] = $result['ticket']['24']['nbrTicket'];

        $serializedResult['3']['titleColumn'] = $this->translator->trans('report.sales.hour_by_hour.avg_ticket');
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            for ($j = 0; $j < $length; $j++) {
                if (is_numeric($result['ticket'][$i]['nbrTicket'][$j])) {
                    $serializedResult['3'][$i][$j] = ($result['ticket'][$i]['nbrTicket'][$j] != 0) ?
                        number_format($result['ca'][$i][$j] / $result['ticket'][$i]['nbrTicket'][$j], 2, ',', '') :
                        0.00;
                } else {
                    $serializedResult['3'][$i][$j] = '*';
                }
            }

        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                for ($j = 0; $j < $length; $j++) {
                    if (is_numeric($result['ticket'][$i]['nbrTicket'][$j])) {
                        $serializedResult['3'][$i][$j] = ($result['ticket'][$i]['nbrTicket'][$j] != 0) ?
                            number_format($result['ca'][$i][$j] / $result['ticket'][$i]['nbrTicket'][$j], 2, ',', '') :
                            0.00;
                    } else {
                        $serializedResult['3'][$i][$j] = '*';
                    }
                }

            }
        }
        $serializedResult['3'][] = ($result['ticket']['24']['nbrTicket'] != 0) ?
            number_format($result['ca']['24'] / $result['ticket']['24']['nbrTicket'], 2, ',', ' ') :
            0.00;

        if (isset($result['origin'])) {
            $j = 4;
            foreach ($result['origin'] as $key => $origin) {

                if ($caType == 0) {
                    $serializedResult[$j]['titleColumn'] = $this->translator->trans(
                            'report.sales.hour_by_hour.ca_brut'
                        ).' ('.$this->translator->trans('canal.'.$key).')';
                } else {
                    $serializedResult[$j]['titleColumn'] = $this->translator->trans(
                            'report.sales.hour_by_hour.ca_ht'
                        ).' ('.$this->translator->trans('canal.'.$key).')';
                }

                $serializedResult[$j + 1]['titleColumn'] = '% CA ('.' '.$this->translator->trans('canal.'.$key).')';
                $serializedResult[$j + 2]['titleColumn'] = $this->translator->trans(
                        'report.sales.hour_by_hour.ticket'
                    ).' '.$this->translator->trans('canal.'.$key);
                for ($i = $openingHour; $i <= $limitHour; $i++) {
                    for ($k = 0; $k < $length; $k++) {
                        if (is_numeric($origin[$i]['ca'][$k])) {
                            $serializedResult[$j][$i][$k] = number_format($origin[$i]['ca'][$k], 2, ',', ' ');
                            $serializedResult[$j + 1][$i][$k] = ($result['ca'][$i][$k] != 0) ?
                                number_format($origin[$i]['ca'][$k] / $result['ca'][$i][$k] * 100, 2, ',', '') :
                                0.00;
                            $serializedResult[$j + 2][$i][$k] = number_format($origin[$i]['tickets'][$k], 2, ',', ' ');
                        } else {
                            $serializedResult[$j][$i][$k] = '*';
                            $serializedResult[$j + 1][$i][$k] = '*';
                            $serializedResult[$j + 2][$i][$k] = '*';
                        }
                    }

                }
                if ($closingHour < $openingHour) {
                    for ($i = 0; $i <= $closingHour; $i++) {
                        for ($k = 0; $k < $length; $k++) {
                            if (is_numeric($origin[$i]['ca'][$k])) {
                                $serializedResult[$j][$i][$k] = number_format($origin[$i]['ca'][$k], 2, ',', ' ');
                                $serializedResult[$j + 1][$i][$k] = ($result['ca'][$i][$k] != 0) ? number_format(
                                    $origin[$i]['ca'][$k] / $result['ca'][$i][$k] * 100,
                                    2,
                                    ',',
                                    ''
                                ) : 0.00;
                                $serializedResult[$j + 2][$i][$k] = number_format(
                                    $origin[$i]['tickets'][$k],
                                    2,
                                    ',',
                                    ' '
                                );
                            } else {
                                $serializedResult[$j][$i][$k] = '*';
                                $serializedResult[$j + 1][$i][$k] = '*';
                                $serializedResult[$j + 2][$i][$k] = '*';
                            }
                        }
                    }
                }
                $serializedResult[$j][] = number_format($origin['24']['ca'], 2, ',', ' ');
                $serializedResult[$j + 1][] = ($result['ca']['24'] != 0) ?
                    number_format($origin['24']['ca'] / $result['ca']['24'] * 100, 2, ',', '') :
                    0.00;
                $serializedResult[$j + 2][] = number_format($origin['24']['tickets'], 2, ',', ' ');

                $j = $j + 3;
            }
        }

        return $serializedResult;
    }

    public function getCaPrevPerHour($criteria, Restaurant $currentRestaurant)
    {
        $caPerHour = array();
        $caFinalPerHour = array();
        $tmpDate = $criteria['from'];
        $index = 0;
        while (strtotime($tmpDate) <= strtotime($criteria['to'])) {
            $date = date_create_from_format('d-m-Y', $tmpDate);
            $previousDate = array();

             $ca_prev_date = $this->em->getRepository(CaPrev::class)->findOneBy(
                array(
                    "date" => $date,
                    "originRestaurant" => $currentRestaurant,
                )
            );
            $previousDate['0'] = new \DateTime();
            $previousDate['0']->setTimestamp($ca_prev_date->getDate1()->getTimestamp() );
            if($this->isComprableDay($previousDate['0']->format('Y-m-d')) == false){
                $previousDate['0']=null;
            }
            $previousDate['1'] = new \DateTime();
            $previousDate['1']->setTimestamp($ca_prev_date->getDate2()->getTimestamp() );
            if($this->isComprableDay($previousDate['1']->format('Y-m-d')) == false){
                $previousDate['1']=null;
            }
            $previousDate['2'] = new \DateTime();
            $previousDate['2']->setTimestamp($ca_prev_date->getDate3()->getTimestamp());
            if($this->isComprableDay($previousDate['2']->format('Y-m-d')) == false){
                $previousDate['2']=null;
            }
            $previousDate['3'] = new \DateTime();
            $previousDate['3']->setTimestamp($ca_prev_date->getDate4()->getTimestamp());
            if($this->isComprableDay($previousDate['3']->format('Y-m-d')) == false){
                $previousDate['3']=null;
            }
            $caHour = $this->em->getRepository('Financial:Ticket')->getTotalPerHour($previousDate, $currentRestaurant);

            $total = 0;
            foreach ($caHour as $ca) {
                $caPerHour[$index][$ca['entryhour']] = $ca['total'];
                $total += $ca['total'];
            }
            for ($i = 0; $i < 24; $i++) {
                $caProportionPerHour[$i] = (isset($caPerHour[$index][$i]) && $total != 0)
                    ? ($caPerHour[$index][$i] / $total)
                    : 0;
            }
            
            $ca_prev_date_ca = isset($ca_prev_date) ? $ca_prev_date->getCa() : 0;
            for ($i = 0; $i < 24; $i++) {
                $caPerHour[$index][$i] = $ca_prev_date_ca * $caProportionPerHour[$i];
            }
            if (!isset($caFinalPerHour[24])) {
                $caFinalPerHour[24] = 0;
            }
            $caFinalPerHour[24] += $ca_prev_date_ca;
            $tmpDate = date("d-m-Y", strtotime("+1 day", strtotime($tmpDate)));
            $index++;
        }

        for ($j = 0; $j < $index; $j++) {
            for ($i = 0; $i < 24; $i++) {
                if (!isset($caFinalPerHour[$i])) {
                    $caFinalPerHour[$i] = 0;
                }
                $caFinalPerHour[$i] += $caPerHour[$j][$i];
            }
        }

        return $caFinalPerHour;
    }

    public function getCaPrevPerHalfOrQuarterHour($criteria, Restaurant $currentRestaurant, $schedule)
    {
        $length = 0;

        if ($schedule == 1) {
            $length = 2;
        } else {
            if ($schedule == 2) {
                $length = 4;
            }
        }

        $caPerHour = array();
        $caFinalPerHour = array();
        $tmpDate = $criteria['from'];
        $index = 0;
        while (strtotime($tmpDate) <= strtotime($criteria['to'])) {
            $date = date_create_from_format('d-m-Y', $tmpDate);

            $previousDate = array();

             $ca_prev_date = $this->em->getRepository(CaPrev::class)->findOneBy(
                array(
                    "date" => $date,
                    "originRestaurant" => $currentRestaurant,
                )
            );
            $previousDate['0'] = new \DateTime();
            $previousDate['0']->setTimestamp($ca_prev_date->getDate1()->getTimestamp() );
            if($this->isComprableDay($previousDate['0']->format('Y-m-d')) == false){
                $previousDate['0']=null;
            }
            $previousDate['1'] = new \DateTime();
            $previousDate['1']->setTimestamp($ca_prev_date->getDate2()->getTimestamp() );
            if($this->isComprableDay($previousDate['1']->format('Y-m-d')) == false){
                $previousDate['1']=null;
            }
            $previousDate['2'] = new \DateTime();
            $previousDate['2']->setTimestamp($ca_prev_date->getDate3()->getTimestamp());
            if($this->isComprableDay($previousDate['2']->format('Y-m-d')) == false){
                $previousDate['2']=null;
            }
            $previousDate['3'] = new \DateTime();
            $previousDate['3']->setTimestamp($ca_prev_date->getDate4()->getTimestamp());
            if($this->isComprableDay($previousDate['3']->format('Y-m-d')) == false){
                $previousDate['3']=null;
            }

            $total = 0;
            $caHour = $this->em->getRepository('Financial:Ticket')->getTotalPerHalfOrQuarterHour(
                $previousDate,
                $currentRestaurant,
                $schedule
            );

            foreach ($caHour as $ca) {
                $caPerHour[$index][$ca['entryhour']][$ca['schedule']] = $ca['total'];
                $total += $ca['total'];
            }

            for ($i = 0; $i < 24; $i++) {
                for ($j = 0; $j < $length; $j++) {
                    $caProportionPerHour[$i][$j] = (isset($caPerHour[$index][$i][$j]) && $total != 0)
                        ? ($caPerHour[$index][$i][$j] / $total)
                        : 0;
                }
            }


             

            $ca_prev_date_ca = isset($ca_prev_date) ? $ca_prev_date->getCa() : 0;

            for ($i = 0; $i < 24; $i++) {
                for ($j = 0; $j < $length; $j++) {
                    $caPerHour[$index][$i][$j] = $ca_prev_date_ca * $caProportionPerHour[$i][$j];
                }
            }
            if (!isset($caFinalPerHour[24])) {
                $caFinalPerHour[24] = 0;
            }
            $caFinalPerHour[24] += $ca_prev_date_ca;
            $tmpDate = date("d-m-Y", strtotime("+1 day", strtotime($tmpDate)));
            $index++;
        }

        for ($k = 0; $k < $index; $k++) {
            for ($i = 0; $i < 24; $i++) {
                for ($j = 0; $j < $length; $j++) {
                    if (!isset($caFinalPerHour[$i][$j])) {
                        $caFinalPerHour[$i][$j] = 0;
                    }
                    $caFinalPerHour[$i][$j] += $caPerHour[$k][$i][$j];
                }
            }
        }

        return $caFinalPerHour;
    }

    public function getCsvHeader($openingHour, $closingHour)
    {
        $header = [''];
        $limitHour = ($closingHour < $openingHour) ? 23 : $closingHour;
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            $header[] = $i.":00";
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                $header[] = $i.":00";
            }
        }
        $header[] = $this->translator->trans('keyword.total');

        return $header;
    }

    public function getHalfORQuarterHourCsvHeader($openingHour, $closingHour, $schedule)
    {
        $header = [''];
        $limitHour = ($closingHour < $openingHour) ? 23 : $closingHour;
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            $header[] = $i.":00";
            if ($schedule == 1) {
                $header[] = $i.":30";
            } elseif ($schedule == 2) {
                $header[] = $i.":15";
                $header[] = $i.":30";
                $header[] = $i.":45";
            }
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                $header[] = $i.":00";
                if ($schedule == 1) {
                    $header[] = $i.":30";
                } elseif ($schedule == 2) {
                    $header[] = $i.":15";
                    $header[] = $i.":30";
                    $header[] = $i.":45";
                }
            }
        }
        $header[] = $this->translator->trans('keyword.total');

        return $header;
    }

    public function getOpeningAndClosingHour($divisionsRaw, Restaurant $currentRestaurant)
    {
        $openingHourRestaurant = $this->paramService->getRestaurantOpeningHour($currentRestaurant);
        $closingHourRestaurant = $this->paramService->getRestaurantClosingHour($currentRestaurant);
        $minTicketHour = $openingHourRestaurant;
        $maxTicketHour = $closingHourRestaurant;

        foreach ($divisionsRaw as $raw) {
            if ($raw['entryhour'] >= Parameter::RESTAURANT_OPENING_HOUR_DEFAULT) {
                $minTicketHour = min([$minTicketHour, $raw['entryhour']]);
                if ($maxTicketHour >= Parameter::RESTAURANT_OPENING_HOUR_DEFAULT) {
                    $maxTicketHour = max([$maxTicketHour, $raw['entryhour']]);
                }
            } else {
                if ($maxTicketHour >= Parameter::RESTAURANT_OPENING_HOUR_DEFAULT) {
                    $maxTicketHour = $raw['entryhour'];
                } else {
                    $maxTicketHour = max([$raw['entryhour'], $maxTicketHour]);
                }
            }
        }
        $openingHour = min([$openingHourRestaurant, $minTicketHour]);
        if (($closingHourRestaurant < Parameter::RESTAURANT_OPENING_HOUR_DEFAULT && $maxTicketHour < Parameter::RESTAURANT_OPENING_HOUR_DEFAULT)
            or ($closingHourRestaurant >= Parameter::RESTAURANT_OPENING_HOUR_DEFAULT && $maxTicketHour >= Parameter::RESTAURANT_OPENING_HOUR_DEFAULT)
        ) {
            $closingHour = max([$closingHourRestaurant, $maxTicketHour]);
        } else {
            $closingHour = min([$closingHourRestaurant, $maxTicketHour]);
        }
        $result = [
            'openingHour' => $openingHour,
            'closingHour' => $closingHour,
        ];

        return $result;
    }

    public function getCaBrutInDates(\DateTime $from,\DateTime $to)
    {
        $conn = $this->em->getConnection();
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $abandonment = Ticket::ABONDON_STATUS_VALUE;
        $from=$from->format('Y-m-d');
        $to=$to->format('Y-m-d');
        $restaurant_id=$this->restaurantService->getCurrentRestaurant()->getId();

        $sql = "SELECT COALESCE(SUM( LEFT_RESULT.totalttc ),0) AS ca_brut_ttc, COALESCE(SUM(LAST_RESULT.discount_amount),0) AS discount_ttc
                FROM (
                SELECT
                t.id AS ticket_id,
                t.totalttc AS totalttc
                FROM public.ticket t
                WHERE ( T.status <> :canceled AND T.status <> :abandonment AND T.counted_canceled <> TRUE AND T.origin_restaurant_id = :restaurant) AND t.date BETWEEN :from AND :to ) AS LEFT_RESULT
                LEFT JOIN
                (
                    SELECT
                    TL.ticket_id AS id_ticket,
                    SUM(TL.discount_ttc::NUMERIC) AS discount_amount
                    FROM public.ticket_line TL
                    WHERE (TL.origin_restaurant_id = :restaurant AND TL.is_discount = TRUE AND TL.date BETWEEN :from AND :to AND TL.combo = FALSE ) GROUP BY TL.ticket_id
                ) AS LAST_RESULT
                ON LEFT_RESULT.ticket_id = LAST_RESULT.id_ticket ";

        $stm = $conn->prepare($sql);
        $stm->bindParam('from', $from);
        $stm->bindParam('to', $to);
        $stm->bindParam('canceled', $canceled);
        $stm->bindParam('abandonment', $abandonment);
        $stm->bindParam('restaurant', $restaurant_id);

        $stm->execute();
        $result = $stm->fetchAll();
        $caBrutTTC = $result[0]['ca_brut_ttc']+abs($result[0]['discount_ttc']);
        return $caBrutTTC;
    }

    public function getCaHTva(\DateTime $from,\DateTime $to)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $qb = $this->em->getRepository(FinancialRevenue::class)->createQueryBuilder('fr')
            ->select('COALESCE(SUM(fr.netHT),0)' )
            ->where('fr.date BETWEEN :from and :to')
            ->andWhere('fr.originRestaurant = :restaurant')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('restaurant', $currentRestaurant);

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result;
    }


    public function isComprableDay($date){

        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $comparableDay = $this->em->getRepository("Financial:AdministrativeClosing")->findBy(
            array(
                'comparable' => true,
                'originRestaurant'=> $currentRestaurant,
                'date' => new \DateTime($date)
            )
        );
        if($comparableDay){
            return true;
        }
        return false;
    }

    public function generateExcelFile($result, $startDate, $endDate, $openingHour, $closingHour, $logoPath, $schedule, $caType)
    {
        $openingHour=intval($openingHour);
        $closingHour=intval($closingHour);
        $length = 0;
        if ($schedule == 1) {
            $length = 2;
        } else {
            if ($schedule == 2) {
                $length = 4;
            }
        }
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $colorOne = "ECECEC";
        $colorTwo = "E5CFAB";
        $colorThree = "F4BF7C";
        $colorFour = "449EFD";
        $colorFive = "FFFCC0";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('report.sales.hour_by_hour.title'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('report.sales.hour_by_hour.title');
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
        $content = $currentRestaurant->getCode().' '.$currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);

        //FILTER ZONE
        // START DATE
        ExcelUtilities::setFont($sheet->getCell('A10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A10"), $colorOne);
        $sheet->setCellValue('A10', $this->translator->trans('keyword.from').":");
        $sheet->mergeCells("B10:C10");
        ExcelUtilities::setFont($sheet->getCell('B10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B10"), $colorOne);
        $sheet->setCellValue('B10', $startDate->format('d-m-Y'));
        // END DATE
        ExcelUtilities::setFont($sheet->getCell('D10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D10"), $colorOne);
        $sheet->setCellValue('D10', $this->translator->trans('keyword.to').":");
        $sheet->mergeCells("E10:F10");
        ExcelUtilities::setFont($sheet->getCell('E10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E10"), $colorOne);
        $sheet->setCellValue('E10', $endDate->format('d-m-Y'));


        //CONTENT

        //Hours
        $header = [''];
        $limitHour = ($closingHour < $openingHour) ? 23 : $closingHour;
        $startCell = 'C';
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            $sheet->setCellValue($startCell.'15', $i.':00');
            ExcelUtilities::setBorder($sheet->getCell($startCell.'15'));
            ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.'15'), $colorFive);
            $startCell++;

            if ($schedule == 1) {
                $sheet->setCellValue($startCell.'15', $i.':30');
                ExcelUtilities::setBorder($sheet->getCell($startCell.'15'));
                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.'15'), $colorFive);
                $startCell++;
            } else {
                if ($schedule == 2) {
                    $sheet->setCellValue($startCell.'15', $i.':15');
                    ExcelUtilities::setBorder($sheet->getCell($startCell.'15'));
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.'15'), $colorFive);
                    $startCell++;
                    $sheet->setCellValue($startCell.'15', $i.':30');
                    ExcelUtilities::setBorder($sheet->getCell($startCell.'15'));
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.'15'), $colorFive);
                    $startCell++;
                    $sheet->setCellValue($startCell.'15', $i.':45');
                    ExcelUtilities::setBorder($sheet->getCell($startCell.'15'));
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.'15'), $colorFive);
                    $startCell++;
                }
            }
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                $sheet->setCellValue($startCell.'15', $i.':00');
                ExcelUtilities::setBorder($sheet->getCell($startCell.'15'));
                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.'15'), $colorFive);
                $startCell++;

                if ($schedule == 1) {
                    $sheet->setCellValue($startCell.'15', $i.':30');
                    ExcelUtilities::setBorder($sheet->getCell($startCell.'15'));
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.'15'), $colorFive);
                    $startCell++;
                } else {
                    if ($schedule == 2) {
                        $sheet->setCellValue($startCell.'15', $i.':15');
                        ExcelUtilities::setBorder($sheet->getCell($startCell.'15'));
                        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.'15'), $colorFive);
                        $startCell++;
                        $sheet->setCellValue($startCell.'15', $i.':30');
                        ExcelUtilities::setBorder($sheet->getCell($startCell.'15'));
                        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.'15'), $colorFive);
                        $startCell++;
                        $sheet->setCellValue($startCell.'15', $i.':45');
                        ExcelUtilities::setBorder($sheet->getCell($startCell.'15'));
                        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.'15'), $colorFive);
                        $startCell++;
                    }
                }
            }
        }

        $sheet->setCellValue($startCell.'15', $this->translator->trans('keyword.total'));
        ExcelUtilities::setBorder($sheet->getCell($startCell.'15'));
        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.'15'), $colorFive);

        //CONTENT
        $lineIndex = 16;

        $lineIndex++;

        //CA PREV
        $sheet->mergeCells('A'.$lineIndex.':B'.$lineIndex);
        $sheet->setCellValue('A'.$lineIndex, $this->translator->trans(('report.sales.hour_by_hour.ca_prev')));
        ExcelUtilities::setBorder($sheet->getCell('A'.$lineIndex));
        ExcelUtilities::setBorder($sheet->getCell('B'.$lineIndex));
        ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineIndex), $colorThree);
        $startCell = 'C';
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            if ($length <= 0) {
                $sheet->setCellValue($startCell.$lineIndex, round($result['ca_prev'][$i], 2));
                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorThree);
                $startCell++;
            } else {
                for ($j = 0; $j < $length; $j++) {
                    $sheet->setCellValue($startCell.$lineIndex, round($result['ca_prev'][$i][$j], 2));
                    ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorThree);
                    $startCell++;
                }
            }
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                if ($length <= 0) {
                    $sheet->setCellValue($startCell.$lineIndex, round($result['ca_prev'][$i], 2));
                    ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorThree);
                    $startCell++;
                } else {
                    for ($j = 0; $j < $length; $j++) {
                        $sheet->setCellValue($startCell.$lineIndex, round($result['ca_prev'][$i][$j], 2));
                        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorThree);
                        $startCell++;
                    }
                }
            }
        }
        $sheet->setCellValue($startCell.$lineIndex, round($result['ca_prev'][24], 2));
        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorThree);
        $lineIndex++;
        //END CA PREV


        //CA BRUT
        $sheet->mergeCells('A'.$lineIndex.':B'.$lineIndex);
        if ($caType == 0) {
            $sheet->setCellValue('A' . $lineIndex, $this->translator->trans(('report.sales.hour_by_hour.ca_brut')));
        } else {
            $sheet->setCellValue('A' . $lineIndex, $this->translator->trans(('report.sales.hour_by_hour.ca_ht')));
        }
        ExcelUtilities::setBorder($sheet->getCell('A'.$lineIndex));
        ExcelUtilities::setBorder($sheet->getCell('B'.$lineIndex));
        ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineIndex), $colorTwo);

        $startCell = 'C';
        $colorSwitcher = 0;
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            if ($length <= 0) {
                $sheet->setCellValue($startCell.$lineIndex, round($result['ca'][$i], 2));
                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                if (is_int($colorSwitcher / 2)) {
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                }
                $startCell++;
            } else {
                for ($j = 0; $j < $length; $j++) {
                    $sheet->setCellValue($startCell.$lineIndex, round($result['ca'][$i][$j], 2));
                    ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                    if (is_int($colorSwitcher / 2)) {
                        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                    }
                    $startCell++;
                }
            }

            $colorSwitcher++;
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                if ($length <= 0) {
                    $sheet->setCellValue($startCell.$lineIndex, round($result['ca'][$i], 2));
                    ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                    if (is_int($colorSwitcher / 2)) {
                        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                    }
                    $startCell++;
                } else {
                    for ($j = 0; $j < $length; $j++) {
                        $sheet->setCellValue($startCell.$lineIndex, round($result['ca'][$i][$j], 2));
                        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                        if (is_int($colorSwitcher / 2)) {
                            ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                        }
                        $startCell++;
                    }
                }
                $colorSwitcher++;
            }
        }
        $sheet->setCellValue($startCell.$lineIndex, round($result['ca'][24], 2));
        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
        $lineIndex += 2;
        //END CA BRUT

        //Tickets
        $sheet->mergeCells('A'.$lineIndex.':B'.$lineIndex);
        $sheet->setCellValue('A'.$lineIndex, $this->translator->trans(('report.sales.hour_by_hour.tickets')));
        ExcelUtilities::setBorder($sheet->getCell('A'.$lineIndex));
        ExcelUtilities::setBorder($sheet->getCell('B'.$lineIndex));
        ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineIndex), $colorTwo);

        $startCell = 'C';
        $colorSwitcher = 0;
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            if ($length <= 0) {
                $sheet->setCellValue($startCell.$lineIndex, round($result['ticket'][$i]['nbrTicket'], 2));
                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                if (is_int($colorSwitcher / 2)) {
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                }
                $startCell++;
            } else {
                for ($j = 0; $j < $length; $j++) {
                    $sheet->setCellValue($startCell.$lineIndex, round($result['ticket'][$i]['nbrTicket'][$j], 2));
                    ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                    if (is_int($colorSwitcher / 2)) {
                        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                    }
                    $startCell++;
                }
            }
            $colorSwitcher++;

        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                if ($length <= 0) {
                    $sheet->setCellValue($startCell.$lineIndex, round($result['ticket'][$i]['nbrTicket'], 2));
                    ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                    if (is_int($colorSwitcher / 2)) {
                        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                    }
                    $startCell++;
                } else {
                    for ($j = 0; $j < $length; $j++) {
                        $sheet->setCellValue($startCell.$lineIndex, round($result['ticket'][$i]['nbrTicket'][$j], 2));
                        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                        if (is_int($colorSwitcher / 2)) {
                            ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                        }
                        $startCell++;
                    }
                }
                $colorSwitcher++;
            }
        }
        $sheet->setCellValue($startCell.$lineIndex, round($result['ticket'][24]['nbrTicket'], 2));
        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
        $lineIndex++;
        //END Tickets

        //Tickets AVG
        $sheet->mergeCells('A'.$lineIndex.':B'.$lineIndex);
        $sheet->setCellValue('A'.$lineIndex, $this->translator->trans(('report.sales.hour_by_hour.avg_ticket')));
        ExcelUtilities::setBorder($sheet->getCell('A'.$lineIndex));
        ExcelUtilities::setBorder($sheet->getCell('B'.$lineIndex));
        ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineIndex), $colorTwo);

        $startCell = 'C';
        $colorSwitcher = 0;
        for ($i = $openingHour; $i <= $limitHour; $i++) {
            if ($length <= 0) {
                if (!is_numeric($result['ticket'][$i]['nbrTicket'])) {
                    $sheet->setCellValue($startCell.$lineIndex, '*');
                } else {
                    if ($result['ticket'][$i]['nbrTicket'] != 0) {
                        $sheet->setCellValue(
                            $startCell.$lineIndex,
                            round($result['ca'][$i] / $result['ticket'][$i]['nbrTicket'], 2)
                        );
                    } else {
                        $sheet->setCellValue($startCell.$lineIndex, '0.00');
                    }
                }
                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                if (is_int($colorSwitcher / 2)) {
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                }
                $startCell++;
            } else {
                for ($j = 0; $j < $length; $j++) {
                    if (!is_numeric($result['ticket'][$i]['nbrTicket'][$j])) {
                        $sheet->setCellValue($startCell.$lineIndex, '*');
                    } else {
                        if ($result['ticket'][$i]['nbrTicket'][$j] != 0) {
                            $sheet->setCellValue(
                                $startCell.$lineIndex,
                                round($result['ca'][$i][$j] / $result['ticket'][$i]['nbrTicket'][$j], 2)
                            );
                        } else {
                            $sheet->setCellValue($startCell.$lineIndex, '0.00');
                        }
                    }
                    ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                    if (is_int($colorSwitcher / 2)) {
                        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                    }
                    $startCell++;
                }
            }
            $colorSwitcher++;
        }
        if ($closingHour < $openingHour) {
            for ($i = 0; $i <= $closingHour; $i++) {
                if ($length <= 0) {
                    if (!is_numeric($result['ticket'][$i]['nbrTicket'])) {
                        $sheet->setCellValue($startCell.$lineIndex, '*');
                    } else {
                        if ($result['ticket'][$i]['nbrTicket'] != 0) {
                            $sheet->setCellValue(
                                $startCell.$lineIndex,
                                round($result['ca'][$i] / $result['ticket'][$i]['nbrTicket'], 2)
                            );
                        } else {
                            $sheet->setCellValue($startCell.$lineIndex, '0.00');
                        }
                    }
                    ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                    if (is_int($colorSwitcher / 2)) {
                        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                    }
                    $startCell++;
                } else {
                    for ($j = 0; $j < $length; $j++) {
                        if (!is_numeric($result['ticket'][$i]['nbrTicket'][$j])) {
                            $sheet->setCellValue($startCell.$lineIndex, '*');
                        } else {
                            if ($result['ticket'][$i]['nbrTicket'][$j] != 0) {
                                $sheet->setCellValue(
                                    $startCell.$lineIndex,
                                    round($result['ca'][$i][$j] / $result['ticket'][$i]['nbrTicket'][$j], 2)
                                );
                            } else {
                                $sheet->setCellValue($startCell.$lineIndex, '0.00');
                            }
                        }
                        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                        if (is_int($colorSwitcher / 2)) {
                            ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                        }
                        $startCell++;
                    }
                }
                $colorSwitcher++;
            }
        }
        if (!is_numeric($result['ticket'][24]['nbrTicket'])) {
            $sheet->setCellValue($startCell.$lineIndex, '*');
        } else {
            if ($result['ticket'][24]['nbrTicket'] != 0) {
                $sheet->setCellValue(
                    $startCell.$lineIndex,
                    round($result['ca'][24] / $result['ticket'][24]['nbrTicket'], 2)
                );
            } else {
                $sheet->setCellValue($startCell.$lineIndex, '0,00');
            }
        }
        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
        $lineIndex += 2;
        //END Tickets AVG

        if (isset($result['origin'])) {
            foreach ($result['origin'] as $key => $origin) {
                //CA BRUT CANAL
                $sheet->mergeCells('A'.$lineIndex.':B'.$lineIndex);
                if ($caType == 0) {
                $sheet->setCellValue(
                    'A'.$lineIndex,
                    $this->translator->trans(('report.sales.hour_by_hour.ca_brut')).' ('.$this->translator->trans(
                        'canal.'.$key
                    ).')'
                );
                }else{
                    $sheet->setCellValue(
                        'A'.$lineIndex,
                        $this->translator->trans(('report.sales.hour_by_hour.ca_ht')).' ('.$this->translator->trans(
                            'canal.'.$key
                        ).')'
                    );
                }
                ExcelUtilities::setBorder($sheet->getCell('A'.$lineIndex));
                ExcelUtilities::setBorder($sheet->getCell('B'.$lineIndex));
                ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineIndex), $colorTwo);

                $startCell = 'C';
                $colorSwitcher = 0;
                for ($i = $openingHour; $i <= $limitHour; $i++) {
                    if ($length <= 0) {
                        if (!is_numeric($origin[$i]['ca'])) {
                            $sheet->setCellValue($startCell.$lineIndex, '*');
                        } else {
                            $sheet->setCellValue($startCell.$lineIndex, round($origin[$i]['ca'], 2));
                        }
                        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                        if (is_int($colorSwitcher / 2)) {
                            ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                        }
                        $startCell++;
                    } else {
                        for ($j = 0; $j < $length; $j++) {
                            if (!is_numeric($origin[$i]['ca'][$j])) {
                                $sheet->setCellValue($startCell.$lineIndex, '*');
                            } else {
                                $sheet->setCellValue($startCell.$lineIndex, round($origin[$i]['ca'][$j], 2));
                            }
                            ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                            if (is_int($colorSwitcher / 2)) {
                                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                            }
                            $startCell++;
                        }
                    }
                    $colorSwitcher++;
                }
                if ($closingHour < $openingHour) {
                    for ($i = 0; $i <= $closingHour; $i++) {
                        if ($length <= 0) {
                            if (!is_numeric($origin[$i]['ca'])) {
                                $sheet->setCellValue($startCell.$lineIndex, '*');
                            } else {
                                $sheet->setCellValue($startCell.$lineIndex, round($origin[$i]['ca'], 2));
                            }
                            ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                            if (is_int($colorSwitcher / 2)) {
                                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                            }
                            $startCell++;
                        } else {
                            for ($j = 0; $j < $length; $j++) {
                                if (!is_numeric($origin[$i]['ca'][$j])) {
                                    $sheet->setCellValue($startCell.$lineIndex, '*');
                                } else {
                                    $sheet->setCellValue($startCell.$lineIndex, round($origin[$i]['ca'][$j], 2));
                                }
                                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                                if (is_int($colorSwitcher / 2)) {
                                    ExcelUtilities::setBackgroundColor(
                                        $sheet->getCell($startCell.$lineIndex),
                                        $colorFour
                                    );
                                }
                                $startCell++;
                            }
                        }
                        $colorSwitcher++;
                    }
                }
                if (!is_numeric($origin[24]['ca'])) {
                    $sheet->setCellValue($startCell.$lineIndex, '*');
                } else {
                    $sheet->setCellValue($startCell.$lineIndex, round($origin[24]['ca'], 2));
                }
                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                $lineIndex++;
                //END CA BRUT CANAL

                // % CA  CANAL
                $sheet->mergeCells('A'.$lineIndex.':B'.$lineIndex);
                $sheet->setCellValue('A'.$lineIndex, '%CA ('.$this->translator->trans('canal.'.$key).')');
                ExcelUtilities::setBorder($sheet->getCell('A'.$lineIndex));
                ExcelUtilities::setBorder($sheet->getCell('B'.$lineIndex));
                ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineIndex), $colorTwo);

                $startCell = 'C';
                $colorSwitcher = 0;
                for ($i = $openingHour; $i <= $limitHour; $i++) {
                    if ($length <= 0) {
                        if (!is_numeric($origin[$i]['ca'])) {
                            $sheet->setCellValue($startCell.$lineIndex, '*');
                        } else {
                            if ($result['ca'][$i] != 0) {
                                $sheet->setCellValue(
                                    $startCell.$lineIndex,
                                    round(($origin[$i]['ca'] / $result['ca'][$i] * 100), 2)
                                );
                            } else {
                                $sheet->setCellValue($startCell.$lineIndex, '0,00');
                            }
                        }
                        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                        if (is_int($colorSwitcher / 2)) {
                            ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                        }
                        $startCell++;
                    } else {
                        for ($j = 0; $j < $length; $j++) {
                            if (!is_numeric($origin[$i]['ca'][$j])) {
                                $sheet->setCellValue($startCell.$lineIndex, '*');
                            } else {
                                if ($result['ca'][$i][$j] != 0) {
                                    $sheet->setCellValue(
                                        $startCell.$lineIndex,
                                        round(($origin[$i]['ca'][$j] / $result['ca'][$i][$j] * 100), 2)
                                    );
                                } else {
                                    $sheet->setCellValue($startCell.$lineIndex, '0,00');
                                }
                            }
                            ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                            if (is_int($colorSwitcher / 2)) {
                                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                            }
                            $startCell++;
                        }
                    }
                    $colorSwitcher++;
                }
                if ($closingHour < $openingHour) {
                    for ($i = 0; $i <= $closingHour; $i++) {
                        if ($length <= 0) {
                            if (!is_numeric($origin[$i]['ca'])) {
                                $sheet->setCellValue($startCell.$lineIndex, '*');
                            } else {
                                if ($result['ca'][$i] != 0) {
                                    $sheet->setCellValue(
                                        $startCell.$lineIndex,
                                        round(($origin[$i]['ca'] / $result['ca'][$i] * 100), 2)
                                    );
                                } else {
                                    $sheet->setCellValue($startCell.$lineIndex, '0,00');
                                }
                            }
                            ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                            if (is_int($colorSwitcher / 2)) {
                                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                            }
                            $startCell++;
                        } else {
                            for ($j = 0; $j < $length; $j++) {
                                if (!is_numeric($origin[$i]['ca'][$j])) {
                                    $sheet->setCellValue($startCell.$lineIndex, '*');
                                } else {
                                    if ($result['ca'][$i][$j] != 0) {
                                        $sheet->setCellValue(
                                            $startCell.$lineIndex,
                                            round(($origin[$i]['ca'][$j] / $result['ca'][$i][$j] * 100), 2)
                                        );
                                    } else {
                                        $sheet->setCellValue($startCell.$lineIndex, '0,00');
                                    }
                                }
                                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                                if (is_int($colorSwitcher / 2)) {
                                    ExcelUtilities::setBackgroundColor(
                                        $sheet->getCell($startCell.$lineIndex),
                                        $colorFour
                                    );
                                }
                                $startCell++;
                            }
                        }
                        $colorSwitcher++;
                    }
                }
                if (!is_numeric($origin[24]['ca'])) {
                    $sheet->setCellValue($startCell.$lineIndex, '*');
                } else {
                    if ($result['ca'][24] != 0) {
                        $sheet->setCellValue(
                            $startCell.$lineIndex,
                            round(($origin[24]['ca'] / $result['ca'][24] * 100), 2)
                        );
                    } else {
                        $sheet->setCellValue($startCell.$lineIndex, '0,00');
                    }
                }
                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                $lineIndex++;
                //END % CA  CANAL

                //TICKET CANAL
                $sheet->mergeCells('A'.$lineIndex.':B'.$lineIndex);
                $sheet->setCellValue(
                    'A'.$lineIndex,
                    $this->translator->trans(('report.sales.hour_by_hour.ticket')).' ('.$this->translator->trans(
                        'canal.'.$key
                    ).')'
                );
                ExcelUtilities::setBorder($sheet->getCell('A'.$lineIndex));
                ExcelUtilities::setBorder($sheet->getCell('B'.$lineIndex));
                ExcelUtilities::setBackgroundColor($sheet->getCell('A'.$lineIndex), $colorTwo);

                $startCell = 'C';
                $colorSwitcher = 0;
                for ($i = $openingHour; $i <= $limitHour; $i++) {
                    if ($length <= 0) {
                        $sheet->setCellValue($startCell.$lineIndex, round($origin[$i]['tickets'], 2));

                        ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                        if (is_int($colorSwitcher / 2)) {
                            ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                        }
                        $startCell++;
                    } else {
                        for ($j = 0; $j < $length; $j++) {
                            $sheet->setCellValue($startCell.$lineIndex, round($origin[$i]['tickets'][$j], 2));

                            ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                            if (is_int($colorSwitcher / 2)) {
                                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                            }
                            $startCell++;
                        }
                    }
                    $colorSwitcher++;
                }
                if ($closingHour < $openingHour) {
                    for ($i = 0; $i <= $closingHour; $i++) {
                        if ($length <= 0) {
                            $sheet->setCellValue($startCell.$lineIndex, round($origin[$i]['tickets'], 2));
                            ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                            if (is_int($colorSwitcher / 2)) {
                                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$lineIndex), $colorFour);
                            }
                            $startCell++;
                        } else {
                            for ($j = 0; $j < $length; $j++) {
                                $sheet->setCellValue($startCell.$lineIndex, round($origin[$i]['tickets'][$j], 2));
                                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                                if (is_int($colorSwitcher / 2)) {
                                    ExcelUtilities::setBackgroundColor(
                                        $sheet->getCell($startCell.$lineIndex),
                                        $colorFour
                                    );
                                }
                                $startCell++;
                            }
                        }
                        $colorSwitcher++;
                    }
                }
                $sheet->setCellValue($startCell.$lineIndex, round($origin[24]['tickets'], 2));

                ExcelUtilities::setBorder($sheet->getCell($startCell.$lineIndex));
                $lineIndex += 2;
                //END TICKET CANAL
            }
        }

        $filename = $this->translator->trans('report.sales.hour_by_hour.title').date('dmY_His').".xls";
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

    private function serializeHourResult($criteria, $divisionsRaw, $currentRestaurant)
    {
        $result = array();
        $hoursOpeningClosing = $this->getOpeningAndClosingHour($divisionsRaw, $currentRestaurant);

        $openingHour = $hoursOpeningClosing['openingHour'];
        $closingHour = $hoursOpeningClosing['closingHour'];

        $firstHour = $openingHour;
        $lastHour = $closingHour;

        $minHour = ($lastHour >= $firstHour) ? $lastHour : 23;
        $maxHour = ($lastHour <= $firstHour) ? $lastHour : 0;

        $criteriaDate = \DateTime::createFromFormat('d/m/Y', $criteria['to']);
        $today = new \DateTime();
        if ($criteriaDate > $today) {
            for ($i = 0; $i < 25; $i++) {
                $result['ticket'][$i]['nbrTicket'] = '*';
                $result['ca'][$i] = '*';
            }
        } else {
            for ($i = 0; $i < 25; $i++) {
                if ($criteria == date('d/m/Y') && (date('G') < $i
                        || (date('G') > $i && $i < $firstHour))
                ) {
                    $result['ticket'][$i]['nbrTicket'] = '*';
                    $result['ca'][$i] = '*';
                } else {
                    $result['ticket'][$i]['nbrTicket'] = 0;
                    $result['ca'][$i] = 0;

                    foreach ($divisionsRaw as $raw) {
                        if ($raw['entryhour'] == $i) {
                            if (($i >= $firstHour && $i <= $minHour) || ($i <= $maxHour)) {
                                $result['ticket'][$i]['nbrTicket'] += $raw['countticket'];
                                $result['ca'][$i] += $raw['ca'];
                                if(isset($raw['origin'])) {
                                    $result['origin'][$raw['origin']][$i]['ca'] = $raw['ca'];
                                    $result['origin'][$raw['origin']][$i]['tickets'] = $raw['countticket'];
                                }                            }
                        }
                    }
                    $result['ca'][$i] = number_format($result['ca'][$i], 2, '.', '');
                }
            }

            if (isset($result['origin'])) {
                foreach ($result['origin'] as &$origin) {
                    $origin[24]['ca'] = 0;
                    $origin[24]['tickets'] = 0;
                    for ($i = 0; $i < 24; $i++) {
                        if ($criteria == date('d/m/Y') && (date('G') < $i
                                || (date('G') > $i && $i < $openingHour))
                        ) {
                            $origin[$i]['ca'] = '*';
                            $origin[$i]['tickets'] = '*';
                        } elseif (!isset($origin[$i])) {
                            $origin[$i]['ca'] = 0;
                            $origin[$i]['tickets'] = 0;
                        } else {
                            $origin[24]['ca'] += $origin[$i]['ca'];
                            $origin[24]['tickets'] += $origin[$i]['tickets'];
                        }
                    }
                }
            }

            for ($i = 0; $i < 24; $i++) {
                $result['ticket'][24]['nbrTicket'] += $result['ticket'][$i]['nbrTicket'];
                $result['ca'][24] += $result['ca'][$i];
            }
        }


        $result['ca_prev'] = $this->getCaPrevPerHour($criteria, $currentRestaurant);
        if (isset($result['origin'])) {
            uksort(
                $result['origin'],
                function ($key1, $key2) {
                    $defaultSort = [
                        'pos' => 0,
                        'drive' => 1,
                        'borne' => 2,
                        'delivery' => 3,
                        'e-ordering' => 4,
                    ];
                    if (!isset($defaultSort[strtolower($key1)])) {
                        return 1;
                    } else {
                        if (!isset($defaultSort[strtolower($key2)])) {
                            return -1;
                        } else {
                            return ($defaultSort[strtolower($key1)] > $defaultSort[strtolower($key2)]) ? 1 : -1;
                        }
                    }
                }
            );
        }

        return [
            'result' => $result,
            'openingHour' => $openingHour,
            'closingHour' => $closingHour,
        ];

    }

    private function serializeHalfOrQuarterHourResult($criteria, $divisionsRaw, $currentRestaurant, $schedule)
    {
        $length = 0;
        if ($schedule == 1) {
            $length = 2;
        } else {
            if ($schedule == 2) {
                $length = 4;
            }
        }

        $result = array();
        $hoursOpeningClosing = $this->getOpeningAndClosingHour($divisionsRaw, $currentRestaurant);

        $openingHour = $hoursOpeningClosing['openingHour'];
        $closingHour = $hoursOpeningClosing['closingHour'];

        $firstHour = $openingHour;
        $lastHour = $closingHour;

        $minHour = ($lastHour >= $firstHour) ? $lastHour : 23;
        $maxHour = ($lastHour <= $firstHour) ? $lastHour : 0;

        $criteriaDate = \DateTime::createFromFormat('d-m-Y', $criteria['to']);
        $today = new \DateTime();

        if ($criteriaDate > $today) {
            for ($i = 0; $i < 24; $i++) {
                for ($j = 0; $j < $length; $j++) {
                    $result['ticket'][$i]['nbrTicket'][$j] = '*';
                    $result['ca'][$i][$j] = '*';
                }
            }
        } else {
            for ($i = 0; $i < 24; $i++) {
                if ($criteria == date('d/m/Y') && (date('G') < $i || (date('G') > $i && $i < $firstHour))) {
                    for ($j = 0; $j < $length; $j++) {
                        $result['ticket'][$i]['nbrTicket'][$j] = '*';
                        $result['ca'][$i][$j] = '*';
                    }
                } else {
                    for ($j = 0; $j < $length; $j++) {
                        $result['ticket'][$i]['nbrTicket'][$j] = 0;
                        $result['ca'][$i][$j] = 0;
                    }
                    foreach ($divisionsRaw as $raw) {
                        if ($raw['entryhour'] == $i) {
                            if (($i >= $firstHour && $i <= $minHour) || ($i <= $maxHour)) {
                                $result['ticket'][$i]['nbrTicket'][$raw["schedule"]] += $raw['countticket'];
                                $result['ca'][$i][$raw["schedule"]] += $raw['ca'];
                                if(isset($raw['origin'])){
                                $result['origin'][$raw['origin']][$i]['ca'][$raw["schedule"]] = $raw['ca'];
                                $result['origin'][$raw['origin']][$i]['tickets'][$raw["schedule"]] = $raw['countticket'];
                                }
                            }
                        }
                    }
                    for ($j = 0; $j < $length; $j++) {
                        $result['ca'][$i][$j] = number_format($result['ca'][$i][$j], 2, '.', '');
                    }
                }
            }

            if (isset($result['origin'])) {
                foreach ($result['origin'] as $k => &$origin) {
                    if (!isset($origin[24]['ca'])) {
                        $origin[24]['ca'] = 0;
                    }
                    if (!isset($origin[24]['tickets'])) {
                        $origin[24]['tickets'] = 0;
                    }
                    for ($i = 0; $i < 24; $i++) {
                        if (!isset($origin[$i])) {

                            for ($j = 0; $j < $length; $j++) {
                                $origin[$i]['ca'][$j] = 0;
                                $origin[$i]['tickets'][$j] = 0;
                            }
                        }

                        for ($j = 0; $j < $length; $j++) {
                            if (!isset($origin[$i]['ca'][$j])) {
                                $origin[$i]['ca'][$j] = 0;
                                $origin[$i]['tickets'][$j] = 0;
                            }
                        }

                        if ($criteria == date('d/m/Y') && (date('G') < $i
                                || (date('G') > $i && $i < $openingHour))
                        ) {
                            for ($j = 0; $j < $length; $j++) {
                                $origin[$i]['ca'][$j] = '*';
                                $origin[$i]['tickets'][$j] = '*';
                            }
                        } else {
                            for ($j = 0; $j < $length; $j++) {
                                $origin[24]['ca'] += $origin[$i]['ca'][$j];
                                $origin[24]['tickets'] += $origin[$i]['tickets'][$j];
                            }
                        }
                    }
                }
            }

            $result['ticket'][24]['nbrTicket'] = 0;
            $result['ca'][24] = 0;
            for ($i = 0; $i < 24; $i++) {
                for ($j = 0; $j < $length; $j++) {
                    $result['ticket'][24]['nbrTicket'] += $result['ticket'][$i]['nbrTicket'][$j];
                    $result['ca'][24] += $result['ca'][$i][$j];
                }
            }
        }

        $result['ca_prev'] = $this->getCaPrevPerHalfOrQuarterHour($criteria, $currentRestaurant, $schedule);


        if (isset($result['origin'])) {
            uksort(
                $result['origin'],
                function ($key1, $key2) {
                    $defaultSort = [
                        'pos' => 0,
                        'drive' => 1,
                        'borne' => 2,
                        'delivery' => 3,
                        'e-ordering' => 4,
                    ];
                    if (!isset($defaultSort[strtolower($key1)])) {
                        return 1;
                    } else {
                        if (!isset($defaultSort[strtolower($key2)])) {
                            return -1;
                        } else {
                            return ($defaultSort[strtolower($key1)] > $defaultSort[strtolower($key2)]) ? 1 : -1;
                        }
                    }
                }
            );
        }

        return [
            'result' => $result,
            'openingHour' => $openingHour,
            'closingHour' => $closingHour,
        ];
    }

    public function generateCaBrutBySliceHourReport($filter)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $criteria1 = $criteria2 = $criteria3 = $criteria4 = array();
        $criteria1['to'] = $criteria1['from'] = $filter['date1']->format('d-m-Y');
        $criteria2['to'] = $criteria2['from'] = $filter['date2']->format('d-m-Y');
        $criteria3['to'] = $criteria3['from'] = $filter['date3']->format('d-m-Y');
        $criteria4['to'] = $criteria4['from'] = $filter['date4']->format('d-m-Y');
        if ($filter['scheduleType'] == 0) {
            if ($this->restaurantService->isHistoricDate($currentRestaurant, $filter['date1'])) {
                $divisionsRaw1 = $this->em->getRepository(Ticket::class)->getHistoricCaBrutTicketsPerHour(
                    $criteria1,
                    $currentRestaurant
                );
            } else {
                $divisionsRaw1 = $this->em->getRepository(Ticket::class)->getCaBrutTicketsPerHour(
                    $criteria1,
                    $currentRestaurant
                );
            }
            if ($this->restaurantService->isHistoricDate($currentRestaurant, $filter['date2'])) {
                $divisionsRaw2 = $this->em->getRepository(Ticket::class)->getHistoricCaBrutTicketsPerHour(
                    $criteria2,
                    $currentRestaurant
                );
            } else {
                $divisionsRaw2 = $this->em->getRepository(Ticket::class)->getCaBrutTicketsPerHour(
                    $criteria2,
                    $currentRestaurant
                );
            }
            if ($this->restaurantService->isHistoricDate($currentRestaurant, $filter['date3'])) {
                $divisionsRaw3 = $this->em->getRepository(Ticket::class)->getHistoricCaBrutTicketsPerHour(
                    $criteria3,
                    $currentRestaurant
                );
            } else {
                $divisionsRaw3 = $this->em->getRepository(Ticket::class)->getCaBrutTicketsPerHour(
                    $criteria3,
                    $currentRestaurant
                );
            }
            if ($this->restaurantService->isHistoricDate($currentRestaurant, $filter['date4'])) {
                $divisionsRaw4 = $this->em->getRepository(Ticket::class)->getHistoricCaBrutTicketsPerHour(
                    $criteria4,
                    $currentRestaurant
                );
            } else {
                $divisionsRaw4 = $this->em->getRepository(Ticket::class)->getCaBrutTicketsPerHour(
                    $criteria4,
                    $currentRestaurant
                );
            }
            $divisionsRaw = array($divisionsRaw1, $divisionsRaw2, $divisionsRaw3, $divisionsRaw4);

            return $this->serializeCaHourResult($divisionsRaw);
        } else {
            $divisionsRaw1 = $this->em->getRepository(Ticket::class)->getCaBrutTicketsPerHalfOrQuarterHour(
                $criteria1,
                $currentRestaurant,
                $filter['scheduleType']
            );
            $divisionsRaw2 = $this->em->getRepository(Ticket::class)->getCaBrutTicketsPerHalfOrQuarterHour(
                $criteria2,
                $currentRestaurant,
                $filter['scheduleType']
            );
            $divisionsRaw3 = $this->em->getRepository(Ticket::class)->getCaBrutTicketsPerHalfOrQuarterHour(
                $criteria3,
                $currentRestaurant,
                $filter['scheduleType']
            );
            $divisionsRaw4 = $this->em->getRepository(Ticket::class)->getCaBrutTicketsPerHalfOrQuarterHour(
                $criteria4,
                $currentRestaurant,
                $filter['scheduleType']
            );
            $divisionsRaw = array($divisionsRaw1, $divisionsRaw2, $divisionsRaw3, $divisionsRaw4);

            return $this->serializeCaHalfOrQuarterHourResult($divisionsRaw, $filter['scheduleType']);
        }

    }

    private function serializeCaHourResult($divisionsRaw)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $openingHourRestaurant = $this->paramService->getRestaurantOpeningHour($currentRestaurant);
        $closingHourRestaurant = $this->paramService->getRestaurantClosingHour($currentRestaurant);
        $result = array();
        for ($k = 0; $k < 4; $k++) {
            for ($i = 0; $i < 25; $i++) {
                $result['date'.$k][$i] = 0;
                foreach ($divisionsRaw[$k] as $raw) {
                    if ($raw['entryhour'] == $i) {
                        $result['date'.$k][$i] += $raw['ca'];
                    }
                }
                $result['date'.$k][$i] = number_format($result['date'.$k][$i], 2, '.', '');
            }
            for ($i = 0; $i < 24; $i++) {
                $result['date'.$k][24] += $result['date'.$k][$i];
            }
        }

        return array(
            'result' => $result, 'openingHour' =>intval($openingHourRestaurant), 'closingHour' => intval($closingHourRestaurant)
        );

    }

    private function serializeCaHalfOrQuarterHourResult($divisionsRaw, $schedule)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $openingHourRestaurant = $this->paramService->getRestaurantOpeningHour($currentRestaurant);
        $closingHourRestaurant = $this->paramService->getRestaurantClosingHour($currentRestaurant);
        $length = 0;
        if ($schedule == 1) {
            $length = 2;
        } else {
            if ($schedule == 2) {
                $length = 4;
            }
        }

        $result = array();
        for ($k = 0; $k < 4; $k++) {
            for ($i = 0; $i < 24; $i++) {
                for ($j = 0; $j < $length; $j++) {
                    $result['date'.$k][$i][$j] = 0;
                }
                foreach ($divisionsRaw[$k] as $raw) {
                    if ($raw['entryhour'] == $i) {
                        $result['date'.$k][$i][$raw["schedule"]] += $raw['ca'];
                    }
                }
            }
            $result['date'.$k][24] = 0;
            for ($i = 0; $i < 24; $i++) {
                for ($j = 0; $j < $length; $j++) {
                    $result['date'.$k][24] += $result['date'.$k][$i][$j];
                }
            }
        }

        return array(
            'result' => $result,'openingHour' => intval($openingHourRestaurant), 'closingHour' => intval($closingHourRestaurant)
        );
    }


    public function generateCaHTvaBySliceHourReport($filter)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $criteria1 = $criteria2 = $criteria3 = $criteria4 = array();
        $criteria1['to'] = $criteria1['from'] = $filter['date1']->format('d-m-Y');
        $criteria2['to'] = $criteria2['from'] = $filter['date2']->format('d-m-Y');
        $criteria3['to'] = $criteria3['from'] = $filter['date3']->format('d-m-Y');
        $criteria4['to'] = $criteria4['from'] = $filter['date4']->format('d-m-Y');
        if ($filter['scheduleType'] == 0) {
            if($this->restaurantService->isHistoricDate($currentRestaurant,$filter['date1'])){
                $divisionsRaw1 = $this->em->getRepository(Ticket::class)->getHistoricCaHTvaPerSliceHour(
                    $criteria1,
                    $currentRestaurant
                );
            }else{
                $divisionsRaw1 = $this->em->getRepository(Ticket::class)->getCaHTvaPerSliceHour(
                    $criteria1,
                    $currentRestaurant
                );
            }

            if($this->restaurantService->isHistoricDate($currentRestaurant,$filter['date2'])){
                $divisionsRaw2 = $this->em->getRepository(Ticket::class)->getHistoricCaHTvaPerSliceHour(
                    $criteria2,
                    $currentRestaurant
                );
            }else{
                $divisionsRaw2 = $this->em->getRepository(Ticket::class)->getCaHTvaPerSliceHour(
                    $criteria2,
                    $currentRestaurant
                );
            }

            if($this->restaurantService->isHistoricDate($currentRestaurant,$filter['date3'])){
                $divisionsRaw3 = $this->em->getRepository(Ticket::class)->getHistoricCaHTvaPerSliceHour(
                    $criteria3,
                    $currentRestaurant
                );
            }else{
                $divisionsRaw3 = $this->em->getRepository(Ticket::class)->getCaHTvaPerSliceHour(
                    $criteria3,
                    $currentRestaurant
                );
            }

            if($this->restaurantService->isHistoricDate($currentRestaurant,$filter['date4'])){
                $divisionsRaw4 = $this->em->getRepository(Ticket::class)->getHistoricCaHTvaPerSliceHour(
                    $criteria4,
                    $currentRestaurant
                );
            }else{
                $divisionsRaw4 = $this->em->getRepository(Ticket::class)->getCaHTvaPerSliceHour(
                    $criteria4,
                    $currentRestaurant
                );
            }

            $divisionsRaw = array($divisionsRaw1, $divisionsRaw2, $divisionsRaw3, $divisionsRaw4);

            return $this->serializeCaHourResult($divisionsRaw);
        } else {
            $divisionsRaw1 = $this->em->getRepository(Ticket::class)->getCaHTvaPerHalfOrQuarterSliceHour(
                $criteria1,
                $currentRestaurant,
                $filter['scheduleType']
            );
            $divisionsRaw2 = $this->em->getRepository(Ticket::class)->getCaHTvaPerHalfOrQuarterSliceHour(
                $criteria2,
                $currentRestaurant,
                $filter['scheduleType']
            );
            $divisionsRaw3 = $this->em->getRepository(Ticket::class)->getCaHTvaPerHalfOrQuarterSliceHour(
                $criteria3,
                $currentRestaurant,
                $filter['scheduleType']
            );
            $divisionsRaw4 = $this->em->getRepository(Ticket::class)->getCaHTvaPerHalfOrQuarterSliceHour(
                $criteria4,
                $currentRestaurant,
                $filter['scheduleType']
            );
            $divisionsRaw = array($divisionsRaw1, $divisionsRaw2, $divisionsRaw3, $divisionsRaw4);

            return $this->serializeCaHalfOrQuarterHourResult($divisionsRaw, $filter['scheduleType']);
        }

    }

    public function generateCaExcelFile($result, $filter, $logoPath, $caType,$openingHour, $closingHour)
    {

        if($openingHour > $closingHour){
            $limitHour=23;
        }else{
            $limitHour= $closingHour;
        }
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $colorOne = "ECECEC";
        $colorTwo = "dbd1cb";
        $colorThree = "ffdcc4";
        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('report.sales.slice_schedule.title'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('report.sales.slice_schedule.title');
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
        $content = $currentRestaurant->getCode().' '.$currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);
        //filter Zone
        // Type
        $sheet->mergeCells("A10:B10");
        ExcelUtilities::setFont($sheet->getCell("A10"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A10"), $colorOne);
        $sheet->setCellValue('A10', $this->translator->trans('report.sales.hour_by_hour.ca_type').":");
        if ($caType == 0) {
            $Type = $this->translator->trans('ca_brut_ttc');
        } else {
            $Type = $this->translator->trans('ca_net_htva');
        }
        $sheet->mergeCells("C10:D10");
        ExcelUtilities::setFont($sheet->getCell("C10"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C10"), $colorOne);
        $sheet->setCellValue('C10', $Type);
        // schedule
        $sheet->mergeCells("E10:F10");
        ExcelUtilities::setFont($sheet->getCell("E10"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E10"), $colorOne);
        $sheet->setCellValue('E10', $this->translator->trans('report.sales.hour_by_hour.calculate_by').":");
        if ($filter['scheduleType'] == 0) {
            $schedule = $this->translator->trans('report.sales.hour_by_hour.hour');
        } elseif ($filter['scheduleType'] == 1) {
            $schedule = $this->translator->trans('report.sales.hour_by_hour.half_hour');
        } else {
            $schedule = $this->translator->trans('report.sales.hour_by_hour.quarter_hour');
        }
        $sheet->mergeCells("G10:L10");
        ExcelUtilities::setFont($sheet->getCell("G10"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G10"), $colorOne);
        $sheet->setCellValue('G10', $schedule);
        //Jours de référence
        $sheet->mergeCells("A11:L11");
        ExcelUtilities::setFont($sheet->getCell("A11"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A11"), $colorOne);
        $sheet->setCellValue('A11', $this->translator->trans('report.sales.slice_schedule.ref_date'));
        //date1
        ExcelUtilities::setFont($sheet->getCell("A12"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A12"), $colorOne);
        $sheet->setCellValue('A12', $this->translator->trans('report.sales.slice_schedule.date').'1');
        $sheet->mergeCells("B12:C12");
        ExcelUtilities::setFont($sheet->getCell("B12"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B12"), $colorOne);
        $sheet->setCellValue('B12', $filter['date1']->format('d-m-Y'));
        //date2
        ExcelUtilities::setFont($sheet->getCell("D12"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D12"), $colorOne);
        $sheet->setCellValue('D12', $this->translator->trans('report.sales.slice_schedule.date').'2');
        $sheet->mergeCells("E12:F12");
        ExcelUtilities::setFont($sheet->getCell("E12"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E12"), $colorOne);
        $sheet->setCellValue('E12', $filter['date2']->format('d-m-Y'));
        //date3
        ExcelUtilities::setFont($sheet->getCell("G12"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G12"), $colorOne);
        $sheet->setCellValue('G12', $this->translator->trans('report.sales.slice_schedule.date').'3');
        $sheet->mergeCells("H12:I12");
        ExcelUtilities::setFont($sheet->getCell("H12"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H12"), $colorOne);
        $sheet->setCellValue('H12', $filter['date3']->format('d-m-Y'));
        //date4
        ExcelUtilities::setFont($sheet->getCell("J12"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J12"), $colorOne);
        $sheet->setCellValue('J12', $this->translator->trans('report.sales.slice_schedule.date').'4');
        $sheet->mergeCells("K12:L12");
        ExcelUtilities::setFont($sheet->getCell("K12"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K12"), $colorOne);
        $sheet->setCellValue('K12', $filter['date4']->format('d-m-Y'));

        //total ca
        //date1
        ExcelUtilities::setFont($sheet->getCell("A13"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A13"), $colorOne);
        $sheet->mergeCells("B13:C13");
        ExcelUtilities::setFont($sheet->getCell("B13"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B13"), $colorOne);
        $sheet->setCellValue('B13', number_format($result['date0'][24], '2', '.', '').' €');
        //date2
        ExcelUtilities::setFont($sheet->getCell("D13"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("D13"), $colorOne);
        $sheet->mergeCells("E13:F13");
        ExcelUtilities::setFont($sheet->getCell("E13"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E13"), $colorOne);
        $sheet->setCellValue('E13', number_format($result['date1'][24], '2', '.', '').' €');
        //date3
        ExcelUtilities::setFont($sheet->getCell("G13"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("G13"), $colorOne);
        $sheet->mergeCells("H13:I13");
        ExcelUtilities::setFont($sheet->getCell("H13"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("H13"), $colorOne);
        $sheet->setCellValue('H13', number_format($result['date2'][24], '2', '.', '').' €');
        //date4
        ExcelUtilities::setFont($sheet->getCell("J13"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("J13"), $colorOne);
        $sheet->mergeCells("K13:L13");
        ExcelUtilities::setFont($sheet->getCell("K13"), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("K13"), $colorOne);
        $sheet->setCellValue('K13', number_format($result['date3'][24], '2', '.', '').' €');

        //Heure
        ExcelUtilities::setFont($sheet->getCell('A15'), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("A15"), $colorTwo);
        //Date 1
        $sheet->mergeCells('B15:C15');
        ExcelUtilities::setFont($sheet->getCell('B15'), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell('B15'), $colorTwo);
        $sheet->setCellValue('B15', $filter['date1']->format('d-m-Y'));
        //Date 2
        $sheet->mergeCells('D15:E15');
        ExcelUtilities::setFont($sheet->getCell('D15'), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell('D15'), $colorTwo);
        $sheet->setCellValue('D15', $filter['date2']->format('d-m-Y'));

        //Date 3
        $sheet->mergeCells('F15:G15');
        ExcelUtilities::setFont($sheet->getCell('F15'), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell('F15'), $colorTwo);
        $sheet->setCellValue('F15', $filter['date3']->format('d-m-Y'));

        //Date 4
        $sheet->mergeCells('H15:I15');
        ExcelUtilities::setFont($sheet->getCell('H15'), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell('H15'), $colorTwo);
        $sheet->setCellValue('H15', $filter['date4']->format('d-m-Y'));

        //Moyenne
        $sheet->mergeCells('J15:K15');
        ExcelUtilities::setFont($sheet->getCell('J15'), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell('J15'), $colorThree);
        $sheet->setCellValue('J15', $this->translator->trans('report.sales.slice_schedule.moyen'));

        //Border
        $cell = 'A';
        while ($cell != 'L') {
            ExcelUtilities::setBorder($sheet->getCell($cell.'15'));
            $cell++;
        }

        $i = 16;
        if ($filter['scheduleType'] == 0) {
            for ($k = $openingHour; $k <= $limitHour; $k++) {
                //Heure
                ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                $sheet->setCellValue('A'.$i, $k.':00');
                //Date 1
                $sheet->mergeCells('B'.$i.':C'.$i);
                ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
                $sheet->setCellValue('B'.$i, $result['date0'][$k]);
                //Date 2
                $sheet->mergeCells('D'.$i.':E'.$i);
                ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
                $sheet->setCellValue('D'.$i, $result['date1'][$k]);

                //Date 3
                $sheet->mergeCells('F'.$i.':G'.$i);
                ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                $sheet->setCellValue('F'.$i, $result['date2'][$k]);

                //Date 4
                $sheet->mergeCells('H'.$i.':I'.$i);
                ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                $sheet->setCellValue('H'.$i, $result['date3'][$k]);

                $moyen = ($result['date0'][$k] + $result['date1'][$k] + $result['date2'][$k] + $result['date3'][$k]) / 4;
                //Moyenne
                $sheet->mergeCells('J'.$i.':K'.$i);
                ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorThree);
                $sheet->setCellValue('J'.$i, number_format($moyen, '2', '.', ''));
                //Border
                $cell = 'A';
                while ($cell != 'L') {
                    ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                    $cell++;
                }
                $i++;
            }
            if($openingHour > $closingHour){
                for ($k = 0; $k <= $closingHour; $k++) {
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                    $sheet->setCellValue('A'.$i, $k.':00');
                    //Date 1
                    $sheet->mergeCells('B'.$i.':C'.$i);
                    ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
                    $sheet->setCellValue('B'.$i, $result['date0'][$k]);
                    //Date 2
                    $sheet->mergeCells('D'.$i.':E'.$i);
                    ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
                    $sheet->setCellValue('D'.$i, $result['date1'][$k]);

                    //Date 3
                    $sheet->mergeCells('F'.$i.':G'.$i);
                    ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                    $sheet->setCellValue('F'.$i, $result['date2'][$k]);

                    //Date 4
                    $sheet->mergeCells('H'.$i.':I'.$i);
                    ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                    $sheet->setCellValue('H'.$i, $result['date3'][$k]);

                    $moyen = ($result['date0'][$k] + $result['date1'][$k] + $result['date2'][$k] + $result['date3'][$k]) / 4;
                    //Moyenne
                    $sheet->mergeCells('J'.$i.':K'.$i);
                    ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorThree);
                    $sheet->setCellValue('J'.$i, number_format($moyen, '2', '.', ''));
                    //Border
                    $cell = 'A';
                    while ($cell != 'L') {
                        ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                        $cell++;
                    }
                    $i++;
                }
            }
            //Heure
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
            $sheet->setCellValue('A'.$i, $this->translator->trans('report.sales.slice_schedule.total'));
            //Date 1
            $sheet->mergeCells('B'.$i.':C'.$i);
            ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("B".$i), $colorOne);
            $sheet->setCellValue('B'.$i, $result['date0'][24]);
            //Date 2
            $sheet->mergeCells('D'.$i.':E'.$i);
            ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("D".$i), $colorOne);
            $sheet->setCellValue('D'.$i, $result['date1'][24]);

            //Date 3
            $sheet->mergeCells('F'.$i.':G'.$i);
            ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorOne);
            $sheet->setCellValue('F'.$i, $result['date2'][24]);

            //Date 4
            $sheet->mergeCells('H'.$i.':I'.$i);
            ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorOne);
            $sheet->setCellValue('H'.$i, $result['date3'][24]);
            $moyenTotal = ($result['date0'][24] + $result['date1'][24] + $result['date2'][24] + $result['date3'][24]) / 4;
            //Moyenne
            $sheet->mergeCells('J'.$i.':K'.$i);
            ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorOne);
            $sheet->setCellValue('J'.$i, number_format($moyenTotal, '2', '.', ''));
            //Border
            $cell = 'A';
            while ($cell != 'L') {
                ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                $cell++;
            }
        } elseif ($filter['scheduleType'] == 1) {
            for ($k = $openingHour ; $k <= $limitHour ; $k++) {
                //Heure
                ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                $sheet->setCellValue('A'.$i, $k.':00');
                //Date 1
                $sheet->mergeCells('B'.$i.':C'.$i);
                ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
                $sheet->setCellValue('B'.$i, $result['date0'][$k][0]);
                //Date 2
                $sheet->mergeCells('D'.$i.':E'.$i);
                ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
                $sheet->setCellValue('D'.$i, $result['date1'][$k][0]);

                //Date 3
                $sheet->mergeCells('F'.$i.':G'.$i);
                ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                $sheet->setCellValue('F'.$i, $result['date2'][$k][0]);

                //Date 4
                $sheet->mergeCells('H'.$i.':I'.$i);
                ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                $sheet->setCellValue('H'.$i, $result['date3'][$k][0]);
                $moyen = ($result['date0'][$k][0] + $result['date1'][$k][0] + $result['date2'][$k][0] + $result['date3'][$k][0]) / 4;
                //Moyenne
                $sheet->mergeCells('J'.$i.':K'.$i);
                ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorThree);
                $sheet->setCellValue('J'.$i, number_format($moyen, '2', '.', ''));
                //Border
                $cell = 'A';
                while ($cell != 'L') {
                    ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                    $cell++;
                }
                $i++;

                //Heure
                ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                $sheet->setCellValue('A'.$i, $k.':30');
                //Date 1
                $sheet->mergeCells('B'.$i.':C'.$i);
                ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
                $sheet->setCellValue('B'.$i, $result['date0'][$k][1]);
                //Date 2
                $sheet->mergeCells('D'.$i.':E'.$i);
                ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
                $sheet->setCellValue('D'.$i, $result['date1'][$k][1]);

                //Date 3
                $sheet->mergeCells('F'.$i.':G'.$i);
                ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                $sheet->setCellValue('F'.$i, $result['date2'][$k][1]);

                //Date 4
                $sheet->mergeCells('H'.$i.':I'.$i);
                ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                $sheet->setCellValue('H'.$i, $result['date3'][$k][1]);
                $moyen = ($result['date0'][$k][1] + $result['date1'][$k][1] + $result['date2'][$k][1] + $result['date3'][$k][1]) / 4;
                //Moyenne
                $sheet->mergeCells('J'.$i.':K'.$i);
                ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorThree);
                $sheet->setCellValue('J'.$i, number_format($moyen, '2', '.', ''));
                //Border
                $cell = 'A';
                while ($cell != 'L') {
                    ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                    $cell++;
                }
                $i++;
            }
            if($openingHour > $closingHour){
                for ($k = 0 ; $k <= $closingHour-1 ; $k++) {
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                    $sheet->setCellValue('A'.$i, $k.':00');
                    //Date 1
                    $sheet->mergeCells('B'.$i.':C'.$i);
                    ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
                    $sheet->setCellValue('B'.$i, $result['date0'][$k][0]);
                    //Date 2
                    $sheet->mergeCells('D'.$i.':E'.$i);
                    ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
                    $sheet->setCellValue('D'.$i, $result['date1'][$k][0]);

                    //Date 3
                    $sheet->mergeCells('F'.$i.':G'.$i);
                    ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                    $sheet->setCellValue('F'.$i, $result['date2'][$k][0]);

                    //Date 4
                    $sheet->mergeCells('H'.$i.':I'.$i);
                    ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                    $sheet->setCellValue('H'.$i, $result['date3'][$k][0]);
                    $moyen = ($result['date0'][$k][0] + $result['date1'][$k][0] + $result['date2'][$k][0] + $result['date3'][$k][0]) / 4;
                    //Moyenne
                    $sheet->mergeCells('J'.$i.':K'.$i);
                    ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorThree);
                    $sheet->setCellValue('J'.$i, number_format($moyen, '2', '.', ''));
                    //Border
                    $cell = 'A';
                    while ($cell != 'L') {
                        ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                        $cell++;
                    }
                    $i++;

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                    $sheet->setCellValue('A'.$i, $k.':30');
                    //Date 1
                    $sheet->mergeCells('B'.$i.':C'.$i);
                    ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
                    $sheet->setCellValue('B'.$i, $result['date0'][$k][1]);
                    //Date 2
                    $sheet->mergeCells('D'.$i.':E'.$i);
                    ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
                    $sheet->setCellValue('D'.$i, $result['date1'][$k][1]);

                    //Date 3
                    $sheet->mergeCells('F'.$i.':G'.$i);
                    ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                    $sheet->setCellValue('F'.$i, $result['date2'][$k][1]);

                    //Date 4
                    $sheet->mergeCells('H'.$i.':I'.$i);
                    ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                    $sheet->setCellValue('H'.$i, $result['date3'][$k][1]);
                    $moyen = ($result['date0'][$k][1] + $result['date1'][$k][1] + $result['date2'][$k][1] + $result['date3'][$k][1]) / 4;
                    //Moyenne
                    $sheet->mergeCells('J'.$i.':K'.$i);
                    ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorThree);
                    $sheet->setCellValue('J'.$i, number_format($moyen, '2', '.', ''));
                    //Border
                    $cell = 'A';
                    while ($cell != 'L') {
                        ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                        $cell++;
                    }
                    $i++;
                }
            }
            //Heure
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
            $sheet->setCellValue('A'.$i, $this->translator->trans('report.sales.slice_schedule.total'));
            //Date 1
            $sheet->mergeCells('B'.$i.':C'.$i);
            ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("B".$i), $colorOne);
            $sheet->setCellValue('B'.$i, $result['date0'][24]);
            //Date 2
            $sheet->mergeCells('D'.$i.':E'.$i);
            ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("D".$i), $colorOne);
            $sheet->setCellValue('D'.$i, $result['date1'][24]);

            //Date 3
            $sheet->mergeCells('F'.$i.':G'.$i);
            ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorOne);
            $sheet->setCellValue('F'.$i, $result['date2'][24]);

            //Date 4
            $sheet->mergeCells('H'.$i.':I'.$i);
            ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorOne);
            $sheet->setCellValue('H'.$i, $result['date3'][24]);
            $moyenTotal = ($result['date0'][24] + $result['date1'][24] + $result['date2'][24] + $result['date3'][24]) / 4;
            //Moyenne
            $sheet->mergeCells('J'.$i.':K'.$i);
            ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorOne);
            $sheet->setCellValue('J'.$i, number_format($moyenTotal, '2', '.', ''));
            //Border
            $cell = 'A';
            while ($cell != 'L') {
                ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                $cell++;
            }


        } else {

            for ($k = $openingHour; $k <= $limitHour; $k++) {
                //Heure
                ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                $sheet->setCellValue('A'.$i, $k.':00');
                //Date 1
                $sheet->mergeCells('B'.$i.':C'.$i);
                ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
                $sheet->setCellValue('B'.$i, $result['date0'][$k][0]);
                //Date 2
                $sheet->mergeCells('D'.$i.':E'.$i);
                ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
                $sheet->setCellValue('D'.$i, $result['date1'][$k][0]);

                //Date 3
                $sheet->mergeCells('F'.$i.':G'.$i);
                ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                $sheet->setCellValue('F'.$i, $result['date2'][$k][0]);

                //Date 4
                $sheet->mergeCells('H'.$i.':I'.$i);
                ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                $sheet->setCellValue('H'.$i, $result['date3'][$k][0]);
                $moyen = ($result['date0'][$k][0] + $result['date1'][$k][0] + $result['date2'][$k][0] + $result['date3'][$k][0]) / 4;
                //Moyenne
                $sheet->mergeCells('J'.$i.':K'.$i);
                ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorThree);
                $sheet->setCellValue('J'.$i, number_format($moyen, '2', '.', ''));
                //Border
                $cell = 'A';
                while ($cell != 'L') {
                    ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                    $cell++;
                }
                $i++;

                //Heure
                ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                $sheet->setCellValue('A'.$i, $k.':15');
                //Date 1
                $sheet->mergeCells('B'.$i.':C'.$i);
                ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
                $sheet->setCellValue('B'.$i, $result['date0'][$k][1]);
                //Date 2
                $sheet->mergeCells('D'.$i.':E'.$i);
                ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
                $sheet->setCellValue('D'.$i, $result['date1'][$k][1]);

                //Date 3
                $sheet->mergeCells('F'.$i.':G'.$i);
                ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                $sheet->setCellValue('F'.$i, $result['date2'][$k][1]);

                //Date 4
                $sheet->mergeCells('H'.$i.':I'.$i);
                ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                $sheet->setCellValue('H'.$i, $result['date3'][$k][1]);
                $moyen = ($result['date0'][$k][1] + $result['date1'][$k][1] + $result['date2'][$k][1] + $result['date3'][$k][1]) / 4;
                //Moyenne
                $sheet->mergeCells('J'.$i.':K'.$i);
                ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorThree);
                $sheet->setCellValue('J'.$i, number_format($moyen, '2', '.', ''));
                //Border
                $cell = 'A';
                while ($cell != 'L') {
                    ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                    $cell++;
                }
                $i++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                $sheet->setCellValue('A'.$i, $k.':30');
                //Date 1
                $sheet->mergeCells('B'.$i.':C'.$i);
                ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
                $sheet->setCellValue('B'.$i, $result['date0'][$k][2]);
                //Date 2
                $sheet->mergeCells('D'.$i.':E'.$i);
                ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
                $sheet->setCellValue('D'.$i, $result['date1'][$k][2]);

                //Date 3
                $sheet->mergeCells('F'.$i.':G'.$i);
                ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                $sheet->setCellValue('F'.$i, $result['date2'][$k][2]);

                //Date 4
                $sheet->mergeCells('H'.$i.':I'.$i);
                ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                $sheet->setCellValue('H'.$i, $result['date3'][$k][2]);
                $moyen = ($result['date0'][$k][2] + $result['date1'][$k][2] + $result['date2'][$k][2] + $result['date3'][$k][2]) / 4;
                //Moyenne
                $sheet->mergeCells('J'.$i.':K'.$i);
                ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorThree);
                $sheet->setCellValue('J'.$i, number_format($moyen, '2', '.', ''));
                //Border
                $cell = 'A';
                while ($cell != 'L') {
                    ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                    $cell++;
                }
                $i++;

                //Heure
                ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                $sheet->setCellValue('A'.$i, $k.':45');
                //Date 1
                $sheet->mergeCells('B'.$i.':C'.$i);
                ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
                $sheet->setCellValue('B'.$i, $result['date0'][$k][3]);
                //Date 2
                $sheet->mergeCells('D'.$i.':E'.$i);
                ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
                $sheet->setCellValue('D'.$i, $result['date1'][$k][3]);

                //Date 3
                $sheet->mergeCells('F'.$i.':G'.$i);
                ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                $sheet->setCellValue('F'.$i, $result['date2'][$k][3]);

                //Date 4
                $sheet->mergeCells('H'.$i.':I'.$i);
                ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                $sheet->setCellValue('H'.$i, $result['date3'][$k][3]);
                $moyen = ($result['date0'][$k][3] + $result['date1'][$k][3] + $result['date2'][$k][3] + $result['date3'][$k][3]) / 4;
                //Moyenne
                $sheet->mergeCells('J'.$i.':K'.$i);
                ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorThree);
                $sheet->setCellValue('J'.$i, number_format($moyen, '2', '.', ''));

                //Border
                $cell = 'A';
                while ($cell != 'L') {
                    ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                    $cell++;
                }
                $i++;

            }

            if($openingHour > $closingHour){
                for ($k = 0; $k <= $closingHour-1; $k++) {
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                    $sheet->setCellValue('A'.$i, $k.':00');
                    //Date 1
                    $sheet->mergeCells('B'.$i.':C'.$i);
                    ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
                    $sheet->setCellValue('B'.$i, $result['date0'][$k][0]);
                    //Date 2
                    $sheet->mergeCells('D'.$i.':E'.$i);
                    ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
                    $sheet->setCellValue('D'.$i, $result['date1'][$k][0]);

                    //Date 3
                    $sheet->mergeCells('F'.$i.':G'.$i);
                    ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                    $sheet->setCellValue('F'.$i, $result['date2'][$k][0]);

                    //Date 4
                    $sheet->mergeCells('H'.$i.':I'.$i);
                    ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                    $sheet->setCellValue('H'.$i, $result['date3'][$k][0]);
                    $moyen = ($result['date0'][$k][0] + $result['date1'][$k][0] + $result['date2'][$k][0] + $result['date3'][$k][0]) / 4;
                    //Moyenne
                    $sheet->mergeCells('J'.$i.':K'.$i);
                    ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorThree);
                    $sheet->setCellValue('J'.$i, number_format($moyen, '2', '.', ''));
                    //Border
                    $cell = 'A';
                    while ($cell != 'L') {
                        ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                        $cell++;
                    }
                    $i++;

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                    $sheet->setCellValue('A'.$i, $k.':15');
                    //Date 1
                    $sheet->mergeCells('B'.$i.':C'.$i);
                    ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
                    $sheet->setCellValue('B'.$i, $result['date0'][$k][1]);
                    //Date 2
                    $sheet->mergeCells('D'.$i.':E'.$i);
                    ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
                    $sheet->setCellValue('D'.$i, $result['date1'][$k][1]);

                    //Date 3
                    $sheet->mergeCells('F'.$i.':G'.$i);
                    ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                    $sheet->setCellValue('F'.$i, $result['date2'][$k][1]);

                    //Date 4
                    $sheet->mergeCells('H'.$i.':I'.$i);
                    ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                    $sheet->setCellValue('H'.$i, $result['date3'][$k][1]);
                    $moyen = ($result['date0'][$k][1] + $result['date1'][$k][1] + $result['date2'][$k][1] + $result['date3'][$k][1]) / 4;
                    //Moyenne
                    $sheet->mergeCells('J'.$i.':K'.$i);
                    ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorThree);
                    $sheet->setCellValue('J'.$i, number_format($moyen, '2', '.', ''));
                    //Border
                    $cell = 'A';
                    while ($cell != 'L') {
                        ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                        $cell++;
                    }
                    $i++;
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                    $sheet->setCellValue('A'.$i, $k.':30');
                    //Date 1
                    $sheet->mergeCells('B'.$i.':C'.$i);
                    ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
                    $sheet->setCellValue('B'.$i, $result['date0'][$k][2]);
                    //Date 2
                    $sheet->mergeCells('D'.$i.':E'.$i);
                    ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
                    $sheet->setCellValue('D'.$i, $result['date1'][$k][2]);

                    //Date 3
                    $sheet->mergeCells('F'.$i.':G'.$i);
                    ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                    $sheet->setCellValue('F'.$i, $result['date2'][$k][2]);

                    //Date 4
                    $sheet->mergeCells('H'.$i.':I'.$i);
                    ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                    $sheet->setCellValue('H'.$i, $result['date3'][$k][2]);
                    $moyen = ($result['date0'][$k][2] + $result['date1'][$k][2] + $result['date2'][$k][2] + $result['date3'][$k][2]) / 4;
                    //Moyenne
                    $sheet->mergeCells('J'.$i.':K'.$i);
                    ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorThree);
                    $sheet->setCellValue('J'.$i, number_format($moyen, '2', '.', ''));
                    //Border
                    $cell = 'A';
                    while ($cell != 'L') {
                        ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                        $cell++;
                    }
                    $i++;

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorTwo);
                    $sheet->setCellValue('A'.$i, $k.':45');
                    //Date 1
                    $sheet->mergeCells('B'.$i.':C'.$i);
                    ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
                    $sheet->setCellValue('B'.$i, $result['date0'][$k][3]);
                    //Date 2
                    $sheet->mergeCells('D'.$i.':E'.$i);
                    ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
                    $sheet->setCellValue('D'.$i, $result['date1'][$k][3]);

                    //Date 3
                    $sheet->mergeCells('F'.$i.':G'.$i);
                    ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
                    $sheet->setCellValue('F'.$i, $result['date2'][$k][3]);

                    //Date 4
                    $sheet->mergeCells('H'.$i.':I'.$i);
                    ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
                    $sheet->setCellValue('H'.$i, $result['date3'][$k][3]);
                    $moyen = ($result['date0'][$k][3] + $result['date1'][$k][3] + $result['date2'][$k][3] + $result['date3'][$k][3]) / 4;
                    //Moyenne
                    $sheet->mergeCells('J'.$i.':K'.$i);
                    ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorThree);
                    $sheet->setCellValue('J'.$i, number_format($moyen, '2', '.', ''));

                    //Border
                    $cell = 'A';
                    while ($cell != 'L') {
                        ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                        $cell++;
                    }
                    $i++;

                }
            }
            //Heure
            ExcelUtilities::setFont($sheet->getCell('A'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("A".$i), $colorOne);
            $sheet->setCellValue('A'.$i, $this->translator->trans('report.sales.slice_schedule.total'));
            //Date 1
            $sheet->mergeCells('B'.$i.':C'.$i);
            ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("B".$i), $colorOne);
            $sheet->setCellValue('B'.$i, $result['date0'][24]);
            //Date 2
            $sheet->mergeCells('D'.$i.':E'.$i);
            ExcelUtilities::setFont($sheet->getCell('D'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("D".$i), $colorOne);
            $sheet->setCellValue('D'.$i, $result['date1'][24]);

            //Date 3
            $sheet->mergeCells('F'.$i.':G'.$i);
            ExcelUtilities::setFont($sheet->getCell('F'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("F".$i), $colorOne);
            $sheet->setCellValue('F'.$i, $result['date2'][24]);

            //Date 4
            $sheet->mergeCells('H'.$i.':I'.$i);
            ExcelUtilities::setFont($sheet->getCell('H'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("H".$i), $colorOne);
            $sheet->setCellValue('H'.$i, $result['date3'][24]);
            $moyenTotal = ($result['date0'][24] + $result['date1'][24] + $result['date2'][24] + $result['date3'][24]) / 4;
            //Moyenne
            $sheet->mergeCells('J'.$i.':K'.$i);
            ExcelUtilities::setFont($sheet->getCell('J'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell('J'.$i), $colorOne);
            $sheet->setCellValue('J'.$i, number_format($moyenTotal, '2', '.', ''));
            //Border
            $cell = 'A';
            while ($cell != 'L') {
                ExcelUtilities::setBorder($sheet->getCell($cell.$i));
                $cell++;
            }

        }
        $filename = $this->translator->trans('report.sales.slice_schedule.title').date('dmY_His').".xls";
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


    public function generateEmployeeHourByHourReport($criteria, $scheduleType)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        if ($scheduleType == 0) {
            $data = $this->em->getRepository(Ticket::class)->getCaPerEmployeePerHour(
                $criteria,
                $currentRestaurant
            );
        } else {
            $data = $this->em->getRepository(Ticket::class)->getCaPerEmployeePerQuartHour(
                $criteria,
                $currentRestaurant
            );
        }
        $results = array();
        $workHours = $this->getHoursList();

        $hoursMaping = array();
        if ($scheduleType == 0) {
            foreach ($workHours as $key => $value) {
                $hoursMaping[$key] = 0;
            }
        } else {
            foreach ($workHours as $key => $value) {
                $hoursMaping[$key] = array('0' => 0, '1' => 0, '2' => 0, '3' => 0);
            }
        }
        $hours = array_keys($hoursMaping);

        if ($scheduleType == 0) {
            foreach ($data as $row) {
                if (!in_array($row['hour'], $hours)) {
                    $insertAtBegining = array(
                        $row['hour'] => 0,
                    );
                    $hoursMaping = $insertAtBegining + $hoursMaping;
                }
            }
        } else {
            foreach ($data as $row) {
                if (!in_array($row['hour'], $hours)) {
                    $insertAtBegining = array(
                        $row['hour'] => array('0' => 0, '1' => 0, '2' => 0, '3' => 0),
                    );
                    $hoursMaping = $insertAtBegining + $hoursMaping;
                }
            }
        }
        $totals = array(
            'ticket_count' => $hoursMaping,
            'ca_brut_ttc' => $hoursMaping,
            'ca_net_htva' => $hoursMaping,
            'item_qty' => $hoursMaping
        );

        if ($scheduleType == 0) {
            foreach ($data as $row) {

                if (!array_key_exists($row['employee_id'], $results)) {
                    $results[$row['employee_id']] = array(
                        'name' => $row['employee'],
                        'ticket_count' => $hoursMaping,
                        'ca_brut_ttc' => $hoursMaping,
                        'ca_net_htva' => $hoursMaping,
                        'item_qty' => $hoursMaping
                    );
                }

                $results[$row['employee_id']]['ticket_count'][$row['hour']] += $row['ticket_count'];
                $results[$row['employee_id']]['ca_brut_ttc'][$row['hour']] += $row['ca_brut_ttc'];
                $results[$row['employee_id']]['ca_net_htva'][$row['hour']] += $row['ca_net_htva'];
                $results[$row['employee_id']]['item_qty'][$row['hour']] += $row['item_qty'];


                $totals['ticket_count'][$row['hour']] += $row['ticket_count'];
                $totals['ca_brut_ttc'][$row['hour']] += $row['ca_brut_ttc'];
                $totals['ca_net_htva'][$row['hour']] += $row['ca_net_htva'];
                $totals['item_qty'][$row['hour']] += $row['item_qty'];
            }
            foreach ($results as $id => $emp) {

                foreach ($emp['ca_brut_ttc'] as $h => $data) {
                    if ($emp['ticket_count'][$h] > 0) {
                        $results[$id]['tm_brut'][$h] = number_format($data / $emp['ticket_count'][$h], 2, '.', '');
                    } else {
                        $results[$id]['tm_brut'][$h] = 0;
                    }

                }
            }

            foreach ($totals['ca_brut_ttc'] as $h => $data) {
                if ($totals['ticket_count'][$h] > 0) {
                    $totals['tm_brut'][$h] = number_format($data / $totals['ticket_count'][$h], 2, '.', '');
                } else {
                    $totals['tm_brut'][$h] = 0;
                }
            }

        } else {
            foreach ($data as $row) {

                if (!array_key_exists($row['employee_id'], $results)) {
                    $results[$row['employee_id']] = array(
                        'name' => $row['employee'],
                        'ticket_count' => $hoursMaping,
                        'ca_brut_ttc' => $hoursMaping,
                        'ca_net_htva' => $hoursMaping,
                        'item_qty' => $hoursMaping
                    );
                }


                $results[$row['employee_id']]['ticket_count'][$row['hour']][$row['schedule']] += $row['ticket_count'];
                $results[$row['employee_id']]['ca_brut_ttc'][$row['hour']][$row['schedule']] += $row['ca_brut_ttc'];
                $results[$row['employee_id']]['ca_net_htva'][$row['hour']][$row['schedule']] += $row['ca_net_htva'];
                $results[$row['employee_id']]['item_qty'][$row['hour']][$row['schedule']] += $row['item_qty'];


                $totals['ticket_count'][$row['hour']][$row['schedule']] += $row['ticket_count'];
                $totals['ca_brut_ttc'][$row['hour']][$row['schedule']] += $row['ca_brut_ttc'];
                $totals['ca_net_htva'][$row['hour']][$row['schedule']] += $row['ca_net_htva'];
                $totals['item_qty'][$row['hour']][$row['schedule']] += $row['item_qty'];
            }

            foreach ($results as $id => $emp) {
                foreach ($emp['ca_brut_ttc'] as $h => $data) {
                    foreach ($data as $key => $res) {
                        if ($emp['ticket_count'][$h][$key] > 0) {
                            $results[$id]['tm_brut'][$h][$key] = number_format($data[$key] / $emp['ticket_count'][$h][$key], 2, '.', '');
                        } else {
                            $results[$id]['tm_brut'][$h][$key] = 0;
                        }

                    }

                }
            }

            foreach ($totals['ca_brut_ttc'] as $h => $data) {
                foreach ($data as $key => $res) {
                    if ($totals['ticket_count'][$h][$key] > 0) {
                        $totals['tm_brut'][$h][$key] = number_format($data[$key] / $totals['ticket_count'][$h][$key], 2, '.', '');
                    } else {
                        $totals['tm_brut'][$h][$key] = 0;
                    }
                }
            }

        }

        return array('employee_results' => $results, 'totals_results' => $totals);
    }

    /**
     * @return array
     * @throws \Exception
     */
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
            $hoursArray[($i >= 24) ? ($i - 24) : $i] = (($i >= 24) ? ($i - 24) : $i).":00";
        }

        return $hoursArray;
    }



    public function generateEmployeeExcelFile($result, $from, $to, $scheduleType,$logoPath){

        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $colorOne = "ECECEC";
        $colorTwo = "1E90FF";
        $colorThree = "8CBDD7";

        $alignmentH = \PHPExcel_Style_Alignment::HORIZONTAL_CENTER;
        $alignmentV = \PHPExcel_Style_Alignment::VERTICAL_CENTER;

        $phpExcelObject = $this->phpExcel->createPHPExcelObject();
        $phpExcelObject->setActiveSheetIndex(0);
        $sheet = $phpExcelObject->getActiveSheet();
        $sheet->setTitle(substr($this->translator->trans('report.sales.hour_by_hour_employee.title'), 0, 30));

        $sheet->mergeCells("B5:K8");
        $content = $this->translator->trans('report.sales.hour_by_hour_employee.title');
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
        $content = $currentRestaurant->getCode().' '.$currentRestaurant->getName();
        $sheet->setCellValue('B2', $content);

        //FILTER ZONE
        // START DATE
        ExcelUtilities::setFont($sheet->getCell('B10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B10"), $colorOne);
        $sheet->setCellValue('B10', $this->translator->trans('keyword.from').":");
        $sheet->mergeCells("C10:D10");
        ExcelUtilities::setFont($sheet->getCell('C10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("C10"), $colorOne);
        $sheet->setCellValue('C10', $from);
        // END DATE
        ExcelUtilities::setFont($sheet->getCell('E10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("E10"), $colorOne);
        $sheet->setCellValue('E10', $this->translator->trans('keyword.to').":");
        $sheet->mergeCells("F10:G10");
        ExcelUtilities::setFont($sheet->getCell('F10'), 11, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("F10"), $colorOne);
        $sheet->setCellValue('F10', $to);

        $i=13;
        $caBrutTotal=$caNetHTVATotal=$TicketCountTotal=$itemQtyTotal=0;
        foreach ($result['employee_results'] as $employeResult){
           //Employee name
            $sheet->mergeCells('B'.$i.':C'.$i);
            ExcelUtilities::setFont($sheet->getCell('B'.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell("B".$i), $colorTwo);
            $sheet->setCellValue('B'.$i, $employeResult['name']);

            $startCell= 'D';
            foreach ($result['totals_results']['ca_brut_ttc'] as $h => $data){
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell.$i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$i), $colorThree);
                ExcelUtilities::setCellAlignment($sheet->getCell($startCell.$i), $alignmentH);
                $sheet->setCellValue($startCell.$i, $h.':00');
                $startCell++;
                if ($scheduleType == 1) {
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell . $i), $colorThree);
                    ExcelUtilities::setCellAlignment($sheet->getCell($startCell . $i), $alignmentH);
                    $sheet->setCellValue($startCell . $i, $h . ':15');
                    $startCell++;

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell . $i), $colorThree);
                    ExcelUtilities::setCellAlignment($sheet->getCell($startCell . $i), $alignmentH);
                    $sheet->setCellValue($startCell . $i, $h . ':30');
                    $startCell++;

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    ExcelUtilities::setBackgroundColor($sheet->getCell($startCell . $i), $colorThree);
                    ExcelUtilities::setCellAlignment($sheet->getCell($startCell . $i), $alignmentH);
                    $sheet->setCellValue($startCell . $i, $h . ':45');
                    $startCell++;
                }
            }
            //Total
            ExcelUtilities::setFont($sheet->getCell($startCell.$i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell($startCell.$i), $colorThree);
            $sheet->setCellValue($startCell.$i, $this->translator->trans('keyword.total'));

            $i++;
            //Ca Brut tiltle
            $sheet->mergeCells('B' . $i . ':C' . $i);
            ExcelUtilities::setFont($sheet->getCell('B' . $i), 10, true);
            $sheet->setCellValue('B' . $i, $this->translator->trans('hour_bu_hour_employee.ca_brut_ttc'));
            $startCell = 'D';
            $caBrutTotalUser = 0;
            foreach ($employeResult['ca_brut_ttc'] as $caBrut) {
                if ($scheduleType == 0) {
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $caBrut);
                    $startCell++;
                    $caBrutTotalUser = $caBrutTotalUser + $caBrut;
                    $caBrutTotal = $caBrutTotal + $caBrut;
                } else {
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $caBrut[0]);
                    $startCell++;
                    $caBrutTotalUser = $caBrutTotalUser + $caBrut[0];
                    $caBrutTotal = $caBrutTotal + $caBrut[0];

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $caBrut[1]);
                    $startCell++;
                    $caBrutTotalUser = $caBrutTotalUser + $caBrut[1];
                    $caBrutTotal = $caBrutTotal + $caBrut[1];

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $caBrut[2]);
                    $startCell++;
                    $caBrutTotalUser = $caBrutTotalUser + $caBrut[2];
                    $caBrutTotal = $caBrutTotal + $caBrut[2];

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $caBrut[3]);
                    $startCell++;
                    $caBrutTotalUser = $caBrutTotalUser + $caBrut[3];
                    $caBrutTotal = $caBrutTotal + $caBrut[3];
                }
            }
            //Total
            ExcelUtilities::setFont($sheet->getCell($startCell.$i), 10, true);
            $sheet->setCellValue($startCell.$i, number_format($caBrutTotalUser,'2','.',''));

            $i++;
            //Ca Net HTVA tiltle
            $sheet->mergeCells('B' . $i . ':C' . $i);
            ExcelUtilities::setFont($sheet->getCell('B' . $i), 10, true);
            $sheet->setCellValue('B' . $i, $this->translator->trans('hour_bu_hour_employee.ca_net_htva'));
            $startCell = 'D';
            $caNetHTVATotalUser = 0;
            foreach ($employeResult['ca_net_htva'] as $caNetHtva) {
                if ($scheduleType == 0) {
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $caNetHtva);
                    $startCell++;
                    $caNetHTVATotalUser = $caNetHTVATotalUser + $caNetHtva;
                    $caNetHTVATotal = $caNetHTVATotal + $caNetHtva;
                } else {
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $caNetHtva[0]);
                    $startCell++;
                    $caNetHTVATotalUser = $caNetHTVATotalUser + $caNetHtva[0];
                    $caNetHTVATotal = $caNetHTVATotal + $caNetHtva[0];

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $caNetHtva[1]);
                    $startCell++;
                    $caNetHTVATotalUser = $caNetHTVATotalUser + $caNetHtva[1];
                    $caNetHTVATotal = $caNetHTVATotal + $caNetHtva[1];

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $caNetHtva[2]);
                    $startCell++;
                    $caNetHTVATotalUser = $caNetHTVATotalUser + $caNetHtva[2];
                    $caNetHTVATotal = $caNetHTVATotal + $caNetHtva[2];

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $caNetHtva[3]);
                    $startCell++;
                    $caNetHTVATotalUser = $caNetHTVATotalUser + $caNetHtva[3];
                    $caNetHTVATotal = $caNetHTVATotal + $caNetHtva[3];
                }
            }
            //Total
            ExcelUtilities::setFont($sheet->getCell($startCell.$i), 10, true);
            $sheet->setCellValue($startCell.$i, number_format($caNetHTVATotalUser,'2','.',''));

            $i++;
            //Ticket count tiltle
            $sheet->mergeCells('B' . $i . ':C' . $i);
            ExcelUtilities::setFont($sheet->getCell('B' . $i), 10, true);
            $sheet->setCellValue('B' . $i, $this->translator->trans('hour_bu_hour_employee.ticket_count'));
            $startCell = 'D';
            $TicketCountTotalUser = 0;
            foreach ($employeResult['ticket_count'] as $ticketCount) {
                if ($scheduleType == 0) {
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $ticketCount);
                    $startCell++;
                    $TicketCountTotalUser = $TicketCountTotalUser + $ticketCount;
                    $TicketCountTotal = $TicketCountTotal + $ticketCount;
                } else {
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $ticketCount[0]);
                    $startCell++;
                    $TicketCountTotalUser = $TicketCountTotalUser + $ticketCount[0];
                    $TicketCountTotal = $TicketCountTotal + $ticketCount[0];

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $ticketCount[1]);
                    $startCell++;
                    $TicketCountTotalUser = $TicketCountTotalUser + $ticketCount[1];
                    $TicketCountTotal = $TicketCountTotal + $ticketCount[1];

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $ticketCount[2]);
                    $startCell++;
                    $TicketCountTotalUser = $TicketCountTotalUser + $ticketCount[2];
                    $TicketCountTotal = $TicketCountTotal + $ticketCount[2];

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $ticketCount[3]);
                    $startCell++;
                    $TicketCountTotalUser = $TicketCountTotalUser + $ticketCount[3];
                    $TicketCountTotal = $TicketCountTotal + $ticketCount[3];
                }
            }
            //Total
            ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
            $sheet->setCellValue($startCell . $i, number_format($TicketCountTotalUser, '2', '.', ''));

            $i++;
            //Item qty tiltle
            $sheet->mergeCells('B' . $i . ':C' . $i);
            ExcelUtilities::setFont($sheet->getCell('B' . $i), 10, true);
            $sheet->setCellValue('B' . $i, $this->translator->trans('hour_bu_hour_employee.item_qty'));
            $startCell = 'D';
            $itemQtyTotalUser = 0;
            foreach ($employeResult['item_qty'] as $itemQty) {
                if ($scheduleType == 0) {
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $itemQty);
                    $startCell++;
                    $itemQtyTotalUser = $itemQtyTotalUser + $itemQty;
                    $itemQtyTotal = $itemQtyTotal + $itemQty;
                } else {
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $itemQty[0]);
                    $startCell++;
                    $itemQtyTotalUser = $itemQtyTotalUser + $itemQty[0];
                    $itemQtyTotal = $itemQtyTotal + $itemQty[0];

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $itemQty[1]);
                    $startCell++;
                    $itemQtyTotalUser = $itemQtyTotalUser + $itemQty[1];
                    $itemQtyTotal = $itemQtyTotal + $itemQty[1];

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $itemQty[2]);
                    $startCell++;
                    $itemQtyTotalUser = $itemQtyTotalUser + $itemQty[2];
                    $itemQtyTotal = $itemQtyTotal + $itemQty[2];

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $itemQty[3]);
                    $startCell++;
                    $itemQtyTotalUser = $itemQtyTotalUser + $itemQty[3];
                    $itemQtyTotal = $itemQtyTotal + $itemQty[3];
                }
            }
            //Total
            ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
            $sheet->setCellValue($startCell . $i, number_format($itemQtyTotalUser, '2', '.', ''));

            $i++;
            //Tm Brut tiltle
            $sheet->mergeCells('B' . $i . ':C' . $i);
            ExcelUtilities::setFont($sheet->getCell('B' . $i), 10, true);
            $sheet->setCellValue('B' . $i, $this->translator->trans('hour_bu_hour_employee.tm_brut'));
            $startCell = 'D';
            foreach ($employeResult['tm_brut'] as $tmBrut) {
                if ($scheduleType == 0) {
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $tmBrut);
                    $startCell++;
                } else {
                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $tmBrut[0]);
                    $startCell++;

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $tmBrut[1]);
                    $startCell++;

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $tmBrut[2]);
                    $startCell++;

                    //Heure
                    ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                    $sheet->setCellValue($startCell . $i, $tmBrut[3]);
                    $startCell++;
                }
            }
            //Total
            ExcelUtilities::setFont($sheet->getCell($startCell.$i), 10, true);
            $sheet->setCellValue($startCell.$i, number_format($caBrutTotalUser/$TicketCountTotalUser,'2','.',''));

            $i++;

        }
        $colorTotal = 'F66F33';
        $colorTotalOne = '7fc1e4';
        $sheet->mergeCells('B' . $i . ':C' . $i);
        ExcelUtilities::setFont($sheet->getCell('B' . $i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell("B" . $i), $colorTotal);
        $sheet->setCellValue('B' . $i, $this->translator->trans('hour_bu_hour_employee.totals'));

        $startCell = 'D';
        foreach ($result['totals_results']['ca_brut_ttc'] as $h => $data) {
            //Heure
            ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
            ExcelUtilities::setBackgroundColor($sheet->getCell($startCell . $i), $colorTotalOne);
            ExcelUtilities::setCellAlignment($sheet->getCell($startCell . $i), $alignmentH);
            $sheet->setCellValue($startCell . $i, $h . ':00');
            $startCell++;

            if ($scheduleType == 1) {
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell . $i), $colorTotalOne);
                ExcelUtilities::setCellAlignment($sheet->getCell($startCell . $i), $alignmentH);
                $sheet->setCellValue($startCell . $i, $h . ':15');
                $startCell++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell . $i), $colorTotalOne);
                ExcelUtilities::setCellAlignment($sheet->getCell($startCell . $i), $alignmentH);
                $sheet->setCellValue($startCell . $i, $h . ':30');
                $startCell++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                ExcelUtilities::setBackgroundColor($sheet->getCell($startCell . $i), $colorTotalOne);
                ExcelUtilities::setCellAlignment($sheet->getCell($startCell . $i), $alignmentH);
                $sheet->setCellValue($startCell . $i, $h . ':45');
                $startCell++;

            }
        }
        //Total
        ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
        ExcelUtilities::setBackgroundColor($sheet->getCell($startCell . $i), $colorTotalOne);
        $sheet->setCellValue($startCell . $i, $this->translator->trans('keyword.total'));

        $i++;
        //Ca Brut tiltle
        $sheet->mergeCells('B' . $i . ':C' . $i);
        ExcelUtilities::setFont($sheet->getCell('B' . $i), 10, true);
        $sheet->setCellValue('B' . $i, $this->translator->trans('hour_bu_hour_employee.ca_brut_ttc'));
        $startCell = 'D';
        foreach ($result['totals_results']['ca_brut_ttc'] as $caBrut) {
            if ($scheduleType == 0) {
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $caBrut);
                $startCell++;
            } else {
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $caBrut[0]);
                $startCell++;

                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $caBrut[1]);
                $startCell++;

                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $caBrut[2]);
                $startCell++;

                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $caBrut[3]);
                $startCell++;
            }
        }
        //Total
        ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
        $sheet->setCellValue($startCell . $i, number_format($caBrutTotal, '2', '.', ''));

        $i++;
        //Ca net Htva tiltle
        $sheet->mergeCells('B' . $i . ':C' . $i);
        ExcelUtilities::setFont($sheet->getCell('B' . $i), 10, true);
        $sheet->setCellValue('B' . $i, $this->translator->trans('hour_bu_hour_employee.ca_net_htva'));
        $startCell = 'D';
        foreach ($result['totals_results']['ca_net_htva'] as $caNetHtva) {
            if ($scheduleType == 0) {
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $caNetHtva);
                $startCell++;
            } else {
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $caNetHtva[0]);
                $startCell++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $caNetHtva[1]);
                $startCell++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $caNetHtva[2]);
                $startCell++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $caNetHtva[3]);
                $startCell++;
            }
        }
        //Total
        ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
        $sheet->setCellValue($startCell . $i, number_format($caNetHTVATotal, '2', '.', ''));

        $i++;
        //Ticket Count tiltle
        $sheet->mergeCells('B' . $i . ':C' . $i);
        ExcelUtilities::setFont($sheet->getCell('B' . $i), 10, true);
        $sheet->setCellValue('B' . $i, $this->translator->trans('hour_bu_hour_employee.ticket_count'));
        $startCell = 'D';
        foreach ($result['totals_results']['ticket_count'] as $ticketCount) {
            if ($scheduleType == 0) {
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $ticketCount);
                $startCell++;
            } else {
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $ticketCount[0]);
                $startCell++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $ticketCount[1]);
                $startCell++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $ticketCount[2]);
                $startCell++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $ticketCount[3]);
                $startCell++;
            }
        }
        //Total
        ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
        $sheet->setCellValue($startCell . $i, number_format($TicketCountTotal, '2', '.', ''));

        $i++;
        //item Qty tiltle
        $sheet->mergeCells('B' . $i . ':C' . $i);
        ExcelUtilities::setFont($sheet->getCell('B' . $i), 10, true);
        $sheet->setCellValue('B' . $i, $this->translator->trans('hour_bu_hour_employee.item_qty'));
        $startCell = 'D';
        foreach ($result['totals_results']['item_qty'] as $itemQty) {
            if ($scheduleType == 0) {
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $itemQty);
                $startCell++;
            } else {
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $itemQty[0]);
                $startCell++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $itemQty[1]);
                $startCell++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $itemQty[2]);
                $startCell++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $itemQty[3]);
                $startCell++;
            }
        }
        //Total
        ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
        $sheet->setCellValue($startCell . $i, number_format($itemQtyTotal, '2', '.', ''));

        $i++;
        //tm Brut tiltle
        $sheet->mergeCells('B' . $i . ':C' . $i);
        ExcelUtilities::setFont($sheet->getCell('B' . $i), 10, true);
        $sheet->setCellValue('B' . $i, $this->translator->trans('hour_bu_hour_employee.tm_brut'));
        $startCell = 'D';
        foreach ($result['totals_results']['tm_brut'] as $tmBrut) {
            if ($scheduleType == 0) {
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $tmBrut);
                $startCell++;
            } else {
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $tmBrut[0]);
                $startCell++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $tmBrut[1]);
                $startCell++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $tmBrut[2]);
                $startCell++;
                //Heure
                ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
                $sheet->setCellValue($startCell . $i, $tmBrut[3]);
                $startCell++;
            }
        }
        //Total
        ExcelUtilities::setFont($sheet->getCell($startCell . $i), 10, true);
        $sheet->setCellValue($startCell . $i, number_format($caBrutTotal / $TicketCountTotal, '2', '.', ''));


        $filename = $this->translator->trans('report.sales.hour_by_hour_employee.title') . date('dmY_His') . ".xls";
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
