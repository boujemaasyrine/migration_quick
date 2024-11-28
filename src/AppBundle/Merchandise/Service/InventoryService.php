<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 18/02/2016
 * Time: 14:41
 */

namespace AppBundle\Merchandise\Service;

use AppBundle\General\Exception\OperationCannotBeDoneException;
use AppBundle\Merchandise\Entity\InventoryLine;
use AppBundle\Merchandise\Entity\InventorySheet;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\ProductPurchasedHistoric;
use AppBundle\Merchandise\Entity\ProductPurchasedMvmt;
use AppBundle\Merchandise\Entity\Restaurant;
use AppBundle\Merchandise\Entity\SheetModelLine;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Translation\Translator;
use Monolog\Logger;
use AppBundle\ToolBox\Service\CommandLauncher;

class InventoryService
{

    private $em;
    private $tokenStorage;
    private $translator;
    private $productPurchasedMvmtService;
    private $logger;
    private $commandLauncher;
    /**
     * @var ProductService
     */
    private $productService;

    public function __construct(
        EntityManager $entityManager,
        TokenStorage $tokenStorage,
        Translator $translator,
        ProductPurchasedMvmtService $productPurchasedMvmtService,
        ProductService $productService,
        Logger $logger,
        CommandLauncher $commandLauncher
    ) {

        $this->em = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->productPurchasedMvmtService = $productPurchasedMvmtService;
        $this->productService = $productService;
        $this->logger = $logger;
        $this->commandLauncher = $commandLauncher;
    }

    public function setProductPurchasedHistorical(InventorySheet $inventorySheet)
    {
        // Fetch historic product if there
        if (count($inventorySheet->getLines())) {
            foreach ($inventorySheet->getLines() as $line) {
                /**
                 * @var InventoryLine $line
                 */
                if ($line->getProduct()) {
                    $histoP = $this->productService->getHistoricProductAsEntity(
                        $line->getProduct(),
                        $inventorySheet->getFiscalDate()
                    );
                    if ($histoP instanceof ProductPurchasedHistoric) {
                        $line->setProductPurchasedHistoric($histoP);
                    }
                }
            }
        }
    }

    /**
     * @param InventorySheet $inventorySheet
     * @param bool           $validated
     * @return InventorySheet
     * @throws OperationCannotBeDoneException
     */
    public function saveInventorySheet($restaurant, InventorySheet $inventorySheet)
    {
        $inventorySheet->setOriginRestaurant($restaurant);
        // InventorySheet can be updated only if createdAt today
        $date = new \DateTime();
        $createdAt = $inventorySheet->getCreatedAt();
        $interval = $date->diff($createdAt);
        if (is_null($inventorySheet->getId()) || $interval->days == 0) {
            $inventorySheet->setStatus(InventorySheet::INVENTORY_VALIDATED);
            $inventorySheet->setEmployee($this->tokenStorage->getToken()->getUser());
            if (is_null($inventorySheet->getId())) {
                // creation
                $this->setProductPurchasedHistorical($inventorySheet);
                $this->em->persist($inventorySheet);
                $this->em->flush();
                $this->productPurchasedMvmtService->createMvmtEntryForInventorySheet($inventorySheet,$restaurant, true);
                $this->em->flush();
            } else {
                // update (to draft or to be validated)
                $temp = $this->em->merge($inventorySheet);
                foreach ($inventorySheet->getLines() as $line) {
                    if ($temp->getLines()->contains($line)) {
                        $key = $temp->getLines()->indexOf($line);
                        $temp->getLines()->set($key, $line);
                    } else {
                        $temp->addLine($line);
                    }
                }
                foreach ($temp->getLines() as $line) {
                    if (!$inventorySheet->getLines()->contains($line)) {
                        $temp->removeLine($line);
                        $this->em->remove($line);
                    }
                }
                $this->setProductPurchasedHistorical($temp);
                $this->em->flush();
                foreach ($inventorySheet->getLines() as $line) {
                    $this->productPurchasedMvmtService->deleteMvmtEntriesByTypeAndSourceId(
                        ProductPurchasedMvmt::INVENTORY_TYPE,
                        $line->getId(),
                        $restaurant
                    );
                }
                $this->productPurchasedMvmtService->createMvmtEntryForInventorySheet($inventorySheet, $restaurant,false);
                $this->em->flush();
            }

            return $inventorySheet;
        } else {
            throw new OperationCannotBeDoneException('validations.only_today_inventory_sheet_can_be_updated');
        }
    }

