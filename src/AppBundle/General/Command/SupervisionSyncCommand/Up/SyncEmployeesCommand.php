<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 19/05/2016
 * Time: 17:09
 */

namespace AppBundle\General\Command\SupervisionSyncCommand\Up;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncEmployeesCommand extends ContainerAwareCommand
{

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:sync:employees')->setDefinition(
            []
        )->setDescription('Sync all employees');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('sync.employee.service')->syncAllEmployees();
    }
}
