<?php
/**
 * Created by PhpStorm.
 * User: zbessassi
 * Date: 21/01/2019
 * Time: 15:17
 */

namespace AppBundle\Financial\Service;

use AppBundle\Financial\Entity\CashboxCheckRestaurantContainer;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Merchandise\Service\RestaurantService;
use Doctrine\ORM\EntityManager;

class CashboxCheckRestaurantContainerService
{

    /**
     * @var EntityManager $em
     */
    private $em;
    /**
     * @var RestaurantService $restaurantService
     */
    private $restaurantService;


    /**
     * CashboxCheckRestaurantContainerService constructor.
     * @param EntityManager $em
     * @param RestaurantService $restaurantService
     */
    public function __construct(EntityManager $em, RestaurantService $restaurantService)
    {
        $this->em = $em;
        $this->restaurantService = $restaurantService;
    }

    public function calculateTheoricalTotal(CashboxCheckRestaurantContainer $ccqc, $electronic = null)
    {
        if (is_bool($electronic)) {
            $electronic = $electronic ? 'TRUE' : 'FALSE';
            $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
            $ticketPaymentIds = implode(',', $this->getIdsFromArrayCollection($ccqc->getTicketPayments()));
            $theoricalTotal = 0;
            if (!empty($ticketPaymentIds)) {
                $asv = Ticket::ABONDON_STATUS_VALUE;
                $csv = Ticket::CANCEL_STATUS_VALUE;
                $crID = $currentRestaurant->getId();
                $conn = $this->em->getConnection();
                $sql = "select 
            sum(CASE when (t.status!=:ABONDON_STATUS_VALUE and t.status!=:CANCEL_STATUS_VALUE ) then tp.amount
                     when(t.status=:CANCEL_STATUS_VALUE and t.counted_canceled=true ) then -1*ABS(tp.amount)
                     END
             ) AS theoricalTotal 
            from  ticket_payment tp INNER JOIN ticket t ON tp.ticket_id=t.id 
              where tp.id in (" . $ticketPaymentIds . ")
               and tp.electronic =:electronic
               and t.origin_restaurant_id=:origin_restaurant_id";
                $stm = $conn->prepare($sql);
                $stm->bindParam('ABONDON_STATUS_VALUE', $asv);
                $stm->bindParam('CANCEL_STATUS_VALUE', $csv);
                $stm->bindParam('electronic', $electronic);
                $stm->bindParam('origin_restaurant_id', $crID);
                $stm->execute();
                $theoricalTotal = $stm->fetchColumn();
            }
        }

        return is_numeric($theoricalTotal) ? $theoricalTotal : 0;
    }

    /**
     *
     * @param $ac
     * @return array
     */
    private function getIdsFromArrayCollection($ac)
    {
        $ids = array();
        if (!$ac->isEmpty()) {
            foreach ($ac as $o) {
                $ids[] = $o->getId();
            }
        }
        return $ids;
    }

}