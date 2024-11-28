<?php

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Entity\SheetModelLine;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateDefaultSheetModelCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('create:default:sheetmodel')
            ->setDescription(
                'Create a default sheet model thaht contains all active and to_inactive purchased products.'
            )
            ->addArgument('restaurantId', InputArgument::OPTIONAL);
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->logger = $this->getContainer()->get('monolog.logger.app_commands');

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentRestaurant=null;
        if ($input->hasArgument('restaurantId') && !empty($input->getArgument('restaurantId'))) {

            $restaurantId = $input->getArgument('restaurantId');

            /**
             * @var Restaurant $currentRestaurant
             */
            $currentRestaurant = $this->em->getRepository(Restaurant::class)
                ->find($restaurantId);

            if ($currentRestaurant == null) {

                $this->logger->addAlert(
                    'Restaurant not found with the Id '.$restaurantId,
                    ['create:default:sheetmodel']
                );
                $output->writeln('Restaurant not found with the Id '.$restaurantId);

                return;
            }
        }


        try {
            if (is_null($currentRestaurant)) {
                $restaurants = $this->em->getRepository(Restaurant::class)->getOpenedRestaurants();
                foreach ($restaurants as $restaurant) {
                    $productsIds = $this->getAllProductsForSheetModel($restaurant);
                    $this->createSheetModel($productsIds, $restaurant);
                    $this->logger->addAlert(
                        'Default Sheet model created for restaurant: '.$restaurant->getId(),
                        ['create:default:sheetmodel']
                    );
                }
            } else {
                $productsIds = $this->getAllProductsForSheetModel($currentRestaurant);
                $this->createSheetModel($productsIds, $currentRestaurant);
                $this->logger->addAlert(
                    'Default Sheet model created for restaurant: '.$restaurantId,
                    ['create:default:sheetmodel']
                );
            }

        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            $this->logger->addAlert($e->getMessage(), ['create:default:sheetmodel']);

            return;
        }

    }

    /**
     * @param Restaurant $restaurant
     * @return mixed
     */
    protected function getAllProductsForSheetModel(Restaurant $restaurant)
    {

        $qb = $this->em->createQueryBuilder();
        $qb->select(['products.id'])
            ->from('Merchandise:ProductCategories', 'productCategories')
            ->leftJoin('productCategories.products', 'products')
            ->where('products.status IN (:allowedStatus)')
            ->andWhere('products.originRestaurant= :restaurant')
            ->andWhere('products.primaryItem is null')
            ->setParameters(
                array(
                    'allowedStatus' => [ProductPurchased::ACTIVE, ProductPurchased::TO_INACTIVE],
                    'restaurant' => $restaurant,
                )
            );

        $qb->addOrderBy('productCategories.order', 'ASC')->addOrderBy('products.name', 'ASC');

        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * @param $productsIds
     * @param Restaurant $restaurant
     */
    protected function createSheetModel($productsIds, Restaurant $restaurant)
    {
        $date = new \DateTime('now');
        $sheetModel = new SheetModel();
        $sheetModel->setOriginRestaurant($restaurant);
        $sheetModel->setLabel("Default_".$date->format('M Y'));
        $sheetModel->setType(SheetModel::INVENTORY_MODEL);
        $order = 0;
        foreach ($productsIds as $productId) {
            $line = new SheetModelLine();
            $line->setOrderInSheet($order);
            $line->setProduct($this->em->getReference(Product::class, $productId));
            $sheetModel->addLine($line);
            $order++;
        }
        $this->em->persist($sheetModel);
        $this->em->flush();
    }
}
