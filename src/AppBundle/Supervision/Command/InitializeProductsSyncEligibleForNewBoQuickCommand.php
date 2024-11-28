<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 12:18
 */

namespace AppBundle\Supervision\Command;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitializeProductsSyncEligibleForNewBoQuickCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:sync:eligible:products')->setDefinition(
            []
        )
            ->addArgument('quickCode', InputArgument::OPTIONAL)
            ->setDescription('');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('logger');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->em->getConnection();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $quickCode = $input->getArgument('quickCode');
        if ($quickCode) {
            $restaurants[] = $this->em->getRepository('AppBundle:Restaurant')->findOneBy(['code' => $quickCode]);
        } else {
            $restaurants = $this->em->getRepository('AppBundle:Restaurant')->findAll();
        }

        foreach ($restaurants as $restaurant) {
            if ($restaurant) {
                $statement = $connection->prepare(
                    "delete from product_restaurant where restaurant_id = :rest_id;"
                );
                $statement->bindValue('rest_id', $restaurant->getId());
                $statement->execute();
                $statement = $connection->prepare(
                    "insert into product_restaurant (product_id, restaurant_id)
                  select product.id, :rest_id from product;"
                );
                $statement->bindValue('rest_id', $restaurant->getId());
                $statement->execute();

                echo "Eligibility off all products for ".$restaurant->getName()."\n";
            }
        }
    }
}
