<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 14/06/2016
 * Time: 10:15
 */

namespace AppBundle\General\Command;

use AppBundle\Financial\Service\RevenuePricesService;
use AppBundle\Merchandise\Entity\LossLine;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitPricesRevenuesForLossLinesCommand extends ContainerAwareCommand
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
        $this->setName('quick:init:prices:revenues:lossLines')->setDefinition(
            []
        )->setDescription('Initialize user role.');
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
        echo "Initializing Prices Revenues For Loss Lines  \n";

        $step = 5;
        $exist = true;

        $i = 0;
        while ($exist) {
            echo "i = $i \n";
            $lossLines = $this->em->getRepository("Merchandise:LossLine")
                ->findBy(['totalRevenuePrice' => null], ['id' => 'asc'], $step, $i * $step);
            echo "Getting ".count($lossLines)." Loss lines \n";
            if (count($lossLines) > 0) {
                foreach ($lossLines as $ll) {
                    if (is_null($ll->getTotalRevenuePrice())) {
                        echo $ll->getId()." \n";
                        $ll->calculateLossTotalRevenue();
                        $ll->getLossSheet()->setSynchronized(false);
                        $this->em->flush();
                    }
                }
            } else {
                $exist = false;
            }
            $i++;
            $this->em->clear();
        }

        echo "Finish Initializing Prices Revenues For Loss Lines  \n";
    }
}
