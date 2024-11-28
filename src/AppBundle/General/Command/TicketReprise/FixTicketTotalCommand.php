<?php
/**
 * Created by PhpStorm.
 * User: bbarhoumi
 * Date: 07/10/2016
 * Time: 17:24
 */

namespace AppBundle\General\Command\TicketReprise;

use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixTicketTotalCommand extends ContainerAwareCommand
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
        $this->setName('quick:reprise:fix:ticket')
            ->addArgument('startDate', InputArgument::REQUIRED)
            ->addArgument('endDate', InputArgument::REQUIRED)
            ->setDescription('Import Financial Revenue From Wynd Tickets.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $supportedFormat = "Y-m-d";
        if ($input->hasArgument('startDate') && $input->hasArgument('endDate')) {
            $startDate = $input->getArgument('startDate');
            $endDate = $input->getArgument('endDate');
        } else {
            $startDate = null;
            $endDate = null;
        }

        $startDate = date_create_from_format($supportedFormat, $startDate);
        $endDate = date_create_from_format($supportedFormat, $endDate);

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        for ($i = 0; $i <= $endDate->diff($startDate)->days; $i++) {
            $date = $date = Utilities::getDateFromDate($startDate, $i);

            $tickets = $this->em->getRepository('Financial:Ticket')->findBy(['date' => $date]);
            foreach ($tickets as $ticket) {
                $discounts = $this->em->getRepository('Financial:Ticket')->getDiscountTicket($ticket->getId());
                $ticket->setTotalTTC($discounts['total_ttc'] - $discounts['discount_ttc']);
                $ticket->setTotalHt($discounts['total_ht'] - $discounts['discount_ht']);
            }
            $this->em->flush();
            $this->em->clear();
            echo "Fix tickets for ".date_format($date, 'Y-m-d')." updated with success \n";
        }
    }
}
