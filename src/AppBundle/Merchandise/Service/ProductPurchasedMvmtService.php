<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 13/06/2016
 * Time: 13:11
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\Administration\Entity\Parameter;
use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Merchandise\Entity\Delivery;
use AppBundle\Merchandise\Entity\DeliveryLine;
use AppBundle\Merchandise\Entity\InventoryLine;
use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductPurchasedHistoric;
use AppBundle\Merchandise\Entity\ProductPurchasedMvmt;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\RecipeLine;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\ReturnLine;
use AppBundle\Merchandise\Entity\Returns;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Merchandise\Entity\Transfer;
use AppBundle\Merchandise\Entity\TransferLine;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Monolog\Logger;
use Symfony\Component\Translation\Translator;

class ProductPurchasedMvmtService
{

    private $em;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(EntityManager $entityManager, Logger $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
    }

    /**
     * @param $mvtType
     * @param $variation => In inventory unit
     * @param $product
     * @param $sourceId
     * @param null $stockQty
     * @param bool $canFlush
     * @throws \Exception
     */
    public function createMvmtEntry($dateTime, $mvtType, $restaurant, $variation, $product, $sourceId, $stockQty = null, $canFlush = true)
    {
        try {
            $this->logger->addInfo("mvtType : " . $mvtType . ';variation : ' . $variation . ' ;product : ' . $product->getId() . ' ;sourceId : ' . $sourceId . ' ;stockQty : ' . $stockQty . ' ;canFlush :' . $canFlush, ['ProductPurchasedMvmtService:createMvmtEntry']);
            $mvmt = new ProductPurchasedMvmt();
            $mvmt->setProductInformations($product)->setStockQty($stockQty)->setVariation(ProductPurchasedMvmt::$variationDirection[$mvtType] * floatval($variation))->setSourceId($sourceId)->setDateTime($dateTime)->setOriginRestaurant($restaurant)->setType($mvtType);
            if ($product instanceof ProductPurchased) {
                $mvmt->setProduct($product);
            } elseif ($product instanceof ProductPurchasedHistoric) {
                /**
                 * @var ProductPurchasedHistoric $product
                 */
                $mvmt->setProduct($this->em->getReference('Merchandise:ProductPurchased', $product->getOriginalID()));
            }

            $this->em->persist($mvmt);
            if ($canFlush) {
                $this->em->flush();
            }
        } catch (\Exception $e) {
            $this->logger->addAlert($e->getMessage(), ['ProductPurchasedMvmtService:createMvmtEntry']);
            throw $e;
        }
    }

    /**
     * @param $mvtType
     * @param $variation => In inventory unit
     * @param $product
     * @param $sourceId
     * @param null $stockQty
     * @param bool $canFlush
     * @throws \Exception
     */
    public function createMvmtEntryForCommand($dateTime, $mvtType, $restaurant, $variation, $product, $sourceId, $stockQty = null, $canFlush = true,$unique='')
    {
        try {
          if($this->checkMvmtNotcreated($sourceId,$product,$restaurant)){
              $this->logger->addInfo("mvtType : " . $mvtType . ';variation : ' . $variation . ' ;product : ' . $product->getId() . ' ;sourceId : ' . $sourceId . ' ;stockQty : ' . $stockQty . ' ;canFlush :' . $canFlush .' unique ='.$unique, ['ProductPurchasedMvmtService:createMvmtEntry']);
              $mvmt = new ProductPurchasedMvmt();
              $mvmt->setProductInformations($product)->setStockQty($stockQty)->setVariation(ProductPurchasedMvmt::$variationDirection[$mvtType] * floatval($variation))->setSourceId($sourceId)->setDateTime($dateTime)->setOriginRestaurant($restaurant)->setType($mvtType);
              if ($product instanceof ProductPurchased) {
                  $mvmt->setProduct($product);
              } elseif ($product instanceof ProductPurchasedHistoric) {
                  /**
                   * @var ProductPurchasedHistoric $product
                   */
                  $mvmt->setProduct($this->em->getReference('Merchandise:ProductPurchased', $product->getOriginalID()));
              }
              $this->em->persist($mvmt);
              if ($canFlush) {
                  $this->em->flush();
              }
          }else{
              $this->logger->addInfo('This mvmt is created source id='.$sourceId .'product=' .$product->getId().' restaurant='.$restaurant->getCode().' unique ='.$unique, ['ProductPurchasedMvmtService:createMvmtEntry']);
          }

        } catch (\Exception $e) {
            $this->logger->addAlert($e->getMessage().' unique ='.$unique, ['ProductPurchasedMvmtService:createMvmtEntry']);
            throw $e;
        }
    }
    private function checkMvmtNotcreated($line, $productPurchased,$restaurant){
      $mvmts=  $this->em->getRepository(ProductPurchasedMvmt::class)->findOneBy(array(
            'product'=>$productPurchased,
            'originRestaurant'=>$restaurant,
            'sourceId'=>$line
        ));
      if(is_object($mvmts)){
          return false;
      }
      return true;
    }

