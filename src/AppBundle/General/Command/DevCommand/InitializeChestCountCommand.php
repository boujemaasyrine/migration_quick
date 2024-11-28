<?php


namespace AppBundle\General\Command\DevCommand;

use AppBundle\Financial\Entity\AdminClosingTmp;
use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Financial\Service\AdministrativeClosingService;
use AppBundle\Financial\Service\ChestService;
use AppBundle\Financial\Service\TicketService;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeChestCountCommand extends ContainerAwareCommand
{


    /** @var EntityManager
     *
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
     * @var ChestService
     */
    private $chestService;
    /**
     * @var TicketService
     */
    private $tickets;

    protected function configure()
    {
        $this->setName('quick_dev:init:chest')->setDefinition([])
            ->addArgument('restaurantId', InputArgument::REQUIRED)
            ->addArgument('startDate', InputArgument::REQUIRED)
            ->addArgument('endDate', InputArgument::REQUIRED)
            ->addArgument('force', InputArgument::OPTIONAL)
            ->setDescription('Initial non closed chest date with empty values');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir') . "/../data/";
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('logger');
        $this->adminClosingService = $this->getContainer()->get('administrative.closing.service');
        $this->chestService = $this->getContainer()->get('chest.service');
        $this->tickets = $this->getContainer()->get('ticket.service');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $supportedFormat = "Y-m-d";

        $restaurantId = $input->getArgument('restaurantId');
        $restaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);

        if (!$restaurant) {
            $output->writeln("restaurant not found with id : ".$restaurantId);
            return;
        };
        $output->writeln("test dates");
        $this->logger->info("1) Test dates values", ['InitializeChestCountCommand']);

        if ($input->hasArgument('startDate') && Utilities::isValidDateFormat($input->getArgument('startDate'), $supportedFormat) && $input->hasArgument('endDate') && Utilities::isValidDateFormat($input->getArgument('endDate'), $supportedFormat)
            && $input->hasArgument('force')) {
            $startDate = $input->getArgument('startDate');
            $endDate = $input->getArgument('endDate');
            $startDate = date_create_from_format($supportedFormat, $startDate);
            $endDate = date_create_from_format($supportedFormat, $endDate);
            $endDate->setTime(0, 0, 0);
            $force = $input->getArgument('force');
        } else if ($input->hasArgument('startDate') && Utilities::isValidDateFormat($input->getArgument('startDate'), $supportedFormat) &&$input->hasArgument('endDate') && Utilities::isValidDateFormat($input->getArgument('endDate'), $supportedFormat)
            && is_null($input->getArgument('force'))) {
            $startDate = $input->getArgument('startDate');
            $startDate = date_create_from_format($supportedFormat, $startDate);
            $endDate = $input->getArgument('endDate');
            $startDate = date_create_from_format($supportedFormat, $startDate);
            $endDate->setTime(0, 0, 0);
            $force = 0;
        } else {
            $output->writeln("plz verify dates u wrote , there is something wrong ");
            return;
        }
        $output->writeln("start date is : " . $startDate->format('Y-m-d') );
        $output->writeln("end date is : " . $endDate->format('Y-m-d') );



        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        if ($force == 1) {
            $output->writeln( "1) Optionnal Step : Check for non-closused restaurants ");
            $restaurants = $this->adminClosingService->getNoCloturedRestaurants($startDate);
            $listOfIds = implode(',', array_map('intval', $restaurants));
            $output->writeln( "restaurant with id : " . $listOfIds . " hasn't been clotured yet for the " . $startDate->format('Y-m-d'));
            $output->writeln("Finished...");
            return;
        } else if (!isset($force) || $force = '') {


            $date = $this->adminClosingService->getLastNonClosedDate($restaurant);
            $startDate->setTime(0, 0, 0);
            $date->setTime(0, 0, 0);
            $diff = date_diff($date, $startDate)->format('%d');
            if ($startDate > $date) {
                $output->writeln('You should close the last ' . $diff . ' days before ' . $startDate->format('Y-m-d') );
                return;
            } else if ($startDate < $date) {
                $output->writeln('The ' . $startDate->format('Y-m-d') . ' is already closed plz verify again ' );
                return;
            } else {
                $diff = date_diff($startDate, $endDate)->format('%a');
                $progress = new ProgressBar($output, $diff);
                while ($startDate <= $endDate) {
                $output->writeln('Step 1 :  Verifying tickets for ' . $startDate->format('Y-m-d'));
                $tickets = $this->tickets->getTicketsCountByRestaurant($startDate, $restaurant);
                if ($tickets) {
                    $output->writeln( 'There is ' . $tickets . ' tickets for this day so we cannot closed it this way' );
                    return;
                } else {
                    $output->writeln( 'There is no tickets for that day ' );



                        $output->writeln( ' Step 2 : Create chest count for : ' . $startDate->format('Y-m-d') );

                        $output->writeln( 'chest count origin_restaurant_id is : ' . $restaurantId );
                        $closedDate = $this->adminClosingService->getLastNonClosedDate($restaurant);
                        $output->writeln( 'chest count closure date is : ' . $closedDate->format('Y-m-d H:i:s') );
                        $chestCount = $this->chestService->CreateChestCount($restaurant, $closedDate);
                        $this->em->persist($chestCount);
                        $this->em->flush();
                        $output->writeln( 'Step 3 :  Create Recipe Ticket: ' );

                        $date=$this->adminClosingService->getLastNonClosedDate($restaurant);
                        $recipeTicket = new RecipeTicket();
                        $recipeTicket->setDate($date)
                            ->setAmount(0)
                            ->setChestCount($chestCount)
                            ->setOwner($chestCount->getOwner())
                            ->setLabel(RecipeTicket::CACHBOX_RECIPE)
                            ->setOriginRestaurant($restaurant);
                        $this->em->persist($recipeTicket);
                        $this->em->flush();

                        //Create Administrative Closing Tmp

                        $output->writeln( 'Step 4 :  Create administrative closingTmp: ' );
                        $adminClosingTmp = new AdminClosingTmp();
                        $adminClosingTmp->setDate($date)->setCaBrutTTCRapportZ(0)
                            ->setDeposed(true);
                        $adminClosingTmp->setOriginRestaurant($restaurant);
                        $this->em->persist($adminClosingTmp);
                        $this->em->flush();


                        //Create Administrative Closing

                        $output->writeln( 'Step 5 :  Create administrative : ' );
                        $administrativeClosingEntity = new AdministrativeClosing();
                        $tomorrow = $this->adminClosingService->getLastNonClosedDate($restaurant);
                        $tomorrow = $tomorrow->add(new \DateInterval('P1D'));
                        $createdAt = $updatedAt = $tomorrow;
                        $output->writeln( 'date de creation de ladministrative closing : ' . $createdAt->format('Y-m-d 11:i:s') );
                        $administrativeClosingEntity
                            ->setDate($date)
                            ->setCreatedAt($createdAt)
                            ->setUpdatedAt($updatedAt)
                            ->setComparable(false)
                            ->setComment('covid-19')
                            ->setCreditAmount(0)
                            ->setCaBrutTTCRapportZ(0);

                        $administrativeClosingEntity->setOriginRestaurant($restaurant);
                        $this->em->persist($administrativeClosingEntity);
                        $this->em->flush();
                        $progress->advance();
                        $startDate->add(new \DateInterval('P1D'));
                        $date=$date->format('Y-m-d') ;
                        echo 'Start administrative closing '."\n".'for'." ".$date;
                        $t1 = time();
                        $cmd = "quick_dev:administrative:closing:update $restaurantId $date  $date ";
                        $t2 = time();
                        $this->logger->addInfo('Generate administrative closing  | generate time = '. ($t2 - $t1) .'seconds');
                        $this->getContainer()->get('toolbox.command.launcher')->execute($cmd);
                        $this->logger->addInfo('administrative closing finish | generate time = '. ($t2 - $t1) .'seconds');

                    }
                }
            }


        }
        
        echo "Finished\n";
        $this->logger->info("Finished", ['InitializeChestCountCommand']);
    }
}