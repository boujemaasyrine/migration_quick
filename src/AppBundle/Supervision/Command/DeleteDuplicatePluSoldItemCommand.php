<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 20/04/2016
 * Time: 09:51
 */

namespace AppBundle\Supervision\Command;

use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\SheetModelLine;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\SoldingCanal;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteDuplicatePluSoldItemCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('quick:duplicate:plu:delete:item')->setDefinition(
            []
        )->setDescription('Delete Sold Item with duplicate plu.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo "Delete duplicated product sold \n";

        $this->em->clear();
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $items = $this->em->getRepository(ProductSold::class)->findBy([], ['externalId' => 'asc']);
        $itemsToDelete = array();
        $i = 0;
        foreach ($items as $keyItem => &$item) {
            $retainedItem = $item;
            $retainedKey = $keyItem;
            foreach ($items as $keySecondItem => &$secondItem) {
                /**
                 * @var ProductSold $secondItem
                 * @var ProductSold $retainedItem
                 */
                if ($secondItem->getCodePlu() === $retainedItem->getCodePlu() and $secondItem->getId(
                ) != $retainedItem->getId()) {
                    if (!$secondItem->getActive() and $retainedItem->getActive()) {
                        if (!in_array($secondItem, $itemsToDelete, true)) {
                            $itemsToDelete[] = $secondItem;
                            $i++;
                            echo "item ".$i." to delete \n";
                        }
                        $this->setNewValuesLossLineForItemToDelete($secondItem, $retainedItem);
                    } else {
                        if ($secondItem->getActive() and !$retainedItem->getActive()) {
                            if (!in_array($retainedItem, $itemsToDelete, true)) {
                                $itemsToDelete[] = $retainedItem;
                                $i++;
                                echo "item ".$i." to delete \n";
                            }
                            $this->setNewValuesLossLineForItemToDelete($retainedItem, $secondItem);
                            $retainedItem = $secondItem;
                        } else {
                            if (intval($secondItem->getExternalId()) > intval($retainedItem->getExternalId())) {
                                if (!in_array($retainedItem, $itemsToDelete, true)) {
                                    $itemsToDelete[] = $retainedItem;
                                    $i++;
                                    echo "item ".$i." to delete \n";
                                }
                                $this->setNewValuesLossLineForItemToDelete($retainedItem, $secondItem);
                                $retainedItem = $secondItem;
                            } else {
                                if (!in_array($secondItem, $itemsToDelete, true)) {
                                    $itemsToDelete[] = $secondItem;
                                    $i++;
                                    echo "item ".$i." to delete \n";
                                }
                                $this->setNewValuesLossLineForItemToDelete($secondItem, $retainedItem);
                            }
                        }
                    }
                }
            }
        }
        echo count($itemsToDelete)."\n";
        foreach ($itemsToDelete as $item) {
            $this->deleteItem($item);
            echo "item2 with plu ".$item->getCodePlu()." deleted with success \n";
        }
        $output->writeln('Updated with success');
    }

    /**
     * @param ProductSold $itemToDelete
     * @param ProductSold $itemToRetain
     */
    public function setNewValuesLossLineForItemToDelete($itemToDelete, $itemToRetain)
    {
        $lossLines = $this->em->getRepository(LossLine::class)->findByProduct($itemToDelete);
        $sheetModelLines = $this->em->getRepository(SheetModelLine::class)->findByProduct($itemToDelete);

        /**
         * @var LossLine $line
         */
        $allCanals = $this->em->getRepository(SoldingCanal::class)->findOneByLabel(SoldingCanal::ALL_CANALS);
        $recipe = $this->em->getRepository(Recipe::class)->getRecipeItemForAllCanals($itemToRetain, $allCanals);

        foreach ($lossLines as $line) {
            $line->setProduct($itemToRetain)
                ->setSoldingCanal($allCanals)
                ->setRecipe($recipe);
            $this->em->persist($line);
        }

        foreach ($sheetModelLines as $modelLine) {
            /**
             * @var SheetModelLine $modelLine
             */
            $modelLine->setProduct($itemToRetain);
            $this->em->persist($modelLine);
        }
        $this->em->flush();
    }

    /**
     * @param ProductSold $item
     */
    public function deleteItem($item)
    {
        foreach ($item->getRecipes() as $recipe) {
            /**
             * @var Recipe $recipe
             */
            $this->em->remove($recipe);
        }
        $this->em->flush();
        $this->em->remove($item);
        $this->em->flush();
    }
}
