<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 17/03/2016
 * Time: 16:40
 */

namespace AppBundle\Financial\Repository;

use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\Utilities;

class CashboxCountRepository extends \Doctrine\ORM\EntityRepository
{
    const EXCEPTION = "FILE SQL DOESN'T EXIST";

    public function getCashboxCountsOwner($filter, $sqlQueriesDir)
    {
        $sqlQueryFile = $sqlQueriesDir."/cashbox_counts_own_lines.sql";

        if (!file_exists($sqlQueryFile)) {
            throw new \Exception($this::EXCEPTION);
        }

        $sql = file_get_contents($sqlQueryFile);
        $ticketType = "invoice";
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $bankCards = $this->_em->getRepository('Financial:PaymentMethod')
            ->findBy(
                array("type" => PaymentMethod::BANK_CARD_TYPE)
            );
        $bankCardsIds = [];
        foreach ($bankCards as $bankCard) {
            $bankCardsIds[] = $bankCard->getValue()['id'];
        }
        $bankCardsIds = implode(',', $bankCardsIds);
        $D1 = $filter['startDate']->format('Y-m-d')." 00:00:00";
        $D2 = $filter['endDate']->format('Y-m-d')." 23:59:59";

        $conn = $this->_em->getConnection();
        $stm = $conn->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('D3', $D1);
        $stm->bindParam('D4', $D2);
        $stm->bindParam(':ticket_type', $ticketType);
        $stm->bindParam(':canceled', $canceled);
        $stm->bindParam(':cb_ids', $bankCardsIds);
        $stm->bindParam('origin_restaurant_id', $filter['currentRestaurantId']);
        $stm->execute();
        $data['lines'] = $stm->fetchAll();
        for($i = 0; $i < count($data['lines']); $i++)
        {
            $data['lines'][$i]['ca_theoretical'] += ($data['lines'][$i]['mt_theoretical'] + $data['lines'][$i]['d_theoretical']);
            $data['lines'][$i]['cr_real'] = ($data['lines'][$i]['cr_real'] + $data['lines'][$i]['cre_real']);
        }


        $sqlQueryFile2 = $sqlQueriesDir."/cashbox_counts_own_total.sql";

        if (!file_exists($sqlQueryFile2)) {
            throw new \Exception($this::EXCEPTION);
        }

        $sql2 = file_get_contents($sqlQueryFile2);
        $ticketType = 'invoice';
        $stm = $conn->prepare($sql2);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('D3', $D1);
        $stm->bindParam('D4', $D2);
        $stm->bindParam(':ticket_type', $ticketType);
        $stm->bindParam(':cb_ids', $codeCB);
        $stm->bindParam("origin_restaurant_id", $filter["currentRestaurantId"]);
        $stm->execute();
        $data['total'] = $stm->fetchAll();
        for($i = 0; $i < count($data['total']); $i++)
        {
            $data['total'][$i]['ca_theoretical'] += ($data['total'][$i]['mt_theoretical'] + $data['total'][$i]['d_theoretical']);
            $data['total'][$i]['cr_real'] = ($data['total'][$i]['cr_real'] + $data['total'][$i]['cre_real']);

        }

        return $data;
    }

    public function getCashboxCountsCashier($filter, $sqlQueriesDir)
    {
        $sqlQueryFile = $sqlQueriesDir."/cashbox_counts_cashier_lines.sql";

        if (!file_exists($sqlQueryFile)) {
            throw new \Exception($this::EXCEPTION);
        }

        $sql = file_get_contents($sqlQueryFile);

        $D1 = $filter['startDate']->format('Y-m-d')." 00:00:00";
        $D2 = $filter['endDate']->format('Y-m-d')." 23:59:59";
        $canceled = Ticket::CANCEL_STATUS_VALUE;
        $bankCards = $this->_em->getRepository('Financial:PaymentMethod')
            ->findBy(
                array("type" => PaymentMethod::BANK_CARD_TYPE)
            );
        $bankCardsIds = [];
        foreach ($bankCards as $bankCard) {
            $bankCardsIds[] = $bankCard->getValue()['id'];
        }
        $bankCardsIds = implode(',', $bankCardsIds);
        $ticketType = 'invoice';
        $conn = $this->_em->getConnection();
        $stm = $conn->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('D5', $D1);
        $stm->bindParam('D6', $D2);
        $stm->bindParam(':ticket_type', $ticketType);
        $stm->bindParam(':canceled', $canceled);
        $stm->bindParam(':cb_ids', $bankCardsIds);
        $stm->bindParam("origin_restaurant_id", $filter["currentRestaurantId"]);
        $stm->execute();
        $data['lines'] = $stm->fetchAll();
        // Add total meal tickets and discounts to the corresponding ca_theorical
        for($i = 0; $i < count($data['lines']); $i++)
        {
            $data['lines'][$i]['ca_theoretical'] += ($data['lines'][$i]['mt_theoretical'] + $data['lines'][$i]['d_theoretical']);
            $data['lines'][$i]['cr_real'] = ($data['lines'][$i]['cr_real'] + $data['lines'][$i]['cre_real']);
        }

        $sqlQueryFile2 = $sqlQueriesDir."/cashbox_counts_cashier_total.sql";

        if (!file_exists($sqlQueryFile2)) {
            throw new \Exception($this::EXCEPTION);
        }

        $sql2 = file_get_contents($sqlQueryFile2);

        $stm = $conn->prepare($sql2);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('D3', $D1);
        $stm->bindParam('D4', $D2);
        $stm->bindParam(':ticket_type', $ticketType);
        $stm->bindParam(':cb_ids', $bankCardsIds);
        $stm->bindParam("origin_restaurant_id", $filter["currentRestaurantId"]);
        $stm->execute();
        $data['total'] = $stm->fetchAll();
        //update totalTTC
        for($i = 0; $i < count($data['total']); $i++)
        {
            $data['total'][$i]['ca_theoretical'] += ($data['total'][$i]['mt_theoretical'] + $data['total'][$i]['d_theoretical']);
            $data['total'][$i]['cr_real'] = ($data['total'][$i]['cr_real'] + $data['total'][$i]['cre_real']);

        }

        return $data;
    }

