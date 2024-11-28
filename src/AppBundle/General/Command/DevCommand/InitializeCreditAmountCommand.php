<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 28/09/2016
 * Time: 09:08
 */

namespace AppBundle\General\Command\DevCommand;

use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeCreditAmountCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager
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
        $this->setName('quick_dev:credit:amount:initialize')->setDefinition([])
            ->setDescription('Initialize Credit Amount for Administrative Closing Command')
            ->addArgument('restaurantId',InputArgument::REQUIRED);
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('logger');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var ChestCount $firstChestCount
         */

        $restaurantId=$input->getArgument('restaurantId');
        $restaurant=$this->em->getRepository(Restaurant::class)->find($restaurantId);

        if(!$restaurant){
            echo 'restaurant not found with id'. $restaurantId;
            return;
        }


        $firstChestCount = $this->em->getRepository('Financial:ChestCount')->findFirstChestCountInClosureAdministrative($restaurant
        );
        if ($firstChestCount) {
            $firstClosing = $this->em->getRepository('Financial:AdministrativeClosing')->findOneBy(
                [
                    'date' => $firstChestCount->getClosureDate(),
                    'originRestaurant'=>$restaurant
                ]
            );
            if ($firstClosing) {
                $firstClosing->setCreditAmount($firstChestCount->calculateRealTotal($restaurant));
                $this->em->flush();
            }
        }
        echo "Finished\n";
    }
}
