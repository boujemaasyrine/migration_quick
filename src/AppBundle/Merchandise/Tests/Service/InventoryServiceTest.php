<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 23/02/2016
 * Time: 08:37
 */

namespace AppBundle\Merchandise\Tests\Service;

use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\OrderLine;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\SupplierPlanning;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use AppBundle\Merchandise\Entity\Supplier;

class InventoryServiceTest extends WebTestCase
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

    public function testCreateZeroInventoryLineForProduct()
    {

        $em = $this->container->get("doctrine.orm.entity_manager");

        $product = $em->getRepository("Merchandise:ProductPurchased")
            ->findOneBy([]);
        if ($product) {
            $inv = $this->container->get('inventory.service')
                ->createZeroInventoryLineForProduct($product);
            $this->container->get('doctrine.orm.entity_manager')
                ->flush();

            $this->assertTrue(!is_null($inv->getId()));
        } else {
            echo 'No existing product purchased.';
            $this->assertTrue(false);
        }
    }
}
