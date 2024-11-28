<?php

namespace AppBundle\Supervision\Command\Report;

use AppBundle\General\Entity\ImportProgression;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Service\Reports\MarginFoodCostService;
use AppBundle\Supervision\Utils\Utilities;
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
     * @var MarginFoodCostService $margeFoodCostService
     */
    private $margeFoodCostService;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('supervision:report:marge:foodcost')
            ->setDescription('Hello PhpStorm')
            ->addArgument('restaurant', InputArgument::REQUIRED)
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
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->margeFoodCostService = $this->getContainer()->get('supervision.report.margin.foodcost.service');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ($input->hasArgument('restaurant') && $input->getArgument('restaurant') != '') {
            $restaurantID = $input->getArgument('restaurant');
            $restaurant = $this->em->getRepository(Restaurant::class)->find($restaurantID);
            if (!$restaurant) {
                echo "Restaurant Param not found \n";

                return;
            }
        } else {
            echo "Restaurant Param not found \n";

            return;
        }
        if ($input->hasArgument('progressBarId') && !empty($input->getArgument('progressBarId'))) {
            $progressBarId = $input->getArgument('progressBarId');
            $progression = $this->em
                ->getRepository(ImportProgression::class)
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

        $lock = $this->margeFoodCostService->checkLocked();
        if ($lock->getValue() == 0) {
            $lock->setValue(1);
            $this->em->flush();
            $this->margeFoodCostService->getMargeFoodCost(
                $restaurant,
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
