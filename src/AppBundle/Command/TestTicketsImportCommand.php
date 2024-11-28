<?php

namespace AppBundle\Command;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Financial\Entity\TicketLineTemp;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Financial\Entity\TicketPaymentTemp;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;

class TestTicketsImportCommand extends ContainerAwareCommand
{

    private $dataDir;

    private $ticketsFileName;

    private $paymentsFileNames;

    private $ticketDetailsFileNames;

    private $adminClosingService;

    private $key;

    /**
     * @var Parameter
     */
    private $param;

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var Logger
     */
    private $logger;

    private $typeRestaurant;
    /**
     * @var Restaurant
     */
    private $currentRestaurant;

    private $ticketArray;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('test:import:tickets')
            ->addOption(
                'typeRestaurant',
                't',
                InputOption::VALUE_REQUIRED,
                'The type of restaurant.',
                ''
            )
            ->addOption(
                'startDate',
                'sd',
                InputOption::VALUE_OPTIONAL,
                'the start Date'
            )
            ->addOption(
                'endDate',
                'ed',
                InputOption::VALUE_OPTIONAL,
                'the end Date'
            )
            ->addOption(
                'restaurantId',
                'i',
                InputOption::VALUE_OPTIONAL,
                'The restaurant id.',
                ''
            )
            ->addOption(
                'brut',
                'b',
                InputOption::VALUE_OPTIONAL,
                'test brut TTC difference flag'
            )
            ->setDescription('Import Wynd Tickets in the file.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->em = $this->getContainer()->get(
            'doctrine.orm.default_entity_manager'
        );
        $this->logger = $this->getContainer()->get('logger');
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')
            ."/../data/import/";
        $this->adminClosingService = $this->getContainer()->get(
            'administrative.closing.service'
        );
        $this->ticketsFileName = '';
        $this->paymentsFileNames = [];
        $this->ticketDetailsFileNames = [];
        $this->currentRestaurant = null;
        $this->ticketArray = array();
        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->typeRestaurant = $input->getOption('typeRestaurant');
        if ($this->typeRestaurant != 'quick' && $this->typeRestaurant != 'bk') {
            echo "the type of restaurant must be 'quick' or 'bk' only";

            return;
        }

        if ($input->hasOption('startDate')
            && !empty(
            $input->getOption(
                'startDate'
            )
            )
            && $input->hasOption('endDate')
            && !empty($input->getOption('endDate'))
        ) {

            $startDate = $input->getOption('startDate');

            $endDate = $input->getOption('endDate');

        } else {
            $startDate = null;
            $endDate = null;
        }


