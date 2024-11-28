<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 31/05/2016
 * Time: 14:25
 */

namespace AppBundle\Supervision\Service;

use AppBundle\Merchandise\Entity\CaPrev;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;

class CaPrevService
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }


    /**
     * @param \DateTime $d
     * @param Restaurant $restaurant
     * @return float
     */
    public function getBudgetForRestaurant(\DateTime $d, Restaurant $restaurant)
    {

        $bud = $this->em->getRepository(CaPrev::class)->findOneBy(
            array(
                'date' => $d,
                'originRestaurant' => $restaurant,
            )
        );

        if ($bud) {
            return $bud->getCa();
        } else {
            return 0;
        }
    }
}
