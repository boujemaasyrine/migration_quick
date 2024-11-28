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

class ProductPurchasedMvmtServiceTest extends WebTestCase
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

    public function testCreateMvmtEntryForTicketLine()
    {
        // Find ticket line
        $plu = 'P172';
        try {
            $line = $this->em->getRepository('Financial:TicketLine')
                ->createQueryBuilder('ticketLine')
                ->where('ticketLine.plu = :plu')
                ->setParameter('plu', $plu)
                ->andWhere('ticketLine.mvmtRecorded = :mvmtRecorded')
                ->setParameter('mvmtRecorded', false)
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult();
            $this->assertTrue(true, 'Launching mvmt creation for line :'.$line->getId());
            $this->container->get('product_purchased_mvmt.service')
                ->createMvmtEntryForTicketLine($line);
            $this->em->flush();
            $mvmts = $this->em->getRepository('Merchandise:ProductPurchasedMvmt')
                ->createQueryBuilder('productPurchasedMvmt')
                ->where('productPurchasedMvmt.type = :soldType')
                ->setParameter('soldType', ProductPurchasedMvmt::SOLD_TYPE)
                ->andWhere('productPurchasedMvmt.sourceId = :lineId')
                ->setParameter('lineId', $line->getId())
                ->getQuery()
                ->getResult();
            $this->assertTrue(count($mvmts) > 0, 'Assert if mvmts was inserted.');
        } catch (\Exception $e) {
            $this->assertTrue(false, 'No existing ticket line for  plu : '.$plu.'.'.$e->getMessage());
        }
    }
}
