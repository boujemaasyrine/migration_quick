<?php

namespace AppBundle\General\Command;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Entity\UnitNeedProducts;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 22/02/2016
 * Time: 10:58
 */
class ImportCategoriesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:categories:import')->setDefinition(
            []
        )->setDescription('Import all categories.');
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
        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $dataPath = $this->getContainer()->getParameter('kernel.root_dir').'/../data/import/';
        $file = fopen($dataPath.'categories.csv', 'r');
        $header = fgets($file);
        while ($item = fgets($file)) {
            $item = explode(';', $item);

            $categoryName = $item[0];
            $categoryNameNl = $item[5];
            $eligible = $item[1];
            $taxBe = $item[2];
            $taxLux = $item[3];
            $order = $item[4];

            // check existing category
            $category = $em->getRepository('Merchandise:ProductCategories')->findOneBy(['name' => $categoryName]);
            if (is_null($category)) {
                $category = new ProductCategories();
                $category->setName($categoryName)
                    ->setTaxBe($taxBe)
                    ->setTaxLux($taxLux)
                    ->setEligible($eligible === "OUI")
                    ->setOrder($order)
                    ->addNameTranslation('nl', $categoryNameNl);
                $em->persist($category);
                $em->flush();
                $output->writeln('Category created: '.$categoryName);
            } else {
                $category->setTaxLux($taxLux)
                    ->setTaxBe($taxBe);
                $em->flush();
            }
        }
        $output->writeln('Process completed with success !');
    }
}
