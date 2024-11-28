<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 19/05/2016
 * Time: 14:32
 */

namespace AppBundle\Merchandise\Command;

use AppBundle\General\Entity\Notification;
use AppBundle\Merchandise\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NotifyNotDeliveredOrdersCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName("order:not:delivered:notify")
            ->setDescription("Notify managers for order not delivered");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $yesterday = new \DateTime('now');
        $yesterday->sub(new \DateInterval('P1D'));
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $orders = $em->getRepository("Merchandise:Order")->findBy(
            array(
                'dateDelivery' => $yesterday,
                'status' => [Order::SENDED, Order::MODIFIED],
            )
        );

        echo count($orders)." not sended \n";

        foreach ($orders as $o) {
            echo "Order #".$o->getNumOrder()." \n";
            $this->getContainer()->get('notification.service')->addNotificationByUsers(
                Notification::NOT_DELIVERED_ORDER_NOTIFICATION,
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
