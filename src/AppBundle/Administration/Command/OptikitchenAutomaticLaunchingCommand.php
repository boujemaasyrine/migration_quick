<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 29/05/2016
 * Time: 17:00
 */

namespace AppBundle\Administration\Command;

use AppBundle\Merchandise\Entity\Restaurant;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;

/**
 * Class OptikitchenAutomaticLaunchingCommand
 */
class OptikitchenAutomaticLaunchingCommand extends ContainerAwareCommand
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
        $this->setName('quick:optikitchen:automatic')->setDefinition([]);
        $this->addArgument('restaurantId',InputArgument::REQUIRED);
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
        $restaurantId=$input->getArgument('restaurantId');
        $currentRestaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);
        if (null==$currentRestaurant) {
            echo 'Restaurant not found with id: '.$restaurantId;
            return;
        }

        $this->getContainer()->get('optikitchen.service')->launchAutomatic(new \DateTime('today'),$currentRestaurant);
    }
}
