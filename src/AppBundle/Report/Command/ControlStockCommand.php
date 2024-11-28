<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 21/04/2016
 * Time: 11:40
 */

namespace AppBundle\Report\Command;

use AppBundle\Merchandise\Entity\Restaurant;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ControlStockCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName("report:control:stock")
            ->addArgument('restaurantId', InputArgument::REQUIRED)
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
            ->getRepository("General:ImportProgression")
            ->find($progressBarId);

        if (!$progression) {
            echo "Progression not found ! \n";

            return;
        }

        $tmp = $this->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository("Report:ControlStockTmp")
            ->find($id);
        if (!$tmp) {
            echo "Control stock tmp table not found ! \n";

            return;
        }
        $restaurantId = $input->getArgument('restaurantId');
        $restaurant = $this->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository(Restaurant::class)
            ->find($restaurantId);

        if (!$restaurant) {
            echo "Restaurant not found ! \n";

            return;
        }

        $this->getContainer()->get('session')->set('currentRestaurant', $restaurantId);

        $progression->setStatus('pending');
        $this
            ->getContainer()->get('report.control.stock.service')
            ->createControlReport($tmp, $progression, $restaurant);
        $progression->setStatus('finish');
        $this->getContainer()->get('doctrine.orm.entity_manager')->flush();
    }
}
