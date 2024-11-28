<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 27/05/2016
 * Time: 14:28
 */

namespace AppBundle\Supervision\Service;

use AppBundle\Merchandise\Entity\ProductPurchasedHistoric;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\ProductSoldHistoric;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\RecipeHistoric;
use AppBundle\Merchandise\Entity\RecipeLine;
use AppBundle\Merchandise\Entity\RecipeLineHistoric;
use AppBundle\Supervision\Entity\ProductPurchasedSupervision;
use AppBundle\Utils\DateUtilities;
use Doctrine\ORM\EntityManager;
use AppBundle\Supervision\Utils\Utilities;

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

    public function createProductPurchasedHistoric(ProductPurchasedSupervision $p)
    {
        
        $today = new \DateTime('today');

        $historic = $this->em->getRepository(ProductPurchasedHistoric::class)->createQueryBuilder('p')
            ->where("p.externalId = :externalID")
            ->andWhere("p.createdAt >= :today ")
            ->setParameter("externalID", $p->getExternalId())
            ->setParameter("today", $today)
            ->getQuery()->getResult();

        if (count($historic) > 0) {
            foreach ($historic as $h) {
                $this->em->remove($h);
            }
        }

        $this->em->flush();

        $copy = new ProductPurchasedHistoric();

        $copy
            ->setCreatedAt($p->getDateSynchro())
            ->setName($p->getName())
            ->setReference($p->getReference())
            ->setActive($p->getActive())
            ->setGlobalProductID($p->getGlobalProductID())
            ->setType($p->getType())
            ->setExternalId($p->getExternalId())
            ->setStorageCondition($p->getStorageCondition())
            ->setBuyingCost($p->getBuyingCost())
            ->setStatus($p->getStatus())
            ->setDeactivationDate($p->getDeactivationDate())
            ->setDlc($p->getDlc())
            ->setLabelUnitUsage($p->getLabelUnitUsage())
            ->setLabelUnitExped($p->getLabelUnitExped())
            ->setLabelUnitInventory($p->getLabelUnitInventory())
            ->setInventoryQty($p->getInventoryQty())
            ->setUsageQty($p->getUsageQty())
            ->setIdItemInv($p->getIdItemInv())
            ->setUnitsLabel($p->getUnitsLabel());
        $copy->setProductCategory(null);
        $copy->setSecondaryItem($p->getSecondaryItem());
        $copy->setPrimaryItem($p->getPrimaryItem());


        $this->em->persist($copy);
        $copy->setSecondaryItem($p->getSecondaryItem());
        $copy->setPrimaryItem($p->getPrimaryItem());
        $copy->setProductCategory($p->getProductCategory());
        foreach ($p->getSupplier() as $s) {
            $copy->addSupplier($s);
        }

        $this->em->flush();
    }

    public function createProductSoldHistoric(ProductSold $p)
    {

        $today = new \DateTime('today');

        $historic = $this->em->getRepository(ProductSoldHistoric::class)->createQueryBuilder('p')
            ->where("p.globalId = :globalId")
            ->andWhere("p.createdAt >= :today ")
            ->setParameter("globalId", $p->getGlobalProductID())
            ->setParameter("today", $today)
            ->getQuery()->getResult();

        if (count($historic) > 0) {
            foreach ($historic as $h) {
                $this->em->remove($h);
            }
        }

        $this->em->flush();

        $copy = new ProductSoldHistoric();
        $copy->setName($p->getName())
            ->setReference($p->getReference())
            ->setActive($p->getActive())
            ->setCodePlu($p->getCodePlu())
            ->setType($p->getType())
            ->setActive($p->isActive())
            ->setGlobalId(is_null($p->getGlobalProductID()) ? $p->getId() : $p->getGlobalProductID());
        if ($p->getType() === ProductSold::TRANSFORMED_PRODUCT) {
            foreach ($p->getRecipes() as $recipe) {
                /**
                 * @var Recipe $recipe
                 */
                $recipeCp = new RecipeHistoric();
                $recipeCp->setSoldingCanal($recipe->getSoldingCanal())
                    ->setExternalId($recipe->getExternalId())
                    ->setActive($recipe->isActive());
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
            }
        } else {
            $copy->setProductPurchased($p->getProductPurchased());
        }
        $this->em->persist($copy);
        $this->em->flush();
    }
}