    // Tickets
    public function createMvmtEntryForTicketLine(TicketLine $line, $restaurant, $ticket=null)
    {
        if(!is_object($ticket)){
            $ticket = $line->getTicket();
        }

        if ($ticket->getStatus() != Ticket::CANCEL_STATUS_VALUE && $ticket->getStatus() != Ticket::ABONDON_STATUS_VALUE) {
            $this->logger->addInfo('Searching for product sold with plu : ' . $line->getPlu());
            $productSold = $recipe = $this->em->getRepository('Merchandise:ProductSold')->findOneBy(['codePlu' => $line->getPlu(), 'originRestaurant' => $restaurant,'active'=>true]);
            if ($productSold) {
                $restaurantReusable= $restaurant->isReusable();
                $this->logger->info('Le restaurant'.  $restaurant->getCode(). 'est '. $restaurantReusable, ['RevenuePricesService: calculateFinancialRevenueForTicketLine']);
                if($restaurantReusable== true){
                    $subsoldingCanal= 2;
                }else{
                    $subsoldingCanal= 1;
                }
                $this->logger->addInfo('Searching for solding Canal with label : ' . $ticket->getDestination());
                $defaultSoldingCanal = null;
                $soldingCanal = null;
                try {
                    $soldingCanal = $recipe = $this->em->getRepository('Merchandise:SoldingCanal')
                        ->createQueryBuilder('soldingCanal')
                        ->where('LOWER(soldingCanal.wyndMppingColumn)  LIKE :destination')
                        ->andWhere('soldingCanal.type = :type')
                        ->setParameter('destination', '%"'.strtolower($ticket->getDestination()).'"%')
                        ->setParameter('type', SoldingCanal::DESTINATION)
                        ->setMaxResults(1)->getQuery()->getSingleResult();
                    $this->logger->addInfo('Solding canal with label : ' . $ticket->getDestination() . ' is found.');
                    if ($productSold->getDefaultRecipe()) {
                        $defaultSoldingCanal = $productSold->getDefaultRecipe()->getSoldingCanal();
                    }
                } catch (NoResultException $e) {
                    $this->logger->addInfo('Solding Canal for label : ' . $ticket->getDestination() . ' is not found.', ['createMvmtEntryForTicketLine']);
                    $this->logger->addInfo('Getting the solding canal from the default recipe for plu : ' . $line->getPlu() . ', product : ' . $productSold->getId() . '.', ['createMvmtEntryForTicketLine']);
                    if ($productSold->getDefaultRecipe()) {
                        $defaultSoldingCanal = $productSold->getDefaultRecipe()->getSoldingCanal();
                    } else {
                        $defaultSoldingCanal = null;
                        $this->logger->addInfo('No default recipe for plu : ' . $line->getPlu() . '.', ['createMvmtEntryForTicketLine']);
                    }
                }
                if ($soldingCanal || $defaultSoldingCanal) {
                    // TODO : Tenir compte de l'historique des produits dans un cas plus complexe selon la date du ticket
                    if ($productSold->getType() == ProductSold::TRANSFORMED_PRODUCT) {
                        if ($soldingCanal != null) {
                            $this->logger->addInfo('Searching for recipe that match solding canal : ' . $soldingCanal->getId() . '.', ['createMvmtEntryForTicketLine']);
                            $this->logger->addInfo('product_id : ' . $productSold->getId() . ', soldingCanal: ' . $soldingCanal->getId() . ', subsoldingCanal: ' . $subsoldingCanal);
                            $recipe = $this->em->getRepository('Merchandise:Recipe')->getRecipeItemForAllCanals($productSold, $soldingCanal, $subsoldingCanal);
                            if ($recipe) {
                                $this->logger->addInfo('Processing mvmt creation.', ['createMvmtEntryForTicketLine']);
                                foreach ($recipe->getRecipeLines() as $recipeLine) {
                                    /**
                                     * @var RecipeLine $recipeLine
                                     */
                                    $qty = ($line->getQty() * $recipeLine->getQty()) / $recipeLine->getProductPurchased()->getUsageQty();
                                    $this->createMvmtEntry($ticket->getDate(), ProductPurchasedMvmt::SOLD_TYPE, $restaurant, $qty, $recipeLine->getProductPurchased(), $line->getId(), null, false);
                                }
                                $line->setMvmtRecorded(true);
                            } elseif ($defaultSoldingCanal) {
                                $this->logger->addInfo('Searching for recipe that match default solding canal : ' . $defaultSoldingCanal->getId() . '.', ['createMvmtEntryForTicketLine']);
                                $this->logger->addInfo('product_id : ' . $productSold->getId() . ', soldingCanal: ' . $defaultSoldingCanal->getId() . ', subsoldingCanal: ' . $subsoldingCanal);
                                $defaultRecipe = $this->em->getRepository('Merchandise:Recipe')->getRecipeItemForAllCanals($productSold, $defaultSoldingCanal, $subsoldingCanal);
                                if ($defaultRecipe) {
                                    $this->logger->addInfo('Processing mvmt creation.', ['createMvmtEntryForTicketLine']);
                                    foreach ($defaultRecipe->getRecipeLines() as $defaultRecipeLine) {
                                        /**
                                         * @var RecipeLine $defaultRecipeLine
                                         */
                                        $qty = ($line->getQty() * $defaultRecipeLine->getQty()) / $defaultRecipeLine->getProductPurchased()->getUsageQty();
                                        $this->createMvmtEntry($ticket->getDate(), ProductPurchasedMvmt::SOLD_TYPE, $restaurant, $qty, $defaultRecipeLine->getProductPurchased(), $line->getId(), null, false);
                                    }
                                    $line->setMvmtRecorded(true);
                                } else {
                                    $line->setMvmtRecorded(false);
                                    $this->logger->addAlert('Default Recipe for plu : ' . $line->getPlu() . ' and for default canal with id ' . $defaultSoldingCanal->getId() . ' is not found (' . $defaultSoldingCanal->getLabel() . ').', ['createMvmtEntryForTicketLine']);
                                }
                            } else {
                                $line->setMvmtRecorded(false);
                                $this->logger->addAlert('Recipe for plu : ' . $line->getPlu() . ' and for canal with id ' . $soldingCanal->getId() . ' is not found (' . $soldingCanal->getLabel() . ').', ['createMvmtEntryForTicketLine']);
                            }
                        } else {
                            if ($defaultSoldingCanal) {
                                $this->logger->addInfo('Searching for recipe that match default solding canal : ' . $defaultSoldingCanal->getId() . '.', ['createMvmtEntryForTicketLine']);
                                $this->logger->addInfo('product_id : ' . $productSold->getId() . ', soldingCanal: ' . $defaultSoldingCanal->getId() . ', subsoldingCanal: ' . $subsoldingCanal);
                                $defaultRecipe = $this->em->getRepository('Merchandise:Recipe')->getRecipeItemForAllCanals($productSold, $defaultSoldingCanal, $subsoldingCanal);
                                if ($defaultRecipe) {
                                    $this->logger->addInfo('Processing mvmt creation.', ['createMvmtEntryForTicketLine']);
                                    foreach ($defaultRecipe->getRecipeLines() as $defaultRecipeLine) {
                                        /**
                                         * @var RecipeLine $defaultRecipeLine
                                         */
                                        $qty = ($line->getQty() * $defaultRecipeLine->getQty()) / $defaultRecipeLine->getProductPurchased()->getUsageQty();
                                        $this->createMvmtEntry($ticket->getDate(), ProductPurchasedMvmt::SOLD_TYPE, $restaurant, $qty, $defaultRecipeLine->getProductPurchased(), $line->getId(), null, false);
                                    }
                                    $line->setMvmtRecorded(true);
                                } else {
                                    $line->setMvmtRecorded(false);
                                    $this->logger->addAlert('Default Recipe for plu : ' . $line->getPlu() . ' and for default canal with id ' . $defaultSoldingCanal->getId() . ' is not found (' . $defaultSoldingCanal->getLabel() . ').', ['createMvmtEntryForTicketLine']);
                                }
                            } else {
                                $line->setMvmtRecorded(false);
                                $this->logger->addAlert('Recipe for plu : ' . $line->getPlu() . ' and for canal with id ' . $soldingCanal->getId() . ' is not found (' . $soldingCanal->getLabel() . ').', ['createMvmtEntryForTicketLine']);
                            }
                        }

                    } else {
                        // NON transformed product sold.
                        $qty = $line->getQty() / $productSold->getProductPurchased()->getUsageQty();
                        $this->createMvmtEntry($ticket->getDate(), ProductPurchasedMvmt::SOLD_TYPE, $restaurant, $qty, $productSold->getProductPurchased(), $line->getId(), null, false);
                        $line->setMvmtRecorded(true);
                    }
                } else {
                    $line->setMvmtRecorded(false);
                    if (!$soldingCanal) {
                        $this->logger->addAlert('Solding Canal for label : ' . $ticket->getDestination() . ' is not found.', ['createMvmtEntryForTicketLine']);
                    }
                }
            } else {
                $this->logger->addAlert('Product for plu : ' . $line->getPlu() . ' is not found.', ['createMvmtEntryForTicketLine']);
            }
        } else {
            $this->logger->addAlert('Ticket with status : ' . $ticket->getStatus(), ['createMvmtEntryForTicketLine']);
        }
    }


