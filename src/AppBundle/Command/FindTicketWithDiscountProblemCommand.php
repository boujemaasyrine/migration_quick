<?php

namespace AppBundle\Command;
/**
 * Created by PhpStorm.
 * User: zbessassi
 * Date: 12/07/2019
 * Time: 16:10
 */

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Report\Service\ReportTicketsService;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cette commande permet de trouver les tickets annulés dans les qu'elles il y a un problème dans les valeurs de discount.
 * Exemple d'utilisation
 * find:ticket:discount:problem restaurant_id startDate endDate
 * Class FindTicketWithDiscountProblemCommand
 * @package AppBundle\Command
 */
class FindTicketWithDiscountProblemCommand extends ContainerAwareCommand
{

    /**
     * @var Logger $loggerCommand
     */
    private $loggerCommand;

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var ReportTicketsService $reportTicketService
     */
    private $reportTicketService;

    protected function configure()
    {
        $this
            ->setName("find:ticket:discount:problem")
            ->addArgument('restaurantId', InputArgument::REQUIRED)
            ->addArgument('startDate', InputArgument::REQUIRED)
            ->addArgument('endDate', InputArgument::REQUIRED)
            ->setDescription("");
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->loggerCommand = $this->getContainer()->get('monolog.logger.app_commands');
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->reportTicketService = $this->getContainer()->get('report.tickets.service');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $restaurantId = $input->getArgument('restaurantId');
        $restaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);
        if (!is_object($restaurant)) {
            echo 'Restaurant is not found with id: ' . $restaurantId;
            return;
        }
        $startDate = $input->getArgument('startDate');
        $endDate = $input->getArgument('endDate');
        $filter = ['invoiceCancelled' => '1',
            'startDate' => new \DateTime($startDate),
            'endDate' => new \DateTime($endDate),
            'restaurantId' => $restaurantId];
        $result = $this->reportTicketService->getTicketListV2($filter);
        $total = 0;
        foreach ($result as $r) {
            /**
             * @var  $ticket Ticket
             */
            $ticket = $r[0];

            foreach ($ticket->getGroupedDiscount() as $discount) {
                if ($discount['total'] < 0) {
                    $total += $discount['total'];
                    echo 'This ticket has problem in discount ' . "\n";
                    echo 'Ticket id= ' . $ticket->getId() . ' Invoice number= ' . $ticket->getInvoiceNumber() . ' date= ' . $ticket->getDate()->format('Y-m-d') . "\n";
                }
            }
        }

        if ($total != 0) {
            echo 'Total value of problem discount between start date= ' . $startDate . ' end date = ' . $endDate . ' is = ' . $total * 2 . "\n";
        }

    }
}