        if ($input->hasOption('restaurantId')
            && !empty(
            $input->getOption(
                'restaurantId'
            )
            )
        ) {
            $restaurantId = $input->getOption('restaurantId');
            $this->currentRestaurant = $this->em->getRepository(
                Restaurant::class
            )->find($restaurantId);
            if ($this->currentRestaurant == null) {
                $this->logger->addAlert(
                    'Restaurant not found with id: '.$restaurantId,
                    ['quick:financial:revenue:import']
                );
                echo 'Restaurant not found with id: '.$restaurantId;

                return;
            }

            if ($input->hasOption('brut') && !empty($input->getOption('brut'))
                && $input->getOption('brut') == 'true'
            ) {

                $this->checkTTCDiff(
                    $this->currentRestaurant,
                    $startDate,
                    $endDate
                );
            } else {
                $this->ticketsFileName = $this->dataDir.'flux_'
                    .$this->currentRestaurant->getCode().'.json';
                $this->em->getConnection()->getConfiguration()->setSQLLogger(
                    null
                );
                $time_start = microtime(true);
                $output->writeln(
                    "Loading tickets for restaurant : "
                    .$this->currentRestaurant->getCode()
                );

                try {

                    $file = fopen($this->ticketsFileName, 'r');

                    if (!$file) {
                        $output->writeln(
                            "Cannot open file $this->ticketsFileName"
                        );

                        return;
                    }

                    $data = file_get_contents($this->ticketsFileName);
                    $json = json_decode($data, true);
                    if (!$json) {
                        $output->writeln(
                            "Data in the $this->ticketsFileName is not JSON format !"
                        );

                        return;
                    }
                    $ticketsList = $json['data'];
                    unset($json);
                    unset($data);
                    $output->writeln(
                        "Total tickets in file : ".count($ticketsList)
                    );
                    $notFoundTickets = 0;
                    $foundTickets = 0;
                    $i = 0;
                    $notEqualTtcTickets = array();
                    $notEqualHtTickets = array();
                    $notEqualHtLinesTickets = array();
                    $notEqualTtcLinesTickets = array();
                    $notEqualTvaLinesTickets = array();
                    $notEqualTTCTicketTicketLines = array();
                    $someTVAFlux = 0;
                    $someTVA = 0;
                    $someTVADiscount = 0;
                    $sumHTTicket = 0;
                    $sumHTTicket_flux = 0;
                    foreach ($ticketsList as $ticket) {
                        $existingTicket = $this->em->getRepository(
                            'Financial:Ticket'
                        )->findOneBy(
                            [
                                'invoiceNumber' => strval(
                                    $ticket['order']['invoiceNumber']
                                ),
                                'date' => new \DateTime(
                                    $ticket['order']['date']
                                ),
                                'originRestaurant' => $this->currentRestaurant,
                            ]
                        );
                        /*if(count($ticket['order']['discounts'])>0 && count($ticket['payments'])>0 and $ticket['order']['discounts'][0]['id']==10){

                            $output->writeln("Ticket : ".strval($ticket['order']['invoiceNumber']).' with BR and payment => '.number_format($ticket['order']['invoiceNumber'],0,'.','') );
                        }*/
                        if (!$existingTicket) {
                            $output->writeln(
                                "Ticket : ".strval(
                                    $ticket['order']['invoiceNumber']
                                ).' not exist in database!'
                            );
                            $notFoundTickets++;
                        } else {
                            // $output->writeln("Ticket : ".strval($ticket['order']['invoiceNumber']).' loaded ==> ID = '.$existingTicket->getId());
                            $foundTickets++;
                            if ($ticket['order']['total_ttc']
                                != $existingTicket->getTotalTTC()
                            ) {
                                $notEqualTtcTickets[] = "ID="
                                    .$existingTicket->getId()
                                    ." | invoiceNumber "
                                    .$existingTicket->getInvoiceNumber()
                                    ." | TotalTtc ticket = "
                                    .$existingTicket->getTotalTTC()
                                    ." | totalTTC flux : "
                                    .$ticket['order']['total_ttc'];
                            }
                            if ($ticket['order']['total_ht']
                                != $existingTicket->getTotalHT()
                            ) {
                                $notEqualHtTickets[] = "ID="
                                    .$existingTicket->getId()
                                    ." | invoiceNumber "
                                    .$existingTicket->getInvoiceNumber()
                                    ." | TotalHT ticket = "
                                    .$existingTicket->getTotalHT()
                                    ." | totalHT flux : "
                                    .$ticket['order']['total_ht'];
                            }
                            $b = false;
                            foreach ($existingTicket->getPayments() as $payment)
                            {
                                if ($payment->getIdPayment() == '5') {
                                    $b = true;
                                    break;
                                }
                            }
                            if (!$b) {
                                $sumHTTicket += $existingTicket->getTotalHT();
                            }
                            $sumHTTicket_flux += $ticket['order']['total_ht'];
                            $ticketLineTTC = 0;
                            $ticketLineHT = 0;
                            $ticketLineTVA = 0;
                            $ticketLineDiscountTTC = 0;
                            /**
                             * @var TicketLine $line
                             */
                            foreach ($existingTicket->getLines() as $line) {
                                $ticketLineHT += $line->getTotalHT();
                                $ticketLineTVA += $line->getTotalTVA();
                                $ticketLineTTC += $line->getTotalTTC();
                                $ticketLineDiscountTTC += $line->getDiscountTtc(
                                );
                                $someTVA += $line->getTotalTVA();
                            }
                            $ticketLineTTC_flux = 0;
                            $ticketLineHT_flux = 0;
                            $ticketLineTVA_flux = 0;
                            foreach ($ticket['lines'] as $line) {
                                $ticketLineHT_flux += $line['total_ht'];
                                $ticketLineTVA_flux += $line['total_tva'];
                                $ticketLineTTC_flux += $line['total_TTC'];
                                if ($line['discount_id'] == 10) {
                                    $someTVAFlux += abs($line['discount_tva']);
                                } else {
                                    $someTVAFlux += $line['total_tva'];
                                }

                                $someTVADiscount += $line['discount_tva'];
                            }
                            if (ABS($ticketLineTTC - $ticketLineTTC_flux)
                                > 0.01
                            ) {
                                $notEqualTtcLinesTickets[] = "ID="
                                    .$existingTicket->getId()
                                    ." | invoiceNumber "
                                    .$existingTicket->getInvoiceNumber()
                                    ." | TotalTTC ticket lines = "
                                    .$ticketLineTTC." | totalTTC flux : "
                                    .$ticketLineTTC_flux;
                            }
                            if (ABS($ticketLineHT - $ticketLineHT_flux)
                                > 0.01
                            ) {
                                $notEqualHtLinesTickets[] = "ID="
                                    .$existingTicket->getId()
                                    ." | invoiceNumber "
                                    .$existingTicket->getInvoiceNumber()
                                    ." | TotalHT ticket lines = ".$ticketLineHT
                                    ." | totalHT flux : ".$ticketLineHT_flux;
                            }
                            if (ABS($ticketLineTVA - $ticketLineTVA_flux)
                                > 0.01
                            ) {
                                $notEqualTvaLinesTickets[] = "ID="
                                    .$existingTicket->getId()
                                    ." | invoiceNumber "
                                    .$existingTicket->getInvoiceNumber()
                                    ." | TotalTVA ticket lines = "
                                    .$ticketLineTVA." | totalTVA flux : "
                                    .$ticketLineTVA_flux;
                            }

                            /**
                             * @var Ticket $existingTicket
                             */
                            if (ABS(
                                    ($existingTicket->getTotalTTC() + ABS(
                                            $ticketLineDiscountTTC
                                        )) - $ticketLineTTC
                                ) > 0.01
                            ) {
                                $brutTicket = $existingTicket->getTotalTTC()
                                    + ABS($ticketLineDiscountTTC);
                                $notEqualTTCTicketTicketLines[] = "ID="
                                    .$existingTicket->getId()
                                    ." | invoiceNumber "
                                    .$existingTicket->getInvoiceNumber()
                                    ." | TotalTTC Brut de ticket = ".$brutTicket
                                    ." |  somme totalTTC des ticketLines : "
                                    .$ticketLineTTC;
                            }
                        }
                        $i++;

                    }
                    $output->writeln(
                        "------------------------------------------------------"
                    );
                    $output->writeln("===> Total ticket tested = ".$i);
                    $output->writeln(
                        $notFoundTickets." tickets not found in database."
                    );
                    $output->writeln(
                        $foundTickets." tickets found in database."
                    );
                    $output->writeln(
                        "-> SUM HT Tickets =".$sumHTTicket." | SUM HT Flux = "
                        .$sumHTTicket_flux
                    );
                    $output->writeln(
                        "-> SUM TVA Tickets =".$someTVA." | SUM TVA Flux = "
                        .$someTVAFlux."  | SUM Disount TVA = ".$someTVADiscount
                    );
                    $output->writeln(
                        "------------------------------------------------------"
                    );
                    $output->writeln(
                        "--> Not equals TotalTTC tickets : ".count(
                            $notEqualTtcTickets
                        )
                    );
                    foreach ($notEqualTtcTickets as $row) {
                        $output->writeln($row);
                    }
                    $output->writeln(
                        "------------------------------------------------------"
                    );
                    $output->writeln(
                        "--> Not equals TotalHT tickets : ".count(
                            $notEqualHtTickets
                        )
                    );
                    foreach ($notEqualHtTickets as $row) {
                        $output->writeln($row);
                    }
                    $output->writeln("**********************************");
                    $output->writeln(
                        "--> Not equals Lines TotalHT tickets : ".count(
                            $notEqualHtLinesTickets
                        )
                    );
                    foreach ($notEqualHtLinesTickets as $row) {
                        $output->writeln($row);
                    }
                    $output->writeln(
                        "------------------------------------------------------"
                    );
                    $output->writeln(
                        "--> Not equals Lines TotalTTC tickets : ".count(
                            $notEqualTtcLinesTickets
                        )
                    );
                    foreach ($notEqualTtcLinesTickets as $row) {
                        $output->writeln($row);
                    }
                    $output->writeln(
                        "------------------------------------------------------"
                    );
                    $output->writeln(
                        "--> Not equals Lines TotalTVA tickets : ".count(
                            $notEqualTvaLinesTickets
                        )
                    );
                    foreach ($notEqualTvaLinesTickets as $row) {
                        $output->writeln($row);
                    }


                    $output->writeln(
                        "------------------------------------------------------"
                    );
                    $output->writeln(
                        "--> Not equals Total TTC BRUT between ticket and ticketLine: "
                        .count($notEqualTTCTicketTicketLines)
                    );
                    foreach ($notEqualTTCTicketTicketLines as $row) {
                        $output->writeln($row);
                    }

                } catch (\Exception $e) {
                    $this->logger->addError(
                        'Importing tickets exception :'.$e->getMessage(),
                        ['ImportTicketsFromCsvCommand']
                    );
                    echo 'Importing tickets exception :'.$e->getMessage();
                    throw $e;
                }
            }


        } else {

            $restaurants = $this->em->getRepository(Restaurant::class)
                ->getOpenedRestaurants();

            /** @var Restaurant $restaurant */

            foreach ($restaurants as $restaurant) {

                $this->checkTTCDiff($restaurant, $startDate, $endDate);

            }


        }


    }


    public function checkTTCDiff(Restaurant $restaurant, $startDate, $endDate)
    {

        $supportedFormat = "Y-m-d";


        if (!is_null($startDate) && !is_null($endDate)
            && Utilities::isValidDateFormat($startDate, $supportedFormat)
            && Utilities::isValidDateFormat($endDate, $supportedFormat)
        ) {
            $startDate = date_create_from_format($supportedFormat, $startDate);
            $endDate = date_create_from_format($supportedFormat, $endDate);
        } else {
            $startDate = $this->adminClosingService->getLastWorkingEndDate(
                $restaurant
            );
            $endDate = $this->adminClosingService->getLastWorkingEndDate(
                $restaurant
            );
        }


        for ($i = 0; $i <= $endDate->diff($startDate)->days; $i++) {
            $anomalies = 0;
            $date = Utilities::getDateFromDate($startDate, $i);
            $diffAmount = 0;
            echo " start checking tickets for the restaurant "
                .$restaurant->getCode()." on date ".date_format($date, 'Y-m-d')
                ."\n";

            $this->logger->addInfo(
                "checking tickets for the restaurant ".$restaurant->getCode()
                ."on date ".date_format($date, 'Y-m-d'),
                ['test:tickets:imported']
            );

            $tickets = $this->em->getRepository(Ticket::class)->findBy(
                array(
                    'originRestaurant' => $restaurant,
                    'date'             => $date,
                    'status'           => 3,
                )
            );


            foreach ($tickets as $ticket) {
                $ticketLineTTC = 0;
                $ticketLineDiscountTTC = 0;

                /**
                 * @var TicketLine $line
                 */
                foreach ($ticket->getLines() as $line) {
                    $ticketLineTTC += $line->getTotalTTC();
                    $ticketLineDiscountTTC += $line->getDiscountTtc();

                }

                if (ABS(
                        ($ticket->getTotalTTC() + ABS($ticketLineDiscountTTC))
                        - $ticketLineTTC
                    ) > 0.01
                ) {
                    $brutTicket = $ticket->getTotalTTC() + ABS(
                            $ticketLineDiscountTTC
                        );

                    echo  "restaurant ".$restaurant->getCode()." Ticket ID="
                        .$ticket->getId()." | invoiceNumber "
                        .$ticket->getInvoiceNumber()
                        ." | TotalTTC Brut de ticket = ".$brutTicket
                        ." |  somme totalTTC des ticketLines : ".$ticketLineTTC. "\n";
                    $this->logger->addDebug(
                        "restaurant ".$restaurant->getCode()." Ticket ID="
                        .$ticket->getId()." | invoiceNumber "
                        .$ticket->getInvoiceNumber()
                        ." | TotalTTC Brut de ticket = ".$brutTicket
                        ." |  somme totalTTC des ticketLines : ".$ticketLineTTC,
                        ['test:tickets:imported']
                    );
                    $diffAmount += $ticketLineTTC - $brutTicket;
                    $anomalies++;
                }

            }

             echo  'number of tickets having a non equal total TTC between ticket and ticketLine on restaurant '
                 .$restaurant->getCode().' on date'.date_format($date, 'Y-m-d')
                 ."= ".$anomalies. "\n";

            $this->logger->addDebug(
                'number of tickets having a non equal total TTC between ticket and ticketLine on restaurant '
                .$restaurant->getCode().' on date'.date_format($date, 'Y-m-d')
                ."= ".$anomalies,
                ['test:tickets:imported']
            );

            if ($diffAmount != 0) {

                echo 'amount of difference for restaurant '.$restaurant->getCode(
                    ).' on date '.date_format($date, 'Y-m-d').'= '.$diffAmount. "\n";
                $this->logger->addInfo(
                    'amount of difference for restaurant '.$restaurant->getCode(
                    ).' on date '.date_format($date, 'Y-m-d').'= '.$diffAmount,
                    ['test:tickets:imported']
                );
            }

            echo " finished checking tickets for the restaurant "
                .$restaurant->getCode()." on date ".date_format($date, 'Y-m-d')
                ."\n";

            $this->logger->addInfo(
                "finished checking tickets for the restaurant ".$restaurant->getCode()
                ." on date ".date_format($date, 'Y-m-d'),
                ['test:tickets:imported']
            );
        }


    }

    public function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');

        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).' '
            .$unit[$i];
    }


}