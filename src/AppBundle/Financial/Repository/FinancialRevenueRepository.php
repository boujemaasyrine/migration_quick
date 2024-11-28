<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 17/03/2016
 * Time: 16:40
 */

namespace AppBundle\Financial\Repository;

use AppBundle\Financial\Entity\FinancialRevenue;
use AppBundle\Merchandise\Entity\Restaurant;

class FinancialRevenueRepository extends \Doctrine\ORM\EntityRepository
{
    public function getFinancialRevenue($filter)
    {

        $conn = $this->_em->getConnection();
        $D1 = $filter['beginDate']; //  transform to D1-1j
        $D2 = $filter['endDate'];

        $sql = "SELECT
                SUM(FN.amount) as amountRevenue,
                EXTRACT(DOW FROM FN.date) as Entryday
                FROM public.financial_revenue FN
                where FN.date >= :D1 and FN.date <= :D2 GROUP BY Entryday";

        // bind

        $stm = $conn->prepare($sql);
        $stm->bindParam('D1', $D1);
        $stm->bindParam('D2', $D2);
        $stm->execute();
        $data = $stm->fetchAll();

        return $data;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $restaurant
     * @param $onlyTotals
     * @return FinancialRevenue[]
     */
    public function getFinancialRevenueBetweenDates(
        $startDate,
        $endDate,
        Restaurant $restaurant = null,
        $onlyTotals = false
    ) {

        $query = $this->createQueryBuilder("r")
            ->where("r.date >= :startDate")
            ->andWhere("r.date <= :endDate")
            ->setParameter("startDate", $startDate)
            ->setParameter("endDate", $endDate);

        if ($restaurant != null) {
            $query->andWhere('r.originRestaurant = :restaurant')
                ->setParameter('restaurant', $restaurant);
        }

        if ($onlyTotals) {
            $query->select(
                'SUM(r.netHT) as caNetHT, SUM(r.netTTC) as caNetTTC, SUM(r.brutTTC) as caBrutTTC,
                SUM(r.br) as br, SUM(r.discount) as discount'
            );

            return $query->getQuery()->getResult();
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return FinancialRevenue[]
     */
    public function getFinancialRevenuesBetweenDates($startDate, $endDate, $currentRestaurantId)
    {

        $query = $this->createQueryBuilder("r")
            ->select('r')
            ->where("r.date >= :startDate")
            ->andWhere("r.date <= :endDate")
            ->andWhere("r.originRestaurant = :currentRestaurantId")
            ->setParameter("startDate", $startDate)
            ->setParameter("endDate", $endDate)
            ->setParameter("currentRestaurantId", $currentRestaurantId);
        //$query->groupBy('r.date');
        //$query->groupBy('r.id');


        return $query->getQuery()->getResult();
    }

    /*************************
     *
     * Supervision Section
     *****************************/

    public function getSupervisionByDateAndRestaurants($date, $restaurants)
    {
        $queryBuilder = $this->createQueryBuilder('fr');

        $queryBuilder->andWhere('fr.date = :date')
            ->setParameter('date', $date);

        if ($restaurants and count($restaurants) > 0) {
            $restaurantsIds = array();

            foreach ($restaurants as $restaurant) {
                $restaurantsIds[] = $restaurant->getId();
            }
            $queryBuilder
                ->andWhere('fr.originRestaurant IN (:restaurants)')
                ->setParameter('restaurants', $restaurantsIds);
        }

        $queryBuilder->select(
            'SUM(fr.netHT) as netHT, SUM(fr.brutHT) as brutHT, SUM(fr.brutTTC) as brutTTC,
                SUM(fr.br) as br, SUM(fr.discount) as discount'
        );

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $onlyTotals
     * @return FinancialRevenue[]
     */
    public function getSupervisionFinancialRevenueBetweenDates($startDate, $endDate, $restaurants, $onlyTotals = false)
    {

        $query = $this->createQueryBuilder("r")
            ->where("r.date >= :startDate")
            ->andWhere("r.date <= :endDate")
            ->setParameter("startDate", $startDate)
            ->setParameter("endDate", $endDate);

        if ($restaurants and count($restaurants) > 0) {
            $restaurantsIds = array();

            foreach ($restaurants as $restaurant) {
                $restaurantsIds[] = $restaurant->getId();
            }
            $query
                ->andWhere('r.originRestaurant IN (:restaurants)')
                ->setParameter('restaurants', $restaurantsIds);
        }

        if ($onlyTotals) {
            $query->select(
                'SUM(r.netHT) as caNetHT, SUM(r.netTTC) as caNetTTC, SUM(r.brutTTC) as caBrutTTC,
                SUM(r.br) as br, SUM(r.discount) as discount'
            );

            return $query->getQuery()->getResult();
        }

        return $query->getQuery()->getResult();
    }
}
