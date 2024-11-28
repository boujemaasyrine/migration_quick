<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 19/02/2016
 * Time: 10:09
 */

namespace AppBundle\Merchandise\Controller;

use AppBundle\Merchandise\Entity\Order;
use AppBundle\Merchandise\Entity\ProductPurchased;
use AppBundle\Merchandise\Entity\Supplier;
use AppBundle\Merchandise\Entity\SupplierPlanning;
use Doctrine\ORM\NoResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SupplierController
 *
 * @package            AppBundle\Merchandise\Controller
 * @Route("/supplier")
 */
class SupplierController extends Controller
{

    /**
     * @return JsonResponse
     * @Route("/list",name="find_suppliers",options={"expose"=true})
     */
    public function find()
    {
        $suppliers = $this->getDoctrine()->getManager()->getRepository("Merchandise:Supplier")->findAll();

        $return = [];
        foreach ($suppliers as $s) {
            $item = ['id' => $s->getId(), 'name' => $s->getName(), 'code' => $s->getCode()];
            $return[] = $item;
        }

        return new JsonResponse(['data' => $return]);
    }

    /**
     * @param Supplier $supplier
     * @return JsonResponse
     * @Route("/get_product_by_supplier/{supplier}",name="get_product_by_supplier",options={"expose"=true})
     */
    public function getProductsBySupplier(Supplier $supplier = null, Request $request)
    {
        $currentRestaurant = $this->get("session")->get("currentRestaurant");
        $filter = $request->get('filterSecondary', null);
        $qb = $this->getDoctrine()->getRepository(ProductPurchased::class)->createQueryBuilder('p');
        if ($supplier) {
            //$qb->where('p.supplier = :supplier')->setParameter('supplier', $supplier);
            $qb->leftJoin('p.suppliers', 's');
            $qb->andWhere('s = :supplier')
                ->setParameter('supplier', $supplier);
        }
        $qb->andWhere("p.originRestaurant = :currentRestaurant")
            ->setParameter("currentRestaurant", $currentRestaurant);

        if (!is_null($filter) && ($filter === true || $filter == 'true')) {
            $qb->andWhere('p.primaryItem is null');
        }

        $products = $qb->getQuery()->getResult();

        $return = array();
        foreach ($products as $p) {
            if ($p->getStatus() != ProductPurchased::INACTIVE) {
                $return[] = array(
                    'code' => $p->getExternalId(),
                    'id' => $p->getId(),
                    'name' => $p->getName(),
                    'unitExp' => $p->getLabelUnitExped(),
                    'unitInv' => $p->getLabelUnitInventory(),
                    'unitUse' => $p->getLabelUnitUsage(),
                    'inv_ratio' => $p->getInventoryQty(),
                    'use_ratio' => $p->getUsageQty(),
                    'stock' => $p->getStockCurrentQty(),
                    'unit_price' => $p->getBuyingCost(),
                    'category_id' => $p->getProductCategory()->getId(),
                    'category_name' => $p->getProductCategory()->getName(),

                );
            }
        }

        return new JsonResponse(
            array(
                'data' => $return,
            )
        );
    }

    /**
     * @param Supplier $supplier
     * @return JsonResponse
     * @Route("/json/supplier_planning/{supplier}",name="supplier_planning_json",options={"expose"=true})
     */
    public function getPlanningsAction(Supplier $supplier = null)
    {

        if ($supplier == null) {
            return new JsonResponse(
                array(
                    'data' => [],
                )
            );
        }
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $plannings = $supplier->getPlannings()->filter(function (SupplierPlanning $item) use($currentRestaurant){
            return $item->getOriginRestaurant() === $currentRestaurant ? true: false;
        });
        $return = [];
        foreach ($plannings as $p) {
            $cat = [];
            foreach ($p->getCategories() as $c) {
                $cat[] = array(
                    'id' => $c->getId(),
                    'name' => $c->getName(),
                );
            }

            $return[] = array(
                'order' => $p->getOrderDay(),
                'delivery' => $p->getDeliveryDay(),
                'categories' => $cat,
            );
        }

        return new JsonResponse(array('data' => $return));
    }

    /**
     * @param Supplier $supplier
     * @return JsonResponse
     * @Route("/pendings_order/{supplier}",name="pendings_orders_by_supplier",options={"expose"=true})
     */
    public function getPendingsOrders(Supplier $supplier = null)
    {
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        if ($supplier == null) {
            return new JsonResponse(array('data' => []));
        }

        $orders = $this->getDoctrine()->getRepository("Merchandise:Order")->getPendingsOrderBySupplier($supplier,$currentRestaurant);
        $orders = $this->get('order.service')->serializeList($orders);

        return new JsonResponse(array('data' => $orders));
    }

    /**
     * @param Supplier $supplier
     * @return JsonResponse
     * @Route("/get_next_planning/{supplier}",name="get_next_planning",options={"expose"=true})
     */
    public function getNextOrderDate(Supplier $supplier = null)
    {

        if ($supplier == null) {
            return new JsonResponse(array('data' => null));
        }

        if ($supplier->isActive() === false || count($supplier->getPlannings()) === 0) {
            return new JsonResponse(
                array(
                    'data' => null,
                )
            );
        }
        $currentRestaurant = $this->get("restaurant.service")->getCurrentRestaurant();
        $pendingsOrder = $this->getDoctrine()->getRepository("Merchandise:Order")->getPendingsOrderBySupplier(
            $supplier, $currentRestaurant
        );
        $excludeOrders = [];

        foreach ($pendingsOrder as $o) {
            $excludeOrders[] = $o->getDateOrder();
        }

        $today = new \DateTime('NOW');
        $today->setTime(0, 0, 0);
        $date = $this->get('order.service')->getNextOrderDate($supplier, $today, $excludeOrders);

        $planning = $supplier->getPlannings()->filter(
            function (SupplierPlanning $sp) use ($date, $currentRestaurant) {

                $d = intval($date->format('w'));

                if ($sp->getOriginRestaurant() === $currentRestaurant && $sp->getOrderDay() === $d) {
                    return true;
                }

                return false;
            }
        );

        $planning = $planning->first();

        $diff = $planning->getDeliveryDay() - $planning->getOrderDay();

        if ($diff < 0) {
            $diff = 7 + $diff;
        }

        $deliveryTimestamp = mktime(
            0,
            0,
            0,
            intval($date->format('m')),
            intval($date->format('d')) + $diff,
            intval($date->format('Y'))
        );
        $delivery = new \DateTime();
        $delivery->setTimestamp($deliveryTimestamp);

        return new JsonResponse(
            array(
                'data' => array(
                    'order' => $date->format('d/m/Y'),
                    'delivery' => $delivery->format('d/m/Y'),
                ),
            )
        );
    }
}
