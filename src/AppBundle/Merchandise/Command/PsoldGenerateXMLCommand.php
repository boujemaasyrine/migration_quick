<?php
/**
 * Created by PhpStorm.
 * User: akarchoud
 * Date: 09/11/2017
 * Time: 10:36
 */

namespace AppBundle\Merchandise\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PsoldGenerateXMLCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('quick:productSold:xml')
            // the short description shown while running "php bin/console list"
            ->setDescription('generate XML for products Sold')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('generate XML for products Sold');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('product.sold.service')->generateProductSoldXML();
    }
}
