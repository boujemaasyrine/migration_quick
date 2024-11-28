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
use AppBundle\Merchandise\Entity\ProductPurchasedMvmt;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\SupplierPlanning;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use AppBundle\Merchandise\Entity\Supplier;

class ProductServiceTest extends WebTestCase
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntityManager
     */
    private $em;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->container = static::$kernel->getContainer();
        $this->em = $this->container->get("doctrine.orm.entity_manager");
    }

    public function testGetStockForProductsAtDate()
    {
        try {
            $products = $this->em->getRepository('Merchandise:ProductPurchased')
                ->createQueryBuilder('productPurchased')
                ->select('productPurchased.id')
                ->getQuery()
                ->getScalarResult();
            $products = array_map('current', $products);
            $result = $this->container->get('product.service')
                ->getStockForProductsAtDate(new \DateTime('now'), $products);
            fwrite(STDERR, print_r($result, true));
            $this->assertTrue(count($result) > 0, 'Assert if we have a result.');
        } catch (\Exception $e) {
            $this->assertTrue(false, 'No existing result.'.$e->getMessage());
        }
    }

    public function testGetCoefForPP()
    {
        try {
            $product = $this->em->getRepository('Merchandise:ProductPurchased')
                ->createQueryBuilder('productPurchased')
                ->where('productPurchased.externalId = :code')
                ->setParameter('code', '1112')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult();
            $result = $this->container->get('product.service')
                ->getCoefForPP(
                    $product,
                    date_create_from_format('d/m/Y', '15/08/2016'),
                    date_create_from_format('d/m/Y', '21/08/2016'),
                    1000
                );
            fwrite(STDERR, print_r($product, true));
            $this->assertTrue(count($result) > 0, 'Assert if we have a result.');
        } catch (\Exception $e) {
            $this->assertTrue(false, 'No existing result.'.$e->getMessage());
        }
    }
}
