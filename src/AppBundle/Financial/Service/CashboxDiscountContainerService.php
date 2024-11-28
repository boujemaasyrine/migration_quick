<?php
/**
 * Created by PhpStorm.
 * User: zbessassi
 * Date: 23/01/2019
 * Time: 09:20
 */

namespace AppBundle\Financial\Service;

use AppBundle\Financial\Entity\CashboxDiscountContainer;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Merchandise\Service\RestaurantService;
use Doctrine\ORM\EntityManager;

class CashboxDiscountContainerService
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
     * @param CashboxDiscountContainer $cdc
     * @param bool $ticketLineTmp
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function calculateTheoricalTotal(CashboxDiscountContainer $cdc, $ticketLineTmp = false)
    {

        if ($ticketLineTmp) {
            $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
            $ticketLineIds = implode(',', $this->getIdsFromArrayCollection($cdc->getTicketLines()));
            $theoricalTotal = 0;
            if (!empty($ticketLineIds)) {
                $asv = Ticket::ABONDON_STATUS_VALUE;
                $csv = Ticket::CANCEL_STATUS_VALUE;
                $crID = $currentRestaurant->getId();
                $conn = $this->em->getConnection();
                $sql = "select 
            sum(tl.discount_ttc) AS theoricalTotal 
            from  ticket_line tl INNER JOIN ticket t ON tl.ticket_id=t.id 
              where tl.id in (" . $ticketLineIds . ")
               and t.origin_restaurant_id=:origin_restaurant_id
               and tl.origin_restaurant_id=:origin_restaurant_id
               and t.status!=:ABONDON_STATUS_VALUE 
               and t.status!=:CANCEL_STATUS_VALUE
               ";
                $stm = $conn->prepare($sql);
                $stm->bindParam('ABONDON_STATUS_VALUE', $asv);
                $stm->bindParam('CANCEL_STATUS_VALUE', $csv);
                $stm->bindParam('origin_restaurant_id', $crID);
                $stm->execute();
                $theoricalTotal = $stm->fetchColumn();
            }

            return is_numeric($theoricalTotal) ? round(abs($theoricalTotal), 2) : 0;

        } else {
            $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
            $asv = Ticket::ABONDON_STATUS_VALUE;
            $csv = Ticket::CANCEL_STATUS_VALUE;
            $crID = $currentRestaurant->getId();
            $cdcId = $cdc->getId();
            $conn = $this->em->getConnection();
            $sql = "select 
            sum(tl.discount_ttc) AS theoricalTotal 
            from  ticket_line tl INNER JOIN ticket t ON tl.ticket_id=t.id 
               and t.origin_restaurant_id=:origin_restaurant_id
               and tl.origin_restaurant_id=:origin_restaurant_id
               and tl.discount_container_id=:discountId
               and t.status!=:ABONDON_STATUS_VALUE 
               and t.status!=:CANCEL_STATUS_VALUE
               ";
            $stm = $conn->prepare($sql);
            $stm->bindParam('ABONDON_STATUS_VALUE', $asv);
            $stm->bindParam('CANCEL_STATUS_VALUE', $csv);
            $stm->bindParam('origin_restaurant_id', $crID);
            $stm->bindParam('discountId', $cdcId);
            $stm->execute();
            $theoricalTotal = $stm->fetchColumn();

            return is_numeric($theoricalTotal) ? round(abs($theoricalTotal), 2) : 0;
        }

    }


    /**
     * Cette fonction permet de calculer la totale amount de l'entité passer en paramètre
     * @param CashboxDiscountContainer $cdc
     * @param bool $ticketLineTmp
     * @return float|int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function calculateTotalAmount(CashboxDiscountContainer $cdc, $ticketLineTmp = false)
    {
        if ($ticketLineTmp) {
            $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
            $ticketLineIds = implode(',', $this->getIdsFromArrayCollection($cdc->getTicketLines()));
            $total = 0;
            if (!empty($ticketLineIds)) {
                $asv = Ticket::ABONDON_STATUS_VALUE;
                $csv = Ticket::CANCEL_STATUS_VALUE;
                $crID = $currentRestaurant->getId();
                $conn = $this->em->getConnection();
                $sql = "select 
            sum(CASE when (t.status!=:ABONDON_STATUS_VALUE and t.status!=:CANCEL_STATUS_VALUE ) then tl.discount_ttc
                     when(t.status=:CANCEL_STATUS_VALUE and t.counted_canceled=true ) then -1*ABS(tl.discount_ttc)
                     END
             ) AS total 
            from  ticket_line tl INNER JOIN ticket t ON tl.ticket_id=t.id 
              where tl.id in (" . $ticketLineIds . ")
               and t.origin_restaurant_id=:origin_restaurant_id
               and tl.origin_restaurant_id=:origin_restaurant_id";
                $stm = $conn->prepare($sql);
                $stm->bindParam('ABONDON_STATUS_VALUE', $asv);
                $stm->bindParam('CANCEL_STATUS_VALUE', $csv);
                $stm->bindParam('origin_restaurant_id', $crID);
                $stm->execute();
                $total = $stm->fetchColumn();
            }

            return is_numeric($total) ? round(abs($total), 2) : 0;

        } else {
            $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
            $asv = Ticket::ABONDON_STATUS_VALUE;
            $csv = Ticket::CANCEL_STATUS_VALUE;
            $crID = $currentRestaurant->getId();
            $cdcId = $cdc->getId();
            $conn = $this->em->getConnection();
            $sql = "select 
            sum(CASE when (t.status!=:ABONDON_STATUS_VALUE and t.status!=:CANCEL_STATUS_VALUE ) then tl.discount_ttc
                     when(t.status=:CANCEL_STATUS_VALUE and t.counted_canceled=true ) then -1*ABS(tl.discount_ttc)
                     END
             ) AS total 
            from  ticket_line tl INNER JOIN ticket t ON tl.ticket_id=t.id 
              where t.origin_restaurant_id=:origin_restaurant_id
               and tl.origin_restaurant_id=:origin_restaurant_id
               and tl.discount_container_id=:discountId";
            $stm = $conn->prepare($sql);
            $stm->bindParam('ABONDON_STATUS_VALUE', $asv);
            $stm->bindParam('CANCEL_STATUS_VALUE', $csv);
            $stm->bindParam('origin_restaurant_id', $crID);
            $stm->bindParam('discountId', $cdcId);
            $stm->execute();
            $total = $stm->fetchColumn();

            return is_numeric($total) ? round(abs($total), 2) : 0;
        }
    }


    /**
     * @param CashboxDiscountContainer $cdc
     * @param  bool $ticketLineTmp
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function listDiscountLabes(CashboxDiscountContainer $cdc, $ticketLineTmp = false)
    {
        if ($ticketLineTmp) {
            $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
            $crID = $currentRestaurant->getId();
            $ticketLineIds = implode(',', $this->getIdsFromArrayCollection($cdc->getTicketLines()));
            $labels = [];
            if (!empty($ticketLineIds)) {
                $conn = $this->em->getConnection();
                $sql = "select tl.discount_label from ticket_line tl where 
                tl.origin_restaurant_id =:origin_restaurant_id and tl.id in (" . $ticketLineIds . ")";
                $stm = $conn->prepare($sql);
                $stm->bindParam('origin_restaurant_id', $crID);
                $stm->execute();
                $result = $stm->fetchAll();

                if (!empty($result)) {
                    foreach ($result as $r) {
                        if (!in_array($r['discount_label'], $labels))
                            $labels[] = $r['discount_label'];
                    }
                }

            }
            return $labels;
        } else {
            $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
            $crID = $currentRestaurant->getId();
            $cdcId = $cdc->getId();
            $conn = $this->em->getConnection();
            $sql = "select tl.discount_label from ticket_line tl where tl.origin_restaurant_id =:origin_restaurant_id and tl.discount_container_id=:discountId";
            $stm = $conn->prepare($sql);
            $stm->bindParam('origin_restaurant_id', $crID);
            $stm->bindParam('discountId', $cdcId);
            $stm->execute();
            $result = $stm->fetchAll();
            $labels = [];
            if (!empty($result)) {
                foreach ($result as $r) {
                    if (!in_array($r['discount_label'], $labels))
                        $labels[] = $r['discount_label'];
                }
            }

            return $labels;
        }

    }


    /**
     * @param CashboxDiscountContainer $cdc
     * @param bool $ticketLineTmp
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function generateAmountByLabels(CashboxDiscountContainer $cdc, $ticketLineTmp = false)
    {
        if ($ticketLineTmp) {
            $ticketLineIds = implode(',', $this->getIdsFromArrayCollection($cdc->getTicketLines()));
            $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
            $amountByLabelArray = [];
            if (!empty($ticketLineIds)) {
                $crID = $currentRestaurant->getId();
                $conn = $this->em->getConnection();
                $sql = "select tl.discount_label,sum(tl.discount_ttc) as amount
        from ticket_line tl where tl.origin_restaurant_id =:origin_restaurant_id 
        and tl.id in (" . $ticketLineIds . ")
        group BY tl.discount_label";
                $stm = $conn->prepare($sql);
                $stm->bindParam('origin_restaurant_id', $crID);
                $stm->execute();
                $result = $stm->fetchAll();
                if (!empty($result)) {
                    foreach ($result as $r) {
                        $amountByLabelArray[$r['discount_label']] = $r['amount'];
                    }
                }
            }
            return $amountByLabelArray;
        } else {
            $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
            $crID = $currentRestaurant->getId();
            $cdcId = $cdc->getId();
            $conn = $this->em->getConnection();
            $sql = "select tl.discount_label,sum(tl.discount_ttc) as amount
        from ticket_line tl where tl.origin_restaurant_id =:origin_restaurant_id 
        and tl.discount_container_id=:discountId
        group BY tl.discount_label";
            $stm = $conn->prepare($sql);
            $stm->bindParam('origin_restaurant_id', $crID);
            $stm->bindParam('discountId', $cdcId);
            $stm->execute();
            $result = $stm->fetchAll();

            $amountByLabelArray = [];
            if (!empty($result)) {
                foreach ($result as $r) {
                    $amountByLabelArray[$r['discount_label']] = $r['amount'];
                }
            }
            return $amountByLabelArray;
        }

    }

    /**
     * @param CashboxDiscountContainer $cdc
     * @param bool $ticketLineTmp
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function generateQuantityByLabels(CashboxDiscountContainer $cdc, $ticketLineTmp = false)
    {
        if ($ticketLineTmp) {

            $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
            $crID = $currentRestaurant->getId();
            $ticketLineIds = implode(',', $this->getIdsFromArrayCollection($cdc->getTicketLines()));
            $quantityByLabelArray = [];
            if (!empty($ticketLineIds)) {
                $conn = $this->em->getConnection();
                $sql = "select tl.discount_label
        from ticket_line tl left join ticket t on tl.ticket_id=t.id where tl.origin_restaurant_id =:origin_restaurant_id 
        and t.origin_restaurant_id =:origin_restaurant_id 
        and tl.id in(".$ticketLineIds.")
        group BY t.id,tl.discount_label";
                $stm = $conn->prepare($sql);
                $stm->bindParam('origin_restaurant_id', $crID);
                $stm->execute();
                $result = $stm->fetchAll();
                $quantityByLabelArray = [];
                if (!empty($result)) {
                    foreach ($result as $r) {
                        $quantityByLabelArray[$r['discount_label']] = 0;
                    }
                    foreach ($result as $r) {
                        $quantityByLabelArray[$r['discount_label']] += 1;
                    }
                }
            }
            return $quantityByLabelArray;
        } else {
            $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
            $crID = $currentRestaurant->getId();
            $cdcId = $cdc->getId();
            $conn = $this->em->getConnection();
            $sql = "select tl.discount_label
        from ticket_line tl left join ticket t on tl.ticket_id=t.id where tl.origin_restaurant_id =:origin_restaurant_id 
        and t.origin_restaurant_id =:origin_restaurant_id 
        and tl.discount_container_id=:discountId
        group BY t.id,tl.discount_label";
            $stm = $conn->prepare($sql);
            $stm->bindParam('origin_restaurant_id', $crID);
            $stm->bindParam('discountId', $cdcId);
            $stm->execute();
            $result = $stm->fetchAll();
            $quantityByLabelArray = [];
            if (!empty($result)) {
                foreach ($result as $r) {
                    $quantityByLabelArray[$r['discount_label']] = 0;
                }
                foreach ($result as $r) {
                    $quantityByLabelArray[$r['discount_label']] += 1;
                }
            }
            return $quantityByLabelArray;
        }

    }

    /**
     * @param CashboxDiscountContainer $cdc
     * @param  bool ticketLineTmp
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function generateTotalQuantity(CashboxDiscountContainer $cdc,$ticketLineTmp=false)
    {
        if($ticketLineTmp){
            $quantityByLabelsArray = $cdc->getQuantityByLabelsArray();
            $total = 0;
            if (empty($quantityByLabelsArray)) {
                $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
                $ticketLineIds = implode(',', $this->getIdsFromArrayCollection($cdc->getTicketLines()));
                if (!empty($ticketLineIds)) {
                $crID = $currentRestaurant->getId();
                $conn = $this->em->getConnection();
                $sql = "select tl.discount_label
        from ticket_line tl left join ticket t on tl.ticket_id=t.id where tl.origin_restaurant_id =:origin_restaurant_id 
        and t.origin_restaurant_id =:origin_restaurant_id 
        and tl.id in (".$ticketLineIds.")
        group BY t.id,tl.discount_label";
                $stm = $conn->prepare($sql);
                $stm->bindParam('origin_restaurant_id', $crID);
                $stm->execute();
                $result = $stm->fetchAll();
                if (!empty($result)) {
                    $total = count($result);
                }
                return $total;}
            } else {
                foreach ($quantityByLabelsArray as $q) {
                    $total += (int)$q;
                }
                return $total;
            }

        }else{
            $quantityByLabelsArray = $cdc->getQuantityByLabelsArray();
            $total = 0;
            if (empty($quantityByLabelsArray)) {
                $currentRestaurant = $this->restaurantService->getCurrentRestaurant();
                $crID = $currentRestaurant->getId();
                $cdcId = $cdc->getId();
                $conn = $this->em->getConnection();
                $sql = "select tl.discount_label
        from ticket_line tl left join ticket t on tl.ticket_id=t.id where tl.origin_restaurant_id =:origin_restaurant_id 
        and t.origin_restaurant_id =:origin_restaurant_id 
        and tl.discount_container_id=:discountId
        group BY t.id,tl.discount_label";
                $stm = $conn->prepare($sql);
                $stm->bindParam('origin_restaurant_id', $crID);
                $stm->bindParam('discountId', $cdcId);
                $stm->execute();
                $result = $stm->fetchAll();
                if (!empty($result)) {
                    $total = count($result);
                }
                return $total;
            } else {
                foreach ($quantityByLabelsArray as $q) {
                    $total += (int)$q;
                }
                return $total;
            }
        }


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