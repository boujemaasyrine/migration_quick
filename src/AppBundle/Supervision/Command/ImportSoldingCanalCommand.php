<?php

namespace AppBundle\Supervision\Command;

use AppBundle\Merchandise\Entity\SoldingCanal;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 22/02/2016
 * Time: 10:58
 */
class ImportSoldingCanalCommand extends ContainerAwareCommand
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
        $this->setName('quick:soldingCanal:import')
            ->setDefinition([])
            ->setDescription('Import all solding canal.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $data = [
            ["label" => "pos", "type" => SoldingCanal::ORIGIN, 'default' => false, 'wyndMappingColumn' => 'pos'],
            [
                "label" => "website",
                "type" => SoldingCanal::ORIGIN,
                'default' => false,
                'wyndMappingColumn' => 'website',
            ],
            ["label" => "borne", "type" => SoldingCanal::ORIGIN, 'default' => false, 'wyndMappingColumn' => 'borne'],
            [
                "label" => "application",
                "type" => SoldingCanal::ORIGIN,
                'default' => false,
                'wyndMappingColumn' => 'application',
            ],
            [
                "label" => "pos_tablet",
                "type" => SoldingCanal::ORIGIN,
                'default' => false,
                'wyndMappingColumn' => 'tablet',
            ],
            [
                "label" => "pos_drive",
                "type" => SoldingCanal::ORIGIN,
                'default' => false,
                'wyndMappingColumn' => 'drive',
            ],

            [
                "label" => "allcanals",
                "type" => SoldingCanal::DESTINATION,
                'default' => true,
                'wyndMappingColumn' => 'allcanals',
            ],
            [
                "label" => "onsite",
                "type" => SoldingCanal::DESTINATION,
                'default' => false,
                'wyndMappingColumn' => 'Sur Place',
            ],
            [
                "label" => "takeaway",
                "type" => SoldingCanal::DESTINATION,
                'default' => false,
                'wyndMappingColumn' => 'A emporter',
            ],
            [
                "label" => "drive",
                "type" => SoldingCanal::DESTINATION,
                'default' => false,
                'wyndMappingColumn' => 'Drive',
            ],
            [
                "label" => "delivery",
                "type" => SoldingCanal::DESTINATION,
                'default' => false,
                'wyndMappingColumn' => 'Livraison',
            ],
            ["label" => "walk", "type" => SoldingCanal::DESTINATION, 'default' => false, 'wyndMappingColumn' => 'Walk'],
        ];

        foreach ($data as $item) {
            $oldCanal = $this->em->getRepository('AppBundle:SoldingCanal')->findOneBy(['label' => $item['label']]);
            if (is_null($oldCanal)) {
                $newCanal = new SoldingCanal();
                $newCanal->setLabel($item['label'])
                    ->setWyndMppingColumn($item['wyndMappingColumn'])
                    ->setType($item['type'])
                    ->setDefault($item['default']);
                $this->em->persist($newCanal);
                $newCanal->setGlobalId($newCanal->getId());
            } else {
                $oldCanal->setDefault($item['default']);
                if ($oldCanal->getGlobalId() == null) {
                    $oldCanal->setGlobalId($oldCanal->getId());
                }
            }
        }
        $this->em->flush();

        $output->writeln('Import solding canal completed !');
    }
}
