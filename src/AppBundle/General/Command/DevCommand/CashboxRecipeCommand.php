<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 11:56
 */

namespace AppBundle\General\Command\DevCommand;

use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Financial\Model\DayIncome;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CashboxRecipeCommand extends ContainerAwareCommand
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
        $this->setName('quick_dev:cashbox:recipe:ticket')->setDefinition(
            []
        )->addArgument('startDate', InputArgument::OPTIONAL)
            ->addArgument('endDate', InputArgument::OPTIONAL)
            ->setDescription('Create Cashbox Recipe Command.');
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
            $startDate = new \DateTime('today');
            $endDate = new \DateTime('today');
        }
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        for ($i = 0; $i <= $endDate->diff($startDate)->days; $i++) {
            $date = Utilities::getDateFromDate($startDate, $i);
            echo "Date: ".$date->format('d/m/Y')."\n";

            $dayIncome = new DayIncome();
            $dayIncome->setDate($date);
            $cashboxCounts = $this->em->getRepository('Financial:CashboxCount')
                ->findBy(array('date' => $dayIncome->getDate()));
            if (count($cashboxCounts) > 0) {
                $dayIncome->setCashboxCounts($cashboxCounts);
            }

            $recipeTicket = $this->em->getRepository('Financial:RecipeTicket')->findOneBy(
                array('date' => $date, 'label' => RecipeTicket::CACHBOX_RECIPE)
            );
            if (is_null($recipeTicket)) {
                $recipeTicket = new RecipeTicket();
            }
            $recipeTicket->setDate($date)
                ->setAmount($dayIncome->calculateCashboxTotal())
                ->setSynchronized(false)
                ->setLabel(RecipeTicket::CACHBOX_RECIPE);
            $this->em->persist($recipeTicket);
        }
        $this->em->flush();

        echo "Finished\n";
    }
}
