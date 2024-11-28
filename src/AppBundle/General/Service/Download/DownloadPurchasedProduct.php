<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 18:30
 */

namespace AppBundle\General\Service\Download;

use AppBundle\Administration\Entity\Action;
use AppBundle\Administration\Service\ParameterService;
use AppBundle\General\Entity\SyncCmdQueue;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductCategories;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\ProductSoldHistoric;
use AppBundle\Merchandise\Entity\RecipeLineHistoric;
use AppBundle\Merchandise\Service\HistoricEntitiesService;
use AppBundle\Merchandise\Service\InventoryService;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bridge\Monolog\Logger;

class DownloadPurchasedProduct extends AbstractDownloaderService
{

    /**
     * @var InventoryService $inventoryService
     */
    private $inventoryService;

    public function __construct(
        ManagerRegistry $managerRegistry,
        Logger $logger,
        $quickCode,
        $supervisionParams,
        HistoricEntitiesService $historicEntityService,
        ParameterService $parameterService,
        InventoryService $inventoryService
    ) {
        parent::__construct(
            $managerRegistry,
            $logger,
            $quickCode,
            $supervisionParams,
            $historicEntityService,
            $parameterService
        );
        $this->inventoryService = $inventoryService;
    }

    public function download($idSynCmd = null)
    {
        $syncCmd = null;
        try
        {
            $this->logger->addInfo("Start Download Purchased Product \n");
            echo "Start Download Purchased Product \n";
            //get the synCommandQueue
            if ($idSynCmd != null) {
                $syncCmd = $this->em->getRepository(SyncCmdQueue::class)->find($idSynCmd);
                if ($syncCmd == null) {
                    $this->logger->addError("SynCmdQueue not found with this id: $idSynCmd \n", ['ExecuteSyncCommand']);
                    echo "SynCmdQueue not found with this id: $idSynCmd \n";

                    return;
                }
            }
            //get the supervision product purchased related to the synCommandQueue
            $productPurchasedSupervision = $this->startDownload($this->supervisionParams['inv_items'], $syncCmd);
            if ($productPurchasedSupervision != null) {
                $this->logger->addInfo(
                    "Downloading Prod ".$productPurchasedSupervision->getName()." #".$productPurchasedSupervision->getId(
                    )."# \n"
                );
                echo "Downloading Prod ".$productPurchasedSupervision->getName()." #".$productPurchasedSupervision->getId(
                    )."# \n";
                $restaurant = $syncCmd->getOriginRestaurant();
                //get the purchased product related to the supervision purchased product and the synCommandQueue's restaurant
                $product = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                    array(
                        "supervisionProduct" => $productPurchasedSupervision,
                        "originRestaurant" => $restaurant,
                    )
                );
                if (!$product) {
                    //if the purchased product is not created yet
                    $this->logger->addInfo("New Product Purchased ".$productPurchasedSupervision->getName()." \n");
                    echo "New Product Purchased ".$productPurchasedSupervision->getName()." \n";
                    $product = new ProductPurchased();
                    $this->em->persist($product);
                    //check if the new created product has a primary product and that primary product is created already
                    $primaryItem = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                        array(
                            "supervisionProduct" => $productPurchasedSupervision->getPrimaryItem(),
                            "originRestaurant" => $restaurant,
                        )
                    );
                    if ($primaryItem) {
                        //if the primary product is created already
                        // point the new created product to that product
                        $product->setPrimaryItem($primaryItem);
                        if ($primaryItem->getSecondaryItem() != null) {
                            $primaryItem->getSecondaryItem()->setPrimaryItem(null);
                        }
                        $primaryItem->setSecondaryItem($product);
                    }
                    //check if the new created product has a secondary product and that secondary product is created already
                    $secondaryItem = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                        array(
                            "supervisionProduct" => $productPurchasedSupervision->getSecondaryItem(),
                            "originRestaurant" => $restaurant,
                        )
                    );
                    if ($secondaryItem) {
                        //if the secondary product is created already
                        // point the new created product to that product
                        $product->setSecondaryItem($secondaryItem);
                        if ($secondaryItem->getPrimaryItem() != null) {
                            $secondaryItem->getPrimaryItem()->setSecondaryItem(null);
                        }
                        $secondaryItem->setPrimaryItem($product);
                    }
                } else {
                    //if the product purchased is created already
                    try {
                        //checking for the status of the product
                        $this->logger->addInfo('Checking if product has been desactivated : ');
                        echo "Checking if product has been desactivated : \n";
                        $this->logger->addInfo('Existing product status : '.$product->getStatus());
                        echo 'Existing product status : '.$product->getStatus()."\n";
                        $this->logger->addInfo('Updating status : '.$productPurchasedSupervision->getStatus());
                        echo 'Updating status : '.$productPurchasedSupervision->getStatus()."\n";
                        if (($product->getStatus() == ProductPurchased::ACTIVE && $productPurchasedSupervision->getStatus(
                                ) == ProductPurchased::INACTIVE)
                            || ($product->getStatus(
                                ) == ProductPurchased::TO_INACTIVE && $productPurchasedSupervision->getStatus(
                                ) == ProductPurchased::INACTIVE)
                        ) {
                            $this->inventoryService->createZeroInventoryLineForProduct($product,$restaurant);
                        }
                        $newHistoricProduct = $this->historicEntityService->createProductPurchasedHistoric($product);
                        // search for historized recipe line, repoint them to newHistoricProduct
                        $histosRl = $this->em->getRepository(RecipeLineHistoric::class)->findBy(
                            ['productPurchased' => $product]
                        );
                        foreach ($histosRl as $item1) {
                            $item1->setProductPurchased(null);
                            $item1->setProductPurchasedHistoric($newHistoricProduct);
                        }
                        $this->em->flush($histosRl);
                        // search for product sold non transformed pointed to this product and repoint them to newHistoricProduct
                        $histosPs = $this->em->getRepository(ProductSoldHistoric::class)->findBy(
                            ['type' => ProductSold::NON_TRANSFORMED_PRODUCT, 'productPurchased' => $product]
                        );
                        foreach ($histosPs as $item2) {
                            $item2->setProductPurchased(null);
                            $item2->setProductPurchasedHistoric($newHistoricProduct);
                        }
                        $this->em->flush();

                        $primaryItem = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                            array(
                                "supervisionProduct" => $productPurchasedSupervision->getPrimaryItem(),
                                "originRestaurant" => $product->getOriginRestaurant(),
                            )
                        );
                        if ($product->getPrimaryItem() != null) {
                            $product->getPrimaryItem()->setSecondaryItem(null);
                        }
                        if ($primaryItem) {
                            $product->setPrimaryItem($primaryItem);
                            /*if ($primaryItem->getSecondaryItem() != null) {
                                $primaryItem->getSecondaryItem()->setPrimaryItem(null);
                            }*/
                            $primaryItem->setSecondaryItem($product);
                        }
                        $secondaryItem = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                            array(
                                "supervisionProduct" => $productPurchasedSupervision->getSecondaryItem(),
                                "originRestaurant" => $product->getOriginRestaurant(),
                            )
                        );
                        if ($product->getSecondaryItem() != null) {
                            $product->getSecondaryItem()->setPrimaryItem(null);
                        }
                        if ($secondaryItem) {
                            $product->setSecondaryItem($secondaryItem);
                            /* if ($secondaryItem->getPrimaryItem() != null) {
                                 $secondaryItem->getPrimaryItem()->setSecondaryItem(null);
                             }*/
                            $secondaryItem->setPrimaryItem($product);
                        }
                    } catch (\Exception $e) {
                        $this->logger->addCritical($e->getMessage());
                    }
                }
                //in the both cases update the product purchased's attributes with the values of the supervision product purchased's attributes
                $product->setOriginRestaurant($restaurant);
                $product->setSupervisionProduct($productPurchasedSupervision);
                $product->setLastDateSynchro(new \DateTime());
                $product->setExternalId($productPurchasedSupervision->getExternalId())
                    ->setType($productPurchasedSupervision->getType())
                    ->setStorageCondition($productPurchasedSupervision->getStorageCondition())
                    ->setBuyingCost($productPurchasedSupervision->getBuyingCost())
                    ->setStatus($productPurchasedSupervision->getStatus())
                    ->setDeactivationDate($productPurchasedSupervision->getDeactivationDate())
                    ->setDlc($productPurchasedSupervision->getDlc())
                    ->setLabelUnitExped($productPurchasedSupervision->getLabelUnitExped())
                    ->setLabelUnitInventory($productPurchasedSupervision->getLabelUnitInventory())
                    ->setLabelUnitUsage($productPurchasedSupervision->getLabelUnitUsage())
                    ->setInventoryQty($productPurchasedSupervision->getInventoryQty())
                    ->setUsageQty($productPurchasedSupervision->getUsageQty())
                    ->setIdItemInv($productPurchasedSupervision->getIdItemInv())
                    ->setName($productPurchasedSupervision->getName())
                    ->addNameTranslation('fr', $productPurchasedSupervision->getNameTranslation("fr"))
                    ->addNameTranslation('nl', $productPurchasedSupervision->getNameTranslation("nl"))
                    ->setReference($productPurchasedSupervision->getReference())->setActive(
                        $productPurchasedSupervision->getActive()
                    )
                    ->setGlobalProductID($productPurchasedSupervision->getGlobalProductID());
                $product->setProductCategory($productPurchasedSupervision->getProductCategory());
                $product->setSuppliers($productPurchasedSupervision->getSuppliers());
                $product->setReusable($productPurchasedSupervision->isReusable());
                $product->setStartDateCmd($productPurchasedSupervision->getStartDateCmd());
                $product->setEndDateCmd($productPurchasedSupervision->getEndDateCmd());

                $this->em->flush();
            }

            //sync cmd executed with success
            $this->notifyCentralSuccess($syncCmd);
        }
        catch (\Exception $e)
        {
            if($syncCmd)
            {
                $this->notifyCentralFail($syncCmd);
            }
            $this->logger->addCritical($e->getMessage());
            echo $e->getMessage();
        }

    }
}
