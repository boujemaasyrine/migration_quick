<?php
/**
 * Created by PhpStorm.
 * User: zbessassi
 * Date: 01/03/2019
 * Time: 12:06
 */

namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


class ResolveDuplicateInventoryItemCommand extends ContainerAwareCommand
{

    const RESTAURANT_NOT_FOUND = 1;
    const INVENTORY_ITEM_NOT_FOUND = 2;

    /**
     * @var Logger $loggerCommand
     */
    private $loggerCommand;

    /**
     * @var EntityManager $em
     */
    private $em;

    protected function configure()
    {
        $this
            ->setName("resolve:duplicate:inventoryitem")
            ->addArgument('restaurantId', InputArgument::REQUIRED)
            ->addArgument('realInventoryItemId', InputArgument::REQUIRED)
            ->addArgument('fakeInventoryItemId', InputArgument::REQUIRED)
            ->setDescription("Resolving the duplicate inventory item");
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->loggerCommand = $this->getContainer()->get('monolog.logger.app_commands');
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $unique = uniqid('duplicate_inventory_item');
        $restaurantId = (int)$input->getArgument('restaurantId');
        $restaurant = $this->em->getRepository(Restaurant::class)->find($restaurantId);
        if (!is_object($restaurant)) {
            $this->loggerCommand->addAlert('Restaurant not found with id: ' . $restaurantId . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            echo 'Restaurant not found with id: ' . $restaurantId;
            return;
        }
        $realInventoryItemId = $input->getArgument('realInventoryItemId');
        $realInventoryItem = $this->em->getRepository(ProductPurchased::class)->find($realInventoryItemId);
        if (!is_object($realInventoryItem)) {
            $this->loggerCommand->addAlert('Inventory item not found with id: ' . $realInventoryItemId . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            echo 'Inventory item not found with id: ' . $realInventoryItemId;
            return;
        }

        $fakeInventoryItemId = $input->getArgument('fakeInventoryItemId');
        $fakeInventoryItem = $this->em->getRepository(ProductPurchased::class)->find($fakeInventoryItemId);
        if (!is_object($fakeInventoryItem)) {
            $this->loggerCommand->addAlert('Inventory item not found with id: ' . $fakeInventoryItemId . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            $output->writeln('Inventory item not found with id: ' . $fakeInventoryItemId);
            return;
        }

        $this->loggerCommand->addDebug('start commande --------' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        $output->writeln('start commande --------');

        $this->loggerCommand->addDebug('Restaurant: ' . $restaurant->getName . '(' . $restaurant->getCode() . ')' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        $output->writeln('Restaurant: ' . $restaurant->getName() . '(' . $restaurant->getCode() . ')');

        $this->loggerCommand->addDebug('Real Inventory Item: ' . $realInventoryItem->getName . '(' . $realInventoryItem->getExternalId() . ')' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        $output->writeln('Real Inventory Item: ' . $realInventoryItem->getName() . '(' . $realInventoryItem->getExternalId() . ')');

        $this->loggerCommand->addDebug('Fake Inventory Item: ' . $fakeInventoryItem->getName . '(' . $fakeInventoryItem->getExternalId() . ')' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        $output->writeln('Fake Inventory Item: ' . $fakeInventoryItem->getName() . '(' . $fakeInventoryItem->getExternalId() . ')');

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Continue with this action?', false);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('The order has been canceled --------');
            $this->loggerCommand->addDebug('The order has been canceled --------' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            return;
        }
        $output->writeln('Start of sql scripting --------');
        $this->loggerCommand->addDebug('Start of sql scripting --------' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        $this->executeSQLScript($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
    }


    private function executeSQLScript($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        $this->scriptCoefficient($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptDeliveryLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptInventoryLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptLossLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptOrderLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptReturnLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptSheetModelLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptTransferLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptProductPurchasedMvmt($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptRecipeLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptRecipeLineHistoric($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptDeleteRecipeLineHistoric($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptDeleteControlStockTmpProductDay($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptDeletecontrolStockTmpProduct($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptDeleteOrderHelpFixedCoef($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
        $this->scriptDeleteProduct($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId);
    }

    private function scriptCoefficient($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "UPDATE coefficient SET product_id=:realInventoryItemId WHERE product_id=:fakeInventoryItemId 
                  and base_id in (SELECT id from  coef_base where origin_restaurant_id=:restaurantId);";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('restaurantId', $restaurantId, \PDO::PARAM_INT);
            $stm->bindParam('realInventoryItemId', $realInventoryItemId, \PDO::PARAM_INT);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : coefficient' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);

            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script coefficient successfully executed, count: '.$stm->rowCount());
            $this->loggerCommand->addDebug('Script coefficient successfully executed, count:'.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script coefficient ');
            $this->loggerCommand->addError('Problem when running sql script coefficient' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }

    private function scriptDeliveryLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "UPDATE delivery_line SET product_id=:realInventoryItemId 
               WHERE product_id=:fakeInventoryItemId and delivery_id in 
               (SELECT id from  delivery where origin_restaurant_id=:restaurantId);";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('restaurantId', $restaurantId, \PDO::PARAM_INT);
            $stm->bindParam('realInventoryItemId', $realInventoryItemId, \PDO::PARAM_INT);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : delivery line' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);

            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script delivery line successfully executed, count: '.$stm->rowCount());
            $this->loggerCommand->addDebug('Script delivery line successfully executed, count:'.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script delivery line ');
            $this->loggerCommand->addError('Problem when running sql script delivery line' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }

    private function scriptInventoryLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "UPDATE inventory_line SET product_id=:realInventoryItemId 
            WHERE product_id=:fakeInventoryItemId and inventory_sheet_id in
          (SELECT id from  inventory_sheet where origin_restaurant_id=:restaurantId);";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('restaurantId', $restaurantId, \PDO::PARAM_INT);
            $stm->bindParam('realInventoryItemId', $realInventoryItemId, \PDO::PARAM_INT);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : Invenory line' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);

            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script inventory line successfully executed, count: '.$stm->rowCount());
            $this->loggerCommand->addDebug('Script inventory line successfully executed, count:'.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script inventory line ');
            $this->loggerCommand->addError('Problem when running sql script inventory line' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }

    private function scriptLossLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "UPDATE loss_line SET product_id=:realInventoryItemId 
WHERE product_id=:fakeInventoryItemId and loss_sheet_id in 
(SELECT id from  loss_sheet where origin_restaurant_id=:restaurantId);
";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('restaurantId', $restaurantId, \PDO::PARAM_INT);
            $stm->bindParam('realInventoryItemId', $realInventoryItemId, \PDO::PARAM_INT);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : loss line' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script loss line successfully executed, count: '.$stm->rowCount());
            $this->loggerCommand->addDebug('Script loss line successfully executed, count:'.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script loss line ');
            $this->loggerCommand->addError('Problem when running sql script loss line' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }

    private function scriptOrderLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "UPDATE order_line SET product_id=:realInventoryItemId 
WHERE product_id=:fakeInventoryItemId and order_id in 
(SELECT id from  orders where origin_restaurant_id=:restaurantId);
";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('restaurantId', $restaurantId, \PDO::PARAM_INT);
            $stm->bindParam('realInventoryItemId', $realInventoryItemId, \PDO::PARAM_INT);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : order line' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script order line successfully executed, count: '.$stm->rowCount());
            $this->loggerCommand->addDebug('Script order line successfully executed, count:'.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script order line ');
            $this->loggerCommand->addError('Problem when running sql script order line' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }

    private function scriptReturnLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "UPDATE return_line SET product_id=:realInventoryItemId
 WHERE product_id=:fakeInventoryItemId and return_id in (SELECT id from  returns where origin_restaurant_id=:restaurantId);
";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('restaurantId', $restaurantId, \PDO::PARAM_INT);
            $stm->bindParam('realInventoryItemId', $realInventoryItemId, \PDO::PARAM_INT);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : return line' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script return line successfully executed, count: '.$stm->rowCount());
            $this->loggerCommand->addDebug('Script return line successfully executed, count:'.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script return line ');
            $this->loggerCommand->addError('Problem when running sql script return line' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }


    private function scriptSheetModelLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "UPDATE sheet_model_line SET product_id=:realInventoryItemId 
WHERE product_id=:fakeInventoryItemId and sheet_id in (SELECT id from  sheet_model where origin_restaurant_id=:restaurantId);
";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('restaurantId', $restaurantId, \PDO::PARAM_INT);
            $stm->bindParam('realInventoryItemId', $realInventoryItemId, \PDO::PARAM_INT);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : SheetModel line' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script SheetModel line successfully executed, count: '.$stm->rowCount());
            $this->loggerCommand->addDebug('Script SheetModel line successfully executed, count'.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script SheetModel line ');
            $this->loggerCommand->addError('Problem when running sql script SheetModel line' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }

    private function scriptTransferLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "UPDATE transfer_line SET product_id=:realInventoryItemId 
WHERE product_id=:fakeInventoryItemId and transfer_id in (SELECT id from transfer where origin_restaurant_id=:restaurantId);
";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('restaurantId', $restaurantId, \PDO::PARAM_INT);
            $stm->bindParam('realInventoryItemId', $realInventoryItemId, \PDO::PARAM_INT);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : Transfer line' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script Transfer line successfully executed, count: '.$stm->rowCount());
            $this->loggerCommand->addDebug('Script Transfer line successfully executed, count:'.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script Transfer line ');
            $this->loggerCommand->addError('Problem when running sql script Transfer line' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }


    private function scriptProductPurchasedMvmt($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "UPDATE product_purchased_mvmt SET product_id=:realInventoryItemId 
WHERE product_id=:fakeInventoryItemId and origin_restaurant_id=:restaurantId;

";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('restaurantId', $restaurantId, \PDO::PARAM_INT);
            $stm->bindParam('realInventoryItemId', $realInventoryItemId, \PDO::PARAM_INT);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : ProductPurchasedMvmt' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script ProductPurchasedMvmt successfully executed, count: '.$stm->rowCount());
            $this->loggerCommand->addDebug('Script ProductPurchasedMvmt successfully executed, count:'.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script ProductPurchasedMvmt');
            $this->loggerCommand->addError('Problem when running sql script ProductPurchasedMvmt' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }


    private function scriptRecipeLine($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "UPDATE recipe_line SET product_purchased_id=:realInventoryItemId 
WHERE product_purchased_id=:fakeInventoryItemId;
";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('realInventoryItemId', $realInventoryItemId, \PDO::PARAM_INT);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : RecipeLine' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script RecipeLine successfully executed, count: '.$stm->rowCount());
            $this->loggerCommand->addDebug('Script RecipeLine successfully executed, count:'.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script RecipeLine');
            $this->loggerCommand->addError('Problem when running sql script RecipeLine' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }

    private function scriptRecipeLineHistoric($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "UPDATE recipe_line_historic SET product_purchased_id=:realInventoryItemId
 WHERE product_purchased_id=:fakeInventoryItemId;
";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('realInventoryItemId', $realInventoryItemId, \PDO::PARAM_INT);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : RecipeLineHistoric' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script RecipeLineHistoric successfully executed, count: '.$stm->rowCount());
            $this->loggerCommand->addDebug('Script RecipeLineHistoric successfully executed, count:'.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script RecipeLineHistoric');
            $this->loggerCommand->addError('Problem when running sql script RecipeLineHistoric' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }


    private function scriptDeleteRecipeLineHistoric($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "DELETE FROM recipe_line_historic  WHERE product_purchased_id = :fakeInventoryItemId;";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : DeleteRecipeLineHistoric' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script DeleteRecipeLineHistoric successfully executed, count: '.$stm->rowCount());
            $this->loggerCommand->addDebug('Script DeleteRecipeLineHistoric successfully executed, count:'.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script DeleteRecipeLineHistoric');
            $this->loggerCommand->addError('Problem when running sql script DeleteRecipeLineHistoric' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }


    private function scriptDeleteControlStockTmpProductDay($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "DELETE FROM control_stock_tmp_product_day WHERE product_tmp_id IN
 (SELECT id FROM control_stock_tmp_product WHERE product_id = :fakeInventoryItemId);
";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : DeleteControlStockTmpProductDay' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script DeleteControlStockTmpProductDay successfully executed, count: '.$stm->rowCount());
            $this->loggerCommand->addDebug('Script DeleteControlStockTmpProductDay successfully executed, count:'.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script DeleteControlStockTmpProductDay');
            $this->loggerCommand->addError('Problem when running sql script DeleteControlStockTmpProductDay' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }

    private function scriptDeletecontrolStockTmpProduct($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "DELETE FROM control_stock_tmp_product WHERE product_id = :fakeInventoryItemId; ";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : DeletecontrolStockTmpProduct' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script DeletecontrolStockTmpProduct successfully executed, count: '.$stm->rowCount());
            $this->loggerCommand->addDebug('Script DeletecontrolStockTmpProduct successfully executed, count: '.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script DeletecontrolStockTmpProduct');
            $this->loggerCommand->addError('Problem when running sql script DeletecontrolStockTmpProduct' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }


    private function scriptDeleteOrderHelpFixedCoef($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $sql = "DELETE FROM order_help_fixed_coef WHERE product_id =:fakeInventoryItemId 
AND origin_restaurant_id =:restaurantId;
";
            $stm = $this->em->getConnection()->prepare($sql);
            $stm->bindParam('restaurantId', $restaurantId, \PDO::PARAM_INT);
            $stm->bindParam('fakeInventoryItemId', $fakeInventoryItemId, \PDO::PARAM_INT);
            $result = $stm->execute();
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : DeleteOrderHelpFixedCoef' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            throw $e;
        }
        if ($result == true) {
            $output->writeln('Script DeleteOrderHelpFixedCoef successfully executed , count: ' .$stm->rowCount());
            $this->loggerCommand->addDebug('Script DeleteOrderHelpFixedCoef successfully executed, count:'.$stm->rowCount() . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } else {
            $output->writeln('Problem when running sql script DeleteOrderHelpFixedCoef');
            $this->loggerCommand->addError('Problem when running sql script DeleteOrderHelpFixedCoef' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        }
    }


    private function scriptDeleteProduct($output, $unique,$restaurantId,$realInventoryItemId,$fakeInventoryItemId)
    {
        try {
            $fakeInventoryItem=  $this->em->getRepository(ProductPurchased::class)->find($fakeInventoryItemId);
            $this->em->remove($fakeInventoryItem);
            $this->em->flush();
            $output->writeln('Script DeleteProduct successfully executed, count:' );
            $this->loggerCommand->addDebug('Script DeleteProduct successfully executed'  . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('Problem when running sql script : DeleteProduct' . ': ' . $unique, ['resolve:duplicate:inventoryitem']);
            throw $e;
        }
    }


}