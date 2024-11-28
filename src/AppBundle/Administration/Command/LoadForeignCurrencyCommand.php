<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 23/05/2016
 * Time: 18:18
 */

namespace AppBundle\Administration\Command;

use AppBundle\Administration\Entity\Currency;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LoadForeignCurrencyCommand
 */
class LoadForeignCurrencyCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:foreign:currency:import')->setDefinition(
            []
        )->setDescription('Import initial foreign currency.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = $this->getContainer()->getParameter('kernel.root_dir').'/../data/import/';
        $file = fopen($dir.'list_currencies.csv', 'r');
        while ($item = fgets($file)) {
            try {
                $item = explode(',', $item);

                $country = str_replace('"', '', $item[0]);
                $code = str_replace('""', '', $item[2]);

                if ('EUR' !== $code) {
                    $currency = new Currency();
                    $currency->setCountry($country)
                        ->setCode($code);
                    $this->em->persist($currency);
                    $this->em->flush();
                    $output->writeln('Currency '.$code.' created.');
                }
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
            }
        }
    }
}
