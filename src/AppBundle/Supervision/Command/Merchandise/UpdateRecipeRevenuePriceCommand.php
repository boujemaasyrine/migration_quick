<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/02/2016
 * Time: 14:44
 */

namespace AppBundle\Supervision\Command\Merchandise;

use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRecipeRevenuePriceCommand extends ContainerAwareCommand
{

    /**
     * @var Logger
     */
    private $logger;

    protected function configure()
    {
        $this
            ->setName("update:recipe:revenue:price")
            ->setDescription("Update recipe revenue price.");
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->logger = $this->getContainer()->get('logger');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $productSolds = $em->getRepository('AppBundle:ProductSold')->findAll();

        $x = 0;
        foreach ($productSolds as $productSold) {
            foreach ($productSold->getRecipes() as $recipe) {
                $x++;
                $recipe->setRevenu();
                $output->writeln("Process Recipe:".$recipe->getRevenuePrice());
            }
        }
        $em->flush();
        $this->logger->addInfo(
            'UpdateRecipeRevenuePriceCommand executed with success on '.$x,
            ['UpdateRecipeRevenuePriceCommand']
        );
    }
}
