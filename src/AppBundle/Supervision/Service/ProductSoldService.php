<?php
/**
 * Created by PhpStorm.
 * User: mchrif
 * Date: 11/03/2016
 * Time: 08:53
 */

namespace AppBundle\Supervision\Service;

use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\RecipeLine;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use AppBundle\Supervision\Entity\ProductSupervision;
use AppBundle\Supervision\Entity\RecipeLineSupervision;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class ProductSoldService
{
    private $em;
    private $tokenStorage;
    private $syncCmdEntryService;
    private $historicService;

    public function __construct(
        EntityManager $entityManager,
        TokenStorage $tokenStorage,
        SyncCmdCreateEntryService $syncCmdCreateEntryService,
        HistoricEntitiesService $historicEntities
    ) {
        $this->em = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->syncCmdEntryService = $syncCmdCreateEntryService;
        $this->historicService = $historicEntities;
    }

    /**
     * @param $criteria
     * @param $order
     * @param $offset
     * @param $limit
     * @return array
     */
    public function getProductsSold($criteria, $order, $offset, $limit)
    {
        $results = [
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => [],
        ];
        $products = $this->em->getRepository(ProductSoldSupervision::class)->getProductsSold(
            $criteria,
            $order,
            $offset,
            $limit
        );

        foreach ($products as $key => $product) {
            $tmpProduct = $this->em->getRepository(ProductSupervision::class)
                ->find($product['id']);
            $restaurantsNames = [];
            foreach ($tmpProduct->getRestaurants() as $restaurant) {
                $restaurantsNames[] = $restaurant->getName();
            }
            sort($restaurantsNames);
            $products[$key]['restaurants'] = implode(',', $restaurantsNames);
            $products[$key]['dateSynchro'] = is_null(
                $products[$key]['dateSynchro']
            ) ? '' : $products[$key]['dateSynchro']->format('Y-m-d');
            $products[$key]['lastDateSynchro'] = is_null(
                $products[$key]['lastDateSynchro']
            ) ? '' : $products[$key]['lastDateSynchro']->format('Y-m-d H:i:s');
        }

        $results['recordsTotal'] = $this->em->getRepository(ProductSoldSupervision::class)->getProductsSoldCount();
        $results['recordsFiltered'] = $this->em->getRepository(
            ProductSoldSupervision::class
        )->getFiltredProductsSoldCount(
            $criteria
        );
        $results['data'] = $products;

        return $results;
    }

    public function getProductsSoldOrdered($criteria, $order, $limit, $offset, $onlyList = false)
    {
        $soldItems = $this->em->getRepository(ProductSoldSupervision::class)->getProductsSoldOrdered(
            $criteria,
            $order,
            $offset,
            $limit,
            $onlyList
        );


        return $this->serializeProduct($soldItems);
    }

    public function getRecipeLinesOrdered($criteria, $order, $limit, $offset, $onlyList = false)
    {

        $recipeLines = $this->em->getRepository(RecipeLineSupervision::class)->getRecipeLinesOrdered(
            $criteria,
            $order,
            $offset,
            $limit,
            $onlyList
        );

        return $this->serializeRecipeLines($recipeLines);
    }

    public function serializeProduct($soldItems)
    {
        $result = [];
        foreach ($soldItems as $i) {
            /**
             * @var ProductSold $i
             */
            $restos = $i->getRestaurants()->toArray();
            usort(
                $restos,
                function ($a, $b) {
                    if ($a->getCode() == $b->getCode()) {
                        return 0;
                    }

                    return ($a->getCode() < $b->getCode()) ? -1 : 1;
                }
            );
            $restaurants = [];
            $restaurantsAsString = [];
            foreach ($restos as $resto) {
                $restaurantsAsString[] = "(".$resto->getCode().") ".$resto->getName();
                $restaurants[] = ['code' => $resto->getCode(), 'name' => $resto->getName()];
            }
            $result[] = array(
                'id' => $i->getId(),
                'codePlu' => $i->getCodePlu(),
                'name' => $i->getName(),
                'type' => $i->getType(),
                'active' => $i->getActive(),
                'restaurants' => json_encode($restaurants),
                'restaurantsAsString' => implode("\n", $restaurantsAsString),
                'dateSynchro' => $i->getDateSynchro() ? $i->getDateSynchro()->format('Y-m-d') : '',
                'lastDateSynchro' => $i->getLastDateSynchro() ? $i->getLastDateSynchro()->format('Y-m-d H:i:s') : '',
                'inventoryItem' => $i->getProductPurchased() != null ? $i->getProductPurchased()->getId() : '',
                'inventoryItemCode' => $i->getProductPurchased() != null ? $i->getProductPurchased()->getExternalId(
                ) : '',
                'inventoryItemName' => $i->getProductPurchased() != null ? $i->getProductPurchased()->getName() : '',
                'venteAnnexe'=> $i->isVenteAnnexe() != null? $i->isVenteAnnexe(): false,




            );
        }

        return $result;
    }

    public function serializeRecipeLines($recipeLines)
    {
        $result = [];

        foreach ($recipeLines as $line) {
            /**
             * @var RecipeLine $line
             */
            if (!is_null($line->getRecipe()) && !is_null($line->getRecipe()->getProductSold())) {
                $priceLine = $line->getProductPurchased()
                    ? $line->getProductPurchased()->getBuyingCost() / $line->getProductPurchased()->getInventoryQty()
                    / $line->getProductPurchased()->getUsageQty() * $line->getQty()
                    : 0;
                $result[] = array(
                    'id' => $line->getId(),
                    'recipeId' => $line->getRecipe()->getId(),
                    'soldItemId' => $line->getRecipe()->getProductSold()->getId(),
                    'soldItemPlu' => $line->getRecipe()->getProductSold()->getCodePlu(),
                    'soldItemName' => $line->getRecipe()->getProductSold()->getName(),
                    'canal' => $line->getRecipe()->getSoldingCanal(),
                    'sous_canal' => $line->getRecipe()->getSubSoldingCanal(),
                    'codeInventoryItem' => $line->getProductPurchased()
                        ? $line->getProductPurchased()->getExternalId() : null,
                    'codeInventoryName' => $line->getProductPurchased()
                        ? $line->getProductPurchased()->getName() : null,
                    'qty' => $line->getQty(),
                    'usageUnit' => $line->getProductPurchased()
                        ? $line->getProductPurchased()->getLabelUnitUsage() : null,
                    'linePrice' => $priceLine,
                    'recipePrice' => $line->getRecipe()->getRevenuePrice(),
                );
            }
        }

        return $result;
    }


    public function saveProductSold(ProductSoldSupervision $productSold)
    {
        foreach ($productSold->getRecipes() as $recipe) {
            /**
             * @var Recipe $recipe
             */
            if (count($recipe->getRecipeLines()) == 0 && $recipe->getId() == null) {
                $productSold->removeRecipe($recipe);
            }
        }
        $productSold->setName($productSold->getNameTranslation('fr'));
        if (is_null($productSold->getId())) {
            $this->em->persist($productSold);
            $productSold->setGlobalProductID($productSold->getId());
            foreach ($productSold->getRecipes() as $recipe) {
                if ($recipe->getId() == null) {
                    $this->em->persist($recipe);
                }
                $recipe->setActive(true);
                $recipe->setGlobalId($recipe->getId());
                $recipe->setRevenu();
            }
        } else {
            foreach ($productSold->getRecipes() as $recipe) {
                if ($recipe->getId() == null) {
                    $this->em->persist($recipe);
                    $recipe->setActive(true);
                    $recipe->setGlobalId($recipe->getId());
                    $recipe->setRevenu();
                }else{
                    foreach ($recipe->getRecipeLines() as $recipeLine) {
                        if (!$recipeLine->getQty() || !$recipeLine->getProductPurchased()) {
                            $recipe->removeRecipeLine($recipeLine);
                            $recipeLine->setRecipe(null);
                            $this->em->remove($recipeLine);
                        }
                        $recipe->setRevenu();
                    }
                }



            }
            /*
            $uow = $this->em->getUnitOfWork();
            $uow->computeChangeSets();
            $changes = $uow->getEntityChangeSet($productSold);
            if (count($changes) > 1 or (count($changes) == 1 and !array_key_exists('dateSynchro', $changes))) {
                $oldEntity = clone $productSold;
                foreach ($changes as $key => $change) {
                    $attribute = strtoupper(substr($key, 0, 1));
                    $attribute = 'set' . $attribute . substr($key, 1);
                    $oldEntity->$attribute($change['0']);
                }

                $recipes = [];
                foreach ($productSold->getRecipes() as $recipeKey => $recipe) {
                    $recipeChanges = $uow->getEntityChangeSet($recipe);
                    $oldRecipe = clone $recipe;
                    foreach ($recipeChanges as $key => $change) {
                        $attribute = strtoupper(substr($key, 0, 1));
                        $attribute = 'set' . $attribute . substr($key, 1);
                        $oldRecipe->$attribute($change['0']);
                    }
                    $recipeLines = [];
                    foreach ($recipe->getRecipeLines() as $recipeLineKey => $recipeLine) {
                        $recipeLinesChanges = $uow->getEntityChangeSet($recipeLine);
                        $oldRecipeLine = clone $recipeLine;
                        foreach ($recipeLinesChanges as $key => $change) {
                            $attribute = strtoupper(substr($key, 0, 1));
                            $attribute = 'set' . $attribute . substr($key, 1);
                            $oldRecipeLine->$attribute($change['0']);
                        }
                        $recipeLines[] = $oldRecipeLine;
                    }
                    $oldRecipe->setRecipeLines($recipeLines);
                    $recipes[] = $oldRecipe;
                }
                $oldEntity->setRecipes($recipes);
                $this->em->flush();
                //$this->historicService->createProductSoldHistoric($oldEntity);
            }*/
        }

        if ($productSold->getType() === ProductSold::NON_TRANSFORMED_PRODUCT) {
            $productPurchased = $productSold->getProductPurchased();
            foreach ($productSold->getRestaurants() as $restaurant) {
                if (!$productPurchased->getRestaurants()->contains($restaurant)) {
                    $productPurchased->addRestaurant($restaurant);
                    $this->syncCmdEntryService->createProductPurchasedEntry(
                        $productPurchased,
                        true,
                        $restaurant,
                        false
                    );
                }
            }
        } else {
            foreach ($productSold->getRecipes() as $recipe) {
                foreach ($recipe->getRecipeLines() as $recipeLine) {
                    $productPurchased = $recipeLine->getProductPurchased();
                        foreach ($productSold->getRestaurants() as $restaurant) {
                            if (!$productPurchased->getRestaurants()->contains($restaurant)) {
                                //  gestion des recettes
//                                if($productPurchased->isReusable() == true && $restaurant->isReusable()== true) {
                                    $productPurchased->addRestaurant($restaurant);
                                    $this->syncCmdEntryService->createProductPurchasedEntry(
                                        $productPurchased,
                                        true,
                                        $restaurant,
                                        false
                                    );
//                                }elseif($productPurchased->isReusable() == false && $restaurant->isReusable()== false){
//                                    $productPurchased->addRestaurant($restaurant);
//                                    $this->syncCmdEntryService->createProductPurchasedEntry(
//                                        $productPurchased,
//                                        true,
//                                        $restaurant,
//                                        false
//                                    );
                            }
                        }
                    }

                }
            }


        if ($productSold->getDateSynchro() != null) {
            $this->syncCmdEntryService->createProductSoldEntry($productSold);
        }


        $this->em->flush();
        return $productSold;
    }

}
