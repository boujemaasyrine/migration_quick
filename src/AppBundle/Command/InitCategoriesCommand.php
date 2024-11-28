<?php

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\CategoryGroup;
use AppBundle\Merchandise\Entity\ProductCategories;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCategoriesCommand extends ContainerAwareCommand
{
    //Row format :   name ; name_translation ;  active  ;  eligible  ;  order ;   tax_lux  ;   tax_be  ; group_name  ;

    private $em;
    private $dataDir;
    private $categories = array(
        array(
            "name" => "ACHATS DIRECTS",
            "name_translation" => "DIRECTE AANKOPEN",
            "active" => true,
            "eligible" => false,
            "order" => 6,
            "tax_lux" => 3,
            "tax_be" => 6,
            "group_name" => "FOODCOST"
        ),
        array(
            "name" => "ALCOOLS",
            "name_translation" => "ALCOHOL",
            "active" => true,
            "eligible" => false,
            "order" => 7,
            "tax_lux" => 17,
            "tax_be" => 21,
            "group_name" => "FOODCOST"
        ),
        array(
            "name" => "NOURRITURE FRAI/SURG",
            "name_translation" => "VOEDSEL VERS/DIEPVRI",
            "active" => true,
            "eligible" => true,
            "order" => 1,
            "tax_lux" => 3,
            "tax_be" => 6,
            "group_name" => "FOODCOST"
        ),
        array(
            "name" => "NOURRITURE SEC",
            "name_translation" => "VOESDEL DROOG",
            "active" => true,
            "eligible" => true,
            "order" => 2,
            "tax_lux" => 3,
            "tax_be" => 6,
            "group_name" => "FOODCOST"
        ),
        array(
            "name" => "003",
            "name_translation" => "003",
            "active" => true,
            "eligible" => false,
            "order" => 5,
            "tax_lux" => 17,
            "tax_be" => 21,
            "group_name" => "NON FOODCOST"
        ),
        array(
            "name" => "PAPERCOST",
            "name_translation" => "PAPERCOST",
            "active" => true,
            "eligible" => true,
            "order" => 3,
            "tax_lux" => 17,
            "tax_be" => 21,
            "group_name" => "PAPERCOST"
        ),
        array(
            "name" => "PRIMES",
            "name_translation" => "PREMIES",
            "active" => true,
            "eligible" => false,
            "order" => 4,
            "tax_lux" => 17,
            "tax_be" => 21,
            "group_name" => "PAPERCOST"
        ),
        array(
            "name" => "PAPERCOST Indirect",
            "name_translation" => "INDIRECTE PAPERCOST",
            "active" => true,
            "eligible" => true,
            "order" => 3,
            "tax_lux" => 17,
            "tax_be" => 21,
            "group_name" => "PAPERCOST"
        ),
        array(
            "name" => "CONSIGNES",
            "name_translation" => "LEEGGOED",
            "active" => true,
            "eligible" => false,
            "order" => 8,
            "tax_lux" => 0,
            "tax_be" => 0,
            "group_name" => "NON FOODCOST"
        ),
        array(
            "name" => "BUNS",
            "name_translation" => "BUNS",
            "active" => true,
            "eligible" => true,
            "order" => 0,
            "tax_lux" => 3,
            "tax_be" => 6,
            "group_name" => "FOODCOST"
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('saas:init:categories')
            ->addArgument('file', InputArgument::OPTIONAL, 'File Name in case of init from csv file.')
            ->setDescription('Command to initialise default categories for the platform.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->dataDir = $this->getContainer()->getParameter('kernel.root_dir')."/../data/import/saas/";

        parent::initialize($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $argument = $input->getArgument('file');
        if (isset($argument)) {
            $filename = $argument.".csv";
            $filePath = $this->dataDir.$filename;

            if (!file_exists($filePath)) {
                $output->writeln("No csv import file with the '".$argument."' name !");

                return;
            }
            try {
                // Import du fichier CSV
                $this->categories = array();
                if (($handle = fopen($filePath, "r")) !== false) { // Lecture du fichier, à adapter
                    $output->writeln("---->Import mode: CSV file.");
                    while (($data = fgetcsv(
                            $handle,
                            1000,
                            ";"
                        )) !== false) { // Eléments séparés par un point-virgule, à modifier si necessaire

                        $this->categories[] = array(
                            "name" => $data[0],
                            "name_translation" => $data[1],
                            "active" => boolval($data[2]),
                            "eligible" => boolval($data[3]),
                            "order" => $data[4],
                            "tax_lux" => $data[5],
                            "tax_be" => $data[6],
                            "group_name" => $data[7],

                        );

                    }
                    fclose($handle);
                } else {
                    $output->writeln("Cannot open the csv file! Exit command...");

                    return;
                }

            } catch (\Exception $e) {
                $output->writeln($e->getMessage());

                return;
            }

        } else {
            $output->writeln("---->Import mode: Default.");
        }

        $output->writeln("Start importing categories...");
        $count = 0;
        $categoriesUpdated=0;

        foreach ($this->categories as $c) {


            $group = $this->em->getRepository(CategoryGroup::class)->findOneBy(['name' => $c['group_name']]);
            if (is_null($group)) {
                $group = new CategoryGroup();
                $group->setName($c['group_name'])
                    ->setActive(true);
                $group->addNameTranslation('nl', $c['group_name']);
                $this->em->persist($group);
                $group->setGlobalId($group->getId());
            }

            // check existing category
            $category = $this->em->getRepository(ProductCategories::class)->findOneBy(['name' => $c['name']]);
            if (is_null($category)) {
                $category = new ProductCategories();
                $category->setName($c['name'])
                    ->setTaxBe($c['tax_be'])
                    ->setTaxLux($c['tax_lux'])
                    ->setEligible($c['eligible'] === "OUI" || $c['eligible'])
                    ->setOrder($c['order'])
                    ->setActive($c['active']);
                if ($c['name_translation'] != "") {
                    $category->addNameTranslation('nl', $c['name_translation']);
                } else {
                    $category->addNameTranslation('nl', $c['name']);
                }
                $this->em->persist($category);
                $output->writeln('=>Category created: '.$c['name']);
                $count++;
            } else {
                $category->setTaxLux($c['tax_lux'])
                    ->setTaxBe($c['tax_be']);
                $output->writeln('->Category updated: '.$c['name']);
                $categoriesUpdated++;
            }
            if ($category->getGlobalId() === null) {
                $category->setGlobalId($category->getId());
            }
            $category->setCategoryGroup($group);

        }

        $this->em->flush();

        if($categoriesUpdated>0){
            $output->writeln("----> ".$categoriesUpdated." catgories updated.");
        }

        $output->writeln("----> ".$count." categories imported.");
        $output->writeln("==> Categories initialised successfully <==");

    }
}
