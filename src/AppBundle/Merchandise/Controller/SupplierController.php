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
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Translatable\TranslatableListener;
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
        $suppliers = $this->getDoctrine()->getManager()->getRepository(
            "Merchandise:Supplier"
        )->findAll();

        $return = [];
        foreach ($suppliers as $s) {
            $item = [
                'id'   => $s->getId(),
                'name' => $s->getName(),
                'code' => $s->getCode(),
            ];
            $return[] = $item;
        }

        return new JsonResponse(['data' => $return]);
    }

    /**
     * @param Supplier $supplier
     *
     * @return JsonResponse
     * @Route("/get_product_by_supplier/{supplier}",name="get_product_by_supplier",options={"expose"=true})
     */
    public function getProductsBySupplier(
        Supplier $supplier = null,
        Request $request
    ) {
        $currentRestaurant = $this->get("session")->get("currentRestaurant");

        $locale = \Locale::getDefault();
        $locale = ($locale == 'nl') ? 'nl' : 'fr';
       /* if ($locale != 'nl' && $locale != 'fr') {
            $locale = $this->getOriginRestaurant()->getLang() ? strtolower(
                $this->getOriginRestaurant()->getLang()
            ) : 'fr';
        }*/


        $filter = $request->get('filterSecondary', null);
        $qb = $this->getDoctrine()->getRepository(ProductPurchased::class)
            ->createQueryBuilder('p');
        if ($supplier) {
            $qb->leftJoin('p.suppliers', 's');
            $qb->andWhere('s = :supplier')
                ->setParameter('supplier', $supplier);
        }
        $qb->andWhere("p.originRestaurant = :currentRestaurant")
            ->setParameter("currentRestaurant", $currentRestaurant);

        if (!is_null($filter) && ($filter === true || $filter == 'true')) {
            $qb->andWhere('p.primaryItem is null');
        }

        $qb->addSelect('c.id as category_id,c.name as category_name')
            ->innerJoin('p.productCategory', 'c');

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker');
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
        $products = $query->getArrayResult();
        $return = array();
        foreach ($products as $p) {
            if ($p[0]['status'] != ProductPurchased::INACTIVE) {
                $return[] = array(
                    'code'          => $p[0]['externalId'],
                    'id'            => $p[0]['id'],
                    'name'          => $p[0]['name'],
                    'unitExp'       => $p[0]['labelUnitExped'],
                    'unitInv'       => $p[0]['labelUnitInventory'],
                    'unitUse'       => $p[0]['labelUnitUsage'],
                    'inv_ratio'     => $p[0]['inventoryQty'],
                    'use_ratio'     => $p[0]['usageQty'],
                    'stock'         => $p[0]['stockCurrentQty'],
                    'unit_price'    => $p[0]['buyingCost'],
                    'category_id'   => $p['category_id'],
                    'category_name' => $p['category_name'],

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
     *
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
        } else {
            $currentRestaurant = $this->get("restaurant.service")
                ->getCurrentRestaurant();

            /**
             * @var QueryBuilder $qb
             */
            $locale = \Locale::getDefault();
            $qb = $this->getDoctrine()->getRepository(SupplierPlanning::class)
                ->createQueryBuilder('pl');
            $qb->where('pl.supplier=:supplier')
                ->andWhere('pl.originRestaurant = :currentRestaurant')
                ->setParameter('supplier', $supplier)
                ->setParameter('currentRestaurant', $currentRestaurant);
            $qb->addSelect('partial c.{id,name}')
                ->innerJoin('pl.categories', 'c');
            $query=$qb->getQuery();
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker');
            $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
            $plannings = $query->getArrayResult();

            $return = [];

            foreach ($plannings as $planning) {

                $cat = [];
                foreach ($planning['categories'] as $category) {
                    $cat[] = [
                        "id"   => $category["id"],
                        "name" => $category["name"],
                    ];
                }

                $return[] = [
                    "order"      => $planning["orderDay"],
                    "delivery"   => $planning["deliveryDay"],
                    "categories" => $cat,
                ];

            }



            return new JsonResponse(array('data' => $return));

        }
    }

    /**
     * @param Supplier $supplier
     *
     * @return JsonResponse
     * @Route("/pendings_order/{supplier}",name="pendings_orders_by_supplier",options={"expose"=true})
     */
    public function getPendingsOrders(Supplier $supplier = null)
    {
        $locale = \Locale::getDefault();
        $currentRestaurant = $this->get("restaurant.service")
            ->getCurrentRestaurant();
        if ($supplier == null) {
            return new JsonResponse(array('data' => []));
        }

        $orders = $this->getDoctrine()->getRepository("Merchandise:Order")
            ->getPendingsOrderBySupplier($supplier, $currentRestaurant,$locale);

        $orders = $this->get('order.service')->serializeList($orders);

        return new JsonResponse(array('data' => $orders));
    }

    /**
     * @param Supplier $supplier
     *
     * @return JsonResponse
     * @Route("/get_next_planning/{supplier}",name="get_next_planning",options={"expose"=true})
     */
    public function getNextOrderDate(Supplier $supplier = null)
    {
        $locale = \Locale::getDefault();
        if ($supplier == null) {
            return new JsonResponse(array('data' => null));
        }

        if ($supplier->isActive() === false
            || count($supplier->getPlannings()) === 0
        ) {
            return new JsonResponse(
                array(
                    'data' => null,
                )
            );
        }
        $currentRestaurant = $this->get("restaurant.service")
            ->getCurrentRestaurant();
        $pendingsOrder = $this->getDoctrine()->getRepository(
            "Merchandise:Order"
        )->getPendingsOrderBySupplier(
            $supplier,
            $currentRestaurant,
            $locale
        );
        $excludeOrders = [];

        foreach ($pendingsOrder as $o) {
            $excludeOrders[] = $o->getDateOrder();
        }

        $today = new \DateTime('NOW');
        $today->setTime(0, 0, 0);
        $date = $this->get('order.service')->getNextOrderDate(
            $supplier,
            $today,
            $excludeOrders
        );

        $planning = $supplier->getPlannings()->filter(
            function (SupplierPlanning $sp) use ($date, $currentRestaurant) {

                $d = intval($date->format('w'));

                if ($sp->getOriginRestaurant() === $currentRestaurant
                    && $sp->getOrderDay() === $d
                ) {
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
                    'order'    => $date->format('d/m/Y'),
                    'delivery' => $delivery->format('d/m/Y'),
                ),
            )
        );
    }
}
