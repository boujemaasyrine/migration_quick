<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 21/04/2016
 * Time: 11:40
 */

namespace AppBundle\Supervision\Command\Report;

use AppBundle\General\Entity\ImportProgression;
use AppBundle\Report\Entity\ControlStockTmp;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ControlStockCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName("supervision:report:control:stock")
            ->addArgument('id', InputArgument::REQUIRED)
            ->addArgument('progressBarId', InputArgument::REQUIRED)
            ->setDescription("Initialize Help order tmp");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $id = $input->getArgument('id');

        $progressBarId = $input->getArgument('progressBarId');

        $progression = $this->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository(ImportProgression::class)
            ->find($progressBarId);

        if (!$progression) {
            echo "Progression not found ! \n";

            return;
        }
        $tmp = $this->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository(ControlStockTmp::class)
            ->find($id);
        if (!$tmp) {
            echo "Control stock tmp table not found ! \n";

            return;
        }

        $progression->setStatus('pending');
        $this
            ->getContainer()->get('supervision.report.control.stock.service')
            ->createControlReport($tmp, $progression);
        $progression->setStatus('finish');
        $this->getContainer()->get('doctrine.orm.entity_manager')->flush();
    }
}
