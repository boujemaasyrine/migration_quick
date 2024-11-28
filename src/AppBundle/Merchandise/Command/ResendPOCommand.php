<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/02/2016
 * Time: 15:36
 */

namespace AppBundle\Merchandise\Command;

use AppBundle\Merchandise\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResendPOCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName("order:sending:launch")
            ->setDescription("RESEND ALL UNSENDED ORDERS");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $orderService = $this->getContainer()->get('order.service');

        //Recupérer toutes les commandes non envoyées
        $orders = $em->getRepository("Merchandise:Order")->findBy(
            array(
                'status' => Order::SENDING,
            )
        );

        foreach ($orders as $o) {
            echo "Send order  ".$o->getId()." \n";

            $orderService->sendOrder($o);
        }
    }
}
