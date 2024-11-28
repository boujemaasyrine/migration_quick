<?php

namespace AppBundle\Supervision\Command;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Supplier;
use Symfony\Bridge\Monolog\Logger;
use AppBundle\Supervision\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\CssSelector\Exception\InternalErrorException;

/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 22/02/2016
 * Time: 10:58
 */
class ImportInventoryItemsCommand extends ContainerAwareCommand
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:inventoryItems:import')->setDefinition(
            []
        )->setDescription('Import all inventory items.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->logger = $this->getContainer()->get('logger');

        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $dataPath = $this->getContainer()->getParameter('kernel.root_dir').'/../data/import/';

        $lines = file($dataPath.'translations/item_inventory.csv');

        $file = fopen($dataPath.'Referentiel_2016_05_30/item_inventory.csv', 'r');
        $old = ini_set('memory_limit', '2500M');
        $header = fgets($file);
        while ($item = fgets($file)) {
            $item = explode(';', $item);
            $idItemInventaire = $item[0];
            $itemName = $item[1];
            $categoryName = $item[2];

            $inventoryQty = $item[6];
            $usageQty = $item[7];
            $buyingCost = $item[8];
            $labelUnitExped = $item[9];
            $labelUnitInventory = $item[10];
            $labelUnitUsage = $item[11];
            $externalId = $item[12];
            $supplierName = $item[13];

            $itemInventory = $em->getRepository('AppBundle:ProductPurchased')->findOneBy(['externalId' => $externalId]);
            if (is_null($itemInventory)) {

                if (is_null($itemNameNl) || $itemNameNl == "#N/A") {
                    $this->logger->addDebug(
                        'Translation not found for item: '.$externalId."\n",
                        ['ImportInventoryItemsCommand']
                    );
                }


                // check existing category
                $category = $em->getRepository(ProductCategories::class)->findOneBy(['name' => $categoryName]);
                if (is_null($category)) {
                    throw new InternalErrorException('Category '.$categoryName.' not found.');
                }
                // check existing supplier
                $supplier = $em->getRepository(Supplier::class)->findOneBy(['name' => $supplierName]);
                if (is_null($supplier)) {
                    throw new InternalErrorException('Supplier '.$supplierName.' not found.');
                }

                $active = !Utilities::startsWith($itemName, '[');
                $itemName = ltrim($itemName, '[');

                // Inserting product
                $itemInventory = new ProductPurchased();
                $itemInventory->setName($itemName);
                if ($itemNameNl) {
                    $itemInventory->addNameTranslation('nl', $itemNameNl);
                }
                $itemInventory->setActive($active);
                $itemInventory->setExternalId($externalId);
                $itemInventory->setProductCategory($category);
                $itemInventory->setLabelUnitExped(Product::$unitsLabel[$labelUnitExped]);
                $itemInventory->setLabelUnitInventory(Product::$unitsLabel[$labelUnitInventory]);
                $itemInventory->setLabelUnitUsage(Product::$unitsLabel[$labelUnitUsage]);
                $itemInventory->setInventoryQty($inventoryQty);
                $itemInventory->setUsageQty($usageQty);
                $itemInventory->setBuyingCost($buyingCost);
                $itemInventory->setIdItemInv($idItemInventaire);
                $itemInventory->setStatus($active ? ProductPurchased::ACTIVE : ProductPurchased::INACTIVE);

                $itemInventory->addSupplier($supplier);

                $em->persist($itemInventory);
                $itemInventory->setGlobalProductID($itemInventory->getId());
                $em->flush();
                $em->clear($itemInventory);

                $output->writeln('Item created: '.$itemName);
            } else {
                $itemInventory->setName($itemName);
                $itemInventory->addNameTranslation('fr', $itemName);

                $itemNameNl = null;
                foreach ($lines as $line) {
                    $line = explode(';', $line);
                    if ($line[0] === $externalId) {
                        $itemNameNl = $line[2];
                    }
                }
                if ($itemNameNl) {
                    $itemInventory->addNameTranslation('nl', $itemNameNl);
                }
                $em->flush();
            }
            $output->writeln('Process item inventory: '.$itemInventory->getName());
        }
        $output->writeln('Process completed with success !');
    }
}
