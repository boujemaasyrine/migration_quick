<?php
namespace AppBundle\General\Command;

use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\Supervision\Service\SyncCmdCreateEntryService;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitRestaurantEligibilityCommand extends ContainerAwareCommand
{
    private $em;
    private $logger;
    private $syncCmdCreateEntryService;

    protected function configure()
    {
        $this->setName('saas:init:restaurant:eligibility')
            ->addArgument('restaurantId', InputArgument::REQUIRED)
            ->setDescription('Make the new created restaurants eligible to all active products.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->logger = $this->getContainer()->get('monolog.logger.synchro');
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->syncCmdCreateEntryService = $this->getContainer()->get('sync.create.entry.service');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $restaurantId = intval($input->getArgument('restaurantId'));
        $restaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);

        if ($restaurant == null) {
            echo 'Restaurant not found with id: '.$restaurantId;
            $this->logger->addAlert('Restaurant not found with id: '.$restaurantId, ['InitRestaurantEligibilityCommand']);
            return;
        } else {
            $i = 0;
           // Récupération des paramètres globaux
            $newAddedRestaurantCode = $this->getContainer()->getParameter('new_restaurant_code');
            $LastOpenedRestaurant = $this->getContainer()->getParameter('last_opened_restaurant_code');
            // Vérification spéciale pour les restaurants avec code spécifique
            if ($newAddedRestaurantCode == $restaurant->getCode()) {
                $LastOpenedRestaurant = $this->em->getRepository(Restaurant::class)->findOneBy(["code" => $LastOpenedRestaurant]);
            } else {
                $LastOpenedRestaurant = $this->em->getRepository(Restaurant::class)
                    ->createQueryBuilder('r')
                    ->where('r.id < :id')
                    ->andWhere('r.active = true')
                    ->andWhere('r.country = :country')
                    ->orderBy('r.id', 'DESC')
                    ->setParameter('id', $restaurantId)
                    ->setParameter('country', $restaurant->getCountry())
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
            }

            if (!$LastOpenedRestaurant) {
                echo "No last opened restaurant found. \n";
                $this->logger->addAlert('No last opened restaurant found.', ['InitRestaurantEligibilityCommand']);
                echo "Processing with all the active products. \n";
                $this->logger->addAlert('Processing with all the active products.', ['InitRestaurantEligibilityCommand']);

                $criteria = array(
                    "inventory_item_search[statusSearch]" => "active",
                );
                $productPurchasedSupervision = $this->em->getRepository(ProductPurchasedSupervision::class)
                    ->getSupervisonInventoryItemsOrdered($criteria, null, null, null, true);

                $criteria = array(
                    "product_sold_search[statusSearch]" => "1",
                );
                $productSoldSupervision = $this->em->getRepository(ProductSoldSupervision::class)
                    ->getProductsSoldOrdered($criteria, null, null, null, true);
            } else {
                $productPurchasedSupervision = $this->em->getRepository(ProductPurchasedSupervision::class)
                    ->getProductsPurchasedSupervisionByRestaurant($LastOpenedRestaurant);

                $productSoldSupervision = $this->em->getRepository(ProductSoldSupervision::class)
                    ->getProductsSoldSupervisionByRestaurant($LastOpenedRestaurant);
            }

            echo "Processing supervision purchased products...\n";
            $this->logger->addAlert("Processing supervision purchased products...\n", ['InitRestaurantEligibilityCommand']);

            foreach ($productPurchasedSupervision as $product) {
                if (!$restaurant->getSupervisionProducts()->contains($product)) {
                    $restaurant->addSupervisionProduct($product);
                    $product->addRestaurant($restaurant);
                    $this->syncCmdCreateEntryService->createProductPurchasedEntry($product, true, $restaurant, false);
                    $i++;
                    $this->flush($i);
                }
            }

            echo "Processing supervision purchased products done.\n";
            $this->logger->addAlert("Processing supervision purchased products done.\n", ['InitRestaurantEligibilityCommand']);

            echo "Processing supervision sold products...\n";
            $this->logger->addAlert("Processing supervision sold products...\n", ['InitRestaurantEligibilityCommand']);

            foreach ($productSoldSupervision as $product) {
                if (!$restaurant->getSupervisionProducts()->contains($product)) {
                    $restaurant->addSupervisionProduct($product);
                    $product->addRestaurant($restaurant);
                    $this->syncCmdCreateEntryService->createProductSoldEntry($product, true, false, $restaurant);
                    $i++;
                    $this->flush($i);
                }
            }

            echo "Processing supervision sold products done.\n";
            $this->logger->addAlert("Processing supervision sold products done.\n", ['InitRestaurantEligibilityCommand']);
            $this->em->flush();
            $this->em->clear();

            echo "Adding eligibility for restaurant with id $restaurantId is done with success.\n";
            $this->logger->addAlert("Adding eligibility for restaurant with id $restaurantId is done with success.\n", ['InitRestaurantEligibilityCommand']);
        }
    }

    private function flush($i)
    {
        if ($i % 100 === 0) {
            $this->em->flush();
        }
    }
}
