<?php

namespace AppBundle\Command;

use AppBundle\Financial\Entity\PaymentMethod;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class InitPaymentMethodsCommand extends ContainerAwareCommand
{

    private $franchiseType;
    private $em;
    private $logger;
    private $translator;
    private $count;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:init:payment_methods')
            ->addOption(
                'franchise',
                'f',
                InputOption::VALUE_REQUIRED,
                'Restaurant franchise type (Quick or BurgerKing)',
                ""
            )
            ->setDescription(
                'Command to init all default payment methods for the platform.'
            );
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
        $this->logger = $this->getContainer()->get(
            'monolog.logger.tickets_import'
        );
        $this->translator = $this->getContainer()->get("translator");
        $this->count = 0;

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $option = $input->getOption("franchise");
        if (trim($option) !== "") {
            if (strtolower($option) === "q"
                || strtolower($option) === "quick"
            ) {
                $this->franchiseType = "QUICK";
            } elseif (strtolower($option) === "b"
                || strtolower($option) === "burgerking"
            ) {
                $this->franchiseType = "BURGER KING";
            } else {
                $output->writeln(
                    "Invalid option passed ! Please provide a valid option : q or quick for Quick / b or burgerking for Burger King."
                );

                return;
            }
        } else {

            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Please select the restaurant franchise type:',
                array('BURGER KING', 'QUICK'),
                0
            );
            $question->setErrorMessage('Franchise type %s is invalid.');
            $this->franchiseType = $helper->ask($input, $output, $question);
        }
        /**********************************************/
        /******Burger king payment methods init *******/
        if (strtoupper($this->franchiseType) === "BURGER KING") {
            $output->writeln(
                "---> Initialising payments methods for BURGER KING..."
            );

            // Real Cash
            $paymentMethod = $this->em->getRepository(PaymentMethod::class)
                ->findOneBy(
                    [
                        "type" => PaymentMethod::REAL_CASH_TYPE,
                    ]
                );
            if (is_null($paymentMethod)) {
                $this->insertNewPaymentMethod(
                    PaymentMethod::REAL_CASH_TYPE,
                    [10, 20, 50],
                    'Cash'
                );
            } else {
                $output->writeln(
                    "-> Payment Method [REAL_CASH_TYPE] already exist! Skipping it..."
                );
            }

            // Ticket Restaurant
            $paymentMethod = $this->em->getRepository(PaymentMethod::class)
                ->findOneBy(
                    [
                        "type" => PaymentMethod::TICKET_RESTAURANT_TYPE,
                    ]
                );
            if (is_null($paymentMethod)) {
                $this->insertNewPaymentMethod(
                    PaymentMethod::TICKET_RESTAURANT_TYPE,
                    [
                        "type"       => "Sodexo",
                        "electronic" => false,
                        "values"     => [4.5, 7, 10],
                        "id"         => "300",
                        "code"       => 'SODEXO',
                    ],
                    'Sodexo'
                );
                $this->insertNewPaymentMethod(
                    PaymentMethod::TICKET_RESTAURANT_TYPE,
                    [
                        "type"       => "Chèque resto Lux",
                        "electronic" => false,
                        "values"     => [5, 10],
                        "id"         => "500",
                        "code"       => 'CHQLUX',
                    ],
                    'Chèque resto Lux'
                );
                $this->insertNewPaymentMethod(
                    PaymentMethod::TICKET_RESTAURANT_TYPE,
                    [
                        "type"       => "Edenred",
                        "electronic" => true,
                        "values"     => [1],
                        "id"         => "108",
                        "code"       => 'EDENRED',
                    ],
                    'Edenred'
                );
                $this->insertNewPaymentMethod(
                    PaymentMethod::TICKET_RESTAURANT_TYPE,
                    [
                        "type"       => "Epasssodexo",
                        "electronic" => true,
                        "values"     => [1],
                        "id"         => "109",
                        "code"       => 'E SODEXO',
                    ],
                    'Epasssodexo'
                );
                $this->insertNewPaymentMethod(
                    PaymentMethod::TICKET_RESTAURANT_TYPE,
                    [
                        "type"       => "Payfair",
                        "electronic" => true,
                        "values"     => [1],
                        "id"         => "110",
                        "code"       => 'PAYFAIR',
                    ],
                    'Payfair'
                );
                $this->insertNewPaymentMethod(
                    PaymentMethod::TICKET_RESTAURANT_TYPE,
                    [
                        "type"       => "Ticket restaurant",
                        "electronic" => false,
                        "values"     => [5, 10],
                        "id"         => "400",
                        "code"       => 'TICKETREST',
                    ],
                    'Ticket restaurant'
                );
            } else {
                $output->writeln(
                    "-> Payment Method [TICKET_RESTAURANT_TYPE] already exist! Skipping it..."
                );
            }

            // Check Quick payment methods

            $paymentMethods = $this->em->getRepository(PaymentMethod::class)
                ->findBy(
                    [
                        "type" => PaymentMethod::CHECK_QUICK_TYPE,
                    ]
                );

            if (empty($paymentMethods)) {


                $this->insertNewPaymentMethod(
                    PaymentMethod::CHECK_QUICK_TYPE,
                    [
                        "type"   => "Cheque 5€",
                        "values" => [5],
                        "id"     => "550",
                        "code"   => 'Cheque 5€',
                    ],
                    'Cheque 5€'
                );
                $this->insertNewPaymentMethod(
                    PaymentMethod::CHECK_QUICK_TYPE,
                    [
                        "type"   => "king 50 euros",
                        "values" => [50],
                        "id"     => "551",
                        "code"   => 'KING 50€',
                    ],
                    'King 50€'
                );
                $this->insertNewPaymentMethod(
                    PaymentMethod::CHECK_QUICK_TYPE,
                    [
                        "type"   => "Loc cheque",
                        "values" => [25],
                        "id"     => "552",
                        "code"   => 'Loc cheque',
                    ],
                    'Loc cheque'
                );
                $this->insertNewPaymentMethod(
                    PaymentMethod::CHECK_QUICK_TYPE,
                    [
                        "type"   => "Radio C 50 euro",
                        "values" => [50],
                        "id"     => "553",
                        "code"   => 'Radio C 50€',
                    ],
                    "Radio C50€"
                );

                $this->insertNewPaymentMethod(
                    PaymentMethod::CHECK_QUICK_TYPE,

                    [
                      "type" =>"Bongo V",
                      "values" => [15],
                      "id"   => "554",
                      "code" => "Bongo V"

                    ],

                    "Bongo V"

                );


            } else {
                $output->writeln(
                    "-> Payment Method [CHECK_QUICK_TYPE] already exist! Skipping it..."
                );
            }

            // Foreign Currency
            $paymentMethod = $this->em->getRepository(PaymentMethod::class)
                ->findOneBy(
                    [
                        "type" => PaymentMethod::FOREIGN_CURRENCY_TYPE,
                    ]
                );
            if (is_null($paymentMethod)) {
                $this->insertNewPaymentMethod(
                    PaymentMethod::FOREIGN_CURRENCY_TYPE,
                    null,
                    'Argent étranger'
                );
            } else {
                $output->writeln(
                    "-> Payment Method [FOREIGN_CURRENCY_TYPE] already exist! Skipping it..."
                );
            }

            // Bank Card
            $paymentMethod = $this->em->getRepository(PaymentMethod::class)
                ->findOneBy(
                    [
                        "type" => PaymentMethod::BANK_CARD_TYPE,
                    ]
                );
            if (!is_null($paymentMethod)) {
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "V-PAY",
//                        "id"   => "106",
//                    ],
//                    'Vpay'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "CB",
//                        "id"   => "100",
//                    ],
//                    'Carte Bancaire'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "MASTERCARD",
//                        "id"   => "103",
//                    ],
//                    'MasterCard'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "AMEX",
//                        "id"   => "107",
//                    ],
//                    'American Express'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "BANCONTACT",
//                        "id"   => "102",
//                    ],
//                    'Bancontact'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "MAESTRO",
//                        "id"   => "104",
//                    ],
//                    'Maestro'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "VISA",
//                        "id"   => "105",
//                    ],
//                    'Visa'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "KIOSK FB",
//                        "id"   => "600",
//                    ],
//                    'Kiosk FB'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "OTHER CARD",
//                        "id"   => "601",
//                    ],
//                    'Autre carte'
//                );
//
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "DELIVEROO",
//                        "id"   => "150",
//                    ],
//                    'Deliveroo'
//
//                );
//
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//
//                        "code" => "TAKEAWAY",
//                        "id"   => "151",
//                    ],
//
//                    'Take away'
//
//                );
//
//
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//
//                        "code" => "UBER EATS",
//                        "id"   => "113",
//                    ],
//
//                    'Uber Eats'
//
//                );
//
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//
//                        "code" => "FOOSTIX",
//                        "id"   => "115",
//                    ],
//
//                    'Foostix'
//
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//
//                        "code" => "EASY2EAT",
//                        "id"   => "116",
//                    ],
//
//                    'Easy2eat'
//
//                );

//                 $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//
//                        "code" => "PREPAID",
//                        "id"   => "702",
//                    ],
//
//                    'Prepaid'
//
//                )
                      $this->insertNewPaymentMethod(
                          PaymentMethod::BANK_CARD_TYPE,
                          [

                              "code" => "Goosty",
                              "id"   => "117",
                          ],

                          'Goosty'

                      )
                 ;


            } else {
                $output->writeln(
                    "-> Payment Method [BANK_CARD_TYPE] already exist! Skipping it..."
                );
            }


        }

        /**********************************************/
        /*********Quick payment methods init **********/
        if (strtoupper($this->franchiseType) === "QUICK") {
            $output->writeln("---> Initialising payments methods for QUICK...");

            // Real Cash
            $paymentMethod = $this->em->getRepository(PaymentMethod::class)
                ->findOneBy(
                    [
                        "type" => PaymentMethod::REAL_CASH_TYPE,
                    ]
                );
            if (is_null($paymentMethod)) {
                $this->insertNewPaymentMethod(
                    PaymentMethod::REAL_CASH_TYPE,
                    [10, 20, 50],
                    'Cash'
                );
            } else {
                $output->writeln(
                    "-> Payment Method [REAL_CASH_TYPE] already exist! Skipping it..."
                );
            }

            // Ticket Restaurant
            $paymentMethod = $this->em->getRepository(PaymentMethod::class)
                ->findOneBy(
                    [
                        "type" => PaymentMethod::TICKET_RESTAURANT_TYPE,
                    ]
                );
            if (is_null($paymentMethod)) {
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::TICKET_RESTAURANT_TYPE,
//                    [
//                        "type"       => "Sodexo",
//                        "electronic" => false,
//                        "values"     => [4.5, 7, 10],
//                        "id"         => "120",
//                        "code"       => 'SODEXO',
//                    ],
//                    'Sodexo'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::TICKET_RESTAURANT_TYPE,
//                    [
//                        "type"       => "Chèque resto Lux",
//                        "electronic" => false,
//                        "values"     => [5, 10],
//                        "id"         => "132",
//                        "code"       => 'CHQLUX',
//                    ],
//                    'Chèque resto Lux'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::TICKET_RESTAURANT_TYPE,
//                    [
//                        "type"       => "Edenred",
//                        "electronic" => true,
//                        "values"     => [1],
//                        "id"         => "108",
//                        "code"       => 'EDENRED',
//                    ],
//                    'Edenred'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::TICKET_RESTAURANT_TYPE,
//                    [
//                        "type"       => "Epasssodexo",
//                        "electronic" => true,
//                        "values"     => [1],
//                        "id"         => "109",
//                        "code"       => 'E SODEXO',
//                    ],
//                    'Epasssodexo'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::TICKET_RESTAURANT_TYPE,
//                    [
//                        "type"       => "Payfair",
//                        "electronic" => true,
//                        "values"     => [1],
//                        "id"         => "110",
//                        "code"       => 'PAYFAIR',
//                    ],
//                    'Payfair'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::TICKET_RESTAURANT_TYPE,
//                    [
//                        "type"       => "Ticket restaurant",
//                        "electronic" => false,
//                        "values"     => [5, 10],
//                        "id"         => "130",
//                        "code"       => 'TICKETREST',
//                    ],
//                    'Ticket restaurant'
//                );
//                      $this->insertNewPaymentMethod(
//                    PaymentMethod::TICKET_RESTAURANT_TYPE,
//                    [
//                        "type"       => "Cons Edenr",
//                        "electronic" => false,
//                        "values"     => [1],
//                        "id"         => "450",
//                        "code"       => 'CONSEDENR',
//                    ],
//                    'Cons Edenr'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::TICKET_RESTAURANT_TYPE,
//                    [
//                        "type"       => "Cons Sodex",
//                        "electronic" => false,
//                        "values"     => [1],
//                        "id"         => "451",
//                        "code"       => 'CONSODEX',
//                    ],
//                    'Cons Sodex'
//                );
            } else {
                $output->writeln(
                    "-> Payment Method [TICKET_RESTAURANT_TYPE] already exist! Skipping it..."
                );
            }

            //Check Quick

            $paymentMethods = $this->em->getRepository(PaymentMethod::class)
                ->findBy(
                    [
                        "type" => PaymentMethod::CHECK_QUICK_TYPE,
                    ]
                );

            if (empty($paymentMethods)) {

                $this->insertNewPaymentMethod(
                    PaymentMethod::CHECK_QUICK_TYPE,
                    [
                        "type"   => "Cheque 5€",
                        "values" => [5],
                        "id"     => "131",
                        "code"   => 'Cheque 5€',
                    ],
                    'Cheque 5€'
                );

                $this->insertNewPaymentMethod(
                    PaymentMethod::CHECK_QUICK_TYPE,
                    [
                        "type"   => "Voucher Quick",
                        "values" => [10],
                        "id"     => "133",
                        "code"   => 'VOUCHER QUICK',
                    ],
                    'Voucher Quick'
                );
            } else {

                $output->writeln(
                    "-> Payment Method [CHECK_QUICK_TYPE] already exist! Skipping it..."
                );
            }


            // Foreign Currency
            $paymentMethod = $this->em->getRepository(PaymentMethod::class)
                ->findOneBy(
                    [
                        "type" => PaymentMethod::FOREIGN_CURRENCY_TYPE,
                    ]
                );
            if (is_null($paymentMethod)) {
                $this->insertNewPaymentMethod(
                    PaymentMethod::FOREIGN_CURRENCY_TYPE,
                    null,
                    'Argent étranger'
                );
            } else {
                $output->writeln(
                    "-> Payment Method [FOREIGN_CURRENCY_TYPE] already exist! Skipping it..."
                );
            }

            // Bank Card
            $paymentMethod = $this->em->getRepository(PaymentMethod::class)
                ->findOneBy(
                    [
                        "type" => PaymentMethod::BANK_CARD_TYPE,
                    ]
                );
            if (!is_null($paymentMethod)) {
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "V-PAY",
//                        "id"   => "106",
//                    ],
//                    'Vpay'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "CB",
//                        "id"   => "2",
//                    ],
//                    'Carte Bancaire'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "MASTERCARD",
//                        "id"   => "103",
//                    ],
//                    'MasterCard'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "AMEX",
//                        "id"   => "107",
//                    ],
//                    'American Express'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "BANCONTACT",
//                        "id"   => "102",
//                    ],
//                    'Bancontact'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "MAESTRO",
//                        "id"   => "104",
//                    ],
//                    'Maestro'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "VISA",
//                        "id"   => "105",
//                    ],
//                    'Visa'
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "OTHER CARD",
//                        "id"   => "600",
//                    ],
//                    'Autre carte'
//                );
//
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "KIOSK FB",
//                        "id"   => "601",
//                    ],
//                    'Kiosk FB'
//                );
//
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//                        "code" => "DELIVEROO",
//                        "id"   => "150",
//                    ],
//                    'Deliveroo'
//
//                );
//
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//
//                        "code" => "TAKEAWAY",
//                        "id"   => "151",
//                    ],
//
//                    'Take away'
//
//                );
//
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//
//                        "code" => "UBER EATS",
//                        "id"   => "113",
//                    ],
//
//                    'Uber Eats'
//
//                );
//
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//
//                        "code" => "FOOSTIX",
//                        "id"   => "115",
//                    ],
//
//                    'Foostix'
//
//                );
//                $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//
//                        "code" => "EASY2EAT",
//                        "id"   => "116",
//                    ],
//
//                    'Easy2eat '
//
//                );
//
//               $this->insertNewPaymentMethod(
//                    PaymentMethod::BANK_CARD_TYPE,
//                    [
//
//                        "code" => "PREPAID",
//                        "id"   => "702",
//                    ],
//
//                    'Prepaid'
//
//                )
                $this->insertNewPaymentMethod(
                    PaymentMethod::BANK_CARD_TYPE,
                    [

                        "code" => "Goosty",
                        "id"   => "117",
                    ],

                    'Goosty'

                );

            } else {
                $output->writeln(
                    "-> Payment Method [BANK_CARD_TYPE] already exist! Skipping it..."
                );
            }

        }
        $output->writeln($this->count." payment methods added.");
        $output->writeln(
            "==> Payment methods initialization ended succsessfully <=="
        );

    }


    public function insertNewPaymentMethod(
        $type,
        $value,
        $labelValue = null,
        $labelTranslation = null
    ) {
        $paymentMethod = new PaymentMethod();
        $paymentMethod->setType($type)
            ->setValue($value)
            ->setLabel($labelValue)
            ->setActive(true);
        if ($labelTranslation != null) {
            $paymentMethod->addLabelTranslation('nl', $labelTranslation);
        }
        $this->em->persist($paymentMethod);
        $paymentMethod->setGlobalId($paymentMethod->getId());
        $this->em->flush();
        $this->count++;
        echo "\n - Payment method Added : ".$type."   -->   ".$labelValue." \n";
    }
}
