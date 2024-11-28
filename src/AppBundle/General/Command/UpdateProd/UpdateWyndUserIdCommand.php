<?php

namespace AppBundle\General\Command\UpdateProd;

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketPayment;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;

class UpdateWyndUserIdCommand extends ContainerAwareCommand
{

    private $dataDir;

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
        $this->setName('quick:update:wynd:user:id')
            ->setDescription('Update Wynd user id.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->logger = $this->getContainer()->get('logger');

        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/support/";
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $filePath = $this->dataDir."295_new_user_id.csv";
        if (!file_exists($filePath)) {
            $this->logger->addDebug($filePath." is not existing !", ['UpdateWyndUserIdCommand']);

            return;
        }
        $file = fopen($filePath, 'r');
        if (!$file) {
            $this->logger->addDebug("Cannot open file $filePath", ['UpdateWyndUserIdCommand']);

            return;
        }
        $this->logger->addDebug("Start at ".date('H:i:s '), ['UpdateWyndUserIdCommand']);
        $header = fgets($file);
        $i = 0;
        $j = 0;
        $k = 0;
        $number = sizeof($file);
        $progress = new ProgressBar($output, $number);
        $idPayment = array();
        $ids = array();
        while ($line = fgetcsv($file, null, ';')) {
            $progress->advance();

            $user = [
                "new_id" => $line[0],
                "old_id" => $line[1],
                "username" => $line[2],
                "old_username" => $line[5],
                "first_name" => $line[3],
                "last_name" => $line[4],
            ];
            try {
                $this->em->beginTransaction();
                $qb = $this->em->getRepository('Staff:Employee')->createQueryBuilder('e')
                    ->andWhere('e.wyndId = :old_id')->setParameter('old_id', $user['old_id']);
                if (sizeof($ids) > 0) {
                    $qb->andWhere('e.id not in (:ids)')->setParameter('ids', $ids);
                }
                $employee = $qb->getQuery()->getOneOrNullResult();
                if ($employee) {
                    $ids[] = $employee->getId();
                    $employee->setWyndId($user['new_id'])
                        ->setUsername($user['username'])
                        ->setFirstName($user['first_name'])
                        ->setLastName($user['last_name']);

                    $tickets = $this->em->getRepository('Financial:Ticket')->createQueryBuilder('t')
                        ->where('t.operator = :oldId')
                        ->setParameter('oldId', $user['old_id'])
                        ->andWhere('t.deliveryTime is null')
                        ->andWhere('t.date > :date')
                        ->setParameter('date', '2016-07-11')
                        ->getQuery()->getResult();

                    foreach ($tickets as $ticket) {
                        /**
                         * @var Ticket $ticket
                         */
                        $ticket->setOperator($user['new_id'])
                            ->setDeliveryTime(new \DateTime('now'));
                        $j++;
                    }

                    $payments = $this->em->getRepository('Financial:TicketPayment')->createQueryBuilder('tp')
                        ->where('tp.operator = :oldId')
                        ->setParameter('oldId', $user['old_id'])
                        ->join('tp.ticket', 't')
                        ->andWhere('t.date > :date')
                        ->setParameter('date', '2016-07-11')
                        ->getQuery()->getResult();

                    foreach ($payments as $payment) {
                        /**
                         * @var TicketPayment $payment
                         */
                        if (!in_array($payment->getId(), $idPayment)) {
                            $payment->setOperator($user['new_id']);
                            $idPayment[] = $payment->getId();
                            $k++;
                        }
                    }
                    $this->em->flush();
                    $this->em->commit();
                    $this->em->clear();
                    $i++;
                } else {
                    $this->logger->addDebug(
                        $i.": User  ".$user['old_id']."was not found.",
                        ['UpdateWyndUserIdCommand']
                    );
                }
            } catch (\Exception $e) {
                $this->logger->addDebug(
                    $i.": User ".$user['old_id']." was failed to update.",
                    ['UpdateWyndUserIdCommand']
                );
                $this->logger->addDebug($e->getMessage(), ['UpdateWyndUserIdCommand']);
            }
        }
        $progress->finish();

        fclose($file);
        $this->logger->addDebug("Finish at ".date('H:i:s '), ['UpdateWyndUserIdCommand']);
        $this->logger->addDebug($i." Users was updated.", ['UpdateWyndUserIdCommand']);
        $this->logger->addDebug($j." Tickets was updated.", ['UpdateWyndUserIdCommand']);
        $this->logger->addDebug($k." Ticket payments was updated.", ['UpdateWyndUserIdCommand']);
    }
}
