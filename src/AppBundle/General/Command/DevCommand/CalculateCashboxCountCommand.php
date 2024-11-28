<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 11:56
 */

namespace AppBundle\General\Command\DevCommand;

use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Financial\Service\AdministrativeClosingService;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CalculateCashboxCountCommand extends ContainerAwareCommand
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
     * @var AdministrativeClosingService
     */
    private $adminClosingService;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick_dev:count:cashbox')->setDefinition(
            []
        )
            ->addArgument('startDate', InputArgument::OPTIONAL)
            ->addArgument('endDate', InputArgument::OPTIONAL)
            ->setDescription('Calculate Cashbox Count Real/Theorical/Gap.');
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
            $startDate = $this->adminClosingService->getLastWorkingEndDate();
            $endDate = $this->adminClosingService->getLastWorkingEndDate();
        }
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        // 2) Update Cashbox Counts calculated values
        $cbcs = $this->em->getRepository("Financial:CashboxCount")->createQueryBuilder("cc")
            ->select('COUNT(cc.id)')
            ->where('cc.date between :start and :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()->getSingleScalarResult();

        echo "1) Update $cbcs Cashbox Counts calculated values\n";
        $this->logger->info("1) Update $cbcs Cashbox Counts calculated values", ['CalculateCashboxCountCommand']);

        $y = $cbcs;
        $x = 0;
        while ($x < $y / 10) {
            $cbcs = $this->em->getRepository("Financial:CashboxCount")->createQueryBuilder("cc")
                ->where('cc.date between :start and :end')
                ->setParameter('start', $startDate)
                ->setParameter('end', $endDate)
                ->orderBy('cc.id')
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
        $this->logger->info("Finished", ['CalculateCashboxCountCommand']);
    }
}
