<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 14/06/2016
 * Time: 10:15
 */

namespace AppBundle\General\Command;

use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Financial\Service\RevenuePricesService;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitPricesRevenuesForTicketLinesCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var RevenuePricesService
     */
    private $revenuPricesService;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:init:prices:revenues:ticketLines')
            ->setDefinition([])
            ->addArgument('restaurantId',InputArgument::REQUIRED)
            ->addArgument('startDate', InputArgument::OPTIONAL)
            ->addArgument('endDate', InputArgument::OPTIONAL)
            ->setDescription('Initialize user role.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->revenuPricesService = $this->getContainer()->get('prices.revenues.service');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $restaurantId = $input->getArgument('restaurantId');
        $currentRestaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);
        if ($currentRestaurant == null) {
            $output->writeln('Restaurant not found with id: '.$restaurantId, ['quick:wynd:rest:import']);

            return;
        }
        echo "Initializing Prices Revenues For Ticket Lines  \n";

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $step = 200;
        $exist = true;

        $supportedFormat = "Y-m-d";
        if ($input->hasArgument('startDate') && $input->hasArgument('endDate')) {
            $startDate = $input->getArgument('startDate');
            $endDate = $input->getArgument('endDate');
        } else {
            $startDate = null;
            $endDate = null;
        }

        if (!is_null($startDate) && !is_null($endDate) && Utilities::isValidDateFormat(
            $startDate,
            $supportedFormat
        ) && Utilities::isValidDateFormat($endDate, $supportedFormat)) {
            $startDate = date_create_from_format($supportedFormat, $startDate);
            $endDate = date_create_from_format($supportedFormat, $endDate);
        } else {
            $startDate = new \DateTime('today');
            $endDate = new \DateTime('today');
        }

        $i = 0;
        $ticketLines = $this->em->getRepository("Financial:TicketLine")->getTotalCount($startDate, $endDate,$restaurantId);
        echo "Total = ".$ticketLines." \n";
        $progress = new ProgressBar($output, $ticketLines / 200);

        while ($exist) {
            $progress->advance();

             $ticketLines = $this->em->getRepository("Financial:TicketLine")
                ->findByDates($startDate, $endDate,$restaurantId, $step, $i * $step);
            if (count($ticketLines) > 0) {
                foreach ($ticketLines as $tl) {
                    /**
                     * @var TicketLine $tl
                     */
                  $price = $this->revenuPricesService->calculateFinancialRevenueForTicketLine($tl, $currentRestaurant);
                    $tl->setRevenuePrice($price);
                    $tl->getTicket()->setSynchronized(false);
                }
            } else {
                $exist = false;
            }
            $i++;
            $this->em->flush();
            $this->em->clear();
        }

        echo "Finish Initializing Prices Revenues For Ticket Lines  \n";
    }
}
