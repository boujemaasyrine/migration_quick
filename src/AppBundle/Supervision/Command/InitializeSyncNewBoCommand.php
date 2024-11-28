<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 12:18
 */

namespace AppBundle\Supervision\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeSyncNewBoCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:sync:cmd:initialize:all')->setDefinition(
            []
        )->addArgument('quickCode', InputArgument::REQUIRED)
            ->setDescription('');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Sync Initialize Command =>');
        $restaurantCommand = $this->getApplication()->find('quick:sync:cmd:initialize');
        $restaurantCommand->run($input, $output);

        $output->writeln('Sync Initialize Eligible Products =>');
        $restaurantCommand = $this->getApplication()->find('quick:sync:eligible:products');
        $restaurantCommand->run($input, $output);

        $output->writeln('Sync Initialize Products =>');
        $restaurantCommand = $this->getApplication()->find('quick:sync:cmd:products');
        $restaurantCommand->run($input, $output);
    }
}
