<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 18:13
 */

namespace AppBundle\General\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportStaffSecurityConfigurationCommand extends ContainerAwareCommand
{

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:staff:security:data:import')->setDefinition(
            []
        )->setDescription('Import all staff security.');
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
        $rolesCommand = $this->getApplication()->find('quick:roles:import');
        $rolesCommand->run($input, $output);

        $rightsCommand = $this->getApplication()->find('quick:actions:import');
        $rightsCommand->run($input, $output);

        $initializeCommand = $this->getApplication()->find('quick:roles:right:initialize');
        $initializeCommand->run($input, $output);


        $output->writeln('All data imported with success !');
    }
}
