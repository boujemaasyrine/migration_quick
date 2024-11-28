<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 29/05/2016
 * Time: 17:00
 */

namespace AppBundle\Administration\Command;

use AppBundle\Merchandise\Entity\Restaurant;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class OptikitchenAutomaticSendingCommand
 */
class OptikitchenAutomaticSendingCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:optikitchen:sending')->setDefinition(
            []
        )
            ->addArgument('restaurantId', InputArgument::OPTIONAL);
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->logger = $this->getContainer()->get('logger');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentRestaurant = null;

        if ($input->hasArgument('restaurantId') && !empty($input->getArgument('restaurantId'))) {
            $restaurantId = $input->getArgument('restaurantId');
            $currentRestaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);
            if ($currentRestaurant == null) {
                $this->logger->addAlert('Restaurant not found with id: '.$restaurantId, ['OptikitchenAutomaticSendingCommand']);
                return;
            }
        }


        if ($currentRestaurant == null) {
            $restaurants= $this->em->getRepository(Restaurant::class)->getOpenedRestaurants();
            foreach ($restaurants as $restaurant){

                /**
                 * @var Restaurant $restaurant
                 */
                $this->logger->info('restaurant '.$restaurant->getCode(),['OptikitchenAutomaticSendingCommand']);
                $this->logger->info(
                    "Launching service 'optikitchen.service': calculate \n ",
                    ['OptikitchenAutomaticSendingCommand']
                );

                $this->getContainer()->get('session')->set('currentRestaurant', $restaurant->getId());
                $o = $this->getContainer()->get('optikitchen.service')->launchAutomatic(new \DateTime('today'),$restaurant);

                $this->logger->info(
                    "Launching service 'optikitchen.service': sending \n ",
                    ['OptikitchenAutomaticSendingCommand']
                );
                $this->getContainer()->get('optikitchen.service')->sendToOptikitchen($o,$restaurant);

                $this->logger->info("Sending to Optikitchen with success. \n ", ['OptikitchenAutomaticSendingCommand']);
            }
        }


        else {
            // treatment for only one restaurant

            $this->logger->info('restaurant '.$currentRestaurant->getCode(),['OptikitchenAutomaticSendingCommand']);
            $this->logger->info(
                "Launching service 'optikitchen.service': calculate \n ",
                ['OptikitchenAutomaticSendingCommand']
            );

           $session=$this->getContainer()->get('session');
           /* $session=new Session();
            $session->start();*/
            $session->set('currentRestaurant', $currentRestaurant->getId());
            $o = $this->getContainer()->get('optikitchen.service')->launchAutomatic(new \DateTime('today'),$currentRestaurant);

            $this->logger->info(
                "Launching service 'optikitchen.service': sending \n ",
                ['OptikitchenAutomaticSendingCommand']
            );
           
            $this->getContainer()->get('optikitchen.service')->sendToOptikitchen($o,$currentRestaurant);

            $this->logger->info("Sending to Optikitchen with success. \n ", ['OptikitchenAutomaticSendingCommand']);
        }


    }
}
