<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 27/05/2016
 * Time: 14:28
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductPurchasedHistoric;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\ProductSoldHistoric;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\RecipeHistoric;
use AppBundle\Merchandise\Entity\RecipeLine;
use AppBundle\Merchandise\Entity\RecipeLineHistoric;
use AppBundle\Merchandise\Entity\SoldingCanal;
use Doctrine\ORM\EntityManager;

class HistoricEntitiesService
{

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createProductPurchasedHistoric(ProductPurchased $p)
    {
        $copy = new ProductPurchasedHistoric();
        $copy->setName($p->getName())
            ->setOriginalID($p->getId())
            ->setStartDate($p->getLastDateSynchro())
            ->setReference($p->getReference())
            ->setStockCurrentQty($p->getStockCurrentQty())
            ->setActive($p->getActive())
            ->setGlobalProductID($p->getGlobalProductID())
            ->setType($p->getType())
            ->setExternalId($p->getExternalId())
            ->setStorageCondition($p->getStorageCondition())
            ->setBuyingCost($p->getBuyingCost())
            ->setStatus($p->getStatus())
            ->setDeactivationDate($p->getDeactivationDate())
            ->setDlc($p->getDlc())
            ->setSecondaryItem($p->getSecondaryItem())
            ->setPrimaryItem($p->getPrimaryItem())
            ->setLabelUnitUsage($p->getLabelUnitUsage())
            ->setLabelUnitExped($p->getLabelUnitExped())
            ->setLabelUnitInventory($p->getLabelUnitInventory())
            ->setInventoryQty($p->getInventoryQty())
            ->setUsageQty($p->getUsageQty())
            ->addSupplier($p->getSuppliers()->first())
            ->setProductCategory($p->getProductCategory())
            ->setOriginRestaurant($p->getOriginRestaurant())
            ->setIdItemInv($p->getIdItemInv())
            ->setUnitsLabel($p->getUnitsLabel());
        $this->em->persist($copy);
        $this->em->flush();

        return $copy;
    }

    public function createProductSoldHistoric(ProductSold $p)
    {
        $copy = new ProductSoldHistoric();
        $copy->setName($p->getName())
            ->setReference($p->getReference())
            ->setActive($p->getActive())
            ->setCodePlu($p->getCodePlu())
            ->setType($p->getType())
            ->setActive($p->isActive())
            ->setGlobalId($p->getGlobalProductID())
            ->setStartDate($p->getLastDateSynchro())
            ->setOriginRestaurant($p->getOriginRestaurant());
        if ($p->getType() === ProductSold::TRANSFORMED_PRODUCT) {
            //  Gestion des recettes 05/2024
            $canals = $this->em->getRepository(SoldingCanal::class)
                ->findBy(array('type' => SoldingCanal::DESTINATION), array('default' => 'DESC'));

            foreach ($p->getRecipes() as $recipe) {
                /**
                 * @var Recipe $recipe
                 */
                $recipeCp = new RecipeHistoric();
                $recipeCp->setSoldingCanal($recipe->getSoldingCanal())
                    ->setExternalId($recipe->getExternalId())
                    ->setActive($recipe->isActive())
                    ->setRevenuePrice($recipe->getRevenuePrice());
                //  Gestion des recettes  05/2024
                foreach ($canals as $canal) {
                    if (in_array($canal->getId(), array(SoldingCanal::CANAL_ALL_CANALS, SoldingCanal::ON_SITE_CANAL, SoldingCanal::E_ORDERING_IN_CANAL))) {
                        $recipeCp->setSubSoldingCanal($recipe->getSubSoldingCanal());
                    }
                }

                foreach ($recipe->getRecipeLines() as $recipeLine) {
                    /**
                     * @var RecipeLine $recipeLine
                     */
                    $recipeLineCp = new RecipeLineHistoric();
                    $recipeLineCp->setProductPurchased($recipeLine->getProductPurchased())
                        ->setQty($recipeLine->getQty());
                    $recipeCp->addRecipeLine($recipeLineCp);
                }
                $copy->addRecipe($recipeCp);
                $this->em->persist($recipeCp);

                $lossLines = $this->em->getRepository(LossLine::class)->findBy(
                    [
                        'recipe' => $recipe,
                        'recipeHistoric' => null,
                    ]
                );
                foreach ($lossLines as $line) {
                    $line->setRecipeHistoric($recipeCp);
                }
            }
        } else {
            $copy->setProductPurchased($p->getProductPurchased());
        }
        $this->em->persist($copy);
        $this->em->flush();
    }
}
