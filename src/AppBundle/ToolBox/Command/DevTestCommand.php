<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 29/05/2016
 * Time: 17:00
 */

namespace AppBundle\ToolBox\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;

class DevTestCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('dev:test')->setDefinition(
            []
        )
            //->addArgument('date',InputArgument::REQUIRED)
            //->addArgument('date1',InputArgument::REQUIRED)
            //->addArgument('date2',InputArgument::REQUIRED)
            //->addArgument('date3',InputArgument::REQUIRED)
            ->addArgument('restaurant_id', InputArgument::OPTIONAL);
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $restaurant_id = $this->getContainer()->get('session')->get('currentRestaurant');
        $output->writeln("Test script start...");
        if ($restaurant_id) {
            $currentRestaurant = $this->em->getRepository("Merchandise:Restaurant")->find(
                $this->getContainer()->get('session')->get("currentRestaurant")
            );
        }
        if ($currentRestaurant) {
            $output->writeln("Current restaurant = ".$currentRestaurant->getName());
        } else {
            $output->writeln("No restaurant in session.");
            $restaurant_id = $input->getArgument('restaurant_id');
            if ($restaurant_id) {
                $this->getContainer()->get('session')->set('currentRestaurant', $restaurant_id);
                $output->writeln("Restaurant id ".$restaurant_id." setted in session.");
                $currentRestaurant = $this->getContainer()->get('restaurant.service')->getCurrentRestaurant();
                $output->writeln("New restaurant in session => ".$currentRestaurant->getName());
            } else {
                $output->writeln("No restaurant passed as argument. Ending script");
            }
        }
    }
}
