<?php
/**
 * Created by PhpStorm.
 * User: hmnaouar
 * Date: 08/11/2017
 * Time: 14:48
 */

namespace AppBundle\General\Command\DevCommand;

use AppBundle\Administration\Entity\Optikitchen\Optikitchen;
use AppBundle\Administration\Entity\Parameter;
use AppBundle\Administration\Entity\Procedure;
use AppBundle\Financial\Entity\AdministrativeClosing;
use AppBundle\Financial\Entity\CashboxCount;
use AppBundle\Financial\Entity\ChestCount;
use AppBundle\Financial\Entity\Deposit;
use AppBundle\Financial\Entity\Envelope;
use AppBundle\Financial\Entity\Expense;
use AppBundle\Financial\Entity\FinancialRevenue;
use AppBundle\Financial\Entity\PaymentMethod;
use AppBundle\Financial\Entity\RecipeTicket;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\Withdrawal;
use AppBundle\Merchandise\Entity\CaPrev;
use AppBundle\Merchandise\Entity\CoefBase;
use AppBundle\Merchandise\Entity\Delivery;
use AppBundle\Merchandise\Entity\DeliveryTmp;
use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductPurchasedMvmt;
use AppBundle\Merchandise\Entity\ProductSoldHistoric;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\Returns;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Entity\Transfer;
use AppBundle\Report\Entity\ControlStockTmp;
use AppBundle\Report\Model\CashBookReport;
use AppBundle\Security\Entity\User;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateToSaasCommand extends ContainerAwareCommand
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
        $this->setName('quick_dev:migrate_saas')
            ->setDescription('Migrate database to the new SaaS schema');
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
        $output->writeln("adding reference of the restaurant...");
        //get restaurant
        $agora = $this->em->getRepository(Restaurant::class)->find(102);

        $output->writeln("treat payment methods...");
        $paymentMethods = $this->em->getRepository(PaymentMethod::class)->findAll();
        foreach ($paymentMethods as $paymentMethod) {
            $paymentMethod->addRestaurant($agora);
            $agora->addPaymentMethod($paymentMethod);
        }

        $output->writeln("treat suppliers...");
        $suppliers = $this->em->getRepository(Supplier::class)->findAll();
        foreach ($suppliers as $supplier) {
            $supplier->addRestaurant($agora);
            $agora->addSupplier($supplier);
        }

        $output->writeln("adding reference of the first supplier to the products purchased");
        $firstSupplier = $this->em->getRepository(Supplier::class)->find(1);
        $productsPurchased = $this->em->getRepository(ProductPurchased::class)->findAll();
        foreach ($productsPurchased as $pr) {
            $pr->addSupplier($firstSupplier);
            $firstSupplier->addProduct($pr);
        }

        /********** *******************/
        $this->em->flush();


        $output->writeln("done.");
    }
}
