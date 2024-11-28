<?php
/**
 * Created by PhpStorm.
 * User: anouira
 * Date: 23/02/2016
 * Time: 10:38
 */

namespace AppBundle\Merchandise\Controller;

use AppBundle\Merchandise\Entity\Order;
use AppBundle\ToolBox\Utils\Utilities;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Merchandise\Entity\Delivery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class OrderAPIController
 *
 * @package               AppBundle\Merchandise\Controller
 * @Route("/json/orders")
 */
class OrderAPIController extends Controller
{
    /**
     * @param Request $request
     * @param int $download
     * @return JsonResponse
     * @Route("/list/{download}",name="pending_list",options={"expose"=true})
     */
    public function pendingListAction(Request $request, $download = 0)
    {

        $orders = ['num_cmd', 'supplier', 'date_order', 'date_delivery', 'responsible', 'status'];
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);
        $dataTableHeaders['criteria']['restaurant'] = $this->get('restaurant.service')->getCurrentRestaurant();

        $download = intval($download);
        if ($download === 1) {
            $translator = $this->get('translator');
            $filepath = $this->get('toolbox.document.generator')
                ->generateCsvFile(
                    'order.service',
                    'getList',
                    array(
                        'criteria' => $dataTableHeaders['criteria'],
                        'order' => $dataTableHeaders['orderBy'],
                    ),
                    ['N° Commande', 'Fournisseur', 'Date Commande', 'Date Livraison', 'Responsable', 'Statut'],
                    function ($line) use ($translator) {
                        return [
                            ($line['numOrder'] == null || trim($line['numOrder']) == '') ? 'N/A' : $line['numOrder'],
                            $line['supplier'],
                            $line['dateOrder'],
                            $line['dateDelivery'],
                            $line['responsible'],
                            $translator->trans($line['status'], [], 'order_status'),
                        ];
                    }
                );

            $response = Utilities::createFileResponse($filepath, 'commandes_en_cours_'.date('dmY_His').".csv");

            return $response;
        } else {
            if ($download === 2) {
                $logoPath = $this->get('kernel')->getRootDir().'/../web/src/images/logo.png';
                $response = $this->get('order.service')->generateExcelFile(
                    $dataTableHeaders['criteria'],
                    $dataTableHeaders['orderBy'],
                    $logoPath
                );

                return $response;
            }
        }

        $list = $this->getDoctrine()
            ->getRepository("Merchandise:Order")
            ->getList(
                false,
                $dataTableHeaders['criteria'],
                $dataTableHeaders['orderBy'],
                $dataTableHeaders['offset'],
                $dataTableHeaders['limit']
            );

        return new JsonResponse(
            array(
                'draw' => $dataTableHeaders['draw'],
                'data' => $this->get('order.service')->serializeList($list['data']),
                'recordsTotal' => $list['total'],
                'recordsFiltered' => $list['filteredCount'],
            )
        );
    }

    /**
     * @param Order $order
     * @return JsonResponse
     * @Route("/order_lines/{order}",name="order_lines",options={"expose"=true})
     */
    public function getOrderLinesAction(Order $order)
    {
        $lines = $order->getLines();

        return new JsonResponse(array('data' => $this->get('order.service')->serializeOrderLines($lines)));
    }

    /**
     * @param Request $request
     * @param int $download
     * @return JsonResponse
     * @Route("/deliveries_list/{download}",name="deliveries_list", options={"expose"=true})
     */
    public function deliveredListAction(Request $request, $download = 0)
    {
        $orders = array('num_delivery', 'supplier', 'order_date', 'delivery_date', 'valorization', 'responsible');
        $dataTableHeaders = Utilities::getDataTableHeader($request, $orders);
        $dataTableHeaders['criteria']['restaurant'] = $this->get('restaurant.service')->getCurrentRestaurant();

        $download = intval($download);
        if ($download === 1) {
            $filepath = $this->get('toolbox.document.generator')
                ->generateCsvFile(
                    'order.service',
                    'getDeliveries',
                    array(
                        'criteria' => $dataTableHeaders['criteria'],
                        'order' => $dataTableHeaders['orderBy'],
                        'onlyList' => true,
                        'serialize' => true,
                    ),
                    [
                        'Fournisseur',
                        'Date du commande',
                        'Date de la livraison',
                        'Date de livraison prévue',
                        'Valorisation',
                    ],
                    function ($row, $n) {
                        return array(
                            'Fournisseur' => $row['order']['supplier'],
                            'Date du commande' => $row['order']['dateOrder'],
                            'Date de la livraison' => $row['date'],
                            'Date de livraison prévue' => $row['order']['dateDelivery'],
                            'Valorisation' => number_format($row['valorization'], 2, ',', ''),
                        );
                    }
                );

            $response = Utilities::createFileResponse($filepath, 'livraisons_'.date('dmY_His').".csv");

            return $response;
        } else {
            if ($download === 2) {
                $logoPath = $this->get('kernel')->getRootDir().'/../web/src/images/logo.png';
                $response = $this->get('order.service')->generateDeliveriesExcelFile(
                    $dataTableHeaders['criteria'],
                    $dataTableHeaders['orderBy'],
                    $logoPath
                );

                return $response;
            }
        }

        $deliveries = $this->getDoctrine()->getRepository("Merchandise:Delivery")->getList(
            $dataTableHeaders['criteria'],
            $dataTableHeaders['orderBy'],
            $dataTableHeaders['offset'],
            $dataTableHeaders['limit']
        );
        $return['draw'] = $dataTableHeaders['draw'];
        $return['recordsFiltered'] = $deliveries['filtred'];
        $return['recordsTotal'] = $deliveries['total'];
        $return['data'] = $this->get('order.service')->serializeDeliveries($deliveries['list']);

        return new JsonResponse($return);
    }
}
