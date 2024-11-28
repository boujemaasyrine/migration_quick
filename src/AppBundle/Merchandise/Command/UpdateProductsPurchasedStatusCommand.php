<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/02/2016
 * Time: 14:44
 */

namespace AppBundle\Merchandise\Command;

use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Service\InventoryService;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateProductsPurchasedStatusCommand extends ContainerAwareCommand
{

    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var InventoryService
     */
    private $inventoryService;


    protected function configure()
    {
        $this
            ->setName("update:products:purchased:status")
            ->addOption('restaurantId', 'r', InputOption::VALUE_OPTIONAL, 'The restaurant id.', '')
            ->setDescription("Update products purchased status.");
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->logger = $this->getContainer()->get('logger');
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->inventoryService = $this->getContainer()->get('inventory.service');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentRestaurant = null;
        if ($input->hasOption('restaurantId') && !empty($input->getOption('restaurantId'))) {
            $restaurantId = $input->getOption('restaurantId');
            $currentRestaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);
            if ($currentRestaurant == null) {
                $this->logger->addAlert('Restaurant not found with id: '.$restaurantId, ['quick:financial:revenue:import']);

                return;
            }
        }

        if($currentRestaurant == null)
        {
            $restaurants = $this->em->getRepository(Restaurant::class)->getOpenedRestaurants();
            foreach ($restaurants as $restaurant)
            {
                $this->updateItemsStatus($restaurant);
            }
        }
        else
        {
            $this->updateItemsStatus($currentRestaurant);
        }
    }

    public function updateItemsStatus(Restaurant $restaurant)
    {
        $products = $this->em->getRepository(ProductPurchased::class)
            ->findBy(
                [
                    'status' => ProductPurchased::TO_INACTIVE,
                    'deactivationDate' => new \DateTime('today'),
                    'originRestaurant' => $restaurant
                ]
            );
        $this->logger->addInfo(
            count($products).' Product purchased, found for the restaurant '.$restaurant->getCode().'. That need to be desactivated today.',
            ['UpdateProductsPurchasedStatusCommand']
        );

        if (count($products)) {
            foreach ($products as $product) {
                /**
                 * @var ProductPurchased $product
                 */
                $product->setStatus(ProductPurchased::INACTIVE);
                $product->getSupervisionProduct()->setStatus(ProductPurchased::INACTIVE);
                $this->inventoryService->createZeroInventoryLineForProduct($product, $restaurant);

            }
            $this->em->flush();
        }

        $this->logger->addInfo(
            'UpdateProductsPurchasedStatusCommand executed with success on restaurant '.$restaurant->getCode(),
            ['UpdateProductsPurchasedStatusCommand']
        );
    }
}
