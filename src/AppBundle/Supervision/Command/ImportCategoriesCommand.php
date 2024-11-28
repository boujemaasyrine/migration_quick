<?php

namespace AppBundle\Supervision\Command;

use AppBundle\Merchandise\Entity\CategoryGroup;
use AppBundle\Merchandise\Entity\ProductCategories;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

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
            $groupName = $item[6];

            $group = $em->getRepository('AppBundle:CategoryGroup')->findOneBy(['name' => $groupName]);
            if (is_null($group)) {
                $group = new CategoryGroup();
                $group->setName($groupName)
                    ->addNameTranslation('nl', $groupName)
                    ->setActive(true);
                $em->persist($group);
                $group->setGlobalId($group->getId());
            }

            // check existing category
            $category = $em->getRepository('AppBundle:ProductCategories')->findOneBy(['name' => $categoryName]);
            if (is_null($category)) {
                $category = new ProductCategories();
                $category->setName($categoryName)
                    ->setTaxBe($taxBe)
                    ->setTaxLux($taxLux)
                    ->setEligible($eligible === "OUI")
                    ->setOrder($order)
                    ->setActive(true)
                    ->addNameTranslation('nl', $categoryNameNl);
                $em->persist($category);
                $output->writeln('Category created: '.$categoryName);
            } else {
                $category->setTaxLux($taxLux)
                    ->setTaxBe($taxBe);
                $output->writeln('Category updated: '.$categoryName);
            }
            if ($category->getId() == null) {
                $category->setGlobalId($category->getId());
            }
            $category->setCategoryGroup($group);
            $em->flush();
        }
        $output->writeln('Process completed with success !');
    }
}
