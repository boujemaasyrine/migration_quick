<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 19/02/2016
 * Time: 10:07
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\Financial\Service\AdministrativeClosingService;
use AppBundle\Merchandise\Entity\LossSheet;
use AppBundle\Merchandise\Entity\LossLine;
use AppBundle\Merchandise\Entity\LossLineType;
use AppBundle\Merchandise\Entity\Product;
use AppBundle\Merchandise\Entity\ProductPurchasedHistoric;
use AppBundle\Merchandise\Entity\ProductPurchasedMvmt;
use AppBundle\Merchandise\Entity\ProductSold;
use AppBundle\Merchandise\Entity\Recipe;
use AppBundle\Merchandise\Entity\RecipeLine;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\SheetModel;
use AppBundle\Merchandise\Entity\SheetModelLine;
use AppBundle\Merchandise\Entity\SoldingCanal;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Intl\Exception\NotImplementedException;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class LossService
{

    private $em;
    private $tokenStorage;
    private $poDirectory;
    /**
     * @var ProductService
     */
    private $productService;
    private $productPurchasedMvmtService;
    /**
     * @var AdministrativeClosingService
     */
    private $administrativeClosing;

    public function __construct(
        EntityManager $entityManager,
        $poDirectory,
        TokenStorage $tokenStorage,
        ProductService $productService,
        ProductPurchasedMvmtService $productPurchasedMvmtService,
        AdministrativeClosingService $administrativeClosing
    ) {
        $this->em = $entityManager;
        $this->poDirectory = $poDirectory;
        $this->tokenStorage = $tokenStorage;
        $this->productService = $productService;
        $this->productPurchasedMvmtService = $productPurchasedMvmtService;
        $this->administrativeClosing = $administrativeClosing;
    }

    /**
     * @param LossSheet $lossSheet
     */
    public function saveLossSheet($restaurant, LossSheet $lossSheet,$isPrevious=false)
    {

        $lossSheet->setEmployee($this->tokenStorage->getToken()->getUser());
        $lossSheet->setOriginRestaurant($restaurant);
        if (is_null($lossSheet->getId())) {
            foreach ($lossSheet->getLossLines() as $line) {

                $recipe = $this->getRecipeForProduct($restaurant, $line->getProduct());
                 /**
                 * @var $line LossLine
                 */
                if ($line->getProduct()->getType() === ProductSold::TRANSFORMED_PRODUCT) {
                    $this->productService->updateStock(
                        $line->getProduct(),
                        -1 * $line->getTotalLoss(),
                        Product::INVENTORY_UNIT,
                      $recipe
                    );
                } else {
                    $this->productService->updateStock($line->getProduct(), -1 * $line->getTotalLoss());
                }
                $line->setLossSheet($lossSheet);
            }
            if(!$isPrevious){
                $fiscalDate = $this->administrativeClosing->getLastWorkingEndDate();
                $lossSheet->setEntryDate($fiscalDate->setTime(6, 0, 0));
            }
            $this->em->persist($lossSheet);
            $this->productPurchasedMvmtService->createMvmtEntryForLossSheet($lossSheet,$restaurant, false);
            $this->em->flush();
        } else {

            $oldLines = $this->em->getRepository('Merchandise:LossLine')->findBy(['lossSheet' => $lossSheet->getId()]);
            foreach ($oldLines as $line) {

                $recipe = $this->getRecipeForProduct($restaurant, $line->getProduct());

                if ($line->getProduct()->getType() === ProductSold::TRANSFORMED_PRODUCT) {
                    $this->productService->updateStock(
                        $line->getProduct(),
                        $line->getTotalLoss(),
                        Product::INVENTORY_UNIT,
                        $recipe
                    );
                } else {
                    $this->productService->updateStock($line->getProduct(), $line->getTotalLoss());
                }
                if($lossSheet->getType() == LossSheet::ARTICLE) {
                    $this->productPurchasedMvmtService->deleteMvmtEntriesByTypeAndSourceId(
                        ProductPurchasedMvmt::PURCHASED_LOSS_TYPE,
                        $line->getId(),
                        $restaurant
                    );
                }

                else {
                    $this->productPurchasedMvmtService->deleteMvmtEntriesByTypeAndSourceId(ProductPurchasedMvmt::SOLD_LOSS_TYPE, $line->getId(),$restaurant);

                }
                $this->em->remove($line);
            }
            $temp = $this->em->merge($lossSheet);
            foreach ($lossSheet->getLossLines() as $line) {
                $recipe = $this->getRecipeForProduct($restaurant, $line->getProduct());
                /**
                 * @var LossLine $line
                 * @var LossSheet $temp
                 */
                $line->setLossSheet($temp);
                /**
                 * @var $line LossLine
                 */
                if ($line->getProduct()->getType() === ProductSold::TRANSFORMED_PRODUCT) {
                    $this->productService->updateStock(
                        $line->getProduct(),
                        -1 * $line->getTotalLoss(),
                        Product::INVENTORY_UNIT,
                       $recipe
                    );
                } else {
                    $this->productService->updateStock($line->getProduct(), -1 * $line->getTotalLoss());
                }

                $line->setRecipe($recipe);
                $this->em->persist($line);
            }

            /*if ($temp->getType() == LossSheet::ARTICLE) {
                foreach ($temp->getLossLines() as $line) {
                    $this->productPurchasedMvmtService->deleteMvmtEntriesByTypeAndSourceId(
                        ProductPurchasedMvmt::PURCHASED_LOSS_TYPE,
                        $line->getId()
                    );
                }
            } else {
                foreach ($temp->getLossLines() as $line) {
                    $this->productPurchasedMvmtService->deleteMvmtEntriesByTypeAndSourceId(
                        ProductPurchasedMvmt::SOLD_LOSS_TYPE,
                        $line->getId()
                    );
                }
            }*/
            $this->em->flush();

            $this->em->detach($temp);
            $lossSheet = $this->em->getRepository('Merchandise:LossSheet')
                ->createQueryBuilder('lossSheet')
                ->leftJoin('lossSheet.lossLines', 'lossLines')
                ->select('lossSheet', 'lossLines')
                ->where('lossSheet.id = :sheetId')
                ->setParameter('sheetId', $temp->getId())
                ->getQuery()
                ->getSingleResult();
            $this->productPurchasedMvmtService->createMvmtEntryForLossSheet($lossSheet, $restaurant,false);
            $this->em->flush();
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



    /**
     * @param SheetModel $model
     * @return LossSheet
     */
    public function initLoss(SheetModel $model)
    {
        $loss = new LossSheet();

        $loss->setType($model->getLinesType());
        $fiscalDate = $this->administrativeClosing->getLastWorkingEndDate();
        $loss->setEntryDate($fiscalDate);

        if ($model->getType() === SheetModel::ARTICLES_LOSS_MODEL) {
            $loss->setType(LossSheet::ARTICLE);
            foreach ($model->getLines() as $line) {
                /**
                 * @var SheetModelLine $line
                 */
                $lossLine = [
                    "idProduct" => $line->getProduct()->getId(),
                    "productName" => $line->getProduct()->getName(),
                    "refProduct" => $line->getProduct()->getExternalId(),
                    "productUsageQty" => $line->getProduct()->getUsageQty(),
                    "productInventoryQty" => $line->getProduct()->getInventoryQty(),
                    "labelUnitUsage" => $line->getProduct()->getLabelUnitUsage(),
                    "labelUnitInventory" => $line->getProduct()->getLabelUnitInventory(),
                    "labelUnitExped" => $line->getProduct()->getLabelUnitExped(),
                    "order" => $line->getOrderInSheet(),
                ];
                $loss->addLossLine($lossLine);
            }
        } elseif ($model->getType() === SheetModel::PRODUCT_SOLD_LOSS_MODEL) {
            $loss->setType(LossSheet::FINALPRODUCT);
            foreach ($model->getLines() as $line) {
                /**
                 * @var SheetModelLine $line
                 */
                $lossLine = [
                    "idProduct" => $line->getProduct()->getId(),
                    "productName" => $line->getProduct()->getName(),
                    "refProduct" => $line->getProduct()->getCodePlu(),
                    "soldingCanalsId" => $line->getProduct()->getSoldingCanalsIds(),
                    "isTransformedProduct" => $line->getProduct()->isTransformedProduct(),
                    "order" => $line->getOrderInSheet(),
                ];
                $loss->addLossLine($lossLine);
            }
        }

        return $loss;
    }

    /**
     * @param array $data
     * @return LossLine[]
     */
    public function getListLoss($form, $type)
    {
        $data = array();
        $lossType = array();
        switch ($type) {
            case 'hourly':
                $data['date'] = $form['date']->getData()->format('Y-m-d');
                $data['entryTime'] = $form['startTime']->getData()->format('H');
                $data['endTime'] = $form['endTime']->getData()->format('H');
                $lossType = $this->em->getRepository("Merchandise:LossLineType")->getTypebyHour($data);
                break;

            case ($type == 'daily' || $type == 'monthly'):
                $data['date'] = $form['date']->getData()->format('Y-m-d');
                $data['endDate'] = $form['endDate']->getData()->format('Y-m-d');
                $lossType = $this->em->getRepository("Merchandise:LossLine")->getlossbyDays($data);
                break;

            case 'weekly':
                $data['date'] = $form['date']->getData()->format('Y-m-d');
                $nbrWeeks = $form['weeks']->getData();
                $nbrDays = $nbrWeeks * 7;
                $addDays = 'P'.$nbrDays.'D';
                $data['endDate'] = $form['date']->getData()
                    ->add(new \DateInterval($addDays))
                    ->format('Y-m-d');
                $lossType = $this->em->getRepository("Merchandise:LossLine")->getlossbyDays($data);
                break;
        }

        return $lossType;
    }
}