    public function getCashboxCountsAnomalies($filter, $sqlQueriesDir)
    {
        $sqlQueryFile = $sqlQueriesDir."/cashbox_counts_anomalies_lines.sql";

        if (!file_exists($sqlQueryFile)) {
            throw new \Exception($this::EXCEPTION);
        }

        $sql = file_get_contents($sqlQueryFile);

        $D1 = $filter['startDate']->format('Y-m-d')." 00:00:00";
        $D2 = $filter['endDate']->format('Y-m-d')." 23:59:59";

        $diffCashbox = $filter['diffCashbox'];
        $annulations = $filter['annulations'];
        $corrections = $filter['corrections'];
        $especes = $filter['especes'];
        $titreRestaurant = $filter['titreRestaurant'];
        $abandons = $filter['abandons'];

        $conn = $this->_em->getConnection();

        $stm = $conn->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);

        $stm->bindParam('diffC1', $diffCashbox['firstInput']);
        $stm->bindParam('diffC2', $diffCashbox['secondInput']);

        $stm->bindParam('annulations1', $annulations['firstInput']);
        $stm->bindParam('annulations2', $annulations['secondInput']);

        $stm->bindParam('corrections1', $corrections['firstInput']);
        $stm->bindParam('corrections2', $corrections['secondInput']);

        $stm->bindParam('abandons1', $abandons['firstInput']);
        $stm->bindParam('abandons2', $abandons['secondInput']);

        $stm->bindParam('especes1', $especes['firstInput']);
        $stm->bindParam('especes2', $especes['secondInput']);

        $stm->bindParam('titreRestaurant1', $titreRestaurant['firstInput']);
        $stm->bindParam('titreRestaurant2', $titreRestaurant['secondInput']);

        $stm->bindParam("origin_restaurant_id", $filter["currentRestaurantId"]);

        $stm->execute();
        $data['lines'] = $stm->fetchAll();

        $sqlQueryFile2 = $sqlQueriesDir."/cashbox_counts_anomalies_total.sql";

        if (!file_exists($sqlQueryFile2)) {
            throw new \Exception($this::EXCEPTION);
        }

        $sql2 = file_get_contents($sqlQueryFile2);

        $stm = $conn->prepare($sql2);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam("origin_restaurant_id", $filter["currentRestaurantId"]);

        $stm->execute();
        $data['total'] = $stm->fetchAll();

