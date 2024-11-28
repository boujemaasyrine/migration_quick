<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 08/02/2018
 * Time: 16:04
 */

namespace AppBundle\Merchandise\Command;


use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\Supervision\Service\SyncCmdCreateEntryService;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateSoldProductsOnBoCommand extends ContainerAwareCommand
{
    /**
     * @var SyncCmdCreateEntryService
     */
    private $syncService;

    /**
     * @var EntityManager
     */
    private $em;

    protected function configure()
    {
        $this->setName('create:product:sold:bo')
            ->setDescription('create product sold for a given restaurant')
            ->addArgument('restaurantId',InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $restaurantId=$input->getArgument('restaurantId');
        $restaurant=$this->em->getRepository(Restaurant::class)->find($restaurantId);

        if(is_null($restaurant)){
            echo 'Restaurant with id '.$restaurantId.'not found';
            return;
        }

        else {

            $productPurchasedSupervision=$this->em->getRepository(ProductPurchasedSupervision::class)->findAll();
            $productSoldSupervision = $this->em->getRepository(ProductSoldSupervision::class)->findAll();
            echo '-->Start creating purchased products '."\n";
            foreach ($productPurchasedSupervision as $product){
                if(!$restaurant->getSupervisionProducts()->contains($product)){
                    $restaurant->addSupervisionProduct($product);
               }
               if(!$product->getRestaurants()->contains($restaurant)){
                   $product->addRestaurant($restaurant);
               }
               $this->syncService->createProductPurchasedEntry($product,true,$restaurant,true);
               $output->writeln("- Sync Cmd for purchased product [ ".$product->getName(). " ] created." );
            }

            echo 'End of  purchased products creation '."\n";



            echo '-->Start creating sold products '."\n";
            foreach ($productSoldSupervision as $product)
            {
                /**
                 * @var ProductSoldSupervision $product
                 */
                if(!$restaurant->getSupervisionProducts()->contains($product)){
                    $restaurant->addSupervisionProduct($product);
                }
                if(!$product->getRestaurants()->contains($restaurant)){
                    $product->addRestaurant($restaurant);
                }

                $this->syncService->createProductSoldEntry($product, true, true, $restaurant);
                $output->writeln("- Sync Cmd for sold product [ ".$product->getName(). " ] created." );
            }

            echo 'End of sold products creation '."\n";
        }
    }

    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {
        parent::initialize(
            $input,
            $output);

        $this->syncService=$this->getContainer()->get('sync.create.entry.service');
        $this->em=$this->getContainer()->get('doctrine.orm.entity_manager');

    }


}