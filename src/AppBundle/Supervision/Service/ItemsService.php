<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 09/03/2016
 * Time: 15:57
 */

namespace AppBundle\Supervision\Service;

use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\ProductSoldHistoric;
use AppBundle\Merchandise\Entity\RecipeLineHistoric;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Supervision\Entity\ProductSoldSupervision;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Translation\Translator;
use AppBundle\Merchandise\Service\InventoryService;
use AppBundle\Merchandise\Service\HistoricEntitiesService as ProductHistoricService;

class ItemsService
{
    private $em;

    private $translator;

    private $syncCmdEntryService;

    private $historicService;

    /**
     * @var Logger $logger
     */
    private $logger;

    /**
     * @var InventoryService $inventoryService
     */
    private $inventoryService;

    /**
     * @var ProductHistoricService $productHistoricService
     */
    private $productHistoricService;

    public function __construct(
        EntityManager $entityManager,
        Translator $translator,
        SyncCmdCreateEntryService $cmdSync,
        HistoricEntitiesService $historicEntitiesService,
        Logger $logger,
        InventoryService $inventoryService,
        ProductHistoricService $productHistoricService
    )
    {
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->syncCmdEntryService = $cmdSync;
        $this->historicService = $historicEntitiesService;
        $this->logger = $logger;
        $this->inventoryService = $inventoryService;
        $this->productHistoricService = $productHistoricService;
    }

    public function saveInventoryItem(ProductPurchasedSupervision $productPurchased)
    {
        $this->em->persist($productPurchased);
        $active = ($productPurchased->getStatus() == ProductPurchasedSupervision::INACTIVE) ? false : true;
        $productPurchased->setActive($active);
        $productPurchased->setName($productPurchased->getNameTranslation('fr'));

        if ($productPurchased->getStatus() != ProductPurchasedSupervision::TO_INACTIVE) {
            $productPurchased->setDeactivationDate(null);
        }

        $oldSecondary = $this->em->getRepository(ProductPurchasedSupervision::class)->findOneByPrimaryItem(
            $productPurchased
        );
        if ($oldSecondary) {
            $oldSecondary->setPrimaryItem(null);
        }
        $newSecondary = $productPurchased->getSecondaryItem();

        if ($newSecondary != null) {
            $newSecondary->setPrimaryItem($productPurchased);
            foreach ($productPurchased->getRestaurants() as $restaurant) {
                if (!$newSecondary->getRestaurants()->contains($restaurant)) {
                    $newSecondary->addRestaurant($restaurant);
                    $this->syncCmdEntryService->createProductPurchasedEntry($newSecondary, true, $restaurant, false);
                }
            }
        }

        $price = floatval(str_replace(',', '.', $productPurchased->getBuyingCost()));

        $productPurchased->setBuyingCost($price);
        $new = is_null($productPurchased->getId());

        /*if (!$new) {
            $uow = $this->em->getUnitOfWork();
            $uow->computeChangeSets();
            $changes = $uow->getEntityChangeSet($productPurchased);
            if (count($changes) > 1 or (count($changes) == 1 and !array_key_exists('dateSynchro', $changes))) {
                $oldEntity = clone $productPurchased;
                foreach ($changes as $key => $change) {
                    $attribute = strtoupper(substr($key, 0, 1));
                    $attribute = 'set' . $attribute . substr($key, 1);
                    $oldEntity->$attribute($change['0']);
                }
              //  $this->historicService->createProductPurchasedHistoric($oldEntity);
            }
        }*/

        $productPurchased->setGlobalProductID($productPurchased->getId());
        $this->em->flush();
        if ($productPurchased->getDateSynchro() != null) {
            $this->syncCmdEntryService->createProductPurchasedEntry($productPurchased);
        }
    }

    public function deleteInventoryItem(ProductPurchased $productPurchased, $activate)
    {
        $status = ($activate) ? ProductPurchased::ACTIVE : ProductPurchased::INACTIVE;

        $productPurchased->setStatus($status);

        $productPurchased->setActive($activate);

        $productPurchased->setDeactivationDate(null);

        $this->em->flush();

        return true;
    }

    public function getInventoryItems($criteria, $order, $limit, $offset, $onlyList = false)
    {
        $inventoryItems = $this->em->getRepository(
            ProductPurchasedSupervision::class
        )->getSupervisonInventoryItemsOrdered(
            $criteria,
            $order,
            $offset,
            $limit,
            $onlyList
        );

        return $this->serializeInventoryItems($inventoryItems);
    }

