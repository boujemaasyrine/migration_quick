<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/02/2016
 * Time: 14:44
 */

namespace AppBundle\Merchandise\Command;

use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Service\InventoryService;
use AppBundle\Merchandise\Service\ProductPurchasedMvmtService;
use AppBundle\ToolBox\Utils\Utilities;
use AppBundle\General\Entity\Notification;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreatePurchasedMvmtOnTicketsCommand extends ContainerAwareCommand
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ProductPurchasedMvmtService
     */
    private $productPurchasedMvmtService;

    protected function configure()
    {
        $this
            ->setName("mvmt:products:purchased:sold")
            ->setDescription("Create mvmt entries for tickets non recoreded.");
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->logger = $this->getContainer()->get('monolog.logger.app_commands');
        $this->productPurchasedMvmtService = $this->getContainer()->get('product_purchased_mvmt.service');
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->addInfo(
            'Launching CreatePurchasedMvmtOnTicketsCommand.',
            ['CreatePurchasedMvmtOnTicketsCommand']
        );
        $this->getContainer()->get('product_purchased_mvmt.service')
            ->createMvmtEntryForExistingNonRecordedTicketLine();
        $this->logger->addInfo(
            'Finished CreatePurchasedMvmtOnTicketsCommand.',
            ['CreatePurchasedMvmtOnTicketsCommand']
        );
    }
}
