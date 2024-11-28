<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 23/05/2016
 * Time: 10:58
 */

namespace AppBundle\General\Command\SupervisionSyncCommand\Up;

use AppBundle\General\Service\Remote\SynchronizerService;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenericUploadCommand extends ContainerAwareCommand
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:upload:generic')->setDefinition(
            []
        )->setDescription('Sync all inventories')
            ->addArgument('type', InputArgument::REQUIRED)
            ->addArgument('id_sync', InputArgument::OPTIONAL);
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->logger = $this->getContainer()->get('logger');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type');

        $service = "sync.".$type.".service";

        $this->logger->info("Launching service $service \n ", ['GenericUploadCommand']);

        if ($input->hasArgument('id_sync')) {
            $idSync = intval($input->getArgument('id_sync'));
        } else {
            $idSync = null;
        }

        if ($this->getContainer()->has($service)) {
            if ($this->getContainer()->get($service) instanceof SynchronizerService) {
                $this->getContainer()->get($service)->start($idSync);
            } else {
                $this->logger->info(
                    "$service must be instance of ".SynchronizerService::class,
                    ['GenericUploadCommand']
                );
            }
        } else {
            $this->logger->info("$service Not found ", ['GenericUploadCommand']);
        }
    }
}
