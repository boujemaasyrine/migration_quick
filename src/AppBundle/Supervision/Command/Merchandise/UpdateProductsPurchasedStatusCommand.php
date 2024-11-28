<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/02/2016
 * Time: 14:44
 */

namespace AppBundle\Supervision\Command\Merchandise;

use AppBundle\Merchandise\Entity\ProductPurchased;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateProductsPurchasedStatusCommand extends ContainerAwareCommand
{

    /**
     * @var Logger
     */
    private $logger;

    protected function configure()
    {
        $this
            ->setName("update:products:purchased:status")
            ->setDescription("Update products purchased status.");
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

        $products = $em->getRepository('AppBundle:ProductPurchased')
            ->findBy(
                [
                    'status' => ProductPurchased::TO_INACTIVE,
                    'deactivationDate' => new \DateTime('today'),
                ]
            );
        $this->logger->addInfo(
            count($products).' Product purchased, found. That need to be desactivated today.',
            ['UpdateProductsPurchasedStatusCommand']
        );

        if (count($products)) {
            foreach ($products as $product) {
                /**
                 * @var ProductPurchased $product
                 */
                $product->setStatus(ProductPurchased::INACTIVE);
            }
            $em->flush();
        }

        $this->logger->addInfo(
            'UpdateProductsPurchasedStatusCommand executed with success on '.count($products),
            ['UpdateProductsPurchasedStatusCommand']
        );
    }
}
