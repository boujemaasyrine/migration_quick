<?php
/**
 * Created by PhpStorm.
 * User: zbessassi
 * Date: 03/04/2019
 * Time: 11:32
 */


namespace AppBundle\Command;

use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductPurchasedMvmt;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Service\ProductPurchasedMvmtService;
use AppBundle\Merchandise\Service\ProductService;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;


/**
 * Cette commande permet de supprimer une ligne de pertes avec leurs mvmts
 *
 * Class DeleteLossSheetLineCommand
 * @version 1 Disponible pour les lignes de perte des items de vente
 * @package AppBundle\Command
 */
class DeleteLossSheetLineCommand extends ContainerAwareCommand
{

    /**
     * @var Logger $loggerCommand
     */
    private $loggerCommand;

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var ProductPurchasedMvmtService $productPurchasedMvmtService
     */
    private $productPurchasedMvmtService;

    /**
     * @var ProductService $productService
     */
    private $productService;

    protected function configure()
    {
        $this
            ->setName("delete:losssheetline")
            ->addArgument('lossSheet', InputArgument::REQUIRED)
            ->addArgument('lossSheetLine', InputArgument::REQUIRED)
            ->addArgument('deleteLine', InputArgument::REQUIRED)
            ->setDescription("Delete loss sheet line");
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->loggerCommand = $this->getContainer()->get('monolog.logger.app_commands');
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->productPurchasedMvmtService = $this->getContainer()->get('product_purchased_mvmt.service');
        $this->productService = $this->getContainer()->get('product.service');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var LossSheet $lossSheet
         */

        /**
         * @var LossLine $lossSheetLine
         */

        $unique = uniqid('delete_loss_sheet_line');

        $lossSheetId = $input->getArgument('lossSheet');
        $lossSheet = $this->em->getRepository(LossSheet::class)->find($lossSheetId);
        if (!is_object($lossSheet)) {
            $this->loggerCommand->addAlert('Loss sheet is not found with id: ' . $lossSheetId . ': ' . $unique, ['delete:losssheetline']);
            echo 'Loss sheet is not found with id: ' . $lossSheetId;
            return;
        }

        $lossSheetLineId = $input->getArgument('lossSheetLine');
        $lossSheetLine = $this->em->getRepository(LossLine::class)->find($lossSheetLineId);
        if (!is_object($lossSheetLine)) {
            $this->loggerCommand->addAlert('Loss sheet line is not found with id: ' . $lossSheetLineId . ': ' . $unique, ['delete:losssheetline']);
            echo 'Loss sheet line is not found with id: ' . $lossSheetLineId;
            return;
        }

        $deleteLine = $input->getArgument('deleteLine');
        if (is_bool($deleteLine)) {
            $this->loggerCommand->addAlert('is not boolean value: ' . $deleteLine . ': ' . $unique, ['delete:losssheetline']);
            echo 'is not boolean value: ' . $deleteLine;
            return;
        }


        $restaurant = $lossSheet->getOriginRestaurant();

        $this->loggerCommand->addDebug('start commande' . ': ' . $unique, ['delete:losssheetline']);
        $output->writeln('start commande');

        $this->loggerCommand->addDebug('Restaurant: ' . $restaurant->getName . '(' . $restaurant->getCode() . ')' . ': ' . $unique, ['delete:losssheetline']);
        $output->writeln('Restaurant: ' . $restaurant->getName() . '(' . $restaurant->getCode() . ')');

        $this->loggerCommand->addDebug('Loss sheet to edit: ' . $lossSheet->getSheetModelLabel() . ': ' . $unique, ['delete:losssheetline']);
        $output->writeln('Loss sheet to edit: ' . $lossSheet->getSheetModelLabel());

        $this->loggerCommand->addDebug('Loss sheet line to edit: ' . $lossSheetLine->getTotalLoss() . ': ' . $unique, ['delete:losssheetline']);
        $output->writeln('Loss sheet line to edit: ' . $lossSheetLine->getTotalLoss());
        $this->loggerCommand->addDebug('Delete loss sheet line: ' . $deleteLine . ': ' . $unique, ['delete:losssheetline']);
        $output->writeln('Delete loss sheet line: ' . $deleteLine);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Continue with this action?', false);


        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('The order has been canceled --------');
            $this->loggerCommand->addDebug('The order has been canceled --------' . ': ' . $unique, ['delete:losssheetline']);
            return;
        }

        if ($lossSheet->getType() != LossSheet::FINALPRODUCT) {
            $output->writeln('This command only for sold loss line --------');
            $this->loggerCommand->addDebug('This command only for sold loss line --------' . ': ' . $unique, ['delete:losssheetline']);
            return;
        }

        if ($lossSheet->getId() != $lossSheetLine->getLossSheet()->getId()) {
            $output->writeln('The loss line does not belong to the loss sheet. --------');
            $this->loggerCommand->addDebug('The loss line does not belong to the loss sheet. --------' . ': ' . $unique, ['delete:losssheetline']);
            return;
        }


        $output->writeln('Start execution order');
        $this->loggerCommand->addDebug('Start execution order' . ': ' . $unique, ['delete:losssheetline']);

        try {

            if ($lossSheetLine->getProduct()->getType() === ProductSold::TRANSFORMED_PRODUCT) {
                $this->productService->updateStock(
                    $lossSheetLine->getProduct(),
                    $lossSheetLine->getTotalLoss(),
                    Product::INVENTORY_UNIT,
                    $lossSheetLine->getRecipe()
                );
            } else {
                $this->productService->updateStock($lossSheetLine->getProduct(), $lossSheetLine->getTotalLoss());
            }

            $this->productPurchasedMvmtService->deleteMvmtEntriesByTypeAndSourceId(ProductPurchasedMvmt::SOLD_LOSS_TYPE, $lossSheetLine->getId(), $restaurant);
            $totalLoss = $lossSheetLine->getTotalLoss();
            if ($deleteLine == 'true') {
                $this->em->remove($lossSheetLine);
            } elseif ($deleteLine == 'false') {
                $lossSheetLine->setFirstEntry(0);
                $lossSheetLine->setSecondEntry(0);
                $lossSheetLine->setThirdEntry(0);
                $lossSheetLine->setTotalLoss(0);
                $lossSheetLine->setTotalRevenuePrice(0);
            }

            $this->em->flush();
            $output->writeln('Loss line with quantity : ' . $totalLoss . ' successfully changed');
            $this->loggerCommand->addDebug('Loss line with quantity  ' . $totalLoss . '  successfully changed' . ': ' . $unique, ['delete:losssheetline']);
        } catch (\Exception $e) {
            $output->writeln('Exeption: ' . $e);
            $this->loggerCommand->addError('problem during this order' . ': ' . $unique, ['delete:losssheetline']);
            throw $e;
        }
    }

}