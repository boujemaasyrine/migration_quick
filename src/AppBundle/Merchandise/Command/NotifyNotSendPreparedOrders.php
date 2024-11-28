<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/03/2016
 * Time: 15:26
 */

namespace AppBundle\Merchandise\Command;

use AppBundle\General\Entity\Notification;
use AppBundle\Merchandise\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NotifyNotSendPreparedOrders extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName("order:prepared:notify")
            ->setDescription("Notify managers by an unsended order");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $today = new \DateTime('now');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $orders = $em->getRepository("Merchandise:Order")->findBy(
            array(
                'dateOrder' => $today,
                'status' => Order::DRAFT,
            )
        );

        echo count($orders)." not sended \n";

        foreach ($orders as $o) {
            echo "Order #".$o->getNumOrder()." \n";
            $this->getContainer()->get('order.service')->notifyManagerByUnsendedPreparedOrder($o);
            $this->getContainer()->get('notification.service')->addNotificationByUsers(
                Notification::PREPARED_NOT_SEND_ORDER_NOTIFICATION,
                [
                    'orderId' => $o->getId(),
                    'orderNum' => $o->getNumOrder(),
                    'modalId' => $o->getId(),
                ],
                Notification::LIST_PENDINGS_COMMANDS_PATH
            );
        }
    }
}
