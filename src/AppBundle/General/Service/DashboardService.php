<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/03/2016
 * Time: 09:39
 */

namespace AppBundle\General\Service;

use AppBundle\General\Entity\RemoteHistoric;
use AppBundle\General\Entity\SyncCmdQueue;
use AppBundle\General\Service\Download\Ping;
use AppBundle\Merchandise\Service\RestaurantService;
use Doctrine\ORM\EntityManager;

class DashboardService
{
    /**
     * @var EntityManager
     */
    private $em;
    private $restaurantService;

    /**
     * BOStatusService constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager, RestaurantService $restaurantService)
    {
        $this->em = $entityManager;
        $this->restaurantService = $restaurantService;
    }

    public function getLoss($filter)
    {

        $invLossVal = $this->em->getRepository('Merchandise:LossLine')->getFiltredLossLine($filter, true, false);
        $invLossVal = $invLossVal ? $invLossVal : 0;
        $result['invLossVal'] = $invLossVal;
        $arraySoldLossVal = $this->em->getRepository('Merchandise:LossLine')->getFiltredLossLineSold($filter, true, false);
        $soldLossVal = $arraySoldLossVal['lossvalorization'];
        $soldLossVal = $soldLossVal ? $soldLossVal : 0;
        $result['soldLossVal'] = $soldLossVal;

        return $result;
    }

    public function getTicketsAvg(\DateTime $date)
    {

        $filter['beginDate'] = $date->format('Y-m-d');
        $filter['endDate'] = $date->format('Y-m-d');

        $dateP = clone $date;
        $dateP->sub(new \DateInterval('P1Y'));
        $filterP['beginDate'] = $dateP->format('Y-m-d');
        $filterP['endDate'] = $dateP->format('Y-m-d');

        $detailsComp = $this->em->getRepository('Financial:Ticket')->getCaTicket(
            $filterP,
            $this->restaurantService->getCurrentRestaurant()->getId()
        );//fixed
        $detailsVoucherComp = $this->em->getRepository('Financial:Ticket')->getVoucherTicket(
            $filterP,
            $this->restaurantService->getCurrentRestaurant()->getId()
        );//fixed
        $caBrutHtComp = $detailsComp['data'][0]['cabrutht'] ? $detailsComp['data'][0]['cabrutht'] : 0;
        $brComp = $detailsVoucherComp['data'][0]['total_voucher_ht'] ? $detailsVoucherComp['data'][0]['total_voucher_ht'] : 0;
        $caNetHtComp = $caBrutHtComp - $brComp;

        $nbrTicketsComp = $this->em->getRepository('Financial:Ticket')->getTotalPerDay(
            $dateP,
            true,
            $this->restaurantService->getCurrentRestaurant()
        );//fixed
        $avgTicketComp = ($nbrTicketsComp > 0) ? $caNetHtComp / $nbrTicketsComp : 0;

        $details = $this->em->getRepository('Financial:Ticket')->getCaTicket(
            $filter,
            $this->restaurantService->getCurrentRestaurant()->getId()
        );//fixed
        $detailsVoucher = $this->em->getRepository('Financial:Ticket')->getVoucherTicket(
            $filter,
            $this->restaurantService->getCurrentRestaurant()->getId()
        );//fixed
        $result['br'] = $detailsVoucher['data'][0]['totalvoucher'] ? $detailsVoucher['data'][0]['totalvoucher'] : 0;
        $result['brHt'] = $detailsVoucher['data'][0]['total_voucher_ht'] ? $detailsVoucher['data'][0]['total_voucher_ht'] : 0;
        $result['caBrutHt'] = $details['data'][0]['cabrutht'] ? $details['data'][0]['cabrutht'] : 0;
        $result['caNetHt'] = $result['caBrutHt'] - $result['brHt'];

        $result['nbrTickets'] = $this->em->getRepository('Financial:Ticket')->getTotalPerDay(
            $date,
            true,
            $this->restaurantService->getCurrentRestaurant()
        );//fixed
        $result['nbrTicketsPerCentNOne'] = ($nbrTicketsComp > 0) ? ($result['nbrTickets'] - $nbrTicketsComp) / $nbrTicketsComp * 100 : null;
        $result['avgTicket'] = ($result['nbrTickets'] > 0) ? $result['caNetHt'] / $result['nbrTickets'] : 0;
        $result['avgTicketPerCentNOne'] = ($avgTicketComp > 0) ? ($result['avgTicket'] - $avgTicketComp) / $avgTicketComp * 100 : null;

        return $result;
    }

    public function getCaVsCaOne(\DateTime $date)
    {
        $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
        $dateP = clone $date;
        $dateP->setTimestamp($dateP->getTimestamp() - (86400 * 364));

        $caNetHt = $caNetNOne = 0;
        $fRev = $this->em->getRepository('Financial:FinancialRevenue')->findOneBy(array(
            'date' => $date,
            'originRestaurant' => $currentRestaurant
        ));
        if ($fRev) {
            $caNetHt = $fRev->getNetHT();
        }

        $fRevNOne = $this->em->getRepository('Financial:FinancialRevenue')->findOneBy(array(
            'date' => $dateP,
            'originRestaurant' => $currentRestaurant
        ));
        if ($fRevNOne) {
            $caNetNOne = $fRevNOne->getNetHT();
        }

        return [
            'caNetHt' => $caNetHt,
            'caNetNOne' => $caNetNOne,
            'day' => $date->format('d/m'),
        ];
    }
}
