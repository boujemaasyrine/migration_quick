<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 13/06/2016
 * Time: 14:53
 */

namespace AppBundle\Financial\Service;

use AppBundle\Financial\Entity\Ticket;
use AppBundle\Financial\Entity\TicketLine;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\RecipeLine;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\SoldingCanal;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;
use Monolog\Logger;

class RevenuePricesService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(EntityManager $em, Logger $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function calculateFinancialRevenueForTicketLine(TicketLine $ticketLine, Restaurant $restaurant)
    {
        $price = 0;
        /**
         * @var Ticket $ticket
         */
        $ticket = $ticketLine->getTicket();
        if ($ticket->getStatus() != Ticket::CANCEL_STATUS_VALUE && $ticket->getStatus() != Ticket::ABONDON_STATUS_VALUE) {
            $productSold = $recipe = $this->em->getRepository('Merchandise:ProductSold')
                ->findOneBy(['codePlu' => $ticketLine->getPlu(), 'originRestaurant' => $restaurant, 'active'=>true]);

            if ($productSold) {
                $restaurantReusable= $restaurant->isReusable();
                if($restaurantReusable== true){
                    $subsoldingCanal= 2;
                }else{
                    $subsoldingCanal= 1;
                }
                $defaultSoldingCanal = null;
                $soldingCanal = null;
                try {
                    $soldingCanal = $recipe = $this->em->getRepository('Merchandise:SoldingCanal')
                        ->createQueryBuilder('soldingCanal')
                        ->where('LOWER(soldingCanal.wyndMppingColumn)  LIKE :destination')
                        ->setParameter('destination', '%"'.strtolower($ticket->getDestination()).'"%')
                        ->setMaxResults(1)
                        ->getQuery()
                        ->getSingleResult();
                    if ($productSold->getDefaultRecipe()) {
                        $defaultSoldingCanal = $productSold->getDefaultRecipe()->getSoldingCanal();
                    }
                } catch (NoResultException $e) {
                    if ($productSold->getDefaultRecipe()) {
                        $defaultSoldingCanal = $productSold->getDefaultRecipe()->getSoldingCanal();
                    } else {
                        $defaultSoldingCanal = null;
                    }
                }
                if ($soldingCanal || $defaultSoldingCanal) {

                    if ($productSold->getType() == ProductSold::TRANSFORMED_PRODUCT) {
                        if($soldingCanal != null)
                        {
                            $recipe = $this->em->getRepository('Merchandise:Recipe')
                                ->getRecipeItemForAllCanals($productSold, $soldingCanal,$subsoldingCanal);

                            if ($recipe) {
                                foreach ($recipe->getRecipeLines() as $recipeLine) {
                                    $elementary_price = 0;
                                    /**
                                     * @var RecipeLine $recipeLine
                                     */
                                    $qty = ($ticketLine->getQty() * $recipeLine->getQty()) / $recipeLine->getProductPurchased()->getUsageQty();
                                    $elementary_price = $qty * ($recipeLine->getProductPurchased()->getBuyingCost() / $recipeLine->getProductPurchased()->getInventoryQty()) ;
                                    $price += $elementary_price;
                                }
                            } elseif ($defaultSoldingCanal) {
                                $defaultRecipe = $this->em->getRepository('Merchandise:Recipe')
                                    ->getRecipeItemForAllCanals($productSold, $defaultSoldingCanal,$subsoldingCanal);
                                if ($defaultRecipe) {
                                    foreach ($defaultRecipe->getRecipeLines() as $defaultRecipeLine) {
                                        /**
                                         * @var RecipeLine $defaultRecipeLine
                                         */
                                        $qty = ($ticketLine->getQty() * $defaultRecipeLine->getQty()) / $defaultRecipeLine->getProductPurchased()->getUsageQty();
                                        $elementary_price = $qty * ($defaultRecipeLine->getProductPurchased()->getBuyingCost() / $defaultRecipeLine->getProductPurchased()->getInventoryQty()) ;
                                        $price += $elementary_price;
                                    }
                                }
                            }
                        }
                        else
                        {
                            $defaultRecipe = $this->em->getRepository('Merchandise:Recipe')
                                ->getRecipeItemForAllCanals($productSold, $defaultSoldingCanal,$subsoldingCanal);
                            if ($defaultRecipe) {
                                foreach ($defaultRecipe->getRecipeLines() as $defaultRecipeLine) {
                                    /**
                                     * @var RecipeLine $defaultRecipeLine
                                     */
                                    $qty = ($ticketLine->getQty() * $defaultRecipeLine->getQty()) / $defaultRecipeLine->getProductPurchased()->getUsageQty();
                                    $elementary_price = $qty * ($defaultRecipeLine->getProductPurchased()->getBuyingCost() / $defaultRecipeLine->getProductPurchased()->getInventoryQty()) ;
                                    $price += $elementary_price;
                                }
                            }
                        }

                    } else {
                        // NON transformed product sold.
                        $pp = $productSold->getProductPurchased();
                        $price = ($pp->getBuyingCost() / ($pp->getInventoryQty() * $pp->getUsageQty())) * $ticketLine->getQty();
                    }
                }
            }

        }

        return $price;
    }
}
