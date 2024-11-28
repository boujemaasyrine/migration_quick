<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 25/04/2016
 * Time: 12:22
 */

namespace AppBundle\Report\Command;

use AppBundle\Report\Service\ReportFoodCostSyntheticV2;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FoodCostSynthetiqueCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var ReportFoodCostSyntheticV2 $syntheticFoodCostService
     */
    private $syntheticFoodCostService;

    protected function configure()
    {
        $this
            ->setName("report:synthetic:foodcost")
            ->addArgument("currentRestaurantId", InputArgument::REQUIRED)
            ->addArgument('starDate', InputArgument::OPTIONAL)
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
        $this->syntheticFoodCostService = $this->getContainer()->get('report.foodcost.synthetic.service');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->em->getConfiguration()->setSQLLogger(null);

        $currentRestaurantId = $input->getArgument("currentRestaurantId");

        if ($input->hasArgument('progressBarId') && !empty($input->getArgument('progressBarId'))) {
            $progressBarId = $input->getArgument('progressBarId');
            $progression = $this->em
                ->getRepository("General:ImportProgression")
                ->find($progressBarId);
            $progression->setStatus('pending');
        } else {
            $progression = null;
            echo "Progression not found => progression = null \n";
        }

        $today = new \DateTime();

        if ($input->hasArgument('starDate') && !empty($input->getArgument('starDate'))) {
            $startDate = $input->getArgument('starDate');
            $startDate = date_create_from_format('Y-m-d', $startDate);
        } else {
            $startDate = Utilities::getDateFromDate($today, -7);
            echo "No Start Date , stardate intitalized to ".$startDate->format('d/m/Y')."\n";
        }

        if ($input->hasArgument('endDate') && !empty($input->getArgument('endDate'))) {
            $endDate = $input->getArgument('endDate');
            $endDate = date_create_from_format('Y-m-d', $endDate);
        } else {
            $endDate = Utilities::getDateFromDate($today, -1);
            echo "No End Date , endate intitalized to ".$endDate->format('d/m/Y')."\n";
        }

        if ($input->hasArgument('force') && $input->getArgument('force') != '') {
            $force = $input->getArgument('force');
        } else {
            $force = 1;
        }

        $lock = $this->syntheticFoodCostService->checkLocked($currentRestaurantId);


        if ($lock->getValue() == 0) {
            $lock->setValue(1);
            $this->em->flush();
            $this->syntheticFoodCostService->getSyntheticFoodCost(
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
