<?php

namespace AppBundle\General\Command;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Entity\UnitNeedProducts;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 07/06/2016
 * Time: 14:15
 */
class InitializeBOCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:bo:initialize')->setDefinition(
            []
        )->setDescription('Initialize BO Quick.');
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
        try {
            $output->writeln('Load parameters =>');
            $initCommand = $this->getApplication()->find('quick:parameters:import');
            $initCommand->run($input, $output);
            $output->writeln('<info> => Load parameters finished with success <= </info>');
            $output->writeln('');
        } catch (\Exception $e) {
            $output->writeln("<error>Exception during Load parameters ".$e->getMessage()."</error>");
        }

        try {
            $output->writeln('Load currency =>');
            $initCommand = $this->getApplication()->find('quick:foreign:currency:import');
            $initCommand->run($input, $output);
            $output->writeln('<info> => Load currency finished with success <= </info>');
            $output->writeln('');
        } catch (\Exception $e) {
            $output->writeln("<error>Exception during Load currency ".$e->getMessage()."</error>");
        }

        try {
            $output->writeln('Load Holidays =>');
            $initCommand = $this->getApplication()->find('quick:holidays:init');
            $initCommand->run($input, $output);
            $output->writeln('<info> => Load Holidays finished with success <= </info>');
            $output->writeln('');
        } catch (\Exception $e) {
            $output->writeln("<error>Exception during Load Holidays ".$e->getMessage()."</error>");
        }

        try {
            $output->writeln('Restaurants import =>');
            $initCommand = $this->getApplication()->find('quick:download:generic');
            $arguments = new ArrayInput(array('type' => 'restaurants'));
            $initCommand->run($arguments, $output);
            $output->writeln('<info> => Restaurants import finished with success <= </info>');
            $output->writeln('');
        } catch (\Exception $e) {
            $output->writeln("<error>Exception during Restaurants import ".$e->getMessage()."</error>");
        }

        try {
            $output->writeln('Download actions =>');
            $initCommand = $this->getApplication()->find('quick:download:generic');
            $arguments = new ArrayInput(array('type' => 'actions'));
            $initCommand->run($arguments, $output);
            $output->writeln('<info> => Download actions finished with success <= </info>');
            $output->writeln('');
        } catch (\Exception $e) {
            $output->writeln("<error>Exception during Download actions ".$e->getMessage()."</error>");
        }

        try {
            $output->writeln('Download roles =>');
            $initCommand = $this->getApplication()->find('quick:download:generic');
            $arguments = new ArrayInput(array('type' => 'roles'));
            $initCommand->run($arguments, $output);
            $output->writeln('<info> => Download roles finished with success <= </info>');
            $output->writeln('');
        } catch (\Exception $e) {
            $output->writeln("<error>Exception during Download roles ".$e->getMessage()."</error>");
        }

        try {
            $output->writeln('Download Product categories =>');
            $initCommand = $this->getApplication()->find('quick:download:generic');
            $arguments = new ArrayInput(array('type' => 'categories'));
            $initCommand->run($arguments, $output);
            $output->writeln('<info> => Download Product categorieswith success <= </info>');
            $output->writeln('');
        } catch (\Exception $e) {
            $output->writeln("<error>Exception during Download Product categories ".$e->getMessage()."</error>");
        }

        try {
            $initCommand = $this->getApplication()->find('quick:user:initialize');
            $initCommand->run($input, $output);
            $output->writeln('');
        } catch (\Exception $e) {
            $output->writeln("<error>Exception during quick:user:initialize ".$e->getMessage()."</error>");
        }

        try {
            $output->writeln('Procedure import =>');
            $initCommand = $this->getApplication()->find('quick:procedure:import');
            $initCommand->run($input, $output);
            $output->writeln('<info> => Procedure import finished with success <= </info>');
            $output->writeln('');
        } catch (\Exception $e) {
            $output->writeln("<error>Exception during Procedure import ".$e->getMessage()."</error>");
        }

        try {
            $output->writeln('Optikitchen init =>');
            $initCommand = $this->getApplication()->find('quick:optikitchen:init');
            $initCommand->run($input, $output);
            $output->writeln('<info> => Optikitchen init finished with success <= </info>');
            $output->writeln('');
        } catch (\Exception $e) {
            $output->writeln("<error>Exception during Optikitchen init  ".$e->getMessage()."</error>");
        }
        try {
            $output->writeln('Import users from Wynd =>');
            $initCommand = $this->getApplication()->find('quick:user:wynd:rest:import');
            $initCommand->run($input, $output);
            $output->writeln('<info> => Import users from Wynd finished with success <= </info>');
            $output->writeln('');
        } catch (\Exception $e) {
            $output->writeln("<error>Exception during  Import users from Wynd  ".$e->getMessage()."</error>");
        }


        try {
            $output->writeln('Upload users to Supervision =>');
            $initCommand = $this->getApplication()->find('quick:sync:employees');
            $initCommand->run($input, $output);
            $output->writeln('<info> => Upload users to Supervision <= </info>');
            $output->writeln('');
        } catch (\Exception $e) {
            $output->writeln("<error>Exception during Upload users to Supervision  ".$e->getMessage()."</error>");
        }

        try {
            $output->writeln('Synchronization Command =>');
            $initCommand = $this->getApplication()->find('quick:sync:execute');
            $initCommand->run($input, $output);
            $output->writeln('<info> => Synchronization command finished with success <= </info>');
            $output->writeln('');
        } catch (\Exception $e) {
            $output->writeln("<error>Exception during Synchronization Command  ".$e->getMessage()."</error>");
        }
    }
}
