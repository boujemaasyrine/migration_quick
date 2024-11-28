<?php


namespace AppBundle\Merchandise\Command;


use AppBundle\Merchandise\Entity\Order;
use Cassandra\Date;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResendRejectedOrderCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName("order:resend:launch")
            ->setDescription("RESEND ALL REJECTED ORDERS");
    }


    public function execute(InputInterface $input, OutputInterface $output)
    {
       $em = $this->getContainer()->get('doctrine.orm.entity_manager');
       $orderService = $this->getContainer()->get('order.service');


        //Recupérer toutes les commandes rejetées
        $orders = $em->getRepository('Merchandise:Order')->getRejectedOrders();
       

        foreach ($orders as $o) {
            echo "status order  ".$o->getId()." ".$o->getStatus()." \n";
            $o->setStatus(Order::SENDING);
        }
        $em->flush();
        foreach ($orders as $o) {
            echo "Send order  ".$o->getId()." \n";
            $orderService->sendOrder($o);
        }
    }


}