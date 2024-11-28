<?php
/**
 * Created by PhpStorm.
 * User: zbessassi
 * Date: 16/01/2019
 * Time: 15:26
 */

namespace AppBundle\Financial\Service;

use AppBundle\Financial\Entity\CashboxCheckQuickContainer;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Merchandise\Service\RestaurantService;
use Doctrine\ORM\EntityManager;

class CashboxCheckQuickContainerService
{

    /**
     * @var EntityManager $em
     */
    private $em;
    /**
     * @var RestaurantService $restaurantService
     */
    private $restaurantService;


    public function __construct(EntityManager $em, RestaurantService $restaurantService)
    {
        $this->em = $em;
        $this->restaurantService = $restaurantService;
    }


    /**
     * Cette fonction permet de calculer la totale théorique de l'entité passer en paramètre
     * @param CashboxCheckQuickContainer $ccqc
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function calculateTheoricalTotal(CashboxCheckQuickContainer $ccqc)
    {
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
               and t.origin_restaurant_id=:origin_restaurant_id";
            $stm = $conn->prepare($sql);
            $stm->bindParam('ABONDON_STATUS_VALUE', $asv);
            $stm->bindParam('CANCEL_STATUS_VALUE', $csv);
            $stm->bindParam('origin_restaurant_id', $crID);
            $stm->execute();
            $theoricalTotal = $stm->fetchColumn();
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