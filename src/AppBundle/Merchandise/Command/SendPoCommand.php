<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/02/2016
 * Time: 14:44
 */

namespace AppBundle\Merchandise\Command;

use AppBundle\Merchandise\Entity\Order;
use AppBundle\ToolBox\Utils\Utilities;
use AppBundle\General\Entity\Notification;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendPoCommand extends ContainerAwareCommand
{

    private $ftpHost;
    private $ftpUser;
    private $ftpPw;
    private $ftpPort;

    /**
     * @var Logger $logger
     */
    private $logger;

    protected function configure()
    {
        $this
            ->setName("order:send")
            ->setDescription("SEND AN ORDER")
            ->addArgument("order", InputArgument::REQUIRED, "Order to be sent");
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->ftpHost = $this->getContainer()->getParameter('ftp_host');
        $this->ftpUser = $this->getContainer()->getParameter('ftp_user');
        $this->ftpPw = $this->getContainer()->getParameter('ftp_pw');
        $this->ftpPort = $this->getContainer()->getParameter('ftp_port');
        $this->logger = $this->getContainer()->get('logger');
        parent::initialize($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $idOrder = $input->getArgument('order');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $order = $em->getRepository("Merchandise:Order")->find($idOrder);

        //Si Y a pa d'ordre, on quit
        if ($order === null) {
            $this->logger->addDebug('Pas d\'ordre pour '.$idOrder, ['SendPoCommand']);

            return;
        }

        //Order avec num order est null
        if ($order->getNumOrder() === null) {
            $this->logger->addDebug("Pas de num ordre pour ".$idOrder, ['SendPoCommand']);
            echo "Pas de num ordre pour ".$idOrder."\n";

            return;
        }

        $this->getContainer()->get('order.service')->generatePoXml($order);

        //Test s'il y a un fichier généré pour cet order
        $filename = $this->getContainer()->getParameter("po_directory")."/PO".$order->getNumOrder().".xml";
        // Il n'existe pas de fichier
        if (!file_exists($filename)) {
            $this->logger->addDebug("Fichier inexistant ".$filename." ".$idOrder, ['SendPoCommand']);
            echo "Fichier inexistant ".$filename." ".$idOrder."\n";

            return;
        }

        $conId = ftp_connect($this->ftpHost, $this->ftpPort);

        if (!$conId) {
            $this->logger->addDebug("Failed to connect !", ['SendPoCommand']);
            echo "Failed to connect !\n";
            $order->setStatus(Order::REJECTED);
        } else {//Connexion établie
            try{
            $login = ftp_login($conId, $this->ftpUser, $this->ftpPw);
            if (!$login) {
                $this->logger->addDebug("Failed to login ! $login ! with connection $conId", ['SendPoCommand']);
                echo "Failed to login !\n";
                $order->setStatus(Order::REJECTED);

            } else {//Connexion reussite
                //Turning passive mode
                ftp_pasv($conId, true);

                $tries = 1;
                $sended = false;
                try {
                    while (!$sended && $tries < 3) {
                        $this->logger->addDebug("Try $tries", ['SendPoCommand']);
                        echo "Try $tries \n ";
                        if (ftp_put($conId, "PO".$order->getNumOrder().".xml", $filename, FTP_ASCII)) {
                            $this->logger->addDebug("Le fichier $filename a été chargé avec succès", ['SendPoCommand']);
                            echo "Le fichier $filename a été chargé avec succès\n";
                            $sended = true;
                        } else {
                            $this->logger->addDebug(
                                "Il y a eu un problème lors du chargement du fichier $filename",
                                ['SendPoCommand']
                            );
                            echo "Il y a eu un problème lors du chargement du fichier $filename\n";
                        }

                        sleep(2);
                        $tries++;
                    }
                } catch (\Exception $e) {
                    $order->setStatus(Order::REJECTED);
                }

                // Fermeture de la connexion
                ftp_close($conId);

                if ($sended) {
                    $this->logger->addDebug("Commande ".$idOrder." envoyé", ['SendPoCommand']);
                    echo "Commande ".$idOrder." envoyé \n";
                    $order->setStatus(Order::SENDED);
                } else {
                    $this->logger->addDebug("Commande ".$idOrder." non envoyé ", ['SendPoCommand']);
                    echo "Commande ".$idOrder." non envoyé \n";
                    $order->setStatus(Order::REJECTED);
                }
            }//End Cnx reusssite
            }catch (\Exception $e) {
                $this->logger->addDebug("Error login",$e );
            }
        }

        $em->flush();

        if ($order->getStatus() == Order::REJECTED) {
            $this->logger->addDebug("Order Rejected => notfying restaurant", ['SendPoCommand']);
            echo "Order Rejected => notfying restaurant \n";
            $this->getContainer()->get('order.service')->notifyByMailRejectedOrder($order);
            $this->getContainer()->get('notification.service')->addNotificationByUsers(
                Notification::REJECTED_ORDER_NOTIFICATION,
                [
                    'orderId' => $order->getId(),
                    'orderNum' => $order->getNumOrder(),
                    'orderDate' => $order->getDateOrder()->format('d-m-Y'),
                    'modalId' => $order->getId(),
                ],
                Notification::LIST_PENDINGS_COMMANDS_PATH
            );
        }
    }
}
