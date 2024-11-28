<?php

namespace AppBundle\General\Command\TicketReprise;

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

class ImportTicketsFromCsvCommand extends ContainerAwareCommand
{

    private $dataDir;

    private $ticketsFileName;

    private $paymentsFileNames;

    private $ticketDetailsFileNames;

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

    private $totalTicket;
    private $totalTicketsLines;
    private $totalTicketsPayments;


    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:wynd:import:csv')
            ->addOption('typeRestaurant', 't', InputOption::VALUE_REQUIRED, 'The type of restaurant.', '')
            ->addOption('restaurantId', 'i', InputOption::VALUE_REQUIRED, 'The restaurant id.', '')
            ->setDescription('Import Wynd Tickets in the file.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->logger = $this->getContainer()->get('logger');

        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir') . "/../data/import/tickets/Reprise_2017/";
        $this->ticketsFileName = '';
        $this->paymentsFileNames = [];
        $this->ticketDetailsFileNames = [];
        $this->currentRestaurant = null;
        $this->totalTicket=0;
        $this->totalTicketsLines=0;
        $this->totalTicketsPayments=0;

        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->typeRestaurant = $input->getOption('typeRestaurant');
        if($this->typeRestaurant != 'quick' && $this->typeRestaurant != 'bk')
        {
            echo "the type of restaurant must be 'quick' or 'bk' only";
            return;
        }
        if ($input->hasOption('restaurantId') && !empty($input->getOption('restaurantId'))) {
            $restaurantId = $input->getOption('restaurantId');
            $this->currentRestaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);
            if ($this->currentRestaurant == null) {
                $this->logger->addAlert('Restaurant not found with id: '.$restaurantId, ['quick:financial:revenue:import']);
                echo 'Restaurant not found with id: '.$restaurantId;

                return;
            }
        }
        $this->dataDir .= $this->currentRestaurant->getCode()."/";
        $this->ticketsFileName = $this->dataDir . 'tickets.csv';
        $this->paymentsFileNames = [
            $this->dataDir . 'payments.csv',
        ];
        $this->ticketDetailsFileNames = [
            $this->dataDir . 'tickets_details.csv',
        ];

        $this->key = Utilities::generateRandomString(5);
        $this->param = $this->getContainer()->get('paremeter.service')->getOrCreateTicketUploadLock($this->key);
        $this->param->setValue($this->key)
            ->setUpdatedAt(new \DateTime('now'));
        $this->em->flush();

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $time_start = microtime(true);
        $this->logger->addInfo('Importing tickets from csv', ['ImportTicketsFromCsvCommand']);
        echo 'Importing tickets from csv'."\n";

