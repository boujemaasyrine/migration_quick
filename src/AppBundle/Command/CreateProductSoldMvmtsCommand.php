<?php
/**
 * Created by PhpStorm.
 * User: zbessassi
 * Date: 18/06/2019
 * Time: 16:04
 */

namespace AppBundle\Command;

use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Service\ProductPurchasedMvmtService;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Cette commande est très spécifique elle permet de créer les mvmts de type vente pour un seul item d'inventaire
 * les paramètres nécessaire
 * -restaurantId  identifiant de restaurant
 * -startDate
 * -endDate
 * -productSoldId identifiant de l'item de vente
 * -productPurchasedId identifiant de l'item d'inventaire
 * Class CreateProductSoldMvmtsCommand
 * @version Cette version permet de créer seulement les mvmts pour un item d'inventaire(un élement de la recette de l'item de vente)
 * @package AppBundle\Command
 */
class CreateProductSoldMvmtsCommand extends ContainerAwareCommand
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
            ->setName("create:productsold:mvmts")
            ->addArgument('restaurantId', InputArgument::REQUIRED)
            ->addArgument('startDate', InputArgument::REQUIRED)
            ->addArgument('endDate', InputArgument::REQUIRED)
            ->addArgument('productSoldId', InputArgument::REQUIRED)
            ->addArgument('productPurchasedId', InputArgument::REQUIRED)
            ->setDescription("Create the movements of a sales item from the ticket lines");
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
        $unique = uniqid('create_productSold_mvmts');
        $restaurantId = $input->getArgument('restaurantId');
        $oRestaurant = $this->em->getRepository(Restaurant::class)->findOneBy(array('id' => $input->getArgument('restaurantId')));
        if (!is_object($oRestaurant)) {
            $this->loggerCommand->addError('restaurant not found with id: ' . $restaurantId . ': ' . $unique, ['create:productsold:mvmts']);
            echo 'restaurant not found with id: ' . $restaurantId;
            return;
        }
        $startDate = $input->getArgument('startDate');
        $oStartDate =  date_create_from_format('Y-m-d',  $startDate);

        if (!is_object($oStartDate)) {
            $this->loggerCommand->addError('problem in start date ' . $startDate . ': ' . $unique, ['create:productsold:mvmts']);
            echo 'problem in start date ' . $startDate;
            return;
        }

        $endDate = $input->getArgument('endDate');
        $oEndDate = date_create_from_format('Y-m-d',   $endDate);
        if (!is_object($oEndDate)) {
            $this->loggerCommand->addError('problem in end date ' . $endDate . ': ' . $unique, ['create:productsold:mvmts']);
            echo 'problem in end date ' . $endDate;
            return;
        }

        $productSoldId = $input->getArgument('productSoldId');
        $oProductSold = $this->em->getRepository(ProductSold::class)->findOneBy(array('id' => $productSoldId, 'originRestaurant' => $oRestaurant));
        if (!is_object($oProductSold)) {
            $this->loggerCommand->addError('Product sold not found with id: ' . $productSoldId . ': ' . $unique, ['create:productsold:mvmts']);
            echo 'Product sold not found with id: ' . $productSoldId;
            return;
        }

        $productPurchasedId = $input->getArgument('productPurchasedId');
        $oProductPurchased = $this->em->getRepository(ProductPurchased::class)->findOneBy(array('id' => $productPurchasedId, 'originRestaurant' => $oRestaurant));
        if (!is_object($oProductPurchased)) {
            $this->loggerCommand->addError('Product purchased not found with id: ' . $productPurchasedId . ': ' . $unique, ['create:productsold:mvmts']);
            echo 'Product purchased not found with id: ' . $productPurchasedId;
            return;
        }

        $this->loggerCommand->addDebug('start commande' . ': ' . $unique, ['create:productsold:mvmts']);
        $output->writeln('start commande');
        $ticketLines =$this->getTicketsLinesOfProductSold($oStartDate,$oEndDate,$oProductSold,$restaurantId);
        foreach ($ticketLines as $line){
            $line->setRevenuePrice($line->getRevenuePrice());
            $line->setMvmtRecorded(true);
            $this->productPurchasedMvmtService->createMvmtEntryForTicketLineFromCommand($line,$oRestaurant,null,
                $oProductSold,$oProductPurchased,$unique);
//            $this->productPurchasedMvmtService-> createMvmtEntryForTicketLine($line, $oRestaurant, $ticket=null);

        }
        $this->loggerCommand->addDebug('End commande' . ': ' . $unique, ['create:productsold:mvmts']);
        $output->writeln('End commande');
    }


    private function getTicketsLinesOfProductSold($startDate, $endDate, ProductSold $productSold, $restaurantId)
    {
        $qb = $this->em->getRepository('Financial:TicketLine')->createQueryBuilder('ticketLine');

        $qb->where('ticketLine.originRestaurantId = :restaurantId')
            ->andWhere('ticketLine.product = :plu')
            ->andWhere('ticketLine.mvmtRecorded = false')
            ->andWhere('ticketLine.date >= :startDate and ticketLine.date <= :endDate')
            ->setParameter('restaurantId', $restaurantId)
            ->setParameter('plu', $productSold->getCodePlu())
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);
        $result = $qb->getQuery()->getResult();

        return $result;
    }


}