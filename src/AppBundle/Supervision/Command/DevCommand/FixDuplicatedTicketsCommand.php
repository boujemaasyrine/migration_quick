<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 11:56
 */

namespace AppBundle\Supervision\Command\DevCommand;

use AppBundle\Financial\Entity\CashboxCount;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixDuplicatedTicketsCommand extends ContainerAwareCommand
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
        $this->setName('quick_dev:fix:duplicate:ticket')->setDefinition(
            []
        )->setDescription('Fix the duplication of tickets problem.');
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
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $conn = $this->em->getConnection();
        //        $sql = "SELECT id FROM (SELECT id, ROW_NUMBER() OVER (partition BY date, num, type ORDER BY id) AS rnum FROM ticket) t WHERE t.rnum > 1";
        $sql = "SELECT id FROM ticket where num < 0 and date >= '2016-07-11' and origin_restaurant_id = 5";
        $stm = $conn->prepare($sql);
        $duplicatedTicketIds = $stm->fetchAll(\PDO::FETCH_COLUMN);

        $this->logger->addDebug(
            "Duplicated Ticket ids :".implode(',', $duplicatedTicketIds),
            ['FixDuplicatedTicketsCommand']
        );

        echo "1) Delete duplicated ticket\n";

        // Delete mvmt records
        $sql2 = "delete from product_purchased_mvmt mvmt where mvmt.type = 'sold' and mvmt.source_id in (select id from ticket_line tl where tl.ticket_id in (
                 SELECT id FROM (".$sql.");";
        $stm = $conn->prepare($sql2);
        $stm->execute();

        // 1) Delete duplicated tickets ...
        $sql1 = "delete from ticket_intervention where ticket_intervention.ticket_id in (".$sql.");";

        $stm = $conn->prepare($sql1);
        $stm->execute();

        $sql1 = "delete from ticket_payment where ticket_payment.ticket_id in (".$sql.");";
        $stm = $conn->prepare($sql1);
        $stm->execute();

        $sql1 = "delete from ticket_line where ticket_line.ticket_id in (".$sql.");";
        $stm = $conn->prepare($sql1);
        $stm->execute();

        $sql1 = "delete from ticket where id in (".$sql.");";
        $stm = $conn->prepare($sql1);
        $stm->execute();

        echo "2) Update Cashbox Counts calculated values\n";
        // 2) Update Cashbox Counts calculated values
        $cbcs = $this->em->getRepository('AppBundle:Financial\CashboxCount')->createQueryBuilder("cashboxCount")
            ->join('cashboxCount.originRestaurant', 'r')
            ->where('r.id = 5')
            ->getQuery()->getResult();

        $y = count($cbcs);
        $x = 0;
        while ($x < $y / 10) {
            $cbcs = $this->em->getRepository('AppBundle:Financial\CashboxCount')->createQueryBuilder("cashboxCount")
                ->join('cashboxCount.originRestaurant', 'r')
                ->where('r.id = 5')
                ->setMaxResults(10)->setFirstResult($x * 10)
                ->getQuery()->getResult();
            $progress = new ProgressBar($output, count($cbcs));
            echo "\nStep ".($x + 1);

            foreach ($cbcs as $cbc) {
                /**
                 * @var CashboxCount $cbc
                 */
                $cbc->setRealCaCounted($cbc->calculateTotalCashbox())
                    ->setTheoricalCa($cbc->calculateTheoricalTotalCashbox());
                $progress->advance();
            }
            $this->em->flush();
            $this->em->clear();
            $x++;
        }

        echo "Finished\n";
    }
}
