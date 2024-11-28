<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 07/04/2016
 * Time: 11:17
 */

namespace AppBundle\Merchandise\Repository;

use Doctrine\ORM\EntityRepository;

class OrderHelpFixedCoefRepository extends EntityRepository
{
    public function deleteAll()
    {
        $sql = "DELETE FROM order_help_fixed_coef ";
        $stm = $this->_em->getConnection()->prepare($sql);
        $stm->execute();
    }

    public function deleteAllFromRestaurant($restaurantId)
    {
        $sql = "DELETE FROM order_help_fixed_coef WHERE origin_restaurant_id= :restaurantId";
        $stm = $this->_em->getConnection()->prepare($sql);
        $stm->bindParam("restaurantId", $restaurantId);
        $stm->execute();
    }
}
