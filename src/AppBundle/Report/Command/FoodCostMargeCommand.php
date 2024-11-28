<?php

namespace AppBundle\Report\Command;

use AppBundle\Report\Service\ReportFoodCostService;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FoodCostMargeCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var ReportFoodCostService $margeFoodCostService
     */
    private $margeFoodCostService;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('report:marge:foodcost')
            ->setDescription('Hello PhpStorm')
            ->addArgument('currentRestaurantId', InputArgument::REQUIRED)
            ->addArgument('startDate', InputArgument::OPTIONAL)
            ->addArgument('endDate', InputArgument::OPTIONAL)
            ->addArgument('progressBarId', InputArgument::OPTIONAL)
            ->addArgument('force', InputArgument::OPTIONAL)
            ->setDescription("");
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->margeFoodCostService = $this->getContainer()->get('report.foodcost.service');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $currentRestaurantId = $input->getArgument("currentRestaurantId");

        if ($input->hasArgument('progressBarId') && !empty($input->getArgument('progressBarId'))) {
            $progressBarId = $input->getArgument('progressBarId');
            $progression = $this->em
                ->getRepository("General:ImportProgression")
                ->find($progressBarId);
            $progression->setStatus('pending');
        } else {
            $progression = null;
            echo "Progression not found ==> NULL\n";
        }


        if (!$input->hasArgument('startDate') || empty($input->getArgument('startDate'))) {
            $today = new \DateTime();
            $startDate = Utilities::getDateFromDate($today, -7);
            echo "No Start Date , stardate intitalized to ".$startDate->format('d/m/Y')."\n";
        } else {
            $startDate = $input->getArgument('startDate');
            $startDate = date_create_from_format('Y-m-d', $startDate);
        }

        if (!$input->hasArgument('endDate') || empty($input->getArgument('endDate'))) {
            $endDate = Utilities::getDateFromDate($today, -1);
            echo "No End Date , endate intitalized to ".$endDate->format('d/m/Y')."\n";
        } else {
            $endDate = $input->getArgument('endDate');
            $endDate = date_create_from_format('Y-m-d', $endDate);
        }

        if ($input->hasArgument('force') && $input->getArgument('force') != '') {
            $force = $input->getArgument('force');
        } else {
            $force = 1;
        }

        $lock = $this->getContainer()->get('report.foodcost.service')->checkLocked($currentRestaurantId);
        if ($lock->getValue() == 0) {
            $lock->setValue(1);
            $this->em->flush();
            $this->margeFoodCostService->getMargeFoodCost(
                $currentRestaurantId,
                $startDate,
                $endDate,
                $progression,
                intval($force)
            );
            $lock->setValue(0);
            $this->em->flush();
        } else {
            echo "\n Un process est encours \n";
        }
        if ($progression) {
            $progression->setStatus('finish');
            $this->em->flush();
        }
    }
}
