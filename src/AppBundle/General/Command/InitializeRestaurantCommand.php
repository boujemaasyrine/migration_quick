<?php

namespace AppBundle\General\Command;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Entity\UnitNeedProducts;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 07/06/2016
 * Time: 14:15
 */
class InitializeRestaurantCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var Logger
     */
    private $logger;
    protected function configure()
    {
        $this->setName('saas:restaurant:initialize')->setDefinition(
            []
        )->setDescription('Initialize restaurant.')
            ->addArgument('restaurantId', InputArgument::REQUIRED)
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('monolog.logger.app_commands');

    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $restaurantId = intval($input->getArgument('restaurantId'));
        $restaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);

        if($restaurant == null){
            echo 'Restaurant not found with id: '.$restaurantId;
            $this->logger->addAlert('Restaurant not found with id: '.$restaurantId, ['InitRestaurantCommand']);
            return;
        }
        else {
            $arguments = new ArrayInput(array('restaurantId' => $restaurantId));
            try {
                $output->writeln('Load parameters =>');
                $initCommand = $this->getApplication()->find('quick:parameters:import');
                $initCommand->run($arguments, $output);
                $output->writeln('<info> => Load parameters finished with success <= </info>');
                $output->writeln('');
            } catch (\Exception $e) {
                $output->writeln("<error>Exception during Load parameters " . $e->getMessage() . "</error>");
            }
            try {
                $output->writeln('Init procedures =>');
                $initCommand = $this->getApplication()->find('quick:procedure:init');
                $initCommand->run($arguments, $output);
                $output->writeln('<info> => Init procedures finished with success <= </info>');
                $output->writeln('');
            } catch (\Exception $e) {
                $output->writeln("<error>Exception during Init procedures " . $e->getMessage() . "</error>");
            }
            try {
                $output->writeln('Init products eligibility =>');
                $initCommand = $this->getApplication()->find('saas:init:restaurant:eligibility');
                $initCommand->run($arguments, $output);
                $output->writeln('<info> => Init products eligibility finished with success <= </info>');
                $output->writeln('');
            } catch (\Exception $e) {
                $output->writeln("<error>Exception during Init products eligibility " . $e->getMessage() . "</error>");
            }
        }
    }
}
