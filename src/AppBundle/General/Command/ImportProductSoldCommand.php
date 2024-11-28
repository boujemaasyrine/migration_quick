<?php

namespace AppBundle\General\Command;

use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\Division;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Entity\UnitNeedProducts;
use AppBundle\ToolBox\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\CssSelector\Exception\InternalErrorException;

/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 22/02/2016
 * Time: 10:58
 */
class ImportProductSoldCommand extends ContainerAwareCommand
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
        $this->setName('quick:productSold:import')
            ->addArgument('filename', InputArgument::OPTIONAL)
            ->setDescription('Import all product sold, receipes and division.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dataDir = $this->getContainer()->getParameter(
            'kernel.root_dir'
        )."/../data/import/Referentiel_2016_05_30/";
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasArgument('filename') && trim($input->getArgument('filename')) != '') {
            $filename = $input->getArgument('filename');
        } else {
            $filename = $this->dataDir.'item_sold.csv';
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
        $dataPath = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/";
        $lines = file($dataPath.'translations/product_sold.csv');

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $divisions = $this->em->getRepository('Merchandise:Division')->findAll();

        $divisions = [];
        foreach ($divisions as $d) {
            /**
             * @var Division $d
             */
            $divisions[$d->getId()] = $d;
        }

        $header = fgets($file);
        while ($line = fgetcsv($file, null, ';')) {
            // DivisionID;
            $divisionId = $line[0];
            //Item Name;
            $productName = $line[1];
            //Id item inv
            $itemId = $line[2];
            // ID de Recette;
            $recetteId = $line[3];
            // ID Item Inv si pas recette;
            $itemInvId = $line[4];
            // Code PLU;
            $codePLU = $line[5];

            $output->writeln("Procced ".implode('/', $line));
            $division = null;
            if (trim($divisionId) != '' && array_key_exists($divisionId, $divisions)) {
                $division = $divisions[$divisionId];
            }
            $productPurchased = null;
            if (trim($recetteId) != '' && strtoupper(trim($recetteId)) != 'NULL') {
                $type = ProductSold::TRANSFORMED_PRODUCT;
            } elseif (trim($itemInvId) != '' && strtoupper(trim($itemInvId)) != 'NULL') {
                $type = ProductSold::NON_TRANSFORMED_PRODUCT;
                $productPurchased = $this->em->getRepository('Merchandise:ProductPurchased')->findOneBy(
                    [
                        "idItemInv" => $itemInvId,
                    ]
                );
            } else {
                continue;
            }
            $active = !Utilities::startsWith($productName, '[');
            $productName = ltrim($productName, '[');
            $productName = str_replace("'", "''", $productName);
            $productName = utf8_encode($productName);
            $checkExistingProductSold = $this->em->getRepository('Merchandise:ProductSold')->findOneBy(
                [
                    'name' => $productName,
                ]
            );

            if (is_null($checkExistingProductSold)) {
                $itemNameNl = null;
                foreach ($lines as $line) {
                    $line = explode(';', $line);
                    if ($line[1] == $codePLU && $line[2] == $productName) {
                        $itemNameNl = $line[3];
                    }
                }

                if (is_null($itemNameNl) || $itemNameNl == "#N/A") {
                    echo 'Translation not found for code: '.$codePLU."\n";
                }

                $codePLU = utf8_encode($codePLU);

                $productSold = new ProductSold();
                $productSold
                    ->setDivision($division)
                    ->setType($type)
                    ->setCodePlu($codePLU)
                    ->setProductPurchased($productPurchased)
                    ->setName($productName)
                    ->addNameTranslation('nl', $itemNameNl)
                    ->setActive($active);
                if ($itemNameNl) {
                    $productSold->addNameTranslation('nl', $itemNameNl);
                }

                $this->em->persist($productSold);
                $productSold->setGlobalProductID($productSold->getId());
                $this->em->flush();

                if ($type == ProductSold::TRANSFORMED_PRODUCT) {
                    $recipe = $this->em->getRepository('Merchandise:Recipe')->findOneBy(
                        [
                            'externalId' => $recetteId,
                        ]
                    );
                    if (is_null($recipe)) {
                        $output->writeln("Cannot find recipe $recetteId \n");
                        continue;
                    } else {
                        if ($recipe->getProductSold()) {
                            $newRecipe = clone $recipe;
                            $newRecipe->setProductSold($productSold);
                            $this->em->persist($newRecipe);
                        } else {
                            $recipe->setProductSold($productSold);
                        }
                        $this->em->flush();
                        $this->em->clear($recipe);
                    }
                } else {
                    continue;
                }
                $this->em->clear($productSold);
            } else {
                $checkExistingProductSold
                    ->setDivision($division)
                    ->setType($type)
                    ->setCodePlu($codePLU)
                    ->setProductPurchased($productPurchased)
                    ->setName($productName)
                    ->setActive($active);
                $this->em->flush();
                $this->em->clear($checkExistingProductSold);
            }
        }
    }
}
