<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 23/06/2016
 * Time: 14:14
 */

namespace AppBundle\Supervision\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NotifyMissingPluCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:notify:missing:plu')->setDefinition(
            []
        )->setDescription('Notify Coordination missing plu\'S.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $missingPluS = $this->em->getRepository('AppBundle:Administration\MissingPlu')->findBy(
            [
                'notified' => false,
            ]
        );

        if (count($missingPluS) > 0) {
            $result = $this->getContainer()->get('notification.service')->notifyByMailMissingPlu($missingPluS);
            echo $result['pluS']." Missing Plu'S in ".$result['restaurants']." restaurants";
            $this->getContainer()->get('logger')->addInfo(
                $result['pluS']." missing PluS in ".$result['restaurants']." restaurants",
                [['MissingPluNotification', 'WsBoAPI']]
            );
        } else {
            echo "No missed PluS";
            $this->getContainer()->get('logger')->addInfo("No missed PluS", ['MissingPluNotification', 'WsBoAPI']);
        }
    }
}
