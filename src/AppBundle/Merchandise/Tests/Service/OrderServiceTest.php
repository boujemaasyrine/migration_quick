<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 23/02/2016
 * Time: 08:37
 */

namespace AppBundle\Merchandise\Tests\Service;

use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\OrderLine;
use AppBundle\Merchandise\Entity\SupplierPlanning;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use AppBundle\Merchandise\Entity\Supplier;

class OrderServiceTest extends WebTestCase
{

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->container = static::$kernel->getContainer();
    }

    public function testCreateOrder()
    {

        $em = $this->container->get("doctrine.orm.entity_manager");

        $employee = $em->getRepository("Staff:Employee")
            ->findAll();
        $employee = $employee[0];
        $numOrder = $this->container->get("order.service")->getLastOrderNum() + 1;

        $supplier = $em->getRepository("Merchandise:Supplier")->findAll()[0];
        $planning = $supplier->getPlannings()[0];
        $ca = $em->getRepository("Merchandise:CaPrev")->findBy(
            array(
                "date" => $planning->nextOrderDate(),
            )
        );

        if (count($ca) == 0) {
            $ca = 1;
        } else {
            $ca = $ca[0]->getCa();
        }

        $order = new Order();
        $order->setEmployee($employee)
            ->setNumOrder($numOrder)
            ->setSupplier($supplier)
            ->setDateOrder($planning->nextOrderDate())
            ->setDateDelivery($planning->nextDeliveryDate());

        $orderLine = new OrderLine();
        $orderLine->setProduct($em->getRepository("Merchandise:ProductPurchased")->findOneBy([]));
        $orderLine->setQty(50);

        $arrayCollection = new ArrayCollection();
        $arrayCollection->add($orderLine);

        $this->container->get('order.service')->createOrder($order, $arrayCollection);

        try {
            $order = $em->getRepository("Merchandise:Order")->findOneBy(array('numOrder' => $numOrder));

            if ($order === null) {
                $this->fail("No order was created");
            }

            $this->assertTrue(true);

            $em->remove($order);
            $em->flush();
        } catch (\Exception $e) {
            $this->fail("No order was created");
        }
    }
}
