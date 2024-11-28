<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 29/06/2016
 * Time: 13:17
 */

namespace AppBundle\Supervision\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitSqlViewsAndIndexesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:super:init:views')->setDefinition(
            []
        )->setDescription('Initialize Views');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var EntityManager
         */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $viewDir = $this->getContainer()->get('kernel')->getRootDir()."/../scripts/views";

        $files = scandir($viewDir);

        foreach ($files as $f) {
            $filepath = $viewDir."/".$f;
            if (!is_dir($filepath)) {
                $stm = $em->getConnection()->prepare(file_get_contents($filepath));
                $stm->execute();
            }
        }

        $otherFiles = [
            $viewDir."/../index.sql",
        ];
        foreach ($otherFiles as $f) {
            $stm = $em->getConnection()->prepare(file_get_contents($f));
            $stm->execute();
        }
    }
}
