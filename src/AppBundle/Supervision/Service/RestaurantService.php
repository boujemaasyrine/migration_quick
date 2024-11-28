<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/03/2016
 * Time: 17:07
 */

namespace AppBundle\Supervision\Service;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Security\Entity\Role;
use AppBundle\Security\Entity\User;
use AppBundle\Staff\Entity\Employee;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\ToolBox\Service\CommandLauncher;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RestaurantService
{
    private $em;

    /**
     * @var SyncCmdCreateEntryService
     */
    private $syncCmdCreateEntryService;

    /**
     * @var CommandLauncher
     */
    private $commandLauncher;

    public function __construct(
        EntityManager $entityManager,
        SyncCmdCreateEntryService $syncCmdCreateEntryService,
        CommandLauncher $commandLauncher
    ) {
        $this->em = $entityManager;
        $this->syncCmdCreateEntryService = $syncCmdCreateEntryService;
        $this->commandLauncher = $commandLauncher;
    }

    public function saveRestaurant(Restaurant $restaurant, $type)
    {
        $this->em->persist($restaurant);

        if ($type == 'plus') { // if it's a new restaurant creation
            $this->em->flush();
            if($restaurant->getActive())
            {
                $this->commandLauncher->execute('saas:restaurant:initialize '.$restaurant->getId());
            }
        }

        $this->em->flush();
    }

    public function deleteRestaurant(Restaurant $restaurant)
    {
        $restaurant->setActive(false);
        $this->em->flush();
        return true;
    }

    public function getRestaurants($criteria, $order, $limit, $offset)
    {
        $restaurants = $this->em->getRepository(Restaurant::class)->getRestaurantOrdered(
            $criteria,
            $order,
            $offset,
            $limit
        );

        return $this->serializeRestaurants($restaurants);
    }

    public function serializeRestaurants($restaurants)
    {
        $result = [];
        foreach ($restaurants as $r) {
            /**
             * @var Restaurant $r
             */
            $suppliers = $r->getSuppliers()->toArray();
            $restaurantSuppliers = [];
            foreach ($suppliers as $supplier) {
                /**
                 * @var Supplier $supplier
                 */
                $restaurantSuppliers[] = $supplier->getName();
            }

            $result[] = array(
                'code' => $r->getCode(),
                'name' => $r->getName(),
                'email' => $r->getEmail(),
                'manager' => $r->getManager(),
                'adress' => $r->getAddress(),
                'phone' => $r->getPhone(),
                'type' => $r->getType(),
                'restaurantSuppliers' => implode(" \n", $restaurantSuppliers),
            );
        }

        return $result;
    }
}
