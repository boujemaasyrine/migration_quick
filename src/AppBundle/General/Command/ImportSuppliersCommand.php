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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Doctrine\ORM\EntityManager;

/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 22/02/2016
 * Time: 10:58
 */
class ImportSuppliersCommand extends ContainerAwareCommand
{

    private $dataDir;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:suppliers:import')
            ->addArgument('filename', InputArgument::OPTIONAL)
            ->setDescription('Import Suppliers');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/";
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "Importing Suppliers \n";

        if ($input->hasArgument('filename') && trim($input->getArgument('filename')) != '') {
            $filename = $input->getArgument('filename');
        } else {
            $filename = $this->dataDir.'suppliers.csv';
        }

        if (!file_exists($filename)) {
            echo $filename." is not existing ! \n";

            return;
        }

        $file = fopen($filename, 'r');

        if (!$file) {
            echo "Cannot open file $filename \n";

            return;
        }

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        while ($line = fgetcsv($file, null, ';')) {
            echo "Procees Supplier  ".$line[0]."\n";

            $supplier = $this->em->getRepository("Merchandise:Supplier")->findOneBy(
                array(
                    'name' => $line[0],
                )
            );

            if (!$supplier) {
                echo "Supplier doesn't exist ".$line[0]."\n";
                $supplier = new Supplier();
                $this->em->persist($supplier);
            }

            $supplier
                ->setActive(true)
                ->setName($line[0])
                ->setCode($line[1])
                ->setZone($line[2]);

            $this->em->flush();

            echo "=====\n";
        }

        echo "=== End Importing Suppliers ==== \n";
    }
}
