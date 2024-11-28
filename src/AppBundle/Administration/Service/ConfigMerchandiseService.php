<?php
/**
 * Created by PhpStorm.
 * User: hcherif
 * Date: 02/03/2016
 * Time: 17:07
 */

namespace AppBundle\Administration\Service;

use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Entity\Restaurant;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\Translator;

class ConfigMerchandiseService
{
    private $em;
    private $translator;

    public function __construct(EntityManager $entityManager, Translator $translator)
    {
        $this->em = $entityManager;
        $this->translator = $translator;
    }

    public function setSupplierPlannings(Supplier $supplier, $restaurant)
    {
        foreach ($supplier->getPlannings() as $planning) {
            $planning->setSupplier($supplier);
            $planning->setOriginrestaurant($restaurant);
        }
        $this->em->persist($supplier);
        $this->em->flush();
    }

    public function deleteLinePlanning($line)
    {
        if (!$line) {
            throw new NotFoundHttpException('No line found');
        }
        $this->em->remove($line);
        $this->em->flush();

        return true;
    }

    public function getSuppliers($criteria, $order, $limit, $offset, $onlyList = false)
    {
        $suppliers = $this->em->getRepository("Merchandise:Supplier")->getSupplierOrdered(
            $criteria,
            $order,
            $offset,
            $limit,
            $onlyList
        );

        return $this->serializeSuppliers($suppliers);
    }

    public function serializeSuppliers($suppliers)
    {
        $result = [];
        foreach ($suppliers as $s) {
            $result[] = array(
                'code' => $s->getCode(),
                'name' => $s->getName(),
                'designation' => $s->getDesignation(),
                'address' => $s->getAddress(),
                'phone' => $s->getphone(),
                'mail' => $s->getEmail(),
            );
        }

        return $result;
    }

    public function getRestaurants($criteria, $order, $limit, $offset)
    {
        $restaurants = $this->em->getRepository("Merchandise:Restaurant")->getRestaurantOrdered(
            $criteria,
            $order,
            $offset,
            $limit
        );

        return $this->serializeRestaurants($restaurants);
    }

    public function serializeRestaurants($restaurants)
    {
        $result = [];
        foreach ($restaurants as $r) {
            /**
             * @var Restaurant $r
             */

            $suppliers = $r->getSuppliers()->toArray();
            $restaurantSuppliers = [];
            foreach ($suppliers as $supplier) {
                /**
                 * @var Supplier $supplier
                 */
                $restaurantSuppliers[] = $supplier->getName();
            }

            $result[] = array(
                'code' => $r->getCode(),
                'name' => $r->getName(),
                'email' => $r->getEmail(),
                'manager' => $r->getManager(),
                'adress' => $r->getAddress(),
                'phone' => $r->getPhone(),
                'type' => $r->getType(),
                'restaurantSuppliers' => implode(" \n", $restaurantSuppliers),
            );
        }

        return $result;
    }

    public function getInventoryItems($criteria, $order, $limit, $offset, $onlyList = false)
    {
        $inventoryItems = $this->em->getRepository(ProductPurchased::class)->getInventoryItemsOrdered(
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
             * @var ProductPurchased $i
             */
            $result[] = array(
                'id' => $i->getId(),
                'code' => $i->getExternalId(),
                'name' => $i->getName(),
                'buyingCost' => number_format($i->getBuyingCost(), 3, ',', ' '),
                'supplier' => $this->translator->trans($i->getSuppliers()->first()->getName()),
                'statusKey' => $i->getStatus(),
                'status' => $this->translator->trans("status.".$i->getStatus()),
                'secondaryItem' => $i->getSecondaryItem() ? $i->getSecondaryItem()->getName() : '',
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
                'nameFr' => $i->getNameTranslation('fr'),
                'nameNl' => $i->getNameTranslation('nl'),
                'category' => $i->getProductCategory() ? $i->getProductCategory()->getName() : '',
            );
        }

        return $result;
    }
}
