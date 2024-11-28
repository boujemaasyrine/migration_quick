<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 18/07/2018
 * Time: 14:55
 */

namespace AppBundle\Command;


use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Report\Service\ReportFoodCostSyntheticV2;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SaasSyntheticFoodcostCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @var ReportFoodCostSyntheticV2 $syntheticFoodCostService
     */
    private $syntheticFoodCostService;


    protected function configure()
    {
        $this->setName("saas:report:synthetic:foodcost")
            ->setDescription(
                'Auto generate Synthetique FoodCost report for all restaurants'
            )
            ->addArgument('startDate', InputArgument::OPTIONAL)
            ->addArgument('endDate', InputArgument::OPTIONAL)
            ->addArgument('restaurantId', InputArgument::OPTIONAL);

    }


    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {
        parent::initialize(
            $input,
            $output
        );

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $this->syntheticFoodCostService = $this->getContainer()->get(
            'report.foodcost.synthetic.service'
        );

        $this->logger = $this->getContainer()->get('logger');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->logger->addInfo(
            'Synthetic FoodCost cron job started',
            ['saas:synthetic:foodcost:cron']
        );

        $restaurant = null;

        if ($input->hasArgument('restaurantId')
            && !empty(
            $input->getArgument(
                'restaurantId'
            )
            )
        ) {

            $restaurantId = $input->getArgument('restaurantId');

            $restaurant = $this->em->getRepository(Restaurant::class)->find(
                $restaurantId
            );

            if ($restaurant == null) {


                $this->logger->addDebug(
                    'restaurant not found with Id '.$restaurantId,
                    ['saas:synthetic:foodcost:cron']
                );

                echo 'Restaurant not found with id: '.$restaurantId;

                return;


            }
        }


        //init params

        $today = new \DateTime();


        if ($input->hasArgument('startDate')
            && !empty(
            $input->getArgument(
                'startDate'
            )
            )
        ) {
            $startDate = $input->getArgument('startDate');
            $startDate = date_create_from_format('Y-m-d', $startDate);
        } else {
            $startDate = Utilities::getDateFromDate($today, -7);
            echo "No Start Date , start date intitalized to "
                .$startDate->format('d/m/Y')."\n";

            $this->logger->addDebug(
                'No start date, start date initialized to '.$startDate->format(
                    'd/m/Y'
                ),
                ['saas:synthetic:foodcost:cron']
            );


        }

        if ($input->hasArgument('endDate')
            && !empty(
            $input->getArgument(
                'endDate'
            )
            )
        ) {
            $endDate = $input->getArgument('endDate');
            $endDate = date_create_from_format('Y-m-d', $endDate);
        } else {
            $endDate = Utilities::getDateFromDate($today, -1);
            echo "No End Date , end date intitalized to ".$endDate->format(
                    'd/m/Y'
                )."\n";

            $this->logger->addDebug(
                'No End date , end date initialized to '.$endDate->format(
                    'd/m/Y'
                ),
                ['saas:synthetic:foodcost:cron']
            );
        }


        $force = 1;

        if ($restaurant == null) {

            $restaurants = $this->em->getRepository(Restaurant::class)
                ->getOpenedRestaurants();

            /**
             * @var Restaurant $restaurant
             */
            foreach ($restaurants as $restaurant) {
                $this->logger->addInfo(
                    'generating Synthetic Foodcost for restaurant '
                    .$restaurant->getCode(),
                    ['saas:synthetic:foodcost:cron']
                );
                echo 'generating Synthetique FoodCost for restaurant '
                    .$restaurant->getCode()."\n";

                echo "Memory Usage: ".(memory_get_usage() / 1048576)." MB \n";

                $this->logger->addDebug(
                    "memory usage of ".(memory_get_usage() / 1048576)." MB",
                    ['saas:synthetic:foodcost:cron']
                );

                $this->generateSyntheticFoodCost(
                    $restaurant->getId(),
                    $startDate,
                    $endDate,
                    $force
                );


                echo "Memory Usage: ".(memory_get_usage() / 1048576)." MB \n";

                $this->logger->addDebug(
                    "memory usage of ".(memory_get_usage() / 1048576)." MB",
                    ['saas:synthetic:foodcost:cron']
                );

                $this->logger->addInfo(
                    'end of synthetic food cost generation for restaurant '
                    .$restaurant->getCode(),
                    ['saas:synthetic:foodcost:cron']
                );


            }

        } else {
            $this->logger->addInfo(
                'generating Synthetic Foodcost for restaurant '
                .$restaurant->getCode()."\n",
                ['saas:synthetic:foodcost:cron']
            );
            echo 'generating Synthetique FoodCost for restaurant '
                .$restaurant->getCode();


            echo "Memory Usage: ".(memory_get_usage() / 1048576)." MB \n";

            $this->logger->addDebug(
                "memory usage of ".(memory_get_usage() / 1048576)." MB",
                ['saas:synthetic:foodcost:cron']
            );

            $this->generateSyntheticFoodCost(
                $restaurant->getId(),
                $startDate,
                $endDate,
                $force
            );
            $this->logger->addInfo(
                'end of synthetic food cost generation for restaurant '
                .$restaurant->getCode(),
                ['saas:synthetic:foodcost:cron']
            );

            echo "Memory Usage: ".(memory_get_usage() / 1048576)." MB \n";

            $this->logger->addDebug(
                "memory usage of ".(memory_get_usage() / 1048576)." MB",
                ['saas:synthetic:foodcost:cron']
            );

        }

        $this->logger->addInfo(
            'Synthetic FoodCost cron job ended',
            ['saas:synthetic:foodcost:cron']
        );

    }


    public function generateSyntheticFoodCost(
        $restaurantId,
        $startDate,
        $endDate,
        $force
    ) {

        $lock = $this->syntheticFoodCostService->checkLocked($restaurantId);


        if ($lock->getValue() == 0) {
            $lock->setValue(1);
            $this->em->flush();

            $this->syntheticFoodCostService->getSyntheticFoodCost(
                $restaurantId,
                $startDate,
                $endDate,
                null,
                $force
            );


            $lock->setValue(0);
            $this->em->flush();

        } else {
            $this->logger->addDebug(
                'another process have the lock',
                ['saas:synthetic:foodcost:cron']
            );
            echo "\n Un process est encours \n";
        }


    }


}