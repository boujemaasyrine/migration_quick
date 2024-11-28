<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 23/05/2016
 * Time: 10:58
 */

namespace AppBundle\General\Command\SupervisionSyncCommand\Download;

use AppBundle\General\Service\Download\AbstractDownloaderService;
use AppBundle\General\Service\Remote\SynchronizerService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenericDownloadCommand extends ContainerAwareCommand
{

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:download:generic')->setDefinition(
            []
        )->setDescription('Download')
            ->addArgument('type', InputArgument::REQUIRED)
            ->addArgument('id_sync', InputArgument::OPTIONAL);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type');

        $service = "download.".$type.".service";

        if ($input->hasArgument('id_sync')) {
            $idSync = intval($input->getArgument('id_sync'));
        } else {
            $idSync = null;
        }

        //  echo "Launching service $service \n ";

        if ($this->getContainer()->has($service)) {
            if ($this->getContainer()->get($service) instanceof AbstractDownloaderService) {
                $this->getContainer()->get($service)->download($idSync);
            } else {
                echo "$service must be instance of ".AbstractDownloaderService::class."\n";
            }
        } else {
            echo "$service Not found  \n";
        }
    }
}
