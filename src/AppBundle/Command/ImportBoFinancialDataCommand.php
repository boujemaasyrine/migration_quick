<?php

namespace AppBundle\Command;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\AdminClosingTmp;
use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Entity\CashboxBankCard;
use AppBundle\Financial\Entity\CashboxBankCardContainer;
use AppBundle\Financial\Entity\CashboxCheckQuick;
use AppBundle\Financial\Entity\CashboxCheckQuickContainer;
use AppBundle\Financial\Entity\CashboxCheckRestaurantContainer;
use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\CashboxDiscountContainer;
use AppBundle\Financial\Entity\CashboxForeignCurrency;
use AppBundle\Financial\Entity\CashboxForeignCurrencyContainer;
use AppBundle\Financial\Entity\CashboxMealTicketContainer;
use AppBundle\Financial\Entity\CashboxRealCashContainer;
use AppBundle\Financial\Entity\CashboxTicketRestaurant;
use AppBundle\Financial\Entity\ChestCashboxFund;
use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Financial\Entity\ChestExchange;
use AppBundle\Financial\Entity\ChestExchangeFund;
use AppBundle\Financial\Entity\ChestSmallChest;
use AppBundle\Financial\Entity\ChestTirelire;
use AppBundle\Financial\Entity\Deposit;
use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Entity\Expense;
use AppBundle\Financial\Entity\FinancialRevenue;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Financial\Entity\TicketPayment;
use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\Merchandise\Entity\CaPrev;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Staff\Entity\Employee;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ImportBoFinancialDataCommand extends ContainerAwareCommand
{
    /*
 * Imported data :
 * - Withdrawal
 * - Envelope
 * - Expense
 * - Deposit
 * - CashboxBankCardContainer
 * - CashboxCheckQuickContainer
 * - CashboxCheckRestaurantContainer
 * - CashboxForeignCurrencyContainer
 * - CashboxMealTicketContainer
 * - CashboxRealCashContainer
 * - CashboxDiscountContainer
 * - CashboxCount
 * - ChestSmallChest
 * - ChestCashboxFund
 * - ChestExchangeFund
 * - ChestCount
 */

    /**
     * @var EntityManager
     */
    private $em;

    private $dataDir;

    private $logger;
    private $restaurant;
    private $restaurantCode;


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:import:bo:financial:data')
            ->addArgument('restaurantCode', InputArgument::OPTIONAL)
            ->setDescription('Import restaurant financial data form json file exported by a BO instance.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/saas/";
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
            $this->restaurantCode = trim($input->getArgument('restaurantCode'));
        } else {
            $helper = $this->getHelper('question');
            $question = new Question(
                'Please enter restaurant code (found at the end of json file name : financialData_restaurant_xxxx.json ) :'
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
            $this->restaurantCode = $helper->ask($input, $output, $question);
        }
        $filename = "financialData_restaurant_".$this->restaurantCode.".json";
        $filePath = $this->dataDir.$filename;

        if (!file_exists($filePath)) {
            $output->writeln("No import file with the '".$this->restaurantCode."' restaurant code found !");
            return;
        }
        try {
            $fileData = file_get_contents($filePath);
            $financialData = json_decode($fileData, true);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return;
        }

        /************ Start the import process *****************/

        try {
            $this->restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($financialData['restaurant_code']);

            if (!$this->restaurant) {
                $output->writeln("No restaurant with the '".$financialData['restaurant_code']."' exist! Command failed... ");
                $output->writeln("->Please add this restaurant first.");
                return;
            }
            $output->writeln("Restaurant ".$this->restaurant->getName()." financial data import started...");
            $restaurantId=$this->restaurant->getId();
            $batchCounter = 0;

            /////////////////////////////////////////////////
            //import withdrawals
            $output->writeln("Importing Withdrawals...");
            $withdrawals=$financialData['withdrawals'];

            $progress = new ProgressBar($output, count($withdrawals));
            $progress->start();
            $addedWithdrawals=0;
            $updatedWithdrawals=0;
            $skippedWithdrawals=0;
            foreach ($withdrawals as $withdrawal) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($withdrawal) || !array_key_exists('id', $withdrawal)) {
                    continue;
                }

                $withdrawalEntity = $this->em->getRepository(Withdrawal::class)->findOneBy(
                    array(
                        "importId" => $withdrawal['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );
                if (!$withdrawalEntity) {
                    $withdrawalEntity = new Withdrawal();
                } else {
                    $isUpdate = true;
                }

                $date = new \DateTime($withdrawal['date']['date']);
                $createdAt = new \DateTime($withdrawal['createdAt']['date']);

                if(empty($withdrawal['member']['wyndId'])){
                    $userName=$withdrawal['member']['username'];
                }else{
                    $userName=$this->restaurantCode."_".$withdrawal['member']['username'];
                }
                $member = $this->em->getRepository(Employee::class)->findOneBy(
                    array(
                        "username" => $userName
                    )
                );
                if ($member) {
                    $withdrawalEntity->setMember($member);
                } else {
                    $skippedWithdrawals++;
                    $this->logger->info('Withdrawal Skipped because Member doesn\'t exist : ', array("withdrawalId" => $withdrawal['id'], "username" => $withdrawal['member']['username'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }

                unset($userName);
                if(empty($withdrawal['responsible']['wyndId'])){
                    $userName=$withdrawal['responsible']['username'];
                }else{
                    $userName=$this->restaurantCode."_".$withdrawal['responsible']['username'];
                }
                $responsible = $this->em->getRepository(Employee::class)->findOneBy(
                    array(
                        "username" => $userName
                    )
                );
                if ($responsible) {
                    $withdrawalEntity->setResponsible($responsible);
                } else {
                    $skippedWithdrawals++;
                    $this->logger->info('Withdrawal Skipped because Responsable doesn\'t exist : ', array("withdrawalId" => $withdrawal['id'], "username" => $withdrawal['member']['username'], "Restaurant" => $this->restaurant->getName()));
                    continue;
                }

                $withdrawalEntity
                    ->setDate($date)
                    ->setCreatedAt($createdAt)
                    ->setAmountWithdrawal($withdrawal['amountWithdrawal'])
                    ->setStatusCount($withdrawal['statusCount'])
                    ->setSynchronized(boolval($withdrawal['synchronized']))
                    ->setEnvelopeId($withdrawal['envelopeId'])
                    ->setImportId($withdrawal['id'] . "_" . $this->restaurantCode)
                    ->setOriginRestaurant($this->em->getReference(Restaurant::class, $restaurantId));

                $this->em->persist($withdrawalEntity);
                $isUpdate ? $updatedWithdrawals++ : $addedWithdrawals++;
                $this->flush($batchCounter);
            }
            $this->em->flush();
            $withdrawals=null;
            unset($withdrawals);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedWithdrawals." Withdrawals were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedWithdrawals." Withdrawals were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedWithdrawals." Withdrawals were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");


            /////////////////////////////////////////////////
            //import envelopes
            $output->writeln("Importing Envelopes...");
            $envelopes=$financialData['envelopes'];

            $progress = new ProgressBar($output, count($envelopes));
            $progress->start();
            $addedEnvelopes=0;
            $updatedEnvelopes=0;
            $skippedEnvelopes=0;

            foreach ($envelopes as $envelope) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($envelope) || !array_key_exists('id', $envelope)) {
                    continue;
                }

                $envelopeEntity = $this->em->getRepository(Envelope::class)->findOneBy(
                    array(
                        "importId" => $envelope['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );
                if (!$envelopeEntity) {
                    $envelopeEntity = new Envelope();
                } else {
                    $isUpdate = true;
                }

                $createdAt = new \DateTime($envelope['createdAt']['date']);

                unset($userName);
                if ($envelope['owner']) {

                    if(empty($envelope['owner']['wyndId'])){
                        $userName=$envelope['owner']['username'];
                    }else{
                        $userName=$this->restaurantCode."_".$envelope['owner']['username'];
                    }
                    $owner = $this->em->getRepository(Employee::class)->findOneBy(
                        array(
                            "username" => $userName
                        )
                    );
                    if ($owner) {
                        $envelopeEntity->setOwner($owner);
                    } else {
                        $skippedEnvelopes++;
                        $this->logger->info('Envelope Skipped because owner doesn\'t exist : ', array("envelopeId" => $envelope['id'], "username" => $envelope['owner']['username'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }

                unset($userName);
                if ($envelope['cashier']) {

                    if(empty($envelope['cashier']['wyndId'])){
                        $userName=$envelope['cashier']['username'];
                    }else{
                        $userName=$this->restaurantCode."_".$envelope['cashier']['username'];
                    }
                    $cashier = $this->em->getRepository(Employee::class)->findOneBy(
                        array(
                            "username" => $userName
                        )
                    );
                    if ($cashier) {
                        $envelopeEntity->setCashier($cashier);
                    } else {
                        $skippedEnvelopes++;
                        $this->logger->info('Envelope Skipped because cashier doesn\'t exist : ', array("envelopeId" => $envelope['id'], "username" => $envelope['cashier']['username'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }

                $envelopeEntity
                    ->setCreatedAt($createdAt)
                    ->setSynchronized(boolval($envelope['synchronized']))
                    ->setNumEnvelope((int)$envelope['numEnvelope'])
                    ->setReference($envelope['reference'])
                    ->setAmount(floatval($envelope['amount']))
                    ->setSourceId($envelope['sourceId'])
                    ->setSource($envelope['source'])
                    ->setStatus($envelope['status'])
                    ->setSousType($envelope['sousType'])
                    ->setType($envelope['type']);

                $envelopeEntity
                    ->setImportId($envelope['id'] . "_" . $this->restaurantCode)
                    ->setOriginRestaurant($this->restaurant);


                $this->em->persist($envelopeEntity);
                $isUpdate ? $updatedEnvelopes++ : $addedEnvelopes++;
                $this->flush($batchCounter);

            }

            $this->em->flush();
            $envelopes=null;
            unset($envelopes);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedEnvelopes." Envelopes were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedEnvelopes." Envelopes were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedEnvelopes." Envelopes were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            /////////////////////////////////////////////////
            //import expenses
            $output->writeln("Importing Expenses...");
            $expenses=$financialData['expenses'];

            $progress = new ProgressBar($output, count($expenses));
            $progress->start();
            $addedExpenses=0;
            $updatedExpenses=0;
            $skippedExpenses=0;
            foreach ($expenses as $expense) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($expense) || !array_key_exists('id', $expense)) {
                    continue;
                }

                $expenseEntity = $this->em->getRepository(Expense::class)->findOneBy(
                    array(
                        "importId" => $expense['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );
                if (!$expenseEntity) {
                    $expenseEntity = new Expense();
                } else {
                    $isUpdate = true;
                }

                $createdAt = new \DateTime($expense['createdAt']['date']);
                $dateExpense = new \DateTime($expense['dateExpense']['date']);

                unset($userName);
                if ($expense['responsible']) {

                    if(empty($expense['responsible']['wyndId'])){
                        $userName=$expense['responsible']['username'];
                    }else{
                        $userName=$this->restaurantCode."_".$expense['responsible']['username'];
                    }
                    $responsible = $this->em->getRepository(Employee::class)->findOneBy(
                        array(
                            "username" => $userName
                        )
                    );
                    if ($responsible) {
                        $expenseEntity->setResponsible($responsible);
                    } else {
                        $skippedExpenses++;
                        $this->logger->info('Expense Skipped because responsible doesn\'t exist : ', array("expenseId" => $expense['id'], "userName" => $expense['responsible']['username'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }

                $expenseEntity
                    ->setCreatedAt($createdAt)
                    ->setDateExpense($dateExpense)
                    ->setSynchronized(boolval($expense['synchronized']))
                    ->setReference($expense['reference'])
                    ->setAmount( floatval($expense['amount']))
                    ->setGroupExpense($expense['groupExpense'])
                    ->setSousGroup($expense['sousGroup'])
                    ->setComment($expense['comment'])
                    ->setTva(floatval($expense['tva']));
                if($expense['groupExpense'] === Expense::GROUP_OTHERS){
                    $p=$this->em->getRepository(Parameter::class)->findOneByLabel($expense['sousGroup']);
                    if($p){
                        $expenseEntity->setSousGroup($p->getGlobalId());
                    }
                }

                $expenseEntity
                    ->setImportId($expense['id'] . "_" . $this->restaurantCode)
                    ->setOriginRestaurant($this->restaurant);


                $this->em->persist($expenseEntity);
                $isUpdate ? $updatedExpenses++ : $addedExpenses++;
                $this->flush($batchCounter);
            }
            $this->em->flush();
            $expenses=null;
            unset($expenses);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedExpenses." Expenses were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedExpenses." Expenses were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedExpenses." Expenses were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            /////////////////////////////////////////////////
            //import deposits
            $output->writeln("Importing Deposits...");
            $deposits=$financialData['deposits'];

            $progress = new ProgressBar($output, count($deposits));
            $progress->start();
            $addedDeposits=0;
            $updatedDeposits=0;
            $skippedDeposits=0;
            foreach ($deposits as $deposit) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($deposit) || !array_key_exists('id', $deposit)) {
                    continue;
                }

                $createdAt = new \DateTime($deposit['createdAt']['date']);

                $depositEntity = $this->em->getRepository(Deposit::class)->findOneBy(
                    array(
                        "importId" => $deposit['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );
                if (!$depositEntity) {
                    $depositEntity = new Deposit();
                } else {
                    $isUpdate = true;
                }

                unset($userName);
                if ($deposit['owner']) {

                    if(empty($deposit['owner']['wyndId'])){
                        $userName=$deposit['owner']['username'];
                    }else{
                        $userName=$this->restaurantCode."_".$deposit['owner']['username'];
                    }
                    $owner = $this->em->getRepository(Employee::class)->findOneBy(
                        array(
                            "username" => $userName
                        )
                    );
                    if ($owner) {
                        $depositEntity->setOwner($owner);
                    } else {
                        $skippedDeposits++;
                        $this->logger->info('Deposit Skipped because owner doesn\'t exist : ', array("depositId" => $deposit['id'], "userName" => $deposit['owner']['username'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }

                $depositEntity
                    ->setCreatedAt($createdAt)
                    ->setReference($deposit['reference'])
                    ->setSource($deposit['source'])
                    ->setDestination($deposit['destination'])
                    ->setAffiliateCode($deposit['affiliateCode'])
                    ->setType($deposit['type'])
                    ->setSousType($deposit['sousType'])
                    ->setTotalAmount($deposit['totalAmount'] ? floatval($deposit['totalAmount']) : null)
                    ->setSynchronized(boolval($deposit['synchronized']));

                foreach ($deposit['envelopeIds'] as $envelopeId) {
                    $envelope = $this->em->getRepository(Envelope::class)->findOneByImportId($envelopeId . "_" . $this->restaurantCode);
                    if ($envelope) {
                        $depositEntity->addEnvelope($envelope);
                        $envelope->setDeposit($depositEntity);
                        $this->em->persist($envelope);
                    }
                }

                if ($deposit['expenseId']) {
                    $expense = $this->em->getRepository(Expense::class)->findOneByImportId($deposit['expenseId'] . "_" . $this->restaurantCode);
                    if ($expense) {
                        $depositEntity->setExpense($expense);
                        $expense->setDeposit($depositEntity);
                        $this->em->persist($expense);
                    }
                }

                $depositEntity
                    ->setImportId($deposit['id'] . "_" . $this->restaurantCode)
                    ->setOriginRestaurant($this->restaurant);

                $this->em->persist($depositEntity);
                $isUpdate ? $updatedDeposits++ : $addedDeposits++;
                $this->flush($batchCounter);


            }
            $this->em->flush();
            $deposits=null;
            unset($deposits);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedDeposits." Deposits were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedDeposits." Deposits were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedDeposits." Deposits were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            /////////////////////////////////////////////////
            //import Cashbox Bank Card Containers
            $output->writeln("Importing Cashbox Bank Card Containers...");
            $cashboxBankCardContainers=$financialData['cashboxBankCardContainers'];

            $progress = new ProgressBar($output, count($cashboxBankCardContainers));
            $progress->start();
            $addedCashboxBankCardContainers=0;
            $updatedCashboxBankCardContainers=0;
            $skippedCashboxBankCardContainers=0;
            foreach ($cashboxBankCardContainers as $cashboxBankCardContainer) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($cashboxBankCardContainer) || !array_key_exists('id', $cashboxBankCardContainer)) {
                    continue;
                }

                $cashboxBankCardContainerEntity = $this->em->getRepository(CashboxBankCardContainer::class)->findOneBy(
                    array(
                        "importId" => $cashboxBankCardContainer['id'] . "_" . $this->restaurantCode
                    )
                );
                if (!$cashboxBankCardContainerEntity) {
                    $cashboxBankCardContainerEntity = new CashboxBankCardContainer();
                } else {
                    $isUpdate = true;
                }
                //delete old bankCardCount in case of update
                if($isUpdate){
                    foreach ($cashboxBankCardContainerEntity->getBankCardCounts() as $bankCardCount){
                        $this->em->remove($bankCardCount);
                    }
                    $this->em->flush();
                }

                foreach ($cashboxBankCardContainer['bankCardCounts'] as $bankCardCount) {
                    $bankCardCountEntity = new CashboxBankCard();
                    $bankCardCountEntity
                        ->setAmount($bankCardCount['amount'])
                        ->setCardName($bankCardCount['cardName'])
                        ->setIdPayment($bankCardCount['idPayment']);
                    $cashboxBankCardContainerEntity->addBankCardCount($bankCardCountEntity);
                }

                foreach ($cashboxBankCardContainer['ticketPaymentsIds'] as $ticketPaymentsId) {
                    $ticketPayment = $this->em->getRepository(TicketPayment::class)->findOneBy(
                        array(
                            "importId" => $ticketPaymentsId . "_" . $this->restaurantCode
                        )
                    );
                    if ($ticketPayment) {
                        if ($ticketPayment->getTicket() && !$cashboxBankCardContainerEntity->getTicketPayments()->contains($ticketPayment) ) {
                            $cashboxBankCardContainerEntity->addTicketPayment($ticketPayment);
                            $ticketPayment->setBankCardContainer($cashboxBankCardContainerEntity);
                            $this->em->persist($ticketPayment);
                        }
                    } else {
                        $this->logger->info('Cashbox Bank Card Containers Ticket Payment not found : ', array("cashboxBankCardContainerId" => $cashboxBankCardContainer['id'], "ticketPaymentsId" => $ticketPaymentsId, "Restaurant" => $this->restaurant->getName()));
                    }
                }

                $cashboxBankCardContainerEntity
                    ->setImportId($cashboxBankCardContainer['id'] . "_" . $this->restaurantCode);

                $this->em->persist($cashboxBankCardContainerEntity);
                $isUpdate ? $updatedCashboxBankCardContainers++ : $addedCashboxBankCardContainers++;
                $this->flush($batchCounter);

            }
            $this->em->flush();
            $cashboxBankCardContainers=null;
            unset($cashboxBankCardContainers);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedCashboxBankCardContainers." Cashbox Bank Card Containers were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedCashboxBankCardContainers." Cashbox Bank Card Containers were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedCashboxBankCardContainers." Cashbox Bank Card Containers were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");


            /////////////////////////////////////////////////
            //import Cashbox CheckQuick Containers
            $output->writeln("Importing Cashbox CheckQuick Containers...");
            $cashboxCheckQuickContainers=$financialData['cashboxCheckQuickContainers'];

            $progress = new ProgressBar($output, count($cashboxCheckQuickContainers));
            $progress->start();
            $addedCashboxCheckQuickContainers=0;
            $updatedCashboxCheckQuickContainers=0;
            $skippedCashboxCheckQuickContainers=0;
            foreach ($cashboxCheckQuickContainers as $cashboxCheckQuickContainer) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($cashboxCheckQuickContainer) || !array_key_exists('id', $cashboxCheckQuickContainer)) {
                    continue;
                }

                $cashboxCheckQuickContainerEntity = $this->em->getRepository(CashboxCheckQuickContainer::class)->findOneBy(
                    array(
                        "importId" => $cashboxCheckQuickContainer['id'] . "_" . $this->restaurantCode
                    )
                );
                if (!$cashboxCheckQuickContainerEntity) {
                    $cashboxCheckQuickContainerEntity = new CashboxCheckQuickContainer();
                } else {
                    $isUpdate = true;
                }

                //delete old checkQuickCount in case of update
                if($isUpdate){
                    foreach ($cashboxCheckQuickContainerEntity->getCheckQuickCounts() as $checkQuickCount){
                        $this->em->remove($checkQuickCount);
                    }
                    $this->em->flush();
                }

                foreach ($cashboxCheckQuickContainer['checkQuickCounts'] as $checkQuickCount) {
                    $checkQuickCountEntity = new CashboxCheckQuick();
                    $checkQuickCountEntity
                        ->setQty($checkQuickCount['qty'])
                        ->setUnitValue($checkQuickCount['unitValue']);
                    $cashboxCheckQuickContainerEntity->addCheckQuickCount($checkQuickCountEntity);
                }

                foreach ($cashboxCheckQuickContainer['ticketPaymentsIds'] as $ticketPaymentsId) {
                    $ticketPayment = $this->em->getRepository(TicketPayment::class)->findOneBy(
                        array(
                            "importId" => $ticketPaymentsId . "_" . $this->restaurantCode
                        )
                    );
                    if ($ticketPayment) {
                        if ($ticketPayment->getTicket() && !$cashboxCheckQuickContainerEntity->getTicketPayments()->contains($ticketPayment)) {
                            $cashboxCheckQuickContainerEntity->addTicketPayment($ticketPayment);
                            $ticketPayment->setCheckQuickContainer($cashboxCheckQuickContainerEntity);
                            $this->em->persist($ticketPayment);
                        }
                    } else {
                        $this->logger->info('Cashbox CheckQuick Containers Ticket Payment not found : ', array("cashboxCheckQuickContainerId" => $cashboxCheckQuickContainer['id'], "ticketPaymentsId" => $ticketPaymentsId, "Restaurant" => $this->restaurant->getName()));
                    }
                }

                $cashboxCheckQuickContainerEntity
                    ->setImportId($cashboxCheckQuickContainer['id'] . "_" . $this->restaurantCode);

                $this->em->persist($cashboxCheckQuickContainerEntity);
                $isUpdate ? $updatedCashboxCheckQuickContainers++ : $addedCashboxCheckQuickContainers++;
                $this->flush($batchCounter);

            }
            $this->em->flush();
            $cashboxCheckQuickContainers=null;
            unset($cashboxCheckQuickContainers);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedCashboxCheckQuickContainers." Cashbox CheckQuick Containers were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedCashboxCheckQuickContainers." Cashbox CheckQuick Containers were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedCashboxCheckQuickContainers." Cashbox CheckQuick Containers were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");


            /////////////////////////////////////////////////
            //import Cashbox Check Restaurant Containers
            $output->writeln("Importing Cashbox Check Restaurant Containers...");
            $cashboxCheckRestaurantContainers=$financialData['cashboxCheckRestaurantContainers'];

            $progress = new ProgressBar($output, count($cashboxCheckRestaurantContainers));
            $progress->start();
            $addedCashboxCheckRestaurantContainers=0;
            $updatedCashboxCheckRestaurantContainers=0;
            $skippedCashboxCheckRestaurantContainers=0;
            foreach ($cashboxCheckRestaurantContainers as $cashboxCheckRestaurantContainer) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($cashboxCheckRestaurantContainer) || !array_key_exists('id', $cashboxCheckRestaurantContainer)) {
                    continue;
                }

                $cashboxCheckRestaurantContainerEntity = $this->em->getRepository(CashboxCheckRestaurantContainer::class)->findOneBy(
                    array(
                        "importId" => $cashboxCheckRestaurantContainer['id'] . "_" . $this->restaurantCode
                    )
                );
                if (!$cashboxCheckRestaurantContainerEntity) {
                    $cashboxCheckRestaurantContainerEntity = new CashboxCheckRestaurantContainer();
                } else {
                    $isUpdate = true;
                }

                //delete old ticketRestaurantCount in case of update
                if($isUpdate){
                    foreach ($cashboxCheckRestaurantContainerEntity->getTicketRestaurantCounts() as $ticketRestaurantCount){
                        $this->em->remove($ticketRestaurantCount);
                    }
                    $this->em->flush();
                }

                foreach ($cashboxCheckRestaurantContainer['ticketRestaurantCounts'] as $ticketRestaurantCount) {
                    $ticketRestaurantCountEntity = new CashboxTicketRestaurant();
                    $ticketRestaurantCountEntity
                        ->setQty($ticketRestaurantCount['qty'])
                        ->setUnitValue( floatval($ticketRestaurantCount['unitValue']))
                        ->setTicketName($ticketRestaurantCount['ticketName'])
                        ->setIdPayment($ticketRestaurantCount['idPayment'])
                        ->setElectronic(boolval($ticketRestaurantCount['electronic']));

                    $cashboxCheckRestaurantContainerEntity->addTicketRestaurantCount($ticketRestaurantCountEntity);
                }

                foreach ($cashboxCheckRestaurantContainer['ticketPaymentsIds'] as $ticketPaymentsId) {
                    $ticketPayment = $this->em->getRepository(TicketPayment::class)->findOneBy(
                        array(
                            "importId" => $ticketPaymentsId . "_" . $this->restaurantCode
                        )
                    );
                    if ($ticketPayment) {
                        if ($ticketPayment->getTicket() && !$cashboxCheckRestaurantContainerEntity->getTicketPayments()->contains($ticketPayment)) {
                            $cashboxCheckRestaurantContainerEntity->addTicketPayment($ticketPayment);
                            $ticketPayment->setCheckRestaurantContainer($cashboxCheckRestaurantContainerEntity);
                            $this->em->persist($ticketPayment);
                        }
                    } else {
                        $this->logger->info('Cashbox Check Restaurant Container Ticket Payment not found : ', array("cashboxCheckRestaurantContainerId" => $cashboxCheckRestaurantContainer['id'], "ticketPaymentsId" => $ticketPaymentsId, "Restaurant" => $this->restaurant->getName()));
                    }
                }

                $cashboxCheckRestaurantContainerEntity
                    ->setImportId($cashboxCheckRestaurantContainer['id'] . "_" . $this->restaurantCode);

                $this->em->persist($cashboxCheckRestaurantContainerEntity);
                $isUpdate ? $updatedCashboxCheckRestaurantContainers++ : $addedCashboxCheckRestaurantContainers++;
                $this->flush($batchCounter);

            }
            $this->em->flush();
            $cashboxCheckRestaurantContainers=null;
            unset($cashboxCheckRestaurantContainers);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedCashboxCheckRestaurantContainers." Cashbox Check Restaurant Containers were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedCashboxCheckRestaurantContainers." Cashbox Check Restaurant Containers were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedCashboxCheckRestaurantContainers." Cashbox Check Restaurant Containers were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");


            /////////////////////////////////////////////////
            //import Cashbox Check Restaurant Containers
            $output->writeln("Importing Cashbox Foreign Currency Containers...");
            $cashboxForeignCurrencyContainers=$financialData['cashboxForeignCurrencyContainers'];

            $progress = new ProgressBar($output, count($cashboxForeignCurrencyContainers));
            $progress->start();
            $addedCashboxForeignCurrencyContainers=0;
            $updatedCashboxForeignCurrencyContainers=0;
            $skippedCashboxForeignCurrencyContainers=0;
            foreach ($cashboxForeignCurrencyContainers as $cashboxForeignCurrencyContainer) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($cashboxForeignCurrencyContainer) || !array_key_exists('id', $cashboxForeignCurrencyContainer)) {
                    continue;
                }

                $cashboxForeignCurrencyContainerEntity = $this->em->getRepository(CashboxForeignCurrencyContainer::class)->findOneBy(
                    array(
                        "importId" => $cashboxForeignCurrencyContainer['id'] . "_" . $this->restaurantCode
                    )
                );
                if (!$cashboxForeignCurrencyContainerEntity) {
                    $cashboxForeignCurrencyContainerEntity = new CashboxForeignCurrencyContainer();
                } else {
                    $isUpdate = true;
                }

                //delete old foreignCurrencyCount in case of update
                if($isUpdate){
                    foreach ($cashboxForeignCurrencyContainerEntity->getForeignCurrencyCounts() as $foreignCurrencyCount){
                        $this->em->remove($foreignCurrencyCount);
                    }
                    $this->em->flush();
                }

                foreach ($cashboxForeignCurrencyContainer['foreignCurrencyCounts'] as $foreignCurrencyCount) {
                    $foreignCurrencyCountEntity = new CashboxForeignCurrency();
                    $foreignCurrencyCountEntity
                        ->setAmount(floatval($foreignCurrencyCount['amount']))
                        ->setForeignCurrencyLabel($foreignCurrencyCount['foreignCurrencyLabel'])
                        ->setExchangeRate(floatval($foreignCurrencyCount['exchangeRate']));

                    $cashboxForeignCurrencyContainerEntity->addForeignCurrencyCount($foreignCurrencyCountEntity);
                }

                foreach ($cashboxForeignCurrencyContainer['ticketPaymentsIds'] as $ticketPaymentsId) {
                    $ticketPayment = $this->em->getRepository(TicketPayment::class)->findOneBy(
                        array(
                            "importId" => $ticketPaymentsId . "_" . $this->restaurantCode
                        )
                    );
                    if ($ticketPayment) {
                        if ($ticketPayment->getTicket() && !$cashboxForeignCurrencyContainerEntity->getTicketPayments()->contains($ticketPayment)) {
                            $cashboxForeignCurrencyContainerEntity->addTicketPayment($ticketPayment);
                            $ticketPayment->setForeignCurrencyContainer($cashboxForeignCurrencyContainerEntity);
                            $this->em->persist($ticketPayment);
                        }
                    } else {
                        $this->logger->info('Cashbox Foreign Currency Container Ticket Payment not found : ', array("cashboxForeignCurrencyContainerId" => $cashboxForeignCurrencyContainer['id'], "ticketPaymentsId" => $ticketPaymentsId, "Restaurant" => $this->restaurant->getName()));
                    }
                }

                $cashboxForeignCurrencyContainerEntity
                    ->setImportId($cashboxForeignCurrencyContainer['id'] . "_" . $this->restaurantCode);

                $this->em->persist($cashboxForeignCurrencyContainerEntity);
                $isUpdate ? $updatedCashboxForeignCurrencyContainers++ : $addedCashboxForeignCurrencyContainers++;
                $this->flush($batchCounter);

            }
            $this->em->flush();
            $cashboxForeignCurrencyContainers=null;
            unset($cashboxForeignCurrencyContainers);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedCashboxForeignCurrencyContainers." Cashbox Foreign Currency Containers were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedCashboxForeignCurrencyContainers." Cashbox Foreign Currency Containers were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedCashboxForeignCurrencyContainers." Cashbox Foreign Currency Containers were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");


            /////////////////////////////////////////////////
            //import Cashbox Meal Ticket Containers
            $output->writeln("Importing Cashbox Meal Ticket Containers...");
            $cashboxMealTicketContainers=$financialData['cashboxMealTicketContainers'];

            $progress = new ProgressBar($output, count($cashboxMealTicketContainers));
            $progress->start();
            $addedCashboxMealTicketContainers=0;
            $updatedCashboxMealTicketContainers=0;
            $skippedCashboxMealTicketContainers=0;
            foreach ($cashboxMealTicketContainers as $cashboxMealTicketContainer) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($cashboxMealTicketContainer) || !array_key_exists('id', $cashboxMealTicketContainer)) {
                    continue;
                }

                $cashboxMealTicketContainerEntity = $this->em->getRepository(CashboxMealTicketContainer::class)->findOneBy(
                    array(
                        "importId" => $cashboxMealTicketContainer['id'] . "_" . $this->restaurantCode
                    )
                );
                if (!$cashboxMealTicketContainerEntity) {
                    $cashboxMealTicketContainerEntity = new CashboxMealTicketContainer();
                } else {
                    $isUpdate = true;
                }

                foreach ($cashboxMealTicketContainer['ticketPaymentsIds'] as $ticketPaymentsId) {
                    $ticketPayment = $this->em->getRepository(TicketPayment::class)->findOneBy(
                        array(
                            "importId" => $ticketPaymentsId . "_" . $this->restaurantCode
                        )
                    );
                    if ($ticketPayment) {
                        if ($ticketPayment->getTicket() && !$cashboxMealTicketContainerEntity->getTicketPayments()->contains($ticketPayment)) {
                            $cashboxMealTicketContainerEntity->addTicketPayment($ticketPayment);
                            $ticketPayment->setMealTicketContainer($cashboxMealTicketContainerEntity);
                            $this->em->persist($ticketPayment);
                        }
                    } else {
                        $this->logger->info('Cashbox Meal Ticket Container Ticket Payment not found : ', array("cashboxMealTicketContainerId" => $cashboxMealTicketContainer['id'], "ticketPaymentsId" => $ticketPaymentsId, "Restaurant" => $this->restaurant->getName()));
                    }
                }

                $cashboxMealTicketContainerEntity
                    ->setImportId($cashboxMealTicketContainer['id'] . "_" . $this->restaurantCode);

                $this->em->persist($cashboxMealTicketContainerEntity);
                $isUpdate ? $updatedCashboxMealTicketContainers++ :  $addedCashboxMealTicketContainers++;
                $this->flush($batchCounter);

            }
            $this->em->flush();
            $cashboxMealTicketContainers=null;
            unset($cashboxMealTicketContainers);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedCashboxMealTicketContainers." Cashbox Meal Ticket Containers were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedCashboxMealTicketContainers." Cashbox Meal Ticket Containers were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedCashboxMealTicketContainers." Cashbox Meal Ticket Containers were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");


            /////////////////////////////////////////////////
            //import Cashbox RealCash Containers
            $output->writeln("Importing Cashbox RealCash Containers...");
            $cashboxRealCashContainers=$financialData['cashboxRealCashContainers'];

            $progress = new ProgressBar($output, count($cashboxRealCashContainers));
            $progress->start();
            $addedCashboxRealCashContainers=0;
            $updatedCashboxRealCashContainers=0;
            $skippedCashboxRealCashContainers=0;
            foreach ($cashboxRealCashContainers as $cashboxRealCashContainer) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($cashboxRealCashContainer) || !array_key_exists('id', $cashboxRealCashContainer)) {
                    continue;
                }

                $cashboxRealCashContainersEntity = $this->em->getRepository(CashboxRealCashContainer::class)->findOneBy(
                    array(
                        "importId" => $cashboxRealCashContainer['id'] . "_" . $this->restaurantCode
                    )
                );
                if (!$cashboxRealCashContainersEntity) {
                    $cashboxRealCashContainersEntity = new CashboxRealCashContainer();
                } else {
                    $isUpdate = true;
                }

                $cashboxRealCashContainersEntity
                    ->setTotalAmount(floatval($cashboxRealCashContainer['totalAmount']))
                    ->setAllAmount(boolval($cashboxRealCashContainer['allAmount']))
                    ->setBillOf5($cashboxRealCashContainer['billOf5'])
                    ->setBillOf10($cashboxRealCashContainer['billOf10'])
                    ->setBillOf20($cashboxRealCashContainer['billOf20'])
                    ->setBillOf50($cashboxRealCashContainer['billOf50'])
                    ->setBillOf100($cashboxRealCashContainer['billOf100'])
                    ->setChange(floatval($cashboxRealCashContainer['change']));


                foreach ($cashboxRealCashContainer['ticketPaymentsIds'] as $ticketPaymentsId) {
                    $ticketPayment = $this->em->getRepository(TicketPayment::class)->findOneBy(
                        array(
                            "importId" => $ticketPaymentsId . "_" . $this->restaurantCode
                        )
                    );
                    if ($ticketPayment) {
                        if ($ticketPayment->getTicket() && !$cashboxRealCashContainersEntity->getTicketPayments()->contains($ticketPayment)) {
                            $cashboxRealCashContainersEntity->addTicketPayment($ticketPayment);
                            $ticketPayment->setRealCashContainer($cashboxRealCashContainersEntity);
                            $this->em->persist($ticketPayment);
                        }
                    } else {
                        $this->logger->info('Cashbox Real Cash Container Ticket Payment not found : ', array("cashboxRealCashContainersId" => $cashboxRealCashContainer['id'], "ticketPaymentsId" => $ticketPaymentsId, "Restaurant" => $this->restaurant->getName()));
                    }
                }

                $cashboxRealCashContainersEntity
                    ->setImportId($cashboxRealCashContainer['id'] . "_" . $this->restaurantCode);

                $this->em->persist($cashboxRealCashContainersEntity);
                $isUpdate ? $updatedCashboxRealCashContainers++ :  $addedCashboxRealCashContainers++;
                $this->flush($batchCounter);

            }
            $this->em->flush();
            $cashboxRealCashContainers=null;
            unset($cashboxRealCashContainers);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedCashboxRealCashContainers." Cashbox Real Cash Containers were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedCashboxRealCashContainers." Cashbox Real Cash Containers were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedCashboxRealCashContainers." Cashbox Real Cash Containers were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");


            /////////////////////////////////////////////////
            //import Cashbox Discount Containers
            $output->writeln("Importing Cashbox Cashbox Discount Containers...");
            $cashboxDiscountContainers=$financialData['cashboxDiscountContainers'];

            $progress = new ProgressBar($output, count($cashboxDiscountContainers));
            $progress->start();
            $addedCashboxDiscountContainers=0;
            $updatedCashboxDiscountContainers=0;
            $skippedCashboxDiscountContainers=0;
            foreach ($cashboxDiscountContainers as $cashboxDiscountContainer) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($cashboxDiscountContainer) || !array_key_exists('id', $cashboxDiscountContainer)) {
                    continue;
                }

                $cashboxDiscountContainerEntity = $this->em->getRepository(CashboxDiscountContainer::class)->findOneBy(
                    array(
                        "importId" => $cashboxDiscountContainer['id'] . "_" . $this->restaurantCode
                    )
                );
                if (!$cashboxDiscountContainerEntity) {
                    $cashboxDiscountContainerEntity = new CashboxDiscountContainer();
                } else {
                    $isUpdate = true;
                }

                foreach ($cashboxDiscountContainer['ticketLinesIds'] as $id) {
                    $ticketLine = $this->em->getRepository(TicketLine::class)->findOneBy(
                        array(
                            "importId" => $id . "_" . $this->restaurantCode
                        )
                    );
                    if ($ticketLine) {
                        if ($ticketLine->getTicket() && !$cashboxDiscountContainerEntity->getTicketLines()->contains($ticketPayment)) {
                            $cashboxDiscountContainerEntity->addTicketLine($ticketLine);
                            $ticketLine->setDiscountContainer($cashboxDiscountContainerEntity);
                            $this->em->persist($ticketLine);
                        }
                    } else {
                        $this->logger->info('Cashbox Discount Container Ticket Line not found : ', array("cashboxDiscountContainerId" => $cashboxDiscountContainer['id'], "ticketLinesId" => $id, "Restaurant" => $this->restaurant->getName()));
                    }
                }

                $cashboxDiscountContainerEntity
                    ->setImportId($cashboxDiscountContainer['id'] . "_" . $this->restaurantCode);

                $this->em->persist($cashboxDiscountContainerEntity);
                $isUpdate ? $updatedCashboxDiscountContainers++ :  $addedCashboxDiscountContainers++;
                $this->flush($batchCounter);

            }
            $this->em->flush();
            $cashboxDiscountContainers=null;
            unset($cashboxDiscountContainers);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedCashboxDiscountContainers." Cashbox Discount Containers were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedCashboxDiscountContainers." Cashbox Discount Containers were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedCashboxDiscountContainers." Cashbox Discount Containers were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");


            /////////////////////////////////////////////////
            //import CashboxCount
            $output->writeln("Importing Cashbox Counts...");
            $cashboxCounts=$financialData['cashboxCounts'];

            $progress = new ProgressBar($output, count($cashboxCounts));
            $progress->start();
            $addedCashboxCounts=0;
            $updatedCashboxCounts=0;
            $skippedCashboxCounts=0;
            foreach ($cashboxCounts as $cashboxCount) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($cashboxCount) || !array_key_exists('id', $cashboxCount)) {
                    continue;
                }

                $cashboxCountEntity = $this->em->getRepository(CashboxCount::class)->findOneBy(
                    array(
                        "importId" => $cashboxCount['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );
                if (!$cashboxCountEntity) {
                    $cashboxCountEntity = new CashboxCount();
                } else {
                    $isUpdate = true;
                }

                $createdAt = new \DateTime($cashboxCount['createdAt']['date']);
                $date = new \DateTime($cashboxCount['date']['date']);

                unset($userName);
                if ($cashboxCount['owner']) {

                    if(empty($cashboxCount['owner']['wyndId'])){
                        $userName=$cashboxCount['owner']['username'];
                    }else{
                        $userName=$this->restaurantCode."_".$cashboxCount['owner']['username'];
                    }
                    $owner = $this->em->getRepository(Employee::class)->findOneBy(
                        array(
                            "username" => $userName
                        )
                    );
                    if ($owner) {
                        $cashboxCountEntity->setOwner($owner);
                    } else {
                        $skippedCashboxCounts++;
                        $this->logger->info('Cashbox Count Skipped because owner doesn\'t exist : ', array("cashboxCountId" => $cashboxCount['id'], "userName" => $cashboxCount['owner']['username'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }

                unset($userName);
                if ($cashboxCount['cashier']) {

                    if(empty($cashboxCount['cashier']['wyndId'])){
                        $userName=$cashboxCount['cashier']['username'];
                    }else{
                        $userName=$this->restaurantCode."_".$cashboxCount['cashier']['username'];
                    }
                    $cashier = $this->em->getRepository(Employee::class)->findOneBy(
                        array(
                            "username" => $userName
                        )
                    );

                    if ($cashier) {
                        $cashboxCountEntity->setCashier($cashier);
                    } else {
                        $skippedCashboxCounts++;
                        $this->logger->info('Cashbox Count Skipped because cashier doesn\'t exist : ', array("cashboxCountId" => $cashboxCount['id'], "userName" => $cashboxCount['cashier']['username'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }

                $cashboxCountEntity
                    ->setCreatedAt($createdAt)
                    ->setDate($date)
                    ->setSynchronized(boolval($cashboxCount['synchronized']))
                    ->setRealCaCounted($cashboxCount['realCaCounted'] ? floatval($cashboxCount['realCaCounted']) : null)
                    ->setTheoricalCa($cashboxCount['theoricalCa'] ? floatval($cashboxCount['theoricalCa']) : null)
                    ->setNumberCancels($cashboxCount['numberCancels'])
                    ->setTotalCancels($cashboxCount['totalCancels'])
                    ->setNumberCorrections($cashboxCount['numberCorrections'])
                    ->setTotalCorrections($cashboxCount['totalCorrections'])
                    ->setNumberAbondons($cashboxCount['numberAbondons'])
                    ->setTotalAbondons($cashboxCount['totalAbondons'])
                    ->setEft(boolval($cashboxCount['eft']))
                    ->setCounted(boolval($cashboxCount['counted']));

                if ($cashboxCount['cashContainerId']) {
                    $cashboxRealCashContainer = $this->em->getRepository(CashboxRealCashContainer::class)->findOneBy(
                        array(
                            "importId" => $cashboxCount['cashContainerId'] . "_" . $this->restaurantCode,
                        )
                    );
                    if ($cashboxRealCashContainer) {
                        $cashboxCountEntity->setCashContainer($this->em->getReference(CashboxRealCashContainer::class, $cashboxRealCashContainer->getId()));
                        $cashboxRealCashContainer->setCashbox($cashboxCountEntity);
                        $this->em->persist($cashboxRealCashContainer);
                    } else {
                        $skippedCashboxCounts++;
                        $this->logger->info('Cashbox Count Skipped because CashboxRealCashContainer doesn\'t exist : ', array("cashboxCountId" => $cashboxCount['id'], "cashContainerId" => $cashboxCount['cashContainerId'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }
                if ($cashboxCount['checkRestaurantContainerId']) {
                    $checkRestaurantContainer = $this->em->getRepository(CashboxCheckRestaurantContainer::class)->findOneBy(
                        array(
                            "importId" => $cashboxCount['checkRestaurantContainerId'] . "_" . $this->restaurantCode,
                        )
                    );
                    if ($checkRestaurantContainer) {
                        $cashboxCountEntity->setCheckRestaurantContainer($this->em->getReference(CashboxCheckRestaurantContainer::class, $checkRestaurantContainer->getId()));
                        $checkRestaurantContainer->setCashbox($cashboxCountEntity);
                        $this->em->persist($checkRestaurantContainer);
                    } else {
                        $skippedCashboxCounts++;
                        $this->logger->info('Cashbox Count Skipped because CheckRestaurantContainer doesn\'t exist : ', array("cashboxCountId" => $cashboxCount['id'], "checkRestaurantContainerId" => $cashboxCount['checkRestaurantContainerId'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }
                if ($cashboxCount['bankCardContainerId']) {
                    $bankCardContainer = $this->em->getRepository(CashboxBankCardContainer::class)->findOneBy(
                        array(
                            "importId" => $cashboxCount['bankCardContainerId'] . "_" . $this->restaurantCode,
                        )
                    );
                    if ($bankCardContainer) {
                        $cashboxCountEntity->setBankCardContainer($this->em->getReference(CashboxBankCardContainer::class, $bankCardContainer->getId()));
                        $bankCardContainer->setCashbox($cashboxCountEntity);
                        $this->em->persist($bankCardContainer);
                    } else {
                        $skippedCashboxCounts++;
                        $this->logger->info('Cashbox Count Skipped because BankCardContainer doesn\'t exist : ', array("cashboxCountId" => $cashboxCount['id'], "bankCardContainerId" => $cashboxCount['bankCardContainerId'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }
                if ($cashboxCount['checkQuickContainerId']) {
                    $checkQuickContainer = $this->em->getRepository(CashboxCheckQuickContainer::class)->findOneBy(
                        array(
                            "importId" => $cashboxCount['checkQuickContainerId'] . "_" . $this->restaurantCode,
                        )
                    );
                    if ($checkQuickContainer) {
                        $cashboxCountEntity->setCheckQuickContainer($this->em->getReference(CashboxCheckQuickContainer::class, $checkQuickContainer->getId()));
                        $checkQuickContainer->setCashbox($cashboxCountEntity);
                        $this->em->persist($checkQuickContainer);
                    } else {
                        $skippedCashboxCounts++;
                        $this->logger->info('Cashbox Count Skipped because CheckQuickContainer doesn\'t exist : ', array("cashboxCountId" => $cashboxCount['id'], "checkQuickContainerId" => $cashboxCount['checkQuickContainerId'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }
                if ($cashboxCount['mealTicketContainerId']) {
                    $mealTicketContainer = $this->em->getRepository(CashboxMealTicketContainer::class)->findOneBy(
                        array(
                            "importId" => $cashboxCount['mealTicketContainerId'] . "_" . $this->restaurantCode,
                        )
                    );
                    if ($mealTicketContainer) {
                        $cashboxCountEntity->setMealTicketContainer($this->em->getReference(CashboxMealTicketContainer::class, $mealTicketContainer->getId()));
                        $mealTicketContainer->setCashbox($cashboxCountEntity);
                        $this->em->persist($mealTicketContainer);
                    } else {
                        $skippedCashboxCounts++;
                        $this->logger->info('Cashbox Count Skipped because MealTicketContainer doesn\'t exist : ', array("cashboxCountId" => $cashboxCount['id'], "mealTicketContainerId" => $cashboxCount['mealTicketContainerId'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }
                if ($cashboxCount['discountContainerId']) {
                    $discountContainer = $this->em->getRepository(CashboxDiscountContainer::class)->findOneBy(
                        array(
                            "importId" => $cashboxCount['discountContainerId'] . "_" . $this->restaurantCode,
                        )
                    );
                    if ($discountContainer) {
                        $cashboxCountEntity->setDiscountContainer($this->em->getReference(CashboxDiscountContainer::class, $discountContainer->getId()));
                        $discountContainer->setCashbox($cashboxCountEntity);
                        $this->em->persist($discountContainer);
                    } else {
                        $skippedCashboxCounts++;
                        $this->logger->info('Cashbox Count Skipped because DiscountContainer doesn\'t exist : ', array("cashboxCountId" => $cashboxCount['id'], "discountContainerId" => $cashboxCount['discountContainerId'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }
                if ($cashboxCount['foreignCurrencyContainerId']) {
                    $foreignCurrencyContainer = $this->em->getRepository(CashboxForeignCurrencyContainer::class)->findOneBy(
                        array(
                            "importId" => $cashboxCount['foreignCurrencyContainerId'] . "_" . $this->restaurantCode,
                        )
                    );
                    if ($foreignCurrencyContainer) {
                        $cashboxCountEntity->setForeignCurrencyContainer($this->em->getReference(CashboxForeignCurrencyContainer::class, $foreignCurrencyContainer->getId()));
                        $foreignCurrencyContainer->setCashbox($cashboxCountEntity);
                        $this->em->persist($foreignCurrencyContainer);
                    } else {
                        $skippedCashboxCounts++;
                        $this->logger->info('Cashbox Count Skipped because ForeignCurrencyContainer doesn\'t exist : ', array("cashboxCountId" => $cashboxCount['id'], "foreignCurrencyContainerId" => $cashboxCount['foreignCurrencyContainerId'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }
                foreach ($cashboxCount['withdrawalsIds'] as $id) {
                    $withdrawal = $this->em->getRepository(Withdrawal::class)->findOneBy(
                        array(
                            "importId" => $id . "_" . $this->restaurantCode,
                            "originRestaurant" => $this->restaurant
                        )
                    );
                    if ($withdrawal) {
                        if(!$cashboxCountEntity->getWithdrawals()->contains($withdrawal)){
                            $cashboxCountEntity->addWithdrawal($withdrawal);
                            $withdrawal->setCashboxCount($cashboxCountEntity);
                            $this->em->persist($withdrawal);
                        }
                    }
                }
                foreach ($cashboxCount['abondonedTicketsId'] as $id) {
                    $abondonedTicket = $this->em->getRepository(Ticket::class)->findOneBy(
                        array(
                            "importId" => $id . "_" . $this->restaurantCode,
                            "originRestaurant" => $this->restaurant
                        )
                    );
                    if ($abondonedTicket) {
                        if(!$cashboxCountEntity->getAbondonedTickets()->contains($abondonedTicket)){
                            $cashboxCountEntity->addAbondonedTicket($abondonedTicket);
                            $abondonedTicket->setCashboxCount($cashboxCountEntity);
                            $this->em->persist($abondonedTicket);
                        }
                    }
                }

                $cashboxCountEntity
                    ->setImportId($cashboxCount['id'] . "_" . $this->restaurantCode)
                    ->setOriginRestaurant($this->restaurant);

                $this->em->persist($cashboxCountEntity);
                $isUpdate ? $updatedCashboxCounts++ :  $addedCashboxCounts++;
                $this->flush($batchCounter);
            }
            $this->em->flush();
            $cashboxCounts=null;
            unset($cashboxCounts);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedCashboxCounts." Cashbox Counts were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedCashboxCounts." Cashbox Counts were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedCashboxCounts." Cashbox Counts were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            /////////////////////////////////////////////////
            //import Chest Small Chests
            $output->writeln("Importing Chest Small Chests...");
            $chestSmallChests=$financialData['chestSmallChests'];

            $progress = new ProgressBar($output, count($chestSmallChests));
            $progress->start();
            $addedChestSmallChests=0;
            $updatedChestSmallChests=0;
            $skippedChestSmallChests=0;
            foreach ($chestSmallChests as $chestSmallChest) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($chestSmallChest) || !array_key_exists('id', $chestSmallChest)) {
                    continue;
                }

                $chestSmallChestEntity = $this->em->getRepository(ChestSmallChest::class)->findOneBy(
                    array(
                        "importId" => $chestSmallChest['id'] . "_" . $this->restaurantCode
                    )
                );
                if (!$chestSmallChestEntity) {
                    $chestSmallChestEntity = new ChestSmallChest();
                } else {
                    $isUpdate = true;
                }

                $chestSmallChestEntity->setTotalCash($chestSmallChest['totalCash'] ? floatval($chestSmallChest['totalCash']) : null);
                $chestSmallChestEntity->setElectronicDeposed(boolval($chestSmallChest['electronicDeposed']));
                $chestSmallChestEntity->setRealTotal($chestSmallChest['realTotal'] ? floatval($chestSmallChest['realTotal']) : null);
                $chestSmallChestEntity->setTheoricalTotal($chestSmallChest['theoricalTotal'] ? floatval($chestSmallChest['theoricalTotal']) : null);
                $chestSmallChestEntity->setGap($chestSmallChest['gap']);
                $chestSmallChestEntity->setRealCashTotal($chestSmallChest['realCashTotal'] ? floatval($chestSmallChest['realCashTotal']) : null);
                $chestSmallChestEntity->setRealTrTotal($chestSmallChest['realTrTotal'] ? floatval($chestSmallChest['realTrTotal']) : null);
                $chestSmallChestEntity->setRealTreTotal($chestSmallChest['realTreTotal']);
                $chestSmallChestEntity->setRealCBTotal($chestSmallChest['realCBTotal']);
                $chestSmallChestEntity->setRealCheckQuickTotal($chestSmallChest['realCheckQuickTotal']);
                $chestSmallChestEntity->setRealForeignCurrencyTotal($chestSmallChest['realForeignCurrencyTotal']);
                $chestSmallChestEntity->setTheoricalCashTotal($chestSmallChest['theoricalCashTotal'] ? floatval($chestSmallChest['theoricalCashTotal']) : null);
                $chestSmallChestEntity->setTheoricalTrTotal($chestSmallChest['theoricalTrTotal'] ? floatval($chestSmallChest['theoricalTrTotal']) : null);
                $chestSmallChestEntity->setTheoricalTreTotal($chestSmallChest['theoricalTreTotal']);
                $chestSmallChestEntity->setTheoricalCBTotal($chestSmallChest['theoricalCBTotal']);
                $chestSmallChestEntity->setTheoricalCheckQuickTotal($chestSmallChest['theoricalCheckQuickTotal']);
                $chestSmallChestEntity->setTheoricalForeignCurrencyTotal($chestSmallChest['theoricalForeignCurrencyTotal']);
                $chestSmallChestEntity->setGlobalId($chestSmallChest['globalId']);
                $chestSmallChestEntity->setRealTrTotalDetail($chestSmallChest['realTrTotalDetail']);
                $chestSmallChestEntity->setTheoricalTrTotalDetail($chestSmallChest['theoricalTrTotalDetail']);

                foreach ($chestSmallChest['cashboxCountsIds'] as $id) {
                    $cashboxCount = $this->em->getRepository(CashboxCount::class)->findOneBy(
                        array(
                            "importId" => $id . "_" . $this->restaurantCode,
                            "originRestaurant" => $this->restaurant
                        )
                    );
                    if ($cashboxCount) {
                        if(!$chestSmallChestEntity->getCashboxCounts()->contains($cashboxCount)){
                            $chestSmallChestEntity->addCashboxCount($cashboxCount);
                            $cashboxCount->setSmallChest($chestSmallChestEntity);
                            $this->em->persist($cashboxCount);
                        }
                    } else {
                        $skippedChestSmallChests++;
                        $this->logger->info('Chest Small Chests Skipped because cashboxCount doesn\'t exist : ', array("chestSmallChestId" => $chestSmallChest['id'], "cashboxCountId" => $id, "Restaurant" => $this->restaurant->getName()));
                        continue 2;
                    }
                }

                if($isUpdate){
                    foreach ($chestSmallChestEntity->getForeignCurrencyCounts() as $foreignCurrency){
                        $this->em->remove($foreignCurrency);
                    }
                    $this->em->flush();
                }
                foreach ($chestSmallChest['foreignCurrencyCounts'] as $foreignCurrency) {
                    $foreignCurrencyCount = new CashboxForeignCurrency();
                    $foreignCurrencyCount
                        ->setAmount($foreignCurrency['amount'] ? floatval($foreignCurrency['amount']) : null)
                        ->setExchangeRate($foreignCurrency['exchangeRate'] ? floatval($foreignCurrency['exchangeRate']) : null)
                        ->setForeignCurrencyLabel($foreignCurrency['foreignCurrencyLabel']);
                    $chestSmallChestEntity->addForeignCurrencyCount($foreignCurrencyCount);
                }

                if($isUpdate){
                    foreach ($chestSmallChestEntity->getCheckQuickCounts() as $checkQuick){
                        $this->em->remove($checkQuick);
                    }
                    $this->em->flush();
                }
                foreach ($chestSmallChest['checkQuickCounts'] as $checkQuick) {
                    $checkQuickCount = new CashboxCheckQuick();
                    $checkQuickCount
                        ->setQty($checkQuick['qty'])
                        ->setUnitValue($checkQuick['unitValue']);
                    $chestSmallChestEntity->addCheckQuickCount($checkQuickCount);
                }

                if($isUpdate){
                    foreach ($chestSmallChestEntity->getTicketRestaurantCounts() as $ticketRestaurant){
                        $this->em->remove($ticketRestaurant);
                    }
                    $this->em->flush();
                }
                foreach ($chestSmallChest['ticketRestaurantCounts'] as $ticketRestaurant) {
                    $ticketRestaurantCount = new CashboxTicketRestaurant();
                    $ticketRestaurantCount
                        ->setQty($ticketRestaurant['qty'])
                        ->setUnitValue($ticketRestaurant['unitValue'])
                        ->setTicketName($ticketRestaurant['ticketName'])
                        ->setIdPayment($ticketRestaurant['idPayment'])
                        ->setElectronic(boolval($ticketRestaurant['electronic']));
                    $chestSmallChestEntity->addTicketRestaurantCount($ticketRestaurantCount);
                }

                if($isUpdate){
                    foreach ($chestSmallChestEntity->getBankCardCounts() as $bankCard){
                        $this->em->remove($bankCard);
                    }
                    $this->em->flush();
                }
                foreach ($chestSmallChest['bankCardCounts'] as $bankCard) {
                    $bankCardCount = new CashboxBankCard();
                    $bankCardCount
                        ->setIdPayment($bankCard['idPayment'])
                        ->setCardName($bankCard['cardName'])
                        ->setAmount($bankCard['amount'] ? floatval($bankCard['amount']) : null);
                    $chestSmallChestEntity->addBankCardCount($bankCardCount);
                }

                $chestSmallChestEntity->setImportId($chestSmallChest['id'] . "_" . $this->restaurantCode);

                $this->em->persist($chestSmallChestEntity);
                $isUpdate ? $updatedChestSmallChests++ :   $addedChestSmallChests++;
                $this->flush($batchCounter);

            }
            $this->em->flush();
            $chestSmallChests=null;
            unset($chestSmallChests);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedChestSmallChests." Chest Small Chests were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedChestSmallChests." Chest Small Chests were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedChestSmallChests." Chest Small Chests were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");


            /////////////////////////////////////////////////
            //import Chest Cashbox Funds
            $output->writeln("Importing Chest Cashbox Funds...");
            $chestCashboxFunds=$financialData['chestCashboxFunds'];

            $progress = new ProgressBar($output, count($chestCashboxFunds));
            $progress->start();
            $addedChestCashboxFunds=0;
            $updateChestCashboxFunds=0;
            $skippedChestCashboxFunds=0;
            foreach ($chestCashboxFunds as $chestCashboxFund) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($chestCashboxFund) || !array_key_exists('id', $chestCashboxFund)) {
                    continue;
                }

                $chestCashboxFundEntity = $this->em->getRepository(ChestCashboxFund::class)->findOneBy(
                    array(
                        "importId" => $chestCashboxFund['id'] . "_" . $this->restaurantCode
                    )
                );
                if (!$chestCashboxFundEntity) {
                    $chestCashboxFundEntity = new ChestCashboxFund();
                } else {
                    $isUpdate = true;
                }

                $chestCashboxFundEntity
                    ->setNbrOfCashboxes($chestCashboxFund['nbrOfCashboxes'])
                    ->setInitialCashboxFunds($chestCashboxFund['initialCashboxFunds'])
                    ->setTheoricalNbrOfCashboxes($chestCashboxFund['theoricalNbrOfCashboxes'])
                    ->setTheoricalInitialCashboxFunds($chestCashboxFund['theoricalInitialCashboxFunds']);

                $chestCashboxFundEntity
                    ->setImportId($chestCashboxFund['id'] . "_" . $this->restaurantCode);

                $this->em->persist($chestCashboxFundEntity);
                $isUpdate ? $updateChestCashboxFunds++ :   $addedChestCashboxFunds++;
                $this->flush($batchCounter);

            }
            $this->em->flush();
            $chestCashboxFunds=null;
            unset($chestCashboxFunds);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedChestCashboxFunds." Chest Cashbox Funds were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updateChestCashboxFunds." Chest Cashbox Funds were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedChestCashboxFunds." Chest Cashbox Funds were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            /////////////////////////////////////////////////
            //import Chest Exchange Funds
            $output->writeln("Importing Chest Exchange Funds...");
            $chestExchangeFunds=$financialData['chestExchangeFunds'];

            $progress = new ProgressBar($output, count($chestExchangeFunds));
            $progress->start();
            $addedChestExchangeFunds=0;
            $updatedChestExchangeFunds=0;
            $skippedChestExchangeFunds=0;
            foreach ($chestExchangeFunds as $chestExchangeFund) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($chestExchangeFund) || !array_key_exists('id', $chestExchangeFund)) {
                    continue;
                }

                $chestExchangeFundEntity = $this->em->getRepository(ChestExchangeFund::class)->findOneBy(
                    array(
                        "importId" => $chestExchangeFund['id'] . "_" . $this->restaurantCode
                    )
                );
                if (!$chestExchangeFundEntity) {
                    $chestExchangeFundEntity = new ChestExchangeFund();
                } else {
                    $isUpdate = true;
                }

                $chestExchangeFundEntity->setRealTotal($chestExchangeFund['realTotal'] ? floatval($chestExchangeFund['realTotal']) : null);
                $chestExchangeFundEntity->setTheoricalTotal($chestExchangeFund['theoricalTotal'] ? floatval($chestExchangeFund['theoricalTotal']) : null);

                if($isUpdate){
                    foreach ($chestExchangeFundEntity->getChestExchanges() as $chestExchange){
                        $this->em->remove($chestExchange);
                    }
                    $this->em->flush();
                }
                foreach ($chestExchangeFund['chestExchanges'] as $chestExchange) {
                    $chestExchangeEntity = new ChestExchange();
                    $chestExchangeEntity
                        ->setQty($chestExchange['qty'])
                        ->setUnitValue($chestExchange['unitValue'])
                        ->setUnitLabel($chestExchange['unitLabel'])
                        ->setType($chestExchange['type']);
                    if(!empty($chestExchange['params'])){
                        $parameter = $this->em->getRepository(Parameter::class)->findOneBy(
                            array(
                                "type" => $chestExchange['params']['type'],
                                "value" => serialize($chestExchange['params']['value'])
                            )
                        );
                        if (!$parameter) {
                            $parameter = new Parameter();
                            $parameter
                                ->setType($chestExchange['params']['type'])
                                ->setLabel($chestExchange['params']['label'])
                                ->setValue($chestExchange['params']['value']);
                            $this->em->persist($parameter);
                            $this->em->flush();
                        }
                        $chestExchangeEntity->setUnitParamsId($parameter->getId());
                    }

                    $chestExchangeFundEntity->addChestExchange($chestExchangeEntity);

                }

                $chestExchangeFundEntity
                    ->setImportId($chestExchangeFund['id'] . "_" . $this->restaurantCode);

                $this->em->persist($chestExchangeFundEntity);
                $isUpdate ? $updatedChestExchangeFunds++ :   $addedChestExchangeFunds++;
                $this->flush($batchCounter);

            }

            $this->em->flush();
            $chestExchangeFunds=null;
            unset($chestExchangeFunds);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedChestExchangeFunds." Chest Exchange Funds were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedChestExchangeFunds." Chest Exchange Funds were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedChestExchangeFunds." Chest Exchange Funds were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");


            /////////////////////////////////////////////////
            //import Chest Counts

            $output->writeln("Importing Chest Counts...");
            $this->em->getClassMetadata(ChestCount::class)->setLifecycleCallbacks(array());// disbale all event because data already calculated
            $chestCounts=$financialData['chestCounts'];
            $progress = new ProgressBar($output, count($chestCounts));
            $progress->start();
            $addedChestCounts=0;
            $updatedChestCounts=0;
            $skippedChestCounts=0;
            $this->em->getClassMetadata(ChestCount::class)->setLifecycleCallbacks(array());// disbale all event because data already calculated
            foreach ($chestCounts as $chestCount) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($chestCount) || !array_key_exists('id', $chestCount)) {
                    continue;
                }

                $chestCountEntity = $this->em->getRepository(ChestCount::class)->findOneBy(
                    array(
                        "importId" => $chestCount['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );
                if (!$chestCountEntity) {
                    $chestCountEntity = new ChestCount();
                } else {
                    $isUpdate = true;
                }

                $createdAt = new \DateTime($chestCount['createdAt']['date']);
                $date = new \DateTime($chestCount['date']['date']);
                $closureDate = null;
                if ($chestCount['closureDate']) {
                    $closureDate = new \DateTime($chestCount['closureDate']['date']);
                }

                unset($userName);
                if ($chestCount['owner']) {

                    if(empty($chestCount['owner']['wyndId'])){
                        $userName=$chestCount['owner']['username'];
                    }else{
                        $userName=$this->restaurantCode."_".$chestCount['owner']['username'];
                    }
                    $owner = $this->em->getRepository(Employee::class)->findOneBy(
                        array(
                            "username" => $userName
                        )
                    );
                    if ($owner) {
                        $chestCountEntity->setOwner($owner);
                    } else {
                        $skippedChestCounts++;
                        $this->logger->info('Chest Count Skipped because owner doesn\'t exist : ', array("chestCountId" => $chestCount['id'], "userName" => $chestCount['owner']['username'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }

                $chestCountEntity
                    ->setCreatedAt($createdAt)
                    ->setDate($date)
                    ->setSynchronized(boolval($chestCount['synchronized']))
                    ->setClosureDate($closureDate)
                    ->setClosure(boolval($chestCount['closure']))
                    ->setEft(boolval($chestCount['eft']))
                    ->setRealTotal($chestCount['realTotal'] ? floatval($chestCount['realTotal']) : null)
                    ->setTheoricalTotal($chestCount['theoricalTotal'] ? floatval($chestCount['theoricalTotal']) : null)
                    ->setGap($chestCount['gap']);

                if($isUpdate) {
                    $tirlire = $chestCountEntity->getTirelire();
                    if ($tirlire) {
                        $this->em->remove($tirlire);
                        $this->em->flush();
                    }
                }
                if ($chestCount['tirelire']) {
                    $tirelire = new ChestTirelire();
                    $tirelire->setRealTotal($chestCount['tirelire']['realTotal']);
                    $tirelire->setTheoricalTotal($chestCount['tirelire']['theoricalTotal']);
                    $tirelire->setGap($chestCount['tirelire']['gap']);
                    $tirelire->setTotalCashEnvelopes($chestCount['tirelire']['totalCashEnvelopes']);
                    $tirelire->setTotalTrEnvelopes($chestCount['tirelire']['totalTrEnvelopes']);
                    $tirelire->setChestCount($chestCountEntity);
                    $chestCountEntity->setTirelire($tirelire);
                }

                if ($chestCount['smallChestId']) {
                    $smallChest = $this->em->getRepository(ChestSmallChest::class)->findOneBy(
                        array(
                            "importId" => $chestCount['smallChestId'] . "_" . $this->restaurantCode,
                        )
                    );
                    if ($smallChest) {
                        $smallChest->setChestCount($chestCountEntity);
                        $chestCountEntity->setSmallChest($this->em->getReference(ChestSmallChest::class, $smallChest->getId()));
                    } else {
                        $skippedChestCounts++;
                        $this->logger->info('Chest Count Skipped because smallChest doesn\'t exist : ', array("chestCountId" => $chestCount['id'], "smallChestId" => $chestCount['smallChestId'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }
                if ($chestCount['exchangeFundId']) {
                    $exchangeFund = $this->em->getRepository(ChestExchangeFund::class)->findOneBy(
                        array(
                            "importId" => $chestCount['exchangeFundId'] . "_" . $this->restaurantCode,
                        )
                    );
                    if ($exchangeFund) {
                        $exchangeFund->setChestCount($chestCountEntity);
                        $this->em->persist($exchangeFund);
                        $chestCountEntity->setExchangeFund($this->em->getReference(ChestExchangeFund::class, $exchangeFund->getId()));
                    } else {
                        $skippedChestCounts++;
                        $this->logger->info('Chest Count Skipped because ChestExchangeFund doesn\'t exist : ', array("chestCountId" => $chestCount['id'], "exchangeFundId" => $chestCount['exchangeFundId'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }
                if ($chestCount['cashboxFundId']) {
                    $cashboxFund = $this->em->getRepository(ChestCashboxFund::class)->findOneBy(
                        array(
                            "importId" => $chestCount['cashboxFundId'] . "_" . $this->restaurantCode,
                        )
                    );
                    if ($cashboxFund) {
                        $cashboxFund->setChestCount($chestCountEntity);
                        $this->em->persist($cashboxFund);
                        $chestCountEntity->setCashboxFund($this->em->getReference(ChestCashboxFund::class, $cashboxFund->getId()));
                    } else {
                        $skippedChestCounts++;
                        $this->logger->info('Chest Count Skipped because ChestCashboxFund doesn\'t exist : ', array("chestCountId" => $chestCount['id'], "cashboxFundId" => $chestCount['cashboxFundId'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }

                foreach ($chestCount['envelopesIds'] as $id) {
                    $envelope = $this->em->getRepository(Envelope::class)->findOneBy(
                        array(
                            "importId" => $id . "_" . $this->restaurantCode,
                            "originRestaurant" => $this->restaurant
                        )
                    );
                    if ($envelope) {
                        if(!$chestCountEntity->getEnvelopes()->contains($envelope)){
                            $chestCountEntity->addEnvelope($envelope);
                            $envelope->setChestCount($chestCountEntity);
                            $this->em->persist($envelope);
                        }
                    } else {
                        $skippedChestCounts++;
                        $this->logger->info('Chest Count Skipped because envelope doesn\'t exist : ', array("chestCountId" => $chestCount['id'], "envelopeId" => $id, "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }

                foreach ($chestCount['depositsIds'] as $id) {
                    $deposit = $this->em->getRepository(Deposit::class)->findOneBy(
                        array(
                            "importId" => $id . "_" . $this->restaurantCode,
                            "originRestaurant" => $this->restaurant
                        )
                    );
                    if ($deposit) {
                        if(!$chestCountEntity->getDeposits()->contains($deposit)){
                            $chestCountEntity->addDeposit($deposit);
                            $deposit->setChestCount($chestCountEntity);
                            $this->em->persist($deposit);
                        }
                    } else {
                        $skippedChestCounts++;
                        $this->logger->info('Chest Count Skipped because deposit doesn\'t exist : ', array("chestCountId" => $chestCount['id'], "depositId" => $id, "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }
                foreach ($chestCount['expensesIds'] as $id) {
                    $expense = $this->em->getRepository(Expense::class)->findOneBy(
                        array(
                            "importId" => $id . "_" . $this->restaurantCode,
                            "originRestaurant" => $this->restaurant
                        )
                    );
                    if ($expense) {
                        if(!$chestCountEntity->getExpenses()->contains($expense)) {
                            $chestCountEntity->addExpense($expense);
                            $expense->setChestCount($chestCountEntity);
                            $this->em->persist($expense);
                        }
                    } else {
                        $skippedChestCounts++;
                        $this->logger->info('Chest Count Skipped because expense doesn\'t exist : ', array("chestCountId" => $chestCount['id'], "expenseId" => $id, "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }

                //remove old recipe tickets in case of update
                if($isUpdate){
                    foreach ($chestCountEntity->getRecipeTickets() as $recipeTicket){
                        $this->em->remove($recipeTicket);
                    }
                    $this->em->flush();
                }
                foreach ($chestCount['recipeTickets'] as $recipeTicket) {
                    $recipeTicketEntity = new RecipeTicket();

                    $recipeTicketEntity
                        ->setLabel($recipeTicket['label'])
                        ->setAmount( floatval($recipeTicket['amount']))
                        ->setDeleted(boolval($recipeTicket['deleted']));
                    $recipeTicketEntity->setOriginRestaurant($this->restaurant);
                    $recipeTicketEntity->setImportId($recipeTicket['id'] . "_" . $this->restaurantCode);
                    $date = new \DateTime($recipeTicket['date']['date']);
                    $recipeTicketEntity->setDate($date);

                    unset($userName);
                    if ($recipeTicket['owner']) {

                        if(empty($recipeTicket['owner']['wyndId'])){
                            $userName=$recipeTicket['owner']['username'];
                        }else{
                            $userName=$this->restaurantCode."_".$recipeTicket['owner']['username'];
                        }
                        $owner = $this->em->getRepository(Employee::class)->findOneBy(
                            array(
                                "username" => $userName
                            )
                        );
                        if ($owner) {
                            $recipeTicketEntity->setOwner($owner);
                        } else {
                            $this->logger->info('Chest Count recipe Ticket Skipped because owner doesn\'t exist : ', array("cashboxCountId" => $chestCount['id'], "userName" => $recipeTicket['owner']['username'], "Restaurant" => $this->restaurant->getName()));
                            continue;
                        }
                    }
                    $chestCountEntity->addRecipeTicket($recipeTicketEntity);
                    $recipeTicketEntity->setChestCount($chestCountEntity);
                    $this->em->persist($recipeTicketEntity);

                }

                $chestCountEntity
                    ->setImportId($chestCount['id'] . "_" . $this->restaurantCode)
                    ->setOriginRestaurant($this->restaurant);


                $this->em->persist($chestCountEntity);
                $isUpdate ? $updatedChestCounts++ : $addedChestCounts++;
                $this->flush($batchCounter);

            }

            $this->em->flush();
            $progress->finish();

            $chestCounts=$financialData['chestCounts'];

            $output->writeln("");
            $output->writeln("Setting Last Chest Counts Ids...");
            $progress = new ProgressBar($output, count($chestCounts));
            $progress->start();
            foreach ($chestCounts as $chestCount) {
                $progress->advance();
                if (empty($chestCount) || !array_key_exists('id', $chestCount)) {
                    continue;
                }
                $chestCountEntity=$this->em->getRepository(ChestCount::class)->findOneBy(
                    array(
                        "importId"=>$chestCount['id']."_".$this->restaurantCode,
                        "originRestaurant"=>$this->restaurant
                    )
                );
                if($chestCountEntity) {
                    if ($chestCount['lastChestCountId']) {
                        $lastChestCount = $this->em->getRepository(ChestCount::class)->findOneBy(
                            array(
                                "importId" => $chestCount['lastChestCountId'] . "_" . $this->restaurantCode,
                                "originRestaurant" => $this->restaurant
                            )
                        );
                        if ($lastChestCount) {
                            $chestCountEntity->setLastChestCount($lastChestCount);
                        }
                    }
                    $this->em->persist($chestCountEntity);
                }
            }
            $this->em->flush();
            $chestCounts=null;
            unset($chestCounts);
            $progress->finish();

            $output->writeln("");
            $output->writeln("--> ".$addedChestCounts." Chest Counts were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedChestCounts." Chest Counts were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedChestCounts." Chest Counts were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            /////////////////////////////////////////////////
            //import Recipe tickets not attached to chest count
            $output->writeln("Importing Recipe tickets...");
            $recipeTickets=$financialData['recipeTickets'];

            $progress = new ProgressBar($output, count($recipeTickets));
            $progress->start();
            $addedRecipeTickets=0;
            $updatedRecipeTickets=0;
            $skippedRecipeTickets=0;
            foreach ($recipeTickets as $recipeTicket) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($recipeTicket) || !array_key_exists('id', $recipeTicket)) {
                    continue;
                }

                $recipeTicketEntity = $this->em->getRepository(RecipeTicket::class)->findOneBy(
                    array(
                        "importId" => $recipeTicket['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );
                if (!$recipeTicketEntity) {
                    $recipeTicketEntity = new RecipeTicket();
                } else {
                    $isUpdate=true;
                }


                $recipeTicketEntity
                    ->setLabel($recipeTicket['label'])
                    ->setAmount(floatval($recipeTicket['amount']))
                    ->setDeleted(boolval($recipeTicket['deleted']));
                $recipeTicketEntity->setOriginRestaurant($this->restaurant);
                $recipeTicketEntity->setImportId($recipeTicket['id'] . "_" . $this->restaurantCode);
                $date = new \DateTime($recipeTicket['date']['date']);
                $recipeTicketEntity->setDate($date);

               $userName=null;
                if ($recipeTicket['owner']) {

                    if(empty($recipeTicket['owner']['wyndId'])){
                        $userName=$recipeTicket['owner']['username'];
                    }else{
                        $userName=$this->restaurantCode."_".$recipeTicket['owner']['username'];
                    }
                    $owner = $this->em->getRepository(Employee::class)->findOneBy(
                        array(
                            "username" => $userName
                        )
                    );
                    if ($owner) {
                        $recipeTicketEntity->setOwner($owner);
                    } else {
                        $this->logger->info('Recipe Ticket Skipped because owner doesn\'t exist : ', array("recipeTicketId" =>$recipeTicket['id'], "userName" => $recipeTicket['owner']['username'], "Restaurant" => $this->restaurant->getName()));
                        continue;
                    }
                }

                $this->em->persist($recipeTicketEntity);

                $isUpdate ? $updatedRecipeTickets++ : $addedRecipeTickets++;
                $this->flush($batchCounter);

            }
            $this->em->flush();
            $recipeTickets=null;
            unset($recipeTickets);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedRecipeTickets." Recipe Tickets were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedRecipeTickets." Recipe Tickets were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedRecipeTickets." Recipe Tickets were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");


            /////////////////////////////////////////////////
            //import Administrative Closing
            $output->writeln("Importing Administrative Closing...");
            $administrativeClosings=$financialData['administrativeClosings'];

            $progress = new ProgressBar($output, count($administrativeClosings));
            $progress->start();
            $addedAdministrativeClosings=0;
            $updatedAdministrativeClosings=0;
            $skippedAdministrativeClosings=0;
            foreach ($administrativeClosings as $administrativeClosing) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($administrativeClosing) || !array_key_exists('id', $administrativeClosing)) {
                    continue;
                }

                $administrativeClosingEntity = $this->em->getRepository(AdministrativeClosing::class)->findOneBy(
                    array(
                        "importId" => $administrativeClosing['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );
                if (!$administrativeClosingEntity) {
                    $administrativeClosingEntity = new AdministrativeClosing();
                } else {
                    $isUpdate=true;
                }

                $date = new \DateTime($administrativeClosing['date']['date']);
                $createdAt = new \DateTime($administrativeClosing['createdAt']['date']);


                $administrativeClosingEntity
                    ->setDate($date)
                    ->setCreatedAt($createdAt)
                    ->setComparable(boolval($administrativeClosing['comparable']))
                    ->setComment($administrativeClosing['comment'])
                    ->setCreditAmount(floatval($administrativeClosing['creditAmount']))
                    ->setCaBrutTTCRapportZ(floatval($administrativeClosing['caBrutTTCRapportZ']));

                $administrativeClosingEntity->setImportId($administrativeClosing['id'] . "_" . $this->restaurantCode);
                $administrativeClosingEntity->setOriginRestaurant($this->restaurant);

                $this->em->persist($administrativeClosingEntity);
                $isUpdate ? $updatedAdministrativeClosings++ : $addedAdministrativeClosings++;
                $this->flush($batchCounter);

            }
            $this->em->flush();
            $administrativeClosings=null;
            unset($administrativeClosings);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedAdministrativeClosings." Administrative Closings were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedAdministrativeClosings." Administrative Closings were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedAdministrativeClosings." Administrative Closings were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            /////////////////////////////////////////////////
            //import Admin Closing Tmps
            $output->writeln("Importing Administrative Closing Tmps...");
            $adminClosingTmps=$financialData['adminClosingTmps'];

            $progress = new ProgressBar($output, count($adminClosingTmps));
            $progress->start();
            $addedAdminClosingTmps=0;
            $updatedAdminClosingTmps=0;
            $skippedAdminClosingTmps=0;
            foreach ($adminClosingTmps as $adminClosingTmp) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($adminClosingTmp) || !array_key_exists('id', $adminClosingTmp)) {
                    continue;
                }

                $adminClosingTmpEntity = $this->em->getRepository(AdminClosingTmp::class)->findOneBy(
                    array(
                        "importId" => $adminClosingTmp['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );
                if (!$adminClosingTmpEntity) {
                    $adminClosingTmpEntity = new AdminClosingTmp();
                } else {
                    $isUpdate = true;
                }

                $date = new \DateTime($adminClosingTmp['date']['date']);

                $adminClosingTmpEntity
                    ->setDate($date)
                    ->setCaBrutTTCRapportZ(floatval($adminClosingTmp['caBrutTTCRapportZ']));

                $adminClosingTmpEntity->setImportId($adminClosingTmp['id'] . "_" . $this->restaurantCode);
                $adminClosingTmpEntity->setOriginRestaurant($this->restaurant);

                $this->em->persist($adminClosingTmpEntity);
                $isUpdate ? $updatedAdminClosingTmps++ : $addedAdminClosingTmps++;
                $this->flush($batchCounter);

            }
            $this->em->flush();
            $adminClosingTmps=null;
            unset($adminClosingTmps);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedAdminClosingTmps." Administrative Closings Tmps were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedAdminClosingTmps." Administrative Closings Tmps were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedAdminClosingTmps." Administrative Closings Tmps were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");


            /////////////////////////////////////////////////
            //import Financial Revenue
            $output->writeln("Importing Financial Revenue...");
            $financialRevenues=$financialData['financialRevenues'];

            $progress = new ProgressBar($output, count($financialRevenues));
            $progress->start();
            $addedFinancialRevenues=0;
            $updatedFinancialRevenues=0;
            $skippedFinancialRevenues=0;
            foreach ($financialRevenues as $financialRevenue){
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if(empty($financialRevenue) || !array_key_exists('id',$financialRevenue)){
                    continue;
                }

                $financialRevenueEntity=$this->em->getRepository(FinancialRevenue::class)->findOneBy(
                    array(
                        "importId"=>$financialRevenue['id']."_".$this->restaurantCode,
                        "originRestaurant"=>$this->restaurant
                    )
                );
                if(!$financialRevenueEntity) {
                    $financialRevenueEntity = new FinancialRevenue();
                }else{
                    $isUpdate=true;
                }

                    $date=new \DateTime($financialRevenue['date']['date']);

                    $financialRevenueEntity
                        ->setDate($date)
                        ->setAmount(floatval($financialRevenue['amount']) )
                        ->setNetHT(floatval($financialRevenue['netHT']))
                        ->setNetTTC(floatval($financialRevenue['netTTC']))
                        ->setBrutTTC(floatval($financialRevenue['brutTTC']))
                        ->setBr(floatval($financialRevenue['br']))
                        ->setBrHt(floatval($financialRevenue['brHt']))
                        ->setDiscount(floatval($financialRevenue['discount']))
                        ->setBrutHT(floatval($financialRevenue['brutHT']))
                        ->setSynchronized(boolval($financialRevenue['synchronized']));

                    $financialRevenueEntity->setImportId($financialRevenue['id']."_".$this->restaurantCode);
                    $financialRevenueEntity->setOriginRestaurant($this->restaurant);

                    $this->em->persist($financialRevenueEntity);
                    $isUpdate ? $updatedFinancialRevenues++ : $addedFinancialRevenues++;
                    $this->flush($batchCounter);

            }

            $this->em->flush();
            $financialRevenues=null;
            unset($financialRevenues);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedFinancialRevenues." Financial Revenues were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedFinancialRevenues." Financial Revenues were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedFinancialRevenues." Financial Revenues were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");

            /////////////////////////////////////////////////
            //import Ca Prev Data
            $output->writeln("Importing Ca Prev...");
            $caPrevs=$financialData['caPrevs'];

            $progress = new ProgressBar($output, count($caPrevs));
            $progress->start();
            $addedCaPrevs=0;
            $updatedCaPrevs=0;
            $skippedCaPrevs=0;
            foreach ($caPrevs as $caPrev) {
                $progress->advance();
                $batchCounter++;
                $isUpdate = false;
                if (empty($caPrev) || !array_key_exists('id', $caPrev)) {
                    continue;
                }

                $caPrevEntity = $this->em->getRepository(CaPrev::class)->findOneBy(
                    array(
                        "importId" => $caPrev['id'] . "_" . $this->restaurantCode,
                        "originRestaurant" => $this->restaurant
                    )
                );
                if (!$caPrevEntity) {
                    $caPrevEntity = new CaPrev();
                } else {
                    $isUpdate = true;
                }

                $date = new \DateTime($caPrev['date']['date']);
                $date1 = new \DateTime($caPrev['date1']['date']);
                $date2 = new \DateTime($caPrev['date2']['date']);
                $date3 = new \DateTime($caPrev['date3']['date']);
                $date4 = new \DateTime($caPrev['date4']['date']);
                $date5 = new \DateTime($caPrev['date5']['date']);
                $date6 = new \DateTime($caPrev['date6']['date']);
                $date7 = new \DateTime($caPrev['date7']['date']);
                $date8 = new \DateTime($caPrev['date8']['date']);
                $comparableDay = new \DateTime($caPrev['comparableDay']['date']);


                $caPrevEntity
                    ->setDate($date)
                    ->setDate1($date1)
                    ->setDate2($date2)
                    ->setDate3($date3)
                    ->setDate4($date4)
                    ->setDate5($date5)
                    ->setDate6($date6)
                    ->setDate7($date7)
                    ->setDate8($date8)
                    ->setComparableDay($comparableDay)
                    ->setCa(floatval($caPrev['ca']))
                    ->setIsTyped(boolval($caPrev['isTyped']))
                    ->setVariance(floatval($caPrev['variance']))
                    ->setFixed(boolval($caPrev['fixed']))
                    ->setSynchronized(boolval($caPrev['synchronized']));

                $caPrevEntity->setImportId($caPrev['id'] . "_" . $this->restaurantCode);
                $caPrevEntity->setOriginRestaurant($this->restaurant);

                $this->em->persist($caPrevEntity);
                $isUpdate ? $updatedCaPrevs++ : $addedCaPrevs++;
                $this->flush($batchCounter);

            }
            $this->em->flush();
            $caPrevs=null;
            unset($caPrevs);

            $progress->finish();
            $output->writeln("");
            $output->writeln("--> ".$addedCaPrevs." Ca Prevs were added successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$updatedCaPrevs." Ca Prevs were updated successfully for [".$this->restaurant->getName()."] restaurant.");
            $output->writeln("--> ".$skippedCaPrevs." Ca Prevs were skipped because of missing data or they already exist in [".$this->restaurant->getName()."] restaurant.");


            /////////////////////////////////////////////
            //Update envelope sourceId
            $query = $this->em
                ->getRepository(Envelope::class)
                ->createQueryBuilder('e')
                ->where("e.originRestaurant = :restaurant")
                ->setParameter("restaurant",$this->restaurant)
                ->getQuery();
            $envelopes = $query->getResult();
            $output->writeln("Updating Envelopes source Ids...");
            $progress = new ProgressBar($output, count($envelopes));
            $progress->start();
            foreach($envelopes as $envelope){

                if($envelope->getSourceId()){
                    switch ($envelope->getSource()) {
                        case Envelope::WITHDRAWAL :
                            $withdrawal=$this->em->getRepository(Withdrawal::class)->findOneBy(
                                array(
                                    "importId"=>$envelope->getSourceId()."_".$this->restaurantCode
                                )
                            );
                            if($withdrawal){
                                $envelope->setSourceId($withdrawal->getId());
                                $withdrawal->setEnvelopeId($envelope->getId());
                                $this->em->persist($envelope);
                                $this->em->persist($withdrawal);
                            }else{
                                $this->logger->info('Cannot update Envelope source ID, Withdrawal not found : ',array("envelopeId"=>$envelope->getId(),"sourceId"=>$envelope->getSource(),"Restaurant"=>$this->restaurant->getName()));
                            }
                            break;
                        case Envelope::CASHBOX_COUNTS :
                            $cashboxCount=$this->em->getRepository(CashboxCount::class)->findOneBy(
                                array(
                                    "importId"=>$envelope->getSourceId()."_".$this->restaurantCode
                                )
                            );
                            if($cashboxCount){
                                $envelope->setSourceId($cashboxCount->getId());
                                $this->em->persist($envelope);
                            }else{
                                $this->logger->info('Cannot update Envelope source ID, CashboxCount not found : ',array("envelopeId"=>$envelope->getId(),"sourceId"=>$envelope->getSource(),"Restaurant"=>$this->restaurant->getName()));
                            }
                            break;
                        case Envelope::EXCHANGE_FUNDS:
                            $chestExchangeFund=$this->em->getRepository(ChestExchangeFund::class)->findOneBy(
                                array(
                                    "importId"=>$envelope->getSourceId()."_".$this->restaurantCode
                                )
                            );
                            if($chestExchangeFund){
                                $envelope->setSourceId($chestExchangeFund->getId());
                                $this->em->persist($envelope);
                            }else{
                                $this->logger->info('Cannot update Envelope source ID, ChestExchangeFund not found : ',array("envelopeId"=>$envelope->getId(),"sourceId"=>$envelope->getSource(),"Restaurant"=>$this->restaurant->getName()));
                            }
                            break;
                        case Envelope::SMALL_CHEST:
                            $chestSmallChest=$this->em->getRepository(ChestSmallChest::class)->findOneBy(
                                array(
                                    "importId"=>$envelope->getSourceId()."_".$this->restaurantCode
                                )
                            );
                            if($chestSmallChest){
                                $envelope->setSourceId($chestSmallChest->getId());
                                $this->em->persist($envelope);
                            }else{
                                $this->logger->info('Cannot update Envelope source ID, ChestSmallChest not found : ',array("envelopeId"=>$envelope->getId(),"sourceId"=>$envelope->getSource(),"Restaurant"=>$this->restaurant->getName()));
                            }
                            break;
                        default:
                            $this->logger->info('Invalid Envelope source : ',array("envelopeId"=>$envelope->getId(),"Restaurant"=>$this->restaurant->getName()));
                    }
                }
                $progress->advance();
            }

            $this->em->flush();
            $progress->finish();

        }catch (\Exception $e){
            $output->writeln("");
            $output->writeln("Command failed !");
            $output->writeln($e->getMessage());
            return;
        }

        $progress->finish();
        $output->writeln("\n====> Restaurant [".$this->restaurant->getName()."] Financial data imported successfully.");

    }

    //doctrine Batch Processing
    public function flush($i)
    {
        if ($i % 100 === 0) {
            $this->em->flush();
            $this->em->clear();
            gc_collect_cycles();
            $this->restaurant = $this->em->getRepository(Restaurant::class)->findOneByCode($this->restaurantCode);
        }
    }



}
