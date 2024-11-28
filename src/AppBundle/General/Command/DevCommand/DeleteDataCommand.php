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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class DeleteDataCommand extends ContainerAwareCommand
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

    private $sqlQueriesDir;


    protected function configure()
    {
        $this->setName('quick_dev:delete:data')->setDefinition([])
            ->addArgument('restaurantId', InputArgument::REQUIRED)
            ->addArgument('startDate', InputArgument::REQUIRED)
            ->addArgument('endDate', InputArgument::REQUIRED)
            ->addArgument('type', InputArgument::OPTIONAL)
            ->setDescription('delete data for a restaurant for a period');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('logger');
        $this->adminClosingService = $this->getContainer()->get('administrative.closing.service');
        $this->chestService = $this->getContainer()->get('chest.service');
        $this->tickets = $this->getContainer()->get('ticket.service');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $supportedFormat = "Y-m-d";

        $restaurantId = $input->getArgument('restaurantId');
        $restaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);

        if (!$restaurant) {
            $output->writeln("restaurant not found with id : " . $restaurantId);
            return;
        };

        $this->logger->info("Verifying dates", ['DeleteDataCommand', $startDate, $endDate]);


        if ($input->hasArgument('startDate') && Utilities::isValidDateFormat($input->getArgument('startDate'), $supportedFormat) && $input->hasArgument('endDate') && Utilities::isValidDateFormat($input->getArgument('endDate'), $supportedFormat)
            && !is_null($input->getArgument('type'))) {

            $startDate = date_create_from_format($supportedFormat, $input->getArgument('startDate'));
            $endDate = date_create_from_format($supportedFormat, $input->getArgument('endDate'));
            $endDate->setTime(0, 0, 0);
            $startDate->setTime(0, 0, 0);
            $type = $input->getArgument('type');
            if ($startDate > $endDate) {
                $output->writeln("enddate should be greater than the startdate");
                return;
            }
        } else if ($input->hasArgument('startDate') && Utilities::isValidDateFormat($input->getArgument('startDate'), $supportedFormat) && $input->hasArgument('endDate') && Utilities::isValidDateFormat($input->getArgument('endDate'), $supportedFormat)
            && is_null($input->getArgument('type'))) {

            $startDate = $input->getArgument('startDate');
            $endDate = $input->getArgument('endDate');
            $startDate = date_create_from_format($supportedFormat, $startDate);
            $endDate = date_create_from_format($supportedFormat, $endDate);
            $endDate->setTime(0, 0, 0);
            $startDate->setTime(0, 0, 0);
            $type = null;
            if ($startDate > $endDate) {
                $output->writeln("enddate should be greater than the startdate");
                return;
            }
        } else {
            $output->writeln("plz verify dates u wrote , there is something wrong ");
            return;
        }
        $output->writeln("start date is : " . $startDate->format('Y-m-d'));
        $output->writeln("end date is : " . $endDate->format('Y-m-d'));
        $output->writeln('*******************  Start by Restaurant Id *****************:' .$restaurantId);

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $diff = date_diff($startDate, $endDate)->format('%a');
        $output->writeln(' diff is ' . $diff);
        $i = 1;
        $progress = new ProgressBar($output, $diff);
        $connexion = $this->em->getConnection();


        $chestIds = array();
        // || is_null($type) je dois l'ajouter dans tout les conditions cas en cas ou on n'a pas spécifié le type la commande
        //va parcourir tout les gestions et faire le necessaire.
        if ($type == 'f') {
            //Financial management
            $output->writeln('Financial management for :' . $startDate->format('Y-m-d') . ' - ' . $endDate->format('Y-m-d'));

            //get all chest id used in the cashbox
            $sql = "select distinct small_chest_id from cashbox_count where origin_restaurant_id=:restaurantId and date>=:startDate and date <=:endDate order by small_chest_id desc ;";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("startDate", $startDate->format('Y-m-d'));
            $stm->bindParam("endDate", $endDate->format('Y-m-d'));
            $stm->execute();
            $chestIdsCashbox = $stm->fetchAll();


            $chestCashboxIds = array();
            foreach ($chestIdsCashbox as $chestId) {
                array_push($chestCashboxIds, $chestId['small_chest_id']);
            }

            if (!isset($chestIdsCashbox)) {
                $output->writeln('There is no cashboxs id to be insered');
            } else {

                $firstChestCount = $this->em->getRepository('Financial:ChestCount')->getChestCountForClosedDate($startDate, $restaurant);
                $lastChestCount = $this->em->getRepository('Financial:ChestCount')->getChestCountForClosedDate($endDate, $restaurant);


                $sql = "select id from chest_count where origin_restaurant_id=:restaurantId and id  <= :idend  and id >= :idstart order by id desc";
                $stm = $connexion->prepare($sql);
                $stm->bindParam("restaurantId", $restaurantId);
                $stm->bindParam("idstart", $firstChestCount->getId());
                $stm->bindParam("idend", $lastChestCount->getId());
                $stm->execute();
                $res = $stm->fetchAll();
                foreach ($res as $chestId) {
                    array_push($chestIds, $chestId['id']);
                }

           if (!is_null($firstChestCount->getLastChestCount())) {
                    $lastChestCountId = $firstChestCount->getLastChestCount()->getId();

                    $chest = $this->em->getRepository('Financial:ChestCount')->getChestCountById($lastChestCountId, $restaurant);

                    $ChestCreatedBeforeFirstChest = array();
                    while (!is_null($chest)) {
                        array_push($ChestCreatedBeforeFirstChest, $chest->getId());
                        if (!is_null($chest->getLastChestCount())) {
                            $chest = $this->em->getRepository('Financial:ChestCount')->getChestCountById($chest->getLastChestCount()->getId(), $restaurant);
                        } else {
                            $chest = null;
                        }

                    }
                    foreach ($ChestCreatedBeforeFirstChest as $c) {
                        if (!in_array($c, $chestCashboxIds)) {
                            array_push($chestIds, $c);
                        }
                    }
                }


                foreach ($chestCashboxIds as $chestUsed) {
                    if (!in_array($chestUsed, $chestIds) && !is_null($chestUsed)) {
                        array_push($chestIds, $chestUsed);
                    }
                }
                $listOfIds = implode(',', $chestIds);
                $output->writeln('Get all chest id used' . $listOfIds);


            }


            /**
             * Ticket and ticketLine and Ticket payement and ticket intervention and ticket
             */
            $sql = "select count(*) from ticket  where  origin_restaurant_id=:restaurantId and date>= :startDate and date <= :endDate ;";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("startDate", $startDate->format('Y-m-d'));
            $stm->bindParam("endDate", $endDate->format('Y-m-d'));
            $stm->execute();
            $data['ticket'] = $stm->fetchColumn();
            $output->writeln('There is ' . $data['ticket'] . ' to be deleted');
            if ($data['ticket'] == 0) {
                $output->writeln('There is no tickets to be deleted');
            } else {
                $output->writeln($data['ticket'] . ' tickets was found ');
                $content = file_get_contents('src/AppBundle/General/Command/Query/ticketDelete.sql');
                $queries = explode(';', $content);
                foreach ($queries as $q) {
                    $stmt = $connexion->prepare($q);
                    $stmt->bindParam("restaurantId", $restaurantId);
                    $stmt->bindParam("startDate", $startDate->format('Y-m-d'));
                    $stmt->bindParam("endDate", $endDate->format('Y-m-d'));
                    $stmt->execute();
                    $data['ticket'] = $stmt->fetchColumn();


                }

                $output->writeln('Tickets are deleted successfully ');


            }

            //Delete withdrawls
            $sql = "select id from  withdrawal   where  origin_restaurant_id=:restaurantId and date>= :startDate and date <=:endDate ;";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("startDate", $startDate->format('Y-m-d'));
            $stm->bindParam("endDate", $endDate->format('Y-m-d'));
            $stm->execute();
            $withdrawals = $stm->fetchAll();

            if (sizeof($withdrawals) == 0) {
                $output->writeln('There is no withdrawals to be deleted');
            } else {
                $output->writeln('There is ' . sizeof($withdrawals) . '  withdrawals to be deleted');
//                $sql = "delete from  withdrawal_tmp  where  origin_restaurant_id=:restaurantId and withdrawal_id=any (:withdrawal_id) and origin_restaurant_id=:restaurantId ;";
//                $stm = $connexion->prepare($sql);
//                $stm->bindParam("restaurantId", $restaurantId);
//                $stm->bindParam("withdrawal_id", $withdrawals );
//                $stm->execute();
                $sql = "delete from  withdrawal  where  origin_restaurant_id=:restaurantId and date>= :startDate and date <=:endDate  ;";
                $stm = $connexion->prepare($sql);
                $stm->bindParam("restaurantId", $restaurantId);
                $stm->bindParam("startDate", $startDate->format('Y-m-d'));
                $stm->bindParam("endDate", $endDate->format('Y-m-d'));
                $stm->execute();
                $output->writeln(' withdrawals for the ' . $startDate->format('Y-m-d') . ' was successfully deleted');
            }

            /**
             * cashbox management
             */

            //Cashbox management
            $sql = "select count(*) from cashbox_count where origin_restaurant_id=:restaurantId and date>= :startDate and date <= :endDate ;";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("startDate", $startDate->format('Y-m-d'));
            $stm->bindParam("endDate", $endDate->format('Y-m-d'));
            $stm->execute();
            $data['cashbox_total'] = $stm->fetchColumn();

            if ($data['cashbox_total'] == 0) {
                $output->writeln('There is no cashbox to be deleted');
            } else {
                $output->writeln('There is ' . $data['cashbox_total'] . ' cashbox to be deleted');
                $content = file_get_contents('src/AppBundle/General/Command/Query/cashboxDelete.sql');
                $queries = explode(';', $content);

                foreach ($queries as $q) {
                    $stmt = $connexion->prepare($q);
                    $stmt->bindParam("restaurantId", $restaurantId);
                    $stmt->bindParam(":startDate", $startDate->format('Y-m-d'));
                    $stmt->bindParam(":endDate", $endDate->format('Y-m-d'));
                    $stmt->execute();
                    $data['cashbox_total'] = $stmt->fetchColumn();

                }
                $output->writeln('cashboxs are succesufully deleted for ' . $startDate->format(('Y-m-d')));
            }

            // get closure chest id
            $sql = "select id from chest_count where origin_restaurant_id=:restaurantId and closure_date=:date ;";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("date", $startDate->format('Y-m-d'));
            $stm->execute();
            $data['chest_count_id'] = $stm->fetchColumn();
            $output->writeln('chest id is :  ' . $data['chest_count_id']);


            /**
             * Enveloppe management
             **/
            //cad le coffre exist déja

            if (empty($listOfIds)) {
                $output->writeln('There is no chestIds to be used to delete envelopes');
            } else {


                $sql = "SELECT count(*) FROM envelope WHERE origin_restaurant_id=:restaurantId and chest_count_id in ($listOfIds)";
                $stm = $connexion->prepare($sql);
                $stm->bindParam("restaurantId", $restaurantId);
                $stm->execute();
                $data['envelope'] = $stm->fetchColumn();
                $output->writeln('There is ' . $data['envelope'] . ' envelopes to be deleted');
                if ($data['envelope'] == 0) {
                    $output->writeln('There is no envelopes to be deleted');
                } else {

                    $output->writeln($data['envelope'] . ' envelope was found ');
                    $sql = "delete  from envelope WHERE origin_restaurant_id=:restaurantId and chest_count_id in ($listOfIds);";
                    $stm = $connexion->prepare($sql);
                    $stm->bindParam("restaurantId", $restaurantId);
                    //
                    //  $stm->bindParam("chest_count_id", $listOfIds);
                    $stm->execute();
                    $output->writeln($data['envelope'] . ' envelope for the period between ' . $startDate->format('Y-m-d') . ' and ' . $endDate->format('Y-m-d') . ' was successfully deleted');
                }
            }
            // if there is many envelopes that related with just one deposit then we should update envelope set deposit_id null
            //otherwise we cannot delete the deposit we want to.
            $sql = "select count(*) from envelope where deposit_id in (select id from deposit  where  origin_restaurant_id=:restaurantId and  expense_id in (select id from expense  where  origin_restaurant_id=:restaurantId and date_expense>= :startDate and date_expense <= :endDate) )
and origin_restaurant_id=:restaurantId;";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("startDate", $startDate->format('Y-m-d'));
            $stm->bindParam("endDate", $endDate->format('Y-m-d'));
            $stm->execute();
            $data['res'] = $stm->fetchColumn();
            $output->writeln('There is ' . $data['res'] . ' envelope with the same deposit id founded');

            if ($data['res'] == 0) {
                $output->writeln('There is no envelope with the same deposit founded');
                // supprimer les deposits s'il ne sont pas liéé a d'autres enveloppes alors on les
                // supprimer tout sinon on ne supprime pas celui liéé au envelopes
                $sql = "select count(*) from deposit where expense_id in (select id from expense  where  origin_restaurant_id=:restaurantId and date_expense>= :startDate and date_expense <= :endDate) ;";
                $stm = $connexion->prepare($sql);
                $stm->bindParam("restaurantId", $restaurantId);
                $stm->bindParam("startDate", $startDate->format('Y-m-d'));
                $stm->bindParam("endDate", $endDate->format('Y-m-d'));
                $stm->execute();
                $data['deposit'] = $stm->fetchColumn();

                if ($data['deposit'] == 0) {
                    $output->writeln('There is no deposits to be deleted');
                } else {
                    $output->writeln($data['deposit'] . ' deposits was found ');
                    $sql = "delete from deposit where expense_id in (select id from expense where date_expense>= :startDate and date_expense <= :endDate and origin_restaurant_id=:restaurantId);";
                    $stm = $connexion->prepare($sql);
                    $stm->bindParam("restaurantId", $restaurantId);
                    $stm->bindParam("startDate", $startDate->format('Y-m-d'));
                    $stm->bindParam("endDate", $endDate->format('Y-m-d'));
                    $stm->execute();
                    $output->writeln($data['deposit'] . ' deposits for the ' . $startDate->format('Y-m-d') . ' was successfully deleted');
                }
            } else {
                $sql = "delete from envelope where deposit_id in (select id from deposit  where  origin_restaurant_id=:restaurantId and  expense_id in (select id from expense  where  origin_restaurant_id=:restaurantId and date_expense>= :startDate and date_expense <= :endDate) )
and origin_restaurant_id=:restaurantId;";
                $stm = $connexion->prepare($sql);
                $stm->bindParam("restaurantId", $restaurantId);
                $stm->bindParam("startDate", $startDate->format('Y-m-d'));
                $stm->bindParam("endDate", $endDate->format('Y-m-d'));
                $stm->execute();
                $output->writeln('There is ' . $data['deposit_id'] . ' deposit that that will not be deleted because its related to other envelopes');


                $sql = "delete from deposit where origin_restaurant_id=:restaurantId and expense_id in (select id from expense where   origin_restaurant_id=:restaurantId and (date_expense between :startDate and :endDate) or chest_count_id in ($listOfIds)) ;";
                $stm = $connexion->prepare($sql);
                $stm->bindParam("restaurantId", $restaurantId);
                $stm->bindParam("startDate", $startDate->format('Y-m-d'));
                $stm->bindParam("endDate", $endDate->format('Y-m-d'));
                $stm->execute();
                $output->writeln(' deposits for the ' . $startDate->format('Y-m-d') . ' was successfully deleted except deposits with ids ');

            }


            /**
             *
             * Expenses and deposit management
             */

            if (empty($listOfIds)) {
                $output->writeln('There is no chestIds to be used to delete expenses');
            } else {
                //Expenses
                $sql = "select count(*) from expense  where  origin_restaurant_id=:restaurantId and (date_expense between :startDate and :endDate) or chest_count_id in ($listOfIds) ;";
                $stm = $connexion->prepare($sql);
                $stm->bindParam("restaurantId", $restaurantId);
                $stm->bindParam("startDate", $startDate->format('Y-m-d'));
                $stm->bindParam("endDate", $endDate->format('Y-m-d'));
                $stm->execute();
                $data['expense'] = $stm->fetchColumn();

                if ($data['expense'] == 0) {
                    $output->writeln('There is no expenses to be deleted');
                } else {
                    $output->writeln($data['expense'] . ' expense was found ');
                    $sql = "delete  from expense where  origin_restaurant_id=:restaurantId  and (date_expense between :startDate and :endDate);";
                    $stm = $connexion->prepare($sql);
                    $stm->bindParam("restaurantId", $restaurantId);
                    $stm->bindParam("startDate", $startDate->format('Y-m-d'));
                    $stm->bindParam("endDate", $endDate->format('Y-m-d'));
                    $stm->execute();
                    $output->writeln($data['expense'] . ' expenses for the period between' . $startDate->format('Y-m-d') . ' and  ' . $endDate->format('Y-m-d') . ' was successfully deleted');
                }
            }
            /**
             *
             * Recipe ticket management
             */
            $sql = "select count(*) from recipe_ticket  where  origin_restaurant_id=:restaurantId and date>= :startDate and date <= :endDate ;";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("startDate", $startDate->format('Y-m-d'));
            $stm->bindParam("endDate", $endDate->format('Y-m-d'));
            $stm->execute();
            $data['recipe_ticket'] = $stm->fetchColumn();

            if ($data['recipe_ticket'] == 0) {
                $output->writeln('There is no recipe_ticket to be deleted');
            } else {
                $output->writeln($data['recipe_ticket'] . ' recipe_ticket was found ');
                $sql = "delete  from recipe_ticket where date>= :startDate and date <= :endDate and origin_restaurant_id=:restaurantId;";
                $stm = $connexion->prepare($sql);
                $stm->bindParam("restaurantId", $restaurantId);
                $stm->bindParam("startDate", $startDate->format('Y-m-d'));
                $stm->bindParam("endDate", $endDate->format('Y-m-d'));
                $stm->execute();
                $output->writeln($data['recipe_ticket'] . ' recipe_ticket for the ' . $startDate->format('Y-m-d') . ' was successfully deleted');
            }


            /**
             * Chest management
             **/
            //cad le coffre exist déja
            if (!empty($listOfIds)) {
                $sql = "select count(*) from chest_count  where  origin_restaurant_id=:restaurantId and id in ($listOfIds);";
                $stm = $connexion->prepare($sql);
                $stm->bindParam("restaurantId", $restaurantId);
                $stm->execute();
                $data['chest'] = $stm->fetchColumn();

                $output->writeln($data['chest'] . ' chest will be deleted with ids : ' . $listOfIds);
                if ($data['chest'] == 0) {
                    $output->writeln('There is no chest to be deleted');
                } else {
                    $output->writeln($data['chest'] . ' chest was found ');
                    $content = file_get_contents('src/AppBundle/General/Command/Query/chestDelete.sql');
                    $queries = explode(';', $content);
                    foreach ($queries as $q) {
                        $stmt = $connexion->prepare($q);
                        // $stmt->bindParam("restaurantId", $restaurantId);
                        $stmt->bindParam("chest_count_id", $listOfIds);
                        $stmt->execute();
                        $data['chest'] = $stmt->fetchColumn();


                    }

                    $output->writeln($data['chest'] . 'finished ');


                }
            }


        } elseif ($type == 'p') {
            $output->writeln('Purchased management for :' . $startDate->format('Y-m-d') . ' - ' . $endDate->format('Y-m-d'));


            /**
             * Delete sold mvmts
             */
//            $output->writeln('Deleting sold mvmts for ' . $startDate->format('Y-m-d') . ' - ' . $endDate->format('Y-m-d'));
//            $sql = "delete from product_purchased_mvmt where type='sold' and  origin_restaurant_id=:restaurantId and date_time>= :startDate and date_time <= :endDate;";
//            $stmt = $connexion->prepare($sql);
//            $stmt->bindParam("restaurantId", $restaurantId);
//            $stmt->bindParam("startDate", $startDate->format('Y-m-d'));
//            $stmt->bindParam("endDate", $endDate->format('Y-m-d'));
//            $stmt->execute();
//
//            $output->writeln('Sold Mvmts are deleted successfully ');

            /**
             * Delivery management
             */
            $sql = "select count(*) from delivery where  origin_restaurant_id=:restaurantId and date>= :startDate and date <= :endDate ;";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("startDate", $startDate->format('Y-m-d'));
            $stm->bindParam("endDate", $endDate->format('Y-m-d'));
            $stm->execute();
            $data['delivery'] = $stm->fetchColumn();
            $output->writeln('There is ' . $data['delivery'] . ' delivery to be deleted');
            $sql = "select count(*) from product_purchased_mvmt where date_time>=:startDate and date_time <= :endDate and type='delivery' and  origin_restaurant_id=:restaurantId and
 source_id in (select id from delivery_line where delivery_id in ( select id from delivery where  origin_restaurant_id=:restaurantId and date>= :startDate and date <= :endDate));";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("startDate", $startDate->format('Y-m-d'));
            $stm->bindParam("endDate", $endDate->format('Y-m-d'));
            $stm->execute();
            $data['mvmts'] = $stm->fetchColumn();

            $output->writeln('There is ' . $data['mvmts'] . ' mvmts delivery to be deleted');
            
            if ($data['delivery'] == 0) {
                $output->writeln('There is no delivery to be deleted');
            } else {
                $output->writeln($data['mvmts'] . ' mvmts was found ');
                $content = file_get_contents('src/AppBundle/General/Command/Query/deliveryDelete.sql');
                $queries = explode(';', $content);
                foreach ($queries as $q) {
                    $stmt = $connexion->prepare($q);
                    $stmt->bindParam("restaurantId", $restaurantId);
                    $stmt->bindParam("startDate", $startDate->format('Y-m-d'));
                    $stmt->bindParam("endDate", $endDate->format('Y-m-d'));
                    $stmt->execute();
                    $data['delivery'] = $stmt->fetchColumn();


                }
                $output->writeln('Delivery are deleted successfully ' . $data['delivery']);
            }


            /**
             * Orders management
             */
            $sql = "select count(*) from orders where  origin_restaurant_id=:restaurantId and datedelivery>= :startDate and datedelivery <= :endDate  ;";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("startDate", $startDate->format('Y-m-d'));
            $stm->bindParam("endDate", $endDate->format('Y-m-d'));
            $stm->execute();
            $data['orders'] = $stm->fetchColumn();
            $output->writeln('There is ' . $data['orders'] . ' orders to be deleted');

            if ($data['orders'] == 0) {
                $output->writeln('There is no orders to be deleted');
            } else {
                $output->writeln($data['orders'] . ' orders was found ');
                $content = file_get_contents('src/AppBundle/General/Command/Query/orderDelete.sql');
                $queries = explode(';', $content);
                foreach ($queries as $q) {
                    $stmt = $connexion->prepare($q);
                    $stmt->bindParam("restaurantId", $restaurantId);
                    $stmt->bindParam("startDate", $startDate->format('Y-m-d'));
                    $stmt->bindParam("endDate", $endDate->format('Y-m-d'));
                    $stmt->execute();
                    $data['orders'] = $stmt->fetchColumn();


                }
                $output->writeln('Orders are deleted successfully ' . $data['orders']);
            }


            /**
             * Transfert managemet
             */
            $sql = "select count(*) from transfer where  origin_restaurant_id=:restaurantId and date_transfer>= :startDate and date_transfer <= :endDate   ;";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("startDate", $startDate->format('Y-m-d'));
            $stm->bindParam("endDate", $endDate->format('Y-m-d'));
            $stm->execute();
            $data['transfer'] = $stm->fetchColumn();
            $output->writeln('There is ' . $data['transfer'] . ' transfers to be deleted');

            if ($data['transfer'] == 0) {
                $output->writeln('There is no transfer to be deleted');
            } else {
                $output->writeln($data['transfer'] . ' transfers was found ');
                $content = file_get_contents('src/AppBundle/General/Command/Query/transferDelete.sql');
                $queries = explode(';', $content);
                foreach ($queries as $q) {
                    $stmt = $connexion->prepare($q);
                    $stmt->bindParam("restaurantId", $restaurantId);
                    $stmt->bindParam("startDate", $startDate->format('Y-m-d'));
                    $stmt->bindParam("endDate", $endDate->format('Y-m-d'));
                    $stmt->execute();
                    $data['transfer'] = $stmt->fetchColumn();
                }
                $output->writeln('transfers are deleted successfully ' . $data['transfer']);
            }


            /**
             * Returns management
             */
            $sql = "select count(*) from returns where  origin_restaurant_id=:restaurantId and date>= :startDate and date <= :endDate;";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("startDate", $startDate->format('Y-m-d'));
            $stm->bindParam("endDate", $endDate->format('Y-m-d'));
            $stm->execute();
            $data['returns'] = $stm->fetchColumn();
            $output->writeln('There is ' . $data['returns'] . ' returns to be deleted');

            if ($data['returns'] == 0) {
                $output->writeln('There is no returns to be deleted');
            } else {
                $output->writeln($data['returns'] . ' returns was found ');
                $content = file_get_contents('src/AppBundle/General/Command/Query/returnsDelete.sql');
                $queries = explode(';', $content);
                foreach ($queries as $q) {
                    $stmt = $connexion->prepare($q);
                    $stmt->bindParam("restaurantId", $restaurantId);
                    $stmt->bindParam("startDate", $startDate->format('Y-m-d'));
                    $stmt->bindParam("endDate", $endDate->format('Y-m-d'));
                    $stmt->execute();
                    $data['returns'] = $stmt->fetchColumn();


                }
                $output->writeln('returns are deleted successfully ' . $data['returns']);
            }
      

            $output->writeln('Stock management for :' . $startDate->format('Y-m-d') . ' - ' . $endDate->format('Y-m-d'));

            //Stock Management

            /**
             * Loss management
             */

            //Inventory Loss
            $sql = "select count(*) from loss_line where loss_sheet_id in (select id from loss_sheet where   origin_restaurant_id =:restaurantId and entry <=:d2 and entry >=:d1 and type='article')";
            $stm = $connexion->prepare($sql);
            $d1 = $startDate->format('Y-m-d') . ' 00:00:00';
            $d2 = $endDate->format('Y-m-d') . ' 23:59:59';
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("d1", $d1);
            $stm->bindParam("d2", $d2);
            $stm->execute();
            $data['inventoryLoss'] = $stm->fetchColumn();
            $output->writeln('There is ' . $data['inventoryLoss'] . ' inventoryLoss to be deleted');

            if ($data['inventoryLoss'] == 0) {
                $output->writeln('There is no inventoryLoss to be deleted');
            } else {
                $output->writeln($data['inventoryLoss'] . ' inventoryLoss was found ');
                $content = file_get_contents('src/AppBundle/General/Command/Query/inventoryLossDelete.sql');
                $queries = explode(';', $content);
                foreach ($queries as $q) {
                    $stmt = $connexion->prepare($q);
                    $stmt->bindParam("restaurantId", $restaurantId);
                    $stmt->bindParam("d1", $d1);
                    $stmt->bindParam("d2", $d2);
                    $stmt->execute();
                    $data['inventoryLoss'] = $stmt->fetchColumn();


                }
                $output->writeln('InventoryLoss are deleted inventoryLoss ' . $data['inventoryLoss']);
            }


            //Sold Loss
            $sql = "select count(*)  from loss_sheet where   origin_restaurant_id =:restaurantId and entry <=:d2 and entry >=:d1 and type='finalProduct'";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("d1", $d1);
            $stm->bindParam("d2", $d2);
            $stm->execute();
            $data['soldLoss'] = $stm->fetchColumn();
            $output->writeln('There is ' . $data['soldLoss'] . ' soldLossSheet to be deleted');

            if ($data['soldLoss'] == 0) {
                $output->writeln('There is no soldLossSheet to be deleted');
            } else {
                $output->writeln($data['soldLoss'] . ' soldLossSheet was found ');
                $content = file_get_contents('src/AppBundle/General/Command/Query/soldLossDelete.sql');
                $queries = explode(';', $content);
                foreach ($queries as $q) {
                    $stmt = $connexion->prepare($q);
                    $stmt->bindParam("restaurantId", $restaurantId);
                    $stmt->bindParam("d1", $d1);
                    $stmt->bindParam("d2", $d2);
                    $stmt->execute();
                    $data['soldLoss'] = $stmt->fetchColumn();


                }
                $output->writeln('SoldLoss are deleted  ' . $data['soldLoss']);
            }


            /**
             * inventory management
             */
            $sql = "select count(*) from inventory_line where inventory_sheet_id in ( select id from  inventory_sheet where   origin_restaurant_id =:restaurantId and fiscal_date>= :startDate and fiscal_date <= :endDate);";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("startDate", $startDate->format('Y-m-d'));
            $stm->bindParam("endDate", $endDate->format('Y-m-d'));
            $stm->execute();
            $data['inventoryLine'] = $stm->fetchColumn();
            $output->writeln('There is ' . $data['inventoryLine'] . ' inventoryLine to be deleted');

            if ($data['inventoryLine'] == 0) {
                $output->writeln('There is no inventoryLine to be deleted');
            } else {
                $output->writeln($data['inventoryLine'] . ' inventoryLine was found ');
                $content = file_get_contents('src/AppBundle/General/Command/Query/inventoryDelete.sql');
                $queries = explode(';', $content);
                foreach ($queries as $q) {
                    $stmt = $connexion->prepare($q);
                    $stmt->bindParam("restaurantId", $restaurantId);
                    $stmt->bindParam("startDate", $startDate->format('Y-m-d'));
                    $stmt->bindParam("endDate", $endDate->format('Y-m-d'));
                    $stmt->execute();
                    $data['inventoryLine'] = $stmt->fetchColumn();
                }
                $output->writeln('InventoryLine are deleted successfully ' . $data['inventoryLine']);
            }
         //supprissions des mvmts
        

            //delete all inventory mvmts except some of them

            $sql = " delete from product_purchased_mvmt where type='inventory' and  origin_restaurant_id=:restaurantId and date_time>= :startDate and
        date_time <= :endDate and id not in (SELECT DISTINCT ON (product_id)
                id
                FROM   product_purchased_mvmt
                where product_purchased_mvmt.origin_restaurant_id =:restaurantId and deleted = false and type = 'inventory' and date_time <= :endDate and stock_qty is not null    ORDER  BY product_id, date_time DESC, id DESC);";
            $stm = $connexion->prepare($sql);
            $stm->bindParam("restaurantId", $restaurantId);
            $stm->bindParam("startDate", $d1);
            $stm->bindParam("endDate", $d2);
            $stm->execute();

            //les mvmts qu'on va créer


            $output->writeln('get all other mvmt data different to inventory that should be created');
            $content = file_get_contents('src/AppBundle/General/Command/Query/selectMvmts.sql');
            $queries = explode(';', $content);
            $d2 = $endDate->format('Y-m-d') . ' 23:59:59';
            foreach ($queries as $q) {
                $stmt = $connexion->prepare($q);
                $stmt->bindParam("restaurantId", $restaurantId);
                $stmt->bindParam("endDate", $d2);
                $stmt->execute();
                $data['Mvmt'] = $stmt->fetchAll();
            }
            array_shift($data['Mvmt']);
           
// delete all mvmts != inventory
            $output->writeln('delete all mvmts is loading ');
            $content = file_get_contents('src/AppBundle/General/Command/Query/deleteMvmts.sql');
            $queries = explode(';', $content);
            $d1 = $startDate->format('Y-m-d').' 00:00:00';
            $d2 = $endDate->format('Y-m-d').' 23:59:59';
            foreach ($queries as $q) {
                $stmt = $connexion->prepare($q);
                $stmt->bindParam(":restaurantId", $restaurantId);
                $stmt->bindParam(":startDate", $d1);
                $stmt->bindParam(":endDate", $d2);
                $stmt->execute();

            }
            $output->writeln('Mvmts are successfully deleted ');


            //creation des mvmt != inventory ( sum(variation) > date last inventory
            if (sizeof($data['Mvmt']) !== 0 && !is_null($data['Mvmt'][0]['product_id'])) {
                $output->writeln('create mvmts with type is  !=inventory ');
                $content = file_get_contents('src/AppBundle/General/Command/Query/createMvmts.sql');
                $queries = explode(';', $content);
                $d2 = $endDate->format('Y-m-d') . ' 00:00:00';

                foreach ($queries as $q) {
                    $stmt = $connexion->prepare($q);
                    foreach ($data['Mvmt'] as $m) {
                        $stmt->bindParam(":restaurantId", $restaurantId);
                        $stmt->bindParam(":endDate", $d2);
                        $stmt->bindParam(":productId", $m['product_id']);
                        $stmt->bindParam(":variation", $m['variation']);
                        // $stmt->bindParam(":buyingCost", $data['Mvmt'][0]['buying_cost']);
                        $stmt->bindParam(":type", $m['type']);
                        $stmt->bindParam(":inventoryQty", $m['inventory_qty']);
                        $stmt->execute();
                    }


                }

                $output->writeln('Mvmt are successufully created');
            } else {
                $output->writeln('No mvmt should be created');

            }

        } else {
            $output->writeln('Can u verify the option u wrote plz...');
            return;
        }
        //HR Management
        /**
         * we cannot do that
         */
        $progress->advance();


        $progress->finish();
    }

}