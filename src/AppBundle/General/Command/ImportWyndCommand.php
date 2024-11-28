<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 16/03/2016
 * Time: 08:13
 */

namespace AppBundle\General\Command;

use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketIntervention;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Financial\Entity\TicketInterventionSub;
use AppBundle\Financial\Service\RevenuePricesService;
use AppBundle\Merchandise\Entity\ProductPurchasedMvmt;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Service\ProductPurchasedMvmtService;
use AppBundle\ToolBox\Utils\Utilities;
use function Sodium\crypto_box_publickey_from_secretkey;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\Date;

class ImportWyndCommand extends ContainerAwareCommand
{

    private $dataDir;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Logger
     */
    private $ignoreLogger;


    private $version;

    /**
     * @var RevenuePricesService
     */
    private $revenuePricesService;

    /**
     * @var ProductPurchasedMvmtService
     */
    private $productPurchasedMvmtService;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:wynd:import')
            ->addArgument('restaurantId', InputArgument::REQUIRED)
            ->addArgument('filename', InputArgument::OPTIONAL)
            ->addArgument('startDate', InputArgument::OPTIONAL)
            ->addArgument('endDate', InputArgument::OPTIONAL)
            ->setDescription('Import Wynd Tickets in the file.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')
            ."/../data/import/";
        $this->em = $this->getContainer()->get(
            'doctrine.orm.default_entity_manager'
        );
        $this->logger = $this->getContainer()->get(
            'monolog.logger.tickets_import'
        );
        $this->ignoreLogger = $this->getContainer()->get(
            'monolog.logger.ignored_tickets'
        );
        $this->revenuePricesService = $this->getContainer()->get(
            'prices.revenues.service'
        );
        $this->productPurchasedMvmtService = $this->getContainer()->get(
            'product_purchased_mvmt.service'
        );
        $this->version = $this->getContainer()->getParameter('version');
        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        try {

            $uid = uniqid();

            echo 'import tickets for '.$this->version;


            $restaurantId = $input->getArgument('restaurantId');
            $currentRestaurant = $this->em->getRepository(Restaurant::class)
                ->find($restaurantId);
            if ($currentRestaurant == null) {
                $this->logger->addInfo(
                    'Restaurant not found with id: '.$restaurantId,
                    ['quick:wynd:rest:import']
                );
                echo 'Restaurant not found with id: '.$restaurantId;

                return;
            }

            $this->logger->addDebug(
                $uid.' Importing tickets for restaurant '
                .$currentRestaurant->getCode(),
                ['ImportWyndCommand']
            );
            $firstOpening = $this->em->getRepository(
                AdministrativeClosing::class
            )->findOneBy(
                ['originRestaurant' => $currentRestaurant],
                ["date" => "asc"]
            );

            if (is_null($firstOpening)) {

                $this->logger->addDebug(
                    'no admin closing found for the restaurant '
                    .$currentRestaurant->getCode(),
                    ['importWyndCommmand']
                );
            } else {
                $openingDate = $firstOpening->getDate()->add(
                    new \DateInterval('P1D')
                );


            }

            if ($input->hasArgument('filename')
                && trim(
                    $input->getArgument('filename')
                ) != ''
            ) {
                $filename = $input->getArgument('filename');
            } else {
                $filename = $this->dataDir.'aloha.new.json';
            }
            $this->logger->addDebug($filename, ['ImportWyndCommand']);

            if (!file_exists($filename)) {
                $this->logger->addDebug(
                    $filename." is not existing !",
                    ['ImportWyndCommand']
                );

                return;
            }

            $file = fopen($filename, 'r');

            if (!$file) {
                $this->logger->addDebug(
                    "Cannot open file $filename",
                    ['ImportWyndCommand']
                );

                return;
            }

            $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

            $data = file_get_contents($filename);

            // $this->logger->addDebug("Data is ".$data." ", ['ImportWyndCommand']);

            $json = json_decode($data, true);
            if (!$json) {
                $this->logger->addDebug(
                    "Data in the ".$filename." is not JSON format !",
                    ['ImportWyndCommand']
                );

                return;
            }

            $t1 = time();
            $this->logger->addDebug(
                "Start at ".date('H:i:s '),
                ['ImportWyndCommand']
            );
            $ticketsList = [Ticket::INVOICE => $json['data']];
            unset($json);
            $i = 0;
            $array_tickets = array();
            foreach ($ticketsList as $type => $tickets) {
                $this->logger->addDebug("type ".$type, ['ImportWyndCommand']);
                $type = 'order';
                foreach ($tickets as $val) {


                    $this->logger->addDebug(
                        "Ticket ".$val[$type]['date'],
                        ['ImportWyndCommand']
                    );

                    $this->logger->addDebug(
                        "invoice number".sprintf(
                            '%.0f',
                            $val[$type]['invoiceNumber']
                        ),
                        ['ImportWyndCommand']
                    );

                    $existingTicket = $this->em->getRepository(
                        'Financial:Ticket'
                    )->findOneBy(
                        [
                            'invoiceNumber'    => $val[$type]['invoiceNumber'],
                            'date'             => new \DateTime(
                                $val[$type]['date']
                            ),
                            'originRestaurant' => $currentRestaurant,
                        ]
                    );

                    if (is_null($existingTicket)) {

                        /**
                         * @var \DateTime $openingDate
                         */

                        $ticketDate = new \DateTime($val[$type]['date']);


                        if (isset($openingDate)
                            && ($ticketDate->format('Y-m-d')
                                == $openingDate->format('Y-m-d'))
                        ) {


                            if (intval($val[$type]['operator']) === 9999) {

                                $this->ignoreLogger->addDebug(
                                    'test ticket ignored in restaurant '
                                    .$currentRestaurant->getCode(),
                                    ['importWyndCommand']
                                );
                                $this->ignoreLogger->addDebug(
                                    "invoice number ".sprintf(
                                        '%.0f',
                                        $val[$type]['invoiceNumber']
                                    ),
                                    ['ImportWyndCommand']
                                );

                                continue;

                            }


                            if (floatval($val[$type]['total_ttc']) == 0
                                && empty($val['payments'])
                                && (empty($val['lines'])
                                    || (count(
                                            $val['lines']
                                        ) == count($val[$type]['interventions'])
                                        && $this->checkforTestInterventions(
                                            $val[$type]['interventions']
                                        )))
                            ) {

                                $this->ignoreLogger->addDebug(
                                    'test ticket ignored on restaurant '
                                    .$currentRestaurant->getCode(),
                                    ["importWyndCommand"]
                                );
                                $this->ignoreLogger->addDebug(
                                    'invoice number of test ticket: '.sprintf(
                                        '%.0f',
                                        $val[$type]['invoiceNumber']
                                    ),
                                    ["importWyndCommand"]
                                );

                                continue;

                            }


                        }
                        $array_tickets[$val[$type]['invoiceNumber']] = $val;

                        if (intval($val[$type]['operator']) === 0) {
                            $this->ignoreLogger->addDebug(
                                'ticket ignored for 0 operator',
                                ['importWyndCommand']
                            );

                            $this->ignoreLogger->addDebug(
                                "invoice number ".sprintf(
                                    '%.0f',
                                    $val[$type]['invoiceNumber']
                                ),
                                ['ImportWyndCommand']
                            );

                            $this->ignoreLogger->addDebug(
                                "ignored Ticket: "."\n".print_r($val, true),
                                ['ImportWyndCommand']
                            );

                        } // tickets with operator = INDEX support (test tickets)

                        elseif (empty($val['payments'])
                            && floatval(
                                $val[$type]['total_ttc']
                            ) == 0
                            && !empty($val['lines'])
                            && (count(
                                    $val['lines']
                                ) != count(
                                    $val[$type]['interventions']
                                ))
                            && empty($val[$type]['discounts'])
                        ) {
                            $this->logger->addDebug(
                                'Ticket ignored for empty payment',
                                ['ImportWyndCommand']
                            );
                        } else {
                            if (empty($val['payments'])
                                && floatval(
                                    $val[$type]['total_ttc']
                                ) != 0
                            ) {
                                $this->ignoreLogger->addDebug(
                                    'Ticket ignored: an empty payment with a total different from zero ',
                                    ['ImportWyndCommand']
                                );

                                $this->ignoreLogger->addDebug(
                                    "invoice number ".sprintf(
                                        '%.0f',
                                        $val[$type]['invoiceNumber']
                                    ),
                                    ['ImportWyndCommand']
                                );

                                $this->ignoreLogger->addDebug(
                                    "ignored Ticket: "."\n".print_r($val, true),
                                    ['ImportWyndCommand']
                                );

                            } else {

                                $this->logger->addDebug(
                                    'Ticket to insert is '.print_r($val, true),
                                    ['ImportWyndCommand']
                                );
                                $this->insertTicket(
                                    $val,
                                    $type,
                                    $i,
                                    $array_tickets,
                                    $currentRestaurant
                                );
                            }

                        }
                    }

                    $i++;
                }
            }//End foreach tickets


            $this->em->flush();
            $this->em->clear();
            $t2 = time();
            $this->logger->addDebug(
                "Finish at ".date('H:i:s '),
                ['ImportWyndCommand']
            );
            $this->logger->addDebug(
                $uid." Import finish in ".($t2 - $t1)." seconds for restaurant "
                .$currentRestaurant->getCode(),
                ['ImportWyndCommand']
            );
            $this->logger->addDebug(
                $uid." ".$i." Tickets was imported.",
                ['ImportWyndCommand']
            );

            // if users are not synced then launch user import
            if (!$this->getContainer()->get('ticket.service')
                ->isAllUsersAreSynced($currentRestaurant)
            ) {
                try {
                    $this->getContainer()->get('staff.service')->importUsers(
                        $currentRestaurant
                    );
                } catch (\Exception $e) {
                    $this->logger->addError(
                        'Users need to be synced but the staff import failed :'
                        .$e->getMessage(),
                        ['ImportWyndCommand']
                    );
                    throw $e;
                }
            }


            // execute financial revenue after import

            if ($input->hasArgument('startDate')
                && $input->hasArgument(
                    'endDate'
                )
            ) {

                $supportedFormat = "Y-m-d";

                $startDate = $input->getArgument('startDate');

                $endDate = $input->getArgument('endDate');

                if(!is_null($startDate) && !is_null($endDate) && Utilities::isValidDateFormat($startDate,$supportedFormat) && Utilities::isValidDateFormat($endDate,$supportedFormat)){


                    $this->logger->addInfo("start of financial revenue execution on restaurant ".$currentRestaurant->getCode(),['ImportWyndCommand']);

                    $this->getContainer()->get('toolbox.command.launcher')->execute("quick:financial:revenue:import --startDate=".$startDate." "."--endDate=".$endDate." "."--restaurantId=".$restaurantId);

                    $this->logger->addInfo("end of financial revenue execution on restaurant ".$currentRestaurant->getCode(),['ImportWyndCommand']);

                }

                else {

                    $this->logger->addError('Error in Financial revenue execution: The dates given are not in a valid format',['ImportWyndCommand']);
                }


            }


        } catch (\Exception $e) {
            $this->logger->addError(
                'Importing tickets exception :'.$e->getMessage()."\n"
                .$e->getTraceAsString(),
                ['ImportWyndCommand']
            );
            throw $e;
        }
    }

    public function insertTicket(
        $val,
        $type,
        $i,
        &$array_tickets,
        Restaurant $restaurant
    ) {
        if ($this->version == 'quick') {
            $employeeDiscountId = 10;
        } else {
            $employeeDiscountId = 203;
        }


        $ticket = new Ticket();
        $invoicenumber = sprintf('%.0f', $val[$type]['invoiceNumber']);
        $ticket
            ->setType($type)
            ->setDate(
                ($val[$type]['date'] != null) ? new \DateTime(
                    $val[$type]['date']
                ) : null
            )
            ->setStartDate(
                ($val[$type]['date_ticket_start'] != null) ? new \DateTime(
                    $val[$type]['date_ticket_start']
                ) : null
            )
            ->setEndDate(
                ($val[$type]['date_ticket_end'] != null) ? new \DateTime(
                    $val[$type]['date_ticket_end']
                ) : null
            )
            ->setNum(0)
            ->setInvoiceNumber($invoicenumber)
            ->setStatus(intval($val[$type]['status']))
            ->setInvoiceCancelled(strval($val[$type]['invoiceCancelled']))
            ->setPaid($val[$type]['paid'])
            ->setDeliveryTime(
                ($val[$type]['delivery_time'] != null) ? new \DateTime(
                    $val[$type]['delivery_time']
                ) : null
            )
            ->setOperator(intval($val[$type]['operator']))
            ->setOperatorName(
                isset($val[$type]['operatorName']) ? $val[$type]['operatorName']
                    : null
            )
            ->setResponsible($val[$type]['responsable'])
            ->setWorkstation(intval($val[$type]['workstation']))
            ->setWorkstationName(
                isset($val[$type]['cashDeskID']) ? $val[$type]['cashDeskID']
                    : null
            )
            ->setOriginId(
                isset($val[$type]['origin_id']) ? intval(
                    $val[$type]['origin_id']
                ) : null
            )
            ->setOrigin($val[$type]['origin'])
            ->setDestinationId(
                isset($val[$type]['destination']) ? intval(
                    $val[$type]['destination']
                ) : null
            )
            ->setDestination($val[$type]['destination'])
            ->setEntity(intval($val[$type]['entity']))
            ->setCustomer(intval($val[$type]['customer']))
            ->setOriginRestaurant($restaurant);
        if (floatval($val[$type]['total_ttc']) == 0 && empty($val['payments'])
            && (empty($val['lines'])
                || (count(
                        $val['lines']
                    ) == count($val[$type]['interventions'])))
        ) {


            echo 'empty payment we have an abondon here';

            $ticket->setStatus(Ticket::ABONDON_STATUS_VALUE)
                ->setCancelledFlag(true);
            $amount = 0;
            foreach ($val[$type]['interventions'] as $intKey => $int) {


                $amount += floatval($int['item']['price']) * intval(
                        $int['item']['quantity']
                    );
            }
            $ticket->setTotalTTC($amount);
            $this->em->persist($ticket);
            $this->em->flush();

            return;
        }


        if (!empty($val[$type]['discounts']) && !empty($val['lines'])
            && ($this->checkForMealTickets(
                    $val[$type]['discounts']
                )
                || (empty($val[$type]['payments'])
                    && floatval($val[$type]['total_ttc']) == 0))
        ) {

            if ($this->checkForMealTickets($val[$type]['discounts'])) {

                if (isset($val['lines'])) {
                    $totalHt = 0;
                    $totalTTC = 0;
                    foreach ($val['lines'] as $pLine => $l) {
                        $ProductSoldSupervision=$this->em->getRepository('Supervision:ProductSoldSupervision')->findOneBy(array('codePlu' => $l['product']));
                        $line = new TicketLine();
                        if ($l['discount_id'] == $employeeDiscountId) {
                            // item is part of an employee meal => adjust line

                            $line
                                ->setLine(intval($l['line']))
                                ->setPrice(floatval($l['price']))
                                ->setCategory(
                                    isset($l['category']) ? $l['category']
                                        : null
                                )
                                ->setDivision(
                                    isset($l['division']) ? intval(
                                        $l['division']
                                    ) : 0
                                )
                                ->setProduct($l['product'])
                                ->setLabel(
                                    isset($l['label']) ? $l['label'] : null
                                )
                                ->setDescription($l['description'])
                                ->setPlu(isset($l['plu']) ? $l['plu'] : null)
                                ->setCombo(
                                    isset($l['combo']) ? $l['combo'] : null
                                )
                                ->setComposition($l['composition'])
                                ->setParentLine(intval($l['parentline']))
                                ->setTva(floatval($l['tva']))
                                ->setIsDiscount(false)
                                ->setDiscountId(strval($l['discount_id']))
                                ->setDiscountCode(strval($l['discount_code']))
                                ->setDiscountLabel(strval($l['discount_label']))
                                ->setDiscountHt(0)
                                ->setDiscountTva(0)
                                ->setDiscountTtc(0)
                                //adjust the totals
                                ->setTotalHT(abs(floatval($l['discount_ht'])))
                                ->setTotalTTC(abs(floatval($l['discount_ttc'])))
                                ->setTotalTVA(
                                    abs(floatval($l['discount_tva']))
                                );
                            if($ProductSoldSupervision){
                                $line->setFlagVA($ProductSoldSupervision->isVenteAnnexe());
                            }

                            $totalHt += abs(floatval($l['discount_ht']));
                            $totalTTC += abs(floatval($l['discount_ttc']));
                        } else {
                            $line
                                ->setLine(intval($l['line']))
                                ->setPrice(floatval($l['price']))
                                ->setTotalTVA(floatval($l['total_tva']));
                                // set total ttc to include discount amount
                                    if($l['discount_id'] != '5061'){
                                        $line->setTotalTTC(
                                    floatval($l['total_TTC']) + abs(
                                        floatval($l['discount_ttc'])
                                    )
                                )
                                 ->setTotalHT(floatval($l['total_ht']));
                                            }else{
                                        $line->setTotalTTC(floatval($l['price']))
                                            ->setTotalHT(floatval($l['price']) -floatval($l['total_tva']));
                                    }
                            $line ->setCategory(
                                    isset($l['category']) ? $l['category']
                                        : null
                                )
                                ->setDivision(
                                    isset($l['division']) ? intval(
                                        $l['division']
                                    ) : 0
                                )
                                ->setProduct($l['product'])
                                ->setLabel(
                                    isset($l['label']) ? $l['label'] : null
                                )
                                ->setDescription($l['description'])
                                ->setPlu(isset($l['plu']) ? $l['plu'] : null)
                                ->setCombo(
                                    isset($l['combo']) ? $l['combo'] : null
                                )
                                ->setComposition($l['composition'])
                                ->setParentLine(intval($l['parentline']))
                                ->setTva(floatval($l['tva']))
                                ->setIsDiscount(boolval($l['is_discount']))
                                ->setDiscountId(strval($l['discount_id']))
                                ->setDiscountCode(strval($l['discount_code']))
                                ->setDiscountLabel(strval($l['discount_label']))
                                ->setDiscountHt(floatval($l['discount_ht']))
                                ->setDiscountTva(floatval($l['discount_tva']))
                                ->setDiscountTtc(floatval($l['discount_ttc']));
                            if($ProductSoldSupervision){
                                $line->setFlagVA($ProductSoldSupervision->isVenteAnnexe());
                            }
                        }

                        $line->setTicket($ticket);
                        $qty = $line->getTicket()->getInvoiceCancelled() == "1"
                            ? -(intval($l['quantity']))
                            : (intval(
                                $l['quantity']
                            ));
                        $line->setQty($qty);
                        $revenuPrice
                            = $this->revenuePricesService->calculateFinancialRevenueForTicketLine(
                            $line,
                            $restaurant
                        );
                        $line->setRevenuePrice($revenuPrice);
                        /**
                         * adding the new added fields
                         */
                        $line->setDate($ticket->getDate());
                        $line->setStartDate($ticket->getStartDate());
                        $line->setEndDate($ticket->getEndDate());
                        if ($restaurant->getId()) {
                            $line->setOriginRestaurantId($restaurant->getId());
                        }

                        if($ProductSoldSupervision){
                            $line->setFlagVA($ProductSoldSupervision->isVenteAnnexe());
                        }
                        $line->setStatus($ticket->getStatus());
                        $ticket->addLine(clone $line);

                    }
                    $ticket->setTotalHT(
                        $totalHt + floatval($val[$type]['total_ht'])
                    );
                    $ticket->setTotalTTC(
                        $totalTTC + floatval($val[$type]['total_ttc'])
                    );
                }

                // add a payment block of type meal ticket
                foreach ($val[$type]['discounts'] as $pKey => $discount) {
                    if ($discount["id"] == $employeeDiscountId) {
                        $block = array(
                            "id"       => 5,
                            "code"     => "BR",
                            "label"    => "Bon repas",
                            "amount"   => $discount["amount"],
                            "employee" => $discount["label"],
                        );
                        $val["payments"][] = $block;
                        unset($val[$type]["discounts"][$pKey]);
                    }
                }

            } else {
                $ticket->setTotalHT(floatval($val[$type]['total_ht']))
                    ->setTotalTTC(floatval($val[$type]['total_ttc']));


                //Ticket Lines
                if (isset($val['lines'])) {
                    foreach ($val['lines'] as $pLine => $l) {
                        $plu = isset($l['plu']) ? $l['plu'] : null;
                        $ProductSoldSupervision=$this->em->getRepository('Supervision:ProductSoldSupervision')->findOneBy(array('codePlu' => $l['product']));
                        //used to be is null
                        $line = new TicketLine();
                        $line
                            ->setLine(intval($l['line']))
                            ->setPrice(floatval($l['price']))
                            ->setTotalTVA(floatval($l['total_tva']));
                            // set total ttc to include discount amount
                            if($l['discount_id'] != '5061'){
                                $line->setTotalTTC(
                                    floatval($l['total_TTC']) + abs(
                                        floatval($l['discount_ttc'])
                                    )
                                )
                                    ->setTotalHT(floatval($l['total_ht']));
                            }else{
                                $line->setTotalTTC(floatval($l['price']))
                                    ->setTotalHT(floatval($l['price']) -floatval($l['total_tva']));
                            }
                             $line->setCategory(
                                isset($l['category']) ? $l['category']
                                    : null
                            )
                            ->setDivision(
                                isset($l['division']) ? intval(
                                    $l['division']
                                )
                                    : 0
                            )
                            ->setProduct($l['product'])
                            ->setLabel(
                                isset($l['label']) ? $l['label'] : null
                            )
                            ->setDescription($l['description'])
                            ->setPlu(isset($l['plu']) ? $l['plu'] : null)
                            ->setCombo(
                                isset($l['combo']) ? $l['combo'] : null
                            )
                            ->setComposition($l['composition'])
                            ->setParentLine(intval($l['parentline']))
                            ->setTva(floatval($l['tva']))
                            ->setIsDiscount(boolval($l['is_discount']))
                            ->setDiscountId(strval($l['discount_id']))
                            ->setDiscountCode(strval($l['discount_code']))
                            ->setDiscountLabel(strval($l['discount_label']))
                            ->setDiscountHt(floatval($l['discount_ht']))
                            ->setDiscountTva(floatval($l['discount_tva']))
                            ->setDiscountTtc(floatval($l['discount_ttc']));
                        $line->setTicket($ticket);
                        $qty = $line->getTicket()->getInvoiceCancelled() == "1"
                            ? -(intval($l['quantity']))
                            : (intval(
                                $l['quantity']
                            ));
                        $line->setQty($qty);
                        $revenuPrice
                            = $this->revenuePricesService->calculateFinancialRevenueForTicketLine(
                            $line,
                            $restaurant
                        );

                        $line->setRevenuePrice($revenuPrice);
                        /**
                         * adding the new added fields
                         */
                        $line->setDate($ticket->getDate());
                        $line->setStartDate($ticket->getStartDate());
                        $line->setEndDate($ticket->getEndDate());
                        if ($restaurant->getId()) {
                            $line->setOriginRestaurantId($restaurant->getId());
                        }
                        $line->setStatus($ticket->getStatus());
                        if($ProductSoldSupervision){
                            $line->setFlagVA($ProductSoldSupervision->isVenteAnnexe());
                        }
                        $ticket->addLine(clone $line);

                    }
                }

                // add a payment block of type cash and value of 0
                $block = array(
                    "id"     => 1,
                    "label"  => "Cash",
                    "amount" => 0,
                );
                $val["payments"][] = $block;


            }

        } else {
            $ticket->setTotalHT(floatval($val[$type]['total_ht']))
                ->setTotalTTC(floatval($val[$type]['total_ttc']));


            //Ticket Lines
            if (isset($val['lines'])) {
                foreach ($val['lines'] as $pLine => $l) {
                    $plu = isset($l['plu']) ? $l['plu'] : null;
                    $ProductSoldSupervision=$this->em->getRepository('Supervision:ProductSoldSupervision')->findOneBy(array('codePlu' => $l['product']));
                    //used to be is null
                    $line = new TicketLine();
                    $line
                        ->setLine(intval($l['line']))
                        ->setPrice(floatval($l['price']))
                        ->setTotalTVA(floatval($l['total_tva']));
                        // set total ttc to include discount amount
                        if($l['discount_id'] != '5061'){
                            $line->setTotalTTC(
                                floatval($l['total_TTC']) + abs(
                                    floatval($l['discount_ttc'])
                                )
                            )
                                ->setTotalHT(floatval($l['total_ht']));
                        }else{
                            $line->setTotalTTC(floatval($l['price']))
                                ->setTotalHT(floatval($l['price']) -floatval($l['total_tva']));
                        }
                       $line ->setCategory(
                            isset($l['category']) ? $l['category'] : null
                        )
                        ->setDivision(
                            isset($l['division']) ? intval($l['division']) : 0
                        )
                        ->setProduct($l['product'])
                        ->setLabel(isset($l['label']) ? $l['label'] : null)
                        ->setDescription($l['description'])
                        ->setPlu(isset($l['plu']) ? $l['plu'] : null)
                        ->setCombo(isset($l['combo']) ? $l['combo'] : null)
                        ->setComposition($l['composition'])
                        ->setParentLine(intval($l['parentline']))
                        ->setTva(floatval($l['tva']))
                        ->setIsDiscount(boolval($l['is_discount']))
                        ->setDiscountId(strval($l['discount_id']))
                        ->setDiscountCode(strval($l['discount_code']))
                        ->setDiscountLabel(strval($l['discount_label']))
                        ->setDiscountHt(floatval($l['discount_ht']))
                        ->setDiscountTva(floatval($l['discount_tva']))
                        ->setDiscountTtc(floatval($l['discount_ttc']));
                    $line->setTicket($ticket);
                    $qty = $line->getTicket()->getInvoiceCancelled() == "1"
                        ? -(intval($l['quantity']))
                        : (intval(
                            $l['quantity']
                        ));
                    $line->setQty($qty);
                    $revenuPrice
                        = $this->revenuePricesService->calculateFinancialRevenueForTicketLine(
                        $line,
                        $restaurant
                    );
                    $line->setRevenuePrice($revenuPrice);
                    /**
                     * adding the new added fields
                     */
                    $line->setDate($ticket->getDate());
                    $line->setStartDate($ticket->getStartDate());
                    $line->setEndDate($ticket->getEndDate());
                    if ($restaurant->getId()) {
                        $line->setOriginRestaurantId($restaurant->getId());
                    }
                    $line->setStatus($ticket->getStatus());
                    if($ProductSoldSupervision){
                        $line->setFlagVA($ProductSoldSupervision->isVenteAnnexe());
                    }
                    $ticket->addLine(clone $line);

                }
            }
        }

        //Interventions
        if (isset($val[$type]['interventions'])) {
            foreach ($val[$type]['interventions'] as $intKey => $int) {
                $intervention = new TicketIntervention();
                $intervention
                    ->setAction($int['action'])
                    ->setManagerID(intval($int['managerID']))
                    ->setManagerName($int['managerName'])
                    ->setItemLabel($int['item']['label'])
                    ->setDate(
                        ($int['date'] != null) ? new \DateTime(
                            $val[$type]['date']
                        ) : null
                    )
                    ->setPostTotal($int['postTotal']);
                if ($intervention->getAction()
                    === TicketIntervention::ABONDON_ACTION
                ) {
                    $intervention->setItemAmount($int['item']['amount']);
                    $ticket->setCancelledFlag(true);
                } else {
                    if ($intervention->getAction()
                        === TicketIntervention::DELETE_ACTION
                    ) {
                        $intervention
                            ->setItemId($int['item']['id'])
                            ->setItemPrice(floatval($int['item']['price']))
                            //->setItemPLU($int['item']['external_ref'])
                            ->setItemQty(intval($int['item']['quantity']));

                        // Sub Item
                        if (isset($int['item']['sub'])) {
                            foreach ($int['item']['sub'] as $subKey => $sub) {
                                $subItem = new TicketInterventionSub();
                                $subItem
                                    ->setSubId($sub['id'])
                                    ->setSubLabel($sub['label'])
                                    ->setSubPrice($sub['price'])
                                    ->setSubPLU($sub['external_ref'])
                                    ->setSubQty($sub['quantity'])
                                    ->setIntervention($intervention);
                                $intervention->addSub(clone $subItem);
                            }
                        }
                    } else {
                        if ($intervention->getAction()
                            === TicketIntervention::DELETE_PAYMENT_ACTION
                        ) {
                            $intervention
                                ->setItemAmount($int['item']['amount'])
                                ->setItemCode($int['item']['code']);
                        } elseif ($intervention->getAction()
                            === TicketIntervention::DECREASE_QUANTITY_ACTION
                        ) {
                            $intervention->setItemPLU(
                                $int['item']['external_ref']
                            )
                                ->setItemId($int['item']['id'])
                                ->setItemPrice($int['item']['price'])
                                ->setItemLabel($int['item']['label'])
                                ->setItemQty($int['item']['quantity']);
                        }
                    }
                }

                $ticket->addIntervention(clone $intervention);
            }
        }

        //Ticket Payments
        if (isset($val['payments'])) {
            $this->processTicketPayment($val, $ticket);

            if (isset($val[$type]['invoiceCancelled'])
                && !is_null(
                    $val[$type]['invoiceCancelled']
                )
            ) {
                $existingTicket = $this->em->getRepository('Financial:Ticket')
                    ->findOneBy(
                        [
                            'invoiceNumber'    => $val[$type]['invoiceCancelled'],
                            'date'             => $ticket->getDate(),
                            'originRestaurant' => $restaurant,
                        ]
                    );
                if (!is_null($existingTicket)) {
                    if (!$existingTicket->isCounted()) {
                        foreach ($existingTicket->getPayments() as $payment) {
                            $_payment = clone $payment;
                            $ticket->addPayment($_payment);
                            $this->em->persist($_payment);
                        }
                    } else {
                        $ticket->setCountedCanceled(true);
                    }
                } elseif (isset($array_tickets[$val[$type]['invoiceCancelled']])) {
                    $this->processTicketPayment(
                        $array_tickets[$val[$type]['invoiceCancelled']],
                        $ticket
                    );
                }
            }
        }

        //Ticket Discounts
        if (isset($val[$type]['discounts'])) {
           /* $roundings = array();

            // search for roundings on the discount block
            foreach ($val[$type]['discounts'] as $pKey => $p) {
                if ($p['code'] == 'ROUNDING') {
                    $roundings[] = $pKey;
                }
            }*/

           // if (sizeof($roundings) == 0) {
                foreach ($val[$type]['discounts'] as $pKey => $p) {
                    $payment = new TicketPayment();
                    $payment
                        ->setType(TicketPayment::DISCOUNT_TYPE)
                        ->setNum(intval($pKey))
                        ->setLabel($p['label'])
                        ->setAmount(floatval($p['amount']));
                    $ticket->addPayment(clone $payment);
                }
            //}

           /* else {
                foreach ($val[$type]['discounts'] as $pKey => $p) {
                    if ($p['code'] != 'ROUNDING') {
                        if (sizeof($roundings) != 0) {
                            $key = $roundings[0];
                            $rounding
                                = $val[$type]['discounts'][$key]['amount'];
                            $payment = new TicketPayment();
                            $payment
                                ->setType(TicketPayment::DISCOUNT_TYPE)
                                ->setNum(intval($pKey))
                                ->setLabel($p['label'])
                                ->setAmount(
                                    floatval($p['amount']) + floatval($rounding)
                                );
                            $ticket->addPayment(clone $payment);
                            array_shift($roundings);
                        } else {
                            $payment = new TicketPayment();
                            $payment
                                ->setType(TicketPayment::DISCOUNT_TYPE)
                                ->setNum(intval($pKey))
                                ->setLabel($p['label'])
                                ->setAmount(floatval($p['amount']));
                            $ticket->addPayment(clone $payment);
                        }
                    }
                }
            } */
        }

        $this->em->persist($ticket);
        $this->productPurchasedMvmtService->createMvmtEntryForTicket(
            $ticket,
            $restaurant
        );
        if ($i % 50 === 0) {
            $this->em->flush();
        }
    }

    /**
     * @param        $numberInvoiceCanceledToBedeleted
     * @param Ticket $existingTicket
     */
    private function removeTicket(
        $numberInvoiceCanceledToBedeleted,
        $existingTicket
    ) {
        foreach ($existingTicket->getLines() as $line) {
            $this->productPurchasedMvmtService->deleteMvmtEntriesByTypeAndSourceId(
                ProductPurchasedMvmt::SOLD_TYPE,
                $line->getId()
            );
        }

        if ($existingTicket->isCounted()) {
            $existingTicket->setCountedCanceled(true)
                ->setSynchronized(false);
        } else {
            $this->em->remove($existingTicket);
        }

        $this->em->flush();
        $this->logger->addInfo(
            "Canceled Ticket deleted with success :"
            .$numberInvoiceCanceledToBedeleted
        );
    }

    /**
     * @param array  $val
     * @param Ticket $ticket
     */
    private function processTicketPayment($val, &$ticket)
    {
        foreach ($val['payments'] as $pKey => $p) {
            $payment = new TicketPayment();
            $payment
                ->setType(TicketPayment::PAYMENT_TYPE)
                //->setNum(intval($pKey))
                ->setLabel($p['label'])
                ->setAmount(floatval($p['amount']));
            if (isset($p['employee'])) {
                $payment->setFirstName($p['employee']);
            }
            if (isset($p['code'])) {
                $payment->setCode($p['code']);
            }
            if (isset($p['id'])) {
                $payment->setIdPayment($p['id']);

                if ($p['id'] == strval(TicketPayment::EDENRED)
                    || $p['id'] == strval(TicketPayment::E_SODEXO)
                    || $p['id'] == strval(TicketPayment::PAYFAIR)
                ) {
                    $payment->setElectronic(true);
                }

            }
            $ticket->addPayment(clone $payment);
        }
    }


    public function checkForMealTickets($discounts)
    {
        if ($this->version == "quick") {
            $employeeDiscountId = 10;
        } else {
            $employeeDiscountId = 203;
        }
        foreach ($discounts as $pKey => $p) {
            if ($p['id'] == $employeeDiscountId) {
                return true;
            }
        }

        return false;
    }


    public function checkforTestInterventions($interventions)
    {

        $test = false;
        foreach ($interventions as $intkey => $intervention) {

            if ($intervention["managerID"] == 9999) {

                $test = true;

                break;

            }


        }

        return $test;
    }
}


