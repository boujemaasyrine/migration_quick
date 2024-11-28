<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 24/05/2016
 * Time: 18:30
 */

namespace AppBundle\General\Service\Download;

use AppBundle\Administration\Entity\Action;
use AppBundle\General\Entity\SyncCmdQueue;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\RecipeLine;
use AppBundle\Merchandise\Entity\SoldingCanal;
use AppBundle\Merchandise\Service\ProductPurchasedMvmtService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;

class DownloadSoldProduct extends AbstractDownloaderService
{
    /**
     * @var ProductPurchasedMvmtService
     */
    private $productPurchasedMvmtService;

    public function setProductPurchasedMvmtService(ProductPurchasedMvmtService $productPurchasedMvmtService)
    {
        $this->productPurchasedMvmtService = $productPurchasedMvmtService;
    }

    public function download($idSynCmd = null)
    {
        $startTime=microtime(true);
        $syncCmd = null;
        //get the synCommandQueue
        if ($idSynCmd != null) {
            $syncCmd = $this->em->getRepository(SyncCmdQueue::class)->find($idSynCmd);
            if ($syncCmd == null) {
                $this->logger->addError("SynCmdQueue not found with this id: $idSynCmd \n", ['ExecuteSyncCommand']);
                echo "SynCmdQueue not found with this id: $idSynCmd \n";

                return;
            }
        }
        $this->logger->addDebug("Start Download Sold Product", ['Download', 'DownloadSoldProduct']);
        echo "Start Download Sold Product\n";
        //get the supervision product sold related to the synCommandQueue

        $startDownloadTime=microtime(true);
        $productSupervision = $this->startDownload($this->supervisionParams['sold_items'], $syncCmd);
        $endDownloadTime=microtime(true);
        $downloadDuration=($endDownloadTime-$startDownloadTime)/60;
        $this->logger->addDebug('Download from supervision took '.$downloadDuration,['Download', 'DownloadSoldProduct']);
        if ($productSupervision != null) {
            try {
                $this->em->beginTransaction();
                $this->logger->addDebug(
                    "Downloading Prod ".$productSupervision->getName(),
                    ['Download', 'DownloadSoldProduct']
                );
                echo "Downloading Prod ".$productSupervision->getName()."\n";
                $restaurant = $syncCmd->getOriginRestaurant();
                //get the sold product related to the supervision sold product and the synCommandQueue's restaurant
                $product = $this->em->getRepository(ProductSold::class)->findOneBy(
                    array(
                        "supervisionProduct" => $productSupervision,
                        "originRestaurant" => $restaurant,
                    )
                );
                $newProduct = false;
                if (!$product) {
                    //if the sold product is not created yet
                    $this->logger->addDebug(
                        "New Product Sold ".$productSupervision->getName(),
                        ['Download', 'DownloadSoldProduct']
                    );
                    echo "New Product Sold ".$productSupervision->getName()."\n";
                    $product = new ProductSold();
                    $product->setGlobalProductID($productSupervision->getGlobalProductID()); // was getId(), changed for import needs
                    $this->em->persist($product);
                    $newProduct = true;
                } else {
                    // Old product, let's historic it and update it.
                    $this->historicEntityService->createProductSoldHistoric($product);
                }
                //in the both cases update the product sold's attributes with the values of the supervision product sold's attributes
                $product->setCodePlu($productSupervision->getCodePlu())
                    ->setType($productSupervision->getType())
                    ->setActive($productSupervision->getActive())
                    ->setSupervisionProduct($productSupervision)
                    ->setName($productSupervision->getName())
                    ->addNameTranslation('fr', $productSupervision->getNameTranslation("fr"))
                    ->addNameTranslation('nl', $productSupervision->getNameTranslation("nl"))
                    ->setReference($productSupervision->getReference())
                    ->setLastDateSynchro(new \DateTime())
                    ->setOriginRestaurant($restaurant);

                if ($productSupervision->getType() === ProductSold::TRANSFORMED_PRODUCT) {
                    // if the the product sold is a transformed one
                    $product->setProductPurchased(null);
                    //synchronize the recipes of this product
                    $this->em->getClassMetadata(Recipe::class)->setLifecycleCallbacks(array());// disbale all event because data already calculated
                    //remove all product recipes and replace them with a new ones got from the product supervision
                    foreach ($product->getRecipes() as $recipe)
                    {
                        $product->removeRecipe($recipe);
                    }
                    //  Gestion des recettes
                    $canals = $this->em->getRepository(SoldingCanal::class)
                        ->findBy(array('type' => SoldingCanal::DESTINATION), array('default' => 'DESC'));

                    foreach ($productSupervision->getRecipes() as $recipe) {
                        $newRecipe = new Recipe();
                        $newRecipe->setActive($recipe->getActive())
                            ->setExternalId($recipe->getExternalId())
                            ->setSoldingCanal($recipe->getSoldingCanal())
                            ->setGlobalId($recipe->getGlobalId())
                            ->setRevenuePrice($recipe->getRevenuePrice());
                        foreach ($canals as $canal) {
                            if(in_array($canal->getId(), array(SoldingCanal::CANAL_ALL_CANALS, SoldingCanal::ON_SITE_CANAL, SoldingCanal::E_ORDERING_IN_CANAL))) {
                                    $newRecipe->setSubSoldingCanal($recipe->getSubSoldingCanal());
                            }
                        }

                        foreach ($recipe->getRecipeLines() as $recipeLine) {
                            $newRecipeLine = new RecipeLine();
                            try {
                                $pp = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                                    array(
                                        "supervisionProduct" => $recipeLine->getProductPurchased(),
                                        "originRestaurant" => $restaurant,
                                    )
                                );
                                if(!$pp){
                                    $this->logger->addDebug("Product purchased not found for recipe line, skipping it : ", array("recipeLineId"=>$recipeLine->getId(),"ProductPurchasedName"=> $recipeLine->getProductPurchased()->getName()));
                                    continue;
                                }
                            } catch (NoResultException $e) {
                                $this->logger->addDebug(
                                    "Product purchased (recipe line) ".$e->getMessage(),
                                    ['Download', 'DownloadSoldProduct']
                                );
                                echo "Product purchased (recipe line) ".$e->getMessage()."\n";
                                throw $e;
                            }
                            $newRecipeLine
                                ->setQty($recipeLine->getQty())
                                ->setProductPurchased($pp);
                            $newRecipe->addRecipeLine($newRecipeLine);
                        }

                        $product->addRecipe($newRecipe);
                    }
                } else {
                    // if the product sold is a non transformed one
                    // get the product purchased related to the supervision product sold and the synCommandQueue's restaurant
                    // and relate it to the product sold
                    $pp = $this->em->getRepository(ProductPurchased::class)->findOneBy(
                        array(
                            "supervisionProduct" => $productSupervision->getProductPurchased(),
                            "originRestaurant" => $restaurant,
                        )
                    );
                    if ($pp == null) {
                        $this->logger->addDebug(
                            "Product purchased not found (non transformed product sold) ",
                            ['Download', 'DownloadSoldProduct']
                        );
                        echo "Product purchased not found (non transformed product sold) \n";

                        return;
                    }
                    $product->setProductPurchased($pp);
                }
                $this->em->flush();
                $this->em->commit();
                /*if ($newProduct) {
                    $this->productPurchasedMvmtService->createMvmtEntryForExistingNonRecordedTicketLine($restaurant);
                }*/
                $this->notifyCentralSuccess($syncCmd);
            } catch (\Exception $e) {
                $this->em->rollback();
                $this->logger->addError($e->getMessage(), ['Download', 'DownloadSoldProduct']);
                $this->notifyCentralFail($syncCmd);

                return;
            }
        }

        $endTime=microtime(true);

        $duration=($endTime - $startTime)/60;

        $this->logger->addDebug("the execution took ".$duration." mins",['Download','DownloadSoldProduct']);


    }
}
