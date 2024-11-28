<?php

namespace AppBundle\Supervision\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 22/02/2016
 * Time: 10:58
 */
class ImportAllDataCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:allData:import')->setDefinition(
            []
        )->setDescription('Import all categories.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $restaurantCommand = $this->getApplication()->find('quick:restaurants:import');
        $restaurantCommand->run($input, $output);

        $categoriesCommand = $this->getApplication()->find('quick:categories:import');
        $categoriesCommand->run($input, $output);

        $divisionsCommand = $this->getApplication()->find('quick:divisions:import');
        $divisionsCommand->run($input, $output);

        $suppliersCommand = $this->getApplication()->find('quick:suppliers:import');
        $suppliersCommand->run($input, $output);

        $inventoryItemsCommand = $this->getApplication()->find('quick:inventoryItems:import');
        $inventoryItemsCommand->run($input, $output);

        $soldingCanalCommand = $this->getApplication()->find('quick:soldingCanal:import');
        $soldingCanalCommand->run($input, $output);

        $recipesCommand = $this->getApplication()->find('quick:recipes:import');
        $recipesCommand->run($input, $output);

        $productSoldCommand = $this->getApplication()->find('quick:productSold:import');
        $productSoldCommand->run($input, $output);

        $productSoldCommand = $this->getApplication()->find('quick:sync:eligible:products');
        $productSoldCommand->run($input, $output);

        $productSoldCommand = $this->getApplication()->find('quick:duplicate:plu:delete:item');
        $productSoldCommand->run($input, $output);

        $productSoldCommand = $this->getApplication()->find('quick:actions:import');
        $productSoldCommand->run($input, $output);

        $productSoldCommand = $this->getApplication()->find('quick:roles:import');
        $productSoldCommand->run($input, $output);

        $productSoldCommand = $this->getApplication()->find('quick:roles:right:initialize');
        $productSoldCommand->run($input, $output);

        $productSoldCommand = $this->getApplication()->find('quick:payment:method:import');
        $productSoldCommand->run($input, $output);

        $output->writeln('All data imported with success !');
    }
}
