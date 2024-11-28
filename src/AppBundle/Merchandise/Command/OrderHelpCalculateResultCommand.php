<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 05/04/2016
 * Time: 10:39
 */

namespace AppBundle\Merchandise\Command;

use AppBundle\General\Entity\ImportProgression;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrderHelpCalculateResultCommand extends ContainerAwareCommand
{
    /**
     * @var ImportProgression $progression
     */
    private $progression;

    protected function configure()
    {
        $this
            ->setName("order:help:result")
            ->addArgument('id', InputArgument::REQUIRED)
            ->addArgument('progressBarId', InputArgument::REQUIRED)
            ->addArgument('restaurant_id', InputArgument::OPTIONAL)
            ->setDescription("Initialize Help order tmp");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $id = $input->getArgument('id');

        $progressBarId = $input->getArgument('progressBarId');

        $restaurant_id = $input->getArgument('restaurant_id');
        if ($restaurant_id) {
            $this->getContainer()->get('session')->set('currentRestaurant', $restaurant_id);
        }

        $helpOrder = $this->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository("Merchandise:OrderHelpTmp")
            ->find($id);

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
        $this->getContainer()->get('help_order.service')->setResultsV2($helpOrder, $this->progression);
        $this->progression->setStatus('finish');
        $this->getContainer()->get('doctrine.orm.entity_manager')->flush();
    }
}
