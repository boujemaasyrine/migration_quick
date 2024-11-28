<?php

namespace AppBundle\Command;

use AppBundle\Financial\Service\WithdrawalSynchronizationService;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cette commande permet la synchronisation des prélèvements entre l'api et l'application.
 * Class ImportWithdrawalFromApiCommand
 * @package AppBundle\Financial\Command
 */
class ImportWithdrawalFromApiCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;
    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @var WithdrawalSynchronizationService $wss
     */
    private $wss;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('quick:withdrawal:rest:import')
            ->addArgument('restaurantId', InputArgument::OPTIONAL)
            ->addArgument('startDate', InputArgument::OPTIONAL)
            ->addArgument('endDate', InputArgument::OPTIONAL)
            ->setDescription('withdrawal synchronization between index and talan');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->wss = $this->getContainer()->get(
            'withdrawal.synchronization.service'
        );
        $this->logger = $this->getContainer()->get('monolog.logger.app_commands');
        $this->wss->setLogger($this->logger);
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentRestaurant = null;
        if ($input->hasArgument('restaurantId') && !empty($input->getArgument('restaurantId'))) {
            $restaurantId = $input->getArgument('restaurantId');
            $currentRestaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);
            if ($currentRestaurant == null) {
                $this->logger->addAlert('Restaurant not found with id: ' . $restaurantId, ['quick:withdrawal:rest:import']);
                return;
            }
        }

        $startDate = $input->getArgument('startDate');
        $endDate = $input->getArgument('endDate');
        //$dsf = $this->getContainer()->getParameter('supported_date_format');
        $supportedFormat = 'Y-m-d';

        if (!is_null($startDate) && !is_null($endDate) && Utilities::isValidDateFormat(
                $startDate,
                $supportedFormat
            ) && Utilities::isValidDateFormat($endDate, $supportedFormat)
        ) {
            $startDate = date_create_from_format($supportedFormat, $startDate);
            $endDate = date_create_from_format($supportedFormat, $endDate);
        } else {
            $startDate = null;
            $endDate = null;
        }

        if ($currentRestaurant == null) {
            $restaurants = $this->em->getRepository(Restaurant::class)->getOpenedRestaurants();
            // import withdrawal for each restaurants
            foreach ($restaurants as $restaurant) {
                $this->wss->synchApiWithdrawalTmp($restaurant, $startDate, $endDate, true);
            }
        } else {
            // treatment for only one restaurant
            $this->wss->synchApiWithdrawalTmp($currentRestaurant, $startDate, $endDate, true);
        }
    }
}