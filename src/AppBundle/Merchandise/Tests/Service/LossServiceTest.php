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

class LossServiceTest extends WebTestCase
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

    public function testSaveLossSheet()
    {

        $em = $this->container->get("doctrine.orm.entity_manager");

        $employee = $em->getRepository("Staff:Employee")
            ->findAll();
        $employee = $employee[0];

        // get A transformer product sold
        /**
         * @var $transformedProduct ProductSold
         */
        $transformedProduct = $em->getRepository('Merchandise:ProductSold')->createQueryBuilder('productSold')
            ->leftJoin('productSold.recipes', 'recipes')
            ->where('productSold.productPurchased IS NULL')
            ->andWhere('productSold.recipes IS NOT NULL')
            ->getQuery()->getSingleResult();

        // get A non transformed product sold
        $nonTransformedProduct = $em->getRepository('Merchandise:ProductSold')->createQueryBuilder(
            'productSold'
        )->where('productSold.productPurchased IS NOT NULL')->getQuery()->getSingleResult();

        $lossSheet = new LossSheet();
        $lossSheet->setEmployee($employee);

        $transformedProductlossLine = new LossLine();
        $transformedProductlossLine->setProduct($transformedProduct)
            ->setFirstEntry(1)
            ->setSecondEntry(2)
            ->setThirdEntry(3);
        $lossSheet->addLossLine($transformedProduct);

        $nonTransformedProductLossLine = new LossLine();
        $nonTransformedProductLossLine->setProduct($nonTransformedProduct)
            ->setFirstEntry(1)
            ->setSecondEntry(2)
            ->setThirdEntry(3);
        $lossSheet->addLossLine($nonTransformedProduct);

        $transformedProductOldQty = $transformedProduct->getStockCurrentQty();
        // get recipes product
        $productPurchasedQties = [];
        //        foreach ($transformedProduct->getRecipes() as $recipe) {
        //
        //        }
        //
        //        $nonTransformedProductOldQty = $nonTransformedProduct->getStockCurrentQty();
        //
        //        $this->container->get('loss.service')->saveLossSheet($lossSheet);
        //
        //        try{
        //            $order = $em->getRepository("Merchandise:Order")->findOneBy(array('numOrder' => $numOrder));
        //
        //            if ($order === null ){
        //                $this->fail("No order was created");
        //            }
        //
        //            $this->assertTrue(true);
        //
        //            $em->remove($order);
        //            $em->flush();
        //
        //        }catch (\Exception $e){
        //            $this->fail("No order was created");
        //        }
    }
}
