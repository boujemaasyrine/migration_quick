<?php

namespace AppBundle\Supervision\Command;

use AppBundle\Merchandise\Entity\Recipe;

use AppBundle\Supervision\Utils\Utilities;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Symfony\Bridge\Monolog\Logger;
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
class ImportRecipesCommand extends ContainerAwareCommand
{
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
        $this->setName('quick:recipes:import')->setDefinition(
            []
        )->setDescription('Import all product sold, receipes and division.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $this->logger = $this->getContainer()->get('logger');
        parent::initialize($input, $output);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->em->getConnection();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $dataPath = $this->getContainer()->getParameter('kernel.root_dir').'/../data/import/Referentiel_2016_05_30/';
        $file = fopen($dataPath.'receipes.csv', 'r');
        $header = fgets($file);
        $soldingCanal = $this->em->getRepository('AppBundle:SoldingCanal')->findOneBy(
            [
                'label' => "allcanals",
            ]
        );
        if (is_null($soldingCanal)) {
            throw new InternalErrorException('You must import/reimport the solding canals first !');
        }

        $missingPp = [];

        while ($item = fgets($file)) {
            $item = explode(';', $item);
            $idItemInventory = null;
            $receipeId = $item[0];
            $receipeName = $item[1];
            $qty = $item[2];
            $labelUnit = $item[4];
            $idItemInventory = $item[5];
            $active = !Utilities::startsWith($receipeName, '[');
            $receipeName = ltrim($receipeName, '[');

            // check if receipe exist
            $statement = $connection->prepare(
                "SELECT * from recipe where external_id = :receipe_id;"
            );
            $statement->bindValue('receipe_id', $receipeId);
            $statement->execute();
            $recipes = $statement->fetchAll();
            $internalReceipeId = null;
            if (count($recipes) > 0) {
                $internalReceipeId = $recipes[0]['id'];
            } else {
                // create new receipe
                $recipe = new Recipe();
                $recipe->setExternalId($receipeId)
                    ->setSoldingCanal($soldingCanal)
                    ->setActive($active);
                $this->em->persist($recipe);
                $recipe->setGlobalId($recipe->getId());
                $this->em->flush();
                $internalReceipeId = $recipe->getId();
                $output->writeln('Recipe '.$receipeName.' created.');
            }

            // retrieve the purchased inventory
            $q = $this->em->getRepository('AppBundle:ProductPurchased')
                ->createQueryBuilder('productPurchased')
                ->where("productPurchased.idItemInv = :idItem ")
                ->setParameter("idItem", $idItemInventory)
                ->getQuery();
            try {
                $purchasedProduct = $q->getSingleResult();
            } catch (NoResultException $e) {
                $this->logger->addDebug(
                    'Purchased product is not found: idItemInventory => '.$idItemInventory,
                    ['ImportDataCommand']
                );
                $purchasedProduct = null;
                $missingPp[] = $idItemInventory;
            }

            if (is_null($purchasedProduct)) {
                $output->writeln('Purchased product is not found: idItemInventory => '.$idItemInventory);
            } else {
                $recipeLineId = $connection->prepare("SELECT nextval('recipe_line_id_seq')");
                $recipeLineId->execute();
                $recipeLineId = $recipeLineId->fetch(\PDO::FETCH_COLUMN);

                $statement = $connection->prepare(
                    "INSERT INTO recipe_line (id, recipe_id, qty, product_purchased_id)
                    VALUES (:id, :recipe_id, :qty, :product_purchased_id);"
                );
                $statement->bindValue('id', $recipeLineId);
                $statement->bindValue('recipe_id', $internalReceipeId);
                $statement->bindValue('qty', floatval($qty));
                $statement->bindValue('product_purchased_id', $purchasedProduct->getId());
                $statement->execute();
                $output->writeln('Recipe Line inserted: '.$idItemInventory);
            }
        }
        $missingPp = array_unique($missingPp);
        $this->logger->addDebug('missing product purchased : '.implode(',', $missingPp), ['ImportDataCommand']);

        $output->writeln('Import receipes completed !');
    }
}
