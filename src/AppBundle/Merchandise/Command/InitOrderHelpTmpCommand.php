<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 28/03/2016
 * Time: 15:23
 */

namespace AppBundle\Merchandise\Command;

use AppBundle\General\Entity\ImportProgression;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitOrderHelpTmpCommand extends ContainerAwareCommand
{

    /**
     * @var ImportProgression
     */
    private $progression;

    protected function configure()
    {
        $this
            ->setName("order:help:init")
            ->addArgument('id', InputArgument::REQUIRED)
            ->addArgument('progressBarId', InputArgument::REQUIRED)
            ->setDescription("Initialize Help order tmp");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $id = $input->getArgument('id');

        $progressBarId = $input->getArgument('progressBarId');

        $helpOrder = $this->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository("Merchandise:OrderHelpTmp")
            ->find($id);

        $restaurant = $helpOrder->getOriginRestaurant();
        if ($restaurant) {
            $this->getContainer()->get('session')->set('currentRestaurant', $restaurant->getId());
        }

        if (!$helpOrder) {
            echo "help Order not found ! \n";

            return;
        }

        $this->progression = $this->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository("General:ImportProgression")
            ->find($progressBarId);

        if (!$this->progression) {
            echo "Progression not found ! \n";

            return;
        }

        $this->progression->setStatus('pending');
        $this->getContainer()->get('help_order.service')->createOrderTmp($helpOrder, $this->progression);
        $this->progression->setStatus('finish');
        $this->getContainer()->get('doctrine.orm.entity_manager')->flush();
    }
}