    // Tickets
    public function createMvmtEntryForTicketLineFromCommand(TicketLine $line,Restaurant $restaurant, $ticket=null,ProductSold $productSold, ProductPurchased $productPurchased,$unique='')
    {
        if(!is_object($ticket)){
            $ticket = $line->getTicket();
        }

        if ($ticket->getStatus() != Ticket::CANCEL_STATUS_VALUE && $ticket->getStatus() != Ticket::ABONDON_STATUS_VALUE) {
            $this->logger->addInfo('Searching for product sold with plu : ' . $line->getPlu().' unique '.$unique);
            if ($productSold) {
                $restaurantReusable= $restaurant->isReusable();
                $this->logger->info('Le restaurant'.  $restaurant->getCode(). 'est '. $restaurantReusable, ['RevenuePricesService: calculateFinancialRevenueForTicketLine']);
                if($restaurantReusable== true){
                    $subsoldingCanal= 2;
                }else{
                    $subsoldingCanal= 1;
                }
                $this->logger->addInfo('Searching for solding Canal with label : ' . $ticket->getDestination().' unique '.$unique);
                $defaultSoldingCanal = null;
                $soldingCanal = null;
                try {
                    $soldingCanal = $this->em->getRepository('Merchandise:SoldingCanal')
                        ->createQueryBuilder('soldingCanal')
                        ->where('LOWER(soldingCanal.wyndMppingColumn)  LIKE :destination')
                        ->andWhere('soldingCanal.type = :type')
                        ->setParameter('destination', '%"'.strtolower($ticket->getDestination()).'"%')
                        ->setParameter('type', SoldingCanal::DESTINATION)
                        ->setMaxResults(1)->getQuery()->getSingleResult();
                    $this->logger->addInfo('Solding canal with label : ' . $ticket->getDestination() . ' is found.'.' unique '.$unique);
                    if ($productSold->getDefaultRecipe()) {
                        $defaultSoldingCanal = $productSold->getDefaultRecipe()->getSoldingCanal();
                    }
                } catch (NoResultException $e) {
                    $this->logger->addInfo('Solding Canal for label : ' . $ticket->getDestination() . ' is not found.'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
                    $this->logger->addInfo('Getting the solding canal from the default recipe for plu : ' . $line->getPlu() . ', product : ' . $productSold->getId() . '.'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
                    if ($productSold->getDefaultRecipe()) {
                        $defaultSoldingCanal = $productSold->getDefaultRecipe()->getSoldingCanal();
                    } else {
                        $defaultSoldingCanal = null;
                        $this->logger->addInfo('No default recipe for plu : ' . $line->getPlu() . '.'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
                    }
                }
                if ($soldingCanal || $defaultSoldingCanal) {
                    // TODO : Tenir compte de l'historique des produits dans un cas plus complexe selon la date du ticket
                    if ($productSold->getType() == ProductSold::TRANSFORMED_PRODUCT) {
                        if ($soldingCanal != null) {
                            $this->logger->addInfo('Searching for recipe that match solding canal : ' . $soldingCanal->getId() . '.'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
                            $recipe = $this->em->getRepository('Merchandise:Recipe')->getRecipeItemForAllCanals($productSold, $soldingCanal, $subsoldingCanal);
                            if ($recipe) {
                                $this->logger->addInfo('Processing mvmt creation.'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
                                foreach ($recipe->getRecipeLines() as $recipeLine) {
                                    /**
                                     * @var RecipeLine $recipeLine
                                     */
                                    if($productPurchased->getId()==$recipeLine->getProductPurchased()->getId()){
                                        $qty = ($line->getQty() * $recipeLine->getQty()) / $recipeLine->getProductPurchased()->getUsageQty();
                                        $this->createMvmtEntryForCommand($ticket->getDate(), ProductPurchasedMvmt::SOLD_TYPE, $restaurant, $qty, $recipeLine->getProductPurchased(), $line->getId(), null, true,$unique);
                                    }
                                }
                            } elseif ($defaultSoldingCanal) {
                                $this->logger->addInfo('Searching for recipe that match default solding canal : ' . $defaultSoldingCanal->getId() . '.'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
                                $defaultRecipe = $this->em->getRepository('Merchandise:Recipe')->getRecipeItemForAllCanals($productSold, $defaultSoldingCanal, $subsoldingCanal);
                                if ($defaultRecipe) {
                                    $this->logger->addInfo('Processing mvmt creation.'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
                                    foreach ($defaultRecipe->getRecipeLines() as $defaultRecipeLine) {
                                        if($productPurchased->getId()==$defaultRecipeLine->getProductPurchased()->getId()){
                                            /**
                                             * @var RecipeLine $defaultRecipeLine
                                             */
                                            $qty = ($line->getQty() * $defaultRecipeLine->getQty()) / $defaultRecipeLine->getProductPurchased()->getUsageQty();
                                            $this->createMvmtEntryForCommand($ticket->getDate(), ProductPurchasedMvmt::SOLD_TYPE, $restaurant, $qty, $defaultRecipeLine->getProductPurchased(), $line->getId(), null, true,$unique);
                                        }
                                   }
                                } else {
                                    $this->logger->addAlert('Default Recipe for plu : ' . $line->getPlu() . ' and for default canal with id ' . $defaultSoldingCanal->getId() . ' is not found (' . $defaultSoldingCanal->getLabel() . ').'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
                                }
                            } else {
                                $this->logger->addAlert('Recipe for plu : ' . $line->getPlu() . ' and for canal with id ' . $soldingCanal->getId() . ' is not found (' . $soldingCanal->getLabel() . ').'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
                            }
                        } else {
                            if ($defaultSoldingCanal) {
                                $this->logger->addInfo('Searching for recipe that match default solding canal : ' . $defaultSoldingCanal->getId() . '.'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
                                $defaultRecipe = $this->em->getRepository('Merchandise:Recipe')->getRecipeItemForAllCanals($productSold, $defaultSoldingCanal, $subsoldingCanal);
                                if ($defaultRecipe) {
                                    $this->logger->addInfo('Processing mvmt creation.', ['createMvmtEntryForTicketLine']);
                                    foreach ($defaultRecipe->getRecipeLines() as $defaultRecipeLine) {
                                        if($productPurchased->getId()==$defaultRecipeLine->getProductPurchased()->getId()){
                                            /**
                                             * @var RecipeLine $defaultRecipeLine
                                             */
                                            $qty = ($line->getQty() * $defaultRecipeLine->getQty()) / $defaultRecipeLine->getProductPurchased()->getUsageQty();
                                            $this->createMvmtEntryForCommand($ticket->getDate(), ProductPurchasedMvmt::SOLD_TYPE, $restaurant, $qty, $defaultRecipeLine->getProductPurchased(), $line->getId(), null, true,$unique);
                                        }
                                   }
                                } else {
                                    $this->logger->addAlert('Default Recipe for plu : ' . $line->getPlu() . ' and for default canal with id ' . $defaultSoldingCanal->getId() . ' is not found (' . $defaultSoldingCanal->getLabel() . ').'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
                                }
                            } else {
                                $this->logger->addAlert('Recipe for plu : ' . $line->getPlu() . ' and for canal with id ' . $soldingCanal->getId() . ' is not found (' . $soldingCanal->getLabel() . ').'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
                            }
                        }

                    } else {
                        // NON transformed product sold.
                        $this->logger->addAlert('Product for plu : ' . $line->getPlu() . ' is not transformed.'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
                    }
                } else {
                    if (!$soldingCanal) {
                        $this->logger->addAlert('Solding Canal for label : ' . $ticket->getDestination() . ' is not found.'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
                    }
                }
            } else {
                $this->logger->addAlert('Product for plu : ' . $line->getPlu() . ' is not found.'.' unique '.$unique, ['createMvmtEntryForTicketLine']);
            }
        } else {
            $this->logger->addAlert('Ticket with status : ' . $ticket->getStatus().' unique '.$unique, ['createMvmtEntryForTicketLine']);
        }
    }



    /**
     * @param Ticket $ticket
     * @throws \Exception
     */
    public function createMvmtEntryForTicket(Ticket $ticket, $restaurant)
    {
        $this->logger->addInfo('ProductPurchasedMvmtService:createMvmtEntryForTicket for ticket :' . $ticket->getId(), ['ProductPurchasedMvmtService']);
        if ($ticket->getStatus() != Ticket::CANCEL_STATUS_VALUE && $ticket->getStatus() != Ticket::ABONDON_STATUS_VALUE) {
            foreach ($ticket->getLines() as $line) {
                $this->createMvmtEntryForTicketLine($line, $restaurant,$ticket);
            }
        } else {
            $this->logger->addAlert('Ticket with status : ' . $ticket->getStatus(), ['createMvmtEntryForTicketLine']);
        }
    }

    public function createMvmtEntryForExistingNonRecordedTicketLine($restaurant)
    {
        //Get Ticket paginated not uploaded
        $total = $this->em->getRepository("Financial:TicketLine")->createQueryBuilder('ticketLine')->select('count(ticketLine)')->leftJoin('ticketLine.ticket', 'ticket')->where("ticketLine.mvmtRecorded = :mvmtRecorded")->andWhere('ticket.countedCanceled <> TRUE')->andWhere('ticket.originRestaurant=:restaurant')->setParameter('restaurant', $restaurant)->andWhere('ticket.status != :canceled')->setParameter('canceled', Ticket::CANCEL_STATUS_VALUE)->andWhere('ticket.status != :abondoned')->setParameter('abondoned', Ticket::ABONDON_STATUS_VALUE)->setParameter('mvmtRecorded', false)->andWhere('ticketLine.isDiscount = false')->getQuery()->getSingleScalarResult();
        $this->logger->addInfo('Try to record ' . $total . ' lines in mvmt.', ['RecordMvmOnTickets']);
        $max_per_page = 50;
        $pages = ceil($total / $max_per_page);
        $this->logger->info('Pages : ' . $pages . ' , max per page : ' . $max_per_page, ['RecordMvmOnTickets']);
        $exit = false;
        while ($total > 0 && !$exit) {
            $startMvmtCount = $this->em->getRepository('Merchandise:ProductPurchasedMvmt')->createQueryBuilder('productPurchasedMvmt')->select('count(productPurchasedMvmt)')->where('productPurchasedMvmt.type = :sold')->setParameter('sold', ProductPurchasedMvmt::SOLD_TYPE)->getQuery()->getSingleScalarResult();
            $lines = $this->em->getRepository("Financial:TicketLine")->createQueryBuilder('ticketLine')->select('ticketLine')->leftJoin('ticketLine.ticket', 'ticket')->where("ticketLine.mvmtRecorded = :mvmtRecorded")->setParameter('mvmtRecorded', false)->andWhere('ticket.countedCanceled <> TRUE')->andWhere('ticket.status != :canceled')->setParameter('canceled', Ticket::CANCEL_STATUS_VALUE)->andWhere('ticket.status != :abondoned')->setParameter('abondoned', Ticket::ABONDON_STATUS_VALUE)->andWhere('ticketLine.isDiscount = false')->setMaxResults($max_per_page)->getQuery()->getResult();
            foreach ($lines as $line) {
                $this->createMvmtEntryForTicketLine($line, $restaurant);
            }
            $this->em->flush();
            $this->em->clear();
            $afterMvmtCount = $this->em->getRepository('Merchandise:ProductPurchasedMvmt')->createQueryBuilder('productPurchasedMvmt')->select('count(productPurchasedMvmt)')->where('productPurchasedMvmt.type = :sold')->setParameter('sold', ProductPurchasedMvmt::SOLD_TYPE)->getQuery()->getSingleScalarResult();
            $exit = $startMvmtCount == $afterMvmtCount;
            $total = $this->em->getRepository("Financial:TicketLine")->createQueryBuilder('ticketLine')->select('count(ticketLine)')->leftJoin('ticketLine.ticket', 'ticket')->where("ticketLine.mvmtRecorded = :mvmtRecorded")->andWhere('ticket.countedCanceled <> TRUE')->andWhere('ticket.status != :canceled')->setParameter('canceled', Ticket::CANCEL_STATUS_VALUE)->andWhere('ticket.status != :abondoned')->setParameter('abondoned', Ticket::ABONDON_STATUS_VALUE)->setParameter('mvmtRecorded', false)->andWhere('ticketLine.isDiscount = false')->getQuery()->getSingleScalarResult();
        }
        return true;
    }

    /**
     * @param InventorySheet $inventorySheet
     * @param bool $canFlush
     * @throws \Exception
     */
    public function createMvmtEntryForInventorySheet(InventorySheet $inventorySheet, $restaurant, $canFlush = true)
    {
        if (count($inventorySheet->getLines())) {
            foreach ($inventorySheet->getLines() as $line) {
                /**
                 * @var InventoryLine $line
                 */
                $product = $line->getProductPurchasedHistoric() ? $line->getProductPurchasedHistoric() : $line->getProduct();
                $this->createMvmtEntry($inventorySheet->getFiscalDate(), ProductPurchasedMvmt::INVENTORY_TYPE, $restaurant, 0, $product, $line->getId(), $line->getTotalInventoryCnt(), $canFlush);
            }
        }
    }

    public function createMvmtEntryForLossSheet(LossSheet $lossSheet, $restaurant, $canFlush = true)
    {
        if (count($lossSheet->getLossLines())) {
            foreach ($lossSheet->getLossLines() as $line) {
                /**
                 * @var LossLine $line
                 */
                if ($lossSheet->getType() == LossSheet::ARTICLE) {
                    $this->createMvmtEntry($lossSheet->getEntryDate(), ProductPurchasedMvmt::PURCHASED_LOSS_TYPE, $restaurant, $line->getTotalLoss(), $line->getProduct(), $line->getId(), null, $canFlush);
                } else {
                    $ps = $line->getProduct();
                    /**
                     * @var ProductSold $ps
                     */
                    if ($ps->getProductPurchased()) {
                        // NON TRANSFORMED PRODUCT
                        $qty = $line->getTotalLoss() / $ps->getProductPurchased()->getUsageQty();
                        $this->createMvmtEntry($lossSheet->getEntryDate(), ProductPurchasedMvmt::SOLD_LOSS_TYPE, $restaurant, $qty, $ps->getProductPurchased(), $line->getId(), null, $canFlush);
                    } else {
                        $subsoldingCanal = $restaurant->isReusable() ? 2 : 1;
                        $soldingCanal =  $this->em->getRepository(SoldingCanal::class)->findOneBy(array('type' => SoldingCanal::DESTINATION), array('default' => 'DESC'));
                        $selectedRecipe = $this->em->getRepository('Merchandise:Recipe')->getRecipeItemForAllCanals($line->getProduct(), $soldingCanal, $subsoldingCanal);
                        $selectedRecipeHis = $this->em->getRepository('Merchandise:RecipeHistoric')->getRecipeHistItemForAllCanals($line->getProduct(), $soldingCanal, $subsoldingCanal);
                        if ($selectedRecipeHis != null) {
                            $lossSheetDate = $line->getLossSheet()->getEntryDate();
                            $dateRecipe = $line->getRecipeHistoric()->getProductSold()->getStartDate();
                            if ($lossSheetDate <= $dateRecipe) {
                                $recipe = $selectedRecipeHis;
                            } else {
                                $recipe = $selectedRecipe;
                            }

                            foreach ($recipe->getRecipeLines() as $recipeLine) {
                                /**
                                 * @var RecipeLine $recipeLine
                                 */
                                $qty = ($line->getTotalLoss() * $recipeLine->getQty()) / $recipeLine->getProductPurchased()->getUsageQty();
                                $this->createMvmtEntry($lossSheet->getEntryDate(), ProductPurchasedMvmt::SOLD_LOSS_TYPE, $restaurant, $qty, $recipeLine->getProductPurchased(), $line->getId(), null, false);
                            }
                        } elseif ($line->getRecipe()) {

                            $recipe = $this->getRecipeForProduct($restaurant, $line->getProduct());

                            if ($recipe) {
                                foreach ($recipe->getRecipeLines() as $recipeLine) {
                                    /**
                                     * @var RecipeLine $recipeLine
                                     */
                                    $qty = ($line->getTotalLoss() * $recipeLine->getQty()) / $recipeLine->getProductPurchased()->getUsageQty();
                                    $this->createMvmtEntry($lossSheet->getEntryDate(), ProductPurchasedMvmt::SOLD_LOSS_TYPE, $restaurant, $qty, $recipeLine->getProductPurchased(), $line->getId(), null, false);
                                }
                            } else {
                                $this->logger->addAlert('No appropriate recipe found for lossline id: ' . $line->getId(), ['ImportWyndCommand']);
                            }
                        } else {
                            $this->logger->addAlert('Lossline id : ' . $line->getId() . ' has neither product purchased nor recipe nor recipe historic !', ['ImportWyndCommand']);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Restaurant $restaurant
     * @param Product $product
     * @return Recipe|null
     */
    public function getRecipeForProduct(Restaurant $restaurant, Product $product)
    {
        $subsoldingCanal = $restaurant->isReusable() ? 2 : 1;
        $soldingCanal = $this->em->getRepository(SoldingCanal::class)->findOneBy(['type' => SoldingCanal::DESTINATION], ['default' => 'DESC']);
        $recipe = $this->em->getRepository('Merchandise:Recipe')->getRecipeItemForAllCanals($product, $soldingCanal, $subsoldingCanal);
        return $recipe;

    }


    public function createMvmtEntryForDelivery(Delivery $delivery, $restaurant, $canFlush = true)
    {
        if (count($delivery->getLines())) {
            foreach ($delivery->getLines() as $line) {
                /**
                 * @var DeliveryLine $line
                 */
                $pp = $line->getProduct();
                $qty = $line->getQty() * $pp->getInventoryQty();
                if ($line->getProduct()->getPrimaryItem() != null) {
                    // Secondary item quantity in usage unit
                    $secondaryUsage = $qty * $pp->getUsageQty();
                    // qty of the primary item in Inventory Unit
                    if ($line->getProduct()->getPrimaryItem()->getUsageQty() != 0) {
                        $qty = $secondaryUsage / $line->getProduct()->getPrimaryItem()->getUsageQty();
                        $this->createMvmtEntry($delivery->getDate(), ProductPurchasedMvmt::DELIVERY_TYPE, $restaurant, $qty, $line->getProduct()->getPrimaryItem(), $line->getId(), null, $canFlush);
                    }


                } else {
                    $this->createMvmtEntry($delivery->getDate(), ProductPurchasedMvmt::DELIVERY_TYPE, $restaurant, $qty, $line->getProduct(), $line->getId(), null, $canFlush);
                }
            }
        }
    }

    public function createMvmtEntryForReturn(Returns $returns, $restaurant, $canFlush = true)
    {
        if (count($returns->getLines())) {
            foreach ($returns->getLines() as $line) {
                /**
                 * @var ReturnLine $line
                 */
                $pp = $line->getProduct();
                $qty = $line->getTotal();
                $this->createMvmtEntry($returns->getDate(), ProductPurchasedMvmt::RETURNS_TYPE, $restaurant, $qty, $pp, $line->getId(), null, $canFlush);
            }
        }
    }

    public function createMvmtEntryForTransfer(Transfer $transfer, $restaurant, $canFlush = true)
    {
        if ($transfer->getType() == Transfer::TRANSFER_IN) {
            if (count($transfer->getLines())) {
                foreach ($transfer->getLines() as $line) {
                    /**
                     * @var TransferLine $line
                     */
                    $this->createMvmtEntry($transfer->getDateTransfer(), ProductPurchasedMvmt::TRANSFER_IN_TYPE, $restaurant, $line->getTotal(), $line->getProduct(), $line->getId(), null, $canFlush);
                }
            }
        } else {
            if (count($transfer->getLines())) {
                foreach ($transfer->getLines() as $line) {
                    /**
                     * @var TransferLine $line
                     */
                    $this->createMvmtEntry($transfer->getDateTransfer(), ProductPurchasedMvmt::TRANSFER_OUT_TYPE, $restaurant, $line->getTotal(), $line->getProduct(), $line->getId(), null, $canFlush);
                }
            }
        }
    }

    public function deleteMvmtEntriesByTypeAndSourceId($type, $sourceId,$originRestaurant=null)
    {
        if($originRestaurant!=null){
            $productPurchasedMvmts = $this->em->getRepository('Merchandise:ProductPurchasedMvmt')->findBy(['sourceId' => $sourceId, 'type' => $type, 'originRestaurant' => $originRestaurant]);
        }else{
            $productPurchasedMvmts = $this->em->getRepository('Merchandise:ProductPurchasedMvmt')->findBy(['sourceId' => $sourceId, 'type' => $type]);
        }
        if (count($productPurchasedMvmts)) {
            foreach ($productPurchasedMvmts as $productPurchasedMvmt) {
                $productPurchasedMvmt->setDeleted(true);
                $productPurchasedMvmt->setSynchronized(false);
                //$this->em->remove($productPurchasedMvmt);
            }
        }
    }
    
    //added by belsem
    public function checkLockedPortion($currentRestaurantId)
    {
        $currentRestaurant = $this->em->getRepository(Restaurant::class)->find($currentRestaurantId);
        if (!$currentRestaurant) {
            throw new \Exception("Restaurant not found");
        }
        $param = $this->em->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => 'portion_control',
                'originRestaurant' => $currentRestaurant,
            )
        );
        $now = new \DateTime('now');
        if (!$param || $param == null || $param->getValue() == 0) {
            if (!$param) {
                $param = new Parameter();
                $param->setType('portion_control');
                $param->setCreatedAt($now);
                $param->setUpdatedAt($now);
                $param->setValue(0);
                $param->setOriginRestaurant($currentRestaurant);
                $this->em->persist($param);
                $this->em->flush($param);
            }

        } else {
            if ($param->getValue() == 1) {
                $diffInSeconds = $now->getTimestamp() - $param->getUpdatedAt()->getTimestamp();

                if ($diffInSeconds > 700) {
                    $param->setValue(0);
                    $this->em->flush($param);
                }
            }
        }

        return $param;
    }


    
}
