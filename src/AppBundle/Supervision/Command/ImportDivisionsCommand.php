<?php

namespace AppBundle\Supervision\Command;

use AppBundle\Merchandise\Entity\Division;
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
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $dataPath = $this->getContainer()->getParameter('kernel.root_dir').'/../data/import/Referentiel_2016_05_30/';
        $file = fopen($dataPath.'divisions.csv', 'r');
        $header = fgets($file);
        while ($item = fgets($file)) {
            try {
                $item = explode(';', $item);

                $externalId = $item[0];
                $divisionName = $item[1];
                $divisionNameNl = $item[8];
                $taxLetter = $item[3];
                $tva = $item[4];
                $specialTaxLetter = $item[6];
                $specialTva = $item[7];

                $division = $em->getRepository('AppBundle:Division')->findOneBy(['externalId' => $externalId]);
                if (is_null($division)) {
                    $division = new Division();
                    $division->setExternalId($externalId)
                        ->setName($divisionName)
                        ->setTaxLetter($taxLetter)
                        ->setTva($tva)
                        ->setSpecialTaxLetter($specialTaxLetter)
                        ->setSpecialTva($specialTva)
                        ->addNameTranslation('nl', $divisionNameNl);
                    $em->persist($division);
                    $em->flush();
                    $output->writeln('Division '.$item[1].' created.');
                }
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
            }
        }
        $output->writeln('Import divisions completed !');
    }
}
