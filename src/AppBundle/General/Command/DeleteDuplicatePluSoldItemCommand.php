<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 20/04/2016
 * Time: 09:51
 */

namespace AppBundle\General\Command;

use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\SheetModelLine;
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
        $items = $this->em->getRepository('Merchandise:ProductSold')->findAllOrderByPlu();
        $itemsToDelete = array();
        $i = 0;
        foreach ($items as $keyItem => &$item) {
            $retainedItem = $item;
            $retainedKey = $keyItem;
            foreach ($items as $keySecondItem => &$secondItem) {
                if ($secondItem->getCodePlu() === $retainedItem->getCodePlu() && $secondItem->getId(
                ) != $retainedItem->getId()) {
                    if (!$secondItem->getActive() && $retainedItem->getActive()) {
                        if (!in_array($secondItem, $itemsToDelete)) {
                            $i++;
                            $itemsToDelete[] = $secondItem;
                            echo "item ".$i." to delete \n";
                        }
                        $this->setNewValuesLossLineForItemToDelete($secondItem, $retainedItem);
                    } else {
                        if ($secondItem->getActive() && !$retainedItem->getActive()) {
                            if (!in_array($retainedItem, $itemsToDelete)) {
                                $i++;
                                $itemsToDelete[] = $retainedItem;
                                echo "item ".$i." to delete \n";
                            }
                            $this->setNewValuesLossLineForItemToDelete($retainedItem, $secondItem);
                            $retainedItem = $secondItem;
                        } else {
                            if ((strlen($secondItem->getName()) < strlen($retainedItem->getName()))
                                || (strlen($secondItem->getName()) == strlen(
                                    $retainedItem->getName()
                                ) && $secondItem->getId() < $retainedItem->getId())
                            ) {
                                if (!in_array($retainedItem, $itemsToDelete)) {
                                    $i++;
                                    $itemsToDelete[] = $retainedItem;
                                    echo "item ".$i." to delete \n";
                                }
                                $this->setNewValuesLossLineForItemToDelete($retainedItem, $secondItem);
                                $retainedItem = $secondItem;
                            } else {
                                if ((strlen($secondItem->getName()) > strlen($retainedItem->getName()))
                                    || (strlen($secondItem->getName()) == strlen(
                                        $retainedItem->getName()
                                    ) && $secondItem->getId() > $retainedItem->getId())
                                ) {
                                    if (!in_array($secondItem, $itemsToDelete)) {
                                        $i++;
                                        $itemsToDelete[] = $secondItem;
                                        echo "item ".$i." to delete \n";
                                    }
                                    $this->setNewValuesLossLineForItemToDelete($secondItem, $retainedItem);
                                }
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
        $lossLines = $this->em->getRepository('Merchandise:LossLine')->findByProduct($itemToDelete);
        $sheetModelLines = $this->em->getRepository('Merchandise:SheetModelLine')->findByProduct($itemToDelete);
        foreach ($lossLines as $line) {
            /**
             * @var LossLine $line
             */
            $allCanals = $this->em->getRepository('Merchandise:SoldingCanal')->findOneByLabel(SoldingCanal::ALL_CANALS);
            $recipe = $this->em->getRepository('Merchandise:Recipe')->getRecipeItemForAllCanals(
                $itemToRetain,
                $allCanals
            );

            $line->setProduct($itemToRetain)
                ->setSoldingCanal($allCanals);

            if ($recipe) {
                $line->setRecipe($recipe);
            }

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

    public function deleteItem($item)
    {
        $this->em->remove($item);
        $this->em->flush();
    }
}