        try {

            $connection = $this->em->getConnection();
            $statement = $connection->prepare(
                "delete from ticket_line_temp;"
            );
            $statement->execute();
            $statement = $connection->prepare(
                "delete from ticket_payment_temp;"
            );
            $statement->execute();
            $output->writeln("=> Loading tickets ...");
            $this->loadTickets($output);
            $output->writeln("=> Loading payments ...");
            $this->loadPayments($output);
            $output->writeln("=> Loading tickets lines ...");
            $this->loadLines($output);



            $statement = $connection->prepare(
                "select count(tpt.id) from ticket_payment_temp tpt;"
            );

            $statement->execute();
            $countPayments = $statement->fetchColumn(0);
            $step = 0;
            $pageSize = 1000;
            $lastId=0;
            $output->writeln("");
            $output->writeln("-> Linking payments to tickets ...");
            $output->writeln("");
            $progress = new ProgressBar($output, $countPayments / $pageSize + 1);
            while ( ($step * $pageSize) < $countPayments) {
                $progress->advance();
                $statement = $connection->prepare(
                    "insert into ticket_payment (id, num, label, amount, type, operator, first_name, last_name, id_payment, ticket_id)
                    select NEXTVAL('ticket_payment_id_seq') as id, tpt.num, tpt.label, tpt.amount, tpt.type, tpt.operator,
                        tpt.first_name, tpt.last_name, tpt.id_payment, t.id as ticket_id
                    from ticket_payment_temp tpt left join ticket t on tpt.ticket_id = t.external_id and t.origin_restaurant_id = :restaurantId WHERE tpt.id > :lastId order by tpt.id LIMIT :pageSize ;"
                );
                $statement->bindValue('pageSize', $pageSize); //limit
                $statement->bindParam("lastId",$lastId);
                $statement->bindValue('restaurantId', $this->currentRestaurant->getId());
                $statement->execute();

                $sql="SELECT max(maxid) FROM (SELECT id as maxid FROM ticket_payment_temp WHERE id > :lastId order by id LIMIT :pageSize) as req";
                $stm = $this->em->getConnection()->prepare($sql);
                $stm->bindParam("lastId",$lastId);
                $stm->bindParam("pageSize", $pageSize);
                $stm->execute();
                $lastId= $stm->fetchColumn();
                $step++;
            }

            $progress->finish();

            $statement = $connection->prepare(
                "select count(tlt.id) from ticket_line_temp tlt;"
            );
            $statement->execute();

            $countLines = $statement->fetchColumn(0);
            $output->writeln("");
            $output->writeln("-> Linking lines to tickets ...");
            $output->writeln("");
            $progress = new ProgressBar($output, $countLines / $pageSize + 1);
            $step = 0;
            $lastId=0;

            while  ( ($step * $pageSize) < $countLines) {
                $progress->advance();

                $statement = $connection->prepare(
                    "insert into ticket_line
                    (id, line, qty, price, totalht, totaltva, totalttc, category, division, product, label, description,
                     plu, combo, composition, parentline, tva, is_discount, discount_code, discount_label, discount_ht,
                     discount_tva, discount_ttc, ticket_id)
                    select NEXTVAL('ticket_line_id_seq') as id, tlt.line, tlt.qty, tlt.price, tlt.totalht, tlt.totaltva,
                     tlt.totalttc, tlt.category, tlt.division, tlt.product, tlt.label, tlt.description, tlt.plu, tlt.combo,
                      tlt.composition, tlt.parentline, tlt.tva, tlt.is_discount, tlt.discount_code, tlt.discount_label,
                      tlt.discount_ht, tlt.discount_tva, tlt.discount_ttc, t.id as ticket_id
                    from ticket_line_temp tlt left join ticket t on tlt.ticket_id = t.external_id and t.origin_restaurant_id = :restaurantId WHERE tlt.id > :lastId order by tlt.id LIMIT :pageSize ;"
                );

                $statement->bindValue('pageSize', $pageSize); //limit
                $statement->bindParam("lastId",$lastId);
                $statement->bindValue('restaurantId', $this->currentRestaurant->getId());
                $statement->execute();

                $sql="SELECT max(maxid) FROM (SELECT id as maxid FROM ticket_line_temp WHERE id > :lastId order by id LIMIT :pageSize) as req";
                $stm = $this->em->getConnection()->prepare($sql);
                $stm->bindParam("lastId",$lastId);
                $stm->bindParam("pageSize", $pageSize);
                $stm->execute();
                $lastId= $stm->fetchColumn();

                $step++;
            }

            $progress->finish();


            // Remove upload tickets lock
            $param = $this->em->getRepository('Administration:Parameter')
                ->findOneBy(['type' => Parameter::TICKET_UPLOAD]);
            $this->em->remove($param);
            $this->em->flush();

            $time_end = microtime(true);
            $execution_time = ($time_end - $time_start) / 60;
            $this->logger->addInfo('Importing tickets from csv took :' . $execution_time . 'Mins', ['ImportTicketsFromCsvCommand']);
            $output->writeln("");
            $output->writeln("===> Importing tickets from csv took :  " . $execution_time . 'Mins');
            $output->writeln("---> Total tickets imported          = ".$this->totalTicket);
            $output->writeln("---> Total tickets lines imported    = ".$this->totalTicketsLines);
            $output->writeln("---> Total tickets payments imported = ".$this->totalTicketsPayments);


        } catch (\Exception $e) {
            $this->logger->addError('Importing tickets exception :' . $e->getMessage(), ['ImportTicketsFromCsvCommand']);
            echo 'Importing tickets exception :' . $e->getMessage();
            throw $e;
        }

    }

    public function loadTickets(OutputInterface $output)
    {
        echo $this->ticketsFileName."\n";
        if (!file_exists($this->ticketsFileName)) {
            $this->logger->addDebug($this->ticketsFileName . " is not existing !", ['ImportTicketsFromCsvCommand']);
            echo $this->ticketsFileName . " is not existing !";
            return;
        }
        $file = new \SplFileObject($this->ticketsFileName, 'r');
        $file->seek(PHP_INT_MAX);
        $linesCount = $file->key() - 1;
        $progress = new ProgressBar($output, $linesCount);
        unset($file);
        $file = fopen($this->ticketsFileName, 'r');
        if (!$file) {
            $this->logger->addDebug("Cannot open file $this->ticketsFileName", ['ImportTicketsFromCsvCommand']);
            echo "Cannot open file $this->ticketsFileName";
            return;
        }
        $t1 = time();
        $output->writeln("Tickets data import started at " . date('H:i:s '));
        $this->logger->addDebug("Start at " . date('H:i:s '), ['ImportTicketsFromCsvCommand']);
        //$header = fgets($file);
        $num = -1;
        $i = 0;
        //ID;Type;Cancelled_flag;Num;Startdate;Enddate;InvoiceNumber;Status;Invoicecancelled;TotalHT;TotalTTC;Paid;Deliverytime;operator;Operatorname;responsable;Workstation;WorkstationName;OriginID;Origin;DestinationID;Destination;entity;customer;FiscalDate

        $progress->start();
        while ($line = fgetcsv($file, null, ';')) {
            if ($line[1] === 'Invoice') {
                $ticketArray = [
                    "invoice" => [
                        "id" => $line[0],
                        "date" => $line[24] == 'NULL' ? null : $line[24],
                        "date_ticket_start" => $line[4] == 'NULL' ? null : $line[4],
                        "date_ticket_end" => $line[5] == 'NULL' ? null : $line[5],
                        "invoiceNumber" => $line[6],
                        "status" => $line[7],
                        "invoiceCancelled" => $line[8] != "0",
                        "total_ht" => $line[9],
                        "total_ttc" => $line[10],
                        "paid" => true,
                        "delivery_time" => null,
                        "operator" => $line[13] == 'NULL' ? null : $line[13],
                        "operator_name" => $line[14] == 'NULL' ? null : $line[14],
                        "responsable" => $line[15],
                        "workstation" => $line[16],
                        "workstation_name" => $line[17],
                        "origin_id" => isset($line[18]) ? intval($line[18]) : null,
                        "origin" => $line[19],
                        "destination_id" => $line[20],
                        "destination" => $line[21],
                        "entity" => $line[22],
                        "customer" => $line[23],
                    ]
                ];
                $this->insertTicket($ticketArray, $num, "invoice", $i);
            } else {
                $ticketArray = [
                    "order" => [
                        "id" => $line[0],
                        "date" => $line[24],
                        "date_ticket_start" => $line[4],
                        "date_ticket_end" => $line[5],
                        "invoiceNumber" => $line[6],
                        "status" => $line[7],
                        "invoiceCancelled" => $line[8] != "0",
                        "total_ht" => $line[9],
                        "total_ttc" => $line[10],
                        "paid" => false,
                        "delivery_time" => null,
                        "operator" => $line[13] == 'NULL' ? null : $line[13],
                        "operator_name" => $line[14] == 'NULL' ? null : $line[14],
                        "responsable" => $line[15],
                        "workstation" => $line[16],
                        "workstation_name" => $line[17],
                        "origin_id" => isset($line[18]) ? intval($line[18]) : null,
                        "origin" => $line[19],
                        "destination_id" => $line[20],
                        "destination" => $line[21],
                        "entity" => $line[22],
                        "customer" => $line[23],
                    ]
                ];
                $this->insertTicket($ticketArray, $num, "order", $i);
            }
            $i++;
            $progress->advance();
        }
        fclose($file);
        $this->flush($i);
        $t2 = time();
        $progress->finish();
        $output->writeln("");
        $output->writeln("Tickets import finish at " . date('H:i:s ')." | Import time =  " . ($t2 - $t1). " seconds");
        $this->logger->addDebug("Finish at " . date('H:i:s '), ['ImportTicketsFromCsvCommand']);
        $this->logger->addDebug("Import tickets finish in " . ($t2 - $t1) . " seconds", ['ImportTicketsFromCsvCommand']);
        $this->logger->addDebug($i . " Tickets was imported.", ['ImportTicketsFromCsvCommand']);
    }

    public function loadPayments(OutputInterface $output)
    {
        $mapIdPaymentCode = [
            'CASH' => 1,
            'CHEQUE QUICK' => ($this->typeRestaurant == 'quick') ? 131 : 550,
            'TICKET RESTAURANT' => ($this->typeRestaurant == 'quick') ? 130 : 400,
            'CHEQUES RESTO LUX' => ($this->typeRestaurant == 'quick') ? 132 : 500,
            'SODEXHO PASS' => ($this->typeRestaurant == 'quick') ? 120 : 300,
            'AMEX' => 107,
            'BANCONTACT' => 102,
            'BANCOMAT' => 7,
            'DEVISES ETRANGERES' => 8,
            'DINERS' => 9,
            'EFT' => 10,
            'MASTERCARD' => 103,
            'MAESTRO' => 104,
            'PROTON' => 13,
            'TEST' => 14,
            'VISA' => 105,
            'EDENRED TR' => 108,
            'EPASS SODEXO' => 109,
            'PAYFAIR' => 110,
            '** A PAYER **' => 19,
            'libre20' => 20,
            'libre21' => 21,
            'libre22' => 22,
            'BR' => 5,
            'DEFAULT' => -1
        ];

        $t1 = time();
        foreach ($this->paymentsFileNames as $paymentsFileName) {
            if (!file_exists($paymentsFileName)) {
                $this->logger->addDebug($paymentsFileName . " is not existing !", ['ImportTicketsFromCsvCommand']);
                return;
            }
            $file = new \SplFileObject($paymentsFileName, 'r');
            $file->seek(PHP_INT_MAX);
            $linesCount = $file->key() - 1;
            $progress = new ProgressBar($output, $linesCount);
            unset($file);
            $file = fopen($paymentsFileName, 'r');
            if (!$file) {
                $this->logger->addDebug("Cannot open file $paymentsFileName", ['ImportTicketsFromCsvCommand']);
                return;
            }
            $this->logger->addDebug("Start importing payments at " . date('H:i:s '), ['ImportTicketsFromCsvCommand']);
            $output->writeln("Tickets payments data import started at " . date('H:i:s '));
            //$header = fgets($file);
            $i = 0;
            $progress->start();
            while ($line = fgetcsv($file, null, ';')) {
                $payment = [
                    'ticket' => $line[0],
                    'num' => $line[1],
                    "id" => array_key_exists($line[11], $mapIdPaymentCode) ? $mapIdPaymentCode[$line[11]]: $mapIdPaymentCode['DEFAULT'],
                    "action" => $line[4],
                    "code" => $line[11],
                    "label" => $line[2],
                    "amount" => $line[3],
                    "operator" => $line[5],
                    "firstname" => $line[6],
                    "lastname" => $line[7]
                ];
                $this->insertTicketPayment($payment, $i);
                $i++;
                $progress->advance();
            }
            fclose($file);
            $this->em->flush();
            $this->em->clear();
            gc_collect_cycles();
            $progress->finish();
        }
        $t2 = time();
        $this->logger->addDebug("Finish at " . date('H:i:s '), ['ImportTicketsFromCsvCommand']);
        $this->logger->addDebug("Import temps payments finish in " . ($t2 - $t1) . " seconds", ['ImportTicketsFromCsvCommand']);
        $this->logger->addDebug($i . " Temp payments was imported.", ['ImportTicketsFromCsvCommand']);
        $output->writeln("");
        $output->writeln("Tickets payments import finish at " . date('H:i:s ')." | Import time = " . ($t2 - $t1). " seconds");
    }

    public function loadLines(OutputInterface $output)
    {
        $gi = 0;
        $t1 = time();
        foreach ($this->ticketDetailsFileNames as $ticketsDetailsFileName) {
            if (!file_exists($ticketsDetailsFileName)) {
                $this->logger->addDebug($ticketsDetailsFileName . " is not existing !", ['ImportTicketsFromCsvCommand']);
                return;
            }
            $file = new \SplFileObject($ticketsDetailsFileName, 'r');
            $file->seek(PHP_INT_MAX);
            $linesCount = $file->key() - 1;
            $progress = new ProgressBar($output, $linesCount);
            unset($file);
            $file = fopen($ticketsDetailsFileName, 'r');
            if (!$file) {
                $this->logger->addDebug("Cannot open file $ticketsDetailsFileName", ['ImportTicketsFromCsvCommand']);
                return;
            }

            $this->logger->addDebug("Start importing lines at " . date('H:i:s '), ['ImportTicketsFromCsvCommand']);
            $output->writeln("Tickets lines data import started at " . date('H:i:s '));
            //$header = fgets($file);
            $i = 0;
            $progress->start();
            while ($line = fgetcsv($file, null, ';')) {
                if($line[9] == 'VENTES ANNEXES Tfort' || $line[9]== 'VENTES ANNEXES TFaib'){
                    $division=1;
                }else{
                    $division=0;
                }
                $ticketLine = [
                    'date' => $line[0],
                    'ticket' => $line[1],
                    "line" => $line[2],
                    "quantity" => $line[3],
                    "price" => $line[4],
                    "total_ht" => $line[5],
                    "total_tva" => $line[6],
                    "total_ttc" => $line[7],
                    "category" => $line[8],
                    "division" => $division,
                    "product" => $line[10] != 'NULL' ? $line[10] : null,
                    "description" => strval($line[11]),
                    "plu" => $line[12],
                    "combo" => $line[13] == "1",
                    "composition" => $line[14] == "1",
                    "parentline" => $line[15] == 'NULL' ? null : $line[14],
                    "tva" => $line[16] == 'NULL' ? null : $line[16],
                    "label" => $line[17] != 'NULL' ? $line[17] : null,
                    "discount_id" => null,
                    "is_discount" => $line[19] == "f" || $line[19] == "0" ? false : true,
                    "discount_code" => $line[20],
                    "discount_label" => $line[21],
                    "discount_ht" => $line[22],
                    "discount_tva" => $line[23],
                    "discount_ttc" => $line[24],

                ];
                $this->insertTicketLine($ticketLine, $i);
                $i++;
                $progress->advance();
            }
            fclose($file);
            $this->flush($i);
            $gi += $i;
        }
        $this->em->flush();
        $progress->finish();

        $t2 = time();
        $this->logger->addDebug("Finish at " . date('H:i:s '), ['ImportTicketsFromCsvCommand']);
        $this->logger->addDebug("Import temp lines finish in " . ($t2 - $t1) . " seconds", ['ImportTicketsFromCsvCommand']);
        $this->logger->addDebug($gi . " Temp lines was imported.", ['ImportTicketsFromCsvCommand']);
        $output->writeln("");
        $output->writeln("Tickets lines import finish at " . date('H:i:s ')." | Import time = " . ($t2 - $t1). " seconds");
    }

    public function insertTicket($ticketArray, $num, $type, $i)
    {
        $ticket = new Ticket();
        $ticket
            ->setNum(intval($num))
            ->setType($type)
            ->setDate($ticketArray[$type]['date'], 'Y-m-d H:i:s.u')
            ->setStartDate(($ticketArray[$type]['date_ticket_start'] != null) ? \DateTime::createFromFormat('Y-m-d H:i:s.u', $ticketArray[$type]['date_ticket_start']) : null)
            ->setEndDate(($ticketArray[$type]['date_ticket_end'] != null) ? \DateTime::createFromFormat('Y-m-d H:i:s.u', $ticketArray[$type]['date_ticket_end']) : null)
            ->setInvoiceNumber($ticketArray[$type]['invoiceNumber'])
            ->setStatus(intval($ticketArray[$type]['status']))
            ->setInvoiceCancelled(strval($ticketArray[$type]['invoiceCancelled']))
            ->setTotalHT(floatval($ticketArray[$type]['total_ht']))
            ->setTotalTTC(floatval($ticketArray[$type]['total_ttc']))
            ->setPaid($ticketArray[$type]['paid'])
            ->setDeliveryTime(($ticketArray[$type]['delivery_time'] != null) ? \DateTime::createFromFormat('Y-m-d H:i:s.u', $ticketArray[$type]['delivery_time']) : null)
            ->setOperator(intval($ticketArray[$type]['operator']))
            ->setOperatorName(isset($ticketArray[$type]['operatorName']) ? $ticketArray[$type]['operatorName'] : null)
            ->setResponsible($ticketArray[$type]['responsable'])
            ->setWorkstation(intval($ticketArray[$type]['workstation']))
            ->setWorkstationName(isset($ticketArray[$type]['cashDeskID']) ? $ticketArray[$type]['cashDeskID'] : null)
            ->setOriginId(isset($ticketArray[$type]['origin_id']) ? intval($ticketArray[$type]['origin_id']) : null)
            ->setOrigin($ticketArray[$type]['origin'])
            ->setDestinationId(isset($ticketArray[$type]['destination_id']) ? intval($ticketArray[$type]['destination_id']) : null)
            ->setDestination($ticketArray[$type]['destination'])
            ->setEntity(intval($ticketArray[$type]['entity']))
            ->setCustomer(intval($ticketArray[$type]['customer']))
            ->setExternalId($ticketArray[$type]['id'])
            ->setCounted(true)
            // ->setImportId('histo_'.$this->currentRestaurant->getCode())
            ->setOriginRestaurant($this->currentRestaurant);
        $this->totalTicket++;
        $this->em->merge($ticket);
        $this->flush($i);
    }

    public function insertTicketPayment($p, $i)
    {
        //Ticket Payments
        $payment = new TicketPaymentTemp();
        $payment
            ->setType(TicketPayment::PAYMENT_TYPE)
            ->setNum(intval($p['num']))
            ->setLabel($p['label'])
            ->setAmount(floatval($p['amount']))
            ->setTicket(intval($p['ticket']));
        if (isset($p['operator'])) {
            $payment->setOperator(intval($p['operator']));
        }
        if (isset($p['firstName'])) {
            $payment->setOperator($p['firstName']);
        }
        if (isset($p['lastName'])) {
            $payment->setOperator($p['lastName']);
        }
        if (isset($p['code'])) {
            $payment->setCode($p['code']);
        }
        if (isset($p['id'])) {
            $payment->setIdPayment($p['id']);
        }
        $this->totalTicketsPayments++;
        $this->em->persist($payment);
        $this->flush($i);
    }

    public function insertTicketLine($l, $i)
    {
        $line = new TicketLineTemp();
        $line
            ->setLine(intval($l['line']))
            ->setQty(intval($l['quantity']))
            ->setPrice(floatval($l['price']))
            ->setTotalHT(floatval($l['total_ht']))
            ->setTotalTVA(floatval($l['total_tva']))
            ->setTotalTTC(floatval($l['total_ttc']))
            ->setCategory(isset($l['category']) ? $l['category'] : null)
            ->setDivision(isset($l['division']) ? intval($l['division']) : null)
            ->setProduct($l['product'])
            ->setLabel(isset($l['label']) && $l['label'] != 'NULL' ? $l['label'] : null)
            ->setDescription($l['description'])
            ->setPlu(isset($l['plu']) ? $l['plu'] : null)
            ->setCombo(isset($l['combo']) ? $l['combo'] : null)
            ->setComposition($l['composition'])
            ->setParentLine($l['parentline'] != 'NULL' ? intval($l['parentline']) : null)
            ->setTva(floatval($l['tva']))
            ->setIsDiscount(boolval($l['is_discount']))
            ->setDiscountCode($l['discount_code'])
            ->setDiscountLabel($l['discount_label'])
            ->setDiscountHt(floatval($l['discount_ht']))
            ->setDiscountTva(floatval($l['discount_tva']))
            ->setDiscountTtc(floatval($l['discount_ttc']))
            ->setTicket(intval($l['ticket']))
            ->setMvmtRecorded(true);

        $this->totalTicketsLines++;
        $this->em->persist($line);
        $this->flush($i);
    }

    public function flush($i)
    {
        if ($i % 200 === 0) {
            $this->param->setValue($this->key)
                ->setUpdatedAt(new \DateTime('now'));
            $this->em->flush();
            $this->em->clear();
            gc_collect_cycles();
            //echo "This process is using :" . $this->convert(memory_get_usage(true)) . "\n";
        }
    }

    function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

}