    public function createZeroInventoryLineForProduct(ProductPurchased $product, Restaurant $restaurant)
    {
        $inventory = new InventorySheet();
        $inventory->setFiscalDate(new \DateTime())
                  ->setOriginRestaurant($restaurant);
        $line = new InventoryLine();
        $line->setInventorySheet($inventory)
            ->setInventoryCnt(0)
            ->setTotalInventoryCnt(0)
            ->setProduct($product);
        $inventory->addLine($line);
        $this->em->persist($inventory);
        $this->productPurchasedMvmtService->createMvmtEntryForInventorySheet($inventory, $restaurant, false);

        return $inventory;
    }

    /**
     * @param InventorySheet $inventorySheet
     * @return InventorySheet
     */
    public function loadInventorySheet(InventorySheet $inventorySheet)
    {
        $selectedModel = $inventorySheet->getSheetModel();
        $inventorySheet->setLines(new ArrayCollection());
        if (is_null($inventorySheet->getId()) && !is_null($selectedModel)) {
            foreach ($selectedModel->getLines() as $line) {
                /**
                 * @var SheetModelLine $line
                 */
                $inventoryLine = [
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

                $inventorySheet->addLine($inventoryLine);
            };
        }

        return $inventorySheet;
    }

    /**
     * @param $search
     * @param $order
     * @param $start
     * @param $pageSize
     * @return array
     */
    public function getInventories($search, $order, $start, $pageSize)
    {
        $results = [
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => [],
        ];
        $types = [];
        $statusList = [];

        if (!empty($search)) {
            foreach (InventorySheet::$inventoryStatus as $statu) {
                if (strpos(strtolower($this->translator->trans($statu)), strtolower($search)) !== false) {
                    $statusList[] = $statu;
                }
            }
        }
        $sheets = $this->em->getRepository('Merchandise:InventorySheet')->getInventorySheets(
            $search,
            $order,
            $start,
            $pageSize,
            $types,
            $statusList
        );
        $results['recordsTotal'] = $this->em
            ->getRepository('Merchandise:InventorySheet')->getInventorySheetsCount();
        $results['recordsFiltered'] = count($sheets);
        $results['data'] = $sheets;

        return $results;
    }

    /**
     * @param $search
     * @param $order
     * @param $start
     * @param $pageSize
     * @return array
     */
    public function getCreatedTodayInventories($restaurant,$draw, $search,$criteria, $order, $start, $pageSize)
    {
        $results = [
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            "data" => [],
        ];
        $types = [];
        $statusList = [];

        if (!empty($search)) {
            foreach (InventorySheet::$inventoryStatus as $statu) {
                if (strpos(strtolower($this->translator->trans($statu)), strtolower($search)) !== false) {
                    $statusList[] = $statu;
                }
            }
        }
        $sheets = $this->em->getRepository('Merchandise:InventorySheet')->getCreatedTodayInventorySheets(
            $restaurant,
            $search,
            $criteria,
            $order,
            $start,
            $pageSize,
            $types,
            $statusList
        );



        $results['recordsTotal'] = $this->em
            ->getRepository('Merchandise:InventorySheet')->getInventorySheetsCount($restaurant);
        $results['recordsFiltered'] = count($sheets);
        $results['draw']=$draw;
        $results['data'] = $sheets;

        return $results;
    }

    public function UpdateMFCforInventory($restaurant, InventorySheet $inventory)
    {
        $fiscalDate = $this->em->getRepository("Administration:Parameter")->findOneBy(
            array(
                'type' => 'date_fiscale',
                'originRestaurant' => $restaurant,
            )
        )->getValue();
        if ($inventory->getFiscalDate()->format('d/m/Y') != $fiscalDate) {
            $command = 'report:marge:foodcost '.$inventory->getOriginRestaurant()->getId().' '.$inventory->getFiscalDate()->format(
                'Y-m-d'
            ).' '.$inventory->getFiscalDate()->format('Y-m-d');
            $this->commandLauncher->execute($command, true, false, true);
            $this->logger->info(
                'Updating Marge FC with success for date :'.$inventory->getFiscalDate()->format('Y-m-d'),
                ['InventoryService']
            );
        }
    }
}