        return $data;
    }

    public function getCashboxCountsAnomaliesTotal($filter, $sqlQueriesDir)
    {
        $D1 = $filter['startDate']->format('Y-m-d')." 00:00:00";
        $D2 = $filter['endDate']->format('Y-m-d')." 23:59:59";

        $conn = $this->_em->getConnection();

        $sqlQueryFile2 = $sqlQueriesDir."/cashbox_counts_anomalies_total.sql";

        if (!file_exists($sqlQueryFile2)) {
            throw new \Exception("");
        }

        $sql2 = file_get_contents($sqlQueryFile2);

        $stm = $conn->prepare($sql2);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('origin_restaurant_id', $filter["currentRestaurantId"]);
        $stm->execute();
        $data['total'] = $stm->fetchAll();

        return $data;
    }

    public function getCashboxCountsAnomaliesMaxPercent($filter, $sqlQueriesDir)
    {
        $D1 = $filter['startDate']->format('Y-m-d')." 00:00:00";
        $D2 = $filter['endDate']->format('Y-m-d')." 23:59:59";

        $conn = $this->_em->getConnection();

        $sqlQueryFile2 = $sqlQueriesDir."/cashbox_counts_max_percent.sql";

        if (!file_exists($sqlQueryFile2)) {
            throw new \Exception("");
        }

        $sql2 = file_get_contents($sqlQueryFile2);

        $stm = $conn->prepare($sql2);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->bindParam('origin_restaurant_id', $filter["currentRestaurantId"]);

        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }

    public function findCashboxCountsBydate(
        \DateTime $dateTime,
        $restaurant = null
    ) {

        $startDate = clone $dateTime;
        $startDate->setTime(0, 0, 0);

        $endDate = clone $startDate;
        $endDate->add(new \DateInterval('P1D'));

        $qb = $this->_em->getRepository('Financial:CashboxCount')
            ->createQueryBuilder('cashboxCount')
            ->where('cashboxCount.date >= :startDate')
            ->setParameter('startDate', $startDate)
            ->andWhere('cashboxCount.date < :endDate')
            ->setParameter('endDate', $endDate);
        if (isset($restaurant)) {
            $qb->andWhere('cashboxCount.originRestaurant=:restaurant')
                ->setParameter('restaurant', $restaurant);
        };

        return $qb->getQuery()->getResult();
    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @param null $restaurant
     * @return array
     * @throws \Exception
     */
    public function findCashboxCountsInDatesInterval(\DateTime $from,\DateTime $to, $restaurant = null) {

        $startDate = clone $from;
        $startDate->setTime(0, 0, 0);

        $endDate = clone $to;
        $endDate->add(new \DateInterval('P1D'));

        $qb = $this->_em->getRepository('Financial:CashboxCount')
            ->createQueryBuilder('cashboxCount')
            ->where('cashboxCount.date >= :startDate')
            ->setParameter('startDate', $startDate)
            ->andWhere('cashboxCount.date < :endDate')
            ->setParameter('endDate', $endDate);
        if (isset($restaurant)) {
            $qb->andWhere('cashboxCount.originRestaurant=:restaurant')
                ->setParameter('restaurant', $restaurant);
        };

        return $qb->getQuery()->getResult();
    }

    public function getRealCashAmount($id)
    {
        $qb = $this->_em->getRepository('Financial:CashboxCount')
            ->createQueryBuilder('c')
            ->select('cc.totalAmount')
            ->leftJoin('c.cashContainer', 'cc')
            ->where('c.id = :id')
            ->setParameter('id', $id);

        return $qb->getQuery()->getResult();
    }

    public function getBankCardTicket($id)
    {
        $qb = $this->_em->getRepository('Financial:TicketPayment')
            ->createQueryBuilder('tp')
            ->leftJoin('tp.bankCardContainer', 'bc')
            ->leftJoin('bc.cashbox', 'c')
            ->leftJoin('c.smallChest', 'sc')
            ->where('sc.id = :id')
            ->setParameter('id', $id);

        return $qb->getQuery()->getResult();
    }

    public function getNotCounted($restaurant = null)
    {
        $qb = $this->createQueryBuilder('cc')
            ->where('cc.counted = false');
        if (isset($restaurant)) {
            $qb->andWhere("cc.originRestaurant = :restaurant")
                ->setParameter('restaurant', $restaurant);
        }

        return $qb->getQuery()->getResult();
    }


    public function getCashboxCountsFilteredOrdered(
        $criteria,
        $order,
        $offset,
        $limit,
        $search = null,
        $onlyList = false
    ) {

        $queryBuilder = $this->createQueryBuilder('cc');
        $queryBuilder->leftJoin('cc.owner', 'o');
        $queryBuilder->leftJoin('cc.cashier', 'c');
        $queryBuilder->leftJoin('cc.smallChest', 'sc');

        if (!$onlyList) {
            $qb1 = clone $queryBuilder;
            if (isset($criteria['restaurant'])) {
                $qb1->andWhere(":restaurant = cc.originRestaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
            $total = $qb1->select('count(cc)')
                ->getQuery()->getSingleScalarResult();
        }

        //filtering
        if ($criteria !== null && is_array($criteria) && count($criteria) > 0) {
            if (Utilities::exist($criteria, 'cashbox_counts_search[source')) {
                $queryBuilder->andWhere("upper(e.source) = upper(:source) ")
                    ->setParameter(
                        "source",
                        $criteria['cashbox_counts_search[source']
                    );
            }
            if (Utilities::exist($criteria, 'cashbox_counts_search[startDate')
                && Utilities::exist(
                    $criteria,
                    'cashbox_counts_search[endDate'
                )
            ) {
                $startDate = \DateTime::createFromFormat(
                    'j/m/Y',
                    $criteria['cashbox_counts_search[startDate']
                );
                $startDate = $startDate->format('Y-m-d');

                $endDate = \DateTime::createFromFormat(
                    'j/m/Y',
                    $criteria['cashbox_counts_search[endDate']
                );
                $endDate = $endDate->format('Y-m-d');

                $from = new \DateTime($startDate." 00:00:00");
                $to = new \DateTime($endDate." 23:59:59");
                $queryBuilder
                    ->andWhere('cc.date BETWEEN :from AND :to ')
                    ->setParameter('from', $from)
                    ->setParameter('to', $to);
            }
            if (Utilities::exist($criteria, 'cashbox_counts_search[owner')) {
                $queryBuilder->andWhere("cc.owner = :owner ")
                    ->setParameter(
                        "owner",
                        $criteria['cashbox_counts_search[owner']
                    );
            }
            if (Utilities::exist($criteria, 'cashbox_counts_search[cashier')) {
                $queryBuilder->andWhere("cc.cashier = :cashier ")
                    ->setParameter(
                        "cashier",
                        $criteria['cashbox_counts_search[cashier']
                    );
            }
            if (isset($criteria['restaurant'])) {
                $queryBuilder->andWhere(":restaurant = cc.originRestaurant")
                    ->setParameter("restaurant", $criteria['restaurant']);
            }
        }

        if ($search) {
            $queryBuilder
                ->andWhere(
                    '(LOWER(o.firstName) like :search or LOWER(o.lastName) like :search
                or LOWER(c.firstName) like :search or LOWER(c.lastName) like :search
                or STRING(cc.realCaCounted) like :search
                or STRING(cc.theoricalCa) like :search
                or STRING(cc.realCaCounted - cc.theoricalCa) like :search
                or DATE_STRING(cc.date) like :search
                or DATE_STRING(cc.createdAt) like :search
                )'
                )
                ->setParameter('search', '%'.strtolower($search).'%');
        }

        if (!$onlyList) {
            $qb2 = clone $queryBuilder;
            $filteredTotal = $qb2->select('count(cc)')
                ->getQuery()->getSingleScalarResult();
        }

        $queryBuilder->select(
            'cc.id, o.firstName as oFirstName, o.lastName as oLastName, c.firstName as cFirstName, c.lastName as cLastName, cc.date, cc.createdAt, cc.theoricalCa, cc.realCaCounted, cc.eft, cc.counted, sc.id as scId'
        );

        //ordering
        if ($order !== null && is_array($order) && count($order) > 0) {
            if (Utilities::exist($order, 'col')) {
                if (Utilities::exist($order, 'dir')) {
                    $orderDir = $order['dir'];
                } else {
                    $orderDir = 'asc';
                }
                switch ($order['col']) {
                    case 'date':
                        $queryBuilder->orderBy('cc.date', $orderDir);
                        break;
                    case 'owner':
                        $queryBuilder->orderBy('o.firstName', $orderDir);
                        break;
                    case 'cashier':
                        $queryBuilder->orderBy('c.firstName', $orderDir);
                        break;
                    case 'realCaCounted':
                        $queryBuilder->orderBy('cc.realCaCounted', $orderDir);
                        break;
                    case 'theoricalCa':
                        $queryBuilder->orderBy('cc.theoricalCa', $orderDir);
                        break;
                    case 'createdAt':
                        $queryBuilder->orderBy('cc.createdAt', $orderDir);
                        break;
                    case 'difference':
                        $queryBuilder
                            ->addSelect('cc.realCaCounted - cc.theoricalCa AS HIDDEN difference')
                            ->orderBy('difference', $orderDir);
                        break;
                }
            }
        }
        if ($limit !== null) {
            $queryBuilder->setMaxResults(intval($limit));
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult(intval($offset));
        }
        if ($onlyList) {
            return $queryBuilder->getQuery()->getResult();
        } else {
            return array(
                'list'     => $queryBuilder->getQuery()->getResult(),
                'total'    => $total,
                'filtered' => $filteredTotal,
            );
        }
    }


    public function findCashboxCountsByDateAndCashier(
        \DateTime $date,
        Restaurant $restaurant,
        $cashiers
    )
    {

        $closureDate = clone $date;

        $closureDate->setTime(0, 0, 0);

        $qb = $this->_em->getRepository(CashboxCount::class)
            ->createQueryBuilder('cashboxCount')
            ->where('cashboxCount.date= :closureDate')
            ->setParameter('closureDate', $closureDate)
            ->andWhere('cashboxCount.originRestaurant= :restaurant')
            ->setParameter('restaurant',$restaurant);

        if (is_array($cashiers)) {

            $qb->andWhere('cashboxCount.cashier in (:cashiers)')
                ->setParameter('cashiers',$cashiers);

        }

        return $qb->getQuery()->getResult();

    }


}
