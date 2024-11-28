<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 01/06/2016
 * Time: 10:14
 */

namespace AppBundle\Merchandise\Command;

use AppBundle\General\Entity\Notification;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PreviousLossNotEncodedCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;

    protected function configure()
    {
        $this
            ->setName("previous:loss:missing")
            ->setDescription("Notify when there is no losses declared yesterday");
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "Launching Previous Loss Not Encoded notification \n ";
        $this->getContainer()->get('logger')->addInfo(
            "Launching Previous Loss Not Encoded notification \n ",
            ["Notifications"]
        );
        $date = (new \DateTime('yesterday'))->setTime(6, 0, 0);
        $yesterdayInventorySheet = $this->em->getRepository('Merchandise:LossSheet')->findOneBy(
            [
                'entryDate' => $date,
                'type' => LossSheet::ARTICLE,
            ]
        );
        if (!$yesterdayInventorySheet) {
            $this->getContainer()->get('notification.service')->generatePreviousLossNotification(
                LossSheet::ARTICLE,
                $date,
                Notification::PREVIOUS_INVENTORY_LOSS_NOTIFICATION,
                SheetModel::ARTICLES_LOSS_MODEL
            );
        }

        $yesterdaySoldSheet = $this->em->getRepository('Merchandise:LossSheet')->findOneBy(
            [
                'entryDate' => $date,
                'type' => LossSheet::FINALPRODUCT,
            ]
        );
        if (!$yesterdaySoldSheet) {
            $this->getContainer()->get('notification.service')->generatePreviousLossNotification(
                LossSheet::FINALPRODUCT,
                $date,
                Notification::PREVIOUS_SOLD_LOSS_NOTIFICATION,
                SheetModel::PRODUCT_SOLD_LOSS_MODEL
            );
        }
    }
}
