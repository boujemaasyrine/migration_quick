<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/05/2016
 * Time: 11:56
 */

namespace AppBundle\General\Command\DevCommand;

use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Merchandise\Entity\ProductPurchased;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixDuplicatedProductCommand extends ContainerAwareCommand
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
        $this->setName('quick_dev:fix:duplicate:product')->setDefinition(
            []
        )->setDescription('Fix the duplication of products problem.');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger = $this->getContainer()->get('logger');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $qb = $this->em->getRepository('Merchandise:ProductPurchased')->createQueryBuilder('pp');
        $qb->select('pp.externalId')->groupBy('pp.externalId')->having('COUNT(pp.externalId) > 1')
            ->orderBy('pp.externalId');
        $ids = $qb->getQuery()->getResult();

        foreach ($ids as $id) {
            echo "ExternalId: ".implode(',', $id)."\n";
            $first = $this->em->getRepository('Merchandise:ProductPurchased')
                ->findOneBy(
                    array('externalId' => $id['externalId'], 'status' => 'active'),
                    array('lastSynchronizedAt' => 'desc')
                );
            /**
             * @var ProductPurchased $second
             */
            $second = $this->em->getRepository('Merchandise:ProductPurchased')
                ->createQueryBuilder('p')->where('p.id not in (:id) and p.externalId = :externalId')
                ->setParameter('externalId', $id['externalId'])->setParameter('id', $first->getId())
                ->setMaxResults(1)->getQuery()->getSingleResult();

            echo "//Set stock quantity\n";
            $first->setStockCurrentQty($first->getStockCurrentQty() + $second->getStockCurrentQty());

            echo "//Change historic to the active product\n";
            if ($first->getLastSynchronizedAt() < $second->getLastSynchronizedAt()) {
                $first->setLastSynchronizedAt($second->getLastSynchronizedAt());
                $histories = $this->em->getRepository('Merchandise:ProductPurchasedHistoric')->findBy(
                    array('globalProductID' => $second->getId())
                );
                echo sizeof($histories)."\n";
                foreach ($histories as $history) {
                    $history->setGlobalProductID($first->getId());
                }
            }
            $this->em->flush();
            $this->em->clear('Merchandise:ProductPurchasedHistoric');

            echo "//Change Mvmnt to the active product\n";

            $conn = $this->em->getConnection();
            $sql2 = 'update product_purchased_mvmt set product_id = :ID2 where product_id = :ID1;';
            $stm = $conn->prepare($sql2);
            $stm->bindParam('ID1', $second->getId());
            $stm->bindParam('ID2', $first->getId());
            $stm->execute();

            echo "//Change InventoryLines to active product\n";
            $inventoryLines = $this->em->getRepository('Merchandise:InventoryLine')->findBy(
                array('product' => $second)
            );
            echo sizeof($inventoryLines)."\n";
            foreach ($inventoryLines as $inventoryLine) {
                $inventoryLine->setProduct($first);
            }

            $this->em->flush();
            $this->em->clear('Merchandise:InventoryLine');

            echo "//Change LossLines to active product\n";
            $lossLines = $this->em->getRepository('Merchandise:LossLine')->findBy(array('product' => $second));
            echo sizeof($lossLines)."\n";
            foreach ($lossLines as $lossLine) {
                $lossLine->setProduct($first);
            }

            $this->em->flush();
            $this->em->clear('Merchandise:LossLine');

            echo "//Change TransferLines to active product\n";
            $transferLines = $this->em->getRepository('Merchandise:TransferLine')->findBy(array('product' => $second));
            echo sizeof($transferLines)."\n";
            foreach ($transferLines as $transferLine) {
                $transferLine->setProduct($first);
            }

            $this->em->flush();
            $this->em->clear('Merchandise:TransferLine');

            echo "// Change DeliveryLine to active product\n";
            $deliveryLines = $this->em->getRepository('Merchandise:DeliveryLine')->findBy(array('product' => $second));
            echo sizeof($deliveryLines)."\n";
            foreach ($deliveryLines as $deliveryLine) {
                $deliveryLine->setProduct($first);
            }

            $this->em->flush();
            $this->em->clear('Merchandise:DeliveryLine');

            echo "// Change Coefficient to active product\n";
            $coefficients = $this->em->getRepository('Merchandise:Coefficient')->findBy(array('product' => $second));
            echo sizeof($coefficients)."\n";
            foreach ($coefficients as $coefficient) {
                $coefficient->setProduct($first);
            }

            $this->em->flush();
            $this->em->clear('Merchandise:Coefficient');

            echo "// Change SheetModelLine to active product\n";
            $sheetModelLines = $this->em->getRepository('Merchandise:SheetModelLine')->findBy(
                array('product' => $second)
            );
            echo sizeof($sheetModelLines)."\n";
            foreach ($sheetModelLines as $sheetModelLine) {
                $sheetModelLine->setProduct($first);
            }

            $this->em->flush();
            $this->em->clear('Merchandise:SheetModelLine');

            echo "// Change RecipeLine to active product\n";
            $recipeLines = $this->em->getRepository('Merchandise:RecipeLine')->findBy(
                array('productPurchased' => $second)
            );
            echo sizeof($recipeLines)."\n";
            foreach ($recipeLines as $recipeLine) {
                $recipeLine->setProductPurchased($first);
            }

            $this->em->flush();
            $this->em->clear('Merchandise:RecipeLine');

            echo "// Change RecipeLineHistoric to active product\n";
            $recipeLines = $this->em->getRepository('Merchandise:RecipeLineHistoric')->findBy(
                array('productPurchased' => $second)
            );
            echo sizeof($recipeLines)."\n";
            foreach ($recipeLines as $recipeLine) {
                $recipeLine->setProductPurchased($first);
            }

            $this->em->flush();
            $this->em->clear('Merchandise:RecipeLineHistoric');

            echo "// Change OrderLine to active product\n";
            $orderLines = $this->em->getRepository('Merchandise:OrderLine')->findBy(array('product' => $second));
            echo sizeof($orderLines)."\n";
            foreach ($orderLines as $orderLine) {
                $orderLine->setProduct($first);
            }

            $this->em->flush();
            $this->em->clear('Merchandise:OrderLine');

            echo "// Change OrderLine to active product\n";
            $orderHelps = $this->em->getRepository('Merchandise:OrderHelpProducts')->findBy(
                array('product' => $second)
            );
            echo sizeof($orderHelps)."\n";
            foreach ($orderHelps as $orderHelp) {
                $orderHelp->setProduct($first);
            }

            $this->em->flush();
            $this->em->clear('Merchandise:OrderHelpProducts');

            echo "// Change ControlStockTmpProduct to active product\n";
            $products = $this->em->getRepository('Report:ControlStockTmpProduct')->findBy(array('product' => $second));
            echo sizeof($products)."\n";
            foreach ($products as $product) {
                $product->setProduct($first);
            }

            $this->em->flush();
            $this->em->clear('Report:ControlStockTmpProduct');

            echo "// Change ProductSold to active product\n";
            $products = $this->em->getRepository('Merchandise:ProductSold')->findBy(
                array('productPurchased' => $second)
            );
            echo sizeof($products)."\n";
            foreach ($products as $product) {
                $product->setProductPurchased($first);
            }

            $this->em->remove($second);
            $this->em->flush();
            $this->em->clear('Merchandise:ProductSold');
        }
        echo "Finished\n";
    }
}
