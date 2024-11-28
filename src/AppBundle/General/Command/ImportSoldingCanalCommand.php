<?php

namespace AppBundle\General\Command;

use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\Division;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Entity\UnitNeedProducts;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\Debug\Exception\FatalErrorException;

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
        $this->setName('quick:soldingCanal:import')->setDefinition(
            []
        )
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
            ["label" => "pos", "type" => SoldingCanal::ORIGIN, 'default' => false, 'wyndMappingColumn' => 'POS'],
            ["label" => "kiosk", "type" => SoldingCanal::ORIGIN, 'default' => false, 'wyndMappingColumn' => 'KIOSK'],
            [
                "label" => "pos_drive",
                "type" => SoldingCanal::ORIGIN,
                'default' => false,
                'wyndMappingColumn' => 'DriveThru',
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
                'wyndMappingColumn' => 'EatIn',
            ],
            [
                "label" => "takeaway",
                "type" => SoldingCanal::DESTINATION,
                'default' => false,
                'wyndMappingColumn' => 'TakeOut',
            ],
            [
                "label" => "drive",
                "type" => SoldingCanal::DESTINATION,
                'default' => false,
                'wyndMappingColumn' => 'DriveThru',
            ],
        ];


        foreach ($data as $item) {
            $oldCanal = $this->em->getRepository('Merchandise:SoldingCanal')->findOneBy(['label' => $item['label']]);
            if (is_null($oldCanal)) {
                $newCanal = new SoldingCanal();
                $newCanal->setLabel($item['label'])
                    ->setWyndMppingColumn($item['wyndMappingColumn'])
                    ->setType($item['type'])
                    ->setDefault($item['default']);
                $this->em->persist($newCanal);
            } else {
                $oldCanal->setDefault($item['default']);
            }
        }
        $this->em->flush();

        $output->writeln('Import solding canal completed !');
    }
}
