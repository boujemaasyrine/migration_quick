<?php

namespace AppBundle\General\Command;

use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\Division;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Entity\UnitNeedProducts;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 22/02/2016
 * Time: 10:58
 */
class ImportDivisionsCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:divisions:import')->setDefinition(
            []
        )->setDescription('Import all product sold, receipes and division.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $dataPath = $this->getContainer()->getParameter('kernel.root_dir').'/../data/import/product/';
        $file = fopen($dataPath.'divisions.csv', 'r');
        $header = fgets($file);
        while ($item = fgets($file)) {
            try {
                $item = explode(';', $item);

                $externalId = $item[0];
                $divisionName = $item[1];
                $divisionNameNL = $item[6];
                $taxLetter = $item[2];
                $tva = $item[3];
                $specialTaxLetter = $item[4];
                $specialTva = $item[5];

                $division = new Division();
                $division->setExternalId($externalId)
                    ->setName($divisionName)
                    ->setTaxLetter($taxLetter)
                    ->setTva($tva)
                    ->setSpecialTaxLetter($specialTaxLetter)
                    ->setSpecialTva($specialTva)
                    ->addNameTranslation('nl', $divisionNameNL);
                $em->persist($division);
                $em->flush();
                $output->writeln('Division '.$item[1].' created.');
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
            }
        }
        $output->writeln('Import divisions completed !');
    }
}
