<?php

namespace AppBundle\Command;

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketIntervention;
use AppBundle\Financial\Entity\TicketInterventionSub;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportBoTicketsDataCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    private $dataDir;

    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:import:bo:tickets:data')
            ->addArgument('restaurantCode', InputArgument::OPTIONAL)
            ->setDescription('Import restaurant tickets data form csv files exported by a BO instance.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir') . "/../data/import/saas/";
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->logger = $this->getContainer()->get('monolog.logger.import_commands');

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ($input->getArgument('restaurantCode')) {
            $restaurantCode = trim($input->getArgument('restaurantCode'));
        } else {
            $helper = $this->getHelper('question');
            $question = new Question(
                'Please enter restaurant code (found at the end of the csv file name : tickets_restaurant_xxxx.csv ) :'
            );
            $question->setValidator(
                function ($answer) {
                    if (!is_string($answer) || strlen($answer) < 1) {
                        throw new \RuntimeException(
                            'Please enter the restaurnat code!'
                        );
                    }
                    return trim($answer);
                }
            );
            $restaurantCode = $helper->ask($input, $output, $question);
        }
        $filesName = array("tickets", "ticketsInterventions", "ticketsInterventionsSub", "ticketsLines", "ticketsPayment");
        //check the existance of all needed files
        foreach ($filesName as $name) {
            $filename = $name . "_restaurant_" . $restaurantCode . ".csv";
            $filePath = $this->dataDir . $filename;

            if (!file_exists($filePath)) {
                $output->writeln("No import file with the '" . $restaurantCode . "' restaurant code found !");
                $output->writeln("Please provide all needed files !");
                return;
            }
        }

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        //$this->em->getConnection()->beginTransaction();
        /************ Start the import process *****************/
        try {

            $restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($restaurantCode);
            if (!$restaurant) {
                $output->writeln("No restaurant with the '" . $restaurantCode . "' exist! Command failed... ");
                $output->writeln("->Please add this restaurant first.");
                return;
            }
            $restaurantId = $restaurant->getId();
            $output->writeln("Restaurant " . $restaurant->getName() . " tickets data import started...");


            ///////////////////////////////////////////////////////////////////////
            /// tickets import
            $filename = "tickets_restaurant_" . $restaurantCode . ".csv";
            $filePath = $this->dataDir . $filename;

            if (!file_exists($filePath)) {
                $output->writeln("No import file with the '" . $restaurantCode . "' restaurant code found !");
                return;
            }
            $file = new \SplFileObject($filePath, 'r');
            $file->seek(PHP_INT_MAX);
            $linesCount = $file->key() - 1;
            $progress = new ProgressBar($output, $linesCount);

            if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier
                $output->writeln("Start importing tickets...");
                $addedTickets = 0;
                $skippedTickets = 0;
                $i = 0;// number of iteration
                $j=0;// counter used for binding values in sql query
                $header = fgets($handle);//load the header
                $raw_query="INSERT INTO ticket(
	id, origin_restaurant_id, type, cancelled_flag, num, startdate, enddate, invoicenumber, status, invoicecancelled, totalht, totalttc, paid, deliverytime, operator, operatorname, responsible, workstation, workstationname, originid, origin, destinationid, destination, entity, customer, date, counted, external_id, counted_canceled, created_at, updated_at, synchronized, import_id)
	VALUES ";
                $query_values="";

                $progress->start();
                while (($data = fgetcsv($handle, 0, ";")) !== false) { // Eléments séparés par un point-virgule, à modifier si necessaire
                    $i++;
                    $j++;
                    $ticketData[] = array(
                        'id' => $data[0],
                        'type' => $data[1],
                        'cancelledFlag' => boolval($data[2]) ? 1 : 0,
                        'num' => $data[3] ? $data[3] : null,
                        'startDate' => $data[4] ? $data[4] : null ,
                        'endDate' => $data[5] ? $data[5] : null ,
                        'invoiceNumber' => $data[6] ? $data[6] : $data[0]."_".$restaurantCode,
                        'status' => $data[7],
                        'invoiceCancelled' => $data[8],
                        'totalHT' => floatval($data[9]),
                        'totalTTC' => floatval($data[10]),
                        'paid' => boolval($data[11])? 1 : 0,
                        'deliveryTime' => $data[12] ? $data[12] : null ,
                        'operator' => intval($data[13]),
                        'operatorName' => $data[14],
                        'responsible' => $data[15],
                        'workstation' => intval($data[16]),
                        'workstationName' => $data[17],
                        'originId' => intval($data[18]),
                        'origin' => $data[19],
                        'destinationId' => intval($data[20]),
                        'destination' => $data[21],
                        'entity' => $data[22] ? $data[22] : null,
                        'customer' => $data[23] ? $data[23] : null,
                        'date' => $data[24] ,
                        'counted' => boolval($data[25])? 1 : 0,
                        'externalId' => $data[26],
                        'countedCanceled' => boolval($data[27])? 1 : 0,
                        'createdAt' => $data[28] ,
                        'updatedAt' => $data[29] ,
                        'synchronized' => boolval($data[30])? 1 : 0,
                        'lines' => explode(',', $data[31])
                    );

                    $query_values .= "(NEXTVAL('ticket_id_seq') , :origin_restaurant_id, :type$j, :cancelled_flag$j, :num$j, :startdate$j, :enddate$j, :invoicenumber$j, :status$j, :invoicecancelled$j, :totalht$j, :totalttc$j, :paid$j, :deliverytime$j, :operator$j, :operatorname$j, :responsible$j, :workstation$j, :workstationname$j, :originid$j, :origin$j, :destinationid$j, :destination$j, :entity$j, :customer$j, :date$j, :counted$j, :external_id$j, :counted_canceled$j, :created_at$j, :updated_at$j, :synchronized$j, :import_id$j ),";

                    if (($i % 100) === 0 || $i >= $linesCount) {
                        $this->em->getConnection()->beginTransaction();
                        $query_values=rtrim($query_values,',');
                        $sql=$raw_query.$query_values." ON CONFLICT (import_id) DO NOTHING ;  ";
                        $statement = $this->em->getConnection()->prepare($sql);
                        // Set parameters
                        $c=1;
                        foreach ($ticketData as  $ticket){
                            $statement->bindValue('origin_restaurant_id', $restaurantId);
                            $statement->bindValue('type'.$c, $ticket['type']);
                            $statement->bindValue('cancelled_flag'.$c, $ticket['cancelledFlag']);
                            $statement->bindValue('num'.$c, $ticket['num']);
                            $statement->bindValue('startdate'.$c, $ticket['startDate']);
                            $statement->bindValue('enddate'.$c, $ticket['endDate']);
                            $statement->bindValue('invoicenumber'.$c, $ticket['invoiceNumber']);
                            $statement->bindValue('status'.$c, $ticket['status']);
                            $statement->bindValue('invoicecancelled'.$c, $ticket['invoiceCancelled']);
                            $statement->bindValue('totalht'.$c, $ticket['totalHT']);
                            $statement->bindValue('totalttc'.$c, $ticket['totalTTC']);
                            $statement->bindValue('paid'.$c, $ticket['paid']);
                            $statement->bindValue('deliverytime'.$c, $ticket['deliveryTime']);
                            $statement->bindValue('operator'.$c, $ticket['operator']);
                            $statement->bindValue('operatorname'.$c, $ticket['operatorName']);
                            $statement->bindValue('responsible'.$c, $ticket['responsible']);
                            $statement->bindValue('workstation'.$c, $ticket['workstation']);
                            $statement->bindValue('workstationname'.$c, $ticket['workstationName']);
                            $statement->bindValue('originid'.$c, $ticket['originId']);
                            $statement->bindValue('origin'.$c, $ticket['origin']);
                            $statement->bindValue('destinationid'.$c, $ticket['destinationId']);
                            $statement->bindValue('destination'.$c, $ticket['destination']);
                            $statement->bindValue('entity'.$c, $ticket['entity']);
                            $statement->bindValue('customer'.$c, $ticket['customer']);
                            $statement->bindValue('date'.$c, $ticket['date']);
                            $statement->bindValue('counted'.$c, $ticket['counted']);
                            $statement->bindValue('external_id'.$c, $ticket['externalId']);
                            $statement->bindValue('counted_canceled'.$c, $ticket['countedCanceled']);
                            $statement->bindValue('created_at'.$c, $ticket['createdAt']);
                            $statement->bindValue('updated_at'.$c, $ticket['updatedAt']);
                            $statement->bindValue('synchronized'.$c, $ticket['synchronized']);
                            $statement->bindValue('import_id'.$c, $ticket['id']. "_" . $restaurantCode);

                            $c++;
                            $addedTickets++;
                        }

                        $statement->execute();
                        $this->em->getConnection()->commit();
                        $query_values="";
                        $ticketData=null;
                        unset($ticketData);
                        $this->em->clear();
                        gc_collect_cycles();
                        $j=0;

                    }

                    $progress->advance();

                }

                fclose($handle);
                $this->em->clear();
                $progress->finish();
                $output->writeln("");
                $output->writeln("=> Total tickets treated = " . $i);
                $output->writeln("--> " . $addedTickets . " Tickets imported for restaurant " . $restaurant->getName());
               // $output->writeln("--> " . $skippedTickets . " Tickets skipped for restaurant " . $restaurant->getName());
                $this->showMemoryUsage();

            } else {
                $output->writeln("Cannot open the Tickets csv file! Exit command...");
                return;
            }


            ///////////////////////////////////////////////////////////////////////
            /// tickets lines
            $filename = "ticketsLines_restaurant_" . $restaurantCode . ".csv";
            $filePath = $this->dataDir . $filename;

            if (!file_exists($filePath)) {
                $output->writeln("No import file with the '" . $restaurantCode . "' restaurant code found !");
                return;
            }
            $file = new \SplFileObject($filePath, 'r');
            $file->seek(PHP_INT_MAX);
            $linesCount = $file->key() - 1;
            $progress = new ProgressBar($output, $linesCount);

            if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier
                $output->writeln("Start Importing Tickets Lines...");
                $addedTicketsLines = 0;
                $skippedTicketsLines = 0;
                $i = 0;// number of iteration
                $j=0;// counter used for binding values in sql query
                $header = fgets($handle);//load the header
                $progress->start();
                $raw_query = "INSERT INTO ticket_line (id, ticket_id,line, qty, price, totalht, totaltva, totalttc, category, division, product, label, description, plu, combo, composition, parentline, tva, is_discount, revenue_price, mvmt_recorded, discount_id, discount_code, discount_label, discount_ht, discount_tva, discount_ttc, import_id) VALUES ";
                $query_values="";
                while (($data = fgetcsv($handle, 0, ";")) !== false) { // Eléments séparés par un point-virgule, à modifier si necessaire
                    $i++;
                    $j++;
                    $ticketLineData[] = array(
                        'id' => $data[0],
                        'line' => $data[1] ? (int)$data[1] : null,
                        'qty' => $data[2] ? (int)$data[2] : null,
                        'price' =>  floatval($data[3]) ,
                        'totalHT' => floatval($data[4]) ,
                        'totalTVA' => floatval($data[5]) ,
                        'totalTTC' =>  floatval($data[6]) ,
                        'category' => $data[7],
                        'division' => $data[8] ? (int)$data[8] : null,
                        'product' => $data[9] ? (int)$data[9] : null,
                        'label' => $data[10],
                        'description' => $data[11],
                        'plu' => $data[12],
                        'combo' => boolval($data[13])? 1 : 0,
                        'composition' => boolval($data[14]) ? 1 : 0,
                        'parentLine' => $data[15] ? (int)$data[15] : null,
                        'tva' => floatval($data[16]) ,
                        'isDiscount' => boolval($data[17]) ? 1 : 0,
                        'revenuePrice' =>  floatval($data[18]) ,
                        'mvmtRecorded' => boolval($data[19]) ? 1 : 0,
                        'discountId' => $data[20],
                        'discountCode' => $data[21],
                        'discountLabel' => $data[22],
                        'discountHt' =>  floatval($data[23]),
                        'discountTva' =>  floatval($data[24]),
                        'discountTtc' => floatval($data[25]),
                        'ticket_id' => $data[26],
                        'discount_container_id' => $data[27]
                    );

                    $query_values .= "(NEXTVAL('ticket_line_id_seq') ,(SELECT id FROM ticket where import_id = :ticket_import_id$j ), :line$j, :qty$j, :price$j, :totalht$j, :totaltva$j, :totalttc$j, :category$j, :division$j, :product$j, :label$j, :description$j, :plu$j, :combo$j, :composition$j, :parentline$j, :tva$j, :is_discount$j, :revenue_price$j, :mvmt_recorded$j, :discount_id$j, :discount_code$j, :discount_label$j, :discount_ht$j, :discount_tva$j, :discount_ttc$j, :import_id$j ),";

                    if (($i % 100) === 0 || $i >= $linesCount) {
                        $this->em->getConnection()->beginTransaction();
                        $query_values=rtrim($query_values,',');
                        $sql=$raw_query.$query_values." ON CONFLICT (import_id) DO NOTHING ;  ";
                        $statement = $this->em->getConnection()->prepare($sql);
                        // Set parameters
                        $c=1;
                        foreach ($ticketLineData as  $line){
                            $statement->bindValue('line'.$c, $line['line']);
                            $statement->bindValue('qty'.$c, $line['qty']);
                            $statement->bindValue('price'.$c, $line['price']);
                            $statement->bindValue('totalht'.$c, $line['totalHT']);
                            $statement->bindValue('totaltva'.$c, $line['totalTVA']);
                            $statement->bindValue('totalttc'.$c, $line['totalTTC']);
                            $statement->bindValue('category'.$c, $line['category']);
                            $statement->bindValue('division'.$c, $line['division']);
                            $statement->bindValue('product'.$c, $line['product']);
                            $statement->bindValue('label'.$c, $line['label']);
                            $statement->bindValue('description'.$c, $line['description']);
                            $statement->bindValue('plu'.$c, $line['plu']);
                            $statement->bindValue('combo'.$c, $line['combo']);
                            $statement->bindValue('composition'.$c, $line['composition']);
                            $statement->bindValue('parentline'.$c, $line['parentLine']);
                            $statement->bindValue('tva'.$c, $line['tva']);
                            $statement->bindValue('is_discount'.$c, $line['isDiscount']);
                            $statement->bindValue('revenue_price'.$c, $line['revenuePrice']);
                            $statement->bindValue('mvmt_recorded'.$c, $line['mvmtRecorded']);
                            $statement->bindValue('discount_id'.$c, $line['discountId']);
                            $statement->bindValue('discount_code'.$c, $line['discountCode']);
                            $statement->bindValue('discount_label'.$c, $line['discountLabel']);
                            $statement->bindValue('discount_ht'.$c, $line['discountHt']);
                            $statement->bindValue('discount_tva'.$c, $line['discountTva']);
                            $statement->bindValue('discount_ttc'.$c, $line['discountTtc']);
                            $statement->bindValue('import_id'.$c, $line['id']. "_" . $restaurantCode);
                            $statement->bindValue('ticket_import_id'.$c, $line['ticket_id']. "_" . $restaurantCode);

                            $c++;
                            $addedTicketsLines++;
                        }

                        $statement->execute();
                        $this->em->getConnection()->commit();
                        $query_values="";
                        $ticketLineData=null;
                        unset($ticketLineData);
                        $this->em->clear();
                        gc_collect_cycles();
                        $j=0;

                    }

                    $progress->advance();

                }

                $this->em->clear();
                gc_collect_cycles();

                fclose($handle);
                $progress->finish();
                $output->writeln("");
                $output->writeln("=> Total tickets lines treated = " . $i);
                $output->writeln("--> " . $addedTicketsLines . " Tickets Lines imported for restaurant " . $restaurant->getName());
               // $output->writeln("--> " . $skippedTicketsLines . " Tickets Lines skipped for restaurant " . $restaurant->getName());
                $this->showMemoryUsage();

            } else {
                $output->writeln("Cannot open the Tickets lines csv file! Exit command...");
                return;
            }


            ///////////////////////////////////////////////////////////////////////
            /// tickets payment
            $filename = "ticketsPayment_restaurant_" . $restaurantCode . ".csv";
            $filePath = $this->dataDir . $filename;

            if (!file_exists($filePath)) {
                $output->writeln("No import file with the '" . $restaurantCode . "' restaurant code found !");
                return;
            }
            $file = new \SplFileObject($filePath, 'r');
            $file->seek(PHP_INT_MAX);
            $linesCount = $file->key() - 1;
            $progress = new ProgressBar($output, $linesCount);

            if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier
                $output->writeln("Start importing tickets Payments...");
                $addedTicketsPayment = 0;
                $skippedTicketsPayment = 0;
                $i = 0;// number of iteration
                $j=0;// counter used for binding values in sql query
                $header = fgets($handle);//load the header
                $raw_query = "INSERT INTO ticket_payment(id, ticket_id, num, label, id_payment, code, amount, type, operator, first_name, last_name, electronic, import_id) VALUES ";
                $query_values="";
                $progress->start();
                while (($data = fgetcsv($handle, 0, ";")) !== false) { // Eléments séparés par un point-virgule, à modifier si necessaire
                    $i++;
                    $j++;
                    $ticketPaymentData[] = array(
                        'id' => $data[0],
                        'num' => $data[1] ? $data[1] : null,
                        'label' => $data[2],
                        'idPayment' => $data[3],
                        'code' => $data[4],
                        'amount' =>  floatval($data[5]) ,
                        'type' => $data[6],
                        'operator' => $data[7],
                        'firstName' => $data[8],
                        'lastName' => $data[9],
                        'electronic' => boolval($data[10]) ? 1 : 0,
                        'ticket_id' => $data[11],
                        'real_cash_container_id' => $data[12],
                        'check_restaurant_container_id' => $data[13],
                        'bank_card_container_id' => $data[14],
                        'check_quick_container_id' => $data[15],
                        'meal_ticket_container_id' => $data[16],
                        'foreign_currency_container_id' => $data[17]
                    );

                    $query_values .= "(NEXTVAL('ticket_payment_id_seq') ,(SELECT id FROM ticket where import_id = :ticket_import_id$j ), :num$j, :label$j, :id_payment$j, :code$j, :amount$j, :type$j, :operator$j, :first_name$j, :last_name$j, :electronic$j, :import_id$j ),";


                    if (($i % 100) === 0 || $i >= $linesCount) {
                        $this->em->getConnection()->beginTransaction();
                        $query_values=rtrim($query_values,',');
                        $sql=$raw_query.$query_values." ON CONFLICT (import_id) DO NOTHING ;  ";
                        $statement = $this->em->getConnection()->prepare($sql);
                        // Set parameters
                        $c=1;
                        foreach ($ticketPaymentData as  $ticketPayment){
                            $statement->bindValue('num'.$c, $ticketPayment['num']);
                            $statement->bindValue('label'.$c, $ticketPayment['label']);
                            $statement->bindValue('id_payment'.$c, $ticketPayment['idPayment']);
                            $statement->bindValue('code'.$c, $ticketPayment['code']);
                            $statement->bindValue('amount'.$c, $ticketPayment['amount']);
                            $statement->bindValue('type'.$c, $ticketPayment['type']);
                            $statement->bindValue('operator'.$c, $ticketPayment['operator']);
                            $statement->bindValue('first_name'.$c, $ticketPayment['firstName']);
                            $statement->bindValue('last_name'.$c, $ticketPayment['lastName']);
                            $statement->bindValue('electronic'.$c, $ticketPayment['electronic']);
                            $statement->bindValue('import_id'.$c, $ticketPayment['id']. "_" . $restaurantCode);
                            $statement->bindValue('ticket_import_id'.$c, $ticketPayment['ticket_id']. "_" . $restaurantCode);

                            $c++;
                            $addedTicketsPayment++;
                        }

                        $statement->execute();
                        $this->em->getConnection()->commit();
                        $query_values="";
                        $ticketPaymentData=null;
                        unset($ticketPaymentData);
                        $this->em->clear();
                        gc_collect_cycles();
                        $j=0;

                    }

                    $progress->advance();

                }

                $this->em->clear();
                gc_collect_cycles();

                fclose($handle);
                $progress->finish();
                $output->writeln("");
                $output->writeln("=> Total tickets payments treated = " . $i);
                $output->writeln("--> " . $addedTicketsPayment . " Tickets Payments imported for restaurant " . $restaurant->getName());
                //->writeln("--> " . $skippedTicketsPayment . " Tickets Payments skipped for restaurant " . $restaurant->getName());
                $this->showMemoryUsage();

            } else {
                $output->writeln("Cannot open the Tickets Payments csv file! Exit command...");
                return;
            }

            ///////////////////////////////////////////////////////////////////////
            /// tickets Interventions
            $filename = "ticketsInterventions_restaurant_" . $restaurantCode . ".csv";
            $filePath = $this->dataDir . $filename;

            if (!file_exists($filePath)) {
                $output->writeln("No import file with the '" . $restaurantCode . "' restaurant code found !");
                return;
            }
            $file = new \SplFileObject($filePath, 'r');
            $file->seek(PHP_INT_MAX);
            $linesCount = $file->key() - 1;
            $progress = new ProgressBar($output, $linesCount);

            if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier
                $output->writeln("Start importing Tickets Interventions...");
                $addedTicketsInterventions = 0;
                $skippedTicketsInterventions = 0;
                $i = 0;// number of iteration to use for batch insert
                $header = fgets($handle);//load the header
                $progress->start();
                $this->em->getConnection()->beginTransaction();
                while (($data = fgetcsv($handle, 0, ";")) !== false) { // Eléments séparés par un point-virgule, à modifier si necessaire
                    $i++;
                    $ticketInterventionData = array(
                        'id' => $data[0],
                        'action' => $data[1] ,
                        'managerID' => $data[2],
                        'managerName' => $data[3] ,
                        'itemId' => $data[4],
                        'itemLabel' => $data[5]  ,
                        'itemPrice' =>  floatval($data[6]) ,
                        'itemPLU' => $data[7],
                        'itemQty' => $data[8] ? $data[8] : null,
                        'itemAmount' =>  floatval($data[9]) ,
                        'itemCode' => $data[10],
                        'date' => $data[11] ? \DateTime::createFromFormat('Y-m-d H:i:s', $data[11]) : null,
                        'postTotal' => boolval($data[12]),
                        'ticket_id' => $data[13]
                    );

                    $ticketIntervention = $this->em->getRepository(TicketIntervention::class)->findOneBy(
                        array(
                            "importId" => $ticketInterventionData['id'] . "_" . $restaurantCode
                        )
                    );
                    if (!$ticketIntervention) {
                        $ticketIntervention = new TicketIntervention();

                        if($ticketInterventionData['ticket_id']) {
                            $ticket = $this->em->getRepository(Ticket::class)->findOneBy(
                                array(
                                    "importId" => $ticketInterventionData['ticket_id'] . "_" . $restaurantCode
                                )
                            );
                            if (!$ticket) {
                                $this->logger->info('Ticket Intervention not assigned to any Ticket  : ', array("Ticket Intervention Id" => $ticketInterventionData['id'], "Ticket Id" => $ticketInterventionData['ticket_id'], "Restaurant" => $restaurant->getName()));
                            }else{
                                $ticketIntervention->setTicket($ticket);
                            }
                        }else{
                            $this->logger->info('Ticket Intervention not assigned to any Ticket  : ', array("Ticket Intervention Id" => $ticketInterventionData['id'], "Ticket Id" => $ticketInterventionData['ticket_id'], "Restaurant" => $restaurant->getName()));
                        }


                        $ticketIntervention
                            ->setAction($ticketInterventionData['action'])
                            ->setManagerID($ticketInterventionData['managerID'])
                            ->setManagerName($ticketInterventionData['managerName'])
                            ->setItemId($ticketInterventionData['itemId'])
                            ->setItemLabel($ticketInterventionData['itemLabel'])
                            ->setItemPrice($ticketInterventionData['itemPrice'])
                            ->setItemPLU($ticketInterventionData['itemPLU'])
                            ->setItemQty($ticketInterventionData['itemQty'])
                            ->setItemAmount($ticketInterventionData['itemAmount'])
                            ->setItemCode($ticketInterventionData['itemCode'])
                            ->setDate($ticketInterventionData['date'])
                            ->setPostTotal($ticketInterventionData['postTotal']);


                        $ticketIntervention->setImportId($ticketInterventionData['id'] . "_" . $restaurantCode);

                        $this->em->persist($ticketIntervention);

                        $this->flush($i);
                        $addedTicketsInterventions++;
                        $progress->advance();
                    } else {
                        $skippedTicketsInterventions++;
                        $this->logger->info('Ticket Interventions skipped because it already exist : ', array("Ticket Interventions" => $ticketInterventionData['id'], "Restaurant" => $restaurant->getName()));
                    }

                }

                $this->em->flush();
                $this->em->getConnection()->commit();
                $this->em->clear();

                fclose($handle);
                $progress->finish();
                $output->writeln("");
                $output->writeln("=> Total tickets Interventions treated = " . $i);
                $output->writeln("--> " . $addedTicketsInterventions . " Tickets Interventions imported for restaurant " . $restaurant->getName());
                $output->writeln("--> " . $skippedTicketsInterventions . " Tickets Interventions skipped for restaurant " . $restaurant->getName());
                $this->showMemoryUsage();

            } else {
                $output->writeln("Cannot open the Tickets Interventions csv file! Exit command...");
                return;
            }


            ///////////////////////////////////////////////////////////////////////
            /// tickets InterventionsSub
            $filename = "ticketsInterventionsSub_restaurant_" . $restaurantCode . ".csv";
            $filePath = $this->dataDir . $filename;

            if (!file_exists($filePath)) {
                $output->writeln("No import file with the '" . $restaurantCode . "' restaurant code found !");
                return;
            }
            $file = new \SplFileObject($filePath, 'r');
            $file->seek(PHP_INT_MAX);
            $linesCount = $file->key() - 1;
            $progress = new ProgressBar($output, $linesCount);

            if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier
                $output->writeln("Start Importing Tickets InterventionsSub...");
                $addedInterventionsSub = 0;
                $skippedInterventionsSub = 0;
                $i = 0;// number of iteration to use for batch insert
                $header = fgets($handle);//load the header
                $progress->start();
                $this->em->getConnection()->beginTransaction();
                while (($data = fgetcsv($handle, 0, ";")) !== false) { // Eléments séparés par un point-virgule, à modifier si necessaire
                    $i++;
                    $interventionSubData = array(
                        'id' => $data[0],
                        'subId' => $data[1] ? $data[1] : null,
                        'subLabel' => $data[2],
                        'subPrice' => $data[3] ? floatval($data[3]) : null,
                        'subPLU' => $data[4],
                        'subQty' => $data[5] ? $data[5] : null,
                        'intervention_id' => $data[6]
                    );

                    $interventionSub = $this->em->getRepository(TicketInterventionSub::class)->findOneBy(
                        array(
                            "importId" => $interventionSubData['id'] . "_" . $restaurantCode
                        )
                    );
                    if (!$interventionSub) {
                        $interventionSub = new TicketInterventionSub();

                        $intervention = $this->em->getRepository(TicketIntervention::class)->findOneBy(
                            array(
                                "importId" => $interventionSubData['intervention_id'] . "_" . $restaurantCode
                            )
                        );
                        if (!$intervention) {
                            $skippedInterventionsSub++;
                            $this->logger->info('Ticket InterventionSub Skipped because Ticket Intervention doesn\'t exist : ', array("Ticket InterventionSub Id" => $interventionSubData['id'], "Intervention Id" => $interventionSubData['intervention_id'], "Restaurant" => $restaurant->getName()));
                            $progress->advance();
                            continue;
                        }


                        $interventionSub
                            ->setSubId($interventionSubData['subId'])
                            ->setSubLabel($interventionSubData['subLabel'])
                            ->setSubPrice($interventionSubData['subPrice'])
                            ->setSubQty($interventionSubData['subQty'])
                            ->setSubPLU($interventionSubData['subPLU']);

                        $intervention->addSub($interventionSub);
                        $interventionSub->setIntervention($intervention);
                        $interventionSub->setImportId($interventionSubData['id'] . "_" . $restaurantCode);

                        $this->em->persist($interventionSub);
                        $this->flush($i);
                        $addedInterventionsSub++;
                        $progress->advance();
                    } else {
                        $skippedInterventionsSub++;
                        $this->logger->info('Ticket InterventionsSub because it already exist : ', array("Ticket InterventionsSub" => $interventionSubData['id'], "Restaurant" => $restaurant->getName()));
                    }

                }

                $this->em->flush();
                $this->em->getConnection()->commit();
                $this->em->clear();

                fclose($handle);
                $progress->finish();
                $output->writeln("");
                $output->writeln("=> Total tickets InterventionsSub treated = " . $i);
                $output->writeln("--> " . $addedInterventionsSub . " Tickets InterventionsSub imported for restaurant " . $restaurant->getName());
                $output->writeln("--> " . $skippedInterventionsSub . " Tickets InterventionsSub skipped for restaurant " . $restaurant->getName());
                $this->showMemoryUsage();

            } else {
                $output->writeln("Cannot open the Tickets InterventionsSub csv file! Exit command...");
                return;
            }


        } catch (\Exception $e) {
            $output->writeln("");
            $output->writeln("Command failed ! Rollback...");
            $this->em->getConnection()->rollBack();
            $output->writeln($e->getMessage());
            return;
        }

        $output->writeln("\n====> Restaurant [" . $restaurant->getName() . "] tickets data imported successfully.");

    }

    public function flush($i)
    {
        if ($i % 100 === 0) {
            $this->em->flush();
            $this->em->getConnection()->commit();
            $this->em->clear();
            gc_collect_cycles();
            $this->em->getConnection()->beginTransaction();
        }
    }

    private function showMemoryUsage()
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        $size = memory_get_usage(true);
        $usage = @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
        echo "\nThis process is using : " . $usage . "\n";
    }



}
