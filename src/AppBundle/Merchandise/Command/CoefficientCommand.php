<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 09/05/2016
 * Time: 09:59
 */

namespace AppBundle\Merchandise\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CoefficientCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName("coef:calculate")
            ->addArgument('id', InputArgument::REQUIRED)
            ->addArgument('progressBarId', InputArgument::REQUIRED)
            ->addArgument('restaurant_id', InputArgument::OPTIONAL)
            ->addArgument('loss', InputArgument::OPTIONAL)
            ->setDescription("Initialize Help order tmp");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $id = $input->getArgument('id');

        $restaurant_id = $input->getArgument('restaurant_id');
        $loss= $input->getArgument('loss');
        if ($restaurant_id) {
            $this->getContainer()->get('session')->set('currentRestaurant', $restaurant_id);
        }

        $progressBarId = $input->getArgument('progressBarId');

        $base = $this->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository("Merchandise:CoefBase")
            ->find($id);

        if (!$base) {
            echo "Base not found ! \n";

            return;
        }

        $progression = $this->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository("General:ImportProgression")
            ->find($progressBarId);

        if (!$progression) {
            echo "Progression not found ! \n";

            return;
        }

        $progression->setStatus('pending');
        $this->getContainer()->get('coef.service')->calculateCoeffForPP($base, $progression,$loss);
        $progression->setStatus('finish')
            ->setEndDateTime(new \DateTime());
        $this->getContainer()->get('doctrine.orm.entity_manager')->flush();
    }
}
