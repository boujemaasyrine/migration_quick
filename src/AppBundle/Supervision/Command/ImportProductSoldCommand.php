<?php

namespace AppBundle\Supervision\Command;

use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Division;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Supervision\Utils\Utilities;

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
     * @var Logger
     */
    private $logger;

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
        $this->logger = $this->getContainer()->get('logger');

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

        $wynd = file($dataPath.'liste_plu_16062016.csv');
        $first = true;
        foreach ($wynd as $line) {
            if ($first) {
                $first = false;
                continue;
            }
            $line = explode(';', $line);
            if ($line[4] && strpos(
                strtoupper($line[2]),
                'STUD'
            ) === false && $line[4] != "M000" && $line[4] != "P000") {
                $wyndPLUs[$line[4]] = $line[2];
            }
        }

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $divisionsRaw = $this->em->getRepository('AppBundle:Division')->findAll();
        $divisions = [];
        foreach ($divisionsRaw as $d) {
            /**
             * @var Division $d
             */
            $divisions[$d->getExternalId()] = $d;
        }
        $x = $y = $z = $s = $t = 0;
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
            $t++;
            if (isset($wyndPLUs[$codePLU]) && strpos($productName, 'STUD') === false && strpos(
                $codePLU,
                'M000'
            ) === false && strpos($codePLU, 'P000') === false) {
                $x++;
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
                    $productPurchased = $this->em->getRepository('AppBundle:ProductPurchased')->findOneBy(
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

                $itemNameNl = null;
                foreach ($lines as $line) {
                    $line = explode(';', $line);
                    if ($line[1] == $codePLU && $line[2] == $productName) {
                        $itemNameNl = $line[3];
                    }
                }

                if (is_null($itemNameNl) || $itemNameNl == "#N/A") {
                    $this->logger->addDebug(
                        'Translation not found for item: '.$itemId."\n",
                        ['ImportProductSoldCommand']
                    );
                }

                $codePLU = utf8_encode($codePLU);
                $productSold = $this->em->getRepository('AppBundle:ProductSold')->findOneBy(
                    [
                        'codePlu' => $codePLU,
                    ]
                );
                if (is_null($productSold) || strtoupper($productSold->getName()) != strtoupper($wyndPLUs[$codePLU])
                ) {
                    if (is_null($productSold)) {
                        $z++;
                        $productSold = new ProductSold();
                        $productSold
                            ->setDivision($division)
                            ->setExternalId($itemId)
                            ->setType($type)
                            ->setCodePlu($codePLU)
                            ->setProductPurchased($productPurchased)
                            ->setName($productName)
                            ->setActive($active)
                            ->addNameTranslation('nl', $itemNameNl);

                        $this->em->persist($productSold);
                        $productSold->setGlobalProductID($productSold->getId());
                        $this->em->flush();

                        if ($type == ProductSold::TRANSFORMED_PRODUCT) {
                            $recipe = $this->em->getRepository('AppBundle:Recipe')->findOneBy(
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
                                    $newRecipe->setRevenu();
                                    $this->em->persist($newRecipe);
                                } else {
                                    $recipe->setProductSold($productSold);
                                    $recipe->setRevenu();
                                }
                                $this->em->flush();
                                $this->em->clear($recipe);
                            }
                        }
                    } else {
                        if (strtoupper($productName) == strtoupper($wyndPLUs[$codePLU]) || strlen(
                            $productSold->getName()
                        ) > strlen($wyndPLUs[$codePLU])) {
                            $s++;
                            $productSold
                                ->setDivision($division)
                                ->setExternalId($itemId)
                                ->setType($type)
                                ->setCodePlu($codePLU)
                                ->setProductPurchased($productPurchased)
                                ->setName($productName)
                                ->setActive($active)
                                ->addNameTranslation('fr', $productName)
                                ->addNameTranslation('nl', $itemNameNl);
                            if ($type == ProductSold::TRANSFORMED_PRODUCT) {
                                foreach ($productSold->getRecipes() as $recipe) {
                                    $productSold->removeRecipe($recipe);
                                }
                                $this->em->flush();

                                $recipe = $this->em->getRepository('AppBundle:Recipe')->findOneBy(
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
                                        $newRecipe->setRevenu();
                                        $this->em->persist($newRecipe);
                                    } else {
                                        $recipe->setProductSold($productSold);
                                        $recipe->setRevenu();
                                    }
                                    $this->em->flush();
                                    $this->em->clear($recipe);
                                }
                            }
                        }
                    }
                }
                $this->em->flush();
                $this->em->clear($productSold);
            }
        }
        $output->writeln("PLU:".sizeof($wyndPLUs)." Ref.:".$t." Matched:".$x." Saved:".$z." Duplicated:".$s." \n");
    }
}
