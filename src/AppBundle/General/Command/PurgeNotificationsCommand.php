<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 12/02/2018
 * Time: 11:33
 */

namespace AppBundle\General\Command;


use AppBundle\General\Entity\Notification;
use AppBundle\General\Entity\NotificationInstance;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PurgeNotificationsCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    protected function configure()
    {
        $this->setName('saas:purge:notifications')->setDefinition(
            []
        )->setDescription('Purge notifications.');
    }

    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ) {
        parent::initialize(
            $input,
            $output
        );

        $this->em=$this->getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        echo "deleting notification instances"."\n";

        $q=$this->em->createQuery('delete from AppBundle\General\Entity\NotificationInstance ni');
        $niDeleted = $q->execute();

        echo "notification instances deleted"."\n";


        echo "deleting notifications"."\n";
        
        $q=$this->em->createQuery('delete from AppBundle\General\Entity\Notification n');
        $nDeleted = $q->execute();

        echo "notifications deleted";


    }


}