    public function serializeInventoryItems($inventoryItems)
    {
        $result = [];
        foreach ($inventoryItems as $i) {
            /**
             * @var ProductPurchasedSupervision $i
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
                $restaurants[] = ['code' => $resto->getCode(), 'name' => $resto->getName()];
                $restaurantsAsString[] = "(" . $resto->getCode() . ") " . $resto->getName();
            }

            $result[] = array(
                'id' => $i->getId(),
                'code' => $i->getExternalId(),
                'nameFr' => $i->getNameTranslation('fr'),
                'nameNl' => $i->getNameTranslation('nl'),
                'name' => $i->getName(),
                'category' => $i->getProductCategory() ? $i->getProductCategory()->getName() : '',
                'buyingCost' => number_format($i->getBuyingCost(), 3, ',', ' '),
                'statusKey' => $i->getStatus(),
                'status' => $this->translator->trans("status." . $i->getStatus()),
                'deactivationDate' => $i->getDeactivationDate() ? $i->getDeactivationDate()->format('Y-m-d') : '',
                'unitExpedition' => $i->getLabelUnitExped() ? $this->translator->trans(
                    strtolower($i->getLabelUnitExped())
                ) : '',
                'unitInventory' => $i->getLabelUnitInventory() ? $this->translator->trans(
                    strtolower($i->getLabelUnitInventory())
                ) : '',
                'unitUsage' => $i->getLabelUnitUsage() ? $this->translator->trans(
                    strtolower($i->getLabelUnitUsage())
                ) : '',
                'inventoryQty' => $i->getInventoryQty(),
                'usageQty' => $i->getUsageQty(),
                'restaurants' => json_encode($restaurants),
                'secondaryItem' => $i->getSecondaryItem() ? $i->getSecondaryItem()->getName() : '',
                'restaurantsAsString' => implode("\r", $restaurantsAsString),
                'dateSynchro' => $i->getDateSynchro() ? $i->getDateSynchro()->format('Y-m-d') : '',
                'lastDateSynchro' => $i->getLastDateSynchro() ? $i->getLastDateSynchro()->format('Y-m-d H:i:s') : '',

            );
        }

        //dump($result); die;
        return $result;
    }

    /**
     * Trouver les restaurants actif et dans lesquels l'item d'inventaire est téléchargé
     * @param ProductPurchasedSupervision $inventoryItem
     * @return array
     */
    public function findRestaurantsWhichTheInventoryItemIsActive(ProductPurchasedSupervision $inventoryItem)
    {
        $rhdpIds = $this->getIdsFromArray($this->findRestaurantsHaveDownloadedProducts($inventoryItem->getProducts()));
        $openedRestsIds = $this->getIdsFromArray($this->em->getRepository(Restaurant::class)->getOpenedRestaurants());
        $rwiia = array_intersect($openedRestsIds, $rhdpIds);
        return $this->em->getRepository(Restaurant::class)->findBy(array('id' => $rwiia));
    }

    /**
     * Trouver les restaurants actif et dans lesquels l'item de vente est téléchargé
     * @param ProductPurchasedSupervision $inventoryItem
     * @return array
     */
    public function findRestaurantsWhichTheProductSoldIsActive(ProductSoldSupervision $productSold)
    {
        $rhdpIds = $this->getIdsFromArray($this->findRestaurantsHaveDownloadedProducts($productSold->getProducts()));
        $openedRestsIds = $this->getIdsFromArray($this->em->getRepository(Restaurant::class)->getOpenedRestaurants());
        $rwiia = array_intersect($openedRestsIds, $rhdpIds);
        return $this->em->getRepository(Restaurant::class)->findBy(array('id' => $rwiia));
    }

    /**
     * Trouver les restaurants dans lesquels l'item de vente/inventaire est téléchargé et activé.
     * @param $downloadedProduct
     * @return array
     */
    private function findRestaurantsHaveDownloadedProducts($downloadedProduct)
    {
        $rest = [];
        if (count($downloadedProduct) > 0) {
            foreach ($downloadedProduct as $dp) {
                if($dp->getActive()){
                    $rest[] = $dp->getOriginRestaurant();
                }
            }
        }
        return $rest;
    }

    /**
     *
     * @param $ac
     * @return array
     */
    private function getIdsFromArray($ac)
    {
        $ids = array();
        if (count($ac) > 0) {
            foreach ($ac as $o) {
                $ids[] = (int)$o->getId();
            }
        }
        return $ids;
    }

    /**
     * @param ProductPurchasedSupervision $pps
     * @param $restaurants
     * @return array
     */
    public function disableInventoryItems(ProductPurchasedSupervision $pps, $restaurants)
    {
        $failedInRestaurant = [];
        $successInRestaurant = [];
        foreach ($restaurants as $r) {
            if ($this->disableInventoryItemForOneRestaurant($pps, $r) === null) {
                $failedInRestaurant[] = $r->getName() . '(' . $r->getCode() . ')';
            } else {
                $successInRestaurant[] = $r->getName() . '(' . $r->getCode() . ')';
            }
        }
        return [$successInRestaurant, $failedInRestaurant];
    }

    /**
     * @param ProductPurchasedSupervision $pps
     * @param Restaurant $restaurant
     * @return bool|null
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function disableInventoryItemForOneRestaurant(ProductPurchasedSupervision $pps, Restaurant $restaurant)
    {
        $product = $this->em->getRepository(ProductPurchased::class)->findOneBy(
            array(
                "supervisionProduct" => $pps,
                "originRestaurant" => $restaurant,
            )
        );
        if (!is_object($product)) {
            $this->logger->addInfo('L\'tem inventaire avec le nom ' . $pps->getName() . 'n\'existe pas au niveau restaurant ' . $restaurant->getCode());
            return null;
        }

        $this->inventoryService->createZeroInventoryLineForProduct($product, $restaurant);

      /*  $newHistoricProduct = $this->productHistoricService->createProductPurchasedHistoric($product);
        $histosRl = $this->em->getRepository(RecipeLineHistoric::class)->findBy(
            ['productPurchased' => $product]
        );
        foreach ($histosRl as $item1) {
            $item1->setProductPurchased(null);
            $item1->setProductPurchasedHistoric($newHistoricProduct);
        }
        //  $this->em->flush($histosRl);
        // search for product sold non transformed pointed to this product and repoint them to newHistoricProduct
        $histosPs = $this->em->getRepository(ProductSoldHistoric::class)->findBy(
            ['type' => ProductSold::NON_TRANSFORMED_PRODUCT, 'productPurchased' => $product]
        );
        foreach ($histosPs as $item2) {
            $item2->setProductPurchased(null);
            $item2->setProductPurchasedHistoric($newHistoricProduct);
        }*/
        $product->setStatus(ProductPurchased::INACTIVE);
        $product->setActive(false);
        $product->setDeactivationDate(null);

        //Rendre le produit non Éligible dans le restaurant
        $pps->removeRestaurant($restaurant);
        $this->em->persist($product);
        $this->em->flush();
        $this->logger->addInfo('L\'tem inventaire avec le nom ' . $pps->getName() . ' a été désactité avec succès dans le restaurant ' . $restaurant->getCode());
        return true;
    }

    /**
     * @param ProductSoldSupervision $pss
     * @param $restaurants
     * @return array
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function disableProductsSold(ProductSoldSupervision $pss, $restaurants){
        $failedInRestaurant = [];
        $successInRestaurant = [];
        foreach ($restaurants as $r) {
            if ($this->disableProductSoldForOneRestaurant($pss, $r) === null) {
                $failedInRestaurant[] = $r->getName() . '(' . $r->getCode() . ')';
            } else {
                $successInRestaurant[] = $r->getName() . '(' . $r->getCode() . ')';
            }
        }
        return [$successInRestaurant, $failedInRestaurant];
    }

    /**
     * @param ProductSoldSupervision $pss
     * @param Restaurant $restaurant
     * @return bool|null
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function disableProductSoldForOneRestaurant(ProductSoldSupervision $pss, Restaurant $restaurant)
    {
        $product = $this->em->getRepository(ProductSold::class)->findOneBy(
            array(
                "supervisionProduct" => $pss,
                "originRestaurant" => $restaurant,
            )
        );
        if (!is_object($product)) {
            $this->logger->addInfo('L\'tem de vente avec le nom ' . $pss->getName() . 'n\'existe pas au niveau restaurant ' . $restaurant->getCode());
            return null;
        }
        $product->setActive(false);

        //Rendre le produit non Éligible dans le restaurant
        $pss->removeRestaurant($restaurant);
        //$this->em->persist($product);
        $this->em->flush();
        $this->logger->addInfo('L\'tem de vente avec le nom ' . $pss->getName() . ' a été désactité avec succès dans le restaurant ' . $restaurant->getCode());
        return true;
    }